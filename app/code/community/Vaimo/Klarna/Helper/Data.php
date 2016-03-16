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

class Vaimo_Klarna_Helper_Data extends Mage_Core_Helper_Abstract
{
    const KLARNA_METHOD_INVOICE  = 'vaimo_klarna_invoice';
    const KLARNA_METHOD_ACCOUNT  = 'vaimo_klarna_account';
    const KLARNA_METHOD_SPECIAL  = 'vaimo_klarna_special';
    const KLARNA_METHOD_CHECKOUT = 'vaimo_klarna_checkout';

    const KLARNA_API_CALL_RESERVE          = 'reserve';
    const KLARNA_API_CALL_CAPTURE          = 'capture';
    const KLARNA_API_CALL_REFUND           = 'refund';
    const KLARNA_API_CALL_CANCEL           = 'cancel';
    const KLARNA_API_CALL_CHECKSTATUS      = 'check_status';
    const KLARNA_API_CALL_ADDRESSES        = 'addresses';
    const KLARNA_API_CALL_PCLASSES         = 'pclasses';
    const KLARNA_API_CALL_CHECKOUTSERVICES = 'checkout_services';

    const KLARNA_API_CALL_KCODISPLAY_ORDER = 'kco_display_order';
    const KLARNA_API_CALL_KCOCREATE_ORDER  = 'kco_create_order';
    
    const KLARNA_STATUS_ACCEPTED = 'accepted';
    const KLARNA_STATUS_PENDING  = 'pending';
    const KLARNA_STATUS_DENIED   = 'denied';
    
    const KLARNA_INFO_FIELD_FEE                         = 'vaimo_klarna_fee';
    const KLARNA_INFO_FIELD_FEE_TAX                     = 'vaimo_klarna_fee_tax';
    const KLARNA_INFO_FIELD_BASE_FEE                    = 'vaimo_klarna_base_fee';
    const KLARNA_INFO_FIELD_BASE_FEE_TAX                = 'vaimo_klarna_base_fee_tax';
    const KLARNA_INFO_FIELD_FEE_CAPTURED_TRANSACTION_ID = 'klarna_fee_captured_transaction_id';
    const KLARNA_INFO_FIELD_FEE_REFUNDED                = 'klarna_fee_refunded';

    const KLARNA_INFO_FIELD_RESERVATION_STATUS  = 'klarna_reservation_status';
    const KLARNA_INFO_FIELD_RESERVATION_ID      = 'klarna_reservation_id';
    const KLARNA_INFO_FIELD_CANCELED_DATE       = 'klarna_reservation_canceled_date';
    const KLARNA_INFO_FIELD_REFERENCE           = 'klarna_reservation_reference';
    const KLARNA_INFO_FIELD_ORDER_ID            = 'klarna_reservation_order_id';
    const KLARNA_INFO_FIELD_INVOICE_LIST        = 'klarna_invoice_list';
    const KLARNA_INFO_FIELD_INVOICE_LIST_STATUS = 'invoice_status';
    const KLARNA_INFO_FIELD_INVOICE_LIST_ID     = 'invoice_id';
    const KLARNA_INFO_FIELD_INVOICE_LIST_KCO_ID = 'invoice_kco_id';
    const KLARNA_INFO_FIELD_HOST                = 'klarna_reservation_host';
    const KLARNA_INFO_FIELD_MERCHANT_ID         = 'merchant_id';

    const KLARNA_INFO_FIELD_PAYMENT_PLAN              = 'payment_plan';
    const KLARNA_INFO_FIELD_PAYMENT_PLAN_TYPE         = 'payment_plan_type';
    const KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHS       = 'payment_plan_months';
    const KLARNA_INFO_FIELD_PAYMENT_PLAN_START_FEE    = 'payment_plan_start_fee';
    const KLARNA_INFO_FIELD_PAYMENT_PLAN_INVOICE_FEE  = 'payment_plan_invoice_fee';
    const KLARNA_INFO_FIELD_PAYMENT_PLAN_TOTAL_COST   = 'payment_plan_total_cost';
    const KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHLY_COST = 'payment_plan_monthly_cost';
    const KLARNA_INFO_FIELD_PAYMENT_PLAN_DESCRIPTION  = 'payment_plan_description';

    const KLARNA_FORM_FIELD_PHONENUMBER = 'phonenumber';
    const KLARNA_FORM_FIELD_PNO         = 'pno';
    const KLARNA_FORM_FIELD_ADDRESS_ID  = 'address_id';
    const KLARNA_FORM_FIELD_DOB_YEAR    = 'dob_year';
    const KLARNA_FORM_FIELD_DOB_MONTH   = 'dob_month';
    const KLARNA_FORM_FIELD_DOB_DAY     = 'dob_day';
    const KLARNA_FORM_FIELD_CONSENT     = 'consent';
    const KLARNA_FORM_FIELD_GENDER      = 'gender';
    const KLARNA_FORM_FIELD_EMAIL       = 'email';
    
    const KLARNA_API_RESPONSE_STATUS         = 'response_status';
    const KLARNA_API_RESPONSE_TRANSACTION_ID = 'response_transaction_id';
    const KLARNA_API_RESPONSE_FEE_REFUNDED   = 'response_fee_refunded';
    const KLARNA_API_RESPONSE_FEE_CAPTURED   = 'response_fee_captured';
    const KLARNA_API_RESPONSE_KCO_CAPTURE_ID = 'response_kco_capture_id';
    const KLARNA_API_RESPONSE_KCO_LOCATION   = 'response_kco_location';

    const KLARNA_LOGOTYPE_TYPE_INVOICE  = 'invoice';
    const KLARNA_LOGOTYPE_TYPE_ACCOUNT  = 'account';
    const KLARNA_LOGOTYPE_TYPE_CHECKOUT = 'checkout';
    const KLARNA_LOGOTYPE_TYPE_BOTH     = 'unified';
    const KLARNA_LOGOTYPE_TYPE_BASIC    = 'basic';

    const KLARNA_FLAG_ITEM_NORMAL = "normal";
    const KLARNA_FLAG_ITEM_SHIPPING_FEE = "shipping";
    const KLARNA_FLAG_ITEM_HANDLING_FEE = "handling";

    const KLARNA_REFUND_METHOD_FULL = "full";
    const KLARNA_REFUND_METHOD_PART = "part";
    const KLARNA_REFUND_METHOD_AMOUNT = "amount";

    const KLARNA_LOGOTYPE_POSITION_FRONTEND = 'frontend';
    const KLARNA_LOGOTYPE_POSITION_PRODUCT  = 'product';
    const KLARNA_LOGOTYPE_POSITION_CHECKOUT = 'checkout';
    
    const KLARNA_DISPATCH_RESERVED = 'vaimo_paymentmethod_order_reserved';
    const KLARNA_DISPATCH_CAPTURED = 'vaimo_paymentmethod_order_captured';
    const KLARNA_DISPATCH_REFUNDED = 'vaimo_paymentmethod_order_refunded';
    const KLARNA_DISPATCH_CANCELED = 'vaimo_paymentmethod_order_canceled';
    
    const KLARNA_LOG_START_TAG = '---------------START---------------';
    const KLARNA_LOG_END_TAG = '----------------END----------------';

    const KLARNA_EXTRA_VARIABLES_GUI_OPTIONS = 0;
    const KLARNA_EXTRA_VARIABLES_GUI_LAYOUT  = 1;
    const KLARNA_EXTRA_VARIABLES_OPTIONS     = 2;

    const KLARNA_KCO_API_VERSION_STD = 2;
    const KLARNA_KCO_API_VERSION_UK  = 3;
    const KLARNA_KCO_API_VERSION_USA = 4;


    protected $_supportedMethods = array(
        Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE,
        Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT,
        Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL,
        Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT
    );

    protected $_klarnaFields = array(
        self::KLARNA_INFO_FIELD_FEE,
        self::KLARNA_INFO_FIELD_FEE_TAX,
        self::KLARNA_INFO_FIELD_BASE_FEE,
        self::KLARNA_INFO_FIELD_BASE_FEE_TAX,
        self::KLARNA_INFO_FIELD_FEE_CAPTURED_TRANSACTION_ID,
        self::KLARNA_INFO_FIELD_FEE_REFUNDED,

        self::KLARNA_INFO_FIELD_RESERVATION_STATUS,
        self::KLARNA_INFO_FIELD_RESERVATION_ID,
        self::KLARNA_INFO_FIELD_CANCELED_DATE,
        self::KLARNA_INFO_FIELD_REFERENCE,
        self::KLARNA_INFO_FIELD_ORDER_ID,
        self::KLARNA_INFO_FIELD_INVOICE_LIST,
        self::KLARNA_INFO_FIELD_INVOICE_LIST_STATUS,
        self::KLARNA_INFO_FIELD_INVOICE_LIST_ID,
        self::KLARNA_INFO_FIELD_HOST,
        self::KLARNA_INFO_FIELD_MERCHANT_ID,

        self::KLARNA_INFO_FIELD_PAYMENT_PLAN,
        self::KLARNA_INFO_FIELD_PAYMENT_PLAN_TYPE,
        self::KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHS,
        self::KLARNA_INFO_FIELD_PAYMENT_PLAN_START_FEE,
        self::KLARNA_INFO_FIELD_PAYMENT_PLAN_INVOICE_FEE,
        self::KLARNA_INFO_FIELD_PAYMENT_PLAN_TOTAL_COST,
        self::KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHLY_COST,
        self::KLARNA_INFO_FIELD_PAYMENT_PLAN_DESCRIPTION,

        self::KLARNA_FORM_FIELD_PHONENUMBER,
        self::KLARNA_FORM_FIELD_PNO,
        self::KLARNA_FORM_FIELD_ADDRESS_ID,
        self::KLARNA_FORM_FIELD_DOB_YEAR,
        self::KLARNA_FORM_FIELD_DOB_MONTH,
        self::KLARNA_FORM_FIELD_DOB_DAY,
        self::KLARNA_FORM_FIELD_CONSENT,
        self::KLARNA_FORM_FIELD_GENDER,
        self::KLARNA_FORM_FIELD_EMAIL,
        
    );

    const KLARNA_CHECKOUT_ENABLE_NEWSLETTER          = 'payment/vaimo_klarna_checkout/enable_newsletter';
    const KLARNA_CHECKOUT_EXTRA_ORDER_ATTRIBUTE      = 'payment/vaimo_klarna_checkout/extra_order_attribute';
    const KLARNA_CHECKOUT_ENABLE_CART_ABOVE_KCO     = 'payment/vaimo_klarna_checkout/enable_cart_above_kco';

    const KLARNA_CHECKOUT_NEWSLETTER_DISABLED       = 0;
    const KLARNA_CHECKOUT_NEWSLETTER_SUBSCRIBE      = 1;
    const KLARNA_CHECKOUT_NEWSLETTER_DONT_SUBSCRIBE = 2;

    const KLARNA_CHECKOUT_ALLOW_ALL_GROUP_ID = 99;

    const KLARNA_DISPATCH_VALIDATE = 'vaimo_klarna_validate_cart';
    const KLARNA_VALIDATE_ERRORS = 'klarna_validate_errors';

    const ENCODING_MAGENTO = 'UTF-8';
    const ENCODING_KLARNA = 'ISO-8859-1';

    /**
     * The name in SESSION variable of the function currently executing, only used for logs
     */
    const LOG_FUNCTION_SESSION_NAME = 'klarna_log_function_name';

    /**
     * Encode the string to klarna encoding
     *
     * @param string $str  string to encode
     * @param string $from from encoding
     * @param string $to   target encoding
     *
     * @return string
     */
    public function encode($str, $from = null, $to = null)
    {
        if ($from === null) {
            $from = self::ENCODING_MAGENTO;
        }
        if ($to === null) {
            $to = self::ENCODING_KLARNA;
        }
        return iconv($from, $to, $str);
    }

    /**
     * Decode the string to the Magento encoding
     *
     * @param string $str  string to decode
     * @param string $from from encoding
     * @param string $to   target encoding
     *
     * @return string
     */
    public function decode($str, $from = null, $to = null)
    {
        if ($from === null) {
            $from = self::ENCODING_KLARNA;
        }
        if ($to === null) {
            $to = self::ENCODING_MAGENTO;
        }
        return iconv($from, $to, $str);
    }

    public function getSupportedMethods()
    {
        return $this->_supportedMethods;
    }

    public function isKlarnaField($key)
    {
        return (in_array($key,$this->_klarnaFields));
    }

    public function isMethodKlarna($method)
    {
        if (in_array($method, $this->getSupportedMethods())) {
            return true;
        }
        return false;
    }
    
    public function getInvoiceLink($order, $transactionId)
    {
        $link = "";
        if ($order) {
            $payment = $order->getPayment();
            if ($payment) {
                $host = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_HOST);
                $domain = ($host === 'LIVE') ? 'online': 'testdrive';
                $link = "https://{$domain}.klarna.com/invoices/" . $transactionId . ".pdf";
            }
        }
        return $link;
    }

    public function shouldItemBeIncluded($item)
    {
        if ($item->getParentItemId()>0 && $item->getPriceInclTax()==0) return false;
        return true;
    }

    public function isShippingInclTax($storeId)
    {
        return Mage::getSingleton('tax/config')->displaySalesShippingInclTax($storeId);
    }

    /**
     * Check if OneStepCheckout is activated or not
     * It also checks if OneStepCheckout is activated, but it's currently using
     * standard checkout
     *
     * @return bool
     */
    public function isOneStepCheckout($store = null)
    {
        $res = false;
        if (Mage::getStoreConfig('onestepcheckout/general/rewrite_checkout_links', $store)) {
            $res = true;
            $request = Mage::app()->getRequest();
            $requestedRouteName = $request->getRequestedRouteName();
            $requestedControllerName = $request->getRequestedControllerName();
            if ($requestedRouteName == 'checkout' && $requestedControllerName == 'onepage') {
                $res = false;
            }
        }
        return $res;
    }

    /**
     * Check if Vaimo_QuickCheckout is activated or not
     *
     * @return bool
     */
    public function isQuickCheckout($store = null)
    {
        $res = false;
        try {
            if (class_exists('Icommerce_QuickCheckout_Helper_Data', true)) {
                $res = true;
            }
        } catch (Exception $e) {
        }
        return $res;
    }

    /*
     * Last minute change. We were showing logotype instead of title, but the implementation was not
     * as good as we wanted, so we reverted it and will make it a setting. This function will be the
     * base of that setting. If it returns false, we should show the logotype together with the title
     * otherwise just show the title.
     */
    public function showTitleAsTextOnly()
    {
        return true;
    }

    /**
     * Check if OneStepCheckout displays their prises with the tax included
     *
     * @return bool
     */
    public function isOneStepCheckoutTaxIncluded()
    {
        return (bool) Mage::getStoreConfig( 'onestepcheckout/general/display_tax_included' );
    }

    protected function _feePriceIncludesTax($store = null)
    {
        $config = Mage::getSingleton('klarna/tax_config');
        return $config->klarnaFeePriceIncludesTax($store);
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param null $store
     * @return mixed
     */
    protected function _getVaimoKlarnaFeeForMethod($quote, $store, $force = false)
    {
        /** @var Mage_Sales_Model_Quote_Payment $payment */
        $payment = $quote->getPayment();
        $method = $payment->getMethod();
        if (!$method && !$force) {
            return 0;
        }

        $fee = 0;
        if ($force || $method==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE) {
            $fee = Mage::getStoreConfig('payment/vaimo_klarna_invoice/invoice_fee', $store);
        }
        return $fee;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param $store
     * @return int
     */
    protected function _getVaimoKlarnaFee($quote, $store, $force = false, $inBaseCurrency = true)
    {
        $localFee = 0;
        $fee = $this->_getVaimoKlarnaFeeForMethod($quote, $store, $force);
        if ($fee) {
            if (!$inBaseCurrency && $store->getCurrentCurrency() != $store->getBaseCurrency()) {
                $rate = $store->getBaseCurrency()->getRate($store->getCurrentCurrency());
                $curKlarnaFee = $fee * $rate;
            } else {
                $curKlarnaFee = $fee;
            }
            $localFee = $store->roundPrice($curKlarnaFee);
        }
        return $localFee;
    }

    /**
     * Returns the label set for fee
     *
     * @param $store
     * @return string
     */
    public function getKlarnaFeeLabel($store = NULL)
    {
        return $this->__(Mage::getStoreConfig('payment/vaimo_klarna_invoice/invoice_fee_label', $store));
    }

    /**
     * Returns the tax class for invoice fee
     *
     * @param $store
     * @return string
     */
    public function getTaxClass($store)
    {
        $config = Mage::getSingleton('klarna/tax_config');
        return $config->getKlarnaFeeTaxClass($store);
    }

    /**
     * Returns the payment fee excluding VAT
     *
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @return float
     */
    public function getVaimoKlarnaFeeExclVat($shippingAddress)
    {
        $quote = $shippingAddress->getQuote();
        $store = $quote->getStore();
        $fee = $this->_getVaimoKlarnaFee($quote, $store);
        if ($fee && $this->_feePriceIncludesTax($store)) {
            $fee -= $this->getVaimoKlarnaFeeVat($shippingAddress);
        }
        return $fee;
    }

    /**
     * Returns the payment fee tax for the payment fee
     *
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @return float
     */
    public function getVaimoKlarnaFeeVat($shippingAddress)
    {
        $paymentTax = 0;
        $quote = $shippingAddress->getQuote();
        $store = $quote->getStore();
        $fee = $this->_getVaimoKlarnaFee($quote, $store);
        if ($fee) {
            $custTaxClassId = $quote->getCustomerTaxClassId();
            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($shippingAddress, $quote->getBillingAddress(), $custTaxClassId, $store);
            $paymentTaxClass = $this->getTaxClass($store);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($paymentTaxClass));
            if ($rate) {
                $paymentTax = $taxCalculationModel->calcTaxAmount($fee, $rate, $this->_feePriceIncludesTax($store), true);
            }
        }
        return $paymentTax;
    }

    /**
     * Returns the payment fee tax rate
     *
     * @param Mage_Sales_Model_Order $order
     * @return float
     */
    public function getVaimoKlarnaFeeVatRate($order)
    {
        $shippingAddress = $order->getShippingAddress();
        $store = $order->getStore();
        $custTaxClassId = $order->getCustomerTaxClassId();

        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $request = $taxCalculationModel->getRateRequest($shippingAddress, $order->getBillingAddress(), $custTaxClassId, $store);
        $paymentTaxClass = $this->getTaxClass($store);
        $rate = $taxCalculationModel->getRate($request->setProductClassId($paymentTaxClass));

        return $rate;
    }

    /**
     * Returns the payment fee including VAT, this function doesn't care about method or shipping address country
     * It's striclty for informational purpouses
     *
     * @return float
     */
    public function getVaimoKlarnaFeeInclVat($quote, $inBaseCurrency = true)
    {
        $shippingAddress = $quote->getShippingAddress();
        $store = $quote->getStore();
        $fee = $this->_getVaimoKlarnaFee($quote, $store, true, $inBaseCurrency);
        if ($fee && !$this->_feePriceIncludesTax($store)) {
            $custTaxClassId = $quote->getCustomerTaxClassId();
            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($shippingAddress, $quote->getBillingAddress(), $custTaxClassId, $store);
            $paymentTaxClass = $this->getTaxClass($store);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($paymentTaxClass));
            if ($rate) {
                $tax = $taxCalculationModel->calcTaxAmount($fee, $rate, $this->_feePriceIncludesTax($store), true);
                $fee += $tax;
            }

        }
        return $fee;
    }

    /*
     * The following functions shouldn't really need to exist...
     * Either I have done something wrong or the versions have changed how they work...
     *
     */
     
    /*
     * Add tax to grand total on invoice collect or not
     */
    public function collectInvoiceAddTaxToInvoice()
    {
        $currentVersion = Mage::getVersion();
        if ((version_compare($currentVersion, '1.10.0')>=0) && (version_compare($currentVersion, '1.12.0')<0)) {
            return false;
        } else {
            return true;
        }
    }
    
    /*
     * Call parent of quote collect or not
     */
    public function collectQuoteRunParentFunction()
    {
        return false; // Seems the code was wrong, this function is no longer required
        $currentVersion = Mage::getVersion();
        if (version_compare($currentVersion, '1.11.0')>=0) {
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * Use extra tax in quote instead of adding to Tax, I don't know why this has to be
     * different in EE, but it clearly seems to be...
     */
    public function collectQuoteUseExtraTaxInCheckout()
    {
        return false; // Seems the code was wrong, this function is no longer required
        $currentVersion = Mage::getVersion();
        if (version_compare($currentVersion, '1.11.0')>=0) {
            return true;
        } else {
            return false;
        }
    }


// KLARNA CHECKOUT FROM NOW

    protected function _addressMatch(array $address1, array $address2)
    {
        $compareFields = array(
            'firstname',
            'lastname',
            'company',
            'street',
            'postcode',
            'city',
            'telephone',
            'country_id',
        );

        // fix street address: sometimes street is array
        if (isset($address1['street']) && is_array($address1['street'])) {
            $address1['street'] = implode("\n", $address1['street']);
        }

        if (isset($address2['street']) && is_array($address2['street'])) {
            $address2['street'] = implode("\n", $address2['street']);
        }

        foreach ($compareFields as $field) {
            $field1 = (isset($address1[$field]) ? $address1[$field] : '');
            $field2 = (isset($address2[$field]) ? $address2[$field] : '');

            if ($field1 != $field2) {
                return false;
            }
        }

        return true;
    }

    public function getCustomerAddressId($customer, $addressData)
    {
        if (!$customer) {
            return false;
        }

        $billingAddress = $customer->getDefaultBillingAddress();

        if ($this->_addressMatch($addressData, $billingAddress->getData())) {
            return $billingAddress->getEntityId();
        }

        $shippingAddress = $customer->getDefaultShippingAddress();

        if ($this->_addressMatch($addressData, $shippingAddress->getData())) {
            return $shippingAddress->getEntityId();
        }

        $additionalAddresses = $customer->getAdditionalAddresses();

        foreach ($additionalAddresses as $additionalAddress) {
            if ($this->_addressMatch($addressData, $additionalAddress->getData())) {
                return $additionalAddress->getEntityId();
            }
        }

        return false;
    }

    public function getExtraOrderAttributeCode()
    {
       return Mage::getStoreConfig(self::KLARNA_CHECKOUT_EXTRA_ORDER_ATTRIBUTE);
    }

    public function excludeCartInKlarnaCheckout()
    {
        if (Mage::getStoreConfig(self::KLARNA_CHECKOUT_ENABLE_CART_ABOVE_KCO)) {
            $res = false;
        } else {
            $res = true;
        }
        return $res;
    }

    /*
     * 
     *
     */
    public function dispatchReserveInfo($order, $pno)
    {
        Mage::dispatchEvent( 'vaimo_klarna_pno_used_to_reserve', array(
            'store_id' => $order->getStoreId(),
            'order_id' => $order->getIncrementId(),
            'customer_id' => $order->getCustomerId(),
            'pno' => $pno
            ));
    }
    
    /*
     * Whenever a refund, capture, reserve or cancel is performed, we send out an event
     * This can be listened to for financial reconciliation
     *
     * @return void
     */
    public function dispatchMethodEvent($order, $eventcode, $amount, $method)
    {
        Mage::dispatchEvent( $eventcode, array(
            'store_id' => $order->getStoreId(),
            'order_id' => $order->getIncrementId(),
            'method' => $method,
            'amount' => $amount
            ));
        
        // Vaimo specific dispatch
        $event_name = NULL;
        switch ($eventcode) {
            case self::KLARNA_DISPATCH_RESERVED:
                $event_name = 'ic_order_success';
                break;
            case self::KLARNA_DISPATCH_CAPTURED:
                $event_name = 'ic_order_captured';
                break;
            case self::KLARNA_DISPATCH_REFUNDED:
                break;
            case self::KLARNA_DISPATCH_CANCELED:
                $event_name = 'ic_order_cancel';
                break;
        }
        if ($event_name) {
            Mage::dispatchEvent( $event_name, array("order" => $order) );
        }
    }

    public function SplitJsonStrings($json)
    {
        $q = false;
        $len = strlen($json);
        for($l=$c=$i=0; $i<$len; $i++) {
            $json[$i] == '"' && ($i>0 ? $json[$i-1] : '') != '\\' && $q = !$q;
            if (!$q && in_array($json[$i], array(" ", "\r", "\n", "\t"))){
                continue;
            }
            in_array($json[$i], array('{', '[')) && !$q && $l++;
            in_array($json[$i], array('}', ']')) && !$q && $l--;
            (isset($objects[$c]) && $objects[$c] .= $json[$i]) || $objects[$c] = $json[$i];
            $c += ($l == 0);
        }
        return $objects;
    }

    public function JsonDecode($json)
    {
        $res = array();
        $jsonArr = $this->SplitJsonStrings($json);
        if ($jsonArr) {
            foreach ($jsonArr as $jsonStr) {
                $decoded = json_decode($jsonStr, true);
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $res = array_merge_recursive($res, $decoded); // array_merge
                        break;
                    case JSON_ERROR_DEPTH:
                        $res = 'Maximum stack depth exceeded';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $res = 'Underflow or the modes mismatch';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $res = 'Unexpected control character found';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $res = 'Syntax error, malformed JSON';
                        break;
                    case JSON_ERROR_UTF8:
                        $res = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                        break;
                    default:
                        $res = 'Unknown error';
                        break;
                }
            }
        }
       return $res;
    }
    
    public function getTermsUrlLink($url)
    {
        if ($url) {
            if (stristr($url, 'http')) {
                $_termsLink = '<a href="' . $url . '" target="_blank">' . $this->__('terms and conditions') . '</a>';
            } else {
                $_termsLink = '<a href="' . Mage::getSingleton('core/url')->getUrl($url) . '" target="_blank">' . $this->__('terms and conditions') . '</a>';
            }
        } else {
            $_termsLink = '<a href="#" target="_blank">' . $this->__('terms and conditions') . '</a>';
        }
        return $_termsLink;
    }

    public function getTermsUrl($url)
    {
        if ($url) {
            if (stristr($url, 'http')) {
                $_termsLink = $url;
            } else {
                $_termsLink = Mage::getSingleton('core/url')->getUrl($url);
            }
        } else {
            $_termsLink = '';
        }
        return $_termsLink;
    }

    /**
     * Sets the function name, which is used in logs. This is set in each class construct
     *
     * @param string $functionName
     *
     * @return void
     */
    public function setFunctionNameForLog($functionName)
    {
        $_SESSION[self::LOG_FUNCTION_SESSION_NAME] = $functionName;
    }
    
    /**
     * Returns the function name set by the constructors in each class
     *
     * @return string
     */
    public function getFunctionNameForLog()
    {
        return array_key_exists(self::LOG_FUNCTION_SESSION_NAME, $_SESSION) ? $_SESSION[self::LOG_FUNCTION_SESSION_NAME] : '';
    }
    
    /**
     * Log function that does the writing to log file
     *
     * @param string $filename  What file to write to, will be placed in site/var/klarna/ folder
     * @param string $msg       Text to log
     *
     * @return void
     */
    protected function _log($filename, $msg)
    {
        Mage::log('PID(' . getmypid() . '): ' . $this->getFunctionNameForLog() . ': ' . $msg, null, $filename, true);
    }
    
    /**
     * Log function that does the writing to log file
     *
     * @param string $filename  What file to write to, will be placed in site/var/klarna/ folder
     * @param string $msg       Text to log
     *
     * @return void
     */
    protected function _logAlways($filename, $msg)
    {
        $logDir  = Mage::getBaseDir('var') . DS . 'log' . DS;
        $logFile = $logDir . $filename;

        try {
            if (!is_dir($logDir)) {
                mkdir($logDir);
                chmod($logDir, 0777);
            }
            if ( file_exists($logFile) ){
                $fp = fopen( $logFile, "a" );
            } else {
                $fp = fopen( $logFile, "w" );
            }
            if ( !$fp ) return null;
            fwrite( $fp, date("Y/m/d H:i:s") . ' ' . $this->getFunctionNameForLog() . ': ' . $msg . "\n" );
            fclose( $fp );
        } catch( Exception $e ) {
            return;
        }
    }
    
    /**
     * Log function that logs all Klarna API calls and replies, this to see what functions are called and what reply they get
     *
     * @param string $comment Text to log
     *
     * @return void
     */
    public function logKlarnaApi($comment)
    {
        $this->_log('klarnaapi.log', $comment);
        $this->logDebugInfo($comment);
    }
    
    /**
     * Log function used for various debug log information, array is optional
     *
     * @param string $info  Header of what is being logged
     * @param array $arr    The array to be logged
     *
     * @return void
     */
    public function logDebugInfo($info, $arr = NULL)
    {
        if (!$arr) {
            $this->_log('klarnadebug.log', $info);
        } else {
            if (is_array($arr)) {
                $this->_log('klarnadebug.log', print_r($arr, true));
            } elseif (is_object($arr)) {
                $this->_log('klarnadebug.log', print_r(array($arr), true));
            }
        }
    }
    
    protected function _logMagentoException($e)
    {
        Mage::logException($e);
    }
    
    /**
     * If there is an exception, this log function should be used
     * This is mainly meant for exceptions concerning klarna API calls, but can be used for any exception
     *
     * @param Exception $e
     *
     * @return void
     */
    public function logKlarnaException($e)
    {
        $this->_logMagentoException($e);
        $errstr = 'Exception:';
        if ($e->getCode()) $errstr = $errstr . ' Code: ' . $e->getCode();
        if ($e->getMessage()) $errstr = $errstr . ' Message: ' . $e->getMessage(); // $this->_decode()
        if ($e->getLine()) $errstr = $errstr . ' Row: ' . $e->getLine();
        if ($e->getFile()) $errstr = $errstr . ' File: ' . $e->getFile();
        $this->_logAlways('klarnaerror.log', $errstr);
    }
    
    public function getDefaultCountry($store = NULL)
    {
/* For shipping this should be called...
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $request = $taxCalculationModel->getRateRequest();
        x = $request->getCountryId();
        y = $request->getRegionId();
        z = $request->getPostcode();
*/
        if (version_compare(Mage::getVersion(), '1.6.2', '>=')) {
            $res = Mage::helper('core')->getDefaultCountry($store);
        } else {
            $res = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_COUNTRY, $store);
        }
        return $res;
    }

}