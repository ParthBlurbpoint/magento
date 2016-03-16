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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Customoptions extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{

	public function run()
	{
		$options = Mage::helper('core')->jsonEncode($this->_prepareData());
		if(!count($options)) return;
		$this->addToRow(Magegiant_Productimportexport_Helper_Data::COL_CUSTOM_OPTIONS, $options);
	}

	protected function _prepareData()
	{
		$options = $this->getCurrentProduct()->getOptions();
		$return = array();
		foreach($options as $option){
			$return[] = $option->getData();
		}
		return $return;
	}

}