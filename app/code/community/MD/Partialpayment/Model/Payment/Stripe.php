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

class MD_Partialpayment_Model_Payment_Stripe extends MD_Partialpayment_Model_Payment_Abstract {
    protected $_realTransactionIdKey = 'real_transaction_id';
    protected $_store = 0;
    protected $_nonZeroDecimalCurriencies = array(
	'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZM', 'BAM', 'BBD', 'BDT', 'BGN', 'BMD', 'BND', 
	'BOB', 'BRL', 'BSD', 'BTN', 'BWP', 'BZD', 'CAD', 'CDF', 'CHF', 'CNY', 'COP', 'CRC', 'CSD', 'CUP', 'CVE', 'CYP', 
	'CZK', 'DKK', 'DOP', 'DZD', 'EEK', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GHC', 'GIP', 'GMD', 
	'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'IRR', 'JMD', 'KES', 'KGS', 'KHR', 'KPW', 
	'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LTL', 'LVL', 'MAD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT',
	'MOP', 'MRO', 'MTL', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZM', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 
	'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'QAR', 'RON', 'RUB', 'SAR', 'SBD', 'SCR', 'SDD', 'SEK', 'SGD', 'SHP', 'SIT', 
	'SKK', 'SLL', 'SOS', 'SRD', 'STD', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMM', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 
	'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEB', 'VND', 'WST', 'XCD', 'YER', 'ZAR', 'ZMK', 'ZWD'
    );
    
   public function pay($details = null) {
	try {
	    $requestMap	        = $card	     = null;
	    $isPreAuthorize	= $isPartial = false;
	    $requestObject      = array();
	    $posts	        = Mage::app()->getRequest()->getParam('payment');
	    
	    $cardsPaymentModel  = Mage::getModel('md_stripe/cardspayment');
	    $fullPaymentFlag	= $this->getIsFullCapture();
	    $order	        = $this->getOrder();
	    $baseCurrencyCode   = $order->getBaseCurrencyCode();
	    $orderPaid		= $order->getTotalPaid();
	    $orderDue		= $order->getTotalDue();
	    $summary	        = $this->getSummary();
	    $amount		= $summary->getAmount();
	    $paymentDetails     = $this->getPayments();
	    $customerId	        = $paymentDetails->getCustomerId();	    
	    $paidInstallments   = $paymentDetails->getPaidInstallments();
	    $dueInstallments    = $paymentDetails->getDueInstallments();
	    $customer	        = Mage::getModel('customer/customer')->load($customerId);
	    $restApiObject	= Mage::getModel('md_stripe/api_rest')
				 ->setApiType($cardsPaymentModel::METHOD_CODE)
				 ->setStore($this->_store);
	    
	    if(!empty($posts['md_stripe_card_id'])) {
		$stripeCardId = $posts['md_stripe_card_id'];
	    } else if(!empty($details['md_stripe_card_id'])) {
		$stripeCardId = $details['md_stripe_card_id'];
	    } else {
		$stripeCardId = null;
	    }
	
	    if($fullPaymentFlag == 1) {
		$amount = $orderDue;
	    }
	    if(empty($posts['md_stripe_save_card'])) {
		$isSaveCardMandatory = Mage::getStoreConfig('payment/' . MD_Stripe_Model_Cardspayment::METHOD_CODE . '/save_optional');
		
		if($isSaveCardMandatory == 0) {
		    $posts['md_stripe_save_card'] = 1;
		} else {
		    $posts['md_stripe_save_card'] = 0;
		}
	    }
	    
	    $payment = Mage::getModel('sales/order_payment')->setMethod(MD_Stripe_Model_Cardspayment::METHOD_CODE);
		
	    if($stripeCardId == 'new') {
		$payment->setData('cc_cid',$posts['cc_cid'])
			->setData('md_stripe_save_card',$posts['md_stripe_save_card'])
			->setData('cc_number',$posts['cc_number'])
			->setData('cc_exp_month',$posts['cc_exp_month'])
			->setData('cc_exp_year',$posts['cc_exp_year'])
			->setData('cc_last4',substr($posts['cc_number'], -4));
	    } else {
		$payment->setData('md_stripe_card_id',$stripeCardId);
	    }
		
	    $payment->setOrder($order);
	    
	    $gatewayCustomerId  = $cardsPaymentModel->getGatewayCustomerId($customer, $payment);
	    $gatewayCardId      = $this->getGatewayCardId($gatewayCustomerId, $payment);
	    $baseCurrencyCode   = $order->getBaseCurrencyCode();
	    $requestType	= Mage::getStoreConfig('payment/' . $payment->getMethod() . '/payment_action');
	    
	    if($requestType == 'authorize') {
		$requestType	= $cardsPaymentModel::REQUEST_TYPE_AUTH_ONLY;
	    } else {
		$requestType	= $cardsPaymentModel::REQUEST_TYPE_AUTH_CAPTURE;
	    }
	    
	    $customerName       = $payment->getOrder()->getCustomerFirstname();
	    $customerName      .= ' '.$payment->getOrder()->getCustomerLastname();
	    
	    if(in_array($baseCurrencyCode, $this->_nonZeroDecimalCurriencies))  {
		$amountCorrected = $amount * 100;
	    } else {
		$amountCorrected = $amount * 1;
	    }
	    
	    if($requestType == $cardsPaymentModel::REQUEST_TYPE_AUTH_ONLY) {
		$requestMap = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
	    } else {
		$requestMap = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
	    }
	    
	    $requestObject['amount']   = round($amountCorrected, 2);
	    $requestObject['currency'] = $baseCurrencyCode;
	    $requestObject['customer'] = $gatewayCustomerId;
	    $requestObject['source']   = $gatewayCardId;
	    $requestObject['metadata'] = array(
		'Base Url'	       => Mage::getBaseUrl(),
		'Order Increment Id'   => $payment->getOrder()->getIncrementId(),
		'Store #'	       => $payment->getOrder()->getStoreId(),
		'Store Name'	       => $payment->getOrder()->getStoreName(),
		'Customer Name'	       => $customerName,
		'Email'		       => $payment->getOrder()->getCustomerEmail()
	    );
	    
	    if(in_array($requestType, array($cardsPaymentModel::REQUEST_TYPE_AUTH_CAPTURE, $cardsPaymentModel::REQUEST_TYPE_CAPTURE_ONLY))) {
		$requestObject['capture'] = 'true';
	    } else {
		$requestObject['capture'] = 'false';
	    }
	     
	    $requestObject['statement_descriptor'] = sprintf('order #%s', $order->getIncrementId());
	    $requestObject['receipt_email']	   = $order->getBillingAddress()->getCustomerEmail();
	    
	    
	    
	    $restResponse  = $restApiObject->chargeCustomerCard($requestObject);
	    
	    if (array_key_exists('result_data', $restResponse)) {
		$restResponseObject = $restResponse['result_data'];
		
		if ((boolean) $restResponseObject->paid === true && $restResponseObject->status == $cardsPaymentModel::STRIPE_CHARGE_SUCCEEDED) {
		    $payment->setCcLast4((string) $restResponseObject->source->last4);
		    $payment->setCcType(Mage::helper('md_stripe')->getCardCode((string) $restResponseObject->source->brand));
		    if (!$isPreAuthorize && is_null($card)) {
			$card = $this->_registerCard($restResponseObject, $payment);
		    }
		    
		    $card->setLastTransId((string) $restResponseObject->id);
		    
		    if ($requestType == $cardsPaymentModel::REQUEST_TYPE_AUTH_CAPTURE) {
			$card->setCapturedAmount($card->getProcessedAmount());
			
			$captureTransactionId = $restResponseObject->id;
			
			$card->setLastCapturedTransactionId($captureTransactionId);
			$cardsPaymentModel->getCardsStorage($payment)->updateCard($card);
		    }
		    
		   if (is_array($posts) && isset($stripeCardId) && $stripeCardId == 'new' && $posts['md_stripe_save_card'] != 1) {
			Mage::getModel('md_stripe/cards')
			    ->setCustomerId($payment->getOrder()->getCustomerId())
			    ->setStripeCardId($gatewayCardId)
			    ->setStripeCustomerId($gatewayCustomerId)
			    ->save();
		    }
		    
		    $payment->setMdStripeCardId($gatewayCardId);
		    $payment->setAdditionalInformation('md_stripe_card_id', $gatewayCardId);
		    $payment->setAdditionalInformation('md_stripe_customer_id', $gatewayCustomerId);

		    $paymentDetails->setSaveCard(true);
		    
		    if ($isPreAuthorize && !is_null($card)) {
			$cardsPaymentModel->getCardsStorage($payment)->updateCard($card);
		    }
		    
		    
		    $orderPaid += $amount;
		    $orderDue  -= $amount;
		    
		    if ($requestType == $cardsPaymentModel::REQUEST_TYPE_AUTH_CAPTURE) {
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
			
			$message = Mage::helper('md_stripe')->getTransactionMessage($payment, $requestType, $restResponseObject->id, $card, $amount);
			
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
			
			$order->addStatusHistoryComment($message)->save();
			
			$status  = MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS;
		    } else {
			$message = $captureTransactionId;
			$status  = MD_Partialpayment_Model_Summary::PAYMENT_PROCESS;
		    }
		    
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
		    Mage::throwException(Mage::helper('md_stripe')->__('Error when payment processing.'));
		}
	    } else {
		Mage::throwException($restResponse['error_code']);
	    }
	} catch(Exception $ex) {
	    Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
	    Mage::throwException($ex->getMessage());
	}
    }
    
     public function getGatewayCardId($gatewayCustomerId = null, $payment) {
	if (!is_null($gatewayCustomerId)) {
	    $cardsPaymentModel = Mage::getModel('md_stripe/cardspayment');
	    $gatewayCardId     = '';
	    
	    if (!$gatewayCardId || $gatewayCardId == '') {
		$gatewayCardId = $payment->getMdStripeCardId();
	    }
	    if ($gatewayCardId == 'new' || $gatewayCardId == '') {
		$billingAddress = $payment->getOrder()->getBillingAddress();
		$requestData	       = array();
		$requestData['source'] = array(
		    'object'	      => 'card',
		    'number'	      => str_replace(array('-', ' '), '', $payment->getCcNumber()),
		    'exp_month'	      => sprintf('%02d', $payment->getCcExpMonth()),
		    'exp_year'	      => sprintf('%04d', $payment->getCcExpYear()),
		    'cvc'	      => $payment->getCcCid(),
		    'name'	      => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
		    'address_line1'   => $billingAddress->getStreet(1),
		    'address_line2'   => $billingAddress->getStreet(2),
		    'address_city'    => $billingAddress->getCity(),
		    'address_state'   => $billingAddress->getRegionId(),
		    'address_zip'     => $billingAddress->getPostcode(),
		    'address_country' => $billingAddress->getCountryId()
		);
		
		$restResponse = $cardsPaymentModel->getRestModel()->createCard($requestData, $gatewayCustomerId);
		
		if (array_key_exists('result_data', $restResponse)) {
		    $restResponseObject = $restResponse['result_data'];
		    $gatewayCardId      = $restResponseObject->id;
		} else {
		    Mage::throwException(Mage::helper('md_stripe')->__($restResponse['error_code']));
		}
	    }
	    return $gatewayCardId;
	} else {
	    Mage::throwException(Mage::helper('md_stripe')->__('Customer card id missing'));
	}
    } 
    
    protected function _registerCard(stdClass $response, Mage_Sales_Model_Order_Payment $payment) {
	$cardsPaymentModel = Mage::getModel('md_stripe/cardspayment');
	$cardsStorage      = $cardsPaymentModel->getCardsStorage($payment);
	$card		   = $cardsStorage->registerCard();
	$chargeCurrency    = strtoupper((string) $response->currency);
	$amountCorrected   = (in_array($chargeCurrency, $this->_nonZeroDecimalCurriencies)) ? $response->amount / 100 : $response->amount / 1;
	
	try {
	    $addLine2 = $response->source->address_line2_check;
	} catch (Exception $ex) {
	    $addLine2 = '';
	}
	
	$card->setRequestedAmount($amountCorrected)
	     ->setBalanceOnCard(null)
	     ->setLastTransId((string) $response->id)
	     ->setProcessedAmount($amountCorrected)
	     ->setCcType(Mage::helper('md_stripe')->getCardCode((string) $response->source->brand))
	     ->setCcOwner((string) $response->source->name)
	     ->setCcLast4((string) $response->source->last4)
	     ->setCcExpMonth((string) $response->source->exp_month)
	     ->setCcExpYear((string) $response->source->exp_year)
	     ->setCcSsIssue(null)
	     ->setCcSsStartMonth(null)
	     ->setCcSsStartYear(null)
	     ->setApprovalCode(null)
	     ->setAddressLine1Check((string) $response->source->address_line1_check)
	     ->setAddressLine2Check((string) $addLine2)
	     ->setAddressZipCheck((string) $response->source->address_zip_check)
	     ->setCvcCheck((string) $response->source->cvc_check)
	     ->setTransactionId((string) $response->id)
	     ->setDescription((string) $response->description)
	     ->setMethod('card')
	     ->setTransactionType((!$response->captured) ? Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH : Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
	
	$cardsStorage->updateCard($card);
	
	return $card;
    }
}
