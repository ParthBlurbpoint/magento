<?php
/**
 * Magedelight
 * Copyright (C) 2015 Magedelight <info@magedelight.com>
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
 * @copyright Copyright (c) 2015 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */
?>
<?php

class MD_Partialpayment_Model_Payment_Cybersource extends MD_Partialpayment_Model_Payment_Abstract {
    
    public function pay($details) {
	$order	        = $this->getOrder();
	$summary	= $this->getSummary();
	$paymentDetails = $this->getPayments();
	$params		= Mage::app()->getRequest()->getParams();
	$payment	= Mage::getModel('sales/order_payment')->setMethod('md_cybersource');
	$requestType	= Mage::getStoreConfig('payment/' . $payment->getMethod() . '/payment_action');
	$customerId	= $paymentDetails->getCustomerId();
	$amount		= $summary->getAmount();
	
	$payment->setOrder($order);
	
	if ($amount <= 0) {
	    Mage::throwException(Mage::helper('md_cybersource')->__('Invalid amount for authorization.'));
	}
	
	if($params['payment']['subscription_id'] != 'new') {
	    if(isset($params['payment']['subscription_id'])) {
		$subscriptionId = $params['payment']['subscription_id'];
	    } else if(!empty($details['subscription_id'])){
		$subscriptionId = $details['subscription_id'];
	    } else {
		$subscriptionId = null;
	    }
	} else {
	    $subscriptionId = null;
	}
	
	if($requestType == 'authorize') {
	    $this->authorize($payment,$amount,$customerId,$subscriptionId);
	} else { //authorize_capture
	    $this->capture($payment,$amount,$customerId,$subscriptionId);
	}
	
    }
    
    protected function _isPreauthorizeCapture($payment) {
	$cybersourcePaymentObj = Mage::getModel('md_cybersource/payment');
	
	if ($cybersourcePaymentObj->getCardsStorage($payment)->getCardsCount() <= 0) {
	    return false;
	}
	
	foreach ($cybersourcePaymentObj->getCardsStorage($payment)->getCards() as $card) {
	    $lastTransactionId = $payment->getData('cc_trans_id');
	    $cardTransactionId = $card->getTransactionId();
	    
	    if ($lastTransactionId == $cardTransactionId) {
		$lastTransaction = $payment->getTransaction($card->getLastTransId());
		
		if (!$lastTransaction || $lastTransaction->getTxnType() != Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
		    return false;
		}
		
		return true;
	    }
	}
    }
    
     protected function _registerCard($response, Mage_Sales_Model_Order_Payment $payment) {
	$cybersourcePaymentObj = Mage::getModel('md_cybersource/payment');
	$cardsStorage	       = $cybersourcePaymentObj->getCardsStorage($payment);
	$card		       = $cardsStorage->registerCard();
	
	if ($payment->getMdcybersourceSubscriptionId() != '') {
	    $customerCard = $cybersourcePaymentObj->getSubscriptionCardInfo($payment->getMdcybersourceSubscriptionId());
	    
	    $card->setCcType($customerCard[0]['cc_type'])
		 ->setCcLast4($customerCard[0]['cc_last4'])
		 ->setCcExpMonth($customerCard[0]['cc_exp_month'])
		 ->setCcOwner($customerCard[0]['firstname'])
		 ->setCcExpYear($customerCard[0]['cc_exp_year']);
	} else {
	    $post = Mage::app()->getRequest()->getParam('payment');

	    if (!isset($post['firstname'])) {
		$post['firstname'] = '';
	    }
	    
	    $card->setCcType($post['cc_type'])
		 ->setCcLast4(substr($post['cc_number'], -4, 4))
		 ->setCcExpMonth($post['cc_exp_month'])
		 ->setCcOwner($post['firstname'])
		 ->setCcExpYear($post['cc_exp_year']);
	}

	if(empty($response->ccAuthReply)) {
	    $response->ccAuthReply = new StdClass;
	}

	if (!isset($response->ccAuthReply->cvCode)) {
	    $response->ccAuthReply->cvCode = '';
	}
	
	if (!isset($response->ccAuthReply->amount)) {
	    $response->ccAuthReply->amount = '';
	}

	$card->setRequestedAmount($response->ccAuthReply->amount)
	     ->setLastTransId($response->requestID)
	     ->setProcessedAmount($response->ccAuthReply->amount)
	     ->setMerchantReferenceCode($response->merchantReferenceCode)
	     ->setreconciliationID($response->ccAuthReply->reconciliationID)
	     ->setauthorizationCode($response->ccAuthReply->authorizationCode)
	     ->setAvsResultCode($response->ccAuthReply->avsCode)
	     ->setCVNResultCode($response->ccAuthReply->cvCode)
	     ->setTransactionId($response->requestID);
	
	$cardsStorage->updateCard($card);
	
	return $card;
    }
    
    public function authorize(Varien_Object $payment, $amount,$customerId,$subScriptionIdCheck) {
	$exceptionMessage      = false;
	$post		       = Mage::app()->getRequest()->getParam('payment');
	$cybersourcePaymentObj = Mage::getModel('md_cybersource/payment');
	$saveCard	       = isset($post['save_card']) ? $post['save_card'] : 0;
	$message	       = '-';
	$order		       = $this->getOrder();
	$baseCurrencyCode      = $order->getBaseCurrencyCode();
	$orderPaid	       = $order->getTotalPaid();
	$orderDue	       = $order->getTotalDue();
	$fullPaymentFlag       = $this->getIsFullCapture();
	$summary	       = $this->getSummary();
	$status		       = MD_Partialpayment_Model_Summary::PAYMENT_PROCESS;
	
	if($fullPaymentFlag == 1) {
	    $amount = $orderDue;
	}
	    
	$cybersourcePaymentObj->getCardsStorage($payment);
	
	try {
	    if (!empty($subScriptionIdCheck) && empty($post['cc_number'])) {             
		$subScriptionIdCheck = Mage::helper('core')->decrypt($subScriptionIdCheck); 
		$subscriptionFlag    = true;
		
		$payment->setMdcybersourceSubscriptionId($subScriptionIdCheck);
	    } else {
		$subscriptionFlag    = false;
	    }
	    
	    $response = Mage::getModel("md_cybersource/api_soap")
			->prepareAuthorizeResponse($payment, $amount, $subscriptionFlag);

	    if ($response->reasonCode == $cybersourcePaymentObj::RESPONSE_CODE_SUCCESS) {
		if (!empty($subScriptionIdCheck) && empty($post['cc_number'])) {
		    $card = $cybersourcePaymentObj->getSubscriptionCardInfo($subScriptionIdCheck);
		    
		    $payment->setCcLast4($card[0]['cc_last4']);
		    $payment->setCcType($card[0]['cc_type']);
		    $payment->setAdditionalInformation('md_cybersource_subscription_id', $subScriptionIdCheck);
		    $payment->setMdcybersourceSubscriptionid($subScriptionIdCheck);
		} else {
		    $payment->setCcLast4(substr($post['cc_number'], -4, 4));
		    $payment->setCcType($post['cc_type']);
		}
		
		if ($saveCard == 1 && $post['cc_number'] != '') {		   
		    $profileResponse	  = Mage::getModel("md_cybersource/api_soap")
					    ->createCustomerProfileFromTransaction($response->requestID);
		    $code		  = $profileResponse->reasonCode;
		    $profileResponsecheck = $profileResponse->paySubscriptionCreateReply->reasonCode;
		    
		    if ($code == '100' && $profileResponsecheck == '100') {
			$saveData = $cybersourcePaymentObj->saveCustomerProfileData($profileResponse, $payment, $customerId);
		    } else {
			$errorMessage = $this->_errorMessage[$code];
			
			if ($code == '102' || $code == '101') {
			    $errorMessage = $response->invalidField . $response->missingField;
			    $errorMessage = is_array($errorMessage) ? implode(",", $errorMessage) : $errorMessage;
			    $errorMessage = $errorMessage . " , " . $this->_errorMessage[$resonCode];
			}
			
			if (isset($errorMessage) && !empty($errorMessage)) {
			    Mage::getSingleton("adminhtml/session")->addError("Error code: " . $code . " : " . $errorMessage);
			} else {
			    Mage::getSingleton("adminhtml/session")->addError("Error code: " . $code . " : " . $errorMessage);
			}
		    }
		}
		
		$csToRequestMap	    = $cybersourcePaymentObj::REQUEST_TYPE_AUTH_ONLY;
		$newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
		
		$payment->setAnetTransType($csToRequestMap);
		$payment->setAmount($amount);
		
		$card = $this->_registerCard($response, $payment);
		
		$card->setLastTransId($response->requestID);

		$payment->setLastTransId($response->requestID)
			->setCcTransId($response->requestID)
			->setTransactionId($response->requestID)
			->setmdCybersourceRequestid($response->requestID)
			->setCybersourceToken($response->requestToken)
			->setIsTransactionClosed(0)
			->setStatus($cybersourcePaymentObj::STATUS_APPROVED)
			->setCcAvsStatus($response->ccAuthReply->avsCode);
		
		if (!empty($response->ccAuthReply->cvCode) && isset($response->ccAuthReply->cvCode)) {
		    $payment->setCcCidStatus($response->ccAuthReply->cvCode);
		}
	    } else {
		$card	   = $this->_registerCard($response, $payment);
		$resonCode = $response->reasonCode;
		
		if ($resonCode == '102' || $resonCode == '101') {
		    $exceptionMessage = $response->invalidField . $response->missingField;
		    $exceptionMessage = is_array($exceptionMessage) ? implode(",", $exceptionMessage) : $exceptionMessage;
		    $exceptionMessage = empty($exceptionMessage) ? $this->_errorMessage[$resonCode] : $exceptionMessage;
		    $exceptionMessage = $cybersourcePaymentObj->_wrapGatewayError($exceptionMessage);
		    $exceptionMessage = $exceptionMessage . " , " . $this->_errorMessage[$resonCode];
		} else {
		    $exceptionMessage = $cybersourcePaymentObj->_wrapGatewayError($this->_errorMessage[$resonCode]);
		}
		
		$exceptionMessage = Mage::helper('md_cybersource')->getTransactionMessage(
		    $payment, $cybersourcePaymentObj::REQUEST_TYPE_AUTH_ONLY, $response->requestID, $card, $amount, $exceptionMessage
		);
		
		Mage::throwException($exceptionMessage);
	    }
	} catch (Exception $e) {
	    Mage::throwException(
		Mage::helper('md_cybersource')->__('Cybersource Gateway request error: %s', $e->getMessage())
	    );
	}
	
	if ($exceptionMessage !== false) {
	    Mage::throwException($exceptionMessage);
	}
	
	$message    = Mage::helper('md_cybersource')->getTransactionMessage(
			    $payment, $csToRequestMap, $response->requestID, $card, $amount
		    );
	
	$payment->setSkipTransactionCreation(true);
	
	if($fullPaymentFlag == 1) {
	    $dueSummaryCollection = $paymentDetails->getDueInstallmentCollections();
	    if ($dueSummaryCollection) {
		foreach ($dueSummaryCollection as $summaryModel) {
		    $summaryModel->setTransactionId($captureTransactionId)
				 ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
				 ->setPaymentMethod($payment->getMethod())
				 ->setTransactionDetails(serialize(array($message)))
				 ->setStatus($status)
				 ->save();
		}
	    }
	} else {
	    $summary->setTransactionId($response->requestID)
		    ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
		    ->setPaymentMethod($payment->getMethod())
		    ->setTransactionDetails(serialize(array($message)))
		    ->setStatus($status)
		    ->save();
	}

	Mage::getSingleton('adminhtml/session')->addSuccess('Installment(s) Processed Successfully.');
	
	return $this;
    }
    
    public function capture(Varien_Object $payment, $amount,$customerId,$subScriptionIdCheck) {
	$errorMessage	       = false;
	$post		       = Mage::app()->getRequest()->getParam('payment');
	$cybersourcePaymentObj = Mage::getModel('md_cybersource/payment');
	$saveCard	       = isset($post['save_card']) ? $post['save_card'] : 0;
	$fullPaymentFlag       = $this->getIsFullCapture();
	$order		       = $this->getOrder();
	$baseCurrencyCode      = $order->getBaseCurrencyCode();
	$orderPaid	       = $order->getTotalPaid();
	$orderDue	       = $order->getTotalDue();
	$summary	       = $this->getSummary();
	$paymentDetails	       = $this->getPayments();
	$paidInstallments      = $paymentDetails->getPaidInstallments();
	$dueInstallments       = $paymentDetails->getDueInstallments();
	$customer	       = Mage::getModel('customer/customer')->load($customerId);
	$message	       = '-';
	
	$cybersourcePaymentObj->getCardsStorage($payment);
	
	if($fullPaymentFlag == 1) {
	    $amount = $orderDue;
	}
	
	try {
	    if ($this->_isPreauthorizeCapture($payment)) {
		$this->_preauthorizeCapture($payment, $amount);
	    } else {
		if (!empty($subScriptionIdCheck) && empty($post['cc_number'])) {
		    $subScriptionIdCheck = Mage::helper('core')->decrypt($subScriptionIdCheck); 
		    $subscriptionFlag	 = true;
		    
		    $payment->setMdcybersourceSubscriptionId($subScriptionIdCheck);
		} else {
		    $subscriptionFlag	 = false;
		}
		
		$response = Mage::getModel("md_cybersource/api_soap")
			    ->prepareCaptureResponse($payment, $amount, $subscriptionFlag);
		
		if ($response->reasonCode == $cybersourcePaymentObj::RESPONSE_CODE_SUCCESS) {
		    if (!empty($subScriptionIdCheck)) {
			$card = $cybersourcePaymentObj->getSubscriptionCardInfo($subScriptionIdCheck);
			
			$payment->setCcLast4($card[0]['cc_last4']);
			$payment->setCcType($card[0]['cc_type']);
			$payment->setAdditionalInformation('md_cybersource_subscription_id', $subScriptionIdCheck);
			$payment->setMdcybersourceSubscriptionid($subScriptionIdCheck);
		    } else {
			$payment->setCcLast4(substr($post['cc_number'], -4, 4));
			$payment->setCcType($post['cc_type']);
		    }
		    
		    if ($saveCard == 1 && !empty($customerId)) {			
			$profileResponse      = Mage::getModel("md_cybersource/api_soap")
						->createCustomerProfileFromTransaction($response->requestID);
			$code		      = $profileResponse->reasonCode;
			$profileResponsecheck = $profileResponse->paySubscriptionCreateReply->reasonCode;
			
			if ($code == '100' && $profileResponsecheck == '100') {
			    $saveData   = $cybersourcePaymentObj->saveCustomerProfileData($profileResponse, $payment, $customerId);
			} else {
			    $errorMessage = $this->_errorMessage[$code];
			    
			    if ($code == '102' || $code == '101') {
				$errorMessage = $response->invalidField;
				$errorMessage = is_array($errorMessage) ? implode(",", $errorMessage) : $errorMessage;
				$errorMessage = $errorMessage . " , " . $this->_errorMessage[$resonCode];
			    }
			    
			    if (isset($errorDescription) && !empty($errorDescription)) {
				Mage::getSingleton("core/session")->addError("Error code: " . $code . " : " . $errorMessage);
			    } else {
				Mage::getSingleton("core/session")->addError("Error code: " . $code . " : " . $errorMessage);
			    }
			}
		    }
		    
		    $card		= $this->_registerCard($response, $payment);
		    $csToRequestMap	= $cybersourcePaymentObj::REQUEST_TYPE_AUTH_CAPTURE;
		    $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
		    
		    $card->setLastTransId($response->requestID);
		    $card->setCapturedAmount($card->getProcessedAmount());
		    
		    $captureTransactionId = $response->requestID;
		    
		    $card->setLastCapturedTransactionId($captureTransactionId);
		    $cybersourcePaymentObj->getCardsStorage($payment)->updateCard($card);

		    $payment->setLastTransId($response->requestID)
			    ->setLastCybersourceToken($response->requestToken)
			    ->setCcTransId($response->requestID)
			    ->setTransactionId($response->requestID)
			    ->setIsTransactionClosed(0)
			    ->setCybersourceToken($response->requestToken);
		    
		    $message    = Mage::helper('md_cybersource')->getTransactionMessage(
				    $payment, $csToRequestMap, $response->requestID, $card, $amount
				  );
		    
		    $orderPaid += $amount;
		    $orderDue  -= $amount;

		    $payment->setSkipTransactionCreation(true)
			    ->setPaidAmount($orderPaid)
			    ->setDueAmount($orderDue);

		    if($fullPaymentFlag == 1) {
			$paidInstallments += $dueInstallments;
			$dueInstallments  = 0;
		    } else {
			$dueInstallments  -= 1;
			$paidInstallments += 1;
		    }

		    if($dueInstallments < 0) {
			$dueInstallments = 0;
		    }

		    $paymentDetails->setPaidAmount($orderPaid)
				   ->setDueAmount($orderDue)
				   ->setPaidInstallments($paidInstallments)
				   ->setDueInstallments($dueInstallments)
				   ->save();

		    $order->setAmountPaid($orderPaid)
			  ->setBaseAmountPaid($orderPaid)
			  ->setTotalPaid($orderPaid)
			  ->setBaseTotalPaid($orderPaid)
			  ->setTotalDue($orderDue)
			  ->setBaseTotalDue($orderDue)
			  ->save();
		    
		    if(!empty($message) && $message != '-') {
			$order->addStatusHistoryComment($message)->save();
		    }

		    $status  = MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS;

		    if($fullPaymentFlag == 1) {
			$dueSummaryCollection = $paymentDetails->getDueInstallmentCollections();
			if ($dueSummaryCollection) {
			    foreach ($dueSummaryCollection as $summaryModel) {
				$summaryModel->setTransactionId($captureTransactionId)
					     ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
					     ->setPaymentMethod($payment->getMethod())
					     ->setTransactionDetails(serialize(array($message)))
					     ->setStatus($status)
					     ->save();
			    }
			}
		    } else {
			$summary->setTransactionId($captureTransactionId)
				->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
				->setPaymentMethod($payment->getMethod())
				->setTransactionDetails(serialize(array($message)))
				->setStatus($status)
				->save();
		    }

		    Mage::getSingleton('adminhtml/session')->addSuccess('Installment(s) Processed Successfully.');
		} else {
		    $card      = $this->_registerCard($response, $payment);
		    $resonCode = $response->reasonCode;
		    
		    if ($resonCode == '102' || $resonCode == '101') {
			$exceptionMessage = $response->invalidField;
			$exceptionMessage = is_array($exceptionMessage) ? implode(",", $exceptionMessage) : $exceptionMessage;
			$exceptionMessage = empty($exceptionMessage) ? $this->_errorMessage[$resonCode] : $exceptionMessage;
			$exceptionMessage = $cybersourcePaymentObj->_wrapGatewayError($exceptionMessage);
		    } else {
			$exceptionMessage = $cybersourcePaymentObj->_wrapGatewayError($this->_errorMessage[$resonCode]);
		    }
		    
		    $exceptionMessage = Mage::helper('md_cybersource')->getTransactionMessage(
			$payment, $cybersourcePaymentObj::REQUEST_TYPE_AUTH_CAPTURE, $response->requestID, $card, $amount, $exceptionMessage
		    );
		    Mage::throwException($exceptionMessage);
		}
	    }
	} catch (Exception $e) {
	    Mage::getSingleton('adminhtml/session_quote')->addError(
		Mage::helper('md_cybersource')->__('Gateway request error: %s', $e->getMessage())
	    );
	}
	
	$payment->setSkipTransactionCreation(true);
	
	return $this;
    }
}
