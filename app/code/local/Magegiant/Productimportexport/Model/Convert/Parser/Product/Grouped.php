<?php

/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magegiant.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement.html
 */
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Grouped extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{

	public function parseGroupedProducts()
	{
		if (!$this->_canParse()) return;
		if (!count($data = $this->getGroupedProducts())) return;
		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_GROUPED,
			Mage::helper('core')->jsonEncode($data));
	}

	public function _canParse()
	{
		return ($this->getCurrentProductType() == 'grouped');
	}

	public function getGroupedProducts()
	{
		$return = array();
		foreach ($this->getAssociatedCollection() as $_item) {
			$return[] = array(
				'position' => $_item->getPosition(),
				'sku'      => $_item->getSku(),
				'qty'      => !is_null($_item->getQty()) ? $_item->getQty() : 0,
			);

		}

		return $return;
	}


	public function getAssociatedCollection()
	{
		return $this->getProductModelFactory()
			->getAssociatedProducts($this->getCurrentProduct());
	}


	public function run()
	{
		$this->parseGroupedProducts();
	}
}

