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
class MD_Partialpayment_Model_Quote_Address_Total_Installment_Due extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    public function collect(Mage_Sales_Model_Quote_Address $address) {
	$totalDueAmount  = $finalTotal = $tax = $additionalFee = $nonPPProductPrice = 0;
	$quote		 = $address->getQuote();
	$items		 = $address->getAllItems();
	$addressType	 = $address->getAddressType();
	$isPartialExists = false;
	$shipping	 = (double)$address->getShippingAmount();	
	$totalItems	 = (double)$quote->getItemsQty();	
	$perItemShipping = 0;
	
	$address->setPartialpaymentDueAmount(0);
	
	if($totalItems > 0 && $shipping > 0) {	   
	    $perItemShipping = $shipping / $totalItems;
	    $perItemShipping = round($perItemShipping,2);
	}
	
	foreach ($items as $item) {
	    if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
		$quoteItem = $item->getAddress()->getQuote()->getItemById($item->getQuoteItemId());
	    } else {
		$quoteItem = $item;
	    }
	    
	    $tax += $quoteItem->getTaxAmount();
	    if (!$quoteItem->getParentItem()) {
		if ($quoteItem->getPartialpaymentOptionSelected() == 1) {
		    $totalDueAmount	 += $quoteItem->getPartialpaymentDueAmount() * $quoteItem->getQty();
		    $finalTotal		 += $quoteItem->getData('partialpayment_paid_amount') * $quoteItem->getQty();
		    $additionalFeeType	  = $quoteItem->getData('partialpayment_price_type');
		    if($additionalFeeType == MD_Partialpayment_Model_Slabs::INSTALLMENT_SLAB_PRICE_PERCENTAGE) {
    			$perProPrice      = $quoteItem->getPrice();
			$percentAmt       = (double)$quoteItem->getData('partialpayment_price');
			$additionalFee   += (($perProPrice * $percentAmt)/100) * $quoteItem->getQty();			
		    } else {
			$additionalFee   += ($quoteItem->getData('partialpayment_price') * $quoteItem->getQty());
		    }
		    
		    $isPartialExists = true;
		    if(Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment') && $perItemShipping>0) {
			$remainingAmt		= $quoteItem->getData('partialpayment_amount_due_after_date');	
			$perInstallmentShipping = round(($perItemShipping / ($quoteItem->getData('partialpayment_installment_count') - 1)),2);
			$remainingAmt	       += $perInstallmentShipping;
			
			$quoteItem->setData('partialpayment_amount_due_after_date', $remainingAmt)->save();
		    }
		} else {
		    $nonPPProductPrice += $quoteItem->getPrice() * $quoteItem->getQty();
		}
	    } 
	}
	
	if($totalDueAmount < 0) {
	    $totalDueAmount = 0;
	}
	
	if ($isPartialExists) {
	    $controllerName = Mage::app()->getRequest()->getControllerName();
	    $route	    = Mage::app()->getRequest()->getRouteName();

	    if (($controllerName=='sales_order_create' && $route == 'adminhtml') || ($controllerName=='adminhtml_summary' && $route='md_partialpayment')) {
		$appliedRuleIds = $quote->getAppliedRuleIds();
		$discount	= $address->getDiscountAmount(); //Discount is negative value here
		$subTotal       = $totalDueAmount + $finalTotal + $nonPPProductPrice;
		
		if(!empty($appliedRuleIds)) {
		    $subTotal -= $discount;
		}
		
		$grandTotal	= $subTotal + $tax + $shipping + $discount; 
		
		if($subTotal < 0) {
		    $subTotal = 0;
		}
		
		if($grandTotal < 0) {
		    $grandTotal = 0;
		}
		$address->setSubtotal($subTotal);
		$address->setBaseSubtotal($subTotal);
		$address->setGrandTotal($grandTotal);
		$address->setBaseGrandTotal($grandTotal); 
		
		if(Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment')) {
		    $totalDueAmount += $tax;
		}
	    }
	    
	    if(Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment')) {
		 $totalDueAmount    += $shipping;
	    } 
	    
	    $address->setPartialpaymentDueAmount($totalDueAmount);
	} else {
	    $address->setPartialpaymentDueAmount(0);
	}

	return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
	$amount	     = $address->getPartialpaymentDueAmount();
	$addressType = $address->getAddressType();
	
	if ($amount != 0) {
	    $address->addTotal(array(
		'code'  => 'md_partialpayment_due',
		'title' => Mage::helper('md_partialpayment')->__('Amount To be Paid Later'),
		'value' => $amount,
		'area'  => 'footer',
	    ));
	}
	
	return $this;
    }
}
