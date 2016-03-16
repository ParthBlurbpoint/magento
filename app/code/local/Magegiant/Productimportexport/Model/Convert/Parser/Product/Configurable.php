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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Configurable extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{

	public function _canParse()
	{
		return ($this->getCurrentProductType() == 'configurable');
	}

	public function parseConfigurableProducts()
	{
		if (!$this->_canParse()) return;

		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_CONFIGURABLE_ATTRIBUTES,
			implode(self::MULTI_DELIMITER_SHORT, $this->getConfigurableAttributesData())
		);
		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_CONFIGURABLE_ASSOCIATED,
			implode(self::MULTI_DELIMITER_SHORT, $this->getConfigurableAssociated())
		);

		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_CONFIGURABLE_SUPER_PRICING,
			implode(self::OR_DELIMITER, $this->getSuperAttributePricing())
		);

	}

	public function getConfigurableAttributesAsArray()
	{
		$data = $this->getCurrentProduct()->getTypeInstance(true);

		return $data->getConfigurableAttributesAsArray($this->getCurrentProduct());
	}

	public function getConfigurableAttributesData()
	{
		$array  = $this->getConfigurableAttributesAsArray();
		$result = array();
		foreach ($array as $_item) {
			$result[] = $_item['attribute_code'];
		}

		return $result;
	}

	public function getConfigurableAssociated()
	{
		$return = array();
		foreach ($this->getAssociatedCollection() as $_item) {
			$return[] = $_item->getSku();
		}

		return $return;
	}

	public function getAssociatedCollection()
	{
		$_product = $this->getCurrentProduct();

		return $this->getProductTypeModel()->factory($_product)->getUsedProducts($_product);
	}

	public function getSuperAttributePricing()
	{
		$result = array();
		foreach ($this->getConfigurableAttributesAsArray() as $attr) {
			foreach ($attr['values'] as $value) {
				$result = array_merge($result, $this->getSuperAttributes($value));
			}
		}

		return $result;
	}

	public function getSuperAttributes($value)
	{

		$query = "SELECT * FROM " . $this->getCoreResource()->getTableName('catalog/product_super_attribute_pricing') . " WHERE product_super_attribute_id = " . $value['product_super_attribute_id'] .
			" AND value_index = " . $value['value_index'];

		$collection = $this->_coreRead()->fetchAll($query);

		$return = array();
		if (count($collection) > 0) {
			foreach ($collection as $_item) {
				if (!isset($_item['label'])) continue;
				if (!isset($_item['pricing_value'])) continue;
				if (!isset($_item['is_percent'])) continue;
				if (empty($_item['pricing_value'])) $_item['pricing_value'] = 0;
				$return[] = $_item['label'] . self::POSITION_DELIMITER . $_item['pricing_value'] . self::POSITION_DELIMITER . $_item['is_percent'];
			}
		} else {
			$return[] = $value['label'] . self::POSITION_DELIMITER . $value['pricing_value'] . self::POSITION_DELIMITER . $value['is_percent'];
		}

		return $return;
	}

	public function run()
	{
		$this->parseConfigurableProducts();
	}


}