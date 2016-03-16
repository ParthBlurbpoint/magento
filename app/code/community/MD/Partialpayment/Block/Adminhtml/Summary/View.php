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
class MD_Partialpayment_Block_Adminhtml_Summary_View extends Mage_Adminhtml_Block_Widget {

    protected $_app = null;
    protected $_request = null;

    public function __construct() {
	parent::__construct();
	$this->setTemplate('md/partialpayment/summary/view.phtml');
	$this->_app = Mage::app();
	$this->_request = $this->_app->getRequest();
    }

    public function getPayment() {
	$id = $this->_request->getParam('id', null);
	$paymentOption = null;
	if ($id) {
	    $paymentOption = Mage::getModel('md_partialpayment/payments')->load($id);
	}
	return $paymentOption;
    }

    public function getTitle() {
	if ($_extOrderId = $this->getPayment()->getOrder()->getExtOrderId()) {
	    $_extOrderId = '[' . $_extOrderId . '] ';
	} else {
	    $_extOrderId = '';
	}
	return Mage::helper('sales')->__('Partial Payment - Order # %s %s | %s', $this->getPayment()->getOrder()->getRealOrderId(), $_extOrderId, $this->formatDate($this->getPayment()->getOrder()->getCreatedAtDate(), 'medium', true));
    }

    public function getCustomerViewUrl() {
	if ($this->getPayment()->getOrder()->getCustomerIsGuest() || !$this->getPayment()->getOrder()->getCustomerId()) {
	    return false;
	}
	return $this->getUrl('adminhtml/customer/edit', array('id' => $this->getPayment()->getOrder()->getCustomerId()));
    }
    
    public function getViewUrl($orderId)
    {
        return $this->getUrl('adminhtml/sales_order/view', array('order_id'=>$orderId));
    }

}
