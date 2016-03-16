<?php
/**
* Magedelight
* Copyright (C) 2014 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Partialpayment
* @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
class MD_Partialpayment_Model_Payment_Sagepay_SagePayDirectPro extends MD_Partialpayment_Model_Payment_Abstract
{
    const SAGEPAY_DIRECT_REGISTRATION_LIVE = "https://live.sagepay.com/gateway/service/vspdirect-register.vsp";
    const SAGEPAY_DIRECT_REGISTRATION_TEST = "https://test.sagepay.com/gateway/service/vspdirect-register.vsp";
    const SAGEPAY_3DSECURE_CALLBACK_LIVE = "https://live.sagepay.com/gateway/service/direct3dcallback.vsp";
    const SAGEPAY_3DSECURE_CALLBACK_TEST = "https://test.sagepay.com/gateway/service/direct3dcallback.vsp";
    
    public function pay($details){
        $methodObject = null;
        $request= $this->_buildRequest($details, $methodObject);
        $result = $this->_postRequest($request, $methodObject);
        
        $isFull = (boolean)$this->getIsFullCapture();
        
        $summary = $this->getSummary();
        $payments = ($summary) ? $summary->getPayments() : $this->getPayments();
        $order = $payments->getOrder();
        
        $amount = 0;
        $paidInstallments = 0;
        $status = MD_Partialpayment_Model_Summary::PAYMENT_FAIL;
        if ($result->getResponseStatus() == Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_APPROVED || $result->getResponseStatus() == 'AUTHENTICATED') {
            if($isFull){
                $amount = $payments->getDueAmount();
                $paidInstallments = $payments->getDueInstallments();
            }else{
                $amount = $summary->getAmount();
                $paidInstallments = 1;
            }
            $status = MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS;
        }
        if(!$isFull){
        $summary->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                        ->setStatus($status)
                        ->setTransactionId($result->getData("VPSTxId"))
                        ->setPaymentMethod('sagepaydirectpro')
                        ->setPaymentFailCount($summary->getPaymentFailCount() + $paidInstallments)
                        ->setTransactionDetails(serialize($result->getData()));
        }else{
            if($status == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
                $dueSummaryCollection = $payments->getDueInstallmentCollections();
                if($dueSummaryCollection){
                   foreach($dueSummaryCollection as $_summary){
                       $_summary
                               ->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS)
                               ->setPaymentMethod('sagepaydirectpro')
                               ->setTransactionId($result->getData("VPSTxId"))
                               ->setId($_summary->getId())
                               ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                               ->save();
                   }
               }
            }
        }
        $payments->setPaidAmount($payments->getPaidAmount() + $amount)
                        ->setDueAmount(max(0,($payments->getDueAmount() - $amount)))
                        ->setLastInstallmentDate(Mage::getSingleton('core/date')->gmtDate())
                        ->setPaidInstallments($payments->getPaidInstallments() + $paidInstallments)
                        ->setDueInstallments(max(0,($payments->getDueInstallments() - $paidInstallments)))
                        ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        if($isFull && $status == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
            $payments->setFullPayment(1)->setFullPaymentData(serialize($result->getData()));
        }
        
        if($payments->getDueInstallments() > 0){
            $orderDueAmount = max(0,($order->getTotalDue() - $amount));
            $baseOrderDueAmount = max(0,($order->getBaseTotalDue() - $amount));
        }else{
            $orderDueAmount = 0;
            $baseOrderDueAmount = 0;
        }
        $order->setTotalPaid($order->getTotalPaid() + $amount)
                    ->setBaseTotalPaid($order->getBaseTotalPaid() + $amount)
                    ->setTotalDue($orderDueAmount)
                    ->setBaseTotalDue($baseOrderDueAmount);
        $transaction = Mage::getModel('core/resource_transaction');
        if(!$this->getIsFullCapture()){
            $transaction->addObject($summary);
        }
        $transaction->addObject($payments);
        $transaction->addObject($order);
        try{
            $transaction->save();
            if(!$isFull){
                $summary->sendStatusPaymentEmail(true,true);
            }else{
                if($status == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
                    $payments->sendFullInstallmentEmail($amount);
                }
            }
        }catch(Exception $e){
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        
        return $result;
    }
    
    protected function _getTokencardById($id = null)
    {
        if(!is_null($id) && $id > 0){
            $model = Mage::getModel('sagepaysuite2/sagepaysuite_tokencard')->load($id);
            if($model){
                return $model;
            }
        }
        return null;
    }
    
    
    
    protected function _buildRequest($data, $methodObject = null)
    {
        $tokenObject = (isset($data['sagepay_token_cc_id']) && (int)$data['sagepay_token_cc_id'] > 0) ? $this->_getTokencardById($data['sagepay_token_cc_id']): null;
        
        $token = ($tokenObject) ? $tokenObject->getToken(): null;
        $tokenType = ($tokenObject) ? $tokenObject->getCardType(): null;
        $tokenCvv = (isset($data['token_cvv']) && strlen($data['token_cvv'])) ? $data['token_cvv']: null;
        
        $ccOwner = (isset($data['cc_owner']) && strlen($data['cc_owner'])) ? $data['cc_owner']: null;
        $ccType = (isset($data['cc_type']) && strlen($data['cc_type'])) ? $data['cc_type']: null;
        $ccNumber = (isset($data['cc_number']) && strlen($data['cc_number'])) ? $data['cc_number']: null;
        $ccExpMonth = (isset($data['cc_exp_month']) && strlen($data['cc_exp_month'])) ? $data['cc_exp_month']: null;
        $ccExpYear = (isset($data['cc_exp_year']) && strlen($data['cc_exp_year'])) ? $data['cc_exp_year']: null;
        $ccId = (isset($data['cc_cid']) && strlen($data['cc_cid'])) ? $data['cc_cid']: null;
        
        $payments = $this->getPayments();
        $summary = $this->getSummary();
        $order = $this->getOrder();
        
        $isTestMode = (Mage::getStoreConfig("payment/sagepaydirectpro/mode",$order->getStoreId()) == 'test') ? true: false;
        $requestUri = ($isTestMode) ? self::SAGEPAY_DIRECT_REGISTRATION_TEST: self::SAGEPAY_DIRECT_REGISTRATION_LIVE;
        
        $vendor = Mage::getStoreConfig("payment/sagepaysuite/vendor",$order->getStoreId());
        
        $avs2 = Mage::getStoreConfig("payment/sagepaydirectpro/avscv2",$order->getStoreId());
        $secure3d = Mage::getStoreConfig("payment/sagepaydirectpro/secure3d",$order->getStoreId());
        
        /* we are checking that whether customer hase choosed full payment option */
        if($this->getIsFullCapture()){
            /* if full payment is selected then total due amount of partial payment will be captured. */
            $captureAmount = $payments->getDueAmount();
        }else{
            /* if regular payment is selected then current summary amount of partial payment will be captured. */
            $captureAmount = $summary->getAmount();
        }
        $request = new Varien_Object();
        $request->setData(array(
           'VPSProtocol'=>'3.00',
            'TxType'=>'PAYMENT',
            'Vendor'=>$vendor,
            'VendorTxCode'=>sprintf("%s-%s",$order->getIncrementId(),date("Y-m-d-H-i-s")),
            'Amount'=>round($captureAmount,2),
            'Currency'=>$order->getBaseCurrencyCode(),
            'Description'=>'123', 
            'ApplyAVSCV2'=>$avs2,
            'ClientIPAddress'=>Mage::helper('core/http')->getRemoteAddr(),
            'Apply3DSecure'=>$secure3d,
            'AccountType'=>'E',
        ));
        if (!empty($order)) {
            $billing = $order->getBillingAddress();
            if (!empty($billing)) {
                $request->addData(array(
                    'BillingSurname'=>$billing->getLastname(),
                    'BillingFirstnames'=>$billing->getFirstname(),
                    'BillingAddress1'=>$billing->getStreet(1),
                    'BillingCity'=>$billing->getCity(),
                    'BillingPostCode'=>$billing->getPostcode(),
                    'BillingCountry'=>$billing->getCountry(),
                    'BillingAddress2'=>$billing->getStreet(2),
                    'BillingState'=>$billing->getRegionCode()
                ));
            }
            
            $shipping = $order->getShippingAddress();
            if (!empty($shipping)) {
                $request->addData(array(
                    'DeliverySurname'=>$shipping->getLastname(),
                    'DeliveryFirstnames'=>$shipping->getFirstname(),
                    'DeliveryAddress1'=>$shipping->getStreet(1),
                    'DeliveryCity'=>$shipping->getCity(),
                    'DeliveryPostCode'=>$shipping->getPostcode(),
                    'DeliveryCountry'=>$shipping->getCountry(),
                    'DeliveryAddress2'=>$shipping->getStreet(2),
                    'DeliveryState'=>$shipping->getRegionCode()
                ));
            }
        }
        if(!is_null($token) && !is_null($tokenCvv)){
            $request->addData(array(
                'Token'=>$token,
                'CV2'=>$tokenCvv,
                'CardType'=>$tokenType
            ));
        }
        
        if(!is_null($ccType) && !is_null($ccNumber)){
            $request->addData(array('CardHolder'=>$ccOwner));
            $request->addData(array('CardType'=>$ccType));
            $request->addData(array('CardNumber'=>$ccNumber));
            $request->addData(array('ExpiryDate'=>sprintf("%02d%02d",$ccExpMonth,substr($ccExpYear,-2))));
            if(!is_null($ccId)){$request->addData(array('CV2'=>$ccId));}
            if(array_key_exists('remembertoken',$data) && $data['remembertoken'] == 1)
            {
                $request->addData(array("CreateToken"=>1));
                $request->addData(array("StoreToken"=>1));
            }
        }
        $request->addData(array("AccountType"=>"E"));
        $request->addData(array("request_url"=>$requestUri));
        $request->addData(array("Language"=>strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(),0,2))));
        $request->addData(array("Website"=>Mage::app()->getStore()->getWebsite()->getName()));
        if(array_key_exists('design_area',$data)){
            $request->addData(array("design_area"=>$data['design_area']));
        }
        return $request;
    }
    
    protected function _postRequest(Varien_Object $request, $methodObject)
    {
        $output = array();
        $error = null;
        $success = null;
        $response = new Varien_Object();
        $client = new Varien_Http_Client();
        $uri = $request->getData('request_url');
        $request->unsetData('request_url');
        $client->setUri($uri);
        $client->setConfig(array(
            'maxredirects'=>0,
            'timeout'=>60,
            //'ssltransport' => 'tcp',
        ));
        $sessionSpace = ($request->getDesignArea() == "adminhtml") ? "adminhtml": "sagepaysuite";
        foreach ($request->getData() as $key => $value) {
            $request->setData($key, str_replace(',', '', $value));
        }
        $client->setParameterPost($request->getData());
        $client->setMethod(Zend_Http_Client::POST);
        try {
            $sagepayResponse = $client->request();
            
        }catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        $responseBody = explode("\n",$sagepayResponse->getBody());
        foreach($responseBody as $fields){
            if(strlen($fields) > 0){
                $exploded = explode("=",$fields,2);
                $key = $exploded[0];
                $value = str_replace(array("\n","\r"),"",$exploded[1]);
                $output[$key] = $value;
            }
        }
            $callback3D = false;
            switch($output["Status"]){
                case 'FAIL':
                    $response->setResponseStatus($output['Status'])
                            ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($output['StatusDetail']))
                            ->setVPSTxID(1)
                            ->setSecurityKey(1)
                            ->setTxAuthNo(1)
                            ->setAVSCV2(1)
                            ->setAddressResult(1)
                            ->setPostCodeResult(1)
                            ->setCV2Result(1)
                            ->setTrnSecuritykey(1);
                    $error = Mage::helper('sagepaysuite')->__($output['StatusDetail']);
                    break;
                case 'FAIL_NOMAIL':
                    $error = Mage::helper("sagepaysuite")->__($output['StatusDetail']);
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_INVALID:
                    $error = Mage::helper("sagepaysuite")->__('INVALID. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_MALFORMED:
                    $error = Mage::helper('sagepaysuite')->__('MALFORMED. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_ERROR:
                    $error = Mage::helper('sagepaysuite')->__('ERROR. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_REJECTED:
                    $error = Mage::helper('sagepaysuite')->__('REJECTED. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_3DAUTH:
                    $callback3D = true;
                    $response->setResponseStatus($output['Status'])
                            ->setResponseStatusDetail((isset($output['StatusDetail']) ? $output['StatusDetail'] : '')) //Fix for simulator
                            ->set3DSecureStatus($output['3DSecureStatus'])    // to store
                            ->setMD($output['MD']) // to store
                            ->setACSURL($output['ACSURL'])
                            ->setPAReq($output['PAReq']);
                    
                    if($output['3DSecureStatus'] == "OK"){
                        Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                        ->loadByVendorTxCode($request->getData("VendorTxCode"))
                        ->setVendorTxCode($request->getData("VendorTxCode"))
                        ->setMd($output['MD'])
                        ->setPareq($output['PAReq'])
                        ->setAcsurl($output['ACSURL'])
                        ->setIntegration("direct")
                        ->setVendorTxCode($request->getData("VendorTxCode"))
                        ->save();
                    }else{
                        $error = Mage::helper('sagepaysuite')->__($output['3DSecureStatus']);
                    }
                    
                    Mage::getSingleton($sessionSpace.'/session')->setVandorTxCode($request->getData("VendorTxCode"));
                    Mage::getSingleton($sessionSpace.'/session')->setSummaryId($this->getSummary()->getId());
                    Mage::getSingleton($sessionSpace.'/session')->setPaymentsId($this->getPayments()->getId());
                    Mage::getSingleton($sessionSpace.'/session')->setMethodCode("sagepaydirectpro");
                    if($this->getIsFullCapture()){
                        Mage::getSingleton($sessionSpace.'/session')->setFullPayment(1);
                    }else{
                        Mage::getSingleton($sessionSpace.'/session')->setFullPayment(0);
                    }
                    break;
                default:
                    $response->setResponseStatus($output['Status'])
                            ->setResponseStatusDetail($output['StatusDetail'])  // to store
                            ->setVpsTxId($output['VPSTxId'])    // to store
                            ->setSecurityKey($output['SecurityKey']) // to store
                            ->setTrnSecuritykey($output['SecurityKey']);
                    if (isset($output['3DSecureStatus']))
                        $response->set3DSecureStatus($output['3DSecureStatus']);
                    if (isset($output['CAVV']))
                        $response->setCAVV($output['CAVV']);

                    if (isset($output['TxAuthNo']))
                        $response->setTxAuthNo($output['TxAuthNo']);
                    if (isset($output['AVSCV2']))
                        $response->setAvscv2($output['AVSCV2']);
                    if (isset($output['PostCodeResult']))
                        $response->setPostCodeResult($output['PostCodeResult']);
                    if (isset($output['CV2Result']))
                        $response->setCv2result($output['CV2Result']);
                    if (isset($output['AddressResult']))
                        $response->setAddressResult($output['AddressResult']);
                    
                    $response->addData($output);
                    
                    if (!$callback3D && $response->getData('Token')) {
                        $tokenData = array(
                            'Token'        => $response->getData('Token'),
                            'Status'       => $response->getData('Status'),
                            'Vendor'       => $request->getData('Vendor'),
                            'CardType'     => $request->getData('CardType'),
                            'ExpiryDate'   => $request->getData('ExpiryDate'),
                            'StatusDetail' => $response->getData('StatusDetail'),
                            'Protocol'     => 'direct',
                            'CardNumber'   => substr($request->getData('CardNumber'),-4),
                            'Nickname'     => $request->getData('Nickname')
                        );

                        Mage::getModel('sagepaysuite/sagePayToken')->persistCard($tokenData);
                    }
                    $success = Mage::helper('sagepaysuite')->__($output['StatusDetail']);
                    break;
            }
        if(!is_null($error) && strlen($error) > 0){
            $response->setErrorMessage($error);
        }
        if(!is_null($success) && strlen($success) > 0){
            $response->setSuccessMessage($success);
        }
        /*$is3dRequired = (in_array($output->getData("Status"),array('OK','NOTAUTHED','REJECTED','AUTHENTICATED','REGISTERED','PPREDIRECT','MALFORMED','INVALID','ERROR'))) ? 0: 1;
        $output->setIs3Dsecure($is3dRequired);    
        if($is3dRequired){
               Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                        ->loadByVendorTxCode($request->getData("VendorTxCode"))
                        ->setVendorTxCode($request->getData("VendorTxCode"))
                        ->setMd($output->getData("MD"))
                        ->setPareq($output->getData("PAReq"))
                        ->setAcsurl($output->getData("ACSURL"))
                        ->setIntegration("direct")
                        ->save();
               Mage::getSingleton('sagepaysuite/session')->setVandorTxCode($request->getData("VendorTxCode"));
               Mage::getSingleton('sagepaysuite/session')->setSummaryId($this->getSummary()->getId());
               Mage::getSingleton('sagepaysuite/session')->setPaymentsId($this->getPayments()->getId());
            }*/    
        return $response;
    }
    
    public function directCall3D($pares,$md)
    {
        
    }
    
    protected function _buildRequest3D($pares,$md)
    {
        return new Varien_Object(array("MD"=>$md,"PARes"=>$pares));
        
    }
    
    protected function _postRequest3D($request)
    {
        $isTestMode = (Mage::getStoreConfig("payment/sagepaydirectpro/mode") == 'test') ? true: false;
        $requestUri = ($isTestMode) ? self::SAGEPAY_3DSECURE_CALLBACK_TEST: self::SAGEPAY_3DSECURE_CALLBACK_LIVE;
        $output = array();
        $error = null;
        $success = null;
        $response = new Varien_Object();
        $client = new Varien_Http_Client();
        
        $client->setUri($requestUri);
        $client->setConfig(array(
            'maxredirects'=>0,
            'timeout'=>60,
            //'ssltransport' => 'tcp',
        ));
        foreach ($request->getData() as $key => $value) {
            $request->setData($key, str_replace(',', '', $value));
        }
        $client->setParameterPost($request->getData());
        $client->setMethod(Zend_Http_Client::POST);
        try {
            $sagepayResponse = $client->request();
            
        }catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }
        $responseBody = explode("\n",$sagepayResponse->getBody());
        foreach($responseBody as $fields){
            if(strlen($fields) > 0){
                $exploded = explode("=",$fields,2);
                $key = $exploded[0];
                $value = str_replace(array("\n","\r"),"",$exploded[1]);
                $output[$key] = $value;
            }
        }
        $failed = true;
        
        switch($output["Status"]){
                case 'FAIL':
                    $response->setResponseStatus($output['Status'])
                            ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($output['StatusDetail']))
                            ->setVPSTxID(1)
                            ->setSecurityKey(1)
                            ->setTxAuthNo(1)
                            ->setAVSCV2(1)
                            ->setAddressResult(1)
                            ->setPostCodeResult(1)
                            ->setCV2Result(1)
                            ->setTrnSecuritykey(1);
                    $error = Mage::helper('sagepaysuite')->__($output['StatusDetail']);
                    break;
                case 'FAIL_NOMAIL':
                    $error = Mage::helper("sagepaysuite")->__($output['StatusDetail']);
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_INVALID:
                    $error = Mage::helper("sagepaysuite")->__('INVALID. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_MALFORMED:
                    $error = Mage::helper('sagepaysuite')->__('MALFORMED. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_ERROR:
                    $error = Mage::helper('sagepaysuite')->__('ERROR. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_REJECTED:
                    $error = Mage::helper('sagepaysuite')->__('REJECTED. %s', Mage::helper('sagepaysuite')->__($output['StatusDetail']));
                    break;
                case Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_3DAUTH:
                    $callback3D = true;
                    $response->setResponseStatus($output['Status'])
                            ->setResponseStatusDetail((isset($output['StatusDetail']) ? $output['StatusDetail'] : '')) //Fix for simulator
                            ->set3DSecureStatus($output['3DSecureStatus'])    // to store
                            ->setMD($output['MD']) // to store
                            ->setACSURL($output['ACSURL'])
                            ->setPAReq($output['PAReq']);
                    
                    if($output['3DSecureStatus'] == "OK"){
                        Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                        ->loadByVendorTxCode($request->getData("VendorTxCode"))
                        ->setVendorTxCode($request->getData("VendorTxCode"))
                        ->setMd($output['MD'])
                        ->setPareq($output['PAReq'])
                        ->setAcsurl($output['ACSURL'])
                        ->setIntegration("direct")
                        ->save();
                    }else{
                        $error = Mage::helper('sagepaysuite')->__($output['3DSecureStatus']);
                    }
                    
                    Mage::getSingleton('sagepaysuite/session')->setVandorTxCode($request->getData("VendorTxCode"));
                    Mage::getSingleton('sagepaysuite/session')->setSummaryId($this->getSummary()->getId());
                    Mage::getSingleton('sagepaysuite/session')->setPaymentsId($this->getPayments()->getId());
                    Mage::getSingleton('sagepaysuite/session')->setMethodCode("sagepaydirectpro");
                    break;
                default:
                    $failed = false;
                    $response->setResponseStatus($output['Status'])
                            ->setResponseStatusDetail($output['StatusDetail'])  // to store
                            ->setVpsTxId($output['VPSTxId'])    // to store
                            ->setSecurityKey($output['SecurityKey']) // to store
                            ->setTrnSecuritykey($output['SecurityKey']);
                    if (isset($output['3DSecureStatus']))
                        $response->set3DSecureStatus($output['3DSecureStatus']);
                    if (isset($output['CAVV']))
                        $response->setCAVV($output['CAVV']);

                    if (isset($output['TxAuthNo']))
                        $response->setTxAuthNo($output['TxAuthNo']);
                    if (isset($output['AVSCV2']))
                        $response->setAvscv2($output['AVSCV2']);
                    if (isset($output['PostCodeResult']))
                        $response->setPostCodeResult($output['PostCodeResult']);
                    if (isset($output['CV2Result']))
                        $response->setCv2result($output['CV2Result']);
                    if (isset($output['AddressResult']))
                        $response->setAddressResult($output['AddressResult']);
                    
                    $response->addData($output);
                    
                    if (!$callback3D && $response->getData('Token')) {
                        $tokenData = array(
                            'Token'        => $response->getData('Token'),
                            'Status'       => $response->getData('Status'),
                            'Vendor'       => $response->getData('Vendor'),
                            'CardType'     => $response->getData('CardType'),
                            'ExpiryDate'   => $response->getData('ExpiryDate'),
                            'StatusDetail' => $response->getData('StatusDetail'),
                            'Protocol'     => 'direct',
                            'CardNumber'   => $response->getData('CardNumber'),
                            'Nickname'     => $response->getData('Nickname')
                        );

                        Mage::getModel('sagepaysuite/sagePayToken')->persistCard($tokenData);
                    }
                    $success = Mage::helper('sagepaysuite')->__($output['StatusDetail']);
                    break;
            }
        if(!is_null($error) && strlen($error) > 0){
            $response->setErrorMessage($error);
        }
        if(!is_null($success) && strlen($success) > 0){
            $response->setSuccessMessage($success);
        }
        return $response;
    }
    
    public function postBack3DResponse($posts, $params)
    {
        $request = $this->_buildRequest3D($posts['PaRes'],$posts['MD']);
        $response = $this->_postRequest3D($request);
        
        $isFull = (boolean)$params['full'];
        $summaryId = $params['summary_id'];
        $paymentsId = $params['payments_id'];
        $summary = (!is_null($summaryId) && $summaryId > 0) ? Mage::getModel('md_partialpayment/summary')->load($summaryId): null;
        $payments = ($summary) ? $summary->getPayments() : Mage::getModel('md_partialpayment/payments')->load($paymentsId);
        $order = $payments->getOrder();
        
        $amount = 0;
        $paidInstallments = 0;
        $status = MD_Partialpayment_Model_Summary::PAYMENT_FAIL;
        if ($response->getResponseStatus() == Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_APPROVED || $response->getResponseStatus() == 'AUTHENTICATED') {
            if($isFull){
                $amount = $payments->getDueAmount();
                $paidInstallments = $payments->getDueInstallments();
            }else{
                $amount = $summary->getAmount();
                $paidInstallments = 1;
            }
            $status = MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS;
        }
        if(!$isFull){
        $summary->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                        ->setStatus($status)
                        ->setTransactionId($response->getData("VPSTxId"))
                        ->setPaymentMethod('sagepaydirectpro')
                        ->setPaymentFailCount($summary->getPaymentFailCount() + $paidInstallments)
                        ->setTransactionDetails(serialize($response->getData()));
        }else{
            if($status == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
                $dueSummaryCollection = $payments->getDueInstallmentCollections();
                if($dueSummaryCollection){
                   foreach($dueSummaryCollection as $_summary){
                       $_summary
                               ->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS)
                               ->setPaymentMethod('sagepaydirectpro')
                               ->setTransactionId($response->getData("VPSTxId"))
                               ->setId($_summary->getId())
                               ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                               ->save();
                   }
               }
            }
        }
        $payments->setPaidAmount($payments->getPaidAmount() + $amount)
                        ->setDueAmount(max(0,($payments->getDueAmount() - $amount)))
                        ->setLastInstallmentDate(Mage::getSingleton('core/date')->gmtDate())
                        ->setPaidInstallments($payments->getPaidInstallments() + $paidInstallments)
                        ->setDueInstallments(max(0,($payments->getDueInstallments() - $paidInstallments)))
                        ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        if($isFull && $status == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
            $payments->setFullPayment(1)->setFullPaymentData(serialize($response->getData()));
        }
        
        if($payments->getDueInstallments() > 0){
            $orderDueAmount = max(0,($order->getTotalDue() - $amount));
            $baseOrderDueAmount = max(0,($order->getBaseTotalDue() - $amount));
        }else{
            $orderDueAmount = 0;
            $baseOrderDueAmount = 0;
        }
        $order->setTotalPaid($order->getTotalPaid() + $amount)
                    ->setBaseTotalPaid($order->getBaseTotalPaid() + $amount)
                    ->setTotalDue($orderDueAmount)
                    ->setBaseTotalDue($baseOrderDueAmount);
        $transaction = Mage::getModel('core/resource_transaction');
        if(!$this->getIsFullCapture()){
            $transaction->addObject($summary);
        }
        $transaction->addObject($payments);
        $transaction->addObject($order);
        try{
            $transaction->save();
            if(!$isFull){
                $summary->sendStatusPaymentEmail(true,true);
            }else{
                if($status == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
                    $payments->sendFullInstallmentEmail($amount);
                }
            }
        }catch(Exception $e){
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        return $response;
    }
    
}

