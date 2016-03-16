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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Related extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{
	public function parseRelated()
	{
		if (!$this->_canParse()) return;
		$items = $this->_getRelateds();
		if (!count($items)) return;
		$this->addToRow('related_associated_skus',  Mage::helper('core')->jsonEncode($items));
	}

	public function _getRelateds()
	{
		$items  = $this->getCurrentProduct()->getRelatedProducts();
		$return = array();
		foreach ($items as $_item) {
			$return[] = array(
				'position' => $_item->getPosition(),
				'sku'      => $_item->getSku(),
				'qty'      => $_item->getQty(),
			);
		}

		return $return;
	}

	public function _canParse()
	{
		return $this->isExportRelated();
	}

	public function isExportRelated()
	{
		return ($this->_getVar('export_related_products') == 'true');
	}

	public function isExportRelatedPosition()
	{
		return ($this->_getVar('export_related_products_with_position') == 'true');
	}

	public function run(){
		$this->parseRelated();
	}
}
