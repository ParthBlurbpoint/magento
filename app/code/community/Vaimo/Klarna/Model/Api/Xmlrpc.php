<?php
/**
 * Copyright (c) 2009-2014 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_Klarna
 * @copyright   Copyright (c) 2009-2014 Vaimo AB
 */

require_once Mage::getBaseDir('lib') . '/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
require_once Mage::getBaseDir('lib') . '/Klarna/Klarna.php';
require_once Mage::getBaseDir('lib') . '/Klarna/pclasses/mysqlstorage.class.php';


class Vaimo_Klarna_Model_Api_Xmlrpc extends Vaimo_Klarna_Model_Api_Abstract
{
    protected $_klarnaApi = NULL;

    protected $_checkoutServiceResult = NULL;

    public function __construct($klarnaApi = null)
    {
        parent::__construct();

        $this->_klarnaApi = $klarnaApi;
        if ($this->_klarnaApi == null) {
            $this->_klarnaApi = new Klarna();
        }
    }

    /**
     * Build the PClass URI
     *
     * @return array
     */
    protected function _getPCURI()
    {
        $mageConfig = Mage::getResourceModel('sales/order')->getReadConnection()->getConfig();
        return array(
            "user"      => $mageConfig['username'],
            "passwd"    => $mageConfig['password'],
            "dsn"       => $mageConfig['host'],
            "db"        => $mageConfig['dbname'],
            "table"     => "klarnapclasses"
        );
    }

    protected function _klarnaReservationStatusToCode($status)
    {
        switch ($status) {
            case KlarnaFlags::ACCEPTED:
                $res = Vaimo_Klarna_Helper_Data::KLARNA_STATUS_ACCEPTED;
                break;
            case KlarnaFlags::PENDING:
                $res = Vaimo_Klarna_Helper_Data::KLARNA_STATUS_PENDING;
                break;
            case KlarnaFlags::DENIED:
                $res = Vaimo_Klarna_Helper_Data::KLARNA_STATUS_DENIED;
                break;
            default:
                $res = 'unknown_' . $status;
                break;
        }
        return $res;
    }

    protected function _setCaptureFlags($sendEmail = false)
    {
        $res = NULL;
        if ($sendEmail) {
            $res = KlarnaFlags::RSRV_SEND_BY_EMAIL;
        }
        return $res;
    }

    /**
     * Klarna supports three different types of refunds, full, part and amount
     * If any additional amount is specified, it will refund amount wise
     * If The entire amount is refunded, it will be a full refund
     * If above alternatives are false, it will refund part (meaning per item)
     * The exception to this is if you have discounts, then the orderline in Klarna
     * is the non-discounted one, followed by a total discount amount line.
     * Then it is impossible to refund part, then we need the amount refund.
     *
     * @param float $amount The amount to refund
     *
     * @return string One of the const methods defined above
     */
    protected function _decideRefundMethod($model, $amount)
    {
        $res = Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_PART;
        $remaining = $model->getOrder()->getTotalInvoiced() - $model->getOrder()->getTotalOnlineRefunded(); // - $model->getOrder()->getShippingRefunded();
        if (abs($remaining - $amount) < 0.00001) {
            $res = Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_FULL;
        } else {
            if ($model->getCreditmemo()->getAdjustmentPositive()!=0 || $model->getCreditmemo()->getAdjustmentNegative()!=0) {
                $res = Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_AMOUNT;
            } else {
                foreach ($model->getExtras() as $extra) {
                    if (isset($extra['flags'])) {
                        switch ($extra['flags']) {
                            case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_HANDLING_FEE:
                                if ($model->getCreditmemo()->getVaimoKlarnaFeeRefund()>0) {
                                    if (isset($extra['original_price'])) {
                                        if ($extra['original_price']!=$extra['price']) { // If not full shipping refunded, it will use refund amount instead
                                            $res = Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_AMOUNT;
                                        }
                                    }
                                }
                                break;
                            case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_SHIPPING_FEE;
                                if ($model->getCreditmemo()->getShippingAmount()>0) {
                                    if (isset($extra['original_price'])) {
                                        if ($extra['original_price']!=$extra['price']) { // If not full shipping refunded, it will use refund amount instead
                                            $res = Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_AMOUNT;
                                        }
                                    }
                                }
                                break;
                            case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_NORMAL:
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
            $discount_amount = 0;
            foreach ($model->getOrder()->getItemsCollection() as $item) {
                $discount_amount += $item->getDiscountAmount();
            }
            if ($discount_amount) {
                $res = Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_AMOUNT;
            }
        }
        return $res;
    }

    /**
     * Set the company reference for the purchase
     *
     * @param KlarnaAddr $shipping Klarna shipping address
     * @param KlarnaAddr $billing  Klarna billing address
     *
     * @return void
     */
    protected function _setReference($data, $shipping, $billing)
    {
        $reference = null;
        if (array_key_exists("reference", $data)) {
            $reference = $data["reference"];
        } elseif ($billing->isCompany) {
            $reference = $shipping->getFirstName() . " " . $shipping->getLastName();
        } elseif ($shipping->isCompany) {
            $reference = $billing->getFirstName() . " " . $billing->getLastName();
        }

        if (strlen($reference) == 0) {
            return;
        }
        $reference = html_entity_decode(trim($reference), ENT_COMPAT, 'ISO-8859-1');
        $this->_klarnaApi->setReference($reference, "");
        $this->_klarnaApi->setComment("Ref:{$reference}");
    }

    /**
     * Split a string into an array consisting of Street, House Number and
     * House extension.
     *
     * @param string $address Address string to split
     *
     * @return array
     */
    protected static function _splitAddress($address)
    {
        // Get everything up to the first number with a regex
        $hasMatch = preg_match('/^[^0-9]*/', $address, $match);

        // If no matching is possible, return the supplied string as the street
        if (!$hasMatch) {
            return array($address, "", "");
        }

        // Remove the street from the address.
        $address = str_replace($match[0], "", $address);
        $street = trim($match[0]);

        // Nothing left to split, return
        if (strlen($address) == 0) {
            return array($street, "", "");
        }
        // Explode address to an array
        $addrArray = explode(" ", $address);

        // Shift the first element off the array, that is the house number
        $housenumber = array_shift($addrArray);

        // If the array is empty now, there is no extension.
        if (count($addrArray) == 0) {
            return array($street, $housenumber, "");
        }

        // Join together the remaining pieces as the extension.
        $extension = implode(" ", $addrArray);

        return array($street, $housenumber, $extension);
    }

    /**
     * Get the formatted street required for a Klarna Addr
     *
     * @param string $street The street to split
     * @param array  $split  An array determining the parts of the split
     *
     * @return array
     */
    protected function _splitStreet($street)
    {
        $split = $this->_getTransport()->getSplit();
        $result = array(
            'street' => '',
            'house_extension' => '',
            'house_number' => ''
        );
        $elements = $this->_splitAddress($street);
        $result['street'] = $elements[0];

        if (in_array('house_extension', $split)) {
            $result['house_extension'] = $elements[2];
        } else {
            $elements[1] .= ' ' . $elements[2];
        }

        if (in_array('house_number', $split)) {
            $result['house_number'] = $elements[1];
        } else {
            $result['street'] .= ' ' . $elements[1];
        }

        return array_map('trim', $result);
    }

    /**
     * Add an article to the goods list and pad it with default values
     *
     * Keys : qty, sku, name, price, tax, discount, flags
     *
     * @param array $array The array to use
     *
     * @return void
     */
    protected function _addArticle($array)
    {
        $default = array(
            "qty" => 0,
            "sku" => "",
            "name" => "",
            "price" => 0,
            "tax" => 0,
            "discount" => 0,
            "flags" => KlarnaFlags::NO_FLAG
        );

        //Filter out null values and overwrite the default values
        $values = array_merge($default, array_filter($array));

        switch ($values['flags']) {
            case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_NORMAL:
                $values['flags'] = KlarnaFlags::INC_VAT;
                break;
            case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_SHIPPING_FEE:
                $values['flags'] = KlarnaFlags::INC_VAT | KlarnaFlags::IS_SHIPMENT;
                break;
            case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_HANDLING_FEE:
                $values['flags'] = KlarnaFlags::INC_VAT | KlarnaFlags::IS_HANDLING;
                break;
        }

        $this->_klarnaApi->addArticle(
            $values["qty"],
            Mage::helper('klarna')->encode($values["sku"]),
            Mage::helper('klarna')->encode($values["name"]),
            $values["price"],
            $values["tax"],
            $values["discount"],
            $values["flags"]
        );
        Mage::helper('klarna')->logDebugInfo('addArticle', $values);
    }

    /*
     * Add an article to the goods list and pad it with default values
     *
     * Keys : qty, sku, name, price, tax, discount, flags
     *
     * @param array $array The array to use
     *
     * @return void
     */
    protected function _addArtNo($array)
    {
        $default = array(
            "qty" => 0,
            "sku" => ""
        );

        //Filter out null values and overwrite the default values
        $values = array_merge($default, array_filter($array));

        $this->_klarnaApi->addArtNo(
            intval($values["qty"]),
            strval(Mage::helper('klarna')->encode($values["sku"]))
            );
        Mage::helper('klarna')->logDebugInfo('addArticle', $values);
    }

    /**
     * Get a unique key used to identify the given address
     *
     * The key is a hash of the lower bit ascii portion of company name,
     * first name, last name and street joined with pipes
     *
     * @param KlarnaAddr $klarnaAddr address
     *
     * @return string key for this address
     */
    protected static function _getAddressKey($klarnaAddr)
    {
        return hash(
            'crc32',
            preg_replace(
                '/[^\w]*/', '',
                $klarnaAddr->getCompanyName() . '|' .
                $klarnaAddr->getFirstName() . '|' .
                $klarnaAddr->getLastName() . '|' .
                $klarnaAddr->getStreet()
            )
        );
    }

    protected function _filterPClasses($method, $pclasses, $page, $amount)
    {
        $types = array();
        switch ($method) {
            case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT:
                $types = array(KlarnaPClass::ACCOUNT,
                        KlarnaPClass::CAMPAIGN,
                        KlarnaPClass::FIXED
                    );
                break;
            case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL:
                $types = array(KlarnaPClass::SPECIAL,
                        KlarnaPClass::DELAY
                    );
                break;
        }
        foreach ($pclasses as $id => $pclass) {
            $type = $pclass->getType();
            $monthlyCost = -1;

            if (!in_array($pclass->getType(), $types)) {
                unset($pclasses[$id]);
                continue;
            }

            if ($pclass->getMinAmount()) {
                if ($amount < $pclass->getMinAmount()) {
                    unset($pclasses[$id]);
                    continue;
                }
            }


            if (in_array($type, array(KlarnaPClass::FIXED, KlarnaPClass::DELAY, KlarnaPClass::SPECIAL))) {
                if ($page == KlarnaFlags::PRODUCT_PAGE) {
                    unset($pclasses[$id]);
                    continue;
                }
            } else {
                $lowestPayment = KlarnaCalc::get_lowest_payment_for_account( $pclass->getCountry() );
                $monthlyCost = KlarnaCalc::calc_monthly_cost( $amount, $pclass, $page );
                if ($monthlyCost < 0.01) {
                    unset($pclasses[$id]);
                    continue;
                }

                if ($monthlyCost < $lowestPayment) {
                    if ($type == KlarnaPClass::CAMPAIGN) {
                        unset($pclasses[$id]);
                        continue;
                    }
                    if ($page == KlarnaFlags::CHECKOUT_PAGE && $type == KlarnaPClass::ACCOUNT) {
                        $monthlyCost = $lowestPayment;
                    }
                }
            }
        }
        return $pclasses;
    }
    
    /*
     * Same logic as function above, but it's easier to read if these functions are split...
     *
     */
    protected function _getPClassMinimum($pclasses, $page, $amount)
    {
        $minimum = NULL;
        $minval = NULL;
        foreach ($pclasses as $pclass) {
            $type = $pclass->getType();
            $monthlyCost = -1;

            if (!in_array($type, array(KlarnaPClass::FIXED, KlarnaPClass::SPECIAL))) {
                $lowestPayment = KlarnaCalc::get_lowest_payment_for_account( $pclass->getCountry() );
                $monthlyCost = KlarnaCalc::calc_monthly_cost( $amount, $pclass, $page );

                if ($monthlyCost < $lowestPayment) {
                    if ($page == KlarnaFlags::CHECKOUT_PAGE && $type == KlarnaPClass::ACCOUNT) {
                        $monthlyCost = $lowestPayment;
                    }
                }
            }

            if ($minimum === null || $minval > $monthlyCost) {
                $minimum = $pclass;
                $minval = $monthlyCost;
            }

        }
        return $minimum;
    }
    
    protected function _getDefaultPClass($pclasses)
    {
        $default = NULL;
        foreach ($pclasses as $pclass) {
            $type = $pclass->getType();

            if ($type == KlarnaPClass::ACCOUNT) {
                $default = $pclass;
            } else if ($type == KlarnaPClass::CAMPAIGN) {
                if ($default === NULL || $default->getType() != KlarnaPClass::ACCOUNT) {
                    $default = $pclass;
                }
            } else { 
                if ($default === NULL) {
                    $default = $pclass;
                }
            }
        }
        return $default;
    }
    
    protected function _PClassToArray($pclass, $amount, $default = NULL, $minimum = NULL)
    {
        $type = $pclass->getType();
        $monthlyCost = -1;

        if (!in_array($type, array(KlarnaPClass::FIXED, KlarnaPClass::SPECIAL))) {
            $lowestPayment = KlarnaCalc::get_lowest_payment_for_account( $pclass->getCountry() );
            $monthlyCost = KlarnaCalc::calc_monthly_cost( $amount, $pclass, KlarnaFlags::CHECKOUT_PAGE );

            if ($monthlyCost < $lowestPayment) {
                if ($type == KlarnaPClass::ACCOUNT) {
                    $monthlyCost = $lowestPayment;
                }
            }
        }
        $totalCost = KlarnaCalc::total_credit_purchase_cost($amount, $pclass, KlarnaFlags::CHECKOUT_PAGE);

        $pclassArr = $pclass->toArray();
        if ($default) {
            if ($pclass==$default) {
                $pclassArr['default'] = true;
            } else {
                $pclassArr['default'] = false;
            }
        } else {
            $pclassArr['default'] = NULL;
        }

        if ($minimum) {
            if ($pclass==$minimum) {
                $pclassArr['cheapest'] = true;
            } else {
                $pclassArr['cheapest'] = false;
            }
        } else {
            $pclassArr['cheapest'] = NULL;
        }
        $pclassArr['monthly_cost'] = $monthlyCost;
        $pclassArr['total_cost'] = $totalCost;
        return $pclassArr;
    }
    
    public function init($klarnaSetup)
    {
        $this->_klarnaSetup = $klarnaSetup;
        $host = $this->_klarnaSetup->getHost();
        if ($host == 'LIVE') {
            $mode = Klarna::LIVE;
        } else {
            $mode = Klarna::BETA;
        }
        $this->_klarnaApi->clear();
        $this->_klarnaApi->config(
            $this->_klarnaSetup->getMerchantId(),
            $this->_klarnaSetup->getSharedSecret(),
            $this->_klarnaSetup->getCountryCode(),
            $this->_klarnaSetup->getLanguageCode(),
            $this->_klarnaSetup->getCurrencyCode(),
            $mode,              // Live / Beta
            'mysql',            // pcStorage
            $this->_getPCURI(), // pclasses.json
            true,               // ssl
            true                // candice
            );
        if ($mode==Klarna::BETA) {
            $this->_klarnaApi->setConfigVariable('checkout_service_uri', 'https://api-test.klarna.com/touchpoint/checkout/');
        }

        if (method_exists('Mage', 'getEdition')) {
            $magentoEdition = Mage::getEdition();
        } else {
            if (class_exists("Enterprise_UrlRewrite_Model_Redirect", false)) {
                $magentoEdition = "Enterprise";
            } else {
                $magentoEdition = "Community";
            }
        }
        $magentoVersion = Mage::getVersion();
        $module = (string)Mage::getConfig()->getNode()->modules->Vaimo_Klarna->name;
        $version = (string)Mage::getConfig()->getNode()->modules->Vaimo_Klarna->version;
        $this->_klarnaApi->setVersion('PHP_' . 'Magento ' . $magentoEdition . '_' . $magentoVersion . '_' . $module . '_' . $version);
    }

    public function reserve()
    {
        try {
            $this->_klarnaApi->setEstoreInfo($this->_getTransport()->getOrder()->getIncrementId());
            $result = $this->_klarnaApi->reserveAmount(
                $this->_getTransport()->getPNO(),
                $this->_getTransport()->getGender(),
                $this->_getTransport()->getOrder()->getTotalDue(),
                KlarnaFlags::NO_FLAG,
                $this->_getTransport()->getPaymentPlan()
            );

            $res = array(
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID => $result[0],
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS => $this->_klarnaReservationStatusToCode($result[1])
            );

        } catch (KlarnaException $e) {
            $msg = 'Response Error Code = ' . $e->getCode();
            switch ($e->getCode()) {
                case '9118':
                    $msg .= ' Please check country, language and currency combination. They must match.';
                    break;
            }
            Mage::helper('klarna')->logKlarnaApi($msg);
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }
    
    public function capture($reservation_no, $amount, $sendEmailf)
    {
        try {
            $this->_klarnaApi->setEstoreInfo($this->_getTransport()->getOrder()->getIncrementId());
            $this->_klarnaApi->setActivateInfo('orderid1', strval($this->_getTransport()->getOrder()->getIncrementId()));

            $ocr = NULL;
            $flags = $this->_setCaptureFlags($sendEmailf);

            $result = $this->_klarnaApi->activate($reservation_no, $ocr, $flags);

            $res = array(
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS => $result[0],
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID => $result[1]
            );
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }
    
    public function refund($amount, $invoice_no)
    {
        try {

            switch ($this->_decideRefundMethod($this->_getTransport(), $amount)) {
                case Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_FULL:
                    Mage::helper('klarna')->logKlarnaApi('Full with invoice ID ' . $invoice_no);
                    $result = $this->_klarnaApi->creditInvoice($invoice_no);
                    break;
                case Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_AMOUNT:
                    $taxRate = 0;
                    foreach ($this->_getTransport()->getGoodsList() as $item) {
                        if (isset($item['tax'])) {
                            $taxRate = $item['tax'];
                            break;
                        }
                    }
//                    $amountExclDiscount = $amount - $this->_getTransport()->getCreditmemo()->getDiscountAmount();
                    Mage::helper('klarna')->logKlarnaApi('Amount with invoice ID ' . $invoice_no);
                    $result = $this->_klarnaApi->returnAmount($invoice_no, $amount, $taxRate, KlarnaFlags::INC_VAT, Mage::helper('klarna')->__('Refund amount'));
                    break;
                default: // Vaimo_Klarna_Helper_Data::KLARNA_REFUND_METHOD_PART
                    Mage::helper('klarna')->logKlarnaApi('Part with invoice ID ' . $invoice_no);
                    $this->_setGoodsListRefund($amount);
                    $result = $this->_klarnaApi->creditPart($invoice_no);
                    break;
            }

            $res = array(
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS => 'OK',
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID => $result
            );

        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }
    
    public function cancel($reservation_no)
    {
        try {
            $result = $this->_klarnaApi->cancelReservation($reservation_no);

            $res = array(
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS => $result,
            );

        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }

    public function release($reservation_no)
    {
        return $this->cancel($reservation_no);
    }
    
    public function checkStatus($reservation_no)
    {
        try {

            $result = $this->_klarnaApi->checkOrderStatus($reservation_no);

            $res = array(
                Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS => $this->_klarnaReservationStatusToCode($result),
            );

        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }
    
    public function getAddresses($personal_id)
    {
        try {
            $res = array();
            $result = $this->_klarnaApi->getAddresses($personal_id);
            foreach ($result as $klarnaAddr) {
                $res[] = array(
                    'company_name' => Mage::helper('klarna')->decode($klarnaAddr->getCompanyName()),
                    'first_name' => Mage::helper('klarna')->decode($klarnaAddr->getFirstName()),
                    'last_name' => Mage::helper('klarna')->decode($klarnaAddr->getLastName()),
                    'street' => Mage::helper('klarna')->decode($klarnaAddr->getStreet()),
                    'zip' => Mage::helper('klarna')->decode($klarnaAddr->getZipCode()),
                    'city' => Mage::helper('klarna')->decode($klarnaAddr->getCity()),
                    'house_number' => Mage::helper('klarna')->decode($klarnaAddr->getHouseNumber()),
                    'house_extension' => Mage::helper('klarna')->decode($klarnaAddr->getHouseExt()),
                    'country_code' => $klarnaAddr->getCountryCode(),
                    'id' => $this->_getAddressKey($klarnaAddr)
                    );
            }

        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }

    public function getPClasses($onlyCurrentStore = false)
    {
        try {
            $res = array();

            $pclasses = $this->_klarnaApi->getAllPClasses();
            
            if (is_array($pclasses)) {
                foreach ($pclasses as $pclass) {
                    if ($onlyCurrentStore) {
                        if ($this->_klarnaSetup->getCountryCode()!=KlarnaCountry::getCode($pclass->getCountry())) {
                            continue;
                        }
                    }
                    $res[] = $pclass;
                }
            }

            
        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }
    
    public function clearPClasses()
    {
        try {
            $this->_klarnaApi->clearPClasses();

        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
    }
    
    public function fetchPClasses($storeId)
    {
        try {
            $this->_klarnaApi->fetchPClasses($this->_klarnaSetup->getCountryCode(), $this->_klarnaSetup->getLanguageCode(), $this->_klarnaSetup->getCurrencyCode());

        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
// Should not be in API file, but I require all details from $e
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('klarna')->__('Fetching PClasses failed for store %s. Error: %s - %s', Mage::app()->getStore($storeId)->getName(), $e->getCode(), $e->getMessage())
            );
        }
    }
    
    public function getSpecificPClass($id, $amount)
    {
        try {
            $pclasses = $this->getPClasses(true);
            $res = NULL;
            foreach ($pclasses as $pclass) {
                if ($pclass->getId()==$id) {
                    $res = $this->_PClassToArray($pclass, $amount);
                    break;
                }
            }

        } catch (KlarnaException $e) {
            Mage::helper('klarna')->logKlarnaApi('Response Error Code = ' . $e->getCode());
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }
    
    public function getValidCheckoutPClasses($method, $amount)
    {
        try {
            $pclasses = $this->getPClasses(true);
            $pclasses = $this->_filterPClasses($method, $pclasses, KlarnaFlags::CHECKOUT_PAGE, $amount);
            $default = $this->_getDefaultPClass($pclasses);
            $minimum = $this->_getPClassMinimum($pclasses, KlarnaFlags::CHECKOUT_PAGE, $amount);

            $res = array();
            foreach ($pclasses as $pclass) {
                $pclassArr = $this->_PClassToArray($pclass, $amount, $default, $minimum);
                $res[] = $pclassArr;
            }
            if (sizeof($res)<=0) {
                $res = NULL;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            Mage::throwException(Mage::helper('klarna')->decode($e->getMessage()));
        }
        return $res;
    }
    
    public function getDisplayAllPClasses()
    {
        $res = array();
        $pclasses = $this->getPClasses(false);
        foreach ($pclasses as $pclass) {
            $pclassArr = $pclass->toArray();
            switch ($pclass->getCountry()) {
                case KlarnaCountry::SE:
                    $pclassArr['countryname']= Mage::helper('core')->__('Sweden');
                    break;
                case KlarnaCountry::NO:
                    $pclassArr['countryname']= Mage::helper('core')->__('Norway');
                    break;
                case KlarnaCountry::NL:
                    $pclassArr['countryname']= Mage::helper('core')->__('Netherlands');
                    break;
                case KlarnaCountry::DE:
                    $pclassArr['countryname']= Mage::helper('core')->__('Germany');
                    break;
                case KlarnaCountry::DK:
                    $pclassArr['countryname']= Mage::helper('core')->__('Denmark');
                    break;
                case KlarnaCountry::FI:
                    $pclassArr['countryname']= Mage::helper('core')->__('Finland');
                    break;
                default:
                    $pclassArr['countryname'] = Mage::helper('core')->__('Unknown');
                    break;
            }
            $res[] = $pclassArr;
        }
        return $res;
    }
    
    public function getCheckoutService($amount, $currency)
    {
        try {
            $res = NULL;
            if (!$this->_checkoutServiceResult) {
                $this->_checkoutServiceResult = $this->_klarnaApi->checkoutService($amount, $currency, $this->_klarnaSetup->getLocaleCode(), $this->_klarnaSetup->getCountryCode());
                if ($this->_checkoutServiceResult) {
                    if ($this->_checkoutServiceResult->getStatus()==200) {
                        Mage::helper('klarna')->logDebugInfo('checkoutService amount',array('amount' => $amount));
                        Mage::helper('klarna')->logDebugInfo('checkoutService',$this->_checkoutServiceResult->getData());
                    }
                }
            }
            if ($this->_checkoutServiceResult) {
                if ($this->_checkoutServiceResult->getStatus()==200) {
                    $res = $this->_checkoutServiceResult->getData();
                }
            }
        } catch (KlarnaException $e) {
            $res = NULL;
        }
        return $res;
    }
    
    /**
     * Create a KlarnaAddr from a Magento address
     *
     * @param object $address The Magento address to convert
     *
     * @return KlarnaAddr
     */
    public function toKlarnaAddress($address)
    {
        if (!$address) return NULL;

        $streetArr = $address->getStreet();
        $street = $streetArr[0];
        if (count($streetArr) > 1) {
            $street .= " " . $streetArr[1];
        }

        $split = $this->_splitStreet($street);

        $houseNo = "";
        if (array_key_exists("house_number", $split)) {
            $houseNo = $split["house_number"];
        }

        $houseExt = "";
        if (array_key_exists("house_extension", $split)) {
            $houseExt = $split["house_extension"];
        }

        $klarnaAddr = new KlarnaAddr(
            "",
            $address->getTelephone(), // Telno
            "", // Cellno
            Mage::helper('klarna')->encode($address->getFirstname()),
            Mage::helper('klarna')->encode($address->getLastname()),
            "",
            Mage::helper('klarna')->encode(trim($split["street"])),
            Mage::helper('klarna')->encode($address->getPostcode()),
            Mage::helper('klarna')->encode($address->getCity()),
            $address->getCountry(),
            Mage::helper('klarna')->encode(trim($houseNo)),
            Mage::helper('klarna')->encode(trim($houseExt))
        );

        $company = $address->getCompany();
        if (strlen($company) > 0 && $this->_getTransport()->isCompanyAllowed()) {
            $klarnaAddr->setCompanyName(Mage::helper('klarna')->encode($company));
            $klarnaAddr->isCompany = true;
        } else {
            $klarnaAddr->setCompanyName('');
            $klarnaAddr->isCompany = false;
        }

        return $klarnaAddr;
    }

    /**
     * Set the addresses on the Klarna object
     *
     * @return void
     */
    public function setAddresses($billingAddress, $shippingAddress, $data)
    {
        $shipping = $this->toKlarnaAddress($shippingAddress);
        $billing = $this->toKlarnaAddress($billingAddress);

        if (array_key_exists("email", $data)) {
            $email = $data["email"];
        } else {
            $email = "";
        }
        $shipping->setEmail($email);
        $billing->setEmail($email);

        $this->_setReference($data, $shipping, $billing);

        $this->_klarnaApi->setAddress(KlarnaFlags::IS_SHIPPING, $shipping);
        $this->_klarnaApi->setAddress(KlarnaFlags::IS_BILLING, $billing);

        Mage::helper('klarna')->logDebugInfo('shippingAddress', $shipping->toArray());
        Mage::helper('klarna')->logDebugInfo('billingAddress', $billing->toArray());

    }

    /**
     * Set the goods list for reservations
     *
     * @return void
     */
    public function setGoodsListReserve()
    {
        foreach ($this->_getTransport()->getGoodsList() as $item) {
            $this->_addArticle($item);
        }
        foreach ($this->_getTransport()->getExtras() as $extra) {
            $this->_addArticle($extra);
        }
    }

    /**
     * Set the goods list for Capture
     * Klarna seems to switch the order of the items in capture, so we simply add them backwards.
     *
     * @return void
     */
    public function setGoodsListCapture($amount)
    {
        foreach (array_reverse($this->_getTransport()->getExtras()) as $extra) {
            $this->_addArtNo($extra);
        }
        foreach (array_reverse($this->_getTransport()->getGoodsList()) as $item) {
            $this->_addArtNo($item);
        }
    }
    
    /**
     * Set the goods list for Refund
     *
     * @return void
     */
    protected function _setGoodsListRefund($amount)
    {
        foreach ($this->_getTransport()->getGoodsList() as $item) {
            $this->_addArtNo($item);
        }

        foreach ($this->_getTransport()->getExtras() as $extra) {
            $this->_addArtNo($extra);
        }
    }

    public function setShippingDetails($shipmentDetails)
    {
        if ($shipmentDetails) {
            $this->_klarnaApi->setShipmentInfo('shipment_details', $shipmentDetails);
        }
    }

}
