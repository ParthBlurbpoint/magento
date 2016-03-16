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
class MD_Partialpayment_Helper_Data extends Mage_Core_Helper_Abstract {

    const PAYMENT_MONTHLY		  = 'monthly';
    const PAYMENT_WEEKLY		  = 'weekly';
    const PAYMENT_QUARTERLY		  = 'quarterly';
    const PARTIAL_ALLOWED_PAYMENT_METHODS = 'global/md_partial_validator/allowed/payment/methods';
    const PARTIAL_MINIMUM_CART_TOTAL	  = 'md_partialpayment/general/minimum_cart_total';
    const PARTIAL_MINIMUM_CART_TOTAL_TYPE = 'md_partialpayment/general/minimum_total_type';

    //public function getInstallmentSummary(Mage_Sales_Model_Order_Item $item)
    public function getInstallmentSummary(Mage_Sales_Model_Order $order, $count = 0) {
	$data	      = array();
	$frequencyMap = array(
	    self::PAYMENT_WEEKLY    => ' +7 days',
	    self::PAYMENT_QUARTERLY => ' +3 months',
	    self::PAYMENT_MONTHLY   => ' +1 month'
	);
	if ($order instanceof Mage_Sales_Model_Order && $count > 0) {
	    $current	    = date('Y-m-d', strtotime($order->getCreatedAt()));
	    $frequency	    = Mage::getStoreConfig("md_partialpayment/general/frequency_of_payments");
	    $payment	    = $order->getPayment();
	    $amount	    = 0;
	    
	    if(!Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment')) {
		$amount = $order->getTaxAmount() + $order->getShippingAmount();
	    }
	    
	    $transactionId  = $payment->getLastTransId();
	    
	    if(isset($transactionId)) {
		$message = $order->getStatusHistoryCollection()->getFirstItem();
		
		if(!empty($message)) {
		    $message = $message->getComment();		    
		} 
	    }
	    
	    $data[0]	= array(
		'amount'	      => $amount,		
		'paid_date'	      => $current,
		'transaction_id'      => $transactionId,
		'payment_method'      => $payment->getMethod(),
		'transaction_details' => !empty($message) ? serialize(array($message)) : ''
	    );
	    
	    if($payment->getMethodInstance()->isGateway() && !in_array($payment->getMethod(), array("sagepaydirectpro", "sagepayserver"))) {
		$data[0]['status'] = MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS;
	    } else {
		$data[0]['status'] = MD_Partialpayment_Model_Summary::PAYMENT_PROCESS;
	    }
		 
	    $current = date('Y-m-d', strtotime($current . $frequencyMap[$frequency]));
	    for ($c  = 1; $c < $count; $c++) {
		$data[$c]      = array(
		    'amount'   => 0,
		    'due_date' => $current,
		);
		
		$current = date('Y-m-d', strtotime($current . $frequencyMap[$frequency]));
	    }
	    
	    foreach ($order->getAllVisibleItems() as $_item) {
		if ($_item->getPartialpaymentOptionSelected() == 1) {
		    
		    $data[0]['amount'] += (float) $_item->getPartialpaymentPaidAmount() * $_item->getQtyOrdered();
		    $itemCount		= (int) $_item->getPartialpaymentInstallmentCount();
		    
		    for ($i = 1; $i < $itemCount; $i++) {
			$data[$i]['amount'] += (float) $_item->getPartialpaymentAmountDueAfterDate() * $_item->getQtyOrdered();
		    }
		} else {
		    $data[0]['amount'] += (float)($_item->getRowTotal() - $_item->getDiscountAmount());
		}
	    }
	}
	
	return $data;
    }

    public function isQuotePartialPayment(Mage_Sales_Model_Quote $quote) {
	$isPartial = false;
	
	if ($quote instanceof Mage_Sales_Model_Quote) {
	    foreach ($quote->getAllVisibleItems() as $item) {
		if ($item->getPartialpaymentOptionSelected()) {
		    $isPartial = true;
		    break;
		}
	    }
	}
	
	return $isPartial;
    }

    public function isAllowedMethod($code) {
	$node = Mage::getConfig()->getNode(self::PARTIAL_ALLOWED_PAYMENT_METHODS);
	if (!$node) {
	    $methods = array();
	} else {
	    $methods = array_keys((array) $node);
	}

	if (in_array($code, $methods)) {
	    return true;
	}
	return false;
    }

    public function sendReminderEmail($summaryIds) {
	if (!is_array($summaryIds)) {
	    $summaryIds = array($summaryIds);
	}

	foreach ($summaryIds as $summaryId) {
	    $summary	   = Mage::getModel('md_partialpayment/summary')->load($summaryId);
	    $payments	   = $summary->getPayments();
	    $order	   = $payments->getOrder();
	    $itemNameArray = array();
	    
	    foreach ($order->getAllVisibleItems() as $_item) {
		if ($_item->getPartialpaymentOptionSelected() == 1) {
		    $itemNameArray[] = $_item->getName();
		}
	    }
	    
	    $orderItem = $order->getItemById($payments->getOrderItemId());
	    $translate = Mage::getSingleton('core/translate');
	    
	    $translate->setTranslateInline(false);
	    
	    $mailTemplate = Mage::getModel('core/email_template');
	    $template	  = (!Mage::getStoreConfig('md_partialpayment/email/installment_reminder', $order->getStoreId())) ? 'md_partialpayment_email_installment_reminder' : Mage::getStoreConfig('md_partialpayment/email/installment_reminder', $order->getStoreId());
	    $bccConfig	  = Mage::getStoreConfig('md_partialpayment/email/installment_reminder_copy_to', $order->getStoreId());
	    $bcc	  = ($bccConfig) ? explode(",", $bccConfig) : array();
	    $sendTo	  = array(
		array(
		    'email' => $payments->getCustomerEmail(),
		    'name'  => $payments->getCustomerName()
		)
	    );

	    foreach ($sendTo as $recipient) {
		if (count($bcc) > 0) {
		    foreach ($bcc as $copyTo) {
			$mailTemplate->addBcc($copyTo);
		    }
		}
		$mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStoreId()))
		    ->sendTransactional(
			$template, 
			Mage::getStoreConfig('md_partialpayment/email/installment_reminder_from', 
			$order->getStoreId()), 
			$recipient['email'], 
			$recipient['name'], 
			array(
			    'paidAmount'       => $order->formatPrice($payments->getPaidAmount()),
			    'dueAmount'	       => $order->formatPrice($payments->getDueAmount()),
			    'installmetAmount' => $order->formatPrice($summary->getAmount()),
			    'productName'      => implode("<br />", $itemNameArray),
			    'billingAddress'   => $order->getBillingAddress(),
			    'shippingAddress'  => $order->getShippingAddress(),
			    'customerName'     => $payments->getCustomerName(),
			    'customerEmail'    => $payments->getCustomerEmail(),
			    'dueDate'	       => Mage::helper('core')->formatDate($summary->getDueDate(), 'medium'),
			    'orderDate'	       => Mage::helper('core')->formatDate($order->getCreatedAt(), 'medium'),
			    'orderId'	       => $order->getIncrementId()
			)
		);
	    }
	}
	return $this;
    }

    public function sendPaymentScheduleEmail(MD_Partialpayment_Model_Payments $payments) {
	if ($payments instanceof MD_Partialpayment_Model_Payments) {
	    $order	   = $payments->getOrder();
	    $itemNameArray = array();
	    
	    foreach ($order->getAllVisibleItems() as $_item) {
		if ($_item->getPartialpaymentOptionSelected() == 1) {
		    $itemNameArray[] = $_item->getName();
		}
	    }
	    
	    $translate = Mage::getSingleton('core/translate');
	    
	    $translate->setTranslateInline(false);
	    
	    $mailTemplate = Mage::getModel('core/email_template');
	    $template	  = (!Mage::getStoreConfig('md_partialpayment/email/installment_schedule', $order->getStoreId())) ? 'md_partialpayment_email_installment_schedule' : Mage::getStoreConfig('md_partialpayment/email/installment_schedule', $order->getStoreId());
	    $bccConfig	  = Mage::getStoreConfig('md_partialpayment/email/installment_schedule_copy_to', $order->getStoreId());
	    $bcc	  = ($bccConfig) ? explode(",", $bccConfig) : array();
	    $sendTo	  = array(
		array(
		    'email' => $payments->getCustomerEmail(),
		    'name'  => $payments->getCustomerName()
		)
	    );
	    
	    foreach ($sendTo as $recipient) {
		if (count($bcc) > 0) {
		    foreach ($bcc as $copyTo) {
			$mailTemplate->addBcc($copyTo);
		    }
		}
		$mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStoreId()))
		    ->sendTransactional(
			$template, 
			Mage::getStoreConfig('md_partialpayment/email/installment_schedule_from', 
			$order->getStoreId()), 
			$recipient['email'], 
			$recipient['name'], 
			array(
			    'paidAmount'      => $order->formatPrice($payments->getPaidAmount()),
			    'dueAmount'	      => $order->formatPrice($payments->getDueAmount()),
			    'productName'     => implode("<br />", $itemNameArray),
			    'billingAddress'  => $order->getBillingAddress(),
			    'shippingAddress' => $order->getShippingAddress(),
			    'customerName'    => $payments->getCustomerName(),
			    'customerEmail'   => $payments->getCustomerEmail(),
			    'orderId'	      => $order->getIncrementId(),
			    'payments'	      => $payments
			)
		);
	    }
	}
    }

    public function isTermsEnabled() {
	return !is_null(Mage::getStoreConfig('md_partialpayment/general/terms'));
    }

    public function getTermsContents() {
	$blockId = Mage::getStoreConfig('md_partialpayment/general/terms');
	$data	 = array();
	
	if ($blockId) {
	    $block		= Mage::getModel('cms/block')->load($blockId);
	    $cmsProcessor	= Mage::helper('cms')->getBlockTemplateProcessor();
	    $data['link_title'] = $this->__('View Terms and Conditions');
	    $data['content']	= $cmsProcessor->filter($block->getContent());
	}
	
	return $data;
    }

    public function isAllowGroups($customerGroupId = null) {
	if(empty($customerGroupId)) {
	    $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
	}
	$config		 = null;
	$isAllowed	 = false;
	$value		 = array();
	
	if (strlen(Mage::getStoreConfig("md_partialpayment/general/customer_groups"))) {
	    $config = (string) Mage::getStoreConfig("md_partialpayment/general/customer_groups");
	    $value  = explode(",", $config);
	}

	if (strlen($config) <= 0) {
	    $isAllowed = true;
	} elseif (count($value) < 0) {
	    $isAllowed = true;
	} elseif (in_array($customerGroupId, $value)) {
	    $isAllowed = true;
	}

	return $isAllowed;
    }

    public function isEnabledOnFrontend() {
	return (boolean) Mage::getStoreConfig('md_partialpayment/general/enabled');
    }

    public function getConfigInstallmentOptions() {
	$isGroupEnabled  = $this->isAllowGroups();
	$isConfigEnabled = $this->isEnabledOnFrontend();
	$config		 = array();
	
	if ($isConfigEnabled && $isGroupEnabled) {
	    $options = unserialize(Mage::getStoreConfig('md_partialpayment/general/total_installments'));

	    if (count($options['price_type']) > 0) {
		foreach ($options['price_type'] as $i => $value) {
		    //Phase 3 - Changes: Allow installment unit 1
		    //if ($options['unit'][$i] > 1) {
		    array_push($config, array('price_type' => (int) $value, 'price' => $options['price'][$i], 'unit' => (int) $options['unit'][$i]));
		    //}
		}
	    }
	}
	return $config;
    }

    public function getCardsByMethods($methodCode, $store = 0) {
	$cards = Mage::getStoreConfig('payment/' . $methodCode . '/cctypes', $store);
	return explode(",", $cards);
    }

    public function getFrequencyLabel() {
	$configFrequency = Mage::getStoreConfig('md_partialpayment/general/frequency_of_payments');
	$label		 = '';
	
	switch ($configFrequency) {
	    case MD_Partialpayment_Model_Options::FREQUENCY_WEEKLY:
		$label = $this->__('Weekly');
		break;
	    case MD_Partialpayment_Model_Options::FREQUENCY_MONTHLY:
		$label = $this->__('Monthly');
		break;
	    case MD_Partialpayment_Model_Options::FREQUENCY_QUARTERLY:
		$label = $this->__('Quarterly');
		break;
	}
	return $label;
    }

    public function isAutoCaptureEnabled() {
	return (boolean) Mage::getStoreConfig('md_partialpayment/general/allow_autocapture');
    }

    public function loadCustomerCards($paymentsId = null, $methodCode = null) {
	$this->_tokenCards = new Varien_Object();
	
	if (!$this->_tokenCards->getSize() && !is_null($paymentsId)) {
	    $payments	= Mage::getModel("md_partialpayment/payments")->load($paymentsId);
	    $customerId = $payments->getCustomerId();
	    
	    if ($customerId === 0) {
		return $this->_tokenCards;
	    }
	    
	    $this->_tokenCards = Mage::getModel('sagepaysuite2/sagepaysuite_tokencard')->getCollection()
		 ->setOrder('id', 'DESC')
		 ->addFieldToFilter("customer_id", $customerId);
	    
	    $this->_tokenCards->load();
	}
	return $this->_tokenCards;
    }

    public function getIsFullCartPartialPaymentEnabled() {
	return (int) Mage::getStoreConfig("md_partialpayment/general/enable_full_cart");
    }

    public function checkCartTotalEligibility($quote = null) {
	$minCartTotal = (double) Mage::getStoreConfig($this::PARTIAL_MINIMUM_CART_TOTAL);
	if (empty($quote)) {
	    $quote = Mage::helper('checkout/cart')->getQuote();
	}

	$minimumCartTotalType = Mage::getStoreConfig($this::PARTIAL_MINIMUM_CART_TOTAL_TYPE);
	if (empty($minimumCartTotalType) || $minimumCartTotalType == 'subtotal') {
	    $grandTotal = $quote->getSubtotalWithDiscount();
	} else {
	    $grandTotal = $quote->getGrandTotal();
	}


	if ($grandTotal < $minCartTotal) {
	    return false;
	} else {
	    return true;
	}
    }

    public function getPlanProductsId($productId) {
	$planProductId = Mage::getModel('md_partialpayment/rule')->getCollection()
			->addFieldToFilter('product_ids', array('finset' => $productId))
			->addFieldToFilter('rule_status', array('eq' => 1))
			->addFieldToSelect('rule_id')
			->setOrder('priority', 'ASC')
			->getFirstItem();

	if (!empty($planProductId)) {
	    $planProductId = $planProductId->getData('rule_id');
	    return $planProductId;
	}

	return;
    }
}
