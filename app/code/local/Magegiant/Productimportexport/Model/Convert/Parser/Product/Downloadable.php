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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Downloadable extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{
	public function run()
	{
		if (!$this->_canParse()) return;
		$links = $this->_getDownloadableLinks();
		if (is_array($links) AND count($links))
			$this->addToRow(Magegiant_Productimportexport_Helper_Data::COL_DOWNLOADABLE_LINKS, Mage::helper('core')->jsonEncode($links));

		$samples = $this->_getDownloadableSamples();
		if (is_array($samples) AND count($samples))
			$this->addToRow(Magegiant_Productimportexport_Helper_Data::COL_DOWNLOADABLE_SAMPLES, Mage::helper('core')->jsonEncode($samples));

	}

	public function _canParse()
	{
		return ($this->getCurrentProductType() == 'downloadable');
	}

	protected function _getDownloadableLinks()
	{
		$_product   = $this->getCurrentProduct();
		$collection = Mage::getModel('downloadable/link')
			->getCollection()
			->addProductToFilter($_product->getId())
			->addTitleToResult($_product->getStoreId())
			->addPriceToResult($_product->getStore()->getWebsiteId());

		$result = array();
		foreach ($collection as $_link) {
			$result[] = $_link->getData();
		}

		return $result;
	}

	protected function _getDownloadableSamples()
	{
		$_product   = $this->getCurrentProduct();
		$collection = Mage::getModel('downloadable/sample')
			->getCollection()
			->addProductToFilter($_product->getId())
			->addTitleToResult($_product->getStoreId());
		$result = array();
		foreach ($collection as $_link) {
			$result[] = $_link->getData();
		}

		return $result;
	}


}
