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
class MD_Partialpayment_Model_Quote_Address_Total_Installment_Paid extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    public function collect(Mage_Sales_Model_Quote_Address $address) {
	parent::collect($address);
	$addressType	     = $address->getAddressType();
	$totalPaidAmount     = 0;
	$baseTotalPaidAmount = 0;
	$items		     = $address->getAllItems();
	$isPartialExists     = false;
	
	$address->setPartialpaymentPaidAmount(0);
	
	foreach ($items as $item) {
	    if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
		$quoteItem = $item->getAddress()->getQuote()->getItemById($item->getQuoteItemId());
	    } else {
		$quoteItem = $item;
	    }
	    
	    if (!$quoteItem->getParentItem()) {
		if ($quoteItem->getPartialpaymentOptionSelected() == 1) {
		    $isPartialExists	  = true;
		    $totalPaidAmount	 += $quoteItem->getPartialpaymentPaidAmount() * $quoteItem->getQty();
		    $baseTotalPaidAmount += $quoteItem->getPartialpaymentPaidAmount() * $quoteItem->getQty();
		} else {
		    $totalPaidAmount	 += ($quoteItem->getRowTotal() - $quoteItem->getDiscountAmount());
		    $baseTotalPaidAmount += ($quoteItem->getBaseRowTotal() - $quoteItem->getBaseDiscountAmount());
		}
	    }
	}
	
	if ($isPartialExists) {	    
	    if(Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment')) {
		$address->setPartialpaymentPaidAmount($totalPaidAmount);	
	    } else {
		$address->setPartialpaymentPaidAmount($totalPaidAmount + $address->getShippingAmount() + $address->getTaxAmount());	
	    }
	    
	} else {
	    $address->setPartialpaymentPaidAmount(0);
	}

	return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
	$amount	        = $address->getPartialpaymentPaidAmount();
	$addressType    = $address->getAddressType();
	$controllerName = Mage::app()->getRequest()->getControllerName();
	$route	        = Mage::app()->getRequest()->getRouteName();

	if (($controllerName=='sales_order_create' && $route == 'adminhtml') || ($controllerName=='adminhtml_summary' && $route='md_partialpayment')) {
	    Mage::getSingleton('adminhtml/sales_order_create')->setRecollect(true)->getQuote()->collectTotals()->save();
	}
	if ($amount != 0) {
	    $address->addTotal(array(
		'code'  => 'md_partialpayment_paid',
		'title' => Mage::helper('md_partialpayment')->__('Amount Paying Now'),
		'value' => $amount,
		'area'  => 'footer',
	    ));
	}
	return $this;
    }

}
