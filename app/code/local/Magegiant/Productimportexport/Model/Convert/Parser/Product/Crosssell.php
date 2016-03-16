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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Crosssell extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{
	public function parseCrosssell()
	{
		if (!$this->_canParse()) return;
		$items = $this->_getCrosssell();
		if (!count($items)) return;
		$this->addToRow('crosssell_associated_skus', Mage::helper('core')->jsonEncode($items));

	}

	public function _getCrosssell()
	{
		$items  = $this->getCurrentProduct()->getCrossSellProducts();
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
		return $this->isExportCrosssell();
	}

	public function isExportCrosssell()
	{
		return ($this->_getVar('export_crosssell_products') == 'true');
	}

	public function isExportCrosssellPosition()
	{
		return ($this->_getVar('export_crosssell_products_with_position') == 'true');
	}

	public function run(){
		$this->parseCrosssell();
	}
}

