<?php


class Magegiant_Productimportexport_Model_Convert_Adapter_Product
	extends Mage_Eav_Model_Convert_Adapter_Entity
{
	const MULTI_DELIMITER = ' , ';
	const MULTI_DELIMITER_SHORT = ',';
	const POSITION_DELIMITER = ':';
	const OR_DELIMITER = ';';
	const EQUAL_DELIMITER = '=';
	const ENTITY = 'catalog_product_import';
	const ROOT_PATH_PREFIX = '1/';
	const ACTIVE_STATUS = 1;
	const DEACTIVE_STATUS = 2;


	protected $_isNew = false;

	/**
	 * Event prefix
	 *
	 * @var string
	 */
	protected $_eventPrefix = 'catalog_product_import';

	/**
	 * Product model
	 *
	 * @var Mage_Catalog_Model_Product
	 */
	protected $_productModel;

	/**
	 * product types collection array
	 *
	 * @var array
	 */
	protected $_productTypes;

	/**
	 * Product Type Instances singletons
	 *
	 * @var array
	 */
	protected $_productTypeInstances = array();

	/**
	 * product attribute set collection array
	 *
	 * @var array
	 */
	protected $_productAttributeSets;

	protected $_stores;

	protected $_attributes = array();

	protected $_configs = array();

	protected $_requiredFields = array();

	protected $_ignoreFields = array();

	protected $_currentProduct;


	/**
	 * @deprecated after 1.5.0.0-alpha2
	 *
	 * @var array
	 */
	protected $_imageFields = array();

	/**
	 * Inventory Fields array
	 *
	 * @var array
	 */
	protected $_inventoryFields = array();

	/**
	 * Inventory Fields by product Types
	 *
	 * @var array
	 */
	protected $_inventoryFieldsProductTypes = array();

	protected $_toNumber = array();

	protected $_importData = array();

	/**
	 * Retrieve event prefix for adapter
	 *
	 * @return string
	 */
	public function getEventPrefix()
	{
		return $this->_eventPrefix;
	}

	/**
	 * Affected entity ids
	 *
	 * @var array
	 */
	protected $_affectedEntityIds = array();

	/**
	 * Store affected entity ids
	 *
	 * @param  int|array $ids
	 *
	 * @return Mage_Catalog_Model_Convert_Adapter_Product
	 */
	protected function _addAffectedEntityIds($ids)
	{
		if (is_array($ids)) {
			foreach ($ids as $id) {
				$this->_addAffectedEntityIds($id);
			}
		} else {
			$this->_affectedEntityIds[] = $ids;
		}

		return $this;
	}

	/**
	 * Retrieve affected entity ids
	 *
	 * @return array
	 */
	public function getAffectedEntityIds()
	{
		return $this->_affectedEntityIds;
	}

	/**
	 * Clear affected entity ids results
	 *
	 * @return Mage_Catalog_Model_Convert_Adapter_Product
	 */
	public function clearAffectedEntityIds()
	{
		$this->_affectedEntityIds = array();

		return $this;
	}

	/**
	 * Load product collection Id(s)
	 */
	public function load()
	{
		$attrFilterArray                   = array();
		$attrFilterArray ['name']          = 'like';
		$attrFilterArray ['sku']           = 'startsWith';
		$attrFilterArray ['type']          = 'eq';
		$attrFilterArray ['attribute_set'] = 'eq';
		$attrFilterArray ['visibility']    = 'eq';
		$attrFilterArray ['status']        = 'eq';
		$attrFilterArray ['price']         = 'fromTo';
		$attrFilterArray ['qty']           = 'fromTo';
		$attrFilterArray ['store_id']      = 'eq';

		$attrToDb = array(
			'type'          => 'type_id',
			'attribute_set' => 'attribute_set_id'
		);

		$filters = $this->_parseVars();

		if ($qty = $this->getFieldValue($filters, 'qty')) {
			$qtyFrom = isset($qty['from']) ? (float)$qty['from'] : 0;
			$qtyTo   = isset($qty['to']) ? (float)$qty['to'] : 0;

			$qtyAttr              = array();
			$qtyAttr['alias']     = 'qty';
			$qtyAttr['attribute'] = 'cataloginventory/stock_item';
			$qtyAttr['field']     = 'qty';
			$qtyAttr['bind']      = 'product_id=entity_id';
			$qtyAttr['cond']      = "{{table}}.qty between '{$qtyFrom}' AND '{$qtyTo}'";
			$qtyAttr['joinType']  = 'inner';

			$this->setJoinField($qtyAttr);
		}

		parent::setFilter($attrFilterArray, $attrToDb);

		if ($price = $this->getFieldValue($filters, 'price')) {
			$this->_filter[] = array(
				'attribute' => 'price',
				'from'      => $price['from'],
				'to'        => $price['to']
			);
			$this->setJoinAttr(array(
				'alias'     => 'price',
				'attribute' => 'catalog_product/price',
				'bind'      => 'entity_id',
				'joinType'  => 'LEFT'
			));
		}

		return parent::load();
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
			$this->_isNew        = true;
		} else {
			$this->_isNew = false;
		}

		return Mage::objects()->load($this->_productModel);
	}


	public function setProductModel($model)
	{
		$this->_productModel = Mage::objects()->save($model);
	}

	/**
	 * Retrieve eav entity attribute model
	 *
	 * @param string $code
	 *
	 * @return Mage_Eav_Model_Entity_Attribute
	 */
	public function getAttribute($code)
	{
		if (!isset($this->_attributes[$code])) {
			$this->_attributes[$code] = $this->getProductModel()->getResource()->getAttribute($code);
		}
		if ($this->_attributes[$code] instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
			$applyTo = $this->_attributes[$code]->getApplyTo();
			if ($applyTo && !in_array($this->getProductModel()->getTypeId(), $applyTo)) {
				return false;
			}
		}

		return $this->_attributes[$code];
	}

	/**
	 * Retrieve product type collection array
	 *
	 * @return array
	 */
	public function getProductTypes()
	{
		if (is_null($this->_productTypes)) {
			$this->_productTypes = array();
			$options             = Mage::getModel('catalog/product_type')
				->getOptionArray();
			foreach ($options as $k => $v) {
				$this->_productTypes[$k] = $k;
			}
		}

		return $this->_productTypes;
	}

	/**
	 * ReDefine Product Type Instance to Product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 *
	 * @return Mage_Catalog_Model_Convert_Adapter_Product
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

	/**
	 * Retrieve product attribute set collection array
	 *
	 * @return array
	 */
	public function getProductAttributeSets()
	{
		if (is_null($this->_productAttributeSets)) {
			$this->_productAttributeSets = array();

			$entityTypeId = Mage::getModel('eav/entity')
				->setType('catalog_product')
				->getTypeId();
			$collection   = Mage::getResourceModel('eav/entity_attribute_set_collection')
				->setEntityTypeFilter($entityTypeId);
			foreach ($collection as $set) {
				$this->_productAttributeSets[$set->getAttributeSetName()] = $set->getId();
			}
		}

		return $this->_productAttributeSets;
	}

	/**
	 *  Init stores
	 */
	protected function _initStores()
	{
		if (is_null($this->_stores)) {
			$this->_stores = Mage::app()->getStores(true, true);
			foreach ($this->_stores as $code => $store) {
				$this->_storesIdCode[$store->getId()] = $code;
			}
		}
	}

	/**
	 * Retrieve store object by code
	 *
	 * @param string $store
	 *
	 * @return Mage_Core_Model_Store
	 */
	public function getStoreByCode($store)
	{
		$this->_initStores();
		/**
		 * In single store mode all data should be saved as default
		 */
		if (Mage::app()->isSingleStoreMode()) {
			return Mage::app()->getStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
		}

		if (isset($this->_stores[$store])) {
			return $this->_stores[$store];
		}

		return false;
	}

	/**
	 * Retrieve store object by code
	 *
	 * @param string $store
	 *
	 * @return Mage_Core_Model_Store
	 */
	public function getStoreById($id)
	{
		$this->_initStores();
		/**
		 * In single store mode all data should be saved as default
		 */
		if (Mage::app()->isSingleStoreMode()) {
			return Mage::app()->getStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
		}

		if (isset($this->_storesIdCode[$id])) {
			return $this->getStoreByCode($this->_storesIdCode[$id]);
		}

		return false;
	}

	public function parse()
	{
		$batchModel = Mage::getSingleton('dataflow/batch');
		/* @var $batchModel Mage_Dataflow_Model_Batch */

		$batchImportModel = $batchModel->getBatchImportModel();
		$importIds        = $batchImportModel->getIdCollection();

		foreach ($importIds as $importId) {
			$batchImportModel->load($importId);
			$importData = $batchImportModel->getBatchData();

			$this->saveRow($importData);
		}
	}

	protected $_productId = '';

	/**
	 * Initialize convert adapter model for products collection
	 *
	 */
	public function __construct()
	{
		$fieldset = Mage::getConfig()->getFieldset('catalog_product_dataflow', 'admin');
		foreach ($fieldset as $code => $node) {
			/* @var $node Mage_Core_Model_Config_Element */
			if ($node->is('inventory')) {
				foreach ($node->product_type->children() as $productType) {
					$productType                                        = $productType->getName();
					$this->_inventoryFieldsProductTypes[$productType][] = $code;
					if ($node->is('use_config')) {
						$this->_inventoryFieldsProductTypes[$productType][] = 'use_config_' . $code;
					}
				}

				$this->_inventoryFields[] = $code;
				if ($node->is('use_config')) {
					$this->_inventoryFields[] = 'use_config_' . $code;
				}
			}
			if ($node->is('required')) {
				$this->_requiredFields[] = $code;
			}
			if ($node->is('ignore')) {
				$this->_ignoreFields[] = $code;
			}
			if ($node->is('to_number')) {
				$this->_toNumber[] = $code;
			}
		}

		$this->setVar('entity_type', 'catalog/product');
		if (!Mage::registry('Object_Cache_Product')) {
			$this->setProduct(Mage::getModel('catalog/product'));
		}

		if (!Mage::registry('Object_Cache_StockItem')) {
			$this->setStockItem(Mage::getModel('cataloginventory/stock_item'));
		}
	}

	/**
	 * Retrieve not loaded collection
	 *
	 * @param string $entityType
	 *
	 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
	 */
	protected function _getCollectionForLoad($entityType)
	{
		$collection = parent::_getCollectionForLoad($entityType)
			->setStoreId($this->getStoreId())
			->addStoreFilter($this->getStoreId());

		return $collection;
	}

	public function setProduct(Mage_Catalog_Model_Product $object)
	{
		$id = Mage::objects()->save($object);
		//$this->_product = $object;
		Mage::register('Object_Cache_Product', $id);
	}

	public function getProduct()
	{
		return Mage::objects()->load(Mage::registry('Object_Cache_Product'));
	}

	public function setStockItem(Mage_CatalogInventory_Model_Stock_Item $object)
	{
		$id = Mage::objects()->save($object);
		Mage::register('Object_Cache_StockItem', $id);
	}

	public function getStockItem()
	{
		return Mage::objects()->load(Mage::registry('Object_Cache_StockItem'));
	}

	public function save()
	{
		$stores = array();
		foreach (Mage::getConfig()->getNode('stores')->children() as $storeNode) {
			$stores[(int)$storeNode->system->store->id] = $storeNode->getName();
		}

		$collections = $this->getData();
		if ($collections instanceof Mage_Catalog_Model_Entity_Product_Collection) {
			$collections = array($collections->getEntity()->getStoreId() => $collections);
		} elseif (!is_array($collections)) {
			$this->addException(
				Mage::helper('catalog')->__('No product collections found.'),
				Mage_Dataflow_Model_Convert_Exception::FATAL
			);
		}

		$stockItems = Mage::registry('current_imported_inventory');
		if ($collections) foreach ($collections as $storeId => $collection) {
			$this->addException(Mage::helper('catalog')->__('Records for "' . $stores[$storeId] . '" store found.'));

			if (!$collection instanceof Mage_Catalog_Model_Entity_Product_Collection) {
				$this->addException(
					Mage::helper('catalog')->__('Product collection expected.'),
					Mage_Dataflow_Model_Convert_Exception::FATAL
				);
			}
			try {
				$i = 0;
				foreach ($collection->getIterator() as $model) {
					$new = false;
					// if product is new, create default values first
					if (!$model->getId()) {
						$new = true;
						$model->save();

						// if new product and then store is not default
						// we duplicate product as default product with store_id -
						if (0 !== $storeId) {
							$data    = $model->getData();
							$default = Mage::getModel('catalog/product');
							$default->setData($data);
							$default->setStoreId(0);
							$default->save();
							unset($default);
						} // end

						#Mage::getResourceSingleton('catalog_entity/convert')->addProductToStore($model->getId(), 0);
					}
					if (!$new || 0 !== $storeId) {
						if (0 !== $storeId) {
							Mage::getResourceSingleton('catalog_entity/convert')->addProductToStore(
								$model->getId(),
								$storeId
							);
						}
						$model->save();
					}

					if (isset($stockItems[$model->getSku()]) && $stock = $stockItems[$model->getSku()]) {
						$stockItem   = Mage::getModel('cataloginventory/stock_item')->loadByProduct($model->getId());
						$stockItemId = $stockItem->getId();

						if (!$stockItemId) {
							$stockItem->setData('product_id', $model->getId());
							$stockItem->setData('stock_id', 1);
							$data = array();
						} else {
							$data = $stockItem->getData();
						}

						foreach ($stock as $field => $value) {
							if (!$stockItemId) {
								if (in_array($field, $this->_configs)) {
									$stockItem->setData('use_config_' . $field, 0);
								}
								$stockItem->setData($field, $value ? $value : 0);
							} else {

								if (in_array($field, $this->_configs)) {
									if ($data['use_config_' . $field] == 0) {
										$stockItem->setData($field, $value ? $value : 0);
									}
								} else {
									$stockItem->setData($field, $value ? $value : 0);
								}
							}
						}
						$stockItem->save();
						unset($data);
						unset($stockItem);
						unset($stockItemId);
					}
					unset($model);
					$i++;
				}
				$this->addException(Mage::helper('catalog')->__("Saved %d record(s)", $i));
			} catch (Exception $e) {
				if (!$e instanceof Mage_Dataflow_Model_Convert_Exception) {
					$this->addException(
						Mage::helper('catalog')->__('An error occurred while saving the collection, aborting. Error message: %s', $e->getMessage()),
						Mage_Dataflow_Model_Convert_Exception::FATAL
					);
				}
			}
		}
		unset($collections);

		return $this;
	}

	/**
	 *
	 * Save product (import)
	 *
	 * @param  array $importData
	 *
	 * @throws Mage_Core_Exception
	 * @return bool
	 */
	public function saveRow(array $importData)
	{
		$this->_importData = $importData;
		$this->_beforeQuery();
		$product = $this->getProductModel()
			->reset();
		$_config = Mage::getSingleton('productimportexport/config');
		$_helper = Mage::helper('productimportexport');


		if (empty($importData['store'])) {
			if (!is_null($this->getBatchParams('store'))) {
				$store = $this->getStoreById($this->getBatchParams('store'));
			} else {
				$message = Mage::helper('catalog')->__('Skipping import row, required field "%s" is not defined.', 'store');
				Mage::throwException($message);
			}
		} else {
			$store = $this->getStoreByCode($importData['store']);
		}

		if ($store === false) {
			$message = Mage::helper('catalog')->__('Skipping import row, store "%s" field does not exist.', $importData['store']);
			Mage::throwException($message);
		}

		if (empty($importData['sku']) AND !$_config->getGenerateSku()) {
			$message = Mage::helper('catalog')->__('Skipping import row, required field "%s" is not defined.', 'sku');
			Mage::throwException($message);
		}

		/**
		 * empty price
		 */
		if (!isset($importData['price']) OR empty($importData['price'])) {
			$importData['price'] = 0.00;
		}
		/**
		 * Set active product as default
		 */
		if (!isset($importData['status']) OR !$importData['status'] OR empty($importData['status'])) {
			$importData['status'] = Mage::helper('productimportexport')->__('Enabled'); //Enabled or 1
		}

		/**
		 * Automatically generate URL
		 */
		if ($_config->getGenerateUrl() AND !isset($importData['url_key']) OR empty($importData['url_key'])) {
			if (!empty($importData['name']))
				$importData['url_key'] = $_helper->slug($importData['name'] . '-' . $importData['sku']);
			else
				$importData['url_key'] = $importData['sku'];
		}

		/**
		 * Automatically generate SKU
		 */
		if ($_config->getGenerateSku() AND !isset($importData['sku']) OR empty($importData['sku'])) {
			$importData['sku'] = $_helper->randKey($_config->getGenerateSkuLength());
		}

		/**
		 * htmlspecialchars_decode() short_description, description
		 */
		if (isset($importData['short_description']) && !empty($importData['short_description'])) {
			$importData['short_description'] = $this->decode_htmlspecialchars($importData['short_description']);
		}

		if (isset($importData['description']) && !empty($importData['description'])) {
			$importData['description'] = $this->decode_htmlspecialchars($importData['description']);
		}

		/**
		 * Only set for new product
		 */
		if ($this->isNew() AND !isset($importData['visibility']) OR empty($importData['visibility'])) {
			$importData['visibility'] = $this->getProductNotVisibleCode();
		}


		$product->setStoreId($store->getId());
		$productId = $product->getIdBySku($importData['sku']);

		if ($productId) {
			$product->load($productId);
		} else {
			$productTypes         = $this->getProductTypes();
			$productAttributeSets = $this->getProductAttributeSets();

			/**
			 * Check product define type
			 */
			if (empty($importData['type']) || !isset($productTypes[strtolower($importData['type'])])) {
				$value   = isset($importData['type']) ? $importData['type'] : '';
				$message = Mage::helper('catalog')->__('Skip import row, is not valid value "%s" for field "%s"', $value, 'type');
				Mage::throwException($message);
			}

			$product->setTypeId($productTypes[strtolower($importData['type'])]);

			/**
			 * Check product define attribute set
			 */
			if (empty($importData['attribute_set']) || !isset($productAttributeSets[$importData['attribute_set']])) {
				$value   = isset($importData['attribute_set']) ? $importData['attribute_set'] : '';
				$message = Mage::helper('catalog')->__('Skip import row, the value "%s" is invalid for field "%s"', $value, 'attribute_set');
				Mage::throwException($message);
			}
			$product->setAttributeSetId($productAttributeSets[$importData['attribute_set']]);

			foreach ($this->_requiredFields as $field) {
				$attribute = $this->getAttribute($field);
				if (!isset($importData[$field]) && $attribute && $attribute->getIsRequired()) {
					$message = Mage::helper('catalog')->__('Skipping import row, required field "%s" for new products is not defined.', $field);
					Mage::throwException($message);
				}
			}
		}

		$this->setProductTypeInstance($product);

		if ($this->checkDeleteCurrentProduct()) return true;

		if (isset($importData['category_ids'])) {
			$product->setCategoryIds($importData['category_ids']);
		}

		$this->importConfigurableProducts();
		$this->importGroupedProducts();
		$this->importDownloadableProducts();
		$this->importBundleProducts();

		$this->importCrosssell();
		$this->importUpsell();
		$this->importRelated();

		$this->importTierPrice();
		$this->importCategory();
		$this->importCustomOptions();

		$this->importImages();
		$this->importGallery();
		$this->importTags();

		$this->importGroupedPrice();


		foreach ($this->_ignoreFields as $field) {
			if (isset($importData[$field])) {
				unset($importData[$field]);
			}
		}

		if ($store->getId() != 0) {
			$websiteIds = $product->getWebsiteIds();
			if (!is_array($websiteIds)) {
				$websiteIds = array();
			}
			if (!in_array($store->getWebsiteId(), $websiteIds)) {
				$websiteIds[] = $store->getWebsiteId();
			}
			$product->setWebsiteIds($websiteIds);
		}

		if (isset($importData['websites'])) {
			$websiteIds = $product->getWebsiteIds();
			if (!is_array($websiteIds) || !$store->getId()) {
				$websiteIds = array();
			}
			$websiteCodes = explode(',', $importData['websites']);
			foreach ($websiteCodes as $websiteCode) {
				try {
					$website = Mage::app()->getWebsite(trim($websiteCode));
					if (!in_array($website->getId(), $websiteIds)) {
						$websiteIds[] = $website->getId();
					}
				} catch (Exception $e) {
				}
			}
			$product->setWebsiteIds($websiteIds);
			unset($websiteIds);
		}

		foreach ($importData as $field => $value) {
			if (in_array($field, $this->_inventoryFields)) {
				continue;
			}
			if (is_null($value)) {
				continue;
			}

			$attribute = $this->getAttribute($field);
			if (!$attribute) {
				continue;
			}

			$isArray  = false;
			$setValue = $value;

			if ($attribute->getFrontendInput() == 'multiselect') {
				$value    = explode(self::MULTI_DELIMITER, $value);
				$isArray  = true;
				$setValue = array();
			}

			if ($value && $attribute->getBackendType() == 'decimal') {
				$setValue = $this->getNumber($value);
			}

			if ($attribute->usesSource()) {
				$options = $attribute->getSource()->getAllOptions(false);

				if ($isArray) {
					foreach ($options as $item) {
						if (in_array($item['label'], $value)) {
							$setValue[] = $item['value'];
						}
					}
				} else {
					$setValue = false;
					foreach ($options as $item) {
						if (is_array($item['value'])) {
							foreach ($item['value'] as $subValue) {
								if (isset($subValue['value']) && $subValue['value'] == $value) {
									$setValue = $value;
								}
							}
						} else if ($item['label'] == $value) {
							$setValue = $item['value'];
						}
					}
				}
			}

			$product->setData($field, $setValue);
		}


		$stockData       = array();
		$inventoryFields = isset($this->_inventoryFieldsProductTypes[$product->getTypeId()])
			? $this->_inventoryFieldsProductTypes[$product->getTypeId()]
			: array();
		foreach ($inventoryFields as $field) {
			if (isset($importData[$field])) {
				if (in_array($field, $this->_toNumber)) {
					$stockData[$field] = $this->getNumber($importData[$field]);
				} else {
					$stockData[$field] = $importData[$field];
				}
			}
		}
		$product->setStockData($stockData);

		$mediaGalleryBackendModel = $this->getAttribute('media_gallery')->getBackend();

		$arrayToMassAdd = array();

		$this->importImages();


		$product->setIsMassupdate(true);
		$product->setExcludeUrlRewrite(true);

		try {
			$product->save();
		} catch (Exception $e) {
			$this->log($e, $this);
		}

		// Store affected products ids
		$this->_addAffectedEntityIds($product->getId());

		$productModel = Mage::getModel('catalog/product')->load($product->getId());
		$this->setProductModel($productModel);

		/**
		 * Import extra data after primportReviewoduct save
		 */
		$this->importReview();


		try {
			$product->save();
		} catch (Exception $e) {
			$this->log($e, $this);
		}
		$this->_afterQuery();

		return true;
	}

	/**
	 * Silently save product (import)
	 *
	 * @param  array $importData
	 *
	 * @return bool
	 */
	public function saveRowSilently(array $importData)
	{
		try {
			$result = $this->saveRow($importData);

			return $result;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Process after import data
	 * Init indexing process after catalog product import
	 */
	public function finish()
	{
		/**
		 * Back compatibility event
		 */
		Mage::dispatchEvent($this->_eventPrefix . '_after', array());

		$entity = new Varien_Object();
		Mage::getSingleton('index/indexer')->processEntityAction(
			$entity, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
		);
	}

	protected function _getVar($key)
	{
		return $this->getBatchParams($key);
	}

	public function getCurrentStoreId()
	{
		if (!isset($this->_importData['store']) OR empty($this->_importData['store'])) {
			Mage::throwException(Mage::helper('catalog')->__('Skip import row, required field "store" for new products not defined'));

			return null;
		}

		return $this->_stores[$this->_importData['store']];

	}

	public function getCurrentWebsiteId()
	{
		return Mage::app()->getStore()->getWebsiteId();
	}


	public function checkDeleteCurrentProduct()
	{
		if (!isset($this->_importData['status']) OR empty($this->_importData['status'])) return;
		if ($this->_importData['status'] == 'delete' OR $this->_importData['status'] == 'remove') {
			$this->_deleteCurrentProduct();

			return true;
		} else {
			return false;
		}
	}

	protected function _deleteCurrentProduct()
	{
		$_product = $this->getProductModel();
		$this->_deleteMedia();
		try {
			$_product->delete();
		} catch (Exception $e) {
			$this->log('Cannot delete product: #' . $_product->getId());
		}
	}


	public function _deleteMedia()
	{
		$_product = $this->getProductModel();
		$files    = array(
			Mage::getSingleton('catalog/product_media_config')->getMediaPath($_product->getImage()),
			Mage::getSingleton('catalog/product_media_config')->getMediaPath($_product->getSmallImage()),
			Mage::getSingleton('catalog/product_media_config')->getMediaPath($_product->getThumbnail()),
		);
		$gallery  = $_product->getMediaGallery();
		if (isset($gallery['images']) && count($gallery['images'])) {
			foreach ($gallery['images'] as $_img) {
				$files[] = $_img['file'];
			}
		}
		$this->_deleteFiles($files);
	}

	public function _deleteFiles($files = array())
	{
		if (empty($files)) return;
		foreach ($files as $_file) {
			if (!is_file($_file)) continue;
			try {
				unlink($_file);
			} catch (Exception $e) {
				$this->log('Cannot delete file: ' . $_file . ' because of ' . $e->getMessage());
			}
		}
	}


	/**
	 * Start import Configurable products
	 */

	public function importConfigurableProducts()
	{

		if ($this->_importData['type'] != 'configurable') return;
		//get attributes code
		$attributes = $this->_importData[Magegiant_Productimportexport_Helper_Data::COL_CONFIGURABLE_ATTRIBUTES];
		if (empty($attributes)) return;
		$attributes = explode(self::MULTI_DELIMITER_SHORT, $attributes);
		if (is_string($attributes)) $attributes = array($attributes);

		$_product = Mage::getModel('catalog/product')->load($this->getProductModel()->getId());
		$_product->setCanSaveConfigurableAttributes(true);

		//get attribute Ids
		$attributeIds = $this->_getAttributeIds($attributes);
		if (empty($attributeIds)) return;

		//update attributes
		$_product->getTypeInstance()->setUsedProductAttributeIds($attributeIds);
		$attributesArray = $_product->getTypeInstance()->getConfigurableAttributesAsArray();
		for ($i = 0; $i < count($_product->getTypeInstance()->getConfigurableAttributesAsArray()); $i++) {
			$attributesArray[$i]['label'] = $attributesArray[$i]['frontend_label'];
			$attributesArray[$i]          = $this->_setDefaultConfigurable($attributesArray[$i]);
		}

		$_product->setConfigurableAttributesData($attributesArray);
		$_product->setCanSaveConfigurableAttributes(true);

		//Import associated
		$this->_importConfigurableAssociated();
		$this->_importSuperPricing();

	}


	public function _setDefaultConfigurable($attribute)
	{
		$default = $this->getBatchParams('set_default_configurable');
		if (('' == $default)) return $attribute;
		$attribute['use_default'] = $default;

		return $attribute;
	}

	public function _getAttributeIds($configurableAttributeCodes)
	{
		$return = array();
		if (empty($configurableAttributeCodes)) return $return;
		$_product = $this->getProductModel();
		foreach ($configurableAttributeCodes as $code) {
			$_attribute = $_product->getResource()->getAttribute($code);
			if (!$_product->getTypeInstance()->canUseAttribute($_attribute)) continue;
//			if (!$this->isNew()) continue;
			$return[] = $_attribute->getAttributeId();
		}

		return $return;
	}


	public function _importConfigurableAssociated()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_CONFIGURABLE_ASSOCIATED;
		if (isset($this->_importData[$col]) && empty($this->_importData[$col])) return;
		$data = $this->_importData[$col];
		$sku  = explode(self::MULTI_DELIMITER_SHORT, $data);
		$ids  = $this->getIdBySku($sku);
		$this->getProductModel()->setConfigurableProductsData($ids);
	}

	/**
	 * Import super pricing
	 */
	public function _importSuperPricing()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_CONFIGURABLE_SUPER_PRICING;
		if (isset($this->_importData[$col]) && empty($this->_importData[$col])) return;
		$prices     = $this->_parseSuperPricing($this->_importData[$col]);
		$_product   = $this->getProductModel();
		$attributes = $_product->getTypeInstance()->getConfigurableAttributesAsArray();
		foreach ($attributes as $att_key => $value):
			foreach ($value['values'] as $_value_key => $_value_value):
				foreach ($prices as $_price):
					if ($_price['label'] != $attributes[$att_key][$_value_key]['label']) continue;
					$_item = $attributes[$att_key]['values'][$_value_key];

					if ($value_id = $this->existSuperPrice($_item)) { // update
						$query = "UPDATE " . $this->getCoreResource()->getTableName('catalog/product_super_attribute_pricing') .
							"SET pricing_value = '" . $_item['pricing_value'] . "'" . " WHERE value_id = '" . $value_id . "'";
						try {
							$this->_coreWrite()->query($query);
						} catch (Exception $e) {
						}

					} else { //add new
						$query = "INSERT INTO " . $this->getCoreResource()->getTableName('catalog/product_super_attribute_pricing') .
							' (product_super_attribute_id, value_index, is_percent, pricing_value) VALUES ' .
							"(" .
							"'" . $_item['product_super_attribute_id'] . "', '" . $_item['value_index'] . "', '" . $_price['is_percent'] . "', '" . $_price['pricing_value'] . "'" .
							')';
						try {
							$this->_coreWrite()->query($query);
						} catch (Exception $e) {
						}
					}//end if isNew()

				endforeach;
			endforeach;//$value

		endforeach; //$attributes
		return $this;

	}

	public function existSuperPrice($data)
	{
		$query = "SELECT value_id FROM " . $this->getCoreResource()->getTableName('catalog/product_super_attribute_pricing') . " WHERE `product_super_attribute_id` = '" . $data['product_super_attribute_id'] . "' AND `value_index` = '" . $data['value_index'] . "'";
		$row   = $this->_coreRead()->fetch($query);

		return (isset($row['value_id']) ? $row['value_id'] : false);
	}

	public function _parseSuperPricing($data)
	{
		$return = array();
		$prices = explode(self::OR_DELIMITER, $data);
		foreach ($prices as $_price) {
			$explode = explode(self::POSITION_DELIMITER, $_price);
			$_item   = array(
				'label'         => '',
				'pricing_value' => '',
				'is_percent'    => '',
			);
			if (!isset($explode[0])) $return[] = $_item;

			$return[] = array(
				'label'         => $explode[0],
				'pricing_value' => $explode[1],
				'is_percent'    => $explode[2],
			);
		}

		return $return;
	}

	/**
	 * Import category
	 */

	public function canImportCategoryId()
	{
		return version_compare(Mage::getVersion(), '1.5.0.0', '<');
	}

	public function canImportCategory()
	{
		return ($this->getBatchParams('append_category') == 'true');
	}

	public function importCategory()
	{

		if (!$this->canImportCategory()) return;
		if (!isset($this->_importData['category_name']) OR empty($this->_importData['category_name'])) return;
		$this->_importCategoryName();

	}

	public function _importCategoryName()
	{
		$categories  = $this->getCategoryData();
		$categoryIds = array();
		foreach ($categories as $_category) {
			$id = $this->_addCategory($_category);
			if ($id) $categoryIds[] = $id;
		}
		$product     = $this->getProductModel();
		$categoryIds = array_unique(array_merge($categoryIds, $product->getCategoryIds()));
		$product->setCategoryIds($categoryIds);
	}

	public function _addCategory($data)
	{
		if ($this->checkExistCategory($data)) return;
		try {
			$category = Mage::getModel('catalog/category')
				->setStoreId($this->getCurrentStoreId())
				->setName($data['name'])
				->setPath($data['path'])
				->setParentId($data['parent_id'])
				->setIsActive(1)
				->save();
		} catch (Exception $e) {
			$this->log($e, $this);
		}
		if ($category && $category->getId())
			return $category->getId();
		else
			return null;
	}

	public function checkExistCategory($data)
	{
		if (!isset($data['path'])) return false;
		$category = Mage::getModel('catalog/category')
			->getCollection()
			->addFieldToFilter('path', $data['path'])
			->getFirstItem();

		return ($category AND $category->getId());

	}

	public function getRootCategoryId()
	{
		if ($id = $this->getBatchParams('your_root_category_id') && !empty($id)) {
			return $id;
		} else {
			Mage::app()->getStore()->getRootCategoryId();
		}
	}

	public function getCategoryRootPath()
	{
		return self::ROOT_PATH_PREFIX . $this->getRootCategoryId();
	}

	/**
	 * get category data
	 *
	 * @return array
	 */
	public function getCategoryData()
	{
		return Mage::helper('core')->jsonDecode($this->_importData['category_data']);
	}


	/**
	 * Import Tier Prices
	 */

	public function importTierPrice()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_TIER_PRICE;
		if (!isset($this->_importData[$col]) OR empty($this->_importData[$col])) return;
		$tierPrices = $this->_getTierPrices();
		$this->getProductModel()->setTierPrice($tierPrices);
	}

	public function _getTierPrices()
	{

		$col        = Magegiant_Productimportexport_Helper_Data::COL_TIER_PRICE;
		$tierPrices = Mage::helper('core')->jsonDecode($this->_importData[$col]);
		if (!($count = count($tierPrices))) return null;
		for ($i = 0; $i < $count; $i++) {
			$tierPrices[$i]['website_id'] = $this->getCurrentWebsiteId();
			$tierPrices[$i]['delete']     = '';
		}

		return $tierPrices;
	}


	/**
	 * Import grouped associated
	 *
	 * @return null
	 * Reference http://www.magentocommerce.com/boards%20/viewthread/13660/
	 */

	public function importGroupedProducts()
	{
		if (!$this->_canImportGroupedProducts()) return;
		if (!isset($this->_importData['grouped_associated_skus']) OR empty($this->_importData['grouped_associated_skus'])) return;
		$data = Mage::helper('core')->jsonDecode($this->_importData['grouped_associated_skus']);
		if (!count($data)) return null;
		$groupedLinkData = array();
		$product         = $this->getProductModel();
		foreach ($data as $_item) {
			$groupedLinkData[$product->getIdBySku($_item['sku'])] = array(
				'position' => $_item['position'],
				'qty'      => !is_null($_item['qty']) ? $_item['qty'] : 0,
			);
		}
		$product->setGroupedLinkData($groupedLinkData);
	}


	protected function _canImportGroupedProducts()
	{
		return ($this->_importData['type'] == 'grouped');
	}

	public function importCrosssell()
	{
		if (!isset($this->_importData['crosssell_associated_skus']) OR empty($this->_importData['crosssell_associated_skus'])) return;
		$data = Mage::helper('core')->jsonDecode($this->_importData['crosssell_associated_skus']);
		if (!($count = count($data))) return null;
		$inkData = array();
		$product = $this->getProductModel();
		foreach ($data as $_item) {
			$inkData[$product->getIdBySku($_item['sku'])] = array(
				'position' => $_item['position'],
			);
		}
		$product->setCrossSellLinkData($inkData);
	}

	public function importUpsell()
	{
		if (!isset($this->_importData['upsell_associated_skus']) OR empty($this->_importData['upsell_associated_skus'])) return;
		$data = Mage::helper('core')->jsonDecode($this->_importData['upsell_associated_skus']);
		if (!($count = count($data))) return null;
		$inkData = array();
		$product = $this->getProductModel();
		foreach ($data as $_item) {
			$inkData[$product->getIdBySku($_item['sku'])] = array(
				'position' => $_item['position'],
			);
		}
		$product->setUpSellLinkData($inkData);
	}


	public function importRelated()
	{
		if (!isset($this->_importData['related_associated_skus']) OR empty($this->_importData['related_associated_skus'])) return;

		$data = Mage::helper('core')->jsonDecode($this->_importData['related_associated_skus']);
		if (!($count = count($data))) return null;
		$inkData = array();
		$product = $this->getProductModel();
		foreach ($data as $_item) {
			$inkData[$product->getIdBySku($_item['sku'])] = array(
				'position' => $_item['position'],
			);
		}
		$product->setRelatedLinkData($inkData);
	}


	public function importCustomOptions()
	{
		$_product = $this->getProductModel();
		$options  = $this->_prepareCustomOptions();
		if (!count($options)) return;


		foreach ($_product->getOptions() as $_option) {
			$this->_deleteOption($_option);
		}


		try {
			$model = Mage::getModel('catalog/product_option');
			$model
				->setProduct($this->getProductModel())
				->setOptions($options)
				->saveOptions();
		} catch (Exception $e) {
			$this->log($e, $this);
		}

		$_product->setCanSaveCustomOptions(true);
		$_product->setHasOptions(1);
		$this->forceShowOptions($_product);

	}

	protected function _deleteOption($option)
	{
		try {
			$option->getValueInstance()->deleteValue($option->getId());
			$option->deletePrices($option->getId());
			$option->deleteTitles($option->getId());
			$option->delete();
		} catch (Exception $e) {
			$this->log($e, $this);
		}
	}


	protected function _addOption($option)
	{
		$model = Mage::getModel('catalog/product_option');
		try {
			$model
				->setProduct($this->getProductModel())
				->addOption($option)
				->saveOptions();
		} catch (Exception $e) {
			$this->log($e, $this);
		}

	}

	protected function _prepareCustomOptions()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_CUSTOM_OPTIONS;
		if (!isset($this->_importData[$col]) OR empty($this->_importData[$col])) return;
		$options = Mage::helper('core')->jsonDecode($this->_importData[$col]);
		$result  = array();
		foreach ($options as $option) {
			if (isset($option['option_id'])) unset($option['option_id']);
			if (!isset($option['is_delete'])) $option['is_delete'] = 0;
			$option['product_id'] = $this->getProductModel()->getId();
			$result[]             = $option;
		}

		return $result;
	}

	public function forceShowOptions($product)
	{
		if (!in_array($this->_importData['type'], array('simple', 'configurable'))) return;

		$model = Mage::getModel('catalog/product')->load($product->getId());
		$model->setHasOptions(1)
			->save();
	}

	public function getProductNotVisibleCode()
	{
		return Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
	}


	public function getCurrentProductId()
	{
		$product = $this->getProductModel();

		return ($product->getId()) ? $product->getId() : null;
	}

	public function importTags()
	{
		if (!$this->canImportTags()) return;
		$col  = Magegiant_Productimportexport_Helper_Data::COL_PRODUCT_TAGS;
		$tags = explode(self::MULTI_DELIMITER_SHORT, $this->_importData[$col]);
		foreach ($tags as $_tag) {
			$this->_addTag($_tag);
		}
	}

	public function canImportTags()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_PRODUCT_TAGS;

		return (isset($this->_importData[$col]) AND !empty($this->_importData[$col]));
	}

	public function _addTag($name)
	{
		if (empty($name)) return;
		$name  = trim(strip_tags($name));
		$tag   = Mage::getModel('tag/tag');
		$exist = $tag->loadByName($name);
		if (!$exist OR !$exist->getId()) {
			//create
			try {
				$tag->setName($name)
					->setStoreId($this->getStoreId())
					->setStatus($tag->getApprovedStatus())
					->save();
			} catch (Exception $e) {
			}
		} else {
			$tag = $exist;
		}
		//add relation
		if (!$tag->getId()) return;
		$this->_addTagRelation($tag);
		$tag->aggregate();

	}

	public function _addTagRelation($tag)
	{
		$relation = Mage::getModel('tag/tag_relation');
		try {
			$relation->setTagId($tag->getId())
				->setProductId($this->getProductModel()->getId())
				->setStoreId($this->getStoreId())
				->setCreatedAt(date("Y-m-d H:i:s", time()))
				->setActive(self::ACTIVE_STATUS)
				->save();
		} catch (Exception $e) {
		}
	}

	/**
	 * Import downloadable including: links, samples
	 */
	public function importDownloadableProducts()
	{
		if ($this->_importData['type'] != 'downloadable') return;
		$product = $this->getProductModel();
		if (isset($this->_importData['links_purchased_separately'])) {
			$product->setData('links_purchased_separately', $this->_importData['links_purchased_separately']);
		}
		/**
		 * Delete all link before update to avoid error
		 */
		if (!$this->isNew()) $this->deleteAllDownloadableLinks();

		$data = array(
			'link'   => $this->_getDownloadableLinks(),
			'sample' => $this->_getDownloadableSamples(),
		);

		/**
		 * Set downloadable data to product model
		 */
//		$product->setDownloadableData($data);

		if (isset($data['sample'])) {
			$_deleteItems = array();
			foreach ($data['sample'] as $sampleItem) {
				if (isset($sampleItem['is_delete']) AND $sampleItem['is_delete'] == '1') {
					if ($sampleItem['sample_id']) {
						$_deleteItems[] = $sampleItem['sample_id'];
					}
				} else {
					unset($sampleItem['sample_id']);
					$sampleModel = Mage::getModel('downloadable/sample');
					$sampleModel->setData($sampleItem)
						->save();
				}

			}

			if ($_deleteItems)
				Mage::getResourceModel('downloadable/link')->deleteItems($_deleteItems);
		}


		if (isset($data['link'])) {
			$_deleteItems = array();
			foreach ($data['link'] as $linkItem) {
				if (isset($linkItem['is_delete']) AND $linkItem['is_delete'] == '1') {
					if ($linkItem['link_id']) {
						$_deleteItems[] = $linkItem['link_id'];
					}
				} else {
					unset($linkItem['link_id']);
					$linkModel = Mage::getModel('downloadable/link');
					$linkModel->setData($linkItem)
						->save();
				}

			}
			if ($_deleteItems)
				Mage::getResourceModel('downloadable/link')->deleteItems($_deleteItems);

		}


		/**
		 * Import downloadable links
		 */
		$this->importDownloadableLinkFiles();

		/**
		 * Import downloadable samples
		 */
		$this->importDownloadableSamples();

	}

	public function canImportDownloadableLinkFiles()
	{
		return ($this->getBatchParams('import_downloadable_link_files' == 'true'));
	}

	/**
	 * including downloadable links + downloadable samples (different to Samples)
	 * Required: Copy folder MagentoRoot/media/downloadable -> MagentoRoot/media/import/downloadable
	 */
	public function importDownloadableLinkFiles()
	{
		if (!$this->canImportDownloadableLinkFiles()) return;
		foreach ($this->_getDownloadableLinks() as $_link) {

			if ($_link['link_type'] == 'file') {
				$link = array(
					'source' => $this->getImportDownloadableLinksDir() . $_link['link_file'],
					'dest'   => Mage_Downloadable_Model_Link::getBasePath() . $_link['link_file'],
				);
				$this->_importFile($link);
			}
			//samples in links
			if ($_link['sample_type'] == 'file') {
				$sample = array(
					'source' => $this->getImportDownloadableLinksDir() . $_link['sample_file'],
					'dest'   => Mage_Downloadable_Model_Link::getBaseSamplePath() . $_link['sample_file'],
				);
				$this->_importFile($sample);
			}

		}
	}


	public function canImportDownloadableSamples()
	{
		return ($this->getBatchParams('import_downloadable_sample_files' == 'true'));
	}

	/**
	 * including downloadable links + downloadable samples (different to Samples)
	 * Required: Copy folder MagentoRoot/media/downloadable -> MagentoRoot/media/import/downloadable
	 */
	public function importDownloadableSamples()
	{
		if (!$this->canImportDownloadableSamples()) return;
		foreach ($this->_getDownloadableSamples() as $_link) {
			//samples in links
			if ($_link['sample_type'] == 'file') {
				$sample = array(
					'source' => $this->getImportDownloadableSamplesDir() . $_link['sample_file'],
					'dest'   => $this->getBaseSamplePath() . $_link['sample_file'],
				);
				$this->_importFile($sample);
			}

		}
	}


	public function getImportDir()
	{
		return Mage::getBaseDir('media') . DS . 'import';
	}

	public function getImportDownloadableLinksDir()
	{
		return $this->getImportDir() . DS . 'downloadable' . DS . 'files' . DS . 'links';
	}

	public function getImportDownloadableLinkSamplesDir()
	{
		return $this->getImportDir() . DS . 'downloadable' . DS . 'files' . DS . 'link_samples';
	}

	public function getImportDownloadableSamplesDir()
	{
		return $this->getImportDir() . DS . 'downloadable' . DS . 'files' . DS . 'samples';
	}

	public function getBaseSamplePath()
	{
		return Mage::getBaseDir('media') . DS . 'downloadable' . DS . 'files' . DS . 'samples';
	}


	/**
	 * Import file. Copy file from media/import/* into media/*
	 *
	 * @param $data
	 */
	public function _importFile($data)
	{
		if (empty($data)) return;
		if (!is_file($data['file'])) {
			Mage::throwException(Mage::helper('catalog')->__('Downloadable Link  File ' . $data['file'] . ' not found'));

			return;
		}
		try {
			$file = new Varien_Io_File();
			$file->setAllowCreateFolders(true);
			$file->cp($data['source'], $data['dest']);
			$file->chmod($data['dest'], 0777);
		} catch (Exception $e) {
			Mage::throwException(Mage::helper('catalog')->__('Cannot copy file from %s to %s because', $data['source'], $data['dest'], $e->getMessage()));
		}
	}


	/**
	 * If downloadable product -> get all downloadable links
	 *
	 * @return mixed
	 */
	public function _getDownloadableLinks()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_DOWNLOADABLE_LINKS;
		if (!isset($this->_importData[$col]) OR empty($this->_importData[$col])) return;
		$data = Mage::helper('core')->jsonDecode($this->_importData[$col]);

		$result = array();
		foreach ($data as $item) {
			$item['link_file']   = json_encode($item['link_file']);
			$item['sample_file'] = json_encode($item['sample_file']);
			$result[]            = $item;


		}

		return $result;
	}

	/**
	 * If downloadable product -> get all downloadable samples
	 *
	 * @return mixed
	 */
	public function _getDownloadableSamples()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_DOWNLOADABLE_SAMPLES;
		if (!isset($this->_importData[$col]) OR empty($this->_importData[$col])) return;
		$data   = Mage::helper('core')->jsonDecode($this->_importData[$col]);
		$result = array();
		foreach ($data as $item) {
			$item['sample_file'] = json_encode($item['sample_file']);
			$result[]            = $item;
		}

		return $result;
	}

	/**
	 * Delete all downloadable links for current Downloadable product
	 */
	public function deleteAllDownloadableLinks()
	{
		$downloadable = Mage::getModel('downloadable/product_type')
			->setProduct($this->getProductModel());
		if (!$downloadable->hasLinks()) return;
		foreach ($downloadable->getLinks() as $_link) {
			try {
				$_link->delete();
			} catch (Exception $e) {
			}
		}

	}

	/**
	 * Check is add new product or update product
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return $this->_isNew;
	}

	public function getIdBySku($sku)
	{
		if (is_string($sku)) $sku = array($sku);
		$return   = array();
		$_product = $this->getProductModel();
		if (!is_array($sku) OR is_object($sku)) return $return;
		foreach ($sku as $_sku) {
			$return[] = $_product->getIdBySku(trim($_sku));
		}

		return $return;
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

	public function importBundleProducts()
	{
		if (!$this->canImportBundleProducts()) return;
		$_product = $this->getProductModel();
		$options  = $this->_getBundleOptions();
		if (count($options))
			$_product->setBundleOptionsData($options);

		$selections = $this->_getBundleSelections();
		$_product->setBundleSelectionsData($selections);
		$_product->setCanSaveBundleSelections(1);

		/**
		 * Remove custom option when price type = 0
		 **/
//		if ($_product->getPriceType() == '0'):
//			$_product->setCanSaveCustomOptions(true);
//			$options = array();
//			foreach ($_product->getProductOptions() as $k => $v) {
//				$options[$k]['is_delete'] = 1;
//			}
//			$_product->setProductOptions($options);
//		endif;
		//productType


	}

	public function canImportBundleProducts()
	{
		return ($this->_importData['type'] == 'bundle');
	}

	protected function _getBundleOptions()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_BUNDLE_OPTIONS;
		if (!isset($this->_importData[$col]) OR empty($this->_importData[$col])) return array();
		$options = Mage::helper('core')->jsonDecode($this->_importData[$col]);

		return $options;
	}

	protected function _getBundleSelections()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_BUNDLE_SELECTIONS;
		if (!isset($this->_importData[$col]) OR empty($this->_importData[$col])) return array();
		$selections = Mage::helper('core')->jsonDecode($this->_importData[$col]);
		if (!count($selections)) return array();

		return $selections;
	}

	public function importGroupedPrice()
	{
		$col = Magegiant_Productimportexport_Helper_Data::COL_GROUPED_PRICE;
		if (!isset($this->_importData[$col]) OR empty($this->_importData[$col])) return;
		$prices   = $this->_parseGroupedPriceData($this->_importData[$col]);
		$_product = $this->getProductModel();
		$this->_emptyGroupPrices();
		$_product->setData('group_price', $prices);

	}

	protected function _emptyGroupPrices()
	{
		if ($this->getBatchParams('append_group_price') != 'true') return;
		$query = "DELETE FROM " . $this->_getGroupedPriceTable() . " WHERE entity_id = '" . $this->getProductModel()->getId() . "'";
		try {
			$this->_coreWrite()->query($query);
		} catch (Exception $e) {
			$this->log($e, $this);
		}
	}

	protected function _canImportGroupedPrice()
	{
		return (version_compare(Mage::getVersion(), '1.7.0.0', '>='));
	}

	protected function _parseGroupedPriceData($data)
	{
		if (empty($data)) return;
		$prices = explode(self::OR_DELIMITER, $data);
		$return = array();
		foreach ($prices as $_price) {
			$_price = explode(self::EQUAL_DELIMITER, $_price);
			if (!isset($_price[1])) continue; //expect there are 2 values.
			$_item    = array(
				'website_id' => $this->getCurrentWebsiteId(),
				'cust_group' => $_price[0],
				'price'      => $_price[1],
			);
			$return[] = $_item;
		}


		return $return;

	}


	public function log($msg, $class = null)
	{
		//If enable debug mode -> run
		return Magegiant_Productimportexport_Helper_Data::log($msg, $class);
	}

	/**
	 * Import images
	 */

	protected function _canImportImages()
	{
		return (
			$this->getBatchParams('import_images') == 'true' AND
			!empty($this->_importData['image']) AND
			!empty($this->_importData['small_image']) AND
			!empty($this->_importData['thumbnail'])
		);
	}

	/**
	 * Import image
	 * Reference: app\code\core\Mage\Catalog\Model\Convert\Adapter\Product.php:761
	 */
	public function importImages()
	{
		if (!$this->_canImportImages()) return;
		$this->deteleImages();
		$mediaGalleryBackendModel = $this->getAttribute('media_gallery')->getBackend();
		$product                  = $this->getProductModel();
		$arrayToMassAdd           = array();
		$importData = $this->_importData;


		foreach ($product->getMediaAttributes() as $mediaAttributeCode => $mediaAttribute) {

			if (isset($importData[$mediaAttributeCode])) {
				$file = trim($importData[$mediaAttributeCode]);
				//$file = $this->_importRemoteImageUrl($file);
				if (!empty($file) && !$mediaGalleryBackendModel->getImage($product, $file)) {
					$arrayToMassAdd[] = array('file' => trim($file), 'mediaAttribute' => $mediaAttributeCode);
				}
			}
		}

		$addedFilesCorrespondence = $mediaGalleryBackendModel->addImagesWithDifferentMediaAttributes(
			$product,
			$arrayToMassAdd, Mage::getBaseDir('media') . DS . 'import',
			false,
			$this->getBatchParams('exclude_images') == 'true'
		);


		foreach ($product->getMediaAttributes() as $mediaAttributeCode => $mediaAttribute) {
			$addedFile = '';
			if (isset($importData[$mediaAttributeCode . '_label'])) {
				$fileLabel = trim($importData[$mediaAttributeCode . '_label']);
				if (isset($importData[$mediaAttributeCode])) {
					$keyInAddedFile = array_search($importData[$mediaAttributeCode],
						$addedFilesCorrespondence['alreadyAddedFiles']);
					if ($keyInAddedFile !== false) {
						$addedFile = $addedFilesCorrespondence['alreadyAddedFilesNames'][$keyInAddedFile];
					}
				}

				if (!$addedFile) {
					$addedFile = $product->getData($mediaAttributeCode);
				}
				if ($fileLabel && $addedFile) {
					$mediaGalleryBackendModel->updateImage($product, $addedFile, array('label' => $fileLabel));
				}
			}
		}

	}

	public function deteleImages()
	{
		if ($this->getBatchParams('delete_images_before_import') != 'true') return;
		$_product   = $this->getProductModel();
		$attributes = $_product->getTypeInstance()->getSetAttributes();
		if (!isset($attributes['media_gallery'])) return;
		$gallery = $attributes['media_gallery'];
		foreach ($_product->getMediaGalleryImages() as $_image) {
			if (!$gallery->getBackend()->getImage($_product, $_image['file'])) continue;
			try {
				$gallery->getBackend()->removeImage($_product, $_image['file']);
			} catch (Exception $e) {
			}
			if (!is_file($_image['file'])) continue;
			try {
				unlink($this->getBaseProductImagePath() . $_image['file']);
			} catch (Exception $e) {
			}


		}
	}

	public function getBaseProductImagePath()
	{
		return Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product';
	}

	protected function _importRemoteImageUrl($url)
	{
		if (!$this->_canImportRemoteImageUrl()) return $url;
		if (!$this->isValidURL($url)) return $url;
		$img = pathinfo($url);
		if (!isset($img['basename'])) return $url;
		$path = $this->getBaseProductImagePath() . DS . $img['basename'];
		if (is_file($path)) {
			try {
				unlink($path);
			} catch (Exception $e) {
				$this->log($e, $this);
			}
		}
		$file = $this->downloadImage($path, $url);

		return $file;
	}

	protected function _canImportRemoteImageUrl()
	{
		return ($this->getBatchParams('import_remote_image_url') == 'true');
	}

	public function isValidURL($text)
	{
		return is_string(filter_var($text, FILTER_VALIDATE_URL));
	}

	public function downloadImage($source, $dest)
	{
		if (!function_exists('curl_init')) {
			try {
				file_put_contents($dest, file_get_contents($source));
			} catch (Exception $e) {
				$this->log('Cannot download image ' . $source . ' . ' . $e->getMessage());
			}
		} else {
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $source);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
				$result = curl_exec($ch);
				curl_close($ch);
				$fp = fopen($dest, 'x');
				fwrite($fp, $result);
				fclose($fp);
			} catch (Exception $e) {
				$this->log($e, $this);
			}

			return is_file($dest) ? $dest : null;
		}

	}

	public function importGallery()
	{
		if (!isset($this->_importData['gallery']) OR empty($this->_importData['gallery'])) return;
		$gallery = explode(self::MULTI_DELIMITER_SHORT, $this->_importData['gallery']);
		if (!isset($gallery[0])) return;
		$exclude = array(
			$this->_importData['image'],
			$this->_importData['small_image'],
			$this->_importData['thumbnail'],
		);
		foreach ($gallery as $_img) {
			if (in_array($_img, $exclude)) continue;
			$_img = $this->_importRemoteImageUrl($_img);
			try {
				$this->getProductModel()->addImageToMediaGallery(
					$_img,
					null,
					false,
					$this->getBatchParams('exclude_images') == 'true'
				);
			} catch (Exception $e) {
			}
		}
	}

	public function decode_htmlspecialchars($str)
	{
		return htmlspecialchars_decode(htmlspecialchars_decode(htmlspecialchars_decode($str)));
	}

	protected function _beforeQuery()
	{
		$this->_coreWrite()->query('SET FOREIGN_KEY_CHECKS=0;');
		$this->_coreWrite()->query('SET UNIQUE_CHECKS=0;');
	}

	protected function _afterQuery()
	{
		$this->_coreWrite()->query('SET FOREIGN_KEY_CHECKS=1;');
		$this->_coreWrite()->query('SET UNIQUE_CHECKS=1;');
	}


	public function importReview()
	{
		if (!$this->_canImportReview()) return;
		$reviews = $this->_prepareReviews();
		foreach ($reviews as $review) {
			$this->_addReview($review);
		}
	}

	protected function _canImportReview()
	{
		return (isset($this->_importData['reviews']) AND !empty($this->_importData['reviews']) AND $this->_importData['reviews'] != '[]');
	}

	protected function _prepareReviews()
	{
		$reviews = Mage::helper('core')->jsonDecode($this->_importData['reviews']);
		if (!$this->_isAppendReview()) $this->_deleteReviews($reviews);

		return $reviews;
	}

	protected function _addReview($data)
	{
		$review  = Mage::getModel('review/review');
		$product = $this->getProductModel();
		$review->setEntityPkValue($data['entity_pk_value']); //product id
		$review->setStatusId($data['status_id']);
		$review->setTitle($data['title']);
		$review->setDetail($data['detail']);
		$review->setEntityId($data['entity_id']);
		$review->setCustomerId($data['customer_id']);
		$review->setNickname($data['nickname']);
		$review->setCreatedAt($data['created_at']);
		$review->setStoreId($product->getStoreId());
		$review->setStores(array($product->getStoreId()));
		try {
			$review->save();
			$review->aggregate();
		} catch (Exception $e) {
			$this->log($e, $this);
		}
	}

	protected function _isAppendReview()
	{
		return $this->getBatchParams('append_reviews') == 'true';
	}

	protected function _deleteReviews($pid)
	{
		$collection = Mage::getModel('review/review')->getCollection()
			->addFieldToFilter('entity_pk_value', $pid);
		foreach ($collection as $review) {
			try {
				$review->delete();
			} catch (Exception $e) {
				$this->log($e, $this);
			}
		}
	}


}
