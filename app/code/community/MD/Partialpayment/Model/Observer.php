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
class MD_Partialpayment_Model_Observer {
    private $ch;
    private $response;
    protected $results;

    protected function _getRequest() {
	return Mage::app()->getRequest();
    }
    
    public function setPartialOrigionalPrice(Varien_Event_Observer $observer) {
	$quoteItem     = $observer->getEvent()->getItem();
	
	if ($origPrice = $quoteItem->getOptionByCode('partialpayment_origional_price')) {
	    $orderItem = $observer->getEvent()->getOrderItem();
	    $options   = $orderItem->getProductOptions();
	    
	    $options['partialpayment_origional_price'] = $origPrice->getValue();
	    
	    $orderItem->setProductOptions($options);
	}
	
	return $this;
    }

    public function savePartialPaymentOptions(Varien_Event_Observer $observer) {
	$databaseFields = array(
	    'status',
	    'initial_payment_amount',
	    'additional_payment_amount',
	    'installments',
	    'frequency_payment'
	);
	$productId	       = $observer->getEvent()->getProduct()->getId();
	$storeId	       = $this->_getRequest()->getParam('store', 0);
	$existingId	       = Mage::getModel('md_partialpayment/options')->getIdByInfo($productId, $storeId);
	$params		       = $this->_getRequest()->getPost('partialpayment');	
	$slabs		       = isset($params['slabs']) ? $params['slabs'] : null;
	$options	       = array();
	$options['store_id']   = $storeId;
	$options['product_id'] = $productId;
	$options['status']     = (array_key_exists('status', $params)) ? $params['status'] : NULL;

	$options['initial_payment_amount']  = (array_key_exists('initial_payment_amount', $params)) ? $params['initial_payment_amount'] : NULL;
	$options['use_config_installments'] = (array_key_exists('use_config_installments', $params)) ? $params['use_config_installments'] : 0;

	$model = Mage::getModel('md_partialpayment/options');

	if (NULL === $options['status'] && NULL === $options['initial_payment_amount'] && 0 === $options['use_config_installments']) {
	    try {
		if (!is_null($existingId) && $existingId > 0) {
		    $model->load($existingId)->delete();
		}
	    } catch (Exception $e) {
		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
	    }
	} else {
	    foreach ($options as $key => $value) {
		if (is_array($value)) {
		    $model->setData($key, implode(",", $value));
		} else {
		    $model->setData($key, $value);
		}
	    }
	    
	    try {
		if (!is_null($existingId) && $existingId > 0) {
		    $model->setId($existingId);
		}
		
		$model->save();
	    } catch (Exception $e) {
		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
	    }
	}

	//save installment slab data
	if (is_array($slabs) && count($slabs) > 0) {
	    foreach ($slabs as $id => $_slabs) {
		$isDelete = ($_slabs['delete'] == 1) ? true : false;
		$id	  = (stripos($id, 'slab_option_', 0) === FALSE) ? $id : null;

		if ($isDelete) {
		    if (!is_null($id)) {
			Mage::getModel('md_partialpayment/slabs')->load($id)->delete();
		    }
		} else {
		    Mage::getModel('md_partialpayment/slabs')
			    ->setProductId($productId)
			    ->setStoreId($storeId)
			    ->setPriceType($_slabs['price_type'])
			    ->setPrice($_slabs['price'])
			    ->setUnit($_slabs['unit'])
			    ->setId($id)
			    ->save();
		}
	    }
	}

	//Partial plan rule starts
	//Check if product satisfies any Partial Plan Rule condition. 
	$product = $observer->getEvent()->getProduct();
	
	if ($product->getIsMassupdate()) {
	    return;
	}

	$writeConnection  = Mage::getSingleton('core/resource')->getConnection('core_write');
	$ruleCollection   = Mage::getModel('md_partialpayment/rule')->getCollection();
	$partialPlanTable = Mage::getSingleton('core/resource')->getTableName('md_partialpayment_partialplan_rule');

	foreach ($ruleCollection as $partialPlan) {
	    $productIdscomma    = $productIds = $partialPlanRuleId = '';
	    $partialPlanRuleId  = $partialPlan->getId();
	    $productIds	        = $partialPlan->getMatchingProductIds();
	    $existingProductIds = $partialPlan->getProductIds();
	    
	    if (!empty($productIds)) {
		$productIdscomma = implode(",", $productIds);
	    }

	    //Direct query has been used for faster performance.
	    if ($existingProductIds != $productIdscomma) {
		$query = "update md_partialpayment_partialplan_rule set product_ids='" . $productIdscomma . "' where rule_id=" . $partialPlanRuleId;
		
		$writeConnection->query($query);
	    }
	}
	//Partial plan rule ends

	return $this;
    }

    public function deletePartialPaymentOptions(Varien_Event_Observer $observer) {
	$productId  = $observer->getEvent()->getProduct()->getId();
	$collection = Mage::getModel('md_partialpayment/options')
		     ->getCollection()
		     ->addFieldToFilter('product_id', array('eq' => $productId));

	if ($collection->count() > 0) {
	    foreach ($collection as $option) {
		try {
		    $option->setId($option->getId())->delete();
		} catch (Exception $e) {
		    Mage::log($e->getMessage(), false, 'partial-payment.log');
		}
	    }
	}

	$slabsCollection = Mage::getModel('md_partialpayment/slabs')
			   ->getCollection()
			   ->addFieldToFilter('product_id', array('eq' => $productId));
	
	if ($slabsCollection) {
	    foreach ($slabsCollection as $_slab) {
		try {
		    $_slab->setId($_slab->getId())->delete();
		} catch (Exception $e) {
		    Mage::log($e->getMessage(), false, 'partial-payment.log');
		}
	    }
	}

	return $this;
    }

    public function setAdminPartialpaymentOptions(Varien_Event_Observer $observer) {
	try {
	    $quoteItem	     = $observer->getEvent()->getItem();
	    $qid	     = $quoteItem->getId();
	    $discount	     = $quoteItem->getDiscountAmount();
	    $quote	     = $quoteItem->getQuote();
	    $customerGroupId = $quote->getCustomerGroupId();
	    
	    if (!$quoteItem->getParentItemId() && Mage::helper("md_partialpayment")->isAllowGroups($customerGroupId)) {
		$product	     = $quoteItem->getProduct();
		$isFullSelected	     = (boolean) $quote->getMdPartialpaymentFullCart();
		$origionalBuyRequest = $quoteItem->getBuyRequest()->getData();
		
		if(!$isFullSelected) {
		    $installmentCount = ($quoteItem->getData('partialpayment_option_intial_amount') > 0) ? $quoteItem->getPartialpaymentInstallmentCount() - 1 :  $quoteItem->getPartialpaymentInstallmentCount();
		    
		    $buyRequest		     =  array(
			"product"	     => $product->getId(),
			"custom_options"     => array(
			    "partialpayment" => $quoteItem->getData('partialpayment_option_selected'),
			    "price"	     => $quoteItem->getPartialpaymentPrice(),
			    "price_type"     => $quoteItem->getPartialpaymentPriceType(),
			    "installments"   => $installmentCount
			)
		    );
		} else {
		    $buyRequest		 = array(
			"product"	     => isset($origionalBuyRequest['product']) ? $origionalBuyRequest['product'] : $product->getId(),
			"custom_options"     => array(
			    "partialpayment" => 1,
			    "price"	     => $quote->getMdPartialpaymentPrice(),
			    "price_type"     => $quote->getMdPartialpaymentPriceType(),
			    "installments"   => $quote->getMdPartialpaymentInstallmentsCount()
			)
		    );
		}

		$frequencyMap   = array(
		    'weekly'    => ' +7 days',
		    'quarterly' => ' +3 months',
		    'monthly'   => ' +1 month'
		);

		if (isset($buyRequest['custom_options']['partialpayment']) && $buyRequest['custom_options']['partialpayment'] == 1 && $product->getId() == $buyRequest['product']) {
		    $qty = 1;

		    if (!$isFullSelected) {
			$partialPaymentOptions = Mage::getModel('md_partialpayment/options')->getStoreOptions($product);
		    } else {
			$partialPaymentOptions = new MD_Partialpayment_Model_Options();

			$partialPaymentOptions->addData(array(
			    "initial_payment_amount"    => null,
			    "additional_payment_amount" => null,
			    "product_id"		=> $buyRequest['product']
			));
		    }

		    if ($partialPaymentOptions) {			
			$price		    = $quoteItem->getPrice();		    
			$frequency	    = Mage::getStoreConfig('md_partialpayment/general/frequency_of_payments');
			$createdAt	    = date('Y-m-d', strtotime($quoteItem->getCreatedAt()));
			$nextPaymentDate    = date('Y-m-d', strtotime($createdAt . $frequencyMap[$frequency]));
			
			$installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($product, $partialPaymentOptions, $qty, $price, $buyRequest['custom_options']['price'], $buyRequest['custom_options']['price_type'], $buyRequest['custom_options']['installments'], '', $quoteItem);

			if (count($installmentSummary) > 0) {
			    if($installmentSummary['remaining_amount'] > 0) {
				$quoteItem->setData('partialpayment_option_selected', '1');
			    } else {
				$quoteItem->setData('partialpayment_option_selected', '0');
			    }

			    $quoteItem->setData('partialpayment_installment_count', $installmentSummary['installment_count']);
			    $quoteItem->setData('partialpayment_paid_amount', $installmentSummary['initial_payment_amount']);
			    $quoteItem->setData('partialpayment_due_amount', $installmentSummary['remaining_amount']);
			    $quoteItem->setData('partialpayment_frequency', $frequency);
			    $quoteItem->setData('partialpayment_amount_due_after_date', $installmentSummary['installment_amount']);
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
			    if($installmentSummary['remaining_amount'] > 0) {
				$quoteItem->getProduct()->setSpecialPrice($installmentSummary['unit_payment']);
				$quoteItem->setPrice($installmentSummary['unit_payment']);
				$quoteItem->setBasePrice($installmentSummary['unit_payment']);
				$quoteItem->setCustomPrice($installmentSummary['unit_payment']);
				$quoteItem->setOriginalCustomPrice($installmentSummary['unit_payment']);
				$quoteItem->getProduct()->setIsSuperMode(true);	
			    } else {
				$quoteItem->getProduct()->setSpecialPrice(null);
				$quoteItem->setPrice($product->getFinalPrice());
				$quoteItem->setBasePrice($product->getFinalPrice());
				$quoteItem->setCustomPrice(null);
				$quoteItem->setOriginalCustomPrice(null);
				$quoteItem->getProduct()->setIsSuperMode(false);	
			    }
			    $quote->setTotalsCollectedFlag(false)->collectTotals();			    
			}
		    }
		}
	    }
	} catch (Exception $ex) {
	    echo $ex->getMessage();
	}
	return $this;
    } 
    
    public function setPartialpaymentOptions(Varien_Event_Observer $observer) {
	$quoteItem = $observer->getEvent()->getQuoteItem();
	$customer  = Mage::getSingleton('customer/session');
	
	if (!$quoteItem->getParentItemId() && Mage::helper("md_partialpayment")->isAllowGroups()) {
	    $product		     = $observer->getEvent()->getProduct();
	    $quote		     = Mage::getSingleton("checkout/session")->getQuote();
	    $isFullSelected	     = (boolean) $quote->getMdPartialpaymentFullCart();
	    $origionalBuyRequest     = $quoteItem->getBuyRequest()->getData();
	    $buyRequest		     = (!$isFullSelected) ? $origionalBuyRequest : array(
		"product"	     => $origionalBuyRequest['product'],
		"custom_options"     => array(
		    "partialpayment" => 1,
		    "price"	     => $quote->getMdPartialpaymentPrice(),
		    "price_type"     => $quote->getMdPartialpaymentPriceType(),
		    "installments"   => $quote->getMdPartialpaymentInstallmentsCount()
		)
	    );

	    $frequencyMap   = array(
		'weekly'    => ' +7 days',
		'quarterly' => ' +3 months',
		'monthly'   => ' +1 month'
	    );

	    if (isset($buyRequest['custom_options']['partialpayment']) && $buyRequest['custom_options']['partialpayment'] == 1 && $product->getId() == $buyRequest['product']) {
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
		    $price		= $quoteItem->getPrice();		    
		    $frequency		= Mage::getStoreConfig('md_partialpayment/general/frequency_of_payments');
		    $createdAt		= date('Y-m-d', strtotime($quoteItem->getCreatedAt()));
		    $nextPaymentDate    = date('Y-m-d', strtotime($createdAt . $frequencyMap[$frequency]));
		    $installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($product, $partialPaymentOptions, $qty, $price, $buyRequest['custom_options']['price'], $buyRequest['custom_options']['price_type'], $buyRequest['custom_options']['installments'], '', $quoteItem);

		    if (count($installmentSummary) > 0) {
			if($installmentSummary['remaining_amount'] > 0) {
			    $quoteItem->setData('partialpayment_option_selected', '1');
			} else {
			    $quoteItem->setData('partialpayment_option_selected', '0');
			}
			
			$quoteItem->setData('partialpayment_installment_count', $installmentSummary['installment_count']);
			$quoteItem->setData('partialpayment_paid_amount', $installmentSummary['initial_payment_amount']);
			$quoteItem->setData('partialpayment_due_amount', $installmentSummary['remaining_amount']);
			$quoteItem->setData('partialpayment_frequency', $frequency);
			$quoteItem->setData('partialpayment_amount_due_after_date', $installmentSummary['installment_amount']);
			$quoteItem->setData('partialpayment_next_installment_date', $nextPaymentDate);
			$quoteItem->setData('partialpayment_price_type', $buyRequest['custom_options']['price_type']);
			$quoteItem->setData('partialpayment_price', $buyRequest['custom_options']['price']);
			$quoteItem->setData('partialpayment_option_intial_amount', $installmentSummary['option_initial_amount']);
			$quoteItem->addOption(
				array(
				    "code"	 => "partialpayment_origional_price",
				    "value"	 => $product->getFinalPrice(),
				    "product_id" => $buyRequest['product']
				)
			);
			
			if($installmentSummary['remaining_amount'] > 0) {
			    $quoteItem->getProduct()->setSpecialPrice($installmentSummary['unit_payment']);
			    $quoteItem->setPrice($installmentSummary['unit_payment']);
			    $quoteItem->setBasePrice($installmentSummary['unit_payment']);
			    $quoteItem->setCustomPrice($installmentSummary['unit_payment']);
			    $quoteItem->setOriginalCustomPrice($installmentSummary['unit_payment']);
			    $quoteItem->getProduct()->setIsSuperMode(true);
			} else {
			    $quoteItem->getProduct()->setSpecialPrice(null);
			    $quoteItem->setPrice($product->getFinalPrice());
			    $quoteItem->setBasePrice($product->getFinalPrice());
			    $quoteItem->setCustomPrice(null);
			    $quoteItem->setOriginalCustomPrice(null);
			    $quoteItem->getProduct()->setIsSuperMode(false);	
			}
		    }
		}
	    }
	}
	return $this;
    }

    public function setCustomSubtotal(Varien_Event_Observer $observer) {
	$installmentData     = array();
	$order		     = $observer->getEvent()->getOrder();
	$hasPartialItem	     = false;
	$grandTotal	     = $order->getGrandTotal();
	$baseGrandTotal	     = $order->getBaseGrandTotal();
	$subtotal	     = $order->getSubtotal()		+ $order->getDiscountAmount();
	$subtotalInclTax     = $order->getSubtotalInclTax()	+ $order->getDiscountAmount(); 
	$baseSubtotal	     = $order->getBaseSubtotal()        + $order->getDiscountAmount();
	$baseSubtotalInclTax = $order->getBaseSubtotalInclTax() + $order->getDiscountAmount();
	$installments	     = array();
	$installmentData['grand_total_origional'] = $grandTotal;

	foreach ($order->getAllVisibleItems() as $item)	       {
	    if ($item->getPartialpaymentOptionSelected() == 1) {
		$hasPartialItem	      = true;
		$installments[]	      = (int) $item->getPartialpaymentInstallmentCount();
		$amount		      = $item->getPartialpaymentPaidAmount() * $item->getQtyOrdered(); 
		$baseAmount	      = $item->getPartialpaymentPaidAmount() * $item->getQtyOrdered(); 
		$subtotal	     -= ($item->getRowTotal()	  - $item->getDiscountAmount());
		$subtotalInclTax     -= ($item->getRowTotal()	  - $item->getDiscountAmount());
		$baseSubtotal	     -= ($item->getBaseRowTotal() - $item->getBaseDiscountAmount());
		$baseSubtotalInclTax -= ($item->getBaseRowTotal() - $item->getBaseDiscountAmount());
		$grandTotal	     -= ($item->getRowTotal()	  - $item->getDiscountAmount());
		$baseGrandTotal	     -= ($item->getBaseRowTotal() - $item->getBaseDiscountAmount());
		$subtotal	     += $amount;
		$subtotalInclTax     += $amount;
		$baseSubtotal	     += $baseAmount;
		$baseSubtotalInclTax += $baseAmount;
		$grandTotal	     += $amount;
		$baseGrandTotal	     += $baseAmount;
	    }
	}

	$installmentData['grand_total_partial'] = $grandTotal;
	$installmentData['installment_count']   = $installments;
	
	$subtotal	     -= $order->getDiscountAmount();
	$baseSubtotal	     -= $order->getBaseDiscountAmount();
	$subtotalInclTax     -= $order->getDiscountAmount();
	$baseSubtotalInclTax -= $order->getDiscountAmount();
	$grandTotal	     -= $order->getDiscountAmount();
	$baseGrandTotal	     -= $order->getDiscountAmount();
	
	$order->setSubtotal($subtotal);
	$order->getQuote()->setSubtotal($subtotal);
	$order->setBaseSubtotal($baseSubtotal);
	$order->getQuote()->setBaseSubtotal($baseSubtotal);
	$order->setSubtotalInclTax($subtotalInclTax);
	$order->getQuote()->setSubtotalInclTax($subtotalInclTax);
	$order->setBaseSubtotalInclTax($baseSubtotalInclTax);
	$order->getQuote()->setBaseSubtotalInclTax($baseSubtotalInclTax);
	$order->setGrandTotal($grandTotal);
	$order->getQuote()->setGrandTotal($grandTotal);
	$order->setBaseGrandTotal($baseGrandTotal);
	$order->getQuote()->setBaseGrandTotal($baseGrandTotal); 
	
	if ($order->getPayment()->getMethod() == Mage_Paypal_Model_Config::METHOD_WPS && $hasPartialItem) {
	    Mage::dispatchEvent('md_partialpayment_order_item_payment_placed', array('order' => $order, 'partial_data' => $installmentData));
	}
	
	return $this;
    }

    public function resetTotalsForOrder(Varien_Event_Observer $observer) {
	$order = $observer->getEvent()->getOrder();
	
	if ($order->getPayment()->getMethod() != Mage_Paypal_Model_Config::METHOD_WPS) {	    
	    $grandTotal	     = $order->getGrandTotal();
	    $installmentData = array();
	    $hasPartialItem  = false;
	    $installments    = array();

	    $installmentData['grand_total_partial'] = round(((float) $grandTotal + $order->getDiscountAmount()),2);

	    $baseGrandTotal	 = $order->getBaseGrandTotal();
	    $subtotal		 = round(($order->getSubtotal()		   + $order->getDiscountAmount()),2);
	    $subtotalInclTax	 = round(($order->getSubtotalInclTax()     + $order->getDiscountAmount()),2);
	    $baseSubtotal	 = round(($order->getBaseSubtotal()        + $order->getDiscountAmount()),2);
	    $baseSubtotalInclTax = round(($order->getBaseSubtotalInclTax() + $order->getDiscountAmount()),2);
	    $totalDue		 = $order->getTotalDue()     + $order->getDiscountAmount();
	    $baseTotalDue	 = $order->getBaseTotalDue() + $order->getBaseDiscountAmount();

	    foreach ($order->getAllVisibleItems() as $item) {
		if ($item->getPartialpaymentOptionSelected() == 1) {
		    $hasPartialItem	      = true;
		    $installments[]	      = (int) $item->getPartialpaymentInstallmentCount();
		    $amount		      = $item->getPartialpaymentPaidAmount() * $item->getQtyOrdered();
		    $baseAmount		      = $item->getPartialpaymentPaidAmount() * $item->getQtyOrdered();
		    $subtotal		      = round(($subtotal	    - $amount),2);
		    $subtotalInclTax	      = round(($subtotalInclTax     - $amount),2);
		    $baseSubtotal	      = round(($baseSubtotal	    - $baseAmount),2);
		    $baseSubtotalInclTax      = round(($baseSubtotalInclTax - $baseAmount),2);
		    $grandTotal		      = round(($grandTotal	    - $amount),2);
		    $baseGrandTotal	      = round(($baseGrandTotal      - $baseAmount),2);
		    $subtotal		     += $item->getRowTotal();
		    $subtotalInclTax	     += $item->getRowTotal();
		    $baseSubtotal	     += $item->getBaseRowTotal();
		    $baseSubtotalInclTax     += $item->getBaseRowTotal();
		    $grandTotal		     += ($item->getRowTotal()     - $item->getDiscountAmount());
		    $baseGrandTotal	     += ($item->getBaseRowTotal() - $item->getBaseDiscountAmount());
		    $totalDue		     += $amount;
		    $baseTotalDue	     += $baseAmount;

		    $order->getItemByQuoteItemId($item->getQuoteItemId())
			  ->setRowInvoiced($amount)
			  ->setBaseRowInvoiced($baseAmount);
		}
	    }

	    $grandTotal	    += $order->getDiscountAmount();
	    $baseGrandTotal += $order->getDiscountAmount();

	    $installmentData['grand_total_origional'] = (float) $grandTotal;
	    $installmentData['installment_count']     = $installments;

	    $order->setSubtotal($subtotal);
	    $order->getQuote()->setSubtotal($subtotal);
	    $order->setBaseSubtotal($baseSubtotal);
	    $order->getQuote()->setBaseSubtotal($baseSubtotal);
	    $order->setSubtotalInclTax($subtotalInclTax);
	    $order->getQuote()->setSubtotalInclTax($subtotalInclTax);
	    $order->setBaseSubtotalInclTax($baseSubtotalInclTax);
	    $order->getQuote()->setBaseSubtotalInclTax($baseSubtotalInclTax);
	    $order->setGrandTotal($grandTotal);
	    $order->getQuote()->setGrandTotal($grandTotal);
	    $order->setBaseGrandTotal($baseGrandTotal);
	    $order->getQuote()->setBaseGrandTotal($baseGrandTotal);
	    $order->setTotalDue($totalDue - $order->getShippingAmount());
	    $order->setBaseTotalDue($baseTotalDue - $order->getBaseShippingAmount());

	    if ($hasPartialItem) {
		Mage::dispatchEvent('md_partialpayment_order_item_payment_placed', array('order' => $order, 'partial_data' => $installmentData));
	    }
	}
	return $this;
    }

    public function insertPaymentSummary(Varien_Event_Observer $observer) {
	$frequencyMap    = array(
	    'weekly'  	 => ' +7 days',
	    'quarterly'  => ' +3 months',
	    'monthly'	 => ' +1 month'
	);
	$order		 = $observer->getEvent()->getOrder();
	$frequency	 = Mage::getStoreConfig('md_partialpayment/general/frequency_of_payments', $order->getStoreId());
	$nextPaymentDate = Mage::getSingleton('core/date')->gmtDate('Y-m-d', strtotime($order->getCreatedAt() . $frequencyMap[$frequency]));
	$partialData	 = $observer->getEvent()->getPartialData();

	if (count($partialData) > 0 && count($partialData['installment_count']) > 0) {
	    $totalInstallmentCount = max($partialData['installment_count']);
	    $payment		   = $order->getPayment();
	    $transactionId	   = $payment->getLastTransId();	
	    
	    
	    if(($payment->getMethod() == 'md_authorizecim') || (($payment->getMethodInstance()->isGateway() || isset($transactionId)) && !in_array($payment->getMethod(), array("sagepaydirectpro", "sagepayserver")))) {
		$paidAmount	  = $partialData['grand_total_partial'];
		$dueAmount	  = $partialData['grand_total_origional'] - $partialData['grand_total_partial'];
		$paidInstallments = 1;
	    } else {
		$paidAmount	  = 0; 
		$dueAmount	  = $partialData['grand_total_origional'];
		$paidInstallments = 0;
	    }
	    
	    try {
		$installmentPayment = Mage::getModel('md_partialpayment/payments')
			->setData('order_id', $order->getRealOrderId())
			->setData('store_id', $order->getStoreId())
			->setData('paid_amount', $paidAmount)
			->setData('due_amount', $dueAmount)
			->setData('customer_id', $order->getCustomerId())
			->setData('customer_name', $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname())
			->setData('customer_email', $order->getCustomerEmail())
			->setData('paid_installments', $paidInstallments)
			->setData('due_installments', $totalInstallmentCount - $paidInstallments)
			->setData('last_installment_date', date('Y-m-d', strtotime($order->getCreatedAt())))
			->setData('next_installment_date', $nextPaymentDate)
			->setData('created_at', date('Y-m-d H:i:s'));
		
		$installmentPayment->save();
		
		$lastId	     = $installmentPayment->getId();
		$summaryData = Mage::helper('md_partialpayment')->getInstallmentSummary($order, $totalInstallmentCount);
		
		if (count($summaryData) > 0) {
		    foreach ($summaryData as $data) {
			$data['payment_id'] = $lastId;
			
			Mage::getModel('md_partialpayment/summary')->setData($data)->save();
		    }
		}
		
		Mage::dispatchEvent("md_partial_payment_summary_save_after", array('payments' => $installmentPayment));
	    } catch (Exception $e) {
		Mage::getSingleton('core/session')->addError($e->getMessage());
	    }
	}
    }

    public function calculatePartialItemsToInvoice(Varien_Event_Observer $observer) {
	$invoice = $observer->getEvent()->getInvoice();
	
	if ((!$invoice->getOrder()->getPayment()->getMethodInstance()->isGateway() && $invoice->getOrder()->getPayment()->getMethod() != Mage_Paypal_Model_Config::METHOD_WPS) || $invoice->getRequestedCaptureCase() == $invoice::CAPTURE_OFFLINE) {
	    $items		 = $invoice->getAllItems();
	    $grandTotal		 = $invoice->getOrder()->getGrandTotal();
	    $baseGrandTotal	 = $invoice->getOrder()->getBaseGrandTotal();
	    $subtotal		 = $invoice->getOrder()->getSubtotal();
	    $baseSubtotal	 = $invoice->getOrder()->getBaseSubtotal();
	    $subtotalInclTax  	 = $invoice->getOrder()->getSubtotalInclTax();
	    $baseSubtotalInclTax = $invoice->getOrder()->getBaseSubtotalInclTax();
	    $totalDue		 = $invoice->getOrder()->getGrandTotal();
	    $baseTotalDue	 = $invoice->getOrder()->getBaseGrandTotal();
	    $payments		 = Mage::getModel('md_partialpayment/payments')->getPaymentsByOrder($invoice->getOrder());

	    if ($payments) {
		$summaryStatusProcessing = $payments->getPaymentSummaryCollection()->getFirstItem();
		
		foreach ($items as $item) {
		    $orderItem = $item->getOrderItem();

		    if ($orderItem->getPartialpaymentOptionSelected() == 1) {
			$invoiceItemQty	       = (int) $item->getQty();
			$partialPaymentPaidAmt = (double) $orderItem->getPartialpaymentPaidAmount();
			$amount		       = ($invoiceItemQty > 0) ? $partialPaymentPaidAmt * $invoiceItemQty : 0; /* - $orderItem->getTaxAmount(); */
			$baseAmount	       = ($invoiceItemQty > 0) ? $partialPaymentPaidAmt * $invoiceItemQty : 0; /* - $orderItem->getBaseTaxAmount(); */

			$totalDue	     -= ($orderItem->getRowTotal()     - $orderItem->getDiscountAmount());
			$baseTotalDue	     -= ($orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount());
			$grandTotal	     -= ($orderItem->getRowTotal()     - $orderItem->getDiscountAmount());
			$baseGrandTotal	     -= ($orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount());
			$subtotal	     -= ($orderItem->getRowTotal()     - $orderItem->getDiscountAmount());
			$baseSubtotal	     -= ($orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount());
			$subtotalInclTax     -= ($orderItem->getRowTotal()     - $orderItem->getDiscountAmount());
			$baseSubtotalInclTax -= ($orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount());
			$totalDue	     += $amount;
			$baseTotalDue	     += $baseAmount;
			$grandTotal	     += $amount;
			$baseGrandTotal	     += $baseAmount;
			$subtotal	     += $amount;
			$baseSubtotal	     += $baseAmount;
			$subtotalInclTax     += $amount;
			$baseSubtotalInclTax += $baseAmount;
		    }
		}

		$invoice->setSubtotal($subtotal);
		$invoice->setBaseSubtotal($baseSubtotal);
		$invoice->setSubtotalInclTax($subtotalInclTax);
		$invoice->setBaseSubtotalInclTax($baseSubtotalInclTax);
		$invoice->setGrandTotal($grandTotal);
		$invoice->setBaseGrandTotal($baseGrandTotal);
		$invoice->getOrder()->setTotalPaid(max(0, $totalDue));
		$invoice->getOrder()->setBaseTotalPaid(max(0, $baseTotalDue));
	    } 

	    if ($invoice->getState() == $invoice::STATE_PAID && !is_null($payments)) {
		$payments->setPaidAmount($payments->getPaidAmount() + $summaryStatusProcessing->getAmount())
			 ->setDueAmount($payments->getDueAmount()   - $summaryStatusProcessing->getAmount())
			 ->setPaidInstallments($payments->getPaidInstallments() + 1)
			 ->setDueInstallments($payments->getDueInstallments()   - 1);

		$summaryStatusProcessing->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS);

		try {
		    $transaction = Mage::getModel('core/resource_transaction');
		    $transaction->addObject($summaryStatusProcessing);
		    $transaction->addObject($payments);
		    $transaction->save();
		    
		    Mage::helper('md_partialpayment')->sendPaymentScheduleEmail($payments);
		} catch (Exception $e) {
		    Mage::getSingleton('core/session')->addError($e->getMessage());
		}
	    }
	}
	
	return $this;
    }

    public function removePartialpaymentOptions(Varien_Event_Observer $observer) {
	$product    = $observer->getEvent()->getProduct();
	$collection = Mage::getModel('md_partialpayment/options')
		      ->getCollection()
		      ->addFieldToFilter('product_id', array('eq' => $product->getId()));

	foreach ($collection as $option) {
	    $option->setId($option->getId())->delete();
	}
	
	return $this;
    }

    public function setCustomSubtotalForBundleProduct(Varien_Event_Observer $observer) {
	$subtotalDelta	       = 0;
	$baseSubtotalDelta     = 0;
	$quote		       = $observer->getEvent()->getQuote();
	$isFullSelected	       = (boolean) $quote->getMdPartialpaymentFullCart();
	$isBundleProductExists = false;
	
	foreach ($quote->getAllItems() as $item) {
	    if ($item->getPartialpaymentOptionSelected() == 1) {
		if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
		    if (!$isBundleProductExists) {
			$isBundleProductExists = true;
		    }
		    
		    $product = $item->getProduct();
		    
		    if (!$isFullSelected) {
			$partialPaymentOptions = Mage::getModel('md_partialpayment/options')->getStoreOptions($product);
		    } else {
			$partialPaymentOptions = new MD_Partialpayment_Model_Options();
			
			$partialPaymentOptions->addData(array (
			    "initial_payment_amount"	=> null,
			    "additional_payment_amount" => null,
			    "product_id"		=> $product->getId()
			));
		    }
		    $additional = 0;
		    $surcharge  = 0;
		    
		    if ($partialPaymentOptions) {
			$origionalBuyRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
			
			if(!$isFullSelected) {
			    $buyRequest = $origionalBuyRequest;
			} else {
			    $buyRequest = array(
				"custom_options"     => array(
				    "partialpayment" => 1, 
				    "product"	     => $product->getId(), 
				    "price"	     => $quote->getMdPartialpaymentPrice(), 
				    "price_type"     => $quote->getMdPartialpaymentPriceType(), 
				    "installments"   => $quote->getMdPartialpaymentInstallmentsCount()
				)
			    );
			}
			 
			if (isset($buyRequest['custom_options']['price']) && isset($buyRequest['custom_options']['price_type']) && isset($buyRequest['custom_options']['installments'])) {
			    
			    $installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($product, $partialPaymentOptions, $item->getQty(), $item->getPrice(), $buyRequest['custom_options']['price'], $buyRequest['custom_options']['price_type'], $buyRequest['custom_options']['installments']);
			    
			} else {

			    $installmentSummary = Mage::getModel('md_partialpayment/options')->getInstallmentSummary($product, $partialPaymentOptions, $item->getQty(), $item->getPrice(), $item->getPartialpaymentPrice(), $item->getPartialpaymentPriceType(), $item->getPartialpaymentInstallmentCount());
			    
			}

			$additional	= $installmentSummary['additional_payment_amount'] * $item->getQty();
			$surcharge      = $installmentSummary['partial_surcharge'] * $item->getQty();
		    }
		    $subtotalDelta     += max(0, $additional + $surcharge);
		    $baseSubtotalDelta += max(0, $additional + $surcharge);

		    $item->setRowTotal($item->getRowTotal() + $additional + $surcharge)
			 ->setBaseRowTotal($item->getBaseRowTotal() + $additional + $surcharge)
			 ->setRowTotalInclTax($item->getRowTotalInclTax() + $additional + $surcharge)
			 ->setBaseRowTotalInclTax($item->getBaseRowTotalInclTax() + $additional + $surcharge)
			 ->setRowTotalWithDiscount($item->getRowTotalWithDiscount() + $additional + $surcharge)
			 ->setBaseRowTotalWithDiscount($item->getBaseRowTotalWithDiscount() + $additional + $surcharge);
		}
	    }
	}
	
	if ($isBundleProductExists)  {
	    if ($quote->isVirtual()) {
		$quote->getBillingAddress()
		      ->setSubtotal($quote->getBillingAddress()->getSubtotal() + $subtotalDelta)
		      ->setBaseSubtotal($quote->getBillingAddress()->getBaseSubtotal() + $baseSubtotalDelta)
		      ->setGrandTotal($quote->getBillingAddress()->getGrandTotal() + $subtotalDelta)
		      ->setBaseGrandTotal($quote->getBillingAddress()->getBaseGrandTotal() + $baseSubtotalDelta)
		      ->setSubtotalInclTax($quote->getBillingAddress()->getSubtotalInclTax() + $subtotalDelta)
		      ->setBaseSubtotalIncltax($quote->getBillingAddress()->getBaseSubtotalIncltax() + $baseSubtotalDelta);
	    } else {
		$quote->getShippingAddress()
		      ->setSubtotal($quote->getShippingAddress()->getSubtotal() + $subtotalDelta)
		      ->setBaseSubtotal($quote->getShippingAddress()->getBaseSubtotal() + $baseSubtotalDelta)
		      ->setGrandTotal($quote->getShippingAddress()->getGrandTotal() + $subtotalDelta)
		      ->setBaseGrandTotal($quote->getShippingAddress()->getBaseGrandTotal() + $baseSubtotalDelta)
		      ->setSubtotalInclTax($quote->getShippingAddress()->getSubtotalInclTax() + $subtotalDelta)
		      ->setBaseSubtotalIncltax($quote->getShippingAddress()->getBaseSubtotalIncltax() + $baseSubtotalDelta);
	    }
	    
	    $quote->setSubtotal($quote->getSubtotal() + $subtotalDelta);
	    $quote->setBaseSubtotal($quote->getBaseSubtotal() + $baseSubtotalDelta);
	    $quote->setSubtotalWithDiscount($quote->getSubtotalWithDiscount() + $subtotalDelta);
	    $quote->setBaseSubtotalWithDiscount($quote->getBaseSubtotalWithDiscount() + $baseSubtotalDelta);
	    $quote->setGrandTotal($quote->getGrandTotal() + $subtotalDelta);
	    $quote->setBaseGrandTotal($quote->getBaseGrandTotal() + $baseSubtotalDelta);
	}
	return $this;
    }
    
    //Paypal related methods start
    public function updatePaypalCartLineItems(Varien_Event_Observer $observer) {
	$paypalCart	    = $observer->getEvent()->getPaypalCart();
	
	if($paypalCart) {
	    $order	    = $paypalCart->getSalesEntity();
	    $discountAmount = $order->getDiscountAmount();
	    $subtotal	    = 0;
	    if($order instanceof Mage_Sales_Model_Order) {
		foreach($order->getAllVisibleItems() as $orderItem)  {
		    if($orderItem->getPartialpaymentOptionSelected() == 1) {
			
			$paypalCart->removeItem($orderItem->getSku());
			$paypalCart->addItem($orderItem->getName(),$orderItem->getQtyOrdered(),$orderItem->getPartialpaymentPaidAmount(),$orderItem->getSku());
		    }
		}
		
		if($discountAmount != 0) {
		    $paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_DISCOUNT, $discountAmount);   
		} 
		
		if(Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment')) {
		    $paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_SHIPPING, (-1 * $order->getBaseShippingAmount()));  
		    $paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_TAX, (-1 * $order->getBaseTaxAmount()));   
		}
		$paypalCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_SUBTOTAL, (-1 * $order->getBaseSubtotal())); 
	    }
	}
    }
    
    public function setPaypalActionItems(Varien_Event_Observer $observer) {
	if ($observer->getEvent()->getControllerAction()->getFullActionName() == 'paypal_standard_success') {
	    $quoteId = Mage::getSingleton('checkout/session')->getPaypalStandardQuoteId(true);
	    $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
	    
	    Mage::dispatchEvent("paypal_redirect_action_after", array("order_id" => $orderId, "quote_id" => $quoteId, "paypal_action" => "success"));
	    
	} elseif ($observer->getEvent()->getControllerAction()->getFullActionName() == 'paypal_standard_cancel') {
	    $quoteId = Mage::getSingleton('checkout/session')->getPaypalStandardQuoteId(true);
	    $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
	    
	    Mage::dispatchEvent("paypal_redirect_action_after", array("order_id" => $orderId, "quote_id" => $quoteId, "paypal_action" => "cancel"));
	    
	} elseif ($observer->getEvent()->getControllerAction()->getFullActionName() == 'paypal_ipn_index') {
	    Mage::dispatchEvent("paypal_first_ipn_received_after", array("request" => $observer->getControllerAction()->getRequest()));
	}
	
	return $this;
    }
    
    public function resetOrderTotalForPaypal(Varien_Event_Observer $observer) {
	$invoice = $observer->getEvent()->getInvoice();
	$order   = $observer->getEvent()->getOrder();
	
	if ($order->getPayment()->getMethod() == Mage_Paypal_Model_Config::METHOD_WPS) {
	    $grandTotal		 = $order->getGrandTotal();
	    $baseGrandTotal	 = $order->getBaseGrandTotal();
	    $subtotal		 = $order->getSubtotal();
	    $subtotalInclTax	 = $order->getSubtotalInclTax();
	    $baseSubtotal	 = $order->getBaseSubtotal();
	    $baseSubtotalInclTax = $order->getBaseSubtotalInclTax();
	    $totalDue		 = $order->getTotalDue();
	    $baseTotalDue	 = $order->getBaseTotalDue();

	    foreach ($order->getAllVisibleItems() as $item) {
		if ($item->getPartialpaymentOptionSelected() == 1) {
		    $amount		  = $item->getPartialpaymentDueAmount(); /* - $item->getTaxAmount(); */
		    $subtotal		 += $amount;
		    $subtotalInclTax	 += $amount;
		    $baseSubtotal	 += $amount;
		    $baseSubtotalInclTax += $amount;
		    $grandTotal		 += $amount;
		    $baseGrandTotal	 += $amount;
		    $totalDue		 += $amount;
		    $baseTotalDue	 += $amount;
		}
	    }

	    $order->setSubtotal($subtotal);
	    $order->setBaseSubtotal($baseSubtotal);
	    $order->setSubtotalInclTax($subtotalInclTax);
	    $order->setBaseSubtotalInclTax($baseSubtotalInclTax);
	    $order->setGrandTotal($grandTotal);
	    $order->setBaseGrandTotal($baseGrandTotal);
	    $order->setTotalDue($totalDue - $order->getShippingAmount());
	    $order->setBaseTotalDue($baseTotalDue - $order->getBaseShippingAmount());
	}
	return $this;
    }

    public function setUpdatedSubtotalPaypal(Varien_Event_Observer $observer) {
	$paypalCart	      = $observer->getEvent()->getPaypalCart();
	$entity		      = $paypalCart->getSalesEntity();
	$baseSubtotal	      = $entity->getBaseSubtotal();
	$baseGrandTotal	      = $entity->getBaseGrandTotal();
	$calculatedBaseAmount = 0;

	foreach ($paypalCart->getItems() as $paypalItem) {
	    if ($paypalItem->getPartialpaymentOptionSelected() == 1) {
		$baseAmount	       = $paypalItem->getPartialpaymentPaidAmount();
		$calculatedBaseAmount += $paypalItem->getBaseRowTotal();
		$calculatedBaseAmount -= $baseAmount;
		
		$paypalCart->removeItem($paypalItem->getSku());
		
		if ($entity instanceof Mage_Sales_Model_Order) {
		    $qty    = (int) $paypalItem->getQtyOrdered();
		    $amount = (float) $baseAmount;
		    // TODO: nominal item for order
		} else {
		    $qty    = (int) $paypalItem->getTotalQty();
		    $amount = $paypalItem->isNominal() ? 0 : (float) $baseAmount;
		}
		
		$paypalCart->addItem($paypalItem->getName(), $qty, $amount, $paypalItem->getSku());
	    }
	}
	
	$baseGrandTotal -= $calculatedBaseAmount;
	
	$entity->setBaseGrandTotal($baseGrandTotal);
	$paypalCart->updateTotal($paypalCart::TOTAL_SUBTOTAL, -$calculatedBaseAmount);
	
	return $this;
    }
    
    public function saveFirstInstallmentStatus(Varien_Event_Observer $observer) {
	$request = $observer->getEvent()->getRequest();
	
	if (!$request->isPost()) {
	    return;
	}
	
	$data = $request->getPost();
	
	Mage::getModel('md_partialpayment/payment_paypal_standard')->processIpnRequest($data);
    }

    public function insertPartialPaymentDetailsByAction(Varien_Event_Observer $observer) {
	$quoteId = $observer->getEvent()->getQuoteId();
	$orderId = $observer->getEvent()->getOrderId();
	$action  = $observer->getEvent()->getPaypalAction();

	if ($orderId) {
	    $order	    = Mage::getModel('sales/order')->loadByIncrementId($orderId);
	    $existsPayments = Mage::getModel('md_partialpayment/payments')->getPaymentsByOrder($order);
	    
	    if ($action == "success") {
		if (!$existsPayments) {
		    $installmentData = array();
		    $hasPartialItem  = false;
		    $installments    = array();
		    $grandTotal	     = $order->getGrandTotal();
		    
		    $installmentData['grand_total_origional'] = $grandTotal;
		    
		    foreach ($order->getAllVisibleItems() as $item)	   {
			if ($item->getPartialpaymentOptionSelected() == 1) {
			    $hasPartialItem = true;
			    $installments[] = (int) $item->getPartialpaymentInstallmentCount();
			    $amount	    = $item->getPartialpaymentPaidAmount();
			    $grandTotal	   -= $item->getRowTotal();
			    $grandTotal	   += $amount;
			}
		    }
		    
		    $installmentData['grand_total_partial'] = $grandTotal;
		    $installmentData['installment_count']   = $installments;
		 if($hasPartialItem){   
		    Mage::dispatchEvent('md_partialpayment_order_item_payment_placed', array('order' => $order, 'partial_data' => $installmentData));
		  }
		}
	    } else {
		$payments = Mage::getModel('md_partialpayment/payments')->getPaymentsByOrder($order);
		if ($payments) {
		    $payments->setId($payments->getId())->delete();
		}
	    }
	}
	return $this;
    }
    //Paypal related methods end
    
    //Emails related methods start    
    public function sendInstallmentReminderEmail() {
	$resource	 = Mage::getSingleton('core/resource');
	$readConnection  = $resource->getConnection('core_read');
	$writeConnection = $resource->getConnection('core_write');
	$days		 = Mage::getStoreConfig('md_partialpayment/email/remind_days_before');
	$table		 = $resource->getTableName('md_partialpayment/summary');
	$query		 = "SELECT e.summary_id  FROM `" . $table . "` as e WHERE DATEDIFF(e.due_date, now()) = '" . $days . "' AND e.status NOT IN ('" . MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS . "')";
	$summaryIds	 = $readConnection->fetchCol($query);

	if (count($summaryIds) > 0) {
	    Mage::helper('md_partialpayment')->sendReminderEmail($summaryIds);
	}

	return $this;
    }

    public function sendInstallmentSummaryEmail(Varien_Event_Observer $observer) {
	$payments = $observer->getEvent()->getPayments();
	
	Mage::helper('md_partialpayment')->sendPaymentScheduleEmail($payments);
	
	return $this;
    }
    //Emails related methods end
    
    //authorize and authorize CIM related
    public function collectAmountOfInstallments() {
	if (Mage::helper('md_partialpayment')->isAutoCaptureEnabled()) {
	    $adapter		 = Mage::getSingleton('core/resource');
	    $summatyTable	 = $adapter->getTableName('md_partialpayment/summary');
	    $partialPaymentTable = $adapter->getTableName('md_partialpayment/payments');
	    $orderTable		 = $adapter->getTableName('sales/order');
	    $paymentTable	 = $adapter->getTableName('sales/order_payment');
	    $readAdapter	 = $adapter->getConnection('core_read');
	    $writeAdapter	 = $adapter->getConnection('core_write');
	    $paymentMethodCodes	 = array(
		Mage_Paygate_Model_Authorizenet::METHOD_CODE,
		'md_authorizecim',
		'md_stripe_cards',
		'md_cybersource'
	    );
	    
	    $query = "SELECT e.*,p.order_id,o.increment_id,op.additional_information,op.md_customer_profile_id,op.md_payment_profile_id FROM `" . $summatyTable . "` as `e` LEFT JOIN `" . $partialPaymentTable . "` AS `p` ON `e`.payment_id=`p`.payment_id LEFT JOIN `" . $orderTable . "` AS `o` ON `p`.order_id=`o`.increment_id LEFT JOIN `" . $paymentTable . "` AS `op` ON `o`.entity_id=`op`.parent_id  WHERE DATEDIFF(e.due_date,now()) <= 0 AND `e`.status NOT IN (" . MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS . "," . MD_Partialpayment_Model_Summary::PAYMENT_PROCESS . ") AND (`op`.method in ('".implode("','",$paymentMethodCodes)."'))";
	    
	    $queryResult = $readAdapter->fetchAll($query);
	    
	    $string	 = '<?xml version="1.0" encoding="utf-8"?><createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"><merchantAuthentication><name>%s</name><transactionKey>%s</transactionKey></merchantAuthentication><transaction><%s><amount>%.2f</amount><tax><amount>%.2f</amount></tax><shipping><amount>%.2f</amount></shipping><customerProfileId>%d</customerProfileId><customerPaymentProfileId>%d</customerPaymentProfileId><order><invoiceNumber>%s</invoiceNumber><description>%s</description></order></%s></transaction><extraOptions><![CDATA[x_duplicate_window=0]]></extraOptions></createCustomerProfileTransactionRequest>';
	    
	    if (is_array($queryResult) && count($queryResult) > 0) {
		foreach ($queryResult as $_result) {
		    $summary	 = Mage::getModel('md_partialpayment/summary')->load($_result['summary_id']);
		    $payments	 = $summary->getPayments();
		    try {
			if(empty($payments)) {
			    $payments = Mage::getModel('md_partialpayment/payment')->load($_result['payment_id']);
			    $orderId  = $payments->getOrderId();
			    $payments->setOrder(Mage::getModel('sales/order')->load($orderId));
			} 
		    } catch(Exception $ex) {
			Mage::log($ex->getMessage(), null, 'partial-payment.log');
		    }
		    
		    $order	= $payments->getOrder();
		    $methodCode = $order->getPayment()->getMethod();
		    
		    if(in_array($methodCode, array(Mage_Paygate_Model_Authorizenet::METHOD_CODE,'md_authorizecim'))) {
			$payDetails		  = unserialize($_result['additional_information']);
			$cust_profile_id	  = $payDetails['profile_id'];
			$customerPaymentProfileId = $payDetails['payment_id'];

			if (!is_null($_result['md_customer_profile_id']) && strlen($_result['md_customer_profile_id']) > 0 && !is_null($_result['md_payment_profile_id']) && strlen($_result['md_payment_profile_id'])) {
			    $text	    = '';
			    $apiLoginId	    = Mage::getStoreConfig('payment/' . $methodCode . '/login');
			    $transactionKey = Mage::getStoreConfig('payment/' . $methodCode . '/trans_key');
			    $isTestMode	    = (boolean) Mage::getStoreConfig('payment/' . $methodCode . '/test');
			    $methodAction   = Mage::getStoreConfig('payment/' . $methodCode . '/payment_action');

			    if($isTestMode) {
				$apiUrl	 = 'https://apitest.authorize.net/xml/v1/request.api';
			    } else {
				$apiUrl	 = 'https://api.authorize.net/xml/v1/request.api';
			    }

			    if($methodAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) {
				$requestAction        = 'profileTransAuthOnly';
				$requestedOperation   = 'authorize';
			    } else {
				$requestAction        = 'profileTransAuthCapture';
				$requestedOperation   = 'authorize and capture';
			    }

			    $statusHistryTextTemplate = "%s %s %s - %s. %s. %s";
			    $stringDescription	  = sprintf('Order #: %s-Summary #: %d-Summary Payment #: %d', $_result['order_id'], $_result['summary_id'], $_result['payment_id']);

			    if($methodCode == 'md_authorizecim') {
				$mappedString  = sprintf($string, $apiLoginId, $transactionKey, $requestAction, $_result['amount'], 0, 0, $cust_profile_id, $customerPaymentProfileId, $_result['increment_id'] . "-" . $_result['summary_id'], $stringDescription, $requestAction);

				$response      = $this->process($mappedString, $apiUrl);
			    } else {
				$mappedString  = sprintf($string, $apiLoginId, $transactionKey, $requestAction, $_result['amount'], 0, 0, $_result['md_customer_profile_id'], $_result['md_payment_profile_id'], $_result['increment_id'] . "-" . $_result['summary_id'], $stringDescription, $requestAction);

				$responseArray = $this->_postProfileCreationRequest($mappedString, $apiUrl);
				$response      = $this->_prepareResponse($responseArray['directResponse']);
			    }

			    if ($response) {
				$amount		  = 0;
				$installmentCount = 0;
				$status		  = MD_Partialpayment_Model_Summary::PAYMENT_PENDING;

				switch ($response->getResponseCode()) {
				    case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_APPROVED:
					$amount		  = $response->getAmount();
					$text		  = Mage::helper('md_partialpayment')->__('successful');
					$installmentCount = 1;
					$status		  = MD_Partialpayment_Model_Summary::PAYMENT_SUCCESS;
					break;
				    case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_HELD:
					$text   = Mage::helper('md_partialpayment')->__('hold');
					$status = MD_Partialpayment_Model_Summary::PAYMENT_HOLD;
					break;
				    case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_DECLINED:
				    case Mage_Paygate_Model_Authorizenet::RESPONSE_CODE_ERROR:
					$status = MD_Partialpayment_Model_Summary::PAYMENT_FAIL;
					$text   = Mage::helper('md_partialpayment')->__('failed');
					break;
				    default:
					break;
				}

				$summary->setPaidDate(Mage::getSingleton('core/date')->gmtDate())
					->setStatus($status)
					->setTransactionId($response->getTransactionId())
					->setPaymentMethod($methodCode)
					->setPaymentFailCount($summary->getPaymentFailCount() + $installmentCount)
					->setTransactionDetails(serialize($response->getData()));

				$payments->setPaidAmount($payments->getPaidAmount() + $amount)
					->setDueAmount(max(0, ($payments->getDueAmount() - $amount)))
					->setLastInstallmentDate(Mage::getSingleton('core/date')->gmtDate())
					->setPaidInstallments($payments->getPaidInstallments() + $installmentCount)
					->setDueInstallments(max(0, ($payments->getDueInstallments() - $installmentCount)))
					->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());

				if ($payments->getDueInstallments() > 0) {
				    $orderDueAmount	= max(0, ($order->getTotalDue() - $amount));
				    $baseOrderDueAmount = max(0, ($order->getBaseTotalDue() - $amount));
				} else {
				    $orderDueAmount	= 0;
				    $baseOrderDueAmount = 0;
				}

				$order->setTotalPaid($order->getTotalPaid() + $amount)
				      ->setBaseTotalPaid($order->getBaseTotalPaid() + $amount)
				      ->setTotalDue($orderDueAmount)
				      ->setBaseTotalDue($baseOrderDueAmount);

				if (strlen($text) > 0) {
				    $historyAmount      = sprintf('amount %s', $order->formatPrice($summary->getAmount()));
				    $card		= sprintf('Credit Card: xxxx-%s', $response->getCcLast4());
				    $historyTransaction = sprintf('Authorize.Net CIM Transaction ID %s', $response->getTransactionId());
				    $statusHistoryText  = sprintf($statusHistryTextTemplate, $card, strip_tags($historyAmount), $requestedOperation, $text, $historyTransaction, $response->getResponseReasonText());

				    $order->addStatusHistoryComment($statusHistoryText);

				    $transaction = Mage::getModel('core/resource_transaction');

				    $transaction->addObject($summary);
				    $transaction->addObject($payments);
				    $transaction->addObject($order);

				    try {
					$transaction->save();
					$summary->sendStatusPaymentEmail(true, true);
				    } catch (Exception $e) {
					Mage::getSingleton('core/session')->addError($e->getMessage());
				    }
				}
			    }
			}
		    } else if($methodCode == 'md_cybersource') {
			$customerId = $payments->getCustomerId();
			$result	    = array();
			if(!empty($customerId)) {
			    $card = Mage::getModel('md_cybersource/cards')->getCollection()
			    ->addFieldToFilter('customer_id',$customerId)
			    ->getFirstItem();
			    
			    if($card !== false && count($card)) {
				 $info = array();
				 $info['method']	  = $methodCode;
				 $info['subscription_id'] = Mage::helper('core')->encrypt($card['subscription_id']);
				 
				  Mage::getModel('md_partialpayment/payment_cybersource')
				    ->setSummary($summary)
				    ->setPayments($payments)
				    ->setOrder($order)
				    ->setPaymentRequestArea('adminhtml')
				    ->pay($info);
			     }
			}
		    } else if($methodCode == 'md_stripe_cards') {
			$info		  = array();
			$info['method']   = $methodCode;
			$customerId       = $payments->getCustomerId();
			$customer	  = Mage::getModel('customer/customer')->load($customerId);
			$stripeCustomerId = $customer->getMdStripeCustomerId();
			$storeId	  = (Mage::getSingleton('adminhtml/session_quote')->getStoreId() > 0) ? Mage::getSingleton('adminhtml/session_quote')->getStoreId() : Mage::app()->getStore()->getId();
			
			if (Mage::getModel("md_stripe/config", array($methodCode, $storeId))->getIsActive()):
			    if ($stripeCustomerId):
				$requestData['id'] = $stripeCustomerId;
				$restApiObject	   = Mage::getModel('md_stripe/api_rest')
					->setApiType(MD_Stripe_Model_Cardspayment::METHOD_CODE)
					->setStore($storeId);

				$restResponse	   = $restApiObject->getCustomerCards($requestData);

				if (array_key_exists('result_data', $restResponse)):
				    $restResponseObject = $restResponse['result_data'];
				    //$cards		= $restResponseObject->sources->data;
				    $defaultCardId	= (string) $restResponseObject->default_source;
				endif;
			    endif;
			endif;
			
			if(!empty($defaultCardId)) {
			    $info['md_stripe_card_id'] = $defaultCardId;
			
			    Mage::getModel('md_partialpayment/payment_stripe')
				->setSummary($summary)
				->setPayments($payments)
				->setOrder($order)
				->setPaymentRequestArea('adminhtml')
				->pay($info);
			}
		    }
		}
	    }
	}
	
	return $this;
    }

    protected function _prepareResponse($response = null) {
	$responseObject = null;
	
	if (is_string($response) && strlen($response) > 0) {
	    $r = explode(",", $response);
	    
	    if ($r) {
		$responseObject = new Varien_Object();
		$responseObject->setResponseCode((int) str_replace('"', '', $r[0]))
			       ->setResponseSubcode((int) str_replace('"', '', $r[1]))
			       ->setResponseReasonCode((int) str_replace('"', '', $r[2]))
			       ->setResponseReasonText($r[3])
			       ->setApprovalCode($r[4])
			       ->setAvsResultCode($r[5])
			       ->setTransactionId($r[6])
			       ->setInvoiceNumber($r[7])
			       ->setDescription($r[8])
			       ->setAmount($r[9])
			       ->setMethod($r[10])
			       ->setTransactionType($r[11])
			       ->setCustomerId($r[12])
			       ->setMd5Hash($r[37])
			       ->setCardCodeResponseCode($r[38])
			       ->setCAVVResponseCode((isset($r[39])) ? $r[39] : null)
			       ->setSplitTenderId($r[52])
			       ->setAccNumber($r[50])
			       ->setCardType($r[51])
			       ->setRequestedAmount($r[53])
			       ->setBalanceOnCard($r[54])
			       ->setCcLast4(substr($r[50], -4));
	    }
	}
	
	return $responseObject;
    }

    

    public function unsetFullCartOptions(Varien_Event_Observer $observer) {
	$quote = $observer->getEvent()->getQuote();
	
	if (!$quote->getItemsCount()) {
	    $quote->setData("md_partialpayment_full_cart", "0");
	    $quote->setData("md_partialpayment_price_type", NULL);
	    $quote->setData("md_partialpayment_price", NULL);
	    $quote->setData("md_partialpayment_installments_count", NULL);
	} else {
	    $displayOpt	   = Mage::helper("md_partialpayment")->getIsFullCartPartialPaymentEnabled();
	    $hasFullCartPP = $quote->getData("md_partialpayment_full_cart");

	    if ($hasFullCartPP == 1) {
		$isEligibleForPP = Mage::helper('md_partialpayment')->checkCartTotalEligibility($quote);
		
		if (!$isEligibleForPP) {
		    $minCartTotal  = Mage::getStoreConfig(MD_Partialpayment_Helper_Data::PARTIAL_MINIMUM_CART_TOTAL);
		    $minimumCartTotalType = Mage::getStoreConfig(MD_Partialpayment_Helper_Data::PARTIAL_MINIMUM_CART_TOTAL_TYPE);
		    
		    if (empty($minimumCartTotalType) || $minimumCartTotalType == 'subtotal') {
			Mage::getSingleton("checkout/session")->addError("For availing Partial Payment, Minimum Cart Subtotal after discount(if any) should be " . $minCartTotal);
			
		    } else {
			Mage::getSingleton("checkout/session")->addError("For availing Partial Payment, Minimum Cart Grand Total after discount(if any) should be " . $minCartTotal);
		    }
		    //Code from \app\code\community\MD\Partialpayment\controllers\SummaryController.php removeCartOptionAction
		    
		    foreach ($quote->getAllVisibleItems() as $_item) {
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
				    array(
					"code"	     => "info_buyRequest", 
					"value"	     => serialize($infoBuyRequest), 
					"product_id" => $_item->getProductId())
				);
			    }
			}

			$_item->getProduct()->setSpecialPrice($_item->getProduct()->getFinalPrice());
			$_item->setPrice($_item->getProduct()->getFinalPrice());
			$_item->setBasePrice($_item->getProduct()->getFinalPrice());
			$_item->setCustomPrice($_item->getProduct()->getFinalPrice());
			$_item->setOriginalCustomPrice($_item->getProduct()->getFinalPrice());
			$_item->getProduct()->setIsSuperMode(true);
		    }
		    
		    $quote->setData("md_partialpayment_full_cart", "0");
		    $quote->setData("md_partialpayment_price_type", NULL);
		    $quote->setData("md_partialpayment_price", NULL);
		    $quote->setData("md_partialpayment_installments_count", NULL);
		    $quote->getShippingAddress()->setCollectShippingRates(true);
		}
	    }
	}
	return $this;
    }
    
    //Authorize CIM related methods start
    private function parseResults() {
	$response = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $this->response);
	$xml = new SimpleXMLElement($response);

	$this->directResponse = str_replace('"', '', (string) $xml->directResponse);

	if (strlen($this->directResponse) > 1) {
	    $this->delimiter = substr($this->directResponse, 1, 1);
	}

	$this->raw		= $xml;
	$this->resultCode	= (string) $xml->messages->resultCode;
	$this->code		= (string) $xml->messages->message->code;
	$this->text		= (string) $xml->messages->message->text;
	$this->validation	= (string) $xml->validationDirectResponse;
	$this->profileId	= (int) $xml->customerProfileId;
	$this->addressId	= (int) $xml->customerAddressId;
	$this->paymentProfileId = (int) $xml->customerPaymentProfileId;
	$this->results		= explode($this->delimiter, $this->directResponse);
    }
    
    public function process($str, $apiUrl) {
	$this->ch = curl_init();
	curl_setopt($this->ch, CURLOPT_URL, $apiUrl);
	curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($this->ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
	curl_setopt($this->ch, CURLOPT_HEADER, 0);
	curl_setopt($this->ch, CURLOPT_POSTFIELDS, $str);
	curl_setopt($this->ch, CURLOPT_POST, 1);
	curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($this->ch, CURLOPT_TIMEOUT, 15);
	$this->response = curl_exec($this->ch);
	
	$gatewayResponse = new Varien_Object();
	if ($this->response) {
	    // Don't log full CC numbers.
	    $this->responses .= preg_replace('#cardCode>(\d+?)</cardCode#', 'cardCode>XXX</cardCode', preg_replace('#cardNumber>(\d{10})(\d+?)</cardNumber#', 'cardNumber>XXXX$2</cardNumber', $this->xml)) . "\n";
	    $this->responses .= $this->response . "\n";

	    $this->parseResults();

	    $r = $this->results;
	    
	    if ($this->resultCode === 'Ok') {
		$gatewayResponse->setResponseCode((int) str_replace('"', '', $r[0]))
				->setResponseSubcode((int) str_replace('"', '', $r[1]))
				->setResponseReasonCode((int) str_replace('"', '', $r[2]))
				->setResponseReasonText($r[3])->setApprovalCode($r[4])->setAvsResultCode($r[5])
				->setTransactionId($r[6])->setInvoiceNumber($r[7])->setDescription($r[8])
				->setAmount($r[9])->setMethod($r[10])->setTransactionType($r[11])
				->setCustomerId($r[12])->setMd5Hash($r[37])->setCardCodeResponseCode($r[38])
				->setCAVVResponseCode((isset($r[39])) ? $r[39] : null)->setSplitTenderId($r[52])
				->setAccNumber($r[50])->setCardType($r[51])->setRequestedAmount($r[53])
				->setBalanceOnCard($r[54])->setCcLast4(substr($r[50], -4));

		$this->success = true;
		$this->error = false;
	    } else {
		$this->success = false;
		$this->error = true;
	    }
	    curl_close($this->ch);
	    unset($this->ch);
	}
	return $gatewayResponse;
    }
    //Authorize CIM related methods end
    
    public function createCimProfiles(Varien_Event_Observer $observer) {
	if (Mage::helper('md_partialpayment')->isAutoCaptureEnabled()) {
	    $payment	     = $observer->getPayment();
	    $code	     = $payment->getMethod();
	    $order	     = $payment->getOrder();
	    $hasPartialItems = false;
	    
	    foreach ($order->getAllVisibleItems() as $_item) {
		if ($_item->getPartialpaymentOptionSelected() == 1) {
		    $hasPartialItems = true;
		    break;
		}
	    }
	    
	    if ($code == Mage_Paygate_Model_Authorizenet::METHOD_CODE && $hasPartialItems) {
		$storeId	= $order->getStoreId();
		$billing	= $order->getBillingAddress();
		$cardNumber	= $payment->getCcNumber();
		$expMonth	= $payment->getCcExpMonth();
		$expYear	= $payment->getCcExpYear();
		$ccId		= $payment->getCcCid();
		$apiLoginId	= Mage::getStoreConfig('payment/' . $code . '/login', $storeId);
		$transactionKey = Mage::getStoreConfig('payment/' . $code . '/trans_key', $storeId);
		$isTestMode	= (boolean) Mage::getStoreConfig('payment/' . $code . '/test', $storeId);
		$methodAction	= Mage::getStoreConfig('payment/' . $code . '/payment_action', $storeId);
		$validationMode = ($isTestMode) ? 'none' : 'liveMode';
		$apiUrl	        = ($isTestMode) ? 'https://apitest.authorize.net/xml/v1/request.api' : 'https://api.authorize.net/xml/v1/request.api';


		$string  = '<?xml version="1.0" encoding="utf-8"?>';
		$string .= '<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
		$string .= '<merchantAuthentication>';
		$string .= '<name>' . $apiLoginId . '</name>';
		$string .= '<transactionKey>' . $transactionKey . '</transactionKey>';
		$string .= '</merchantAuthentication>';
		$string .= '<profile>';
		
		if ($billing->getCustomerId()) {
		    $string .= '<merchantCustomerId>' . $billing->getCustomerId() . '</merchantCustomerId>';
		}
		$string .= '<description>' . sprintf("%s : %s : %s", Mage::getBaseUrl(), $billing->getEmail(), Mage::getSingleton('core/date')->gmtDate()) . '</description>';
		$string .= '<email>' . $billing->getEmail() . '</email>';
		$string .= '<paymentProfiles>';
		$string .= '<customerType>individual</customerType>';
		$string .= '<billTo>';
		$string .= '<firstName>' . $billing->getFirstname() . '</firstName><lastName>' . $billing->getLastname() . '</lastName><company>' . $billing->getCompany() . '</company><address>' . $billing->getStreet(1) . '</address><city>' . $billing->getCity() . '</city><state>' . $billing->getRegion() . '</state><zip>' . $billing->getPostcode() . '</zip><country>' . $billing->getCountry() . '</country><phoneNumber>' . $billing->getTelephone() . '</phoneNumber><faxNumber>' . $billing->getFax() . '</faxNumber>';
		
		$string .= '</billTo>';
		$string .= '<payment>';
		$string .= '<creditCard>';
		$string .= '<cardNumber>' . $cardNumber . '</cardNumber><expirationDate>' . sprintf('%04d-%02d', $expYear, $expMonth) . '</expirationDate>';
		
		if (!is_null($ccId) && strlen($ccId) > 0) {
		    $string .= '<cardCode>' . $ccId . '</cardCode>';
		}
		
		$string  .= '</creditCard>';
		$string  .= '</payment>';
		$string  .= '</paymentProfiles>';
		$string  .= '</profile>';
		$string  .= '<validationMode>' . $validationMode . '</validationMode>';
		$string	 .= '</createCustomerProfileRequest>';
		$response = $this->_postProfileCreationRequest($string, $apiUrl);
		
		if ($response['messages']['resultCode'] == 'Ok' && $response['messages']['message']['code'] == 'I00001') {
		    $customerProfileId = $response['customerProfileId'];
		    
		    if (!is_array($response['customerPaymentProfileIdList']['numericString'])) {
			$paymentProfileId = $response['customerPaymentProfileIdList']['numericString'];
		    } else {
			$paymentProfileId = $response['customerPaymentProfileIdList']['numericString'][0];
		    }
		    
		    $payment->setMdCustomerProfileId($customerProfileId);
		    $payment->setMdPaymentProfileId($paymentProfileId);
		}
	    }
	}
	
	return $this;
    }

    protected function _postProfileCreationRequest($request, $apiUrl) {
	$xmlToArrayResponse = array();
	try {
	    $http   = new Varien_Http_Adapter_Curl();
	    $config = array(
		'timeout'    => 60,
		'verifypeer' => false,
		'header'     => false,
	    );
	    
	    $http->setConfig($config);
	    
	    if (function_exists($http, 'setOptions')) {
		$http->setOptions(CURLOPT_HEADER, false);
	    }
	    
	    $http->write(
		    Zend_Http_Client::POST, $apiUrl, '1.1', array('Content-Type: application/xml'), $request
	    );
	    
	    $response = $http->read();
	    
	    $http->close();
	    
	    if (function_exists($http, 'setOptions')) {
		$xml	       = new Varien_Simplexml_Element($response);
	    } else {
		$strPos	       = stripos($response, "<createCustomerProfileResponse");
		$finalResponse = substr($response, $strPos);
		$xml	       = new Varien_Simplexml_Element('<?xml version="1.0" encoding="utf-8"?>' . $finalResponse);
	    }
	    
	    $xmlToArrayResponse = $xml->asArray();
	} catch (Exception $e) {
	    $xmlToArrayResponse['messages']['resultCode'] = 'Error';
	    $xmlToArrayResponse['messages']['message']	  = $e->getMessage();
	}
	return $xmlToArrayResponse;
    }
    
    //Layout related observers start
    public function removeGuestCheckout(Varien_Event_Observer $observer) {
	$quote	   = $observer->getEvent()->getQuote();
	$store	   = $observer->getEvent()->getStore();
	$result    = $observer->getEvent()->getResult();
	$isContain = false;
	
	foreach ($quote->getAllVisibleItems() as $item) {
	    if (($product  = $item->getProduct()) && $item->getPartialpaymentOptionSelected() == 1) {
		$isContain = true;
	    }
	}
	if ($isContain && Mage::getStoreConfigFlag("md_partialpayment/general/disable_guest_checkout", $store)) {
	    $result->setIsAllowed(false);
	}
	return $this;
    }
    
    public function addPartialpaymentTab(Varien_Event_Observer $observer) {
	$block = $observer->getEvent()->getBlock();
	$id    = $this->_getRequest()->getParam('id', null);
	
	if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs) {
	    if (Mage::registry('current_product')) {
		$product = Mage::registry('current_product');
		$type    = $product->getTypeId();
	    } else {
		$type    = $this->_getRequest()->getParam('type', null);
	    }
	    
	    if (!is_null($type) && $type != Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
		if (method_exists($block, 'addTabAfter')) {
		    $block->addTabAfter('md_partialpayment_options', array(
			'label'   => Mage::helper('md_partialpayment')->__('Partial Payment Information'),
			'title'   => Mage::helper('md_partialpayment')->__('Partial Payment Information'),
			'content' => $block->getLayout()->createBlock("md_partialpayment/adminhtml_catalog_product_edit_tab_partialpayment")->toHtml(),
		    ), 'price');
		} else {
		    $block->addTab('md_partialpayment_options', array(
			'label'   => Mage::helper('md_partialpayment')->__('Partial Payment Information'),
			'title'	  => Mage::helper('md_partialpayment')->__('Partial Payment Information'),
			'content' => $block->getLayout()->createBlock("md_partialpayment/adminhtml_catalog_product_edit_tab_partialpayment")->toHtml(),
		    ));
		}
	    }
	}
    }
    
     public function removePaymentMethods(Varien_Event_Observer $observer) {
	$block	       = $observer->getEvent()->getBlock();
	if ($block instanceof Mage_Checkout_Block_Onepage_Payment_Methods) {
	    $quote     = $block->getQuote();
	    $isPartial = Mage::helper('md_partialpayment')->isQuotePartialPayment($quote);
	    
	    if ($isPartial) {
		$revisedMethods = array();
		
		foreach ($block->getMethods() as $mthod) {
		    if (Mage::helper('md_partialpayment')->isAllowedMethod($mthod->getCode())) {
			$revisedMethods[] = $mthod;
		    }
		}
		
		$block->setData('methods', $revisedMethods);
	    }
	}
	return $this;
    }
    
    public function appendFullCartPaymentBlock(Varien_Event_Observer $observer) {
	$controller = Mage::app()->getRequest()->getControllerName();
	$router	    = Mage::app()->getRequest()->getRouteName();
	$action	    = Mage::app()->getRequest()->getActionName();
	$block	    = $observer->getEvent()->getBlock();
	
	if ($router == 'checkout' && $controller == 'cart' && $action == 'index' && Mage::helper("md_partialpayment")->getIsFullCartPartialPaymentEnabled()) {
	    if ($block->getNameInLayout() == 'checkout.cart.coupon') {
		$transportObject = $observer->getEvent()->getTransport();
		$existingHtml	 = $transportObject->getHtml();
		$toAppendHtml	 = $block->getLayout()->createBlock("core/template")->setTemplate('md/partialpayment/checkout/cart/cart_full.phtml')->toHtml();
		
		$transportObject->setHtml($toAppendHtml . $existingHtml);
	    }
	}
	
	if ($block instanceof Mage_Paypal_Block_Express_Shortcut) {
	    $quote	= Mage::getSingleton("checkout/session")->getQuote();
	    $hasPartial = false;
	    
	    if ($quote) {
		$hasPartial = Mage::helper("md_partialpayment")->isQuotePartialPayment($quote);
	    }

	    if ($hasPartial) {
		$transportObject = $observer->getEvent()->getTransport();
		$transportObject->setHtml("");
	    }
	}
	
	return $this;
    }
    //Layout related observers end 
}
