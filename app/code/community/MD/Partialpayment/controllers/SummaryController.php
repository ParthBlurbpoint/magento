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
class MD_Partialpayment_SummaryController extends Mage_Core_Controller_Front_Action {

    protected $_redirectMethods = array (
	Mage_Paypal_Model_Config::METHOD_WPS,
    );
    
    protected $_creditCardRequiredMethod = array (
	Mage_Paygate_Model_Authorizenet::METHOD_CODE,
    );
    
    protected $_processMethod	  = array (
	Mage_Paygate_Model_Authorizenet::METHOD_CODE => 'md_partialpayment/payment_authorizenet',
	'ccsave'		  => 'md_partialpayment/payment_ccsave',
	'checkmo'		  => 'md_partialpayment/payment_checkmo',
	'cashondelivery'	  => 'md_partialpayment/payment_cashondelivery',
	'authorizenet_directpost' => 'md_partialpayment/payment_authorizenet_directpost',
	'md_authorizecim'	  => 'md_partialpayment/payment_authorizenetcim',
	'md_stripe_cards'	  => 'md_partialpayment/payment_stripe',
	'md_cybersource'	  => 'md_partialpayment/payment_cybersource',
    );
    
    protected $_redirectAction = array (
	Mage_Paypal_Model_Config::METHOD_WPS => '*/*/paypalRedirect',
    );
    
    protected $_sagepayDetails = array  (
	'sagepaydirectpro'     => array (
	    "action"	       => "directRedirect",
	    "controller"       => "sagepay",
	    "module"	       => "md_partialpayment"
	),
	'sagepayserver'  => array (
	    "action"	 => "serverRedirect",
	    "controller" => "sagepay",
	    "module"	 => "md_partialpayment"
	)
    );
    
    protected $_adminActionMethod = array(
	'ccsave', 'checkmo', 'cashondelivery'
    );

    public function paypalRedirectAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $summaryId		= $this->getRequest()->getParam('summary_id');
	    $isFullSelected	= $this->getRequest()->getParam('is_full_selected', 0);
	    $p			= $this->getRequest()->getParam('p', null);
	    $limit		= $this->getRequest()->getParam('limit', null);
	    $paymentRequestArea = $this->getRequest()->getParam('request_area', null);
	    $session		= Mage::getSingleton('core/session');

	    $session->setPartialSummaryId($summaryId);
	    $session->setPartialFullSelected($isFullSelected);

	    $this->getResponse()->setBody($this->getLayout()->createBlock('md_partialpayment/paypal_standard_redirect')->setPageNo($p)->setPagerLimit($limit)->setPaymentRequestArea($paymentRequestArea)->toHtml());
	}
    }

    public function paypalCancelAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $params    = $this->getRequest()->getParams();
	    $p	       = ($params['p']) ? $params['p'] : null;
	    $limit     = ($params['limit']) ? $params['limit'] : null;
	    $returnUrl = Mage::getUrl('md_partialpayment/summary/view', array('payment_id' => $params['payment_id']));
	    
	    Mage::getSingleton('core/session')->addError($this->__('Error Occured during payment.'));

	    if ($p && $limit) {
		$returnUrl .= '?p=' . $p . '&limit=' . $limit;
	    } elseif ($p) {
		$returnUrl .= '?p=' . $p;
	    } elseif ($limit) {
		$returnUrl .= '?limit=' . $limit;
	    }

	    $this->getResponse()->setRedirect($returnUrl);
	}
    }

    public function paypalSuccessAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $params    = $this->getRequest()->getParams();
	    $p	       = isset($params['p']) ? $params['p'] : null;
	    $limit     = isset($params['limit']) ? $params['limit'] : null;
	    $returnUrl = Mage::getUrl('md_partialpayment/summary/view', array('payment_id' => $params['payment_id']));
	    
	    Mage::getSingleton('core/session')->addSuccess($this->__('Payment has been submited'));

	    if ($p && $limit) {
		$returnUrl .= '?p=' . $p . '&limit=' . $limit;
	    } elseif ($p) {
		$returnUrl .= '?p=' . $p;
	    } elseif ($limit) {
		$returnUrl .= '?limit=' . $limit;
	    }

	    $this->getResponse()->setRedirect($returnUrl);
	}
    }

    public function paypalIpnAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    if (!$this->getRequest()->isPost()) {
		return;
	    }

	    try {
		$data  = $this->getRequest()->getPost();
		$param = $this->getRequest()->getParams();
		$fullOptionSelected = (boolean) $param['full_payment'];

		Mage::getModel('md_partialpayment/payment_paypal_standard')->processIpnRequest($data, $param['summary_id'], $param['payment_id'], $fullOptionSelected);
	    } catch (Exception $e) {
		Mage::logException($e);
		$this->getResponse()->setHttpResponseCode(500);
	    }
	}
    }

    public function preDispatch() {
	parent::preDispatch();
	$action = $this->getRequest()->getActionName();

	if (!in_array($action, array('paypalIpn', 'relayResponse', 'pay', 'paypalRedirect', 'cartPayment', 'removeCartOption'))) {
	    $loginUrl = Mage::helper('customer')->getLoginUrl();

	    if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
		$this->setFlag('', self::FLAG_NO_DISPATCH, true);
	    }
	}
	
	return $this;
    }

    public function relayResponseAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $data      = $this->getRequest()->getPost();
	    $summaryId = $this->getRequest()->getParam('summary_id', null);

	    Mage::getModel('md_partialpayment/payment_authorizenet_directpost')->process($data, $summaryId);
	}
    }

    public function listAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $this->loadLayout();
	    $this->renderLayout();
	} else {
	    $this->_redirect('customer/account/');
	}
    }

    public function viewAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    if (!$this->_loadValidInstallment()) {
		return;
	    }

	    $this->loadLayout();
	    $this->_initLayoutMessages('catalog/session');

	    $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');

	    if ($navigationBlock) {
		$navigationBlock->setActive('md_partialpayment/summary/list');
	    }

	    $this->renderLayout();
	} else {
	    $this->_redirect('customer/account/');
	}
    }

    protected function _loadValidInstallment($paymentId = null) {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
	    
	    if (null === $paymentId) {
		$paymentId = (int) $this->getRequest()->getParam('payment_id');
	    }
	    
	    if (!$paymentId) {
		$this->_forward('noRoute');
		return false;
	    }

	    $payment = Mage::getModel('md_partialpayment/payments')->getCollection()
		    ->addFieldToFilter('payment_id',array('eq'=>$paymentId))
		    ->addFieldToFilter('customer_id',array('eq'=>$customerId))
		    ->getFirstItem();
	    
	    if ($payment->getId()) {
		return true;
	    } else {
		Mage::getSingleton("core/session")->addError(Mage::helper('md_partialpayment')->__('Installment not found !!'));
		$this->_redirect('*/*/list');
	    }
	}
	return false;
    }

    public function paymentOptionsAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $this->loadLayout();

	    $params = $this->getRequest()->getParam('summary_id');
	    $block  = $this->getLayout()->createBlock('md_partialpayment/summary_payment_methods')->setSummaryId($params);

	    $this->getResponse()->setBody($block->toHtml());
	}
    }

    public function payAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $params	     = $this->getRequest()->getParams();
	    $p		     = isset($params['p']) ? $params['p'] : null;
	    $limit	     = isset($params['limit']) ? $params['limit'] : null;
	    $summaryId	     = (array_key_exists('payment_summary', $params)) ? $params['payment_summary'] : null;
	    $summary	     = null;
	    $paymentId	     = $params['payment_id'];
	    $fullPaymentFlag = (array_key_exists('full_payment', $params) && $params['full_payment'] == 1) ? true : false;
	    $allowPay	     = true;
	    $method	     = isset($params['partial']['method']) ? $params['partial']['method'] : '';
	    
	    if ($summaryId) {
		$summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);

		if ($summary) {
		    if ($summary->getStatus() == MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS) {
			$allowPay = false;
		    }
		}
	    }
	    
	    if(isset($params[$method])) {
		$info	    = $params[$method];
	    } else {
		$info	    = array();
	    }
	    $info['method'] = $method;
	    
	    /*if($info['cc_number'] != null || strlen($info['cc_number']) > 0) {
		unset($info['payment_id']);
	    } */
	    
	    $payments	    = ($summary) ? $summary->getPayments() : Mage::getModel('md_partialpayment/payments')->load($paymentId);
	    $order	    = $payments->getOrder();
	    $returnUrl	    = Mage::getUrl('md_partialpayment/summary/view', array('payment_id' => $paymentId));

	    if ($p && $limit) {
		$returnUrl .= '?p=' . $p . '&limit=' . $limit;
	    } elseif ($p) {
		$returnUrl .= '?p=' . $p;
	    } elseif ($limit) {
		$returnUrl .= '?limit=' . $limit;
	    }

	    if (is_null($summaryId) && !$fullPaymentFlag) {
		Mage::getSingleton("core/session")->addError(Mage::helper("md_partialpayment")->__("Installment summary not found."));
		$this->getResponse()->setRedirect($returnUrl);
		return $this;
	    }

	    if ($allowPay) {
		if (in_array($method, $this->_redirectMethods)) {
		    
		    $this->_redirect($this->_redirectAction[$method], array('_secure' => true, 'summary_id' => $summaryId, 'p' => $p, 'limit' => $limit, 'is_full_selected' => (int) $fullPaymentFlag));
		    
		} elseif (array_key_exists($method, $this->_sagepayDetails)) {
		    
		    $this->_forward($this->_sagepayDetails[$method]["action"], $this->_sagepayDetails[$method]["controller"], $this->_sagepayDetails[$method]["module"]);
		    
		} else {
		    try {
			Mage::getModel($this->_processMethod[$method])
				->setIsFullCapture($fullPaymentFlag)
				->setSummary($summary)
				->setPayments($payments)
				->setOrder($order)
				->pay($info);
		    } catch (Exception $e) {
			Mage::getSingleton('core/session')->addError($e->getMessage());
		    }

		    $this->getResponse()->setRedirect($returnUrl);
		}
	    } else {
		Mage::getSingleton("core/session")->addError(Mage::helper('md_partialpayment')->__('This installment has already paid.'));
		$this->getResponse()->setRedirect($returnUrl);
	    }
	}
    }

    public function cartPaymentAction() {
	if (Mage::helper('md_partialpayment')->isEnabledOnFrontend()) {
	    $quote	     = Mage::getSingleton("checkout/cart")->getQuote();
	    $params	     = $this->getRequest()->getParams();
	    $optionObject    = new MD_Partialpayment_Model_Options();
	    $isEligibleForPP = Mage::helper('md_partialpayment')->checkCartTotalEligibility($quote);
	    
	    $optionObject->addData(array(
		"initial_payment_amount"    => null,
		"additional_payment_amount" => null,
		"product_id"		    => null
	    ));

	    $frequencyMap   = array(
		'weekly'    => ' +7 days',
		'quarterly' => ' +3 months',
		'monthly'   => ' +1 month'
	    );

	    if (!$isEligibleForPP) {
		$minCartTotal	      = Mage::getStoreConfig(MD_Partialpayment_Helper_Data::PARTIAL_MINIMUM_CART_TOTAL);
		$minimumCartTotalType = Mage::getStoreConfig(MD_Partialpayment_Helper_Data::PARTIAL_MINIMUM_CART_TOTAL_TYPE);

		if (empty($minimumCartTotalType) || $minimumCartTotalType == 'subtotal') {
		    Mage::getSingleton("checkout/session")->addError("Minimum Cart Subtotal after discount(if any) should be " . $minCartTotal);
		} else {
		    Mage::getSingleton("checkout/session")->addError("Minimum Cart Grand Total after discount(if any) should be " . $minCartTotal);
		}
		
	    } else {
		if (is_array($params['partialpayment']) && count($params['partialpayment']) > 0) {
		    foreach ($quote->getAllVisibleItems() as $_item) {
			$qty	      = 1;
			$price	      = $_item->getProduct()->getFinalPrice();
			$itemDiscount = $_item->getDiscountAmount();
			$price	     -= $itemDiscount; 
			
			$optionObject->addData(array("product_id" => $_item->getProductId()));
			
			$installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($_item->getProduct(), $optionObject, $qty, $price, $params['partialpayment']['price'], $params['partialpayment']['price_type'], $params['partialpayment']['installments'],true);
			
			if (count($installmentSummary) > 0) {
			    $frequency	     = Mage::getStoreConfig('md_partialpayment/general/frequency_of_payments');
			    $createdAt	     = date('Y-m-d', strtotime($_item->getCreatedAt()));
			    $nextPaymentDate = date('Y-m-d', strtotime($createdAt . $frequencyMap[$frequency]));
			    
			    $_item->setData('partialpayment_option_selected', '1');
			    $_item->setData('partialpayment_installment_count', $installmentSummary['installment_count']);
			    $_item->setData('partialpayment_paid_amount', $installmentSummary['initial_payment_amount']);
			    $_item->setData('partialpayment_due_amount', $installmentSummary['remaining_amount']);
			    $_item->setData('partialpayment_frequency', $frequency);
			    $_item->setData('partialpayment_amount_due_after_date', $installmentSummary['installment_amount']);
			    $_item->setData('partialpayment_next_installment_date', $nextPaymentDate);
			    $_item->setData('partialpayment_price_type', $params['partialpayment']['price_type']);
			    $_item->setData('partialpayment_price', $params['partialpayment']['price']);
			    $_item->addOption(
				array(
				    "code"	 => "partialpayment_origional_price",
				    "value"	 => $_item->getProduct()->getFinalPrice(),
				    "product_id" => $_item->getProductId()
				)
			    );
			    
			    $_item->getProduct()->setSpecialPrice($installmentSummary['unit_payment']);
			    $_item->setPrice($installmentSummary['unit_payment']);
			    $_item->setBasePrice($installmentSummary['unit_payment']);
			    $_item->setCustomPrice($installmentSummary['unit_payment']);
			    $_item->setOriginalCustomPrice($installmentSummary['unit_payment']);
			    $_item->getProduct()->setIsSuperMode(true);
			}
		    }
		    
		    $quote->setData("md_partialpayment_full_cart", "1");
		    $quote->setData("md_partialpayment_price_type", $params['partialpayment']['price_type']);
		    $quote->setData("md_partialpayment_price", $params['partialpayment']['price']);
		    $quote->setData("md_partialpayment_installments_count", $params['partialpayment']['installments']);
		    $quote->getShippingAddress()->setCollectShippingRates(true);
		    $quote->collectTotals()->save();

		    Mage::getSingleton("checkout/session")->addSuccess("Partial payment option has been applied to cart.");
		} else {
		    Mage::getSingleton("checkout/session")->addError("Partial payment option not found.");
		}
	    }
	}
	$this->_redirect("checkout/cart/");
    }

    public function removeCartOptionAction() {
	$quote = Mage::getSingleton("checkout/cart")->getQuote();
	
	foreach ($quote->getAllVisibleItems() as $_item) {
	    $_item->setData('partialpayment_option_selected', '0');
	    $_item->setData('partialpayment_installment_count', NULL);
	    $_item->setData('partialpayment_paid_amount', NULL);
	    $_item->setData('partialpayment_due_amount', NULL);
	    $_item->setData('partialpayment_frequency', NULL);
	    $_item->setData('partialpayment_amount_due_after_date', NULL);
	    $_item->setData('partialpayment_next_installment_date', NULL);
	    $_item->setData('partialpayment_price_type', NULL);
	    $_item->setData('partialpayment_price', NULL);
	    
	    $infoBuyRequestOption = $_item->getOptionByCode("info_buyRequest");
	    $infoOrigionalPrice	  = $_item->getOptionByCode("partialpayment_origional_price");
	    
	    if ($infoBuyRequestOption) {
		$infoBuyRequest   = unserialize($infoBuyRequestOption->getValue());

		if (isset($infoBuyRequest['custom_options']) && is_array($infoBuyRequest['custom_options']) && isset($infoBuyRequest['custom_options']['partialpayment']) && isset($infoBuyRequest['custom_options']['installments']) && isset($infoBuyRequest['custom_options']['price'])) {
		    
		    unset($infoBuyRequest['custom_options']['partialpayment']);
		    unset($infoBuyRequest['custom_options']['installments']);
		    unset($infoBuyRequest['custom_options']['price']);
		    unset($infoBuyRequest['custom_options']['price_type']);

		    $_item->addOption(
			array(
			    "code"	 => "info_buyRequest", 
			    "value"	 => serialize($infoBuyRequest), 
			    "product_id" => $_item->getProductId()
			)
		    );
		}
	    }
	    
	    $origional	   = $_item->getProduct()->getFinalPrice();
	    
	    if ($infoOrigionalPrice) {
		$origional = $infoOrigionalPrice->getValue();
	    }
	    
	    $_item->getProduct()->setSpecialPrice($origional);
	    $_item->setPrice($origional);
	    $_item->setBasePrice($origional);
	    $_item->setCustomPrice($origional);
	    $_item->setOriginalCustomPrice($origional);
	    $_item->getProduct()->setIsSuperMode(true);
	}
	
	$quote->setData("md_partialpayment_full_cart", "0");
	$quote->setData("md_partialpayment_price_type", NULL);
	$quote->setData("md_partialpayment_price", NULL);
	$quote->setData("md_partialpayment_installments_count", NULL);
	$quote->getShippingAddress()->setCollectShippingRates(true);
	$quote->collectTotals()->save();
	
	Mage::getSingleton("checkout/session")->addSuccess("Partial payment option has been removed from cart.");
	$this->_redirect("checkout/cart/");
    }

    public function changeInstallmentAction() {
	$post	       = Mage::app()->getRequest()->getParams();
	$enteredAmount = (float)$post['newamount'];
	$summaryid     = $post['summaryid'];
	$paymentId     = $post['paymnetid'];

	if(!empty($summaryid) && !empty($paymentId)) {
	    $currentSummaryModel = Mage::getModel('md_partialpayment/summary')->load($summaryid);
	    $orgAmount		 = $currentSummaryModel->getData('amount');
	    $currentPaymnetModel = Mage::getModel('md_partialpayment/payments')->load($paymentId);
	    $remainingAmount     = $unpaidRemainingAmount = 0;
	    $dueamount		 = $post['dueamount'];

	    if($orgAmount > $enteredAmount) {
		$org = "greater";
	    } else {
		$org = "lower";
	    }

	    if ($enteredAmount <= $dueamount) {
		$summaryCollection = Mage::getModel('md_partialpayment/summary')->getCollection()
				     ->addFieldToFilter('payment_id', $paymentId)
				     ->addFieldToFilter('summary_id', array('gt' => $summaryid))
				     ->addFieldToFilter('status', array(0, 2))
				     ->addFieldToSelect('amount')
				     ->addFieldToSelect('summary_id')
				     ->getData();

		foreach ($summaryCollection as $summaryDatas) {
		    $summaryData[$summaryDatas['summary_id']] = $summaryDatas['amount'];

		    $remainingAmount += $summaryDatas['amount'];
		}

		$summaryUnpaidCollection = Mage::getModel('md_partialpayment/summary')->getCollection()
					   ->addFieldToFilter('payment_id', $paymentId)
					   ->addFieldToFilter('summary_id', array('lt' => $summaryid))
					   ->addFieldToFilter('status', array(0, 2))
					   ->addFieldToSelect('amount')
					   ->addFieldToSelect('summary_id')
					   ->getData();

		foreach ($summaryUnpaidCollection as $unpaidSummarDatas) {
		    $unpaidSummarData[$unpaidSummarDatas['summary_id']] = $unpaidSummarDatas['amount'];

		    $unpaidRemainingAmount += $unpaidSummarDatas['amount'];
		}

		$summaryProcessCollection = Mage::getModel('md_partialpayment/summary')->getCollection()
					    ->addFieldToFilter('payment_id', $paymentId)
					    ->addFieldToFilter('status', array(3, 4))
					    ->addFieldToSelect('amount')
					    ->addFieldToSelect('summary_id')
					    ->getData();

		foreach ($summaryProcessCollection as $ProcessSummarDatas) {
		    $ProcessSummarData[$ProcessSummarDatas['summary_id']] = $ProcessSummarDatas['amount'];

		    $ProcessAmount += $ProcessSummarDatas['amount'];
		}
		$ProcessAmount	       = isset($ProcessAmount)	       ? $ProcessAmount : "";
		$unpaidRemainingAmount = isset($unpaidRemainingAmount) ? $unpaidRemainingAmount : "";
		$unpaidRemainingAmount = $unpaidRemainingAmount + $ProcessAmount;
		$allowAmount	       = (float) $dueamount	- $unpaidRemainingAmount;

		if ($enteredAmount <= $allowAmount) {
		    $summaryData	   = isset($summaryData)     ? $summaryData	: "";
		    $remainingAmount       = isset($remainingAmount) ? $remainingAmount : "";
		    $remainingSummarycount = count($summaryData);

		    if ($org == "greater") {
			$difference	   = $orgAmount       - $enteredAmount;
			$remainingAmount   = $remainingAmount + $difference;
		    }
		    if ($org == "lower") {
			$difference	   = $enteredAmount - $orgAmount;
			$remainingTemp     = $remainingAmount;
			$remainingAmount   = $remainingAmount - $difference;
		    }
		    $newSummaryamtSingle   = round($remainingAmount / $remainingSummarycount, 2);
		    $remainingSummaryIds   = array_keys($summaryData);

		    $totalRemaining	   = count($remainingSummaryIds) * $newSummaryamtSingle;
		    $currentTotal	   = $totalRemaining + ($enteredAmount + $unpaidRemainingAmount);

		    if ($dueamount != $currentTotal) {
			if ($dueamount < $currentTotal) {
			    $round	    = "more";
			    $remainingRound = round($currentTotal - $dueamount, 2);
			} else {
			    $round	    = "less";
			    $remainingRound = round($dueamount - $currentTotal, 2);
			}
		    }

		    if ($remainingAmount == 0 || $remainingAmount < 0)  {
			
			for ($p = 0; $p < $remainingSummarycount; $p++) {
			    Mage::getModel('md_partialpayment/summary')->load($remainingSummaryIds[$p])->delete();
			}

			//reset due installments and then save
			$currentPaymentModel = Mage::getModel('md_partialpayment/payments')->load($paymentId)
					       ->setDueInstallments(count(array_keys($unpaidSummarData)) + 1) 
					       ->save();

			$response['dueinstallments'] = count(array_keys($unpaidSummarData)) + 1;
		    } else {
			if (empty($summaryData) && !empty($remainingAmount)) {
			    $frequencyMap   = array(
				"weekly"    => ' +7 days',
				"quarterly" => ' +3 months',
				"monthly"   => ' +1 month'
			    );

			    $frequency   = Mage::getStoreConfig("md_partialpayment/general/frequency_of_payments");
			    $current     = date('Y-m-d', strtotime($currentSummaryModel->getData('due_date')));
			    $nextDuedate = date('Y-m-d', strtotime($current . $frequencyMap[$frequency]));

			    $nextSummaryData['amount']     = $remainingAmount;
			    $nextSummaryData['payment_id'] = $paymentId;
			    $nextSummaryData['due_date']   = $nextDuedate;

			    $nextSummaryModel    = Mage::getModel('md_partialpayment/summary')->setData($nextSummaryData);
			    $currentPaymentModel = Mage::getModel('md_partialpayment/payments')->load($paymentId);
			    $nextDueinstallments = $currentPaymentModel->getData('due_installments') + 1;

			    // increase 1 due installments
			    $currentPaymentModel->setDueInstallments($nextDueinstallments); 

			    $transactionSave = Mage::getModel('core/resource_transaction');
			    $transactionSave->addObject($nextSummaryModel);
			    $transactionSave->addObject($currentPaymentModel);
			    $transactionSave->save();

			    $response['dueinstallments'] = $nextDueinstallments;
			} else {
			    for ($p = 0; $p < $remainingSummarycount; $p++) {
				
				if ($p == ($remainingSummarycount - 1))	    {
				    if ($round == "more") {
					$newSummaryamtSingle = $newSummaryamtSingle - $remainingRound;
				    }

				    if ($round == "less") {
					$newSummaryamtSingle = $newSummaryamtSingle + $remainingRound;
				    }
				}

				Mage::getModel('md_partialpayment/summary')->load($remainingSummaryIds[$p])
				->setAmount($newSummaryamtSingle)
				->save();
			    }
			}
		    }

		    $currentSummaryModel->setAmount($enteredAmount)->save();

		    Mage::getSingleton('core/session')->addSuccess($this->__("Installment amount has been successfully updated."));

		    $returnUrl = Mage::getUrl('md_partialpayment/summary/view/payment_id/', array('payment_id' => $paymentId));  

		    $this->getResponse()->setRedirect($returnUrl);
		    return;

		} else {
		    $message = "You cannot enter an amount greater than" . $allowAmount;
		    Mage::getSingleton('core/session')->addError($this->__($message));
		    $returnUrl = Mage::getUrl('md_partialpayment/summary/view/payment_id/', array('payment_id' => $paymentId));  
		    $this->getResponse()->setRedirect($returnUrl);

		    return;
		}

	    } else {
		Mage::getSingleton('core/session')->addError($this->__('You cannot enter an amount greater than Total Due Amount.'));
		$returnUrl = Mage::getUrl('md_partialpayment/summary/list', array('payment_id' => $paymentId));  

		$this->getResponse()->setRedirect($returnUrl);

		return;
	    }

	} else {
	    Mage::getSingleton('core/session')->addError($this->__('Partial Payment Summary not found.'));

	    if(empty($paymentId)) {
		$returnUrl = Mage::getUrl('md_partialpayment/summary/list', array('payment_id' => $paymentId));
	    } else {
		$returnUrl = Mage::getUrl('md_partialpayment/summary/view', array('payment_id' => $paymentId));  
	    }

	    $this->getResponse()->setRedirect($returnUrl);

	    return;
	}
    }
}
