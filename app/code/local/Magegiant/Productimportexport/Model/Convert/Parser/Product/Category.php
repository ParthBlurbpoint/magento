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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Category extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{


	const COL_STORE         = '_store';
	const COL_ATTR_SET      = '_attribute_set';
	const COL_TYPE          = '_type';
	const COL_CATEGORY      = '_category';
	const COL_ROOT_CATEGORY = '_root_category';
	const COL_SKU           = 'sku';

	/**
	 * Pairs of attribute set ID-to-name.
	 *
	 * @var array
	 */
	protected $_attrSetIdToName = array();

	/**
	 * Categories ID to text-path hash.
	 *
	 * @var array
	 */
	protected $_categories = array();
	protected $_rootCategories = array();
	protected $_categoriesData = array();

	/**
	 * Attributes with index (not label) value.
	 *
	 * @var array
	 */
	protected $_indexValueAttributes = array(
		'status',
		'tax_class_id',
		'visibility',
		'enable_googlecheckout',
		'gift_message_available',
		'custom_design'
	);

	/**
	 * Can export category
	 * @return bool
	 */
	public function _canParse()
	{
		return true;
	}

	public function _canParseCategoryId()
	{
		return version_compare($this->getMagentoVersion(), '1.5.0.0', '<');
	}

	/**
	 * Check config Export category
	 * @return bool
	 */
	public function isExportCategory()
	{
		return ($this->_getVar('export_category') == 'true');
	}

	/**
	 * Parse category
	 */
	public function parseCategories()
	{
		if (!$this->_canParse()) return;
		$categoriesIds = $this->_getAllCategories();
		if (!count($categoriesIds)) return;
		if ($this->_canParseCategoryId())
			$this->addToRow('category_ids', implode(self::MULTI_DELIMITER_SHORT, $categoriesIds));

		$this->_prepareCategoryData($categoriesIds);

		$this->addToRow('category_name_read_only', implode(self::SLASH_DELIMITER, $this->_prepareCategoryData($categoriesIds)));
		$this->addToRow('category_data', Mage::helper('core')->jsonEncode($this->_categoriesData));

	}

	public function _getAllCategories()
	{
		$query         = "SELECT `category_id` FROM " . $this->_getCategoryTable() . ' WHERE product_id = ' . $this->getCurrentProduct()->getId();
		$results       = $this->_coreRead()->fetchAll($query);
		$categoriesIds = array();
		foreach ($results as $k => $v) {
			$categoriesIds[] = $v['category_id'];
		}

		return $categoriesIds;
	}

	public function _getAllCategoryName($ids)
	{
		$return = array();
		foreach ($ids as $id) {
			$_category = Mage::getSingleton('catalog/category')->load($id);
			if ($_category AND $_category->getId())
				$return[] = $_category->getName();
		}

		return $return;
	}

	protected function _prepareCategoryData($categoryIds)
	{
		$model        = Mage::getSingleton('catalog/category');
		$collection   = $model->getCollection()
			->addAttributeToSelect('name')
			->addAttributeToFilter('entity_id', array($categoryIds, 'IN'));
		$result       = array();
		$categoryData = array();
		foreach ($collection as $category) {
			/**
			 * Save category_data col, use for import easily
			 */
			$categoryData[] = $this->getCategoryExportData($category);

			$structure = preg_split('#/+#', $category->getPath());
			$pathSize  = count($structure);
			if ($pathSize > 1) {
				$path = array();
				for ($i = 1; $i < $pathSize; $i++) {
					$path[] = Mage::getModel('catalog/category')
						->load($structure[$i])->getName();
				}
				$result[] = array_shift($path);
				if ($pathSize > 2) {
					$result[] = implode(self::SLASH_DELIMITER, $path);

				}
			}
		}
		$this->_categoriesData = $categoryData;

		//end foreach
		return $result;
	}

	public function getCategoryExportData($category)
	{
		return array(
			'id'         => $category->getId(),
			'name'       => $category->getName(),
			'path'       => $category->getPath(),
			'parent_id'  => $category->getParentId(),
//			'createAt'   => $category->getCreatedAt(),
//			'updated_at' => $category->getUpdatedAt(),
//			'position'   => $category->getPosition(),
//			'level'      => $category->getLevel(),
		);

	}

	//function

	public function run()
	{
		return $this->parseCategories();
	}
}//class