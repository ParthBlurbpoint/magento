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
class MD_Partialpayment_Model_Summary extends Mage_Core_Model_Abstract {
    const PAYMENT_PENDING	 = 0;
    const PAYMENT_SUCCESS	 = 1;
    const PAYMENT_FAIL		 = 2;
    const PAYMENT_HOLD		 = 3;
    const PAYMENT_DECLINED	 = 4;
    const PAYMENT_PROCESS	 = 5;
    protected $_payments	 = null;
    protected $_methodToModelMap = array(
	Mage_Paypal_Model_Config::METHOD_WPS	     => 'md_partialpayment/payment_paypal_standard',
	Mage_Paygate_Model_Authorizenet::METHOD_CODE => 'md_partialpayment/payment_authorizenet',
	'authorizenet_directpost'		     => 'md_partialpayment/payment_authorizenet_directpost',
	'cashondelivery'			     => 'md_partialpayment/payment_cashondelivery',
	'ccsave'				     => 'md_partialpayment/payment_ccsave',
	'checkmo'				     => 'md_partialpayment/payment_checkmo',
    );

    public function _construct() {
	parent::_construct();
	$this->_init('md_partialpayment/summary');
    }

    public function getPayments() {
	if (is_null($this->_payments)) {
	    $this->_payments = Mage::getModel('md_partialpayment/payments')->load($this->getPaymentId());
	}
	return $this->_payments;
    }

    public function getTransactionData() {
	if ($this->getPaymentMethod()) {
	    $method = $this->getPaymentMethod();
	    $data = array();

	    if (array_key_exists($method, $this->_methodToModelMap)) {
		$data = Mage::getModel($this->_methodToModelMap[$method])
			->setSummary($this)
			->getDetails();
	    }

	    return $data;
	}
	return null;
    }

    public function sendStatusPaymentEmail($notifyCustomer = false, $notifyAdmin = true, $forcedMessage = null, $hasFullSelected = false) {
	$sendTo   = array();
	$payments = $this->getPayments();
	$order    = $payments->getOrder();
	$itemNameArray = array();
	foreach ($order->getAllVisibleItems() as $_item) {
	    if ($_item->getPartialpaymentOptionSelected() == 1) {
		$itemNameArray[] = $_item->getName();
	    }
	}
	$translate = Mage::getSingleton('core/translate');
	$translate->setTranslateInline(false);
	$mailTemplate = Mage::getModel('core/email_template');
	$template     = (!Mage::getStoreConfig('md_partialpayment/email/installment_status', $order->getStoreId())) ? 'md_partialpayment_email_installment_status' : Mage::getStoreConfig('md_partialpayment/email/installment_status', $order->getStoreId());
	$bccConfig    = Mage::getStoreConfig('md_partialpayment/email/installment_status_copy_to', $order->getStoreId());
	$bcc	      = ($bccConfig) ? explode(",", $bccConfig) : array();
	
	if ($notifyAdmin) {
	    $sendTo[] = array('email' => Mage::getStoreConfig('trans_email/ident_general/email'), 'name' => Mage::getStoreConfig('trans_email/ident_general/name'));
	}

	if ($notifyCustomer) {
	    $sendTo[] = array('email' => $this->getPayments()->getCustomerEmail(), 'name' => $this->getPayments()->getCustomerName());
	}
	$templateParams = array(
	    'installmetAmount' => $order->formatPrice($this->getAmount()),
	    'productName'      => implode("<br />", $itemNameArray),
	    'billingAddress'   => $order->getBillingAddress(),
	    'shippingAddress'  => $order->getShippingAddress(),
	    'customerName'     => $payments->getCustomerName(),
	    'customerEmail'    => $payments->getCustomerEmail(),
	    'orderId'	       => $order->getIncrementId(),
	    'paymentMethod'    => Mage::getStoreConfig('payment/' . $this->getPaymentMethod() . '/title', $order->getStoreId()),
	    'reasonString'     => $this->getResponseText(),
	    'paymentDetails'   => implode('<br />', $this->getTransactionData())
	);

	if ($forcedMessage) {
	    $templateParams['reasonString'] = $forcedMessage;
	}
	foreach ($sendTo as $recipient) {
	    if (count($bcc) > 0) {
		foreach ($bcc as $copyTo) {
		    $mailTemplate->addBcc($copyTo);
		}
	    }
	    $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStoreId()))
		    ->sendTransactional(
			    $template, Mage::getStoreConfig('md_partialpayment/email/installment_status_from', $order->getStoreId()), $recipient['email'], $recipient['name'], $templateParams
	    );
	}
    }

    public function getResponseText() {
	if ($this->getPaymentMethod()) {
	    $method = $this->getPaymentMethod();
	    $string = '';

	    if (array_key_exists($method, $this->_methodToModelMap)) {
		$string = Mage::getModel($this->_methodToModelMap[$method])
			->setSummary($this)
			->getResponseText();
	    }

	    return $string;
	}
	return null;
    }

}
