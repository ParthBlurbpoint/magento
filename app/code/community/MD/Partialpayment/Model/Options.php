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
class MD_Partialpayment_Model_Options extends Mage_Core_Model_Abstract {

    const INSTALLMENT_REPORTS	       = 'md_partialpayment_installments';
    const FREQUENCY_WEEKLY	       = 'weekly';
    const FREQUENCY_MONTHLY	       = 'monthly';
    const FREQUENCY_QUARTERLY	       = 'quarterly';
    const PAYMENT_FIXED		       = 'F';
    const PAYMENT_PERCENTAGE	       = 'P';
    const DISPLAY_OPTIONS_CART	       = 1;
    const DISPLAY_OPTIONS_PRODUCTS     = 0;
    const DISPLAY_OPTIONS_BOTH	       = 2;
    const DISPLAY_OPTIONS_PRODUCTS_ALL = 3;

    public function _construct() {
	parent::_construct();
	$this->_init('md_partialpayment/options');
    }

    public function getIdByInfo($productId, $storeId = 0) {
	$id = null;

	$existingOption = $this->getCollection()
		->addFieldToFilter('product_id', array('eq' => $productId))
		->addFieldToFilter('store_id', array('eq' => $storeId))
		->getFirstItem();
	
	if ($existingOption->getId()) {
	    $id = $existingOption->getId();
	}
	
	return $id;
    }

    public function getOptionByProduct(Mage_Catalog_Model_Product $product) {
	$object	   = null;
	$productId = (int) $product->getId();
	$storeId   = (int) $product->getStoreId();
	
	if (is_int($productId) && is_int($storeId)) {
	    $object = $this->getCollection()
		    ->addFieldToFilter('product_id', array('eq' => $productId))
		    ->addFieldToFilter('store_id', array('eq' => $storeId))
		    ->getFirstItem();
	}
	
	return $object;
    }

    public function getStoreOptions(Mage_Catalog_Model_Product $product) {
	$object		 = null;
	$productId	 = (int) $product->getId();
	$storeId	 = (int) $product->getStoreId();
	$data		 = $product->getData();
	$helper		 = Mage::helper('md_partialpayment');
	$isGroupEnabled  = $helper->isAllowGroups();
	$isConfigEnabled = $helper->isEnabledOnFrontend();
	$found		 = false;
	$isglobalConfig  = (boolean) (Mage::getStoreConfig("md_partialpayment/general/enable_full_cart", $storeId) == self::DISPLAY_OPTIONS_PRODUCTS_ALL);

	if (is_int($productId) && $isGroupEnabled && $isConfigEnabled) {
	    $productPartialPlanRuleId = Mage::helper('md_partialpayment')->getPlanProductsId($productId);
	    $storeOption	      = $this->getCollection()
					->addFieldToFilter('product_id', array('eq' => $productId))
					->addFieldToFilter('store_id', array('eq' => $storeId))
					->addFieldToFilter('status', array('eq' => 1));

	    if ($storeOption->count() > 0) {
		$found  = true;
		$object = $storeOption->getFirstItem();
	    } else {
		$defaultOption = $this->getCollection()
				->addFieldToFilter('product_id', array('eq' => $productId))
				->addFieldToFilter('store_id', array('eq' => 0))
				->addFieldToFilter('status', array('eq' => 1));

		if ($defaultOption->count() > 0) {
		    $found  = true;
		    $object = $defaultOption->getFirstItem();
		}
	    }

	    //partial plan rule application starts
	    //if(($isglobalConfig == true) || ($found == true && !empty($productPartialPlanRuleId))) {
	    if (($isglobalConfig == true) || (!empty($productPartialPlanRuleId))) {
		$partialPlanRule     = Mage::getModel('md_partialpayment/rule')->load($productPartialPlanRuleId);
		$installmentSettings = $partialPlanRule->getInstallmentSettings();

		if (!empty($installmentSettings)) {
		    $initialAmountType = $partialPlanRule->getData('initial_payment_amount_type');
		    
		    if ($initialAmountType == $this::PAYMENT_PERCENTAGE) {
			$intialPaymentAmount = ($product->getPrice() * $partialPlanRule->getData('initial_payment_amount')) / 100;
		    } else {
			$intialPaymentAmount = $partialPlanRule->getData('initial_payment_amount');
		    }

		    $installmentSettings = unserialize($installmentSettings);
		    $found		 = true;
		    $object		 = new MD_Partialpayment_Model_Options();
		    
		    $object->addData(array(
			"option_id"  => $productId,
			"product_id" => $productId,
			"store_id"   => $storeId,
			"status"     => 1,
			"initial_payment_amount"  => $intialPaymentAmount,
			"use_config_installments" => 0
		    ));
		    
		    $object->setId($productId);
		}
	    }
	    //partial plan rule application ends 

	    if (!$found && $isglobalConfig) {
		$object = new MD_Partialpayment_Model_Options();
		$object->addData(array(
		    "option_id"  => $productId,
		    "product_id" => $productId,
		    "store_id"	 => $storeId,
		    "status"	 => 1,
		    "initial_payment_amount"  => null,
		    "use_config_installments" => 1
		));
		
		$object->setId($productId);
	    }
	}
	return $object;
    }
    

    public function getInstallmentSummary(Mage_Catalog_Model_Product $product, MD_Partialpayment_Model_Options $option, $qty = 1, $price = null, $installmentConfigPrice = 0, $installmentConfigPriceType = null, $productInstallmentCount = 0, $asOptions = false, $item = null) {
	$options = array();

	if (($product instanceof Mage_Catalog_Model_Product && $option instanceof MD_Partialpayment_Model_Options) && ($product->getId() == $option->getProductId()))    {
	    if (!$asOptions || is_null($price)) {
		$originalProduct       = Mage::getModel('catalog/product')->load($product->getId());
		$price		       = $basePrice = round($originalProduct->getData('special_price'),2); 
		$catalogPriceRulePrice = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice());
		
		if(!empty($catalogPriceRulePrice)) {
		    $price = $catalogPriceRulePrice;
		} 
		
		if(empty($price)) {
		    $price     = round($product->getFinalPrice(), 2);
		    $basePrice = round($product->getFinalPrice(), 2);
		}
	    }

	    if ($installmentConfigPriceType == MD_Partialpayment_Model_Slabs::INSTALLMENT_SLAB_PRICE_FIXED) {
		$price += $installmentConfigPrice;
		$options['partial_surcharge'] = $installmentConfigPrice;
	    } else {
		$toAddPrice = ($price * $installmentConfigPrice) / 100;
		$options['partial_surcharge'] = $toAddPrice;
		$price += $toAddPrice;
	    }
	    
	    $quoteItemDiscount		   = 0;
	    $additionalPaymentAmount	   = 0;
	    $options['initial_price']	   = $price;
	    $configInitialAmountType	   = Mage::getStoreConfig('md_partialpayment/general/initial_payment_type');
	    $configInitialAmount	   = Mage::getStoreConfig('md_partialpayment/general/initial_payment_amount');
	    $calculatedConfigInitialAmount = ($configInitialAmountType == 'F') ? $configInitialAmount : (($configInitialAmount * $basePrice) / 100);

	    $productInitialPaymentAmount   = $option->getInitialPaymentAmount();
	    $initialPaymentAmount	   = (!is_null($productInitialPaymentAmount)) ? $productInitialPaymentAmount : $calculatedConfigInitialAmount;

	    if (($options['initial_price'] + $additionalPaymentAmount) > $initialPaymentAmount) {
		$options['initial_payment_amount']    = round($initialPaymentAmount * $qty, 2);
		$installmentCount		      = $productInstallmentCount;
		$options['installment_count']	      = ($initialPaymentAmount > 0) ? $productInstallmentCount + 1 : $productInstallmentCount;

		$options['additional_payment_amount'] = $additionalPaymentAmount;
		$totalPaymentAmount		      = (float) $price + (float) $additionalPaymentAmount;
		$options['unit_payment']	      = (float) $totalPaymentAmount;
		$options['total_payment_amount']      = round($totalPaymentAmount, 2);
		
		if($item) {
		    $quoteItemDiscount      = (double)$item->getDiscountAmount();
		    
		    if($quoteItemDiscount > 0 ) {
			$quoteItemDiscount = (double) ($item->getDiscountAmount()/$item->getQtyOrdered());
			$quoteItemDiscount = round($quoteItemDiscount,2);
		    }
		} 
		
		$remainingAmount	     = $totalPaymentAmount - (float) $initialPaymentAmount - $quoteItemDiscount;
		
		if($remainingAmount < 0) {
		    $remainingAmount = 0;
		}
		
		$options['remaining_amount'] = round($remainingAmount * $qty, 2);
		
		if(Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment') && (!empty($item))) {
		    $tax		 = round(((double)$item->getTaxAmount()) / $item->getQtyOrdered(),2);
		    $remainingAmount	+= $tax;
		    
		    $options['remaining_amount'] = $remainingAmount;
		} 		
		
		if($remainingAmount <= 0) {		    
		    $options['installment_count'] -= $productInstallmentCount;
		    
		    if($options['installment_count'] < 0) {
			$options['installment_count'] = 0;
		    }
		} 
		
		if($remainingAmount > 0 && $installmentCount > 0) {
		    $parts = (float) $remainingAmount / (float) $installmentCount;
		} else {
		    $parts = 0;
		}
		
		$options['installment_amount']	  = round($parts * $qty, 2);
		$options['option_initial_amount'] = $options['initial_payment_amount'] * $qty;

		if ($initialPaymentAmount <= 0) {
		    $options['initial_payment_amount'] = $parts * $qty;
		    $options['remaining_amount']      -= ($parts * $qty);
		}
	    } else {
		$options = array();
	    }
	}
	return $options;
    }

    public function isActive() {
	return (boolean) $this->getStatus();
    }

    public function getInstallmentSlabs($storeId = null) {
	$productId   = $this->getProductId();
	if (is_null($storeId)) {
	    $storeId = 0;
	}
	$product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);
	//Phase 3 - Changes: Allow installment unit 1
	return Mage::getModel('md_partialpayment/slabs')->getSlabsByProduct($product);
	//return Mage::getModel('md_partialpayment/slabs')->getSlabsByProduct($product)->addFieldToFilter('unit', array('gt' => 1));
    }

    public function getUseConfigForInstallmentOptions() {
	return (boolean) $this->getUseConfigInstallments();
    }

}
