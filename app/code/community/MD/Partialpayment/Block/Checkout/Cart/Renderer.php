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
class MD_Partialpayment_Block_Checkout_Cart_Renderer extends Mage_Core_Block_Template {

    protected $_item = null;

    public function setPartialQuoteItem($item = null) {
	if (!is_null($item)) {
	    $this->_item = $item;
	}
	return $this;
    }

    public function getPartialQuoteItem() {
	return $this->_item;
    }

    protected function _toHtml() {
	$item	    = $this->getPartialQuoteItem();
	$coreHelper = Mage::helper("core");
	$html	    = '';
	
	if (!is_null($item)) {
	    $isPartialPaymentApplied = (boolean) $item->getPartialpaymentOptionSelected();
	    
	    if ($isPartialPaymentApplied) {
		if($item instanceof Mage_Sales_Model_Quote_Item) {
		    $quote	       = $item->getQuote();
		    $qty	       = $item->getQty();
		    $options	       = $item->getOptionByCode("partialpayment_origional_price")->getValue();
		    $originalPrice     = $options;
		    $initialPaymentAmt = (double)$item->getData('partialpayment_option_intial_amount');
		} else if($item instanceof Mage_Sales_Model_Order_Item) {
		    $order	       = $item->getOrderId();
		    $order	       = Mage::getModel('sales/order')->load($order);
		    $quote	       = $order->getQuoteId();
		    $store	       = Mage::getSingleton('core/store')->load(1);
		    $quote	       = Mage::getModel('sales/quote')->setStore($store)->load($quote);
		    $qty	       = $item->getQtyOrdered();
		    $options	       = $item->getProductOptions();
		    $originalPrice     = isset($options['partialpayment_origional_price']) ? $options['partialpayment_origional_price'] : 0;
		    $quoteItem	       = $item->getQuoteItemId();
		    $quoteItem	       = Mage::getModel('sales/quote_item')->load($quoteItem);
		    $initialPaymentAmt = (double)$quoteItem->getData('partialpayment_option_intial_amount');
		} else {
		    return;
		}
		
		$isFullSelected   = (boolean) $quote->getMdPartialpaymentFullCart();
		$product	  = $item->getProduct();
		$selectedPPOption = $item->getData('partialpayment_installment_count');
		
		if($initialPaymentAmt > 0) {
		    $selectedPPOption -= 1;
		}
		
		if (!$isFullSelected) {
		    $partialPaymentOptions = Mage::getModel('md_partialpayment/options')->getStoreOptions($product);
		} else {
		    $partialPaymentOptions = new MD_Partialpayment_Model_Options();
		    
		    $partialPaymentOptions->addData(array(
			"initial_payment_amount"    => null,
			"additional_payment_amount" => null,
			"product_id"		    => $product->getId()
		    ));
		}
		
		if ($partialPaymentOptions) {
		    $installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($product, $partialPaymentOptions, $qty, $item->getPrice(), $item->getPartialpaymentPrice(), $item->getPartialpaymentPriceType(), $selectedPPOption, '', $item);
		    
		    if (count($installmentSummary) > 0) {
			if($isPartialPaymentApplied==1) {
			    $additionalFees = $item->getData('partialpayment_price');
			    $rowTotal	    = ($additionalFees * $qty) + ($item->getRowTotal()-$item->getDiscountAmount());
			    
			    $rowTotal     -= ($additionalFees * $qty);
			    
			    if($initialPaymentAmt > 0) {
				$unpaidRowTotal = $rowTotal - ($item->getData('partialpayment_paid_amount') * $qty);
			    } else {
				$unpaidRowTotal = $rowTotal;
			    }
			    
			    if($unpaidRowTotal > 0 && $selectedPPOption > 0) {
				$perInstallmentAmt = $unpaidRowTotal/$selectedPPOption;
			    } else {
				$perInstallmentAmt = 0;
			    }
			    
			    $rowTotal	       = Mage::helper('core')->currency($rowTotal,true,false);
			    $perInstallmentAmt = Mage::helper('core')->currency($perInstallmentAmt,true,false);
			}
			
			$html .= '<dl style="margin:0; padding:0;">';
			$html .= '<dt><strong><em>' . $this->__("Original Price") . '</em></strong></dt>';
			$html .= '<dd>' . $coreHelper->currency($originalPrice, true, false) . '</dd>';
			$html .= '<dt><strong><em>' . $this->__("Installment Fees") . '</em></strong></dt>';
			$html .= '<dd>' . $coreHelper->currency($installmentSummary['partial_surcharge'], true, false) . '</dd>';
			/* $html .= '<dt><strong><em>' . $this->__("Additional Amount") . '</em></strong></dt>';
			$html .= '<dd>' . $coreHelper->currency($installmentSummary['additional_payment_amount'], true, false) . '</dd>'; */
			$html .= '<dt><strong><em>' . $this->__("Initial Amount") . '</em></strong></dt>';
			$html .= '<dd>' . $coreHelper->currency($installmentSummary['initial_payment_amount'], true, false) . '</dd>';
			$html .= '<dt><strong><em>' . $this->__("Total Installments (%s)", Mage::helper("md_partialpayment")->getFrequencyLabel()) . '</em></strong></dt>';
			$html .= '<dd>' . $selectedPPOption . '</dd>';
			$html .= '</dl>';
			$html .= '<dt><strong><em>' . $this->__("Per Installment Amount", Mage::helper("md_partialpayment")->getFrequencyLabel()) . '</em></strong></dt>';
			$html .= '<dd>' . $perInstallmentAmt . '</dd>';
			$html .= '</dl>';
		    }
		}
	    }
	}
	
	return $html;
    }

}
