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

class MD_Partialpayment_Model_Payment_Authorizenetcim extends MD_Partialpayment_Model_Payment_Authorizenet {
    protected $_responseCodesMap = array(
	Mage_Paygate_Model_Authorizenet::METHOD_CODE => array(
	    1 => 1,
	    2 => 4,
	    3 => 2,
	    4 => 3
	),
    );

    public function pay($details) {
	$order		  = $this->getOrder();
	$payment	  = $this->getPayments();
	$summary	  = $this->getSummary();
	$amount		  = $summary->getAmount();
	$paymentProfileId = null;
	
	if (($details['payment_id'] != 'new') && empty($details['cc_number'])) {
	    $paymentProfileId = $details['payment_id'];
	    
	    $payment->setMdCimPaymentProfileId($details['payment_id']);
	}
	
	if ((isset($details['save_card']) && $details['save_card'] == 1) && !empty($details['cc_number'])) {
	    $payment->setSaveCard(true);
	}
	
	$this->processCimRequest($payment, $amount, 'profileTransAuthOnly', $details);
    }

    public function processCimRequest(Varien_Object $payment, $amount, $requestType = 'profileTransAuthOnly', $details) {
	$customer	   = Mage::getModel('customer/customer')->load($payment->getOrder()->getCustomerId());
	$customerProfileId = $this->getCustomerProfileId($customer, $payment);
	$paymentProfileId  = $this->getCustomerPaymentProfileId($customer, $payment, $details);
	$requestObject	   = new Varien_Object();
	
	$requestObject->addData(
	    array(
		'amount'	      => $amount, 
		'customer_profile_id' => $customerProfileId, 
		'payment_profile_id'  => $paymentProfileId, 
		'invoice_number'      => $payment->getOrder()->getIncrementId()
	    )
	);

	if ($payment->getOrder()->getBaseTaxAmount() && ($requestType == 'profileTransAuthOnly' || $requestType == 'profileTransAuthCapture')) {
	    $requestObject->addData(array('tax_amount' => round($payment->getOrder()->getBaseTaxAmount(), 4)));
	}
	
	if ($payment->getBaseShippingAmount()) {
	    $requestObject->addData(array('shipping_amount' => round($payment->getBaseShippingAmount(), 4)));
	}
	
	$authCimConfigModel = Mage::getModel("md_authorizecim/config")->setStoreId(Mage::app()->getStore()->getId());
	$apiMode	    = strtolower($authCimConfigModel->getApiType());
	$modelClass	    = "md_authorizecim/api_" . $apiMode;
	
	if ($authCimConfigModel->sendLineItems()) {
	    $requestObject->addData(array('line_items' => $this->_prepareLineItems($payment)));
	}
	
	if($authCimConfigModel->getAllowPartialAuthorization()) {
	    $allowPartialAuthorization = 'TRUE';
	} else {
	    $allowPartialAuthorization = 'FALSE';
	}
	
	$requestObject->addData(
	    array(
		'x_currency_code'      => $payment->getOrder()->getBaseCurrencyCode(), 
		'x_allow_partial_auth' => $allowPartialAuthorization, 
		'x_version'	       => 3.1
	    )
	);
	
	$requestObject->addData(
	    array(
		'email' => $payment->getOrder()->getBillingAddress()->getEmail()
	    )
	);
	
	$installmentCount = 0;
	$messages	  = array();
	$helper		  = Mage::helper('md_partialpayment');
	$result		  = Mage::getModel($modelClass)
			    ->setInputData($requestObject)
			    ->createCustomerProfileTransaction($requestType);
	
	if ($details['payment_id'] == 'new') {
	    $result->setCcLast4('xxxx-' . substr($details['cc_number'],-4));
	}
	
	$amount = $result->getAmount();

	if ($result instanceof Varien_Object && count($result->getData()) > 0) {
	    $failed = true;
	    
	    switch ($result->getResponseCode()) {
		case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_APPROVED:
		    if(!$this->getIsFullCapture()) {
			$installmentCount = 1;
		    } else {
			$installmentCount = (int) $this->getPayments()->getDueInstallments();
		    }
		    
		    $failed		  = false;
		    $messages['success']  = $helper->__('Payment Processed Successfully.');
		    $amount		  = $result->getAmount();
		    
		    if (is_array($details) && $details['payment_id'] == 'new' && $details['save_card'] != 1) {
			Mage::getModel('md_authorizecim/cards')
				->setCustomerId($payment->getOrder()->getCustomerId())
				->setCustomerProfileId($customerProfileId)
				->setPaymentProfileId($paymentProfileId)
				->save();
		    }
		    break;
		case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_HELD:
		    $installmentCount   = 0;
		    $amount	        = 0;
		    $messages['notice'] = $helper->__($result->getResponseReasonText());
		    break;
		case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_DECLINED:
		case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_ERROR:
		    $amount	       = 0;
		    $installmentCount  = 0;
		    $messages['error'] = $helper->__($result->getResponseReasonText());
		default:
		    break;
	    }
	    
	    if (array_key_exists('success', $messages)) {
		Mage::getSingleton('core/session')->addSuccess($messages['success']);
	    } elseif (array_key_exists('notice', $messages)) {
		Mage::getSingleton('core/session')->addError($messages['notice']);
	    } elseif (array_key_exists('error', $messages)) {
		Mage::getSingleton('core/session')->addError($messages['error']);
	    }
	    
	    if (!$this->getIsFullCapture()) {
		$summary = $this->getSummary()
			   ->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
			   ->setStatus($this->_responseCodesMap['authorizenet'][$result->getResponseCode()])
			   ->setTransactionId($result->getTransactionId())
			   ->setPaymentMethod($details['method'])
			   ->setPaymentFailCount($this->getSummary()->getPaymentFailCount() + $installmentCount)
			   ->setTransactionDetails(serialize($result->getData()));
	    } else {
		if (!$failed) {
		    $dueSummaryCollection = $this->getPayments()->getDueInstallmentCollections();
		    
		    if ($dueSummaryCollection) {
			foreach ($dueSummaryCollection as $_summary) {
			    
			    $_summary->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS)
				     ->setPaymentMethod('md_authorizecim')
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
			->setDueAmount(max(0, ($this->getPayments()->getDueAmount() - $amount)))
			->setLastInstallmentDate(Mage::getSingleton('core/date')->gmtDate())
			->setPaidInstallments($this->getPayments()->getPaidInstallments() + $installmentCount)
			->setDueInstallments(max(0, ($this->getPayments()->getDueInstallments() - $installmentCount)))
			->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
	    
	    if ($this->getIsFullCapture() && !$failed) {
		$payments->setFullPayment(1)->setFullPaymentData(serialize($result->getData()));
	    }

	    if ($payments->getDueInstallments() > 0) {
		$orderDueAmount     = max(0, ($this->getOrder()->getTotalDue() - $amount));
		$baseOrderDueAmount = max(0, ($this->getOrder()->getBaseTotalDue() - $amount));
	    } else {
		$orderDueAmount     = 0;
		$baseOrderDueAmount = 0;
	    }
	    
	    $order = $this->getOrder()
		    ->setTotalPaid($this->getOrder()->getTotalPaid() + $amount)
		    ->setBaseTotalPaid($this->getOrder()->getBaseTotalPaid() + $amount)
		    ->setTotalDue($orderDueAmount)
		    ->setBaseTotalDue($baseOrderDueAmount);

	    if (strlen($this->getResponseText($result->getData())) > 0) {
		$order->addStatusHistoryComment($this->getResponseText($result->getData()));
	    }
	    
	    $transaction = Mage::getModel('core/resource_transaction');
	    
	    if (!$this->getIsFullCapture()) {
		$transaction->addObject($summary);
	    }
	    
	    $transaction->addObject($payments);
	    $transaction->addObject($order);
	    
	    try {
		$transaction->save();
		if (!$this->getIsFullCapture()) {
		    $summary->sendStatusPaymentEmail(true, true);
		} else {
		    if (!$failed) {
			$payments->sendFullInstallmentEmail($result->getAmount());
		    }
		}
	    } catch (Exception $e) {
		Mage::getSingleton('core/session')->addError($e->getMessage());
	    }
	}
    }

// end of function 

    public function getCustomerProfileId($customer, Varien_Object $payment) {
	if ($payment->getOrder()->getCustomerIsGuest()) {
	    $customerProfileId = $payment->getAdditionalInformation('md_customer_profile_id');
	} else {
	    $customerProfileId = (string) $customer->getMdCustomerProfileId();
	}

	if (strlen($customerProfileId) <= 0) {
	    $authCimConfigModel = Mage::getModel("md_authorizecim/config")->setStoreId(Mage::app()->getStore()->getId());
	    $apiMode		= strtolower($authCimConfigModel->getApiType());
	    $modelClass		= "md_authorizecim/api_" . $apiMode;
	    $requestObject	= new Varien_Object();
	    $email		= $customer->getEmail();
	    $customerId		= $customer->getEntityId();
	    
	    /**
	     * If not logged in, we must be checking out as a guest--try to grab their info.
	     */
	    if (empty($email) || $customerId < 2) {
		$session	= Mage::getSingleton("customer/session")->getData();
		
		if ($payment != null && $payment->getQuote() != null && $payment->getQuote()->getCustomerEmail() != '') {
		    $email	= $payment->getQuote()->getCustomerEmail();
		    $customerId = is_numeric($payment->getQuote()->getCustomerId()) ? $payment->getQuote()->getCustomerId() : 0;
		} elseif ($payment != null && $payment->getOrder() != null && $payment->getOrder()->getCustomerEmail() != '') {
		    $email	= $payment->getOrder()->getCustomerEmail();
		    $customerId = is_numeric($payment->getOrder()->getCustomerId()) ? $payment->getOrder()->getCustomerId() : 0;
		} elseif (isset($session['visitor_data']) && !empty($session['visitor_data']['quote_id'])) {
		    $quote      = Mage::getModel('sales/quote')->load($session['visitor_data']['quote_id']);
		    $email	= $quote->getBillingAddress()->getEmail();
		    $customerId = is_numeric($quote->getBillingAddress()->getCustomerId()) ? $quote->getBillingAddress()->getCustomerId() : 0;
		}

		$customer->setEmail($email);
		$customer->setEntityId($customerId);
	    }
	    
	    /**
	     * Failsafe: We must have some email to go through here. The data might not actually be available.
	     */
	    if (empty($email)) {
		Mage::log("No customer email found; can't create a CIM profile.", null, 'authnetcim.log');
		return false;
	    }
	    
	    $requestObject->addData(array(
		'customer_id' => $payment->getOrder()->getCustomerIsGuest() ? 0 : $customerId,
		'email'       => $email
	    ));

	    $response = Mage::getModel($modelClass)
		        ->setInputData($requestObject)
		        ->createCustomerProfile();
	    
	    if($apiMode == 'xml') {
		$code	    = $response->messages->message->code;
		$resultCode = $response->messages->resultCode;
	    } else {
		$code	    = $response->CreateCustomerProfileResult->messages->MessagesTypeMessage->code;
		$resultCode = $response->CreateCustomerProfileResult->resultCode;
	    }
	    
	    if ($code == 'I00001' && $resultCode == 'Ok') {
		$customerProfileId = ($apiMode == 'xml') ? (string) $response->customerProfileId : (string) $response->CreateCustomerProfileResult->customerProfileId;
		
		$customer->setMdCustomerProfileId($customerProfileId)->save();
	    } elseif ($code == 'E00039' && strpos($response->site_display_message, 'duplicate') !== false) {
		$customerProfileId = preg_replace('/[^0-9]/', '', $response->site_display_message);
	    } else {
		$customerProfileId = false;
		
		Mage::throwException('Authorize.Net CIM Gateway: ' . $response->site_display_message);
	    }
	}
	
	return $customerProfileId;
    }

    public function getCustomerPaymentProfileId($customer, Varien_Object $payment, $posts) {
	$authCimConfigModel = Mage::getModel("md_authorizecim/config")->setStoreId(Mage::app()->getStore()->getId());
	$customerProfileId  = $this->getCustomerProfileId($customer, $payment);
	$paymentProfileId   = $posts['payment_id'];
	$order		    = $payment->getOrder();
	$billing	    = $order->getBillingAddress();
	$shipping	    = $order->getShippingAddress();
	
	if (!$paymentProfileId || $paymentProfileId == '') {
	    $paymentProfileId = $payment->getMdCimPaymentProfileId();
	}

	if ($paymentProfileId == 'new' || $paymentProfileId == '') {
	    $apiMode       = strtolower($authCimConfigModel->getApiType());
	    $modelClass    = "md_authorizecim/api_" . $apiMode;
	    $requestObject = new Varien_Object();
	    $requestObject->addData(
		array(
		    'customer_profile_id' => $customerProfileId, 
		    'cc_number'		  => $posts['cc_number'], 
		    'cc_exp_month'	  => $posts['cc_exp_month'], 
		    'cc_exp_year'	  => $posts['cc_exp_year'], 
		    'cc_cid'		  => $posts['cc_cid']
		)
	    );
	    
	    if ($billing) {
		$requestObject->addData(
		    array(
			'firstname'  => $billing->getFirstname(), 
			'lastname'   => $billing->getLastname(), 
			'company'    => $billing->getCompany(), 
			'street'     => $billing->getStreet(1), 
			'city'	     => $billing->getCity(), 
			'region_id'  => $billing->getRegionId(),
			'state'	     => $billing->getState(), 
			'postcode'   => $billing->getPostcode(), 
			'country_id' => $billing->getCountryId(), 
			'phone'	     => $billing->getTelephone(), 
			'fax'	     => $billing->getFax()
		    )
		);
	    }
	    if ($shipping) {
		$requestObject->addData(
		    array(
			'ship_firstname'  => $shipping->getFirstname(), 
			'ship_lastname'   => $shipping->getLastname(), 
			'ship_company'	  => $shipping->getCompany(), 
			'ship_street'	  => $shipping->getStreet(1), 
			'ship_city'	  => $shipping->getCity(), 
			'ship_region_id'  => $shipping->getRegionId(),
			'ship_state'	  => $shipping->getState(), 
			'ship_postcode'   => $shipping->getPostcode(), 
			'ship_country_id' => $shipping->getCountryId(), 
			'ship_phone'	  => $shipping->getTelephone(), 
			'ship_fax'	  => $shipping->getFax()
		));
	    }
	    $response = Mage::getModel($modelClass)
		        ->setInputData($requestObject)
			->createCustomerPaymentProfile();
	    
	    if($apiMode == 'xml') {
		$code	    = $response->messages->message->code;
		$resultCode = $response->messages->resultCode;
	    } else {
		$code	    = $response->CreateCustomerPaymentProfileResult->messages->MessagesTypeMessage->code;
		$resultCode = $response->CreateCustomerPaymentProfileResult->resultCode;
	    }
	    
	    $wasDuplicate = false;
	    
	    if ($code == 'I00001' && $resultCode == 'Ok') {
		if($apiMode == 'xml') {
		    $paymentProfileId = (string) $response->customerPaymentProfileId;
		} else {
		    $paymentProfileId = (string) $response->CreateCustomerPaymentProfileResult->customerPaymentProfileId;
		}
	    } elseif ($code == 'E00039' && strpos($response->site_display_message, 'duplicate') !== false) {
		$existingProfiles = $this->getCustomerPaymentProfiles($customer, $customerProfileId);
		$lastFour	  = substr($posts['cc_number'], -4);
		
		if (is_array($existingProfiles) && count($existingProfiles) > 0) {
		    foreach ($existingProfiles as $_existing) {
			$existingCard = substr((string) $_existing->payment->creditCard->cardNumber, -4);
			
			if ($lastFour == $existingCard) {
			    $wasDuplicate     = true;
			    $paymentProfileId = (string) $_existing->customerPaymentProfileId;
			    break;
			}
		    }
		    
		    if ($wasDuplicate && strlen($paymentProfileId) > 0) {
			$card = Mage::getModel('md_authorizecim/cards')->load($paymentProfileId, 'payment_profile_id');

			if ($card->getId() > 0) {
			    $card->delete();
			}
			$requestObject['customer_profile_id'] = $customerProfileId;
			$requestObject['payment_profile_id']  = $paymentProfileId;
			
			$response = Mage::getModel($modelClass)
				->setInputData($requestObject)
				->updateCustomerPaymentProfile();
			
			if($apiMode == 'xml') {
			    $code = $response->messages->message->code;
			    $resultCode = $response->messages->resultCode;
			} else {
			    $code = $response->CreateCustomerPaymentProfileResult->messages->MessagesTypeMessage->code;
			    $resultCode = $response->CreateCustomerPaymentProfileResult->resultCode;
			}
			  
			if ($code != 'I00001') {
			    Mage::throwException('Authorize.Net CIM Gateway: ' . $response->site_display_message);
			}
		    }
		}
	    } else {
		$paymentProfileId = false;
		
		Mage::throwException('Authorize.Net CIM Gateway: ' . $response->site_display_message);
	    }
	} else {
	    $paymentProfileId = $posts['payment_id'];

	    if (!$paymentProfileId || $paymentProfileId == '') {
		$paymentProfileId = $payment->getMdCimPaymentProfileId();
	    }
	}
	return $paymentProfileId;
    }

    public function getCustomerPaymentProfiles($customer, $customerProfileId = null, $removeExcludedCards = false) {
	$authCimConfigModel = Mage::getModel("md_authorizecim/config")->setStoreId(Mage::app()->getStore()->getId());
	$cards		    = array();
	
	if (is_null($customerProfileId)) {
	    $customerProfileId = $customer->getMdCustomerProfileId();
	}

	if (!empty($customerProfileId)) {
	    $requestObject = new Varien_Object();
	    
	    $requestObject->addData(array(
		"customer_profile_id" => $customerProfileId
	    ));
	    
	    $apiMode	= strtolower($authCimConfigModel->getApiType());
	    $modelClass = "md_authorizecim/api_" . $apiMode;
	    $response	= Mage::getModel($modelClass)
			  ->setInputData($requestObject)
			  ->getCustomerProfile();
	    
	    if($apiMode == 'xml') {
		$resultCode   = (string) $response->messages->resultCode;
		$responseCode = (string) $response->messages->message->code;
	    } else {
		$resultCode   = (string) $response->GetCustomerProfileResult->resultCode;
		$responseCode = (string) $response->GetCustomerProfileResult->messages->MessagesTypeMessage->code;
	    }
	     
	    if ($responseCode == 'I00001' && $resultCode == 'Ok') {
		if ($apiMode == 'xml') {
		    $profiles = $response->profile->paymentProfiles;
		} else {
		    $profiles = $response->GetCustomerProfileResult->profile->paymentProfiles->CustomerPaymentProfileMaskedType;
		}
		 
		if (count($profiles) > 1) {
		    foreach ($profiles as $paymentProfiles) {
			if ($removeExcludedCards) {
			    if (Mage::helper('md_authorizecim')->shouldDisplayCard($paymentProfiles->customerPaymentProfileId) == 1) {
				$cards[] = $paymentProfiles;
			    }
			} else {
			    $cards[] = $paymentProfiles;
			}
		    }
		} elseif (count($profiles) == 1) {
		    if ($removeExcludedCards) {
		       if (Mage::helper('md_authorizecim')->shouldDisplayCard($profiles->customerPaymentProfileId) == 1) {
			    $cards[] = $profiles;
		       }
		    } else {
			$cards[] = $profiles;
		    }
		}
	    }
	}
	
	return $cards;
    }

    protected function _prepareLineItems(Varien_Object $payment) {
	$items = array();
	
	if (is_object($payment)) {
	    $order = $payment->getOrder();
	    
	    if ($order instanceof Mage_Sales_Model_Order) {
		$i = 0;
		
		foreach ($order->getAllVisibleItems() as $_item) {
		    $items[$i]	      = array(
			'sku'	      => $_item->getSku(), 
			'name'	      => substr($_item->getName(), 0, 30), 
			'description' => $_item->getName(), 
			'qty'	      => $_item->getQtyOrdered(), 
			'price'	      => round($_item->getBasePrice(), 2), 
			'taxable'     => true
		    );
		    $i++;
		}
	    }
	}
	
	return $items;
    }
}
