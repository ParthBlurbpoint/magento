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
class MD_Partialpayment_Model_Payment_Sagepay_SagePayServer extends MD_Partialpayment_Model_Payment_Abstract
{
    const SAGEPAY_SERVER_LIVE = 'https://live.sagepay.com/gateway/service/vspserver-register.vsp';
    const SAGEPAY_SERVER_TEST = 'https://test.sagepay.com/gateway/service/vspserver-register.vsp';
    
    public function pay($details){
        
        $request = $this->_buildRequest($details);
        $result = $this->_postRequest($request);
        
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
        $modelString = '';
        if(Mage::app()->getStore()->isAdmin()){
            $modelString = "adminhtml_";
        }
        
        $tokenObject = (isset($data['sagepay_token_cc_id']) && (int)$data['sagepay_token_cc_id'] > 0) ? $this->_getTokencardById($data['sagepay_token_cc_id']): null;
        $token = ($tokenObject) ? $tokenObject->getToken(): null;
        $tokenType = ($tokenObject) ? $tokenObject->getCardType(): null;
        $tokenCvv = (isset($data['token_cvv']) && strlen($data['token_cvv'])) ? $data['token_cvv']: null;
        
        
        $summary = $this->getSummary();
        $summaryId = ($summary) ? $summary->getId(): null;
        $payments = $this->getPayments();
        $order = $this->getOrder();
        
        $vendor = Mage::getStoreConfig("payment/sagepaysuite/vendor");
        $vendorTxCode = sprintf("%s-%s",$order->getIncrementId(),date("Y-m-d-H-i-s"));
        $avs2 = Mage::getStoreConfig("payment/sagepayserver/avscv2",$order->getStoreId());
        $isTestMode = (Mage::getStoreConfig("payment/sagepayserver/mode",$order->getStoreId()) == 'test') ? true: false;
        $requestUri = ($isTestMode) ? self::SAGEPAY_SERVER_TEST: self::SAGEPAY_SERVER_LIVE;
        
        /* we are checking that whether customer hase choosed full payment option */
        $fullFlag = ($this->getIsFullCapture()) ? 1: null;
        if($this->getIsFullCapture()){
            /* if full payment is selected then total due amount of partial payment will be captured. */
            $captureAmount = $payments->getDueAmount();
        }else{
            /* if regular payment is selected then current summary amount of partial payment will be captured. */
            $captureAmount = $summary->getAmount();
        }
        
        $request = new Varien_Object();
        $request->addData(array(
            'VPSProtocol'=>'3.00',
            'TxType'=>'PAYMENT',
            'Vendor'=>$vendor,
            'VendorTxCode'=>$vendorTxCode,
            'Amount'=>round($captureAmount,2),
            'Currency'=>$order->getBaseCurrencyCode(),
            'Description'=>Mage::getStoreConfig("payment/sagepayserver/purchase_description",$order->getStoreId()), 
            'ApplyAVSCV2'=>$avs2,
            'ClientIPAddress'=>Mage::helper('core/http')->getRemoteAddr(),
            'Apply3DSecure'=>'2',
            'AccountType'=>'E',
            'StoreToken'=>0,
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
        
        if(array_key_exists('remembertoken',$data) && $data['remembertoken'] == 1)
            {
                $request->addData(array("CreateToken"=>1));
                $request->addData(array("StoreToken"=>1));
            }
            $request->addData(array("AccountType"=>"E"));
        $request->addData(array("request_url"=>$requestUri));
        $request->addData(array("Language"=>strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(),0,2))));
        $request->addData(array("Website"=>Mage::app()->getStore()->getWebsite()->getName()));
        $request->addData(array(
            'NotificationURL'=>Mage::getUrl("md_partialpayment/".$modelString."sagepay/serverNotification",array('_secure' => true,'_current' => true,"summary_id"=>$summaryId,"payments_id"=>$payments->getId(),"full"=>$fullFlag)),
            'SuccessURL'=>Mage::getUrl("md_partialpayment/".$modelString."sagepay/success",array('_secure' => true,'_current' => true,"summary_id"=>$summaryId,"payments_id"=>$payments->getId(),"full"=>$fullFlag)),
            'FailureURL'=>Mage::getUrl("md_partialpayment/".$modelString."sagepay/failure",array('_secure' => true,'_current' => true,"summary_id"=>$summaryId,"payments_id"=>$payments->getId(),"full"=>$fullFlag)),
            'RedirectURL'=> Mage::getUrl("md_partialpayment/".$modelString."summary/view",array('_secure' => true,'_current' => true,"payment_id"=>$payments->getId()))
        ));
        
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
        if (empty($output) || $output['Status'] == 'FAIL') {
            $msg = Mage::helper('sagepaysuite')->__('Sage Pay is not available at this time. Please try again later.');
            $response->setResponseStatus('ERROR')->setResponseStatusDetail($msg);
        }elseif($output['Status'] == "OK"){
            $response
                    ->setResponseStatus($output['Status'])
                    ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($output['StatusDetail']))
                    ->setNextUrl($output['NextURL'])
                    ->setVPSTxID($output['VPSTxId']);
                $response->addData($output);
                $response->setTxType($request->getTxType());
                Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                        ->loadByVendorTxCode($request->getData("VendorTxCode"))
                        ->setData("vps_tx_id",$output['VPSTxId'])
                        ->setIntegration("server")
                        ->setSecurityKey($output['SecurityKey'])
                        ->setStatus($output['Status'])
                        ->setVendorTxCode($request->getData("VendorTxCode"))
                        ->setOrderId(($this->getOrder()) ? $this->getOrder()->getId(): null)
                        ->save();
        }else{
            $response->setResponseStatus($response['Status'])
                    ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($response['StatusDetail']));
        }
        return $response;
    }
    
    
}

