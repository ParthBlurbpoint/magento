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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Review extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{

	protected function _canParse()
	{
		return ($this->_isExportReview());
	}

	protected function _isExportReview()
	{
		return ($this->_getVar('export_reviews') == 'true');
	}

	public function run()
	{
		if(count($this->_prepareData())){
			$this->addToRow('reviews', Mage::helper('core')->jsonEncode($this->_prepareData()));
		}else{
			$this->addToRow('reviews', '');
		}
	}


	public function _prepareData()
	{
		$reviews = Mage::getModel('review/review')->getResourceCollection();
		$reviews->addStoreFilter(Mage::app()->getStore()->getId())
			->addFieldToFilter('entity_pk_value',$this->getCurrentProduct()->getId())
			->load();
		$return = array();
		foreach ($reviews as $_review) {
			$return[] = $_review->getData();
		}

		return $return;
	}

}