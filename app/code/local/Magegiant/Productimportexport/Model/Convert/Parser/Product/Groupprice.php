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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Groupprice extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{

	const GROUP_TABLE = 'catalog/product_attribute_group_price';


	protected $_websiteIdToCode;

	public function __construct()
	{
		$this->_initWebsites();
	}

	public function run()
	{
		$this->parseGroupPrice();
	}

	public function parseGroupPrice()
	{
		if (!$this->_canParse()) return;
		if (!count($data = $this->_getGroupPrice())) return;
		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_GROUPED_PRICE,
			implode(self::OR_DELIMITER, $data)
		);

	}

	public function _canParse()
	{
		return version_compare($this->getMagentoVersion(), '1.7.0.0', '>=');
	}

	public function _getGroupPrice()
	{
		$return = array();
		if (!is_array($this->_getGroupPriceCollection())) return;
		foreach ($this->_getGroupPriceCollection() as $_item) {
			$return[] = $_item['customer_group_id'] . self::EQUAL_DELIMITER . $_item['value'];
		}

		return $return;
	}

	public function _getGroupPriceCollection()
	{

		$_product = $this->getCurrentProduct();
		$select     = "SELECT * FROM " .
			$this->getCoreResource()->getTableName('catalog/product_attribute_group_price') .
			" WHERE entity_id = " . $_product->getId();
		$collection = $this->_coreRead()->fetchAll($select);
		if (!is_null($collection)) {
			return $collection;
		} else {
			return null;
		}


	}


	protected function _initWebsites()
	{
		foreach (Mage::app()->getWebsites() as $website) {
			$this->_websiteIdToCode[$website->getId()] = $website->getCode();
		}

		return $this;
	}
}

