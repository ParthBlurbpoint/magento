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

/*
 *
 * This is the only file in the module that loads and uses the Klarna library folder
 * It should never be instantiated by itself, it can, but for readability one should not
 * No Klarna specific variables, constants or functions should be used outside this class
 *
 */

class Vaimo_Klarna_Model_Api_Kco extends Vaimo_Klarna_Model_Api_Abstract
{
    protected $_klarnaOrder = NULL;
    protected $_useKlarnaOrderSessionCache = false;

    protected function _getLocationOrderId()
    {
        $res = $this->_klarnaOrder->getLocation();
        $arr = explode('/', $res);
        if (is_array($arr)) {
            $res = $arr[sizeof($arr)-1];
        }
        return $res;
    }
    
    /**
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/cart')->getQuote();
    }

    /**
     * Get active Klarna checkout id
     *
     * @return string
     */
    protected function _getKlarnaCheckoutId()
    {
        return $this->_getQuote()->getKlarnaCheckoutId();
    }

    /**
     * Put Klarna checkout id to quote
     *
     * @param $checkoutId string
     */
    protected function _setKlarnaCheckoutId($checkoutId)
    {
        $quote = $this->_getQuote();

        if ($quote->getId() && $quote->getKlarnaCheckoutId() != $checkoutId) {
            Mage::helper('klarna')->logDebugInfo('SET checkout id: ' . $checkoutId);
            Mage::helper('klarna')->logDebugInfo('Quote Id: ' . $quote->getId());
            $quote->setKlarnaCheckoutId($checkoutId);
            $quote->save();
        }

        Mage::getSingleton('checkout/session')->setKlarnaCheckoutId($checkoutId);
    }

    /**
     * Get quote items and totals
     *
     * @return array
     */
    protected function _getCartItems()
    {
        $quote = $this->_getQuote();
        $items = array();

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getTaxPercent() > 0) {
                $taxRate = $quoteItem->getTaxPercent();
            } else {
                $taxRate = $quoteItem->getTaxAmount() / $quoteItem->getRowTotal() * 100;
            }
            $items[] = array(
                'reference' => $quoteItem->getSku(),
                'name' => $quoteItem->getName(),
                'quantity' => round($quoteItem->getQty()),
                'unit_price' => round($quoteItem->getPriceInclTax() * 100),
//                'discount_rate' => round($quoteItem->getDiscountPercent() * 100),
                'tax_rate' => round($taxRate * 100),
            );
        }

        foreach ($quote->getTotals() as $key => $total) {
            switch ($key) {
                case 'shipping':
                    if ($total->getValue() != 0) {
                        $amount_incl_tax = $total->getAddress()->getShippingInclTax();
                        $amount = $total->getAddress()->getShippingAmount();
                        $taxAmount = $total->getAddress()->getShippingTaxAmount();
                        $hiddenTaxAmount = $total->getAddress()->getShippingHiddenTaxAmount();
                        //if (Mage::helper('klarna')->isShippingInclTax($quote->getStoreId())) {
                        if (($amount_incl_tax>0) && (round($amount_incl_tax,2) == round($amount,2))) {
                            $amount = $amount - $taxAmount - $hiddenTaxAmount;
                        }
                        $taxRate = ($taxAmount + $hiddenTaxAmount) / $amount * 100;
                        $amount_incl_tax = $amount + $taxAmount + $hiddenTaxAmount;
                        $items[] = array(
                            'type' => 'shipping_fee',
                            'reference' => Mage::helper('klarna')->__('shipping'), // $total->getCode()
                            'name' => $total->getTitle(),
                            'quantity' => 1,
                            'unit_price' => round(($amount_incl_tax) * 100),
                            'discount_rate' => 0,
                            'tax_rate' => round($taxRate * 100),
                        );
                    }
                    break;
                case 'discount':
                    if ($total->getValue() != 0) {
                        // ok, this is a bit shaky here, i know...
                        // but i don't have discount tax anywhere but in hidden_tax_amount field :(
                        // and I have to send discount also with tax rate to klarna
                        // otherwise the total tax wouldn't match
                        $taxAmount = $total->getAddress()->getHiddenTaxAmount();
                        $amount = -$total->getAddress()->getDiscountAmount() - $taxAmount;
                        $taxRate = $taxAmount / $amount * 100;
                        $items[] = array(
                            'type' => 'discount',
                            'reference' => Mage::helper('klarna')->__('discount'), // $total->getCode()
                            'name' => $total->getTitle(),
                            'quantity' => 1,
                            'unit_price' => -round(($amount + $taxAmount) * 100),
                            'discount_rate' => 0,
                            'tax_rate' => round($taxRate * 100),
                        );
                    }
                    break;
                case 'giftcardaccount':
                    if ($total->getValue() != 0) {
                        $items[] = array(
                            'type' => 'discount',
                            'reference' => Mage::helper('klarna')->__('gift_card'), // $total->getCode()
                            'name' => $total->getTitle(),
                            'quantity' => 1,
                            'unit_price' => round($total->getValue() * 100),
                            'discount_rate' => 0,
                            'tax_rate' => 0,
                        );
                    }
                    break;
                case 'ugiftcert':
                    if ($total->getValue() != 0) {
                        $items[] = array(
                            'type' => 'discount',
                            'reference' => Mage::helper('klarna')->__('gift_card'), // $total->getCode()
                            'name' => $total->getTitle(),
                            'quantity' => 1,
                            'unit_price' => -round($total->getValue() * 100),
                            'discount_rate' => 0,
                            'tax_rate' => 0,
                        );
                    }
                    break;
                case 'reward':
                    if ($total->getValue() != 0) {
                        $items[] = array(
                            'type' => 'discount',
                            'reference' => Mage::helper('klarna')->__('reward'), // $total->getCode()
                            'name' => $total->getTitle(),
                            'quantity' => 1,
                            'unit_price' => round($total->getValue() * 100),
                            'discount_rate' => 0,
                            'tax_rate' => 0,
                        );
                    }
                    break;
                case 'customerbalance':
                    if ($total->getValue() != 0) {
                        $items[] = array(
                            'type' => 'discount',
                            'reference' => Mage::helper('klarna')->__('customer_balance'), // $total->getCode()
                            'name' => $total->getTitle(),
                            'quantity' => 1,
                            'unit_price' => round($total->getValue() * 100),
                            'discount_rate' => 0,
                            'tax_rate' => 0,
                        );
                    }
                    break;
            }
        }

        return $items;
    }

    /**
     * Get create request
     *
     * @return array
     */
    protected function _getCreateRequest()
    {
        $create = array();
        $create['purchase_country'] = Mage::helper('klarna')->getDefaultCountry();
        $create['purchase_currency'] = $this->_getQuote()->getQuoteCurrencyCode();
        $create['locale'] = str_replace('_', '-', Mage::app()->getLocale()->getLocaleCode());
        $create['merchant']['id'] = $this->_klarnaSetup->getMerchantId();
        $create['merchant']['terms_uri'] = Mage::helper('klarna')->getTermsUrl($this->_klarnaSetup->getTermsUrl());
        $create['merchant']['checkout_uri'] = Mage::getUrl('checkout/klarna');
        $create['merchant']['confirmation_uri'] = Mage::getUrl('checkout/klarna/success');
        $create['gui']['layout'] = $this->_isMobile() ? 'mobile' : 'desktop';
        if ($this->_getTransport()->getConfigData('enable_auto_focus')==false) {
            $create['gui']['options'] = array('disable_autofocus');
        }
        if ($this->_getTransport()->AllowSeparateAddress()) {
            $create['options']['allow_separate_shipping_address'] = true;
        }
        if ($this->_getTransport()->getConfigData('force_phonenumber')) {
            $create['options']['phone_mandatory'] = true;
        }
        if ($this->_getTransport()->getConfigData('packstation_enabled')) {
            $create['options']['packstation_enabled'] = true;
        }

        $this->_addUserDefinedVariables($create);

        $pushUrl = Mage::getUrl('checkout/klarna/push?klarna_order={checkout.order.id}', array('_nosid' => true));
        if (substr($pushUrl, -1, 1) == '/') {
            $pushUrl = substr($pushUrl, 0, strlen($pushUrl) - 1);
        }

        $create['merchant']['push_uri'] = $pushUrl;

        if ($this->_getTransport()->getConfigData('enable_validation')) {
            $validateUrl = Mage::getUrl('checkout/klarna/validate?klarna_order={checkout.order.id}', array('_nosid' => true));
            if (substr($validateUrl, -1, 1) == '/') {
                $validateUrl = substr($validateUrl, 0, strlen($validateUrl) - 1);
            }
            if (substr($validateUrl, 0, 5) == 'https') {
                $create['merchant']['validation_uri'] = $validateUrl;
            }
        }

        $create['cart']['items'] = $this->_getCartItems();

        if ($data = $this->_getBillingAddressData()) {
            $create['shipping_address'] = $data;
        }
        
        if ($data = $this->_getCustomerData()) {
            $create['customer'] = $data;
        }
        
        Mage::helper('klarna')->logDebugInfo('_getCreateRequest', $create);
        $request = new Varien_Object($create);
        Mage::dispatchEvent('klarnacheckout_get_create_request', array('request' => $request));

        return $request->getData();
    }

    /**
     * Get update request
     *
     * @return array
     */
    protected function _getUpdateRequest()
    {
        $update = array();
        $update['cart']['items'] = $this->_getCartItems();
//        $update['gui']['layout'] = 'desktop';

        if ($data = $this->_getBillingAddressData()) {
            $update['shipping_address'] = $data;
        }

        if ($data = $this->_getCustomerData()) {
            $update['customer'] = $data;
        }
        
        Mage::helper('klarna')->logDebugInfo('_getUpdateRequest', $update);
        $request = new Varien_Object($update);
        Mage::dispatchEvent('klarnacheckout_get_update_request', array('request' => $request));

        return $request->getData();
    }

    protected function _getBillingAddressData()
    {
        if (!$this->_getTransport()->getConfigData('auto_prefil')) return NULL;
        
        /** @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()) {
            $result = array();
            $address = $session->getCustomer()->getPrimaryBillingAddress();
            if ($session->getCustomer()->getEmail()) {
                $result['email'] = $session->getCustomer()->getEmail();
            }
            if ($address) {
                if ($address->getPostcode()) {
                    $result['postal_code'] = $address->getPostcode();
                }
            }
            if ($this->_getTransport()->moreDetailsToKCORequest()) {
                if ($address && 
                    ( preg_match('/^([^\d]*[^\d\s]) *(\d.*)$/', $address->getStreet(1), $tmp) )) {
                    $streetName = $tmp[1];
                    $streetNumber = $tmp[2];
                }
                
                if ($gender = $session->getCustomer()->getGender()) {
                    switch ($gender) {
                        case 1:
                            $gender = Mage::helper('klarna')->__('Male');
                            break;
                        case 2:
                            $gender = Mage::helper('klarna')->__('Female');
                            break;
                    }
                }
                if ($streetName) {
                    $result['street_name'] = $streetName;
                }
                if ($streetNumber) {
                    $result['street_number'] = $streetNumber;
                }
                if ($address) {
                    if ($address->getFirstname()) {
                        $result['given_name'] = $address->getFirstname();
                    }
                    if ($address->getLastname()) {
                        $result['family_name'] = $address->getLastname();
                    }
                    if ($address->getCity()) {
                        $result['city'] = $address->getCity();
                    }
                    if ($address->getTelephone()) {
                        $result['phone'] = $address->getTelephone();
                    }
                    if ($address->getCountryId()) {
                        $result['country'] = $address->getCountryId();
                    }
                    if ($gender) {
                        $result['title'] = $gender;
                    }
                }
            }
            return $result;
        }

        return NULL;
    }    

    protected function _getCustomerData()
    {
        if (!$this->_getTransport()->getConfigData('auto_prefil')) return NULL;
        
        /** @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()) {
            if ($this->_getTransport()->needDateOfBirth()) {
                if ($session->getCustomer()->getDob()) {
                    $result = array(
                        'date_of_birth' => substr($session->getCustomer()->getDob(),0,10),
                    );
                    return $result;
                }
            }
        }

        return NULL;
    }    

    public function init($klarnaSetup)
    {
        $this->_klarnaSetup = $klarnaSetup;
        if ($this->_klarnaSetup->getHost()=='LIVE') {
            Klarna_Checkout_Order::$baseUri = 'https://checkout.klarna.com/checkout/orders';
        } else {
            Klarna_Checkout_Order::$baseUri = 'https://checkout.testdrive.klarna.com/checkout/orders';
        }
        Klarna_Checkout_Order::$contentType = "application/vnd.klarna.checkout.aggregated-order-v2+json";
    }

    /**
     * Get connector
     *
     * @return Klarna_Checkout_Connector
     */
    protected function _getConnector()
    {
        $secret = $this->_klarnaSetup->getSharedSecret();

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
        $module_info = array('Application' => array(
                             'name' => 'Magento ' . '_' . $magentoEdition,
                             'version' => $magentoVersion),
                             'Module' => array(
                             'name' => $module,
                             'version' => $version),
                             );
        return Klarna_Checkout_Connector::create($secret, $module_info);
    }
    
    public function setKlarnaOrderSessionCache($value)
    {
        $this->_useKlarnaOrderSessionCache = $value;
    }
    
    /*
     * Will return the klarna order or null, if it doesn't find it
     * Not used by this module, but as a service for others.
     *
     */
    public function getKlarnaOrderRaw($checkoutId)
    {
        if ($checkoutId) {
            $this->_klarnaOrder = new Klarna_Checkout_Order($this->_getConnector(), Klarna_Checkout_Order::$baseUri . '/' . $checkoutId);
            $this->_klarnaOrder->fetch();
            $order = new Varien_Object($this->_klarnaOrder->marshal());
            return $order;
        }
        return NULL;
    }
    
    /**
     * Get Klarna checkout order
     *
     * @param null $checkoutId
     * @param bool $createIfNotExists
     * @param bool $updateItems
     * @return Klarna_Checkout_Order|null
     */
    public function initKlarnaOrder($checkoutId = null, $createIfNotExists = false, $updateItems = false)
    {
        if ($checkoutId) {
            Mage::helper('klarna')->logKlarnaApi('initKlarnaOrder checkout id: ' . $checkoutId);
            $loadf = true;
            if ($this->_useKlarnaOrderSessionCache) {
                if ($this->_klarnaOrder) {
                    $loadf = false;
                }
            }
            if ($loadf) {
                $this->_klarnaOrder = new Klarna_Checkout_Order($this->_getConnector(), Klarna_Checkout_Order::$baseUri . '/' . $checkoutId);
                $this->_klarnaOrder->fetch();
            }
            $res = $this->_klarnaOrder!=NULL;
            if ($res) {
                if ($this->_getLocationOrderId()) {
                    $this->_setKlarnaCheckoutId($this->_getLocationOrderId());
                }
            }
            Mage::helper('klarna')->logKlarnaApi('initKlarnaOrder res: ' . $res);
            return $res;
        }

        if ($klarnaCheckoutId = $this->_getKlarnaCheckoutId()) {
            try {
                Mage::helper('klarna')->logKlarnaApi('initKlarnaOrder klarnaCheckoutId id: ' . $klarnaCheckoutId);
                $this->_klarnaOrder = new Klarna_Checkout_Order($this->_getConnector(), Klarna_Checkout_Order::$baseUri . '/' . $klarnaCheckoutId);
                if ($updateItems) {
                    $this->_klarnaOrder->update($this->_getUpdateRequest());
                }
                $this->_klarnaOrder->fetch();
                $res = $this->_klarnaOrder!=NULL;
                if ($res) {
                    if ($this->_getLocationOrderId()) {
                        $this->_setKlarnaCheckoutId($this->_getLocationOrderId());
                    }
                }
                Mage::helper('klarna')->logKlarnaApi('initKlarnaOrder res: ' . $res);
                return $res;
            } catch (Exception $e) {
                // when checkout in Klarna was expired, then exception, so we just ignore and create new
                Mage::helper('klarna')->logKlarnaException($e);
            }
        }

        if ($createIfNotExists) {
            Mage::helper('klarna')->logKlarnaApi('initKlarnaOrder create');
            $this->_klarnaOrder = new Klarna_Checkout_Order($this->_getConnector());
            $this->_klarnaOrder->create($this->_getCreateRequest());
            $this->_klarnaOrder->fetch();
            $res = $this->_klarnaOrder!=NULL;
            if ($res) {
                if ($this->_getLocationOrderId()) {
                    $this->_setKlarnaCheckoutId($this->_getLocationOrderId());
                }
            }
            Mage::helper('klarna')->logKlarnaApi('initKlarnaOrder res: ' . $res);
            return $res;
        }

        return false;
    }
    
    public function prepareTaxAndShippingReply()
    {
        return '';
    }

    /*
     * Not happy with this, but I guess we can't solve it in other ways.
     *
     */
    public function getActualKlarnaOrder()
    {
        if ($this->_klarnaOrder) {
            return $this->_klarnaOrder;
        }
        return NULL;
    }
    
    public function getKlarnaCheckoutGui()
    {
        if ($this->_klarnaOrder) {
            if ($this->_klarnaOrder->offsetExists('gui')) {
                $gui = $this->_klarnaOrder->offsetGet('gui');
                return isset($gui['snippet']) ? $gui['snippet'] : '';
            }
        }
        return '';
    }
    
    public function getKlarnaCheckoutStatus()
    {
        if ($this->_klarnaOrder) {
            if ($this->_klarnaOrder->offsetExists('status')) {
                return $this->_klarnaOrder->offsetGet('status');
            }
        }
        return '';
    }
    
    public function loadQuote()
    {
        if ($this->_klarnaOrder) {
            /** @var $quote Mage_Sales_Model_Quote */
            $quote = Mage::getModel('sales/quote')->load($this->_getLocationOrderId(), 'klarna_checkout_id');
            if ($quote->getId()) {
                return $quote;
            }
        }
        return NULL;
    }
    
    public function fetchCreatedOrder($checkoutId)
    {
        $this->initKlarnaOrder($checkoutId);
        if ($this->_klarnaOrder) {
            $order = new Varien_Object($this->_klarnaOrder->marshal());
            if ($order) {
                return $order;
            }
        }
        return NULL;
    }
    
    public function updateKlarnaOrder($order, $repeatCall = false)
    {
        if ($this->_klarnaOrder) {
            if ($repeatCall) {
                Mage::helper('klarna')->logKlarnaApi('updateKlarnaOrder AGAIN for order no: ' . $order->getIncrementId());
            } else {
                Mage::helper('klarna')->logKlarnaApi('updateKlarnaOrder order no: ' . $order->getIncrementId());
            }
            // Update Klarna
            $update = array(
                'status' => 'created',
                'merchant_reference' => array('orderid1' => $order->getIncrementId()),
            );

            // Add extra attribute to order
            $orderid2Code = trim($this->_getTransport()->getConfigData('extra_order_attribute'));
            if ($orderid2Code && $orderid2Code!='' && $order->getData($orderid2Code)) {
                $orderid2Value = $order->getData($orderid2Code);
                $update['merchant_reference']['orderid2'] = $orderid2Value;
            }
        
            $this->_klarnaOrder->update($update);
            Mage::helper('klarna')->logKlarnaApi('updateKlarnaOrder success');
            return true;
        }
        return false;
    }
    
}
