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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Bundle extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{

	public function run()
	{
		$this->parseBundleProducts();
	}

	public function parseBundleProducts()
	{
		if (!$this->_canParse()) return;
		$options    = array();
		$selections = array();
		foreach ($this->getBundleCollection() as $_item) {
			$_option = $this->getOptions($_item);
			if ($_option) $options[] = $_option;

			$_selection = $this->getSelections($_item);
			if ($_selection) $selections[] = $_selection;
		}

		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_BUNDLE_OPTIONS,
			Mage::helper('core')->jsonEncode($options)
		);

		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_BUNDLE_SELECTIONS,
			Mage::helper('core')->jsonEncode($selections)
		);

	}

	protected function _canParse()
	{
		return ($this->getCurrentProduct()->getTypeId() == 'bundle');
	}


	public function getOptionName($option)
	{
		$query = "SELECT `title` FROM " . $this->getTablePrefix() . "catalog_product_bundle_option_value WHERE `option_id` = " . $option->getOptionId();
		$row   = $this->_coreRead()->fetchOne($query);
		if ($row && isset($row['title']))
			return $row['title'];
		else
			return null;
	}

	public function getOptions($option)
	{
		$name = $this->getOptionName($option);
		if(!$name OR empty($name)) return null;
		return array(
			'name'     => $this->getOptionName($option),
			'type'     => $option->getType(),
			'required' => $option->getRequired(),
			'position' => $option->getPosition(),
			'delete'   => '0',
		);
	}

	public function getSelections($option)
	{
		$result = array();
		try {
			$selection = $this->getBundleSelectionModel()
				->setOptionId($option->getOptionId())
				->getResourceCollection();

			$result = array();
			foreach ($selection as $_item) {
				if ($_item->getOptionId() != $option->getOptionId()) continue;
				$_selection = array(
					'sku'                      => $_item->getSku(),
					'parent_product_id'        => $_item->getParentProductId(),
					'selection_price_type'     => $_item->getSelectionPriceType(),
					'selection_price_value'    => $_item->getSelectionPriceValue(),
					'selection_qty'            => $_item->getSelectionQty(),
					'selection_can_change_qty' => $_item->getSelectionCanChangeQty(),
					'position'                 => $_item->getPosition(),
					'is_default'               => $_item->getIsDefault(),
					'delete'                   => '0',

				);
				$result[]   = $_selection;
			}
		} catch (Exception $e) {
			Mage::log($e->getMessage());
		}

		if (!count($result)) return null;

		return implode(self::MULTI_DELIMITER_SHORT, $result);
	}

	public function getBundleSelectionModel()
	{
		return Mage::getModel('bundle/selection');
	}

	protected function getBundleCollection()
	{
		$collection = $this->getBundleOptionModel()
			->getResourceCollection()
			->setProductIdFilter($this->getCurrentProduct()->getId());

		return $collection;
	}

	protected function getBundleOptionModel()
	{
		return Mage::getModel('bundle/option');
	}

}

