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
class MD_Partialpayment_Adminhtml_SagepayController extends Mage_Core_Controller_Front_Action
{
    protected $_publicActions = array('callback3d','serverRedirect','directRedirect','redirect3D','redirectServer','serverNotification','successRedirect','failRedirect');
    protected $_processMethod = array(
        'sagepaydirectpro'=>'md_partialpayment/payment_sagepay_sagePayDirectPro',
        'sagepayserver'=>'md_partialpayment/payment_sagepay_sagePayServer'
    );
    public $eoln = "\r\n";
    public function callback3dAction()
    {
        $posts = $this->getRequest()->getPost();
        $params = $this->getRequest()->getParams();
        $sessionMethodCode = "sagepaydirectpro";
        $paymentsId = $params['payments_id'];
        $vendorTxCode = $params['v'];
        $transaction = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                        ->loadByVendorTxCode($vendorTxCode);

        $emede = $transaction->getMd();
        $pares = $params['PaRes'];

        $transaction->setPares($pares)
                    ->save();
        $returnUrl = Mage::getUrl('md_partialpayment/adminhtml_summary/view',array('id'=>$paymentsId));
        if(!is_null($sessionMethodCode) && strlen($sessionMethodCode) > 0){
            $result = Mage::getModel($this->_processMethod[$sessionMethodCode])
                            ->postBack3DResponse($posts, $params);
            
            $paymentsId = $result->getPaymentsId();
            $summaryId = $result->getSummaryId();
            
            if ($result->getResponseStatus() == Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_APPROVED || $result->getResponseStatus() == 'AUTHENTICATED') {
                $_transaction = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                    ->loadByVendorTxCode($vendorTxCode)
                    ->setVpsProtocol($result->getData('VPSProtocol'))
                    ->setSecurityKey($result->getData('SecurityKey'))
                    ->setStatus($result->getData('Status'))
                    ->setStatusDetail($result->getData('StatusDetail'))
                    ->setVpsTxId($result->getData('VPSTxId'))
                    ->setTxAuthNo($result->getData('TxAuthNo'))
                    ->setAvscv2($result->getData('AVSCV2'))
                    ->setPostcodeResult($result->getData('PostCodeResult'))
                    ->setAddressResult($result->getData('AddressResult'))
                    ->setCv2result($result->getData('CV2Result'))
                    ->setThreedSecureStatus($result->getData('3DSecureStatus'))
                    ->setCavv($result->getData('CAVV'))
                    ->setRedFraudResponse($result->getData('FraudResponse'))
                    ->setBankAuthCode($result->getData('BankAuthCode'))
                    ->setDeclineCode($result->getData('DeclineCode'))
                    ->save();
                if ($result->getData('Token')) {
                $tokenData = array(
                    'Token'        => $result->getData('Token'),
                    'Status'       => $result->getData('Status'),
                    'Vendor'       => $_transaction->getVendorname(),
                    'CardType'     => $_transaction->getCardType(),
                    'ExpiryDate'   => $result->getData('ExpiryDate'),
                    'StatusDetail' => $result->getData('StatusDetail'),
                    'Protocol'     => 'direct',
                    'CardNumber'   => $_transaction->getLastFourDigits(),
                    'Nickname'     => $_transaction->getNickname()
                );
                Mage::getModel('sagepaysuite/sagePayToken')->persistCard($tokenData);
            }
            }else{
                Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                    ->loadByVendorTxCode($vendorTxCode)
                    ->setStatus($result->getResponseStatus())
                    ->setStatusDetail($result->getResponseStatusDetail())
                    ->setVpsTxId($result->getVpsTxId())
                    ->setSecurityKey($result->getSecurityKey())
                    ->setPares(null)//Resetting data so we dont get "5036 : transaction not found" error for repeated calls to sagepay on 3d callback.
                    ->setMd(null)//Resetting data so we dont get "5036 : transaction not found" error for repeated calls to sagepay on 3d callback.
                    ->setPareq(null)
                    ->save();
            }
        }
        
                if($result->getErrorMessage())
                {
                    Mage::getSingleton("adminhtml/session")->addError($result->getErrorMessage());
                }
                if($result->getSuccessMessage())
                {
                    Mage::getSingleton("adminhtml/session")->addSuccess($result->getSuccessMessage());
                }   

                $this->getResponse()->setRedirect($returnUrl);
    }
    
    public function serverRedirectAction()
    {
        $params = $this->getRequest()->getParams();
        $p = ($params['p']) ? $params['p']: null;
        $limit = ($params['limit']) ? $params['limit']: null;
        $summaryId = (array_key_exists('payment_summary',$params)) ? $params['payment_summary'] : null;
        $summary = null;
        $paymentId = $params['payment_id'];
        $fullPaymentFlag = (array_key_exists('full_payment',$params) && $params['full_payment'] == 1) ? true: false;
        $allowPay = true;
        if($summaryId){
            $summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);
            if($summary){
                if($summary->getStatus() == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
                    $allowPay = false;
                }
            }
        }
        $method = $params['partial']['method'];
        $info = $params[$method];
        $info['method'] = $method;
        
        $payments = ($summary) ? $summary->getPayments() : Mage::getModel('md_partialpayment/payments')->load($paymentId);
        $order = $payments->getOrder();
        
        $returnUrl = Mage::getUrl('md_partialpayment/adminhtml_summary/view',array('id'=>$paymentId));
                
        if($allowPay){
            $response = Mage::getModel($this->_processMethod[$method])
                            ->setIsFullCapture($fullPaymentFlag)
                            ->setSummary($summary)
                            ->setPayments($payments)
                            ->setOrder($order)
                            ->pay($info);
            
            if ($response->getResponseStatus() == Ebizmarts_SagePaySuite_Model_Api_Payment :: RESPONSE_CODE_APPROVED) {
                $returnUrl = $response->getNextUrl();
            }else{
                Mage::getSingleton("adminhtml/session")->addError($response->getResponseStatusDetail());
            }
            $this->getResponse()->setRedirect($returnUrl);
        }else{
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper('md_partialpayment')->__('This installment has already paid.'));
            $this->getResponse()->setRedirect($returnUrl);
        }
    }
    
    public function directRedirectAction()
    {
        $params = $this->getRequest()->getParams();
        $p = ($params['p']) ? $params['p']: null;
        $limit = ($params['limit']) ? $params['limit']: null;
        $summaryId = (array_key_exists('payment_summary',$params)) ? $params['payment_summary'] : null;
        $summary = null;
        $paymentId = $params['payment_id'];
        $fullPaymentFlag = (array_key_exists('full_payment',$params) && $params['full_payment'] == 1) ? true: false;
        $allowPay = true;
        if($summaryId){
            $summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);
            if($summary){
                if($summary->getStatus() == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS){
                    $allowPay = false;
                }
            }
        }
        $method = $params['partial']['method'];
        $info = $params[$method];
        $info['method'] = $method;
        $info['design_area'] = "adminhtml";
        $payments = ($summary) ? $summary->getPayments() : Mage::getModel('md_partialpayment/payments')->load($paymentId);
        $order = $payments->getOrder();
        
        $returnUrl = Mage::getUrl('md_partialpayment/adminhtml_summary/view',array('id'=>$paymentId));
                if($p && $limit){
                    $returnUrl .= '?p='.$p.'&limit='.$limit;
                }elseif($p){
                    $returnUrl .= '?p='.$p;
                }elseif($limit){
                    $returnUrl .= '?limit='.$limit;
                }
        if($allowPay){
            //echo $this->_processMethod[$method];exit;
            
                $response = Mage::getModel($this->_processMethod[$method])
                            ->setIsFullCapture($fullPaymentFlag)
                            ->setSummary($summary)
                            ->setPayments($payments)
                            ->setOrder($order)
                            ->pay($info);
            
            if($response->getResponseStatus() == Ebizmarts_SagePaySuite_Model_Api_Payment::RESPONSE_CODE_3DAUTH && $response->get3DsecureStatus() == "OK"){
                
                $this->_redirect("md_partialpayment/adminhtml_sagepay/redirect3D",array("vandor_tx_code"=>Mage::getSingleton('adminhtml/session')->getVandorTxCode(true),"summary_id"=>$summaryId,"payments_id"=>$paymentId,"full_payment"=>($fullPaymentFlag) ? 1:null,"area"=>"adminhtml"));
            }else{
            
                if($response->getErrorMessage())
                {
                    Mage::getSingleton("adminhtml/session")->addError($response->getErrorMessage());
                }
                if($response->getSuccessMessage())
                {
                    Mage::getSingleton("adminhtml/session")->addSuccess($response->getSuccessMessage());
                }   

                $this->getResponse()->setRedirect($returnUrl);
            } 
        }else{
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper('md_partialpayment')->__('This installment has already paid.'));
            $this->getResponse()->setRedirect($returnUrl);
        }
    }
    
    public function redirect3DAction(){
        return $this->getResponse()->setBody($this->getLayout()->createBlock('md_partialpayment/sagepay_redirect_secure3D')->toHtml());
    }
    
    public function redirectServerAction()
    {
        $params = $this->getRequest()->getParams();
        $this->getResponse()->setBody($this->getLayout()
                ->createBlock('md_partialpayment/sagepay_redirect_server')
                ->setSummaryId($params['summary_id'])
                ->setPaymentsId($params['payments_id'])
                ->setFullPayment($params['full_pay'])
                ->toHtml());
    }
    
    public function serverNotificationAction()
    {
        $request = $this->getRequest();
        $posts = $request->getPost();
        $params = $request->getParams();
        $summary = (array_key_exists("summary_id",$params)) ? Mage::getModel("md_partialpayment/summary")->load($params["summary_id"]): null;
        $payments = ($summary) ? $summary->getPayments(): Mage::getModel()->load($params['payments_id']);
        $fullFlag = (array_key_exists("full",$params) && $params['full'] == 1) ? true: false;
        $order = $payments->getOrder();
        
        $transaction = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                            ->loadByVendorTxCode($this->getRequest()->getPost('VendorTxCode'));
        
        
                $transaction->addData(Mage::helper('sagepaysuite')->arrayKeysToUnderscore($_POST))
                            ->setPostcodeResult($this->getRequest()->getPost('PostCodeResult'))
                ->setData('cv2result', $this->getRequest()->getPost('CV2Result'))
                ->setThreedSecureStatus($this->getRequest()->getPost('3DSecureStatus'))
                ->setLastFourDigits($this->getRequest()->getPost('Last4Digits'))
                ->setRedFraudResponse($this->getRequest()->getPost('FraudResponse'))
                ->setBankAuthCode($this->getRequest()->getPost('BankAuthCode'))
                ->setDeclineCode($this->getRequest()->getPost('DeclineCode'))
                ->save();
        
        if ($request->getPost('Token')) {
            $tokenData = array(
                'Token' => $request->getPost('Token'),
                'Status' => $request->getPost('Status'),
                'Vendor' => $transaction->getVendorname(),
                'CardType' => $request->getPost('CardType'),
                'ExpiryDate' => $request->getPost('ExpiryDate'),
                'StatusDetail' => $request->getPost('StatusDetail'),
                'Nickname'      => $transaction->getNickname(),
                'Protocol' => 'server',
                'CardNumber' => '00000000' . $request->getPost('Last4Digits')
            );
            Mage::getModel('sagepaysuite/sagePayToken')->persistCard($tokenData);
        }
        $sageStatus = $request->getParam('Status');
        if ($sageStatus == 'ABORT') {
            
            $transaction->setStatus($sageStatus)
                    ->setStatusDetail($request->getParam('StatusDetail'))
                    ->save();
            $this->_returnOkAbort($params['payments_id']);
            return;
        }
        if ($transaction->getId() && $transaction->getOrderId()) {
            $strVendorName = Mage::getStoreConfig("payment/sagepaysuite/vendor");
			
            $strStatus       = $request->getParam('Status', '');
            $strVendorTxCode = $request->getParam('VendorTxCode', '');
            $strVPSTxId      = $request->getParam('VPSTxId', '');
            $strSecurityKey = '';
            
            if ($transaction->getVendorTxCode() == $strVendorTxCode && $transaction->getVpsTxId() == $strVPSTxId) {
                $strSecurityKey = $transaction->getSecurityKey();
            }
            $response = '';
            if (strlen($strSecurityKey) == 0) {
                $transaction->setStatus('MAGE_ERROR')
                    ->setStatusDetail("Security Key invalid. " . $transaction->getStatusDetail())
                    ->save();
                $this->_returnInvalid('Security Key invalid',$params['payments_id']);
            }else{
                $strStatusDetail = $strTxAuthNo = $strAVSCV2 = $strAddressResult = $strPostCodeResult = $strCV2Result = $strGiftAid = $str3DSecureStatus = $strCAVV = $strAddressStatus = $strPayerStatus = $strCardType = $strPayerStatus = $strLast4Digits = $strMySignature = '';
                $strVPSSignature = $request->getParam('VPSSignature', '');
                $strStatusDetail = $request->getParam('StatusDetail', '');
                if (strlen($request->getParam('TxAuthNo', '')) > 0) {
                    $strTxAuthNo = $request->getParam('TxAuthNo', '');
                }
                $strAVSCV2 = $request->getParam('AVSCV2', '');
                $strAddressResult = $request->getParam('AddressResult', '');
                $strPostCodeResult = $request->getParam('PostCodeResult', '');
                $strCV2Result = $request->getParam('CV2Result', '');
                $strGiftAid = $request->getParam('GiftAid', '');
                $str3DSecureStatus = $request->getParam('3DSecureStatus', '');
                $strCAVV = $request->getParam('CAVV', '');
                $strAddressStatus = $request->getParam('AddressStatus', '');
                $strPayerStatus = $request->getParam('PayerStatus', '');
                $strCardType = $request->getParam('CardType', '');
                $strLast4Digits = $request->getParam('Last4Digits', '');
                $strDeclineCode = $request->getParam('DeclineCode', '');
                $strExpiryDate = $request->getParam('ExpiryDate', '');
                $strFraudResponse = $request->getParam('FraudResponse', '');
                $strBankAuthCode = $request->getParam('BankAuthCode', '');
                
                $strMessage = $strVPSTxId . $strVendorTxCode . $strStatus . $strTxAuthNo . $strVendorName . $strAVSCV2 . $strSecurityKey
                          . $strAddressResult . $strPostCodeResult . $strCV2Result . $strGiftAid . $str3DSecureStatus . $strCAVV
                          . $strAddressStatus . $strPayerStatus . $strCardType . $strLast4Digits . $strDeclineCode
                          . $strExpiryDate . $strFraudResponse . $strBankAuthCode;

                $strMySignature = strtoupper(md5($strMessage));
                $response = '';
                $validSignature = ($strMySignature !== $strVPSSignature);
                if ($validSignature) {
                    $transaction->setStatus('MAGE_ERROR')
                        ->setStatusDetail("Cannot match the MD5 Hash. " . $transaction->getStatusDetail())
                        ->save();
                    $this->_returnInvalid('Cannot match the MD5 Hash. Order might be tampered with. ' . $strStatusDetail,$params['payments_id']);
                }else{
                    $strDBStatus = $this->_getHRStatus($strStatus, $strStatusDetail);
					
                    $message = $request->getParam("StatusDetail");
					
                    $fullSummary = $payments->getFullPaymentSummary();
					
                        $amount = ($fullFlag) ? $fullSummary['installment_amount']: $summary->getAmount();
						
                    if ($strStatus == 'OK' || $strStatus == 'AUTHENTICATED' || $strStatus == 'REGISTERED') {
                        if(!$fullFlag){
                            $summary->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                                ->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS)
                                ->setTransactionId($request->getParam('VPSTxId', ''))
                                ->setPaymentMethod('sagepayserver')
                                ->setPaymentFailCount($summary->getPaymentFailCount() + 0)
                                ->setTransactionDetails(serialize($params));
                        }else{
                            $dueSummaryCollection = $payments->getDueInstallmentCollections();
                            if($dueSummaryCollection){
                                foreach($dueSummaryCollection as $_summary){
                                    $_summary
                                            ->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS)
                                            ->setPaymentMethod('sagepayserver')
                                            ->setTransactionId($request->getParam('VPSTxId', ''))
                                            ->setId($_summary->getId())
                                            ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                                            ->save();
                                }
                            }
                        }
                        $payments->setPaidAmount($payments->getPaidAmount() + $amount)
                            ->setDueAmount(max(0,($payments->getDueAmount() - $amount)))
                            ->setLastInstallmentDate(Mage::getSingleton('core/date')->gmtDate())
                            ->setPaidInstallments($payments->getPaidInstallments() + 1)
                            ->setDueInstallments(max(0,($payments->getDueInstallments() - 1)))
                            ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
                        if($fullFlag){
                            $payments->setFullPayment(1)->setFullPaymentData(serialize(serialize($params)));
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
        
                            $mTransaction = Mage::getModel('core/resource_transaction');
                            if(!$fullFlag){
                                $mTransaction->addObject($summary);
                            }
                            $mTransaction->addObject($payments);
                            $mTransaction->addObject($order);
                            try{
                                $mTransaction->save();
                                if(!$fullFlag){
                                    $summary->sendStatusPaymentEmail(true,true);
                                }else{
                                    $payments->sendFullInstallmentEmail($amount);
                                }
                                Mage::getSingleton("adminhtml/session")->addSuccess($message);
                            }catch(Exception $e){
                                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                            }
                        $this->_returnOk($params['payments_id']);
                    }else {
                        if(!$fullFlag){
                            $summary->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
                                ->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_FAIL)
                                ->setTransactionId($request->getParam('VPSTxId', ''))
                                ->setPaymentMethod('sagepayserver')
                                ->setPaymentFailCount($summary->getPaymentFailCount() + 1)
                                ->setTransactionDetails(serialize($params));
                        }
                        $payments->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
                        $mTransaction = Mage::getModel('core/resource_transaction');
                            if(!$fullFlag){
                                $mTransaction->addObject($summary);
                            }
                            $mTransaction->addObject($payments);
                            try{
                                $mTransaction->save();
                                if(!$fullFlag){
                                    $summary->sendStatusPaymentEmail(true,true);
                                }
                                Mage::getSingleton("adminhtml/session")->addError($message);
                            }catch(Exception $e){
                                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                            }
                        $transaction->setStatus('MAGE_ERROR')
                            ->setStatusDetail($strDBStatus . $transaction->getStatusDetail())
                            ->save();
                        $this->_returnInvalid($strDBStatus,$params['payments_id']);
                    }
                    $this->_returnInvalid($strDBStatus,$params['payments_id']);
                }
            }
			$this->_returnInvalid($strDBStatus,$params['payments_id']);
        }
		$this->_returnInvalid($strDBStatus,$params['payments_id']);
    }
    protected function _getHRStatus($strStatus, $strStatusDetail) {
        if ($strStatus == 'OK')
            $strDBStatus = 'AUTHORISED - The transaction was successfully authorised with the bank.';
        elseif ($strStatus == 'NOTAUTHED'){
            if($strStatusDetail == "1003 : The transaction timed out."){
                $strDBStatus = 'ERROR - The transaction timed out.';
            }else{
                $strDBStatus = 'DECLINED - The transaction was not authorised by the bank.';
            }
        }
        elseif ($strStatus == 'ABORT')
            $strDBStatus = 'ABORTED - The customer clicked Cancel on the payment pages, or the transaction was timed out due to customer inactivity.';
        elseif ($strStatus == 'REJECTED')
            $strDBStatus = 'REJECTED - The transaction was failed by your 3D-Secure or AVS/CV2 rule-bases.';
        elseif ($strStatus == 'AUTHENTICATED')
            $strDBStatus = 'AUTHENTICATED - The transaction was successfully 3D-Secure Authenticated and can now be Authorised.';
        elseif ($strStatus == 'REGISTERED')
            $strDBStatus = 'REGISTERED - The transaction was could not be 3D-Secure Authenticated, but has been registered to be Authorised.';
        elseif ($strStatus == 'ERROR')
            $strDBStatus = 'ERROR - There was an error during the payment process.  The error details are: ' . $strStatusDetail;
        elseif ($strStatus == 'PENDING')
            $strDBStatus = 'PENDING - Transaction pending';
        else
            $strDBStatus = 'UNKNOWN - An unknown status was returned from Sage Pay.  The Status was: ' . $strStatus . ', with StatusDetail:' . $strStatusDetail;

        return $strDBStatus;
    }
    
    private function _returnOkAbort($paymentsId=null) {
        $strResponse = 'Status=OK' . $this->eoln;
        $strResponse .= 'StatusDetail=Transaction ABORTED successfully' . $this->eoln;
        $strResponse .= 'RedirectURL=' . Mage::getUrl("md_partialpayment/adminhtml_sagepay/successRedirect",array("payment_id"=>$paymentsId)) . $this->eoln;
        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);
        return;
    }

    private function _returnOk($params = array(),$paymentsId=null) {
        $strResponse = 'Status=OK' . $this->eoln;
        $strResponse .= 'StatusDetail=Transaction completed successfully' . $this->eoln;
        $strResponse .= 'RedirectURL=' . Mage::getUrl("md_partialpayment/adminhtml_sagepay/successRedirect",array("payment_id"=>$paymentsId)) . $this->eoln;
        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);
        return;
    }

    private function _returnInvalid($message = 'Unable to find the transaction in our database.',$paymentsId=null) {
        $response = 'Status=INVALID' . $this->eoln;
        $response .= 'RedirectURL=' . Mage::getUrl("md_partialpayment/adminhtml_sagepay/failRedirect",array("payment_id"=>$paymentsId)) . $this->eoln;
        $response .= 'StatusDetail=' . $message . $this->eoln;
        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($response);
        return;
    }
    
    public function successRedirectAction()
    {
        Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("md_partialpayment")->__('Payment paid successfully.'));
        $this->_redirect("md_partialpayment/adminhtml_summary/view",array("id"=>$this->getRequest()->getParam("payment_id",null)));
        return $this;
    }
    
    public function failRedirectAction()
    {
        Mage::getSingleton("adminhtml/session")->addError(Mage::helper("md_partialpayment")->__('Payment failed.'));
        $this->_redirect("md_partialpayment/adminhtml_summary/view",array("id"=>$this->getRequest()->getParam("payment_id",null)));
        return $this;
    }
}