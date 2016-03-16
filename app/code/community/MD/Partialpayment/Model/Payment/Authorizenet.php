<?php
class MD_Partialpayment_Model_Payment_Authorizenet extends MD_Partialpayment_Model_Payment_Abstract
{
    protected $_urlMap = array(
        Mage_Paygate_Model_Authorizenet::METHOD_CODE=>array(
            'test'=>'https://test.authorize.net/gateway/transact.dll',
            'live'=>'https://secure.authorize.net/gateway/transact.dll'
        )
    );
    
    protected $_responseCodesMap = array(
        Mage_Paygate_Model_Authorizenet::METHOD_CODE=>array(
            1=>1,
            2=>4,
            3=>2,
            4=>3
        ),
    );
    
    protected $_typeMap = array(
        Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE => Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_AUTH_ONLY,
        Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE => Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_AUTH_CAPTURE
    );
    
    protected $_paymentModel = array(
        Mage_Paygate_Model_Authorizenet::METHOD_CODE => 'paygate/authorizenet',
    );
    
    public function pay($details){
        
        $messages = array();
        $installmentCount = 0;
        $helper = Mage::helper('md_partialpayment');
        $methodObject = Mage::getModel($this->_paymentModel[$details['method']]);
        $request= $this->_buildRequest($details, $methodObject);
        $result = $this->_postRequest($request, $methodObject);
        $result->setCcLast4('xxxx-'.substr($details['cc_number'],-1,4));
        $amount = $result->getAmount();
        
        $failed = true;
        switch ($result->getResponseCode()) {
            case $methodObject::RESPONSE_CODE_APPROVED:
                                $installmentCount = (!$this->getIsFullCapture()) ? 1 : (int)$this->getPayments()->getDueInstallments();
                                $failed = false;
                                $messages['success'] = $helper->__('Payment Processed Successfully.');
                                $amount = $result->getAmount();
                                //$histryString = sprintf('Credit Card: ',);
                                break;
            case $methodObject::RESPONSE_CODE_HELD:
                                $installmentCount = 0;
                                $amount = 0;
                                
                                $messages['notice'] = $helper->__($result->getResponseReasonText());
                                break;
            case $methodObject::RESPONSE_CODE_DECLINED:
            case $methodObject::RESPONSE_CODE_ERROR:
                                    $amount = 0;
                                 $installmentCount = 0;
                                 $messages['error'] = $helper->__($result->getResponseReasonText());                   
            default:
                    break;
        }
        if(array_key_exists('success', $messages)){
            Mage::getSingleton('core/session')->addSuccess($messages['success']);
        }elseif(array_key_exists('notice', $messages)){
            Mage::getSingleton('core/session')->addError($messages['notice']);
        }elseif(array_key_exists('error', $messages)){
            Mage::getSingleton('core/session')->addError($messages['error']);
        }
        if(!$this->getIsFullCapture()){
        $summary = $this->getSummary()
                        ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                        ->setStatus($this->_responseCodesMap[$details['method']][$result->getResponseCode()])
                        ->setTransactionId($result->getTransactionId())
                        ->setPaymentMethod($details['method'])
                        ->setPaymentFailCount($this->getSummary()->getPaymentFailCount() + $installmentCount)
                        ->setTransactionDetails(serialize($result->getData()));
        }else{
           if(!$failed){
               $dueSummaryCollection = $this->getPayments()->getDueInstallmentCollections();
               if($dueSummaryCollection){
                   foreach($dueSummaryCollection as $_summary){
                       $_summary
                               ->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS)
                               ->setPaymentMethod(Mage_Paygate_Model_Authorizenet::METHOD_CODE)
                               ->setTransactionId($result->getTransactionId())
                               ->setId($_summary->getId())
                               ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                               ->save();
                   }
               }
           } 
        }
        $payments = $this->getPayments()
                        ->setPaidAmount($this->getPayments()->getPaidAmount() + $amount)
                        ->setDueAmount(max(0,($this->getPayments()->getDueAmount() - $amount)))
                        ->setLastInstallmentDate(Mage::getSingleton('core/date')->gmtDate())
                        ->setPaidInstallments($this->getPayments()->getPaidInstallments() + $installmentCount)
                        ->setDueInstallments(max(0,($this->getPayments()->getDueInstallments() - $installmentCount)))
                        ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        if($this->getIsFullCapture() && !$failed){
            $payments->setFullPayment(1)->setFullPaymentData(serialize($result->getData()));
        }
        
        if($payments->getDueInstallments() > 0){
            $orderDueAmount = max(0,($this->getOrder()->getTotalDue() - $amount));
            $baseOrderDueAmount = max(0,($this->getOrder()->getBaseTotalDue() - $amount));
        }else{
            $orderDueAmount = 0;
            $baseOrderDueAmount = 0;
        }
        $order = $this->getOrder()
                    ->setTotalPaid($this->getOrder()->getTotalPaid() + $amount)
                    ->setBaseTotalPaid($this->getOrder()->getBaseTotalPaid() + $amount)
                    ->setTotalDue($orderDueAmount)
                    ->setBaseTotalDue($baseOrderDueAmount);
        
        if(strlen($this->getResponseText($result->getData())) > 0){
                $order->addStatusHistoryComment($this->getResponseText($result->getData()));
            }
        
        $transaction = Mage::getModel('core/resource_transaction');
        if(!$this->getIsFullCapture()){
            $transaction->addObject($summary);
        }
        $transaction->addObject($payments);
        $transaction->addObject($order);
        try{
            $transaction->save();
            if(!$this->getIsFullCapture()){
                $summary->sendStatusPaymentEmail(true,true);
            }else{
                if(!$failed){
                    $payments->sendFullInstallmentEmail($result->getAmount());
                }
            }
        }catch(Exception $e){
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        
        return $this;
    }
    
    public function _buildRequest($data, $methodObject){
        
        $order = $this->getOrder();
        $summary = $this->getSummary();
        
        
        /* we are checking that whether customer hase choosed full payment option */
        if($this->getIsFullCapture()){
            /* if full payment is selected then total due amount of partial payment will be captured. */
            $captureAmount = $this->getPayments()->getDueAmount();
        }else{
            /* if regular payment is selected then current summary amount of partial payment will be captured. */
            $captureAmount = $summary->getAmount();
        }
        
        $request = new Varien_Object();
        $request->setXVersion(3.1)
                ->setXDelimData('True')
                ->setXRelayResponse('False')
                ->setXTestRequest($methodObject->getConfigData('test') ? 'TRUE' : 'FALSE')
                ->setXLogin($methodObject->getConfigData('login'))
                ->setXTranKey($methodObject->getConfigData('trans_key'))
                ->setXType($this->_typeMap[$methodObject->getConfigData('payment_action')])
                ->setXMethod($methodObject::REQUEST_METHOD_CC)
                ->setXInvoiceNum($order->getIncrementId())
                ->setXAmount($captureAmount,2)
                ->setXCurrencyCode($order->getBaseCurrencyCode());
        
        if (!empty($order)) {
            $billing = $order->getBillingAddress();
            if (!empty($billing)) {
                $request->setXFirstName($billing->getFirstname())
                    ->setXLastName($billing->getLastname())
                    ->setXCompany($billing->getCompany())
                    ->setXAddress($billing->getStreet(1))
                    ->setXCity($billing->getCity())
                    ->setXState($billing->getRegion())
                    ->setXZip($billing->getPostcode())
                    ->setXCountry($billing->getCountry())
                    ->setXPhone($billing->getTelephone())
                    ->setXFax($billing->getFax())
                    ->setXCustId($order->getCustomerId())
                    ->setXCustomerIp($order->getRemoteIp())
                    ->setXCustomerTaxId($billing->getTaxId())
                    ->setXEmail($order->getCustomerEmail())
                    ->setXEmailCustomer($methodObject->getConfigData('email_customer'))
                    ->setXMerchantEmail($methodObject->getConfigData('merchant_email'));
            }
            $shipping = $order->getShippingAddress();
            if (!empty($shipping)) {
                $request->setXShipToFirstName($shipping->getFirstname())
                    ->setXShipToLastName($shipping->getLastname())
                    ->setXShipToCompany($shipping->getCompany())
                    ->setXShipToAddress($shipping->getStreet(1))
                    ->setXShipToCity($shipping->getCity())
                    ->setXShipToState($shipping->getRegion())
                    ->setXShipToZip($shipping->getPostcode())
                    ->setXShipToCountry($shipping->getCountry());
            }
        }
        if(isset($data['cc_number'])){
            $request->setXCardNum($data['cc_number'])
                ->setXExpDate(sprintf('%02d-%04d', $data['cc_exp_month'], $data['cc_exp_year']));
                //->setXCardCode($data['cc_type']);
        }
        
        return $request;
    }
    
    public function _postRequest(Varien_Object $request, $methodObject){
        
        
        $result = new Varien_Object();
        $client = new Varien_Http_Client();
        $uri = $methodObject->getConfigData('cgi_url');
        $client->setUri($uri ? $uri : $methodObject::CGI_URL);
        $client->setConfig(array(
            'maxredirects'=>0,
            'timeout'=>60,
            //'ssltransport' => 'tcp',
        ));
        foreach ($request->getData() as $key => $value) {
            $request->setData($key, str_replace($methodObject::RESPONSE_DELIM_CHAR, '', $value));
        }
        $request->setXDelimChar($methodObject::RESPONSE_DELIM_CHAR);
        $client->setParameterPost($request->getData());
        $client->setMethod(Zend_Http_Client::POST);
        try {
            $response = $client->request();
        }catch (Exception $e) {
            $result->setResponseCode(-1)
                ->setResponseReasonCode($e->getCode())
                ->setResponseReasonText($e->getMessage());

            Mage::throwException($e->getMessage());
        }
        $responseBody = $response->getBody();
        $r = explode($methodObject::RESPONSE_DELIM_CHAR, $responseBody);
        if ($r) {
            $result->setResponseCode((int)str_replace('"','',$r[0]))
                ->setResponseSubcode((int)str_replace('"','',$r[1]))
                ->setResponseReasonCode((int)str_replace('"','',$r[2]))
                ->setResponseReasonText($r[3])
                ->setApprovalCode($r[4])
                ->setAvsResultCode($r[5])
                ->setTransactionId($r[6])
                ->setInvoiceNumber($r[7])
                ->setDescription($r[8])
                ->setAmount($r[9])
                ->setMethod($r[10])
                ->setTransactionType($r[11])
                ->setCustomerId($r[12])
                ->setMd5Hash($r[37])
                ->setCardCodeResponseCode($r[38])
                ->setCAVVResponseCode( (isset($r[39])) ? $r[39] : null)
                ->setSplitTenderId($r[52])
                ->setAccNumber($r[50])
                ->setCardType($r[51])
                ->setRequestedAmount($r[53])
                ->setBalanceOnCard($r[54])
                ;
        }
        else {
             Mage::throwException(
                Mage::helper('paygate')->__('Error in payment gateway.')
            );
        }
        return $result;
    }
    
    public function getDetails()
    {
        $transactionDetails = unserialize($this->getSummary()->getTransactionDetails());
		$order = $this->getSummary()->getPayments()->getOrder();
        $details = array();
        $helper = Mage::helper('md_partialpayment');
        if(is_array($transactionDetails) && count($transactionDetails) > 0){
            if(array_key_exists('card_type',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Credit Card Type').':</b> '.$transactionDetails['card_type'];
            }
            
            if(array_key_exists('acc_number',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Credit Card Number').':</b> '.$transactionDetails['acc_number'];
            }
            
            if(array_key_exists('amount',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Processed Amount').':</b> '.strip_tags($order->formatPrice($transactionDetails['amount']));
            }
            
            $details[] = $helper->__('Order was placed using <b>%s</b>', $order->getBaseCurrencyCode());
            
        }
        return $details;
    }
    
    public function getResponseText($transactionDetails = null)
    {
        if(!$transactionDetails){
            $transactionDetails = unserialize($this->getSummary()->getTransactionDetails());
        }
        
        $helper = Mage::helper('paygate');
        $order = $this->getSummary()->getPayments()->getOrder();
        $amount = $helper->__('amount %s', $order->formatPrice($transactionDetails['amount']));
        $result = $helper->__($transactionDetails['response_reason_text']);
        $texts = $helper->__('Authorize.Net Transaction ID %s', $transactionDetails['transaction_id']);
        
        $message = sprintf('%s. %s. %s',$amount, $texts, $result);
        return $message;
    }
}

