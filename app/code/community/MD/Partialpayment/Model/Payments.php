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
class MD_Partialpayment_Model_Payments extends Mage_Core_Model_Abstract {

    protected $_order = null;
    protected $_orderItem = null;
    protected $_paymentSummary = null;

    public function _construct() {
	parent::_construct();
	$this->_init('md_partialpayment/payments');
    }

    protected function _beforeSave() {
	parent::_beforeSave();
	$this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
	if ($this->getDueInstallments() == 0) {
	    $this->setDueAmount(0);
	}
    }

    protected function _beforeDelete() {
	parent::_beforeDelete();
	foreach ($this->getPaymentSummaryCollection() as $summary) {
	    $summary->setId($summary->getId())->delete();
	}
    }

    public function getOrder() {
	if (is_null($this->_order)) {
	    $this->_order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
	}
	return $this->_order;
    }

    public function getOrderItem() {
	if (is_null($this->_orderItem)) {
	    $this->_orderItem = $this->getOrder()->getItemById($this->getOrderItemId());
	}
	return $this->_orderItem;
    }

    public function getPaymentSummaryCollection() {
	if (is_null($this->_paymentSummary)) {
	    $this->_paymentSummary = Mage::getModel('md_partialpayment/summary')->getCollection()
		    ->addFieldToFilter('payment_id', $this->getId());
	}
	return $this->_paymentSummary;
    }

    /* Deprecated due to partial payment was made for order based. */

    public function getPaymentsByOrderItem(Mage_Sales_Model_Order_Item $item) {
	if ($item instanceof Mage_Sales_Model_Order_Item) {
	    $payment = $this->getCollection()
		    ->addFieldToFilter('order_item_id', array('eq' => $item->getId()));

	    if ($payment->count()) {
		return $payment->getFirstItem();
	    } else {
		return null;
	    }
	}
	return null;
    }

    public function getPaymentsByOrder(Mage_Sales_Model_Order $order) {
	if ($order instanceof Mage_Sales_Model_Order) {
	    $payment = $this->getCollection()
		    ->addFieldToFilter('order_id', array('eq' => $order->getIncrementId()));
	    if ($payment->count()) {
		return $payment->getFirstItem();
	    } else {
		return null;
	    }
	}
	return null;
    }

    public function getLastPaidInstallmentId() {
	$activeSummary = Mage::getModel('md_partialpayment/summary')->getCollection()
		->addFieldToFilter('payment_id', $this->getId())
		->addFieldToFilter('status', array('eq' => MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS))
		->getLastItem();

	if ($activeSummary) {
	    return $activeSummary->getId();
	} else {
	    return null;
	}
    }

    public function getNextPaidInstallmentId() {
	$allIds = Mage::getModel('md_partialpayment/summary')->getCollection()
			->addFieldToFilter('payment_id', $this->getId())->getAllIds();
	$lastPaid = $this->getLastPaidInstallmentId();
	
	if(isset($allIds[0])) {
	    $nextPaidId = $allIds[0];
	} else {
	    $nextPaidId = null;
	}
	
	if ($lastPaid) {
	    $key = array_search($lastPaid, $allIds);
	    
	    if(isset($allIds[$key + 1])) {
		$nextPaidId = $allIds[$key + 1];
	    } else {
		$nextPaidId = null;
	    }
	}
	return $nextPaidId;
    }

    public function canAllowToDoPayments() {
	$can = false;
	$dueInstllments = (int) $this->getDueInstallments();
	if ($dueInstllments > 0) {
	    $can = true;
	}
	return $can;
    }

    public function getDueInstallmentCollections() {
	return $this->getPaymentSummaryCollection()->addFieldToFilter("status", array('nin' => array(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS, MD_Partialpayment_Model_Summary::PAYMENT_PROCESS)));
    }

    public function getFullPaymentSummary() {
	$duePaymentSummary = $this->getDueInstallmentCollections();
	$summary = array();
	if ($duePaymentSummary) {
	    $installmentCount = (int) $duePaymentSummary->count();
	    if ($installmentCount > 1) {
		$amount = 0;
		foreach ($duePaymentSummary as $_summary) {
		    $amount += round($_summary->getAmount(), 2);
		}
		$summary['installment_count'] = $installmentCount;
		$summary['installment_amount'] = $amount;
	    }
	}
	return $summary;
    }

    public function sendFullInstallmentEmail($transactionAmount = 0) {
	if ($this instanceof MD_Partialpayment_Model_Payments) {
	    $order = $this->getOrder();
	    $itemNameArray = array();
	    foreach ($order->getAllVisibleItems() as $_item) {
		if ($_item->getPartialpaymentOptionSelected() == 1) {
		    $itemNameArray[] = $_item->getName();
		}
	    }
	    $translate = Mage::getSingleton('core/translate');
	    $translate->setTranslateInline(false);
	    $mailTemplate = Mage::getModel('core/email_template');
	    $template = (!Mage::getStoreConfig('md_partialpayment/email/full_payment', $order->getStoreId())) ? 'md_partialpayment_email_full_payment' : Mage::getStoreConfig('md_partialpayment/email/full_payment', $order->getStoreId());
	    $bccConfig = Mage::getStoreConfig('md_partialpayment/email/full_payment_copy_to', $order->getStoreId());
	    $bcc = ($bccConfig) ? explode(",", $bccConfig) : array();
	    $sendTo = array(
		array(
		    'email' => $this->getCustomerEmail(),
		    'name' => $this->getCustomerName()
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
				$template, Mage::getStoreConfig('md_partialpayment/email/full_payment_from', $order->getStoreId()), $recipient['email'], $recipient['name'], array(
			    'paidAmount' => $order->formatPrice($transactionAmount),
			    'dueAmount' => $order->formatPrice($this->getDueAmount()),
			    'productName' => implode("<br />", $itemNameArray),
			    'billingAddress' => $order->getBillingAddress(),
			    'shippingAddress' => $order->getShippingAddress(),
			    'customerName' => $this->getCustomerName(),
			    'customerEmail' => $this->getCustomerEmail(),
			    'orderDate' => Mage::helper('core')->formatDate($order->getCreatedAt(), 'medium'),
			    'orderId' => $order->getIncrementId(),
			    'paidInstallments' => (int) $this->getPaidInstallments(),
			    'dueInstallments' => (int) $this->getDueInstallments(),
				)
		);
	    }
	}
	return $this;
    }

    public function getIsFullSelected() {
	return (boolean) $this->getFullPayment();
    }

}
