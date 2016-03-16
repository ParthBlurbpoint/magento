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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Upsell extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{
	public function run()
	{
		$this->parseUpsell();
	}

	public function parseUpsell()
	{
		if (!$this->isExportUpsell()) return;
		$items = $this->_getUpsell();
		if (!count($items)) return;
		$this->addToRow('upsell_associated_skus', Mage::helper('core')->jsonEncode($items));

	}

	public function _getUpsell()
	{
		$items  = $this->getCurrentProduct()->getUpSellProducts();
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

	public function isExportUpsell()
	{
		return ($this->_getVar('export_upsell_products') == 'true');
	}

	public function isExportUpsellPosition()
	{
		return ($this->_getVar('export_upsell_products_with_position') == 'true');
	}
}
