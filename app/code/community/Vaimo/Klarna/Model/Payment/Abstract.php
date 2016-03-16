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

class Vaimo_Klarna_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canVoid                 = true;
    protected $_canCancel               = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canManageRecurringProfiles = false;

    protected $_moduleHelper = NULL;

    public function __construct($moduleHelper = NULL)
    {
        parent::__construct();

        $this->_moduleHelper = $moduleHelper;
        if ($this->_moduleHelper==NULL) {
            $this->_moduleHelper = Mage::helper('klarna');
        }
    }

    /**
     * @return false|Vaimo_Klarna_Model_Klarna
     */
    protected function _getKlarnaModel()
    {
        return Mage::getModel('klarna/klarna');
    }

    /**
     * @return Vaimo_Klarna_Helper_Data|null
     */
    protected function _getHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * @param      $field
     * @param null $storeId
     *
     * @return mixed
     */
    protected function _getConfigData($field, $storeId = NULL)
    {
        if (!$storeId) $storeId = Mage::app()->getStore()->getId();
        return $this->getConfigData($field, $storeId);
    }
    
    protected function _getCustomerFromSession()
    {
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        return $customerSession->getCustomer();
    }

    public function canCapture()
    {
        $klarna = $this->_getKlarnaModel();
        $klarna->setQuote($this->getQuote(), $this->_code);
        if ($klarna->getConfigData('disable_backend_calls')) return false;
        return true;
    }

    public function canRefund()
    {
        return $this->canCapture();
    }

    public function canCapturePartial()
    {
        return $this->canCapture();
    }

    public function canRefundInvoicePartial()
    {
        return $this->canCapture();
    }

    public function canRefundPartialPerInvoice()
    {
        return $this->canCapture();
    }
    
    // Expects Magentos current store to be accurate...
    protected function _roundPrice($price)
    {
        return Mage::app()->getStore()->roundPrice($price);
    }
    
    /*
     *
     * This returns blank because Klarna doesn't want the title in text
     * So we use the getMethodLabelAfterHtml function in Form to return
     * the image and title after the image
     * Perhaps there is a better way, but this worked.
     *
     */
    public function getTitle()
    {
        if ($this->_getHelper()->showTitleAsTextOnly()) {
            $klarna = $this->_getKlarnaModel();
            $klarna->setQuote($this->getQuote(), $this->_code);
            $presetTitle = NULL;
            $serviceMethods = $klarna->getCheckoutService($this->_code);
            if ($serviceMethods) {
                foreach ($serviceMethods as $serviceMethod) {
                    if (isset($serviceMethod['group'])) {
                        if (isset($serviceMethod['group']['title']) && isset($serviceMethod['title'])) {
                            $presetTitle = $serviceMethod['group']['title'];
                            if ($serviceMethod['vaimo_klarna_method']==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE ||
                                $serviceMethod['vaimo_klarna_method']==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL) {
                                $presetTitle .= ' ' . $serviceMethod['title'];
                            }
                            break;
                        }
                    }
                }
            }
            return $klarna->getMethodTitleWithFee($this->_getHelper()->getVaimoKlarnaFeeInclVat($this->getQuote(), false), $presetTitle);
        } else {
            return '';
        }
    }

    public function getQuote()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        return $checkoutSession->getQuote();
    }

    protected function _isAvailableParent( $quote = NULL)
    {
         return parent::isAvailable($quote);
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable( $quote = null )
    {
        $available = $this->_isAvailableParent($quote);
        if (!$available) return false;

        try {
            $active = $this->_getConfigData('active'); // Only call to this

            if (!$active) return false;
            if (is_null($quote)) return false;

            $grandTotal = $quote->getGrandTotal();
            if (empty ($grandTotal) || $grandTotal <= 0) return false;

            $klarna = $this->_getKlarnaModel();
            $klarna->setQuote($quote, $this->_code);
            if ($klarna->isBelowAllowedHardcodedLimit($grandTotal) == false ) {
                return false;
            }

            $address = $quote->getShippingAddress();
            if ($address->getCountry() == null) {
                $address = $this->getDefaultAddress();
                if ($address == null) {
                    return false;
                }
            }
            if ($klarna->isCountryAllowed()==false) {
                return false;
            }
            $billingAddress = $quote->getBillingAddress();
            if ($billingAddress->getCompany() != null) {
                if ($klarna->showMethodForCompanyPurchases()==false) {
                    return false;
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
            return false;
        }
        return true;
    }


    public function assignData( $data )
    {
        try {
            if (!($data instanceof Varien_Object)) {
                $data = new Varien_Object($data);
            }
            $info = $this->getInfoInstance();
            /** @var Mage_Sales_Model_Quote $quote */
            $quote = $info->getQuote();
            $klarna = $this->_getKlarnaModel();
            $klarna->setQuote($quote, $data->getMethod());
            $klarna->clearInactiveKlarnaMethodsPostvalues($data, $data->getMethod());
            $klarna->addPostValues($data->getData(), $data->getMethod());
            $email = $klarna->getEmailValue($this->_getCustomerFromSession()->getEmail());
            $klarna->addPostValues(array('email' => $email)); // will replace email from checkout...
            $klarna->updateAssignAddress();
            $klarna->setPaymentPlan();
            $klarna->setPaymentFee($quote);
            if ($klarna->getPostValues('consent')===NULL) {
                $klarna->addPostValues(array('consent' => 'NO')); // If this is not set in post, set it to NO to mark as no consent was given.
            }
            if ($klarna->getPostValues('gender')===NULL) {
                $klarna->addPostValues(array('gender' => '-1')); // If this is not set in post, set it to -1.
            }

            $klarnaAddr = $klarna->toKlarnaAddress($klarna->getBillingAddress()); // Shipping

            // These ifs were in a sense copied from old klarna module
            // Don't send in reference for non-company purchase.
            if (!$klarnaAddr->isCompany) {
                if ($klarna->getPostValues('reference')!==NULL) {
                    $klarna->unsPostvalue('reference');
                }
            } else {
                // This insane ifcase is for OneStepCheckout
                if ($klarna->getPostValues('reference')===NULL) {
                    $reference = $klarnaAddr->getFirstName() . " " . $klarnaAddr->getLastName();
                    $klarna->addPostValues(array('reference' => $reference));
                }
            }

            $klarna->updateAdditionalInformation( $info );

        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $this;
    }

    protected function _validateParent()
    {
        return parent::validate();
    }

    public function validate()
    {
        $this->_validateParent();
        try {
            $klarna = $this->_getKlarnaModel();
            $info = $this->getInfoInstance();
            if ($info->getQuote()) {
                // Validate is called while in checkout and immediately after place order is pushed
                $quote = $info->getQuote();
                $klarna->setInfoInstance($this->getInfoInstance());
                $klarna->setQuote($quote, $info->getMethod());
            } else {
                // Magento also calls validate when the quote has been changed into an order, then the quote doesn't exist and we do our tests against the order
                $order = $info->getOrder();
                $klarna->setInfoInstance($this->getInfoInstance());
                $klarna->setOrder($order);
            }

            // We cannot perform basic tests with OneStepCheckout because they try
            // to save the payment method as soon as the customer views the checkout
            if ($this->_getHelper()->isOneStepCheckout()) {
                return $this;
            }

            $klarna->doBasicTests();

        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $this;
    }

    /**
     * Authorize the purchase
     *
     * @param Varien_Object $payment Magento payment model
     * @param double $amount  The amount to authorize with
     *
     * @return Vaimo_Klarna_Model_Klarna_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        try {
            /*
             * Since we could not perform basic tests with OneStepCheckout at validate and assign functions
             * we do them here instead
             */
            $klarna = $this->_getKlarnaModel();
            if ($this->_getHelper()->isOneStepCheckout()) {
                $klarna->setInfoInstance($this->getInfoInstance());
                $klarna->setPayment($payment);
                $klarna->doBasicTests();
            }

            $klarna = $this->_getKlarnaModel(); // Is a clean model really needed here?
            $klarna->setPayment($payment);
            $klarna->updateAuthorizeAddress();

            if ($this->_getHelper()->isOneStepCheckout() && $klarna->shippingSameAsBilling()) {
                $klarna->updateBillingAddress();
            }
        
            $itemList = $klarna->createItemListAuthorize();
            $payment->setKlarnaItemList($itemList);

            $result = $klarna->reserve($amount);
            $transactionStatus = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS];
            $transactionId = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID];

            $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_RESERVATION_ID, $transactionId);
            $payment->setAdditionalInformation(
                Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_RESERVATION_STATUS, $transactionStatus
            );
            $payment->setAdditionalInformation(
                Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_HOST, $klarna->getConfigData('host')
            );
            $payment->setAdditionalInformation(
                Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_MERCHANT_ID, $klarna->getConfigData('merchant_id')
            );

            if ($transactionStatus==Vaimo_Klarna_Helper_Data::KLARNA_STATUS_PENDING) {
                $payment->setIsTransactionPending(true);
            }

            $payment->setTransactionId($transactionId)
                ->setIsTransactionClosed(0);
        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        try {
            $klarna = $this->_getKlarnaModel();
            $klarna->setPayment($payment);
            $result = $klarna->capture($amount);
            $transactionStatus = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS];
            $transactionId = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID];
            $feeAmountCaptured = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_FEE_CAPTURED];
            if (isset($result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_KCO_CAPTURE_ID])) {
                $kcoCaptureId = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_KCO_CAPTURE_ID];
            } else {
                $kcoCaptureId = NULL;
            }

            $invoice = array(
                Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_INVOICE_LIST_STATUS => $transactionStatus,
                Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_INVOICE_LIST_ID => $transactionId
            );
            if ($kcoCaptureId) {
                $invoice[Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_INVOICE_LIST_KCO_ID] = $kcoCaptureId;
                $transactionId = $transactionId . '/' . $kcoCaptureId;
            }
            $invoices = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_INVOICE_LIST);
            if (!is_array($invoices)) {
                $invoices = array();
            }
            $invoices[] = $invoice;
            $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_INVOICE_LIST, $invoices);

            if ($feeAmountCaptured) {
                $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_CAPTURED_TRANSACTION_ID, $transactionId);
                $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_REFUNDED, 0);
            }

            $payment->setTransactionId($transactionId)
                ->setIsTransactionClosed(0);
        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $this;
    }

    public function refund(Varien_Object $payment, $amount)
    {
        try {
            $klarna = $this->_getKlarnaModel();
            $klarna->setPayment($payment);
            $itemList = $klarna->createItemListRefund();
            $payment->setKlarnaItemList($itemList);
            $klarna->setInfoInstance($this->getInfoInstance());
            $result = $klarna->refund($amount);
            $transactionStatus = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS]; // Always OK...
            $transactionId = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_TRANSACTION_ID];
            $klarnaFeeRefunded = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_FEE_REFUNDED];
            if ($klarnaFeeRefunded) {
                if ($payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_REFUNDED)) {
                    $klarnaFeeRefunded = $klarnaFeeRefunded + $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_REFUNDED);
                }
                $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_REFUNDED, $klarnaFeeRefunded);
            }
            $id = date('His');
            if (!$id) $id = 1;

            $payment->setTransactionId($transactionId . '-' . $id . '-refund')
                ->setIsTransactionClosed(1);
        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $this;
    }
    
    public function cancel(Varien_Object $payment)
    {
        try {
            $klarna = $this->_getKlarnaModel();
            $klarna->setPayment($payment);
            $result = $klarna->cancel();
            $transactionStatus = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS];

            if (!$transactionStatus) {
                Mage::throwException($this->_getHelper()->__('Klarna was not able to cancel the reservation'));
            }
            $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_CANCELED_DATE, date("Y-m-d") );

            $payment->setIsTransactionClosed(1);
        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $this;
    }

    public function void(Varien_Object $payment)
    {
        return $this->cancel($payment);
    }

    protected function _fetchTransactionInfoParent(Mage_Payment_Model_Info $payment, $transactionId)
    {
        return parent::fetchTransactionInfo($payment, $transactionId);
    }
    /**
     * Fetch transaction details info
     *
     * To ask for update on a pending order, see if we get denied or accepted
     *
     * @param Mage_Payment_Model_Info $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId)
    {
        $klarna = $this->_getKlarnaModel();
        $klarna->setPayment($payment);
        $status = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_RESERVATION_STATUS);
        $result = $klarna->checkStatus();
        $transactionStatus = $result[Vaimo_Klarna_Helper_Data::KLARNA_API_RESPONSE_STATUS];
        if ($transactionStatus!=$status) {
            $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_RESERVATION_STATUS, $transactionStatus );
        }
        if ($transactionStatus==Vaimo_Klarna_Helper_Data::KLARNA_STATUS_ACCEPTED) {
            $payment->setIsTransactionApproved(true);
        } elseif ($transactionStatus==Vaimo_Klarna_Helper_Data::KLARNA_STATUS_DENIED) {
            $payment->setIsTransactionDenied(true);
        }
        return $this->_fetchTransactionInfoParent($payment, $transactionId);
    }
}
