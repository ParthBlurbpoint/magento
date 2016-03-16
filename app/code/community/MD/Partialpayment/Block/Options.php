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
class MD_Partialpayment_Block_Options extends Mage_Catalog_Block_Product_View {

    public function getPartialPaymentOption($product=null) {
	if(empty($product)) {
	    $product = $this->getProduct();
	}
	$option = Mage::getModel('md_partialpayment/options')->getStoreOptions($product);
	return $option;
    }

    public function getOptionInstallmentOptions($product=null) {
	if(empty($product)) {
	    $product = $this->getProduct();
	}
	
	$productPartialPlanRuleId = Mage::helper('md_partialpayment')->getPlanProductsId($product->getId());
	$existingOption		  = $this->getPartialPaymentOption($product);
	$installments		  = null;
	$appliedRule		  = false;

	if (!empty($productPartialPlanRuleId)) {
	    if ($existingOption && !$existingOption->getUseConfigForInstallmentOptions()) {
		$partialPlanRule = Mage::getModel('md_partialpayment/rule')->load($productPartialPlanRuleId);
		$installmentSettings = $partialPlanRule->getInstallmentSettings();
		if (!empty($installmentSettings)) {
		    $appliedRule = true;
		    $installmentSettings = unserialize($installmentSettings);
		    $installments = new Varien_Data_Collection();
		    foreach ($installmentSettings as $slab) {
			$installments->addItem(new Varien_Object($slab));
		    }
		}
	    }
	}

	if ($appliedRule == false) {
	    if ($existingOption) {
		if (!$existingOption->getUseConfigForInstallmentOptions()) {
		    $installments = $existingOption->getInstallmentSlabs($product->getStoreId());
		    if ($installments->count() <= 0) {
			$installments = $existingOption->getInstallmentSlabs();
		    }
		} else {
		    $installments = new Varien_Data_Collection();
		    $configSlabs = Mage::helper('md_partialpayment')->getConfigInstallmentOptions();
		    foreach ($configSlabs as $slab) {
			$installments->addItem(new Varien_Object($slab));
		    }
		}
	    }
	}
	return $installments;
    }

    public function getSlabJson($product=null) {
	$slabCollection = $this->getOptionInstallmentOptions($product);
	$data = array();
	foreach ($slabCollection as $_slab) {
	    array_push($data, $_slab->getData());
	}
	return Mage::helper('core')->jsonEncode($data);
    }

    public function getCheckboxLabel($product=null) {
	if(empty($product)) {
	    $product = $this->getProduct();
	}
	
	$option = $this->getPartialPaymentOption($product);
	$configInitialPaymentType = Mage::getStoreConfig("md_partialpayment/general/initial_payment_type");
	$configInitialPayment = Mage::getStoreConfig("md_partialpayment/general/initial_payment_amount");
	$priceLabel = $this->__("Pay with installments");
	if (!is_null($option->getInitialPaymentAmount()) || $configInitialPaymentType == MD_Partialpayment_Model_Options::PAYMENT_FIXED) {
	    $initialPaymentAmount = (!is_null($option->getInitialPaymentAmount())) ? $option->getInitialPaymentAmount() : $configInitialPayment;
	    if ($initialPaymentAmount > 0) {
		$priceLabel = sprintf("Pay Now %s and rest with easy installments.", Mage::helper("core")->currency($initialPaymentAmount, true, false));
	    }
	}
	return $priceLabel;
    }

    public function shouldReloadCheckboxLabel($product=null) {
	if(empty($product)) {
	    $product = $this->getProduct();
	}
	
	$option = $this->getPartialPaymentOption($product);
	$configInitialPaymentType = Mage::getStoreConfig("md_partialpayment/general/initial_payment_type", $product->getStoreId());
	$shouldReload = true;
	if (is_null($option->getInitialPaymentAmount()) && $configInitialPaymentType == MD_Partialpayment_Model_Options::PAYMENT_FIXED) {
	    $shouldReload = false;
	}
	return $shouldReload;
    }

}
