<?php


class Magegiant_Productimportexport_Model_Convert_Parser_Product
	extends Mage_Eav_Model_Convert_Parser_Abstract
{
	const MULTI_DELIMITER       = ' , ';
	const MULTI_DELIMITER_SHORT = ',';
	const OR_DELIMITER          = ';';
	const POSITION_DELIMITER    = ':';
	const SOURCE_DELIMITER      = '\\';
	const SLASH_DELIMITER       = '/';
	const EQUAL_DELIMITER       = '=';
	public $_resource;
	public $_total = 0;
	public $_product;
	public $_productType;
	protected static $_row = array();

	const ENTITY                          = 'catalog_product';
	const NOTICE                          = 'NOTICE';
	const WARNING                         = 'WARNING';
	const ERROR                           = 'ERROR';
	const FATAL                           = 'FATAL';
	const META_ROBOTS                     = 'meta_robots';
	const CANONICAL_URL                   = 'canonical_url';
	const EAV_ENTITY_ATTRIBUTE_COLLECTION = 'eav/entity_attribute_collection';
	const CATALOG_CATEGORY_PRODUCT_TABLE  = 'catalog_category_product';

	const VALUE_ALL = 'all';

	const CURRENT_PRODUCT = 'iep_current_product';


	/**
	 * Product collections per store
	 *
	 * @var array
	 */
	protected $_collections;

	/**
	 * Product Type Instances object cache
	 *
	 * @var array
	 */
	protected $_productTypeInstances = array();

	/**
	 * Product Type cache
	 *
	 * @var array
	 */
	protected $_productTypes;

	protected $_inventoryFields = array();

	protected $_imageFields = array();

	protected $_systemFields = array();
	protected $_internalFields = array();
	protected $_externalFields = array();

	protected $_inventoryItems = array();

	protected $_productModel;

	protected $_setInstances = array();

	protected $_store;
	protected $_storeId;
	protected $_attributes = array();


	public function __construct()
	{

		foreach (Mage::getConfig()->getFieldset('catalog_product_dataflow', 'admin') as $code => $node) {
			if ($node->is('inventory')) {
				$this->_inventoryFields[] = $code;
				if ($node->is('use_config')) {
					$this->_inventoryFields[] = 'use_config_' . $code;
				}
			}
			if ($node->is('internal')) {
				$this->_internalFields[] = $code;
			}
			if ($node->is('system')) {
				$this->_systemFields[] = $code;
			}
			if ($node->is('external')) {
				$this->_externalFields[$code] = $code;
			}
			if ($node->is('img')) {
				$this->_imageFields[] = $code;
			}
		}
		$this->setVar('entity_type', 'catalog/product');

	}


	/**
	 * @return Mage_Catalog_Model_Mysql4_Convert
	 */
	public function getResource()
	{
		if (!$this->_resource) {
			$this->_resource = Mage::getResourceSingleton('catalog_entity/convert');
		}

		return $this->_resource;
	}

	public function getCollection($storeId)
	{
		if (!isset($this->_collections[$storeId])) {
			$this->_collections[$storeId] = Mage::getResourceModel('catalog/product_collection');
			$this->_collections[$storeId]->getEntity()->setStore($storeId);
		}

		return $this->_collections[$storeId];
	}

	/**
	 * Retrieve product type options
	 *
	 * @return array
	 */
	public function getProductTypes()
	{
		if (is_null($this->_productTypes)) {
			$this->_productTypes = Mage::getSingleton('catalog/product_type')
				->getOptionArray();
		}

		return $this->_productTypes;
	}

	/**
	 * Retrieve Product type name by code
	 *
	 * @param string $code
	 * @return string
	 */
	public function getProductTypeName($code)
	{
		$productTypes = $this->getProductTypes();
		if (isset($productTypes[$code])) {
			return $productTypes[$code];
		}

		return false;
	}

	/**
	 * Retrieve product type code by name
	 *
	 * @param string $name
	 * @return string
	 */
	public function getProductTypeId($name)
	{
		$productTypes = $this->getProductTypes();
		if ($code = array_search($name, $productTypes)) {
			return $code;
		}

		return false;
	}

	/**
	 * Retrieve product model cache
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	public function getProductModel()
	{
		if (is_null($this->_productModel)) {
			$productModel        = Mage::getModel('catalog/product');
			$this->_productModel = Mage::objects()->save($productModel);
		}

		return Mage::objects()->load($this->_productModel);
	}

	/**
	 * Retrieve current store model
	 *
	 * @return Mage_Core_Model_Store
	 */
	public function getStore()
	{
		if (is_null($this->_store)) {
			try {
				$store = Mage::app()->getStore($this->getVar('store'));
			} catch (Exception $e) {
				$this->addException(
					Mage::helper('catalog')->__('Invalid store specified'),
					Varien_Convert_Exception::FATAL
				);
				throw $e;
			}
			$this->_store = $store;
		}

		return $this->_store;
	}

	/**
	 * Retrieve store ID
	 *
	 * @return int
	 */
	public function getStoreId()
	{
		if (is_null($this->_storeId)) {
			$this->_storeId = $this->getStore()->getId();
		}

		return $this->_storeId;
	}

	/**
	 * ReDefine Product Type Instance to Product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return Mage_Catalog_Model_Convert_Parser_Product
	 */
	public function setProductTypeInstance(Mage_Catalog_Model_Product $product)
	{
		$type = $product->getTypeId();
		if (!isset($this->_productTypeInstances[$type])) {
			$this->_productTypeInstances[$type] = Mage::getSingleton('catalog/product_type')
				->factory($product, true);
		}
		$product->setTypeInstance($this->_productTypeInstances[$type], true);

		return $this;
	}

	public function getAttributeSetInstance()
	{
		$productType    = $this->getProductModel()->getType();
		$attributeSetId = $this->getProductModel()->getAttributeSetId();

		if (!isset($this->_setInstances[$productType][$attributeSetId])) {
			$this->_setInstances[$productType][$attributeSetId] =
				Mage::getSingleton('catalog/product_type')->factory($this->getProductModel());
		}

		return $this->_setInstances[$productType][$attributeSetId];
	}

	/**
	 * Retrieve eav entity attribute model
	 *
	 * @param string $code
	 * @return Mage_Eav_Model_Entity_Attribute
	 */
	public function getAttribute($code)
	{
		if (!isset($this->_attributes[$code])) {
			$this->_attributes[$code] = $this->getProductModel()->getResource()->getAttribute($code);
		}

		return $this->_attributes[$code];
	}


	public function parse()
	{
		$data            = $this->getData();
		$entityTypeId    = Mage::getSingleton('eav/config')->getEntityType(self::ENTITY)->getId();
		$inventoryFields = array();

		foreach ($data as $i => $row) {
			$this->setPosition('Line: ' . ($i + 1));
			try {
				// validate SKU
				if (empty($row['sku'])) {
					$this->addException(
						Mage::helper('catalog')->__('Missing SKU, skipping the record.'),
						self::ERROR
					);
					continue;
				}
				$this->setPosition('Line: ' . ($i + 1) . ', SKU: ' . $row['sku']);

				// try to get entity_id by sku if not set
				if (empty($row['entity_id'])) {
					$row['entity_id'] = $this->getResource()->getProductIdBySku($row['sku']);
				}

				// if attribute_set not set use default
				if (empty($row['attribute_set'])) {
					$row['attribute_set'] = 'Default';
				}
				// get attribute_set_id, if not throw error
				$row['attribute_set_id'] = $this->getAttributeSetId($entityTypeId, $row['attribute_set']);
				if (!$row['attribute_set_id']) {
					$this->addException(
						Mage::helper('catalog')->__('Invalid attribute set specified, skipping the record.'),
						self::ERROR
					);
					continue;
				}

				if (empty($row['type'])) {
					$row['type'] = 'Simple';
				}
				// get product type_id, if not throw error
				$row['type_id'] = $this->getProductTypeId($row['type']);
				if (!$row['type_id']) {
					$this->addException(
						Mage::helper('catalog')->__('Invalid product type specified, skipping the record.'),
						self::ERROR
					);
					continue;
				}

				// get store ids
				$storeIds = $this->getStoreIds(isset($row['store']) ? $row['store'] : $this->getVar('store'));
				if (!$storeIds) {
					$this->addException(
						Mage::helper('catalog')->__('Invalid store specified, skipping the record.'),
						self::ERROR
					);
					continue;
				}

				// import data
				$rowError = false;
				foreach ($storeIds as $storeId) {
					$collection = $this->getCollection($storeId);
					$entity     = $collection->getEntity();

					$model = Mage::getModel('catalog/product');
					$model->setStoreId($storeId);
					if (!empty($row['entity_id'])) {
						$model->load($row['entity_id']);
					}
					foreach ($row as $field => $value) {
						$attribute = $entity->getAttribute($field);

						if (!$attribute) {
							//$inventoryFields[$row['sku']][$field] = $value;

							if (in_array($field, $this->_inventoryFields)) {
								$inventoryFields[$row['sku']][$field] = $value;
							}
							continue;
						}
						if ($attribute->usesSource()) {
							$source   = $attribute->getSource();
							$optionId = $this->getSourceOptionId($source, $value);
							if (is_null($optionId)) {
								$rowError = true;
								$this->addException(
									Mage::helper('catalog')->__('Invalid attribute option specified for attribute %s (%s), skipping the record.', $field, $value),
									self::ERROR
								);
								continue;
							}
							$value = $optionId;
						}
						$model->setData($field, $value);

					}
					//foreach ($row as $field=>$value)

					if (!$rowError) {
						$collection->addItem($model);
					}
					unset($model);
				} //foreach ($storeIds as $storeId)
			} catch (Exception $e) {
				if (!$e instanceof Mage_Dataflow_Model_Convert_Exception) {
					$this->addException(
						Mage::helper('catalog')->__('Error during retrieval of option value: %s', $e->getMessage()),
						self::FATAL
					);
				}
			}
		}

		// set importinted to adaptor
		if (sizeof($inventoryFields) > 0) {
			Mage::register('current_imported_inventory', $inventoryFields);
		} // end setting imported to adaptor

		$this->setData($this->_collections);

		return $this;
	}

	/**
	 * @param $items
	 */
	public function setInventoryItems($items)
	{
		$this->_inventoryItems = $items;
	}

	/**
	 * @return array
	 */
	public function getInventoryItems()
	{
		return $this->_inventoryItems;
	}

	/**
	 * get Start product number
	 * @return null
	 */
	public function getFromRow()
	{
		return $this->getVar('from_row');
	}

	/**
	 * Get end limit
	 * @return null
	 */
	public function getToRow()
	{
		return $this->getVar('to_row');
	}

	/**
	 * Get magento version
	 * @return string
	 */
	public function getMagentoVersion()
	{
		return Mage::getVersion();
	}

	/**
	 * get current product in rows
	 * @return mixed
	 */
	public function getCurrentProduct()
	{
		return Mage::registry(self::CURRENT_PRODUCT);
	}

	/**
	 * Set current product in rows
	 * @param $product
	 * @return mixed
	 */
	public function setCurrentProduct($product)
	{
		if (Mage::registry(self::CURRENT_PRODUCT))
			Mage::unregister(self::CURRENT_PRODUCT);

		Mage::register(self::CURRENT_PRODUCT, $product);

		return true;
	}

	/**
	 * Unparse (prepare data) loaded products
	 *
	 * @return Mage_Catalog_Model_Convert_Parser_Product
	 */
	public function unparse()
	{
		$this->_setVariables();
		$entityIds = $this->getData();
		foreach ($entityIds as $i => $entityId) {
			$this->setTotal($this->getTotal() + 1);
			if (!$this->_canUnparse()) continue;

			$product = $this->getNewProductModel()
				->setStoreId($this->getStoreId())
				->load($entityId);
			$this->setCurrentProduct($product);
			$this->setProductTypeInstance($product);
			/* @var $product Mage_Catalog_Model_Product */

			$position = Mage::helper('catalog')->__('Line %d, SKU: %s', ($i + 1), $product->getSku());
			$this->setPosition($position);

			$row = array(
				'store'         => $this->getStore()->getCode(),
				'websites'      => '',
				'attribute_set' => $this->getAttributeSetName(
						$product->getEntityTypeId(),
						$product->getAttributeSetId()),
				'type'          => $product->getTypeId(),
				'category_ids'  => join(self::MULTI_DELIMITER_SHORT, $product->getCategoryIds())
			);

			if ($this->getStore()->getCode() == Mage_Core_Model_Store::ADMIN_CODE) {
				$websiteCodes = array();
				foreach ($product->getWebsiteIds() as $websiteId) {
					$websiteCode                = Mage::app()->getWebsite($websiteId)->getCode();
					$websiteCodes[$websiteCode] = $websiteCode;
				}
				$row['websites'] = join(self::MULTI_DELIMITER_SHORT, $websiteCodes);
			} else {
				$row['websites'] = $this->getStore()->getWebsite()->getCode();
				if ($this->getVar('url_field')) {
					$row['url'] = $product->getProductUrl(false);
				}
			}

			foreach ($product->getData() as $field => $value) {
				if (in_array($field, $this->_systemFields) || is_object($value)) {
					continue;
				}

				$attribute = $this->getAttribute($field);
				if (!$attribute) {
					continue;
				}

				if ($attribute->usesSource()) {
						$option = $this->getOptionText($attribute, $value, $field);

					if ($value && empty($option) && $option != '0' AND !$this->isMetaRobots($field)) {
						$this->addException(
							Mage::helper('catalog')->__('Invalid option ID specified for %s (%s), skipping the record.', $field, $value),
							self::ERROR
						);
						continue;
					}
					if (is_array($option)) {
						$value = join(self::MULTI_DELIMITER, $option);
					} else {
						$value = $option;
					}
					unset($option);
				} elseif (is_array($value)) {
					continue;
				}

				$row[$field] = $value;
			}

			$row = $this->getExtraData($row);
			if ($stockItem = $product->getStockItem()) {
				foreach ($stockItem->getData() as $field => $value) {
					if (in_array($field, $this->_systemFields) || is_object($value)) {
						continue;
					}
					$row[$field] = $value;
				}
			}

			foreach ($this->_imageFields as $field) {
				if (isset($row[$field]) && $row[$field] == 'no_selection') {
					$row[$field] = null;
				}
			}
			$batchExport = $this->getBatchExportModel()
				->setId(null)
				->setBatchId($this->getBatchModel()->getId())
				->setBatchData($row)
				->setStatus(1)
				->save();
			$product->reset();
		}

		return $this;
	}

	/**
	 * Retrieve accessible external product attributes
	 *
	 * @return array
	 */
	public function getExternalAttributes()
	{
		$productAttributes = Mage::getResourceModel('catalog/product_attribute_collection')->load();
		$attributes        = $this->_externalFields;

		foreach ($productAttributes as $attr) {
			$code = $attr->getAttributeCode();
			if (in_array($code, $this->_internalFields) || $attr->getFrontendInput() == 'hidden') {
				continue;
			}
			$attributes[$code] = $code;
		}

		foreach ($this->_inventoryFields as $field) {
			$attributes[$field] = $field;
		}

		return $attributes;
	}

	/**
	 * get total
	 * @return int
	 */
	public function getTotal()
	{
		return $this->_total;
	}

	/**
	 * set total
	 * @param $total
	 * @return mixed
	 */
	public function setTotal($total)
	{
		$this->_total = $total;

		return $this->_total;
	}

	/**
	 * Check can unparse row
	 * @return bool
	 */
	public function _canUnparse()
	{
		return (($this->getFromRow() <= $this->getTotal()) AND ($this->getTotal() <= $this->getToRow()));
	}

	/**
	 * Get new catalog product model
	 * @return Mage_Catalog_Model_Product
	 */
	public function getNewProductModel()
	{
		return $this->getProductModel()->reset();
	}

	public function getProductTypeModel()
	{
		return Mage::getSingleton('catalog/product_type');
	}

	/**
	 * Check field is meta robots
	 * @param $value
	 * @return bool
	 */
	public function isMetaRobots($value)
	{
		return ($value == self::META_ROBOTS);
	}

	public function isCanonicalUrl($value)
	{
		return ($value == self::CANONICAL_URL);
	}

	public function isSEOExtensionDispatch($value)
	{
		return (!$this->isMetaRobots($value) AND !$this->isCanonicalUrl($value));
	}


	public function _canUseSource($attribute)
	{
		return is_object($attribute->usesSource());
	}


	public function getOptionText($attr, $value, $field)
	{
		$_option = '';
		if ($this->_canUseSource($attr)) {
			$_option = $attr->getSource()->getOptionText($value);
		} elseif ($this->isSEOExtensionDispatch($field)) {
			$_option = $this->_setEntity($field, $value);
		}

		return $_option;
	}

	public function getExtraData($row)
	{
		$this->_setRow($row);

		$event = array('row' => $this->_getRow(), 'product' => $this);

		$exportPrefix = 'productimportexport/convert_parser_product_';
		Mage::getSingleton($exportPrefix . 'gallery')->run();
		Mage::getSingleton($exportPrefix . 'category')->run();
		Mage::getSingleton($exportPrefix . 'tierPrice')->run();
		Mage::getSingleton($exportPrefix . 'groupprice')->run();
		Mage::getSingleton($exportPrefix . 'related')->run();
		Mage::getSingleton($exportPrefix . 'upsell')->run();
		Mage::getSingleton($exportPrefix . 'crosssell')->run();
		Mage::getSingleton($exportPrefix . 'configurable')->run();
		Mage::getSingleton($exportPrefix . 'grouped')->run();
		Mage::getSingleton($exportPrefix . 'bundle')->run();
		Mage::getSingleton($exportPrefix . 'downloadable')->run();
		Mage::getSingleton($exportPrefix . 'tag')->run();
		Mage::getSingleton($exportPrefix . 'customoptions')->run();
		Mage::getSingleton($exportPrefix . 'review')->run();

		Mage::dispatchEvent('ie_export_add_extra_after', $event);

		return $this->_getRow();
	}


	public function _getRow()
	{
		return self::$_row;
	}

	public function _setRow($row)
	{
		self::$_row = $row;

		return $row;
	}

	public function addToRow($key, $value)
	{
		self::$_row[$key] = $value;
	}

	public function _setEntity($field, $value)
	{
		$_product = $this->getCurrentProduct();
		$_attr    = Mage::getResourceModel(self::EAV_ENTITY_ATTRIBUTE_COLLECTION)
			->addFieldToFilter('attribute_code', $field)
			->setEntityTypeFilter($_product->getResource()->getTypeId())
			->getFirstItem()
			->setEntity($_product->getResource());
		$option   = $_attr->getSource()->getOptionText($value);

		return $option;
	}


	public function getCurrentProductType()
	{
		return $this->getCurrentProduct()->getTypeId();
	}


	public function getCoreResource()
	{
		return Mage::getSingleton('core/resource');
	}

	public function getTablePrefix()
	{
		return Mage::getConfig()->getTablePrefix();
	}

	public function _coreRead()
	{
		return $this->getCoreResource()->getConnection('core_read');
	}

	public function _coreWrite()
	{
		return $this->getCoreResource()->getConnection('core_write');
	}

	public function _getCategoryTable()
	{
		return $this->getTablePrefix() . self::CATALOG_CATEGORY_PRODUCT_TABLE;
	}

	public function getProductModelFactory()
	{
		return $this->getProductTypeModel()
			->factory($this->getCurrentProduct());
	}


	protected function _getVar($key)
	{
		$vars = Mage::registry('xml_get_vars');

		return isset($vars[$key]) ? $vars[$key] : null;
	}

	protected function _setVariables()
	{
		Mage::register('xml_get_vars', $this->getVars());
	}


}
