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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Tag extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{

	protected function _canParse()
	{
		return ($this->_isExport());
	}

	protected function _isExport()
	{
		return ($this->_getVar('export_tags') == 'true');
	}

	protected function _prepareData()
	{
		$model  = Mage::getModel('tag/tag');
		$tags   = $model->getResourceCollection()
			->addPopularity()
			->addProductFilter($this->getCurrentProduct()->getId())
			->setFlag('relation', true)
			->addStoreFilter($this->getCurrentProduct()->getStoreIds(),'IN')
			->load();
		$return = array();
		foreach ($tags as $_tag) {
			if ($_tag->getName())
				$return[] = $_tag->getName();
		}

		return $return;

	}

	public function run()
	{
		$this->addToRow(Magegiant_Productimportexport_Helper_Data::COL_PRODUCT_TAGS, implode(self::MULTI_DELIMITER_SHORT, $this->_prepareData()));
	}
}