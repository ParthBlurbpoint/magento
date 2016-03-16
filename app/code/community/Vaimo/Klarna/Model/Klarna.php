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
class Vaimo_Klarna_Model_Klarna extends Vaimo_Klarna_Model_Klarna_Abstract
{
    protected $_api = NULL;

    protected static $_session_key = 'klarna_address';
    protected static $_pclasses_key = 'klarna_pclasses';

    public function __construct($setStoreInfo = true, $moduleHelper = NULL, $entGWHelper = NULL, $salesHelper = NULL, $taxCalculation = NULL)
    {
        parent::__construct($setStoreInfo, $moduleHelper, $entGWHelper, $salesHelper, $taxCalculation);
        $this->_getHelper()->setFunctionNameForLog('klarna');
    }
    
    /**
     * Function added for Unit testing
     *
     * @param Vaimo_Klarna_Model_Api_Abstract $apiObject
     */
    public function setApi(Vaimo_Klarna_Model_Api_Abstract $apiObject)
    {
        $this->_api = $apiObject;
    }

    /**
     * Will return the API object, it set, otherwise null
     *
     * @return Vaimo_Klarna_Model_Api_Xmlrpc|Vaimo_Klarna_Model_Api_Rest|Vaimo_Klarna_Model_Api_Kco
     */
    public function getApi()
    {
        return $this->_api;
    }

    /**
     * Could have been added to getApi, but I made it separate for Unit testing
     *
     * @param $storeId
     * @param $method
     * @param $functionName
     */
    protected function _initApi($storeId, $method, $functionName)
    {
        if (!$this->getApi()) {
            /** @var Vaimo_Klarna_Model_Api $klarnaApiModel */
            $klarnaApiModel = Mage::getModel('klarna/api');
            $this->setApi($klarnaApiModel->getApiInstance($storeId, $method, $functionName));
        }
    }

    /**
     * Init funcition
     *
     * @todo If storeid is null, we need to find first store where Klarna is active, not just trust that default store has it active...
     */
    protected function _init($functionName)
    {
        $this->_getHelper()->setFunctionNameForLog($this->_getHelper()->getFunctionNameForLog() . '-' . $functionName);
        $this->_initApi($this->_getStoreId(), $this->getMethod(), $functionName);
        $this->getApi()->init($this->getKlarnaSetup());
        $this->getApi()->setTransport($this->_getTransport());
    }

    public function reserve($amount)
    {
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_RESERVE);
            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $this->_setAdditionalInformation($this->getPayment()->getAdditionalInformation());
            $items = $this->getPayment()->getKlarnaItemList();
            $this->_createGoodsList($items);
            $this->_getHelper()->logKlarnaApi('Call with personal ID ' . $this->getPNO());
            
            $this->getApi()->setGoodsListReserve();
            $this->getApi()->setAddresses($this->getBillingAddress(), $this->getShippingAddress(), $this->_getAdditionalInformation());
            $res = $this->getApi()->reserve();

            $this->_getHelper()->logKlarnaApi('Response ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS] . ' - ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID]);
            if ($res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS]==Vaimo_Klarna_Helper_Data::KLARNA_STATUS_PENDING) {
                if ($this->getConfigData('pending_status_action')) {
                    $this->cancel($res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID]);
                    Mage::throwException($this->_getHelper()->__('Unable to pay with Klarna, please choose another payment method'));
                }
            }

            $this->_getHelper()->dispatchMethodEvent($this->getOrder(), Vaimo_Klarna_Helper_Data::KLARNA_DISPATCH_RESERVED, $this->getOrder()->getTotalDue(), $this->getMethod());

            $this->_cleanAdditionalInfo();
            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }
    
    public function capture($amount)
    {
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_CAPTURE);
            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $this->_setAdditionalInformation($this->getPayment()->getAdditionalInformation());
            $items = $this->getPayment()->getKlarnaItemList();
            $this->_createGoodsList($items);

            $reservation_no = $this->_getReservationNo();
            $this->_getHelper()->logKlarnaApi('Call with reservation ID ' . $reservation_no);

            $this->getApi()->setGoodsListCapture($amount);
            $this->getApi()->setAddresses($this->getBillingAddress(), $this->getShippingAddress(), $this->_getAdditionalInformation());
            $this->getApi()->setShippingDetails($this->_createShippingDetails($this->getOrder(), $items));

            $res = $this->getApi()->capture($reservation_no, $amount, $this->getConfigData('send_klarna_email'));

            $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_FEE_CAPTURED] = $this->_feeAmountIncluded();

            $this->_getHelper()->dispatchMethodEvent($this->getOrder(), Vaimo_Klarna_Helper_Data::KLARNA_DISPATCH_CAPTURED, $this->getOrder()->getTotalDue(), $this->getMethod());

            $this->_getHelper()->logKlarnaApi('Response ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS] . ' - ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID]);

            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }
    
    public function refund($amount)
    {
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_REFUND);
            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $invoice_no = $this->getInfoInstance()->getParentTransactionId();
            $this->_setAdditionalInformation($this->getInfoInstance()->getAdditionalInformation());
            $items = $this->getPayment()->getKlarnaItemList();
            $this->_createRefundGoodsList($items);

            $res = $this->getApi()->refund($amount, $invoice_no);
            
            $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_FEE_REFUNDED] = $this->_feeAmountIncluded();

            $this->_getHelper()->dispatchMethodEvent($this->getOrder(), Vaimo_Klarna_Helper_Data::KLARNA_DISPATCH_REFUNDED, $amount, $this->getMethod());

            $this->_getHelper()->logKlarnaApi('Response ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS] . ' - ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID]);

            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }
    
    public function cancel($direct_rno = NULL)
    {
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_CANCEL);
            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $this->_setAdditionalInformation($this->getPayment()->getAdditionalInformation());

            if ($direct_rno) {
                $reservation_no = $direct_rno;
            } else {
                $reservation_no = $this->_getReservationNo();
            }
            $this->_getHelper()->logKlarnaApi('Call with reservation ID ' . $reservation_no);
            
            if ($this->getOrder()->getTotalPaid()>0) {
                $res = $this->getApi()->release($reservation_no);
            } else {
                $res = $this->getApi()->cancel($reservation_no);
            }

            $this->_getHelper()->dispatchMethodEvent($this->getOrder(), Vaimo_Klarna_Helper_Data::KLARNA_DISPATCH_CANCELED, $this->getOrder()->getTotalDue(), $this->getMethod());

            $this->_getHelper()->logKlarnaApi('Response ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS]);

            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }

    public function checkStatus()
    {
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_CHECKSTATUS);
            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $this->_setAdditionalInformation($this->getPayment()->getAdditionalInformation());
            
            $reservation_no = $this->_getReservationNo();
            $this->_getHelper()->logKlarnaApi('Call with reservation ID ' . $reservation_no);

            $res = $this->getApi()->checkStatus($reservation_no);

            $this->_getHelper()->logKlarnaApi('Response ' . $res[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS]);

            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }
    
    /*
     * I have copied the cache function from previous Klarna module, it's only for this session
     *
     * @return array
     */
    public function getAddresses($personal_id)
    {
        try {
            $cache = array();

            if (array_key_exists(self::$_session_key, $_SESSION)) {
                $cache = unserialize( base64_decode($_SESSION[self::$_session_key]) );
            }
            if (array_key_exists($personal_id, $cache)) {
                return $cache[$personal_id];
            }

            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_ADDRESSES);
            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $this->_getHelper()->logKlarnaApi('Call with Personal ID ' . $personal_id);

            $res = $this->getApi()->getAddresses($personal_id);

            $this->_getHelper()->logKlarnaApi('Response ' .'OK');

            $cache[$personal_id] = $res;
            $_SESSION[self::$_session_key] = base64_encode( serialize($cache) );

            $this->_getHelper()->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }

    /**
     * Update addresses with data from our checkout box
     *
     * @return void
     */
    public function updateAssignAddress()
    {
        /*
         * getAddress is only allowed in Sweden, so this code is for Sweden only
         */
        if ($this->useGetAddresses()) {
            if (!$this->getPostValues('pno') || !$this->getPostValues('address_id')) {
                /*
                 * OneStepCheckout saves payment method upon load, which means an error message must not be produced
                 * in this function. Authorize will attempt to use the value and give an error message, which means
                 * it will be checked and reported anyway
                 */
                if (!$this->_getHelper()->isOneStepCheckout() && !$this->_getHelper()->isQuickCheckout()) {
                    Mage::throwException($this->_getHelper()->__(
                        'Unknown address, please specify correct personal id in the payment selection and press Fetch again, or use another payment method'
                        )
                    );
                }
            }
            if ($this->getPostValues('pno') && $this->getPostValues('address_id')) {
                $addr = $this->_getSelectedAddress($this->getPostValues('pno'), $this->getPostValues('address_id'));
                if ($addr!=NULL) {
                    /*
                     * This is not approved by Klarna, so address will be updated only when order is placed. This is NOT a bug.
                     */
                    // $this->_updateWithSelectedAddress($addr);
                } else {
                    /*
                     * No error message here if using OneStepCheckout
                     */
                    if (!$this->_getHelper()->isOneStepCheckout() && !$this->_getHelper()->isQuickCheckout()) {
                        Mage::throwException($this->_getHelper()->__(
                            'Unknown address, please specify correct personal id in the payment selection and press Fetch again, or use another payment method'
                            )
                        );
                    }
                }
            }
        }

        /*
         *  Update the addresses with values from the checkout
         */
        $this->_updateAddress($this->getShippingAddress());
        $this->_updateAddress($this->getBillingAddress(), 'phonenumber');

    }

    /**
     * Update addresses with data from our checkout box
     *
     * @return void
     */
    public function updateAuthorizeAddress()
    {
        //Update with the getAddress call for Swedish customers
        if ($this->useGetAddresses()) {
            $addr = $this->_getSelectedAddress($this->getPayment()->getAdditionalInformation('pno'), $this->getPayment()->getAdditionalInformation('address_id'));
            if ($addr!=NULL) {
                $this->_updateWithSelectedAddress($addr);
            } else {
                Mage::throwException($this->_getHelper()->__('Unknown address, please specify correct personal id in the payment selection and press Fetch again, or use another payment method'));
            }
        }

        //Check to see if the addresses must be same. If so overwrite shipping
        //address with the billing address.
        if ($this->shippingSameAsBilling()) {
            $this->updateBillingAddress();
//            $this->updateShippingAddress();
        }
    }

    /**
     * Get a matching address using getAddresses
     *
     * @return array
     */
    protected function _getSelectedAddress($pno, $address_id)
    {
        try {
            $addresses = $this->getAddresses($pno);
            foreach ($addresses as $address) {
                if ($address['id']==$address_id) {
                    return $address;
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
            return NULL;
        }
        return NULL;
    }
    
    /*
     * 
     *
     */
    public function reloadAllPClasses()
    {
        try {
            $countries = $this->_getKlarnaActiveStores();

            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_PCLASSES);

            $this->_getHelper()->logKlarnaApi('Call clear');
            $this->getApi()->clearPClasses();
            $this->_getHelper()->logKlarnaApi('Call clear OK');

        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }

        foreach ($countries as $storeId) {
            try {
                $this->setStoreInformation($storeId);
                $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_PCLASSES); // Need to call it again because we now have new storeId

                $this->_getHelper()->logKlarnaApi('Call fetch all');
                $this->getApi()->fetchPClasses($storeId);
                $this->_getHelper()->logKlarnaApi('Call fetch all OK');

            } catch (KlarnaException $e) {
                Mage::throwException($e->getMessage());
            }
        }
    }

    /*
     * 
     *
     */
    public function getValidCheckoutPClasses($method)
    {
        try {
            $amount = $this->getQuote()->getGrandTotal();

            $key = $method . ":" . round($amount,2);
            $cache = array();

            if (array_key_exists(self::$_pclasses_key, $_SESSION)) {
                $cache = unserialize( base64_decode($_SESSION[self::$_pclasses_key]) );
            }
            if (array_key_exists($key, $cache)) {
                return $cache[$key];
            }
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_PCLASSES);
            $res = $this->getApi()->getValidCheckoutPClasses($method, $amount);

            $cache[$key] = $res;
            $_SESSION[self::$_pclasses_key] = base64_encode( serialize($cache) );

        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }
    
    /*
     * 
     *
     */
    protected function _getSpecificPClass($id)
    {
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_PCLASSES);
            $amount = $this->getQuote()->getGrandTotal();
            $this->_getHelper()->logKlarnaApi('Call get specific');

            $res = $this->getApi()->getSpecificPClass($id, $amount);

            $this->_getHelper()->logKlarnaApi('Response OK');
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
        }
        return $res;
    }
    
    /*
     * 
     *
     */
    public function getDisplayAllPClasses()
    {
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_PCLASSES);
            $this->_getHelper()->logKlarnaApi('Call get display all');

            $res = $this->getApi()->getDisplayAllPClasses();

            $this->_getHelper()->logKlarnaApi('Response OK');
        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }

    public function getPClassDetails($id)
    {
        $pclassArray = $this->_getSpecificPClass($id);
        $res = new Varien_Object($pclassArray);
        return $res;
    }
    
    /**
     *
     *
     */
    public function getCheckoutService($method = NULL)
    {
        try {
            $res = NULL;
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_CHECKOUTSERVICES);
            $amount = $this->getQuote()->getGrandTotal();
            $currency = $this->_getCurrencyCode();
            $data = $this->getApi()->getCheckoutService($amount, $currency);
            if (!isset($data['payment_methods'])) {
                return NULL;
            }
            $paymentMethods = $data['payment_methods'];
            // If method is set, filter out the ones that doesn't belong
            $paymentMethodsFiltered = array();
            if ($method) {
                foreach ($paymentMethods as $paymentMethod) {
                    if (isset($paymentMethod['group'])) {
                        if (isset($paymentMethod['group']['code'])) {
                            $paymentMethod['vaimo_klarna_method'] = $method;
                            switch ($method) {
                                case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE:
                                    if ($paymentMethod['group']['code']=='invoice' && sizeof($paymentMethod['details'])==0) {
                                        $paymentMethodsFiltered[] = $paymentMethod;
                                    }
                                    break;
                                case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT:
                                    if ($paymentMethod['group']['code']=='part_payment') {
                                        $paymentMethodsFiltered[] = $paymentMethod;
                                    }
                                    break;
                                case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL:
                                    if ($paymentMethod['group']['code']=='invoice' && sizeof($paymentMethod['details'])>0) {
                                        $paymentMethodsFiltered[] = $paymentMethod;
                                    }
                                    break;
                            }
                        }
                    }
                }
            } else {
                $paymentMethodsFiltered = $paymentMethods;
            }

            // If no method specified, restructure the array, to place all methods under each Vaimo payment method
            // Currently, all calls to this function contains a defined method
            if ($method) {
                $paymentMethodsFilteredGroupd = $paymentMethodsFiltered;
            } else {
                $paymentMethodsFilteredGroupd = array();
                foreach ($paymentMethodsFiltered as $filtered) {
                    if (isset($filtered['vaimo_klarna_method'])) {
                        $paymentMethodsFilteredGroupd[$filtered['vaimo_klarna_method']][] = $filtered;
                    }
                }
            }
            if (sizeof($paymentMethodsFilteredGroupd)>0) {
                $res = $paymentMethodsFilteredGroupd;
            }
        } catch (Exception $e) {
            $res = NULL;
        }
        return $res;
    }

    public function setPaymentPlan()
    {
        $id = $this->getPostValues(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN);
        // @todo read from checkoutService to find new details for Sweden and Norway and store them
        $method = $this->getPostValues('method');
        if ($id) {
            $pclassArray = $this->_getSpecificPClass($id);
            if (!$pclassArray) {
                Mage::throwException($this->_getHelper()->__('Unexpected error, pclass does not exist, please reload page and try again'));
            }
            $this->addPostValues(array(
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_DESCRIPTION  => $pclassArray['description'],
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHLY_COST => $pclassArray['monthly_cost'],
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_TOTAL_COST   => $pclassArray['total_cost'],
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_INVOICE_FEE  => $pclassArray['invoicefee'],
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_START_FEE    => $pclassArray['startfee'],
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHS       => $pclassArray['months'],
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_TYPE         => $pclassArray['type'],
                        ));
        } else {
            if ($method==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT || $method==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL ) {
                Mage::throwException($this->_getHelper()->__('You must choose a payment plan'));
            }
            $this->addPostValues(array(
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_DESCRIPTION  => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHLY_COST => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_TOTAL_COST   => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_INVOICE_FEE  => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_START_FEE    => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_MONTHS       => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN_TYPE         => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN              => '',
                        ));
        }
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
        try {
            $this->_init(Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_ADDRESSES);
            $res = $this->getApi()->toKlarnaAddress($address);
        } catch (KlarnaException $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }

    public function doBasicTests()
    {
        if ($this->_checkPhone()==false) {
            Mage::throwException($this->_getHelper()->__('Phonenumber must be properly entered'));
        }
        if ($this->needDateOfBirth()) {
            if ($this->_checkDateOfBirth()==false) {
                Mage::throwException($this->_getHelper()->__('Date of birth fields must be properly entered'));
            }
        } else {
            if ($this->_checkPno()==false) {
                Mage::throwException($this->_getHelper()->__('Personal ID must not be empty'));
            }
        }
        if ($this->needConsent()) {
            if ($this->_checkConsent()==false) {
                Mage::throwException($this->_getHelper()->__('You need to agree to the terms to be able to continue'));
            }
        }
        if ($this->needGender()) {
            if ($this->_checkGender()==false) {
                Mage::throwException($this->_getHelper()->__('You need to enter your gender to be able to continue'));
            }
        }
    }

    public function createItemListRefund()
    {
        // The array that will hold the items that we are going to use
        $items = array();

        // Loop through the item collection
        foreach ($this->getCreditmemo()->getAllItems() as $item) {
            $ord_items = $this->getOrder()->getItemsCollection();
            foreach ($ord_items as $ord_item) {
                if ($ord_item->getId()==$item->getOrderItemId()) {
                    if ($this->_getHelper()->shouldItemBeIncluded($ord_item)) {
                        $items[] = $item;
                    }
                    break;
                }
            }
        }
        
        return $items;
    }
    
    public function createItemListCapture()
    {
        // The array that will hold the items that we are going to use
        $items = array();

        // Loop through the item collection
        foreach ($this->getInvoice()->getAllItems() as $item) {
            $ord_items = $this->getOrder()->getItemsCollection();
            foreach ($ord_items as $ord_item) {
                if ($ord_item->getId()==$item->getOrderItemId()) {
                    if ($this->_getHelper()->shouldItemBeIncluded($ord_item)) {
                        $items[] = $item;
                    }
                    break;
                }
            }
        }
        
        return $items;
    }
    
    public function createItemListAuthorize()
    {
        // The array that will hold the items that we are going to use
        $items = array();

        // Loop through the item collection
        foreach ($this->getOrder()->getAllItems() as $item) {
            if ($this->_getHelper()->shouldItemBeIncluded($item)==false) continue;
            $items[] = $item;
        }
        
        return $items;
    }

    protected function _getTransport()
    {
        return $this;
    }
    
}
