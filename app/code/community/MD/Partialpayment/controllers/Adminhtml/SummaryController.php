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
class MD_Partialpayment_Adminhtml_SummaryController extends Mage_Adminhtml_Controller_Action {

    protected $_publicActions   = array('pay', 'paypalCancel', 'paypalSuccess', 'paypalRedirect', 'view');
    protected $_redirectMethods = array(
	Mage_Paypal_Model_Config::METHOD_WPS
    );
    
    protected $_creditCardRequiredMethod = array(
	Mage_Paygate_Model_Authorizenet::METHOD_CODE
    );
    
    protected $_processMethod = array(
	Mage_Paygate_Model_Authorizenet::METHOD_CODE => 'md_partialpayment/payment_authorizenet',
	'ccsave'		  => 'md_partialpayment/payment_ccsave',
	'checkmo'		  => 'md_partialpayment/payment_checkmo',
	'cashondelivery'	  => 'md_partialpayment/payment_cashondelivery',
	'authorizenet_directpost' => 'md_partialpayment/payment_authorizenet_directpost',
	'md_authorizecim'	  => 'md_partialpayment/payment_authorizenetcim',
	'md_stripe_cards'	  => 'md_partialpayment/payment_stripe',
	'md_cybersource'	  => 'md_partialpayment/payment_cybersource',
    );
    
    protected $_redirectAction = array(
	Mage_Paypal_Model_Config::METHOD_WPS => '*/*/paypalRedirect'
    );
    
    protected $_adminActionMethod = array(
	'ccsave', 'checkmo', 'cashondelivery'
    );
    
    protected $_sagepayDetails = array (
	'sagepaydirectpro' => array (
	    "action"	   => "directRedirect", 
	    "controller"   => "adminhtml_sagepay", 
	    "module"	   => "md_partialpayment"
	),
	'sagepayserver'    => array (
	    "action"	   => "serverRedirect", 
	    "controller"   => "adminhtml_sagepay", 
	    "module"	   => "md_partialpayment"
	)
    );

    protected function _isAllowed() {
	return Mage::getSingleton('admin/session')->isAllowed('md_partialpayment/installment_summary');  
    }
    
    public function paypalRedirectAction() {
	$summaryId = $this->getRequest()->getParam('summary_id');
	$session   = Mage::getSingleton('adminhtml/session');
	
	$session->setPartialSummaryId($summaryId);

	$this->getResponse()->setBody($this->getLayout()->createBlock('md_partialpayment/paypal_standard_redirect')->setPaymentRequestArea('adminhtml')->toHtml());
    }

    public function indexAction() {
	$this->loadLayout();
	$this->_setActiveMenu('md_partialpayment');
	$this->getLayout()->getBlock('head')->setTitle(Mage::helper('md_partialpayment')->__('Installment Summary'));
	$this->renderLayout();
    }

    public function viewAction() {
	$this->loadLayout();
	$this->_setActiveMenu('md_partialpayment');
	$this->getLayout()->getBlock('head')->setTitle(Mage::helper('md_partialpayment')->__('Installment Summary Details'));
	$this->renderLayout();
    }

    public function paypalCancelAction() {
	$params = $this->getRequest()->getParams();
	Mage::getSingleton('adminhtml/session_quote')->addError($this->__('Error Occured during payment.'));
	$this->_redirect('*/*/view', array('id' => $params['payment_id']));
    }

    public function paypalSuccessAction() {
	$params = $this->getRequest()->getParams();
	Mage::getSingleton('adminhtml/session_quote')->addSuccess($this->__('Payment has been submited'));
	$this->_redirect('*/*/view', array('id' => $params['payment_id']));
    }

    public function confirmPaymentAction() {
	$summaryId = $this->getRequest()->getParam('summary_id', null);
	$paymentId = $this->getRequest()->getParam('payment_id', null);
	
	if ($summaryId) {
	    $summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);
	    
	    $summary->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS);
	    
	    $amount   = $summary->getAmount();
	    $payments = $summary->getPayments();
	    
	    $payments->setPaidAmount($payments->getPaidAmount() + $amount)
		    ->setDueAmount($payments->getDueAmount() - $amount)
		    ->setLastInstallmentDate($summary->getPaidDate())
		    ->setPaidInstallments($payments->getPaidInstallments() + 1)
		    ->setDueInstallments($payments->getDueInstallments() - 1)
		    ->setUpdatedAt(date('Y-m-d H:i:s'));


	    $order = $payments->getOrder();
	    
	    if ($payments->getDueInstallments() > 0) {
		$orderDueAmount	    = max(0, ($order->getTotalDue() - $amount));
		$baseOrderDueAmount = max(0, ($order->getBaseTotalDue() - $amount));
	    } else {
		$orderDueAmount	    = 0;
		$baseOrderDueAmount = 0;
	    }
	    
	    $order->setTotalPaid($order->getTotalPaid() + $amount)
		  ->setBaseTotalPaid($order->getBaseTotalPaid() + $amount)
		  ->setTotalDue($orderDueAmount)
		  ->setBaseTotalDue($baseOrderDueAmount);
	    
	    $transaction = Mage::getModel('core/resource_transaction');
	    $transaction->addObject($summary);
	    $transaction->addObject($payments);
	    $transaction->addObject($order);
	    
	    try {
		$transaction->save();
		Mage::getSingleton('adminhtml/session_quote')->addSuccess(Mage::helper('md_partialpayment')->__('Payment Confirmed Successfully.'));
	    } catch (Exception $e) {
		Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
	    }
	}
	$this->_redirect('*/*/view', array('id' => $paymentId));
    }

    public function rejectPaymentAction() {
	$summaryId = $this->getRequest()->getParam('summary_id', null);
	$paymentId = $this->getRequest()->getParam('payment_id', null);
	
	if ($summaryId) {
	    try {
		$summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);
		$summary->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_FAIL);
		$summary->save();
		
		Mage::getSingleton('adminhtml/session_quote')->addSuccess(Mage::helper('md_partialpayment')->__('Payment Status changed successfully.'));
		
	    } catch (Exception $e) {
		Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
	    }
	}
	$this->_redirect('*/*/view', array('id' => $paymentId));
    }

    public function _initReportAction($blocks) {
	if (!is_array($blocks)) {
	    $blocks = array($blocks);
	}

	$requestData = Mage::helper('adminhtml')->prepareFilterString($this->getRequest()->getParam('filter'));
	$requestData = $this->_filterDates($requestData, array('from', 'to'));
	$params	     = new Varien_Object();

	foreach ($requestData as $key => $value) {
	    if (!empty($value)) {
		$params->setData($key, $value);
	    }
	}

	foreach ($blocks as $block) {
	    if ($block) {
		$block->setPeriodType($params->getData('period_type'));
		$block->setFilterData($params);
	    }
	}
	return $this;
    }

    public function reportAction() {
	$this->loadLayout();
	$gridBlock	 = $this->getLayout()->getBlock('adminhtml_report.grid');
	$filterFormBlock = $this->getLayout()->getBlock('grid.filter.form');

	$this->_initReportAction(array(
	    $gridBlock,
	    $filterFormBlock
	));
	
	$this->renderLayout();
    }

    public function sendEmailAction() {
	$params  = $this->getRequest()->getParams();
	$helper  = Mage::helper('md_partialpayment');
	$payment = null;
	$summary = null;
	
	if (isset($params['summary_id'])) {
	    $summary = Mage::getModel('md_partialpayment/summary')->load($params['summary_id']);
	}
	
	if (isset($params['payment_id'])) {
	    $payment = Mage::getModel('md_partialpayment/payments')->load($params['payment_id']);
	}

	switch ($params['action']) {
	    case 'reminder':
		try {
		    $helper->sendReminderEmail($params['summary_id']);
		    Mage::getSingleton('adminhtml/session_quote')->addSuccess($helper->__('Reminder Email has been sent.'));
		} catch (Exception $e) {
		    Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
		}
		break;
	    case 'failed':
		try {
		    $summary->sendStatusPaymentEmail(true, false, 'failed');
		    Mage::getSingleton('adminhtml/session_quote')->addSuccess($helper->__('Installment Status Email has been sent.'));
		} catch (Exception $e) {
		    Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
		}
		break;

	    case'success':
		try {
		    $summary->sendStatusPaymentEmail(true, false);
		    Mage::getSingleton('adminhtml/session_quote')->addSuccess($helper->__('Installment Status Email has been sent.'));
		} catch (Exception $e) {
		    Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
		}
		break;
	    case 'schedule':
		try {
		    $helper->sendPaymentScheduleEmail($payment);
		    Mage::getSingleton('adminhtml/session_quote')->addSuccess($helper->__('Schedule Email has been sent.'));
		} catch (Exception $e) {
		    Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
		}
		break;
	}
	
	$this->_redirect('*/*/view', array('id' => $params['payment_id']));
    }

    public function massDeleteAction() {
	$helper = Mage::helper('md_partialpayment');
	$ids    = $this->getRequest()->getParam("partialpayment");
	
	if (!is_array($ids)) {
	    Mage::getSingleton("adminhtml/session_quote")->addError($helper->__("Please select any item to delete."));
	} else {
	    try {
		foreach ($ids as $id) {
		    $model = Mage::getModel("md_partialpayment/payments")->load($id);
		    $model->delete();
		}
		
		Mage::getSingleton("adminhtml/session_quote")->addSuccess($helper->__("Total %d item(s) are deleted successfully", count($ids)));
		
	    } catch (Exception $e) {
		Mage::getSingleton("adminhtml/session_quote")->addError($e->getMessage());
	    }
	}
	$this->_redirect("*/*/index");
    }

    public function payAction() {
	$params	   = $this->getRequest()->getParams();
	$summaryId = (array_key_exists('payment_summary', $params)) ? $params['payment_summary'] : null;
	
	if (is_null($summaryId)) {
	    Mage::getSingleton("adminhtml/session_quote")->addError(Mage::helper("md_partialpayment")->__("Installment summary not found."));
	    
	    $this->_redirect('*/*/view', array('id' => $params['payment_id']));
	    return $this;
	}
	
	$summary  = Mage::getModel('md_partialpayment/summary')->load($summaryId);
	$allowPay = true;
	
	if ($summaryId) {
	    $summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);
	    if ($summary) {
		if (in_array($summary->getStatus(), array(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS, MD_Partialpayment_Model_Summary::PAYMENT_PROCESS))) {
		    $allowPay = false;
		}
	    }
	}
	
	if ($allowPay) {
	    $requestArea    = 'adminhtml';
	    $method	    = $params['partial']['method'];
	    
	    if(isset($params[$method])) {
		$info	    = $params[$method];
	    } else {
		$info	    = array();
	    }
	    
	    $info['method'] = $method;
	    $payments	    = $summary->getPayments();
	    $order	    = $payments->getOrder();

	    if (in_array($method, $this->_redirectMethods)) {
		$this->_redirect($this->_redirectAction[$method], array('_secure' => true, 'summary_id' => $summaryId, 'request_area' => $requestArea));
		
	    } elseif (array_key_exists($method, $this->_sagepayDetails)) {
		$this->_forward($this->_sagepayDetails[$method]["action"], $this->_sagepayDetails[$method]["controller"], $this->_sagepayDetails[$method]["module"]);
		
	    } else {
		try {
		    Mage::getModel($this->_processMethod[$method])
			->setSummary($summary)
			->setPayments($payments)
			->setOrder($order)
			->setPaymentRequestArea('adminhtml')
			->pay($info);
		    
		} catch (Exception $e) {
		    Mage::getSingleton('adminhtml/session_quote')->addError($e->getMessage());
		}
		$this->_redirect('*/*/view', array('id' => $summary->getPaymentId()));
	    }
	} else {
	    Mage::getSingleton("adminhtml/session_quote")->addError(Mage::helper('md_partialpayment')->__('This installment has already paid.'));
	    $this->_redirect('*/*/view', array('id' => $params['payment_id']));
	}
    }

    public function cartpaymentAction() {
	$quote		   = Mage::getSingleton('adminhtml/session_quote')->getQuote();
	$params		   = $this->getRequest()->getParam('partialparams');
	$optionObject	   = new MD_Partialpayment_Model_Options();
	$isEligibleForPP   = Mage::helper('md_partialpayment')->checkCartTotalEligibility($quote);
	$configInitialType = Mage::getStoreConfig("md_partialpayment/general/initial_payment_type", $quote->getStoreId());
	$configPrice	   = (double)Mage::getStoreConfig("md_partialpayment/general/initial_payment_amount", $quote->getStoreId());
	if (!empty($params)) {
	    $params = json_decode(stripslashes($params), true);
	}

	$optionObject->addData(array(
	    "initial_payment_amount"	=> null,
	    "additional_payment_amount" => null,
	    "product_id"		=> null
	));

	$frequencyMap = array(
	    'weekly'    => ' +7 days',
	    'quarterly' => ' +3 months',
	    'monthly'	=> ' +1 month'
	);

	if (!$isEligibleForPP) {
	    $minCartTotal         = Mage::getStoreConfig(MD_Partialpayment_Helper_Data::PARTIAL_MINIMUM_CART_TOTAL);
	    $minimumCartTotalType = Mage::getStoreConfig(MD_Partialpayment_Helper_Data::PARTIAL_MINIMUM_CART_TOTAL_TYPE);

	    if (empty($minimumCartTotalType) || $minimumCartTotalType == 'subtotal') {
		Mage::getSingleton("adminhtml/session_quote")->addError($this->__('Minimum Cart Subtotal after discount(if any) should be %s',$minCartTotal));
	    } else {
		Mage::getSingleton("adminhtml/session_quote")->addError($this->__('Minimum Cart Grand Total after discount(if any) should be %s',$minCartTotal));
	    }
	} else {
	    if (is_array($params['partialpayment']) && count($params['partialpayment']) > 0) {
		$totalQty = 0;
		
		foreach ($quote->getAllVisibleItems() as $_item) {
		    $qty       = 1;
		    $totalQty += $_item->getQty();
		    
		    $optionObject->addData(array("product_id" => $_item->getProductId()));

		    $customPrice       = $_item->getCustomPrice();
		    if(!empty($customPrice) && $isFullSelected != 1) {
			$productPrice = $customPrice;
		    } else {
			$catalogPriceRulePrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($_item->getProduct(),$_item->getProduct()->getPrice());
			if(!empty($catalogPriceRulePrice)) {
			    $productPrice = $catalogPriceRulePrice;
			} else {
			    $productPrice = $_item->getProduct()->getFinalPrice();			    
			}
		    }	
		    
		    $installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($_item->getProduct(), $optionObject, $qty, $productPrice, $params['partialpayment']['price'], $params['partialpayment']['price_type'], $params['partialpayment']['installments'],true, $_item);

		    if (count($installmentSummary) > 0) {
			$frequency	 = Mage::getStoreConfig('md_partialpayment/general/frequency_of_payments');
			$createdAt	 = date('Y-m-d', strtotime($_item->getCreatedAt()));
			$nextPaymentDate = date('Y-m-d', strtotime($createdAt . $frequencyMap[$frequency]));
			if($installmentSummary['remaining_amount'] > 0) {
			    $_item->setData('partialpayment_option_selected', '1');
			} else {
			    $_item->setData('partialpayment_option_selected', null);
			}
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

		$disposition = 1;

		if ($configInitialType == MD_Partialpayment_Model_Options::PAYMENT_FIXED) {
		    $disposition = $totalQty;
		}

		$totalInstallments = $params['partialpayment']['installments'];
		//$subtotal	   = $installmentSummary['total_payment_amount'] * $disposition;
		$subtotal	   = $quote->getSubtotalWithDiscount();
		
		if($configPrice > 0) {
		    $subtotalWithoutInitialPrice = $subtotal -  ($configPrice * $disposition);
		} else {
		    $subtotalWithoutInitialPrice = $subtotal;
		}
		
		$perInstallmentAmt = $subtotalWithoutInitialPrice / $totalInstallments;
		$labelText	   = $totalInstallments . " installments of " 
				    . Mage::helper('core')->currency($perInstallmentAmt, true, false)
				    . " amount at total price " 
				    . Mage::helper('core')->currency($subtotal, true, false);
		
		echo $labelText;
	    } else {
		echo false;
	    }
	}
    }

    public function productPartialplanAction() {
	$quote	        = Mage::getSingleton('adminhtml/session_quote')->getQuote();
	$customer       = Mage::getSingleton('customer/session');
	$selectedPlan   = $this->getRequest()->getParam('partialparams');
	$hasFullCartPP  = $quote->getData("md_partialpayment_full_cart");
	
	if($hasFullCartPP != "1") {
	    $this->removeCartOptionAction();
	}
	
	
	if (!empty($selectedPlan)) {
	    $selectedPlan = json_decode(stripslashes($selectedPlan), true);
	}

	foreach ($quote->getAllVisibleItems() as $quoteItem) {
	    $itemId = $quoteItem->getId();
	    $product = $quoteItem->getProduct();

	    if (!empty($selectedPlan[$itemId])) {
		if (!$quoteItem->getParentItemId() && Mage::helper("md_partialpayment")->isAllowGroups()) {
		    $isFullSelected = (boolean) $quote->getMdPartialpaymentFullCart();
		    $origionalBuyRequest = $quoteItem->getBuyRequest()->getData();

		    if ((!$isFullSelected)) {
			$buyRequest = array(
			    "product"		 => $product->getId(),
			    'custom_options'	 => array(
				"partialpayment" => 1,
				"installments"   => $selectedPlan[$itemId]['partialpayment']['installments'],
				"price"		 => $selectedPlan[$itemId]['partialpayment']['price'],
				"price_type"	 => $selectedPlan[$itemId]['partialpayment']['price_type'],
				"product"	 => $product->getId(),
			    )
			);
		    } else {
			$buyRequest = array(
			    "product"		 => $origionalBuyRequest['product'],
			    "custom_options"	 => array(
				"partialpayment" => 1,
				"price"		 => $quote->getMdPartialpaymentPrice(),
				"price_type"	 => $quote->getMdPartialpaymentPriceType(),
				"installments"   => $quote->getMdPartialpaymentInstallmentsCount()
			    )
			);
		    }

		    $frequencyMap   = array(
			'weekly'    => ' +7 days',
			'quarterly' => ' +3 months',
			'monthly'   => ' +1 month'
		    );

		    if (isset($buyRequest['custom_options']['partialpayment']) && $buyRequest['custom_options']['partialpayment'] == 1) {
			$qty = 1;

			if (!$isFullSelected) {
			    $partialPaymentOptions = Mage::getModel('md_partialpayment/options')->getStoreOptions($product);
			} else {
			    $partialPaymentOptions = new MD_Partialpayment_Model_Options();
			    $partialPaymentOptions->addData(array(
				"initial_payment_amount"    => null,
				"additional_payment_amount" => null,
				"product_id"		    => $buyRequest['product']
			    ));
			}

			if ($partialPaymentOptions) {
			    $frequency		= Mage::getStoreConfig('md_partialpayment/general/frequency_of_payments');
			    $createdAt		= date('Y-m-d', strtotime($quoteItem->getCreatedAt()));
			    $nextPaymentDate	= date('Y-m-d', strtotime($createdAt . $frequencyMap[$frequency]));
			    $price		= $quoteItem->getCustomPrice();
			    $itemDiscount       = $quoteItem->getDiscountAmount();
			    
			    if(empty($price)) {
				$catalogPriceRulePrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice());
				if(!empty($catalogPriceRulePrice)) {
				    $price = $catalogPriceRulePrice;
				} else {
				    $price = $quoteItem->getPrice();
				}
			    }
			    
			    $installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($product, $partialPaymentOptions, $qty, $price, $buyRequest['custom_options']['price'], $buyRequest['custom_options']['price_type'], $buyRequest['custom_options']['installments'],true,$quoteItem);


			    if (count($installmentSummary) > 0) {
				if($installmentSummary['remaining_amount'] > 0) {
				    $quoteItem->setData('partialpayment_option_selected', '1');
				} else {
				    $quoteItem->setData('partialpayment_option_selected', null);
				}
				$quoteItem->setData('partialpayment_installment_count', $installmentSummary['installment_count']);
				$quoteItem->setData('partialpayment_paid_amount', $installmentSummary['initial_payment_amount']);
				$quoteItem->setData('partialpayment_due_amount', $installmentSummary['remaining_amount']);
				$quoteItem->setData('partialpayment_frequency', $frequency);
				$quoteItem->setData('partialpayment_amount_due_after_date', $installmentSummary['installment_amount']);
				$quoteItem->setData('partialpayment_option_intial_amount', $installmentSummary['option_initial_amount']);
				$quoteItem->setData('partialpayment_next_installment_date', $nextPaymentDate);
				$quoteItem->setData('partialpayment_price_type', $buyRequest['custom_options']['price_type']);
				$quoteItem->setData('partialpayment_price', $buyRequest['custom_options']['price']);
				$quoteItem->addOption(
				    array(
					"code"	     => "partialpayment_origional_price", 
					"value"	     => $product->getFinalPrice(), 
					"product_id" => $buyRequest['product']
				    )
				);
				$quoteItem->getProduct()->setSpecialPrice($installmentSummary['unit_payment']);
				$quoteItem->setPrice($installmentSummary['unit_payment']);
				$quoteItem->setBasePrice($installmentSummary['unit_payment']);
				$quoteItem->setCustomPrice($installmentSummary['unit_payment']);
				$quoteItem->setOriginalCustomPrice($installmentSummary['unit_payment']);
				$quoteItem->getProduct()->setIsSuperMode(true);
				$quoteItem->calcRowTotal();
				$quoteItem->save();
				$quote->collectTotals()->save();
				echo true;
			    }
			}
		    }
		}
	    }
	}
    }

    public function summaryChangeAction() {
	try {
	    $paymentId	     = Mage::app()->getRequest()->getParam('id');
	    $enteredAmount   = (float) Mage::app()->getRequest()->getParam('newamount');
	    $orgAmount	     = (float) Mage::app()->getRequest()->getParam('orgamt');
	    $summaryid	     = Mage::app()->getRequest()->getParam('summaryid');
	    $remainingAmount = 0;
	    $orgAmount > $enteredAmount ? $org = "greater" : $org = "lower";
	    
	    $dueamount	   = (float) Mage::app()->getRequest()->getParam('dueamount');
	    
	    if (is_numeric($enteredAmount)) {
		if ($enteredAmount <= $dueamount) {
		    if (!empty($paymentId) && !empty($summaryid)) {
			$currentSummaryModel = Mage::getModel('md_partialpayment/summary')->load($summaryid);
			$summaryCollection   = Mage::getModel('md_partialpayment/summary')->getCollection()
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
				#  ->addFieldToFilter('summary_id',array('lt' => $summaryid))
				->addFieldToFilter('status', array(3, 4, 5))
				->addFieldToSelect('amount')
				->addFieldToSelect('summary_id')
				->getData()
			;
			foreach ($summaryProcessCollection as $ProcessSummarDatas) {
			    $ProcessSummarData[$ProcessSummarDatas['summary_id']] = $ProcessSummarDatas['amount'];
			    $ProcessAmount+=$ProcessSummarDatas['amount'];
			}
			#if($unpaidRemainingAmount<=)
			$ProcessAmount = isset($ProcessAmount) ? $ProcessAmount : "";
			$unpaidRemainingAmount = isset($unpaidRemainingAmount) ? $unpaidRemainingAmount : "";
			$unpaidRemainingAmount = $unpaidRemainingAmount + $ProcessAmount;
			$allowAmount = (float) $dueamount - $unpaidRemainingAmount;
			if ($enteredAmount <= $allowAmount) {
			    $summaryData = isset($summaryData) ? $summaryData : "";
			    $remainingAmount = isset($remainingAmount) ? $remainingAmount : "";
			    $remainingSummarycount = count($summaryData);
			    if ($org == "greater") {
				$difference = $orgAmount - $enteredAmount;
				$remainingAmount = $remainingAmount + $difference;
			    }
			    if ($org == "lower") {
				$difference = $enteredAmount - $orgAmount;
				$remainingTemp = $remainingAmount;
				$remainingAmount = $remainingAmount - $difference;
			    }
			    $newSummaryamtSingle = round($remainingAmount / $remainingSummarycount, 2);
			    $remainingSummaryIds = array_keys($summaryData);
			    /* round issue */
			    $totalRemaining = count($remainingSummaryIds) * $newSummaryamtSingle;
			    $currentTotal = $totalRemaining + ($enteredAmount + $unpaidRemainingAmount);
			    if ($dueamount != $currentTotal) {
				if ($dueamount < $currentTotal) {
				    $round = "more";
				    $remainingRound = round($currentTotal - $dueamount, 2);
				} else {
				    $round = "less";
				    $remainingRound = round($dueamount - $currentTotal, 2);
				}
			    }
			    if ($remainingAmount == 0 || $remainingAmount < 0) {
				for ($p = 0; $p < $remainingSummarycount; $p++) {
				    Mage::getModel('md_partialpayment/summary')->load($remainingSummaryIds[$p])
					    ->delete();
				}
				$currentPaymentModel = Mage::getModel('md_partialpayment/payments')->load($paymentId)
					->setDueInstallments(count(array_keys($unpaidSummarData)) + 1) //reset due installments
					->save();

				$response['dueinstallments'] = count(array_keys($unpaidSummarData)) + 1;
			    } else {
				if (empty($summaryData) && !empty($remainingAmount)) {
				    $frequencyMap = array(
					"weekly" => ' +7 days',
					"quarterly" => ' +3 months',
					"monthly" => ' +1 month'
				    );
				    $frequency = Mage::getStoreConfig("md_partialpayment/general/frequency_of_payments");
				    $current	 = date('Y-m-d', strtotime($currentSummaryModel->getData('due_date')));
				    $nextDuedate = date('Y-m-d', strtotime($current . $frequencyMap[$frequency]));
				    $nextSummaryData['amount']	   = $remainingAmount;
				    $nextSummaryData['payment_id'] = $paymentId;
				    $nextSummaryData['due_date']   = $nextDuedate;
				    $nextSummaryModel = Mage::getModel('md_partialpayment/summary')->setData($nextSummaryData);
				    $currentPaymentModel = Mage::getModel('md_partialpayment/payments')->load($paymentId);
				    $nextDueinstallments = $currentPaymentModel->getData('due_installments') + 1;
				    $currentPaymentModel->setDueInstallments($nextDueinstallments); // increase 1 due installments                             
				    $transactionSave = Mage::getModel('core/resource_transaction');
				    $transactionSave->addObject($nextSummaryModel);
				    $transactionSave->addObject($currentPaymentModel);
				    $transactionSave->save();
				    $response['dueinstallments'] = $nextDueinstallments;
				} else {
				    for ($p = 0; $p < $remainingSummarycount; $p++) {
					if ($p == ($remainingSummarycount - 1)) {
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
			    $currentSummaryModel
				    ->setAmount($enteredAmount)
				    ->save();
			    $tabledata = $this->getLayout()->createBlock('md_partialpayment/adminhtml_summary_view')->setTemplate('md/partialpayment/summary/paymentsummarytable.phtml')->toHtml();
			    $response['result'] = "true";
			    $response['data'] = $tabledata;
			    $responsedata = json_encode($response);
			    $this->getResponse()
				    ->clearHeaders()
				    ->setHeader('Content-Type', 'application/json')
				    ->setBody($responsedata);
			    return;
			} else {
			    $response['result'] = "false";
			    $response['data'] = "You can not enter amount greater then $allowAmount";
			    $responsedata = json_encode($response);
			    $this->getResponse()
				    ->clearHeaders()
				    ->setHeader('Content-Type', 'application/json')
				    ->setBody($responsedata);
			    return;
			}
		    } else {
			$response['result'] = "false";
			$response['data'] = "Unable to find payment id";
			$responsedata = json_encode($response);
			$this->getResponse()
				->clearHeaders()
				->setHeader('Content-Type', 'application/json')
				->setBody($responsedata);
			return;
		    }
		} else {
		    $response['result'] = "false";
		    $response['data'] = "You can not enter amunt greater then Total Due Amount";
		    $responsedata = json_encode($response);
		    $this->getResponse()
			    ->clearHeaders()
			    ->setHeader('Content-Type', 'application/json')
			    ->setBody($responsedata);
		    return;
		}
	    } else {
		$response['result'] = "false";
		$response['data'] = "Please enter valid amount";
		$responsedata = json_encode($response);
		$this->getResponse()
			->clearHeaders()
			->setHeader('Content-Type', 'application/json')
			->setBody($responsedata);
		return;
	    }
	} catch (Exception $e) {
	    $response['result'] = "false";
	    $response['data'] = "Unable to save data";
	    $responsedata = json_encode($response);
	    $this->getResponse()
		    ->clearHeaders()
		    ->setHeader('Content-Type', 'application/json')
		    ->setBody($responsedata);
	    return;
	}
    }

    public function removeCartOptionAction() {
	$quote		   = Mage::getSingleton('adminhtml/session_quote')->getQuote();
	$fullCartPPApplied = $quote->getData("md_partialpayment_full_cart");
	$resetPP	   = false;
	
	foreach ($quote->getAllVisibleItems() as $_item) {
	    $perProductPPApplied  = $_item->getData('partialpayment_option_selected');
	    
	    if($fullCartPPApplied == '1' || $perProductPPApplied == '1') { 
		$resetPP = true;
		
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
		if ($infoBuyRequestOption) {
		    $infoBuyRequest = unserialize($infoBuyRequestOption->getValue());

		    if (is_array($infoBuyRequest['custom_options']) && isset($infoBuyRequest['custom_options']['partialpayment']) && isset($infoBuyRequest['custom_options']['installments']) && isset($infoBuyRequest['custom_options']['price'])) {
			unset($infoBuyRequest['custom_options']['partialpayment']);
			unset($infoBuyRequest['custom_options']['installments']);
			unset($infoBuyRequest['custom_options']['price']);
			unset($infoBuyRequest['custom_options']['price_type']);

			$_item->addOption(
				array("code" => "info_buyRequest", "value" => serialize($infoBuyRequest), "product_id" => $_item->getProductId())
			);
		    }
		}

		$_item->getProduct()->setSpecialPrice($_item->getProduct()->getFinalPrice());
		$_item->setPrice($_item->getProduct()->getFinalPrice());
		$_item->setBasePrice($_item->getProduct()->getFinalPrice());
		$_item->setCustomPrice(null);
		$_item->setOriginalCustomPrice(null);
		$_item->getProduct()->setIsSuperMode(true);
	    }
	}
	
	if($resetPP == true) {
	    $quote->setData("md_partialpayment_full_cart", "0");
	    $quote->setData("md_partialpayment_price_type", NULL);
	    $quote->setData("md_partialpayment_price", NULL);
	    $quote->setData("md_partialpayment_installments_count", NULL);
	    $quote->getShippingAddress()->setCollectShippingRates(true);
	    $quote->collectTotals()->save();
	}
	//Mage::getSingleton('adminhtml/session_quote')->addSuccess("Partial payment option has been removed from cart.");
	echo true;
    }

}
