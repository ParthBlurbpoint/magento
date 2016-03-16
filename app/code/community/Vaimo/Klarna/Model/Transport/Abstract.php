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
 * This class is the base for all calculations and tests required
 * Payment methods will use Mage::getModel on the appropriate class and then set known information
 * For example, assign and reserve part of the code sets the quote, capture sets the order, refund sets the payment
 * When the record is set, this class will fill other variables of any known information, such as address, payment methods, store id, currency and language
 * As soon as that is done, you can use this class to do tests and set values that are retreived later in the code
 * This class does not have any database connection in itself, that's why it's extending Varian_Object rather than any Magento class
 *
 * Once a model has been created that inherits from this, one or more of setQuote, setOrder, setPayment, setInfoInstance,
 * setInvoice or setCreditmemo should be called. If none of these are called, a call to setStoreInformation() must be made
 * in order to set the required variables. That was previous included in construct, but was changed due to unit testing.
 *
 */

abstract class Vaimo_Klarna_Model_Transport_Abstract extends Varien_Object
{
    /**
     * The following variables are set depending on what child it is
     * Setting payment will automatically also set the order, as it is known in the payment object
     */
    protected $_quote = NULL;
    protected $_order = NULL;
    protected $_invoice = NULL;
    protected $_payment = NULL;
    protected $_creditmemo = NULL;

    /**
     * Info instance is set for example when doing a refund, it also tries to set the credit memo and order, if they are known
     */
    protected $_info_instance = NULL;

    /**
     * The current payment method
     */
    protected $_method = NULL;

    /**
     * Store id that should be used while loading settings etc
     * It's set to Mage::app()->getStore()->getId() initially
     * But is then changed as soon as one of the record variables above is set
     */
    protected $_storeId = NULL;

    /**
     * Country, Language and Currency code of the current store or of the current record
     */
    protected $_countryCode = '';
    protected $_languageCode = NULL;
    protected $_currencyCode = NULL;

    /**
     * Both addresses are set when the records above are set
     */
    protected $_shippingAddress = NULL;
    protected $_billingAddress = NULL;

    /**
     * Languages supported by Klarna
     */
    protected $_supportedLanguages = array(
                                    'da', // Danish
                                    'de', // German
                                    'en', // English
                                    'fi', // Finnish
                                    'nb', // Norwegian
                                    'nl', // Dutch
                                    'sv', // Swedish
                                    );

    /**
     * Countries supported by Klarna (array not used)
     */
    protected $_supportedCountries = array(
                                    'AT', // Austria
                                    'DK', // Danmark
                                    'DE', // Germany
                                    'FI', // Finland
                                    'NL', // Netherlands
                                    'NO', // Norway
                                    'SE', // Sweden
                                    );

    
    protected $_moduleHelper = NULL;    

    /**
     * Constructor
     * setStoreInfo parameter added for Unittesting, never set otherwise
     *
     * @param bool $setStoreInfo
     */
    public function __construct($setStoreInfo = true, $moduleHelper = NULL)
    {
        parent::__construct();
        if ($setStoreInfo) {
            $this->setStoreInformation();
        }

        $this->_moduleHelper = $moduleHelper;
        if ($this->_moduleHelper==NULL) {
            $this->_moduleHelper = Mage::helper('klarna');
        }
    }

    /**
     * @return Vaimo_Klarna_Helper_Data
     */
    protected function _getHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Will call normal Mage::getStoreConfig
     * It's in it's own function, so it can be mocked in tests
     * 
     * @param string $field
     * @param string $storeId
     *
     * @return string
     */
    protected function _getConfigDataCall($field, $storeId)
    {
        return Mage::getStoreConfig($field, $storeId);
    }
    
    /**
     * Will set current store language, but there is a language override in the Klarna payment setting.
     * Language is sent to the Klarna API
     * The reason for the override is for example if you use the New Norwegian language in the site (nn as code),
     * Klarna will not allow that code, so we have the override
     *
     * @return void
     */
    protected function _setDefaultLanguageCode()
    {
        if ($this->getConfigData('klarna_language')) {
            $this->_languageCode = $this->getConfigData('klarna_language');
        } else {
            $localeCode = $this->_getConfigDataCall(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $this->_getStoreId() );
            $this->_languageCode = $this->_getLocaleLanguageCode($localeCode);
        }
        if (!in_array($this->_languageCode, $this->_supportedLanguages)) {
            $this->_languageCode = 'en';
        }
    }
    
    /**
     * Gets the Default country of the store
     *
     * @return string
     */
    protected function _getDefaultCountry()
    {
        $res = $this->_getHelper()->getDefaultCountry($this->_getStoreId());
        return strtoupper($res);
    }

    /**
     * Sets the default currency to that of this store id
     *
     * @return void
     */
    protected function _setDefaultCurrencyCode()
    {
        $currencyCode = $this->_getConfigDataCall(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_DEFAULT, $this->_getStoreId() );
        $this->_currencyCode = strtoupper($currencyCode);
    }

    /**
     * Sets the default country to that of this store id
     *
     * @return void
     */
    protected function _setDefaultCountry()
    {
        $this->_countryCode = $this->_getDefaultCountry();
    }

    protected function _getMageStore()
    {
        return Mage::app()->getStore()->getId();
    }
    
    /**
     * Sets the store of this class and then updates the default values
     *
     * If no record is set, like setQuote or setOrder, the code that
     * gets this model MUST call setStoreInformation. It used to be part
     * of construct, but it was removed from there because of unit tests
     *
     * @return void
     */
    public function setStoreInformation($storeId = NULL)
    {
        if ($storeId === NULL) {
            $this->_storeId = (int)$this->_getMageStore();
        } else {
            $this->_storeId = (int)$storeId;
        }
        $this->_setDefaultLanguageCode();
        $this->_setDefaultCurrencyCode();
        $this->_setDefaultCountry();
    }
    
    /**
     * Parse a locale code into a language code Klarna can use.
     *
     * @param string $localeCode The Magento locale code to parse
     *
     * @return string
     */
    protected function _getLocaleLanguageCode($localeCode)
    {
        $res = NULL;
        $preg_result = preg_match("/([a-z]+)_[A-Z]+/", $localeCode, $collection);
        if ($preg_result !== 0) {
            $res = $collection[1];
        }
        return $res;
    }

    /**
     * This function is only called if multiple countries are allowed
     * And one chooses one of the countries that aren't the default one
     * It then changes the language, to match with the country.
     *
     * @return void
     */
    protected function _updateNonDefaultCountryLanguage()
    {
        switch ($this->_countryCode) {
            case 'AT':
                $this->_languageCode = 'de';
                break;
            case 'DK':
                $this->_languageCode = 'da';
                break;
            case 'DE':
                $this->_languageCode = 'de';
                break;
            case 'FI':
                $this->_languageCode = 'fi';
                break;
            case 'NL':
                $this->_languageCode = 'nl';
                break;
            case 'NO':
                $this->_languageCode = 'nb';
                break;
            case 'SE':
                $this->_languageCode = 'sv';
                break;
        }
    }

    /**
     * Once we have a record in one of the record variables, we update the addresses and then we set the country to
     * that of the shipping address or billing address, if shipping is empty
     *
     * @return void
     */
    protected function _updateCountry()
    {
        if ($this->_shippingAddress && $this->_shippingAddress->getCountry()) {
            $this->_countryCode = strtoupper($this->_shippingAddress->getCountry());
        } elseif ($this->_billingAddress && $this->_billingAddress->getCountry()) {
            $this->_countryCode = strtoupper($this->_billingAddress->getCountry());
        }
        if ($this->_countryCode!=$this->_getDefaultCountry()) {
            $this->_updateNonDefaultCountryLanguage();
        } else {
            // It's only necessary to call this if updateNonDefaultCountryLanguage
            // has been called. A minor speed improvement possible here, as
            // _updateCountry() is called quite a number of times.
            $this->_setDefaultLanguageCode();
        }
    }
    
    /**
     * Set current shipping address
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     *
     * @return void
     */
    protected function _setShippingAddress($address)
    {
        if ($address) {
            $this->_shippingAddress = $address;
        }
        $this->_updateCountry();
    }

    /**
     * Set current billing address
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     *
     * @return void
     */
    protected function _setBillingAddress($address)
    {
        $this->_billingAddress = $address;
        if ($this->_shippingAddress==NULL) {
            $this->_shippingAddress = $address;
        }
        $this->_updateCountry();
    }

    /**
     * Set current addresses from quote and updates this class currency
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return void
     */
    protected function _setAddressesFromQuote($quote)
    {
        $this->_setShippingAddress($quote->getShippingAddress());
        $this->_setBillingAddress($quote->getBillingAddress());
        $this->_currencyCode = $quote->getQuoteCurrencyCode();
    }

    /**
     * Set current addresses from order and updates this class currency
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return void
     */
    protected function _setAddressesFromOrder($order)
    {
        $this->_setShippingAddress($order->getShippingAddress());
        $this->_setBillingAddress($order->getBillingAddress());
        $this->_currencyCode = $order->getOrderCurrencyCode();
    }

    /**
     * Sets the payment method, either directly from the top class or when the appropriate record object is set
     *
     * @param string
     *
     * @return void
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
     * Sets the order of this class plus updates what is known on the order, such as payment method, store and address
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return void
     */
    public function setOrder($order)
    {
        $this->_order = $order;
        if (!$this->getQuote() && $order->getQuote()) {
            $this->_quote = $order->getQuote();
        }
        $this->setMethod($this->getOrder()->getPayment()->getMethod());
        $this->setStoreInformation($this->getOrder()->getStoreId());
        $this->_setAddressesFromOrder($order);
    }
    
    /**
     * Sets the quote of this class plus updates what is known on the quote, store and address
     * Method can also be set by this function, if it is known
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string Payment method
     *
     * @return void
     */
    public function setQuote($quote, $method = NULL)
    {
        $this->_quote = $quote;
        if ($method) {
            $this->setMethod($method);
        }
        if ($quote) {
            $this->setStoreInformation($this->getQuote()->getStoreId());
            $this->_setAddressesFromQuote($quote);
        }
    }
    
    /**
     * Sets the invoice of this class plus updates current store
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return void
     */
    public function setInvoice($invoice)
    {
        $this->_invoice = $invoice;
        if (!$this->getOrder() && $invoice->getOrder()) {
            $this->setOrder($invoice->getOrder());
        }
    }
    
    /**
     * Sets the creditmemo of this classplus updates what is known of other varibles, such as order, creditmemo and invoice
     *
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @return void
     */
    public function setCreditmemo($creditmemo)
    {
        $this->_creditmemo = $creditmemo;
    }

    /**
     * Sets the payment of this class
     * @param Mage_Sales_Model_Order_Payment $payment
     *
     * @return void
     */
    public function setPayment($payment)
    {
        $this->_payment = $payment;
        if ($this->getPayment()) {
            $this->setOrder($this->getPayment()->getOrder());
            if ($this->getPayment()->getCreditmemo()) {
                $this->setCreditmemo($this->getPayment()->getCreditmemo());
                if ($this->getCreditmemo()->getInvoice()) {
                    $this->setInvoice($this->getCreditmemo()->getInvoice());
                }
            }
        }
    }
    
    /**
     * Sets the info instance of this class plus updates what is known of other varibles, such as order and creditmemo
     *
     * @param Mage_Payment_Model_Info $info
     *
     * @return void
     */
    public function setInfoInstance($info)
    {
        $this->_info_instance = $info;
        if ($this->getInfoInstance()) {
            $this->setCreditmemo($this->getInfoInstance()->getCreditmemo());
            if ($this->getCreditmemo()) {
                $this->setOrder($this->getCreditmemo()->getOrder());
            }
        }
    }

    /**
     * Check if consent is needed
     *
     * @return boolean
     */
    public function needConsent()
    {
        switch ($this->_getCountryCode()) {
            case 'DE':
            case 'AT':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if an asterisk is needed
     *
     * @return boolean
     */
    public function needAsterisk()
    {
        return false;
    }

    /**
     * Check if gender is needed
     *
     * @return boolean
     */
    public function needGender()
    {
        switch ($this->_getCountryCode()) {
            case 'NL':
            case 'DE':
            case 'AT':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if date of birth is needed
     *
     * @return boolean
     */
    public function needDateOfBirth()
    {
        switch ($this->_getCountryCode()) {
            case 'NL':
            case 'DE':
            case 'AT':
                return true;
            default:
                return false;
        }
    }
    
    /**
     * Some countries supports to get more details to create request
     *
     * @return boolean
     */
    public function moreDetailsToKCORequest()
    {
        switch ($this->_getCountryCode()) {
//            case 'NL':
            case 'DE':
            case 'AT':
                return true;
            default:
                return false;
        }
    }
    
    /**
     * Norway has special rules regarding the details of the payment plan you are selecting
     *
     * @return boolean
     */
    public function needExtraPaymentPlanInformaton()
    {
        switch ($this->_getCountryCode()) {
            case 'NO':
                return true;
            default:
                return false;
        }
    }

    /**
     * Return the fields a street should be split into.
     *
     * @return array
     */
    public function getSplit()
    {
        switch ($this->_getCountryCode()) {
            case 'DE':
                return array('street', 'house_number');
            case 'NL':
                return array('street', 'house_number', 'house_extension');
            default:
                return array('street');
        }
    }

    /**
     * Is the sum below the limit allowed for the given _country?
     * This contains a hardcoded value for NL.
     * Meaning, if a customer shops for over 250 EUR, it won't be allowed to use any part payment option...
     * I'm leaving this as hardcoded... But should get a better solution...
     *
     * @param float  $sum    Sum to check
     * @param string $method payment method
     *
     * @return boolean
     */
    public function isBelowAllowedHardcodedLimit($sum)
    {
        if ($this->_getCountryCode() !== 'NL') {
            return true;
        }

        if ($this->getMethod() === Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE) {
            return true;
        }

        if (((double)$sum) <= 250.0) { // Hardcoded
            return true;
        }

        return false;
    }

    /**
     * Do we need to call getAddresses
     *
     * @return boolean
     */
    public function useGetAddresses()
    {
        switch ($this->_getCountryCode()) {
            case 'SE':
                return true;
            default:
                return false;
        }
    }

    /**
     * Are Company Purchases supported?
     *
     * @return boolean
     */
    public function isCompanyAllowed()
    {
        switch ($this->_getCountryCode()) {
            case 'NL':
            case 'DE':
            case 'AT':
                return false;
            default:
                return true;
        }
    }
    
    /**
     * Do we need to display the autofill warning label
     *
     * @return boolean
     */
    public function AllowSeparateAddress()
    {
        $res = false;
        switch ($this->_getCountryCode()) {
            case 'GB':
            case 'DE':
                $res = true;
                break;
        }
        switch ($this->getConfigData('allow_separate_address')) {
            case 0:
                $res = false;
                break;
            case 1:
                $res = true;
                break;
        }
        return $res;
    }
    
    
    /**
     * Do we need to display the autofill warning label
     *
     * @return boolean
     */
    public function shouldDisplayAutofillWarning()
    {
        switch ($this->_getCountryCode()) {
            case 'AT':
            case 'DE':
                $this->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
                return $this->getConfigData('active');
            default:
                return false;
        }
    }

    public function getAvailableMethods()
    {
        $res = array();
        $this->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE);
        if ($this->getConfigData('active')) {
            $res[] = $this->getMethod();
        }
        $this->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT);
        if ($this->getConfigData('active')) {
            $res[] = $this->getMethod();
        }
        $this->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL);
        if ($this->getConfigData('active')) {
            $res[] = $this->getMethod();
        }
        $this->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        if ($this->getConfigData('active')) {
            $res[] = $this->getMethod();
        }
        return $res;
    }

    /**
     * Check if shipping and billing should be the same
     *
     * @return boolean
     */
    public function shippingSameAsBilling()
    {
        if ($this->getConfigData('allow_separate_address')) {
            $res = false;
        } else {
            $res = true;
        }
        if (!$res) {
            if ($this->getQuote()) {
                $shipping = $this->getQuote()->isVirtual() ? null : $this->getQuote()->getShippingAddress();
                if ($shipping && $shipping->getSameAsBilling()) {
                    $res = true;
                }
            }
        }
        return $res;
    }

    /**
     * Check if current country is allowed
     *
     * @return boolean
     */
    public function isCountryAllowed()
    {
        if ($this->_getCountryCode() != $this->_getDefaultCountry()) {
            if ($this->getConfigData('allowspecific')) {
                $allowedCountries = $this->getConfigData('specificcountry');
                if ($allowedCountries) {
                    if (in_array($this->_getCountryCode(), explode(",", $allowedCountries))) {
                        return true;
                    }
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Check if method should be disabled if company field is filled in
     * See isCompanyAllowed, perhaps we should merge functions...
     *
     * @return boolean
     */
    public function showMethodForCompanyPurchases()
    {
        if ($this->getConfigData('disable_company_purchase')) {
            return false;
        }
        return true;
    }

    /**
     * Function to read correct payment method setting
     *
     * @param string $field
     *
     * @return string
     */
    public function getConfigData($field)
    {
        if ($this->getMethod() && $this->_getStoreId()!==NULL) {
            $res = $this->_getConfigDataCall('payment/' . $this->getMethod() . '/' . $field, $this->_getStoreId());
        } else {
            $res = NULL;
        }
        return $res;
    }
    
    /**
     * Returns this class country code
     *
     * @return string
     */
    protected function _getCountryCode()
    {
        return $this->_countryCode;
    }
    
    /**
     * Returns this class language code
     *
     * @return string
     */
    protected function _getLanguageCode()
    {
        return $this->_languageCode;
    }
    
    /**
     * Returns this locale string
     *
     * @return string
     */
    protected function _getLocale()
    {
        return $this->_getLanguageCode() . "_" . $this->_getCountryCode();
    }
    
    /**
     * Returns this class currency code
     *
     * @return string
     */
    protected function _getCurrencyCode()
    {
        return $this->_currencyCode;
    }
    
    /**
     * Returns this class store id
     *
     * @return int
     */
    protected function _getStoreId()
    {
        return $this->_storeId;
    }
    
    /**
     * Returns this class payment method
     *
     * @return string
     */
    // Can probably be protected...
    public function getMethod()
    {
        return $this->_method;
    }
    
    /**
     * Returns the order set in this class
     *
     * @return Mage_Sales_Model_Order
     */
    // Can probably be protected...
    public function getOrder()
    {
        return $this->_order;
    }
    
    /**
     * Returns the creditmemo set in this class
     *
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    // Can probably be protected...
    public function getCreditmemo()
    {
        return $this->_creditmemo;
    }
    
    /**
     * Returns the invoice set in this class
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    // Can probably be protected...
    public function getInvoice()
    {
        return $this->_invoice;
    }
    
    /**
     * Returns the payment set in this class
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    // Can probably be protected...
    public function getPayment()
    {
        return $this->_payment;
    }
    
    /**
     * Returns the info instance set in this class
     *
     * @return Mage_Payment_Model_Info
     */
    // Can probably be protected...
    public function getInfoInstance()
    {
        return $this->_info_instance;
    }
    
    /**
     * Returns the quote set in this class
     *
     * @return Mage_Sales_Model_Quote
     */
    // Can probably be protected...
    public function getQuote()
    {
        return $this->_quote;
    }

    /**
     * Returns the billing address set in this class
     *
     * @return Mage_Customer_Model_Address_Abstract
     */
    public function getBillingAddress()
    {
        return $this->_billingAddress;
    }

    /**
     * Returns the shipping address set in this class
     *
     * @return Mage_Customer_Model_Address_Abstract
     */
    public function getShippingAddress()
    {
        return $this->_shippingAddress;
    }
    
    protected function _formatPrice($price)
    {
        return Mage::app()->getStore($this->getQuote()->getStoreId())->formatPrice($price, false);
    }
    
    /**
     * Returns the current payment methods title, as set in Klarna Payment settings
     *
     * @return string
     */
    public function getMethodTitleWithFee($fee = NULL, $presetTitle = NULL)
    {
        if ($presetTitle) {
            $res = $presetTitle;
        } else {
            $res = $this->_getHelper()->__($this->getConfigData('title'));
        }
        if ($this->getQuote() && $this->getMethod()==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE) {
            if ($fee) {
                $res .= ' (' . $this->_formatPrice($fee) . ')';
            }
        }
        return $res;
    }

    /**
     * A function that returns a few setup values unique to the current active session
     * If currently selected method is not setup it will default to Invoice method and try again
     * It uses recursion, but can only call itself once
     *
     * Klarna checkout is different, if one places an order with KCO and then disables the option, it should
     * still be possible to create the setup of it.
     *
     * @return Varien_Object
     */
    public function getKlarnaSetup()
    {
        try {
            if (!$this->getConfigData('active')) {
                if ($this->getMethod()!=Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT) {
                    Mage::throwException($this->_getHelper()->__('Current payment method not available'));
                }
            }
            $res = new Varien_Object(
                array(
                    'merchant_id' => $this->getConfigData('merchant_id'),
                    'shared_secret' => $this->getConfigData('shared_secret'),
                    'country_code' => $this->_getCountryCode(),
                    'language_code' => $this->_getLanguageCode(),
                    'locale_code' => $this->_getLocale(),
                    'currency_code' => $this->_getCurrencyCode(),
                    'terms_url' => $this->getConfigData('terms_url'),
                    'host' => $this->getConfigData('host'),
                    )
                );
        } catch( Exception $e ) {
            if ($this->getMethod()!=Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE) {
                $this->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE);
                $res = $this->getKlarnaSetup();
            } else {
                $res = new Varien_Object();
                $this->_getHelper()->logKlarnaException($e);
            }
        }
        return $res;
    }

    /**
     * Creates the path to the Klarna logotype, it depends on payment method, intended placemen and your merchant id
     *
     * @param $width the width of the logotype
     * @param $position const defined in Klarna Helper (checkout, product or frontpage)
     * @param $type optional const defined in Klarna Helper (invoice, account, both) if not provided, it will look at current payment method to figure it out
     *
     * @return string containing the full path to image
     */
    public function getKlarnaLogotype($width, $position, $type = NULL)
    {
        $res = "";
        if (!$type) {
            $type = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BASIC; // KLARNA_LOGOTYPE_TYPE_INVOICE;
            switch ($this->getMethod()) {
                case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT:
                    $type = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BASIC;
                    break;
                case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT:
                    $type = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BASIC; // KLARNA_LOGOTYPE_TYPE_ACCOUNT;
                    break;
                case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL:
                    if ($this->getConfigData('cdn_logotype_override')) {
                        $res = $this->getConfigData('cdn_logotype_override');
                        $res .= '" width="' . $width; // Adding width to the file location like this is ugly, but works fine
                        return $res;
                    }
                    $type = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BASIC; // KLARNA_LOGOTYPE_TYPE_ACCOUNT;
                    break;
            }
        }

        switch ($position) {
            case Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_POSITION_FRONTEND:
                if ($type==Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BOTH) {
                    $res = 'https://cdn.klarna.com/public/images/' . $this->_getCountryCode() . '/badges/v1/' . $type . '/' . $this->_getCountryCode() . '_' . $type . '_badge_banner_blue.png?width=' . $width . '&eid=' . $this->getConfigData('merchant_id');
                } elseif ($type==Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BASIC) {
                    $res = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/' . $this->_getLanguageCode() . "_" . strtolower($this->_getCountryCode()) . '/' . $type . '/blue-black.png?width=' . $width . '&eid=' . $this->getConfigData('merchant_id');
//                    $res = 'https://cdn.klarna.com/public/images/' . $this->_getCountryCode() . '/logos/v1/' . $type . '/' . $this->_getCountryCode() . '_' . $type . '_logo_std_blue-black.png?width=' . $width . '&eid=' . $this->getConfigData('merchant_id');
                } elseif ($type==Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_CHECKOUT) {
                    $res = 'https://cdn.klarna.com/1.0/shared/image/generic/badge/' . $this->_getLanguageCode() . "_" . strtolower($this->_getCountryCode()) . '/checkout/short-blue.png?width=' . $width . '&eid=' . $this->getConfigData('merchant_id'); // ' . $type . '/
                } else {
                    $res = 'https://cdn.klarna.com/public/images/' . $this->_getCountryCode() . '/badges/v1/' . $type . '/' . $this->_getCountryCode() . '_' . $type . '_badge_std_blue.png?width=' . $width . '&eid=' . $this->getConfigData('merchant_id');
                }
                break;
            case Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_POSITION_PRODUCT:
                $res = 'https://cdn.klarna.com/public/images/' . $this->_getCountryCode() . '/logos/v1/' . $type . '/' . $this->_getCountryCode() . '_' . $type . '_logo_std_blue-black.png?width=' . $width . '&eid=' . $this->getConfigData('merchant_id');
                break;
            case Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_POSITION_CHECKOUT:
                $res = 'https://cdn.klarna.com/public/images/' . $this->_getCountryCode() . '/badges/v1/' . $type . '/' . $this->_getCountryCode() . '_' . $type . '_badge_std_blue.png?width=' . $width . '&eid=' . $this->getConfigData('merchant_id');
                break;
        }
        return $res;
    }

}
