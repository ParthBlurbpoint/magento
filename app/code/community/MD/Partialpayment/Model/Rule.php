<?php

/**
 * Magedelight
 * Copyright (C) 2014 Magedelight <info@magedelight.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category MD
 * @package MD_Partialpayment
 * @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */
class MD_Partialpayment_Model_Rule extends Mage_Rule_Model_Abstract {

    protected $_productIds;
    protected $_eventPrefix = 'catalogrule_rule';
    protected $_productsFilter = null;
    protected static $_priceRulesData = array();
    protected $_factory = null;
    protected $_config = null;
    protected $_app = null;

    public function __construct(array $args = array()) {
	$this->_factory = !empty($args['factory']) ? $args['factory'] : Mage::getSingleton('core/factory');
	$this->_config = !empty($args['config']) ? $args['config'] : Mage::getConfig();
	$this->_app = !empty($args['app']) ? $args['app'] : Mage::app();

	parent::__construct();
    }

    protected function _construct() {
	parent::_construct();
	$this->_init('md_partialpayment/rule');
	$this->setIdFieldName('rule_id');
    }

    public function getConditionsInstance() {
	return Mage::getModel('catalogrule/rule_condition_combine');
    }

    public function getActionsInstance() {
	return Mage::getModel('catalogrule/rule_action_collection');
    }

    public function toArray(array $arrAttributes = array()) {
	return parent::toArray($arrAttributes);
    }

    public function getMatchingProductIds($websiteIds = null) {
	if (empty($websiteIds)) {
	    $websiteIds = array(1);
	} else {
	    $websiteIds = explode(",", $websiteid);
	}
	
	if (is_null($this->_productIds)) {
	    $this->_productIds = array();
	    $this->setCollectedAttributes(array());

	    if ($websiteIds) {
		/** @var $productCollection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
		$productCollection = Mage::getResourceModel('catalog/product_collection');
		
		$productCollection->addWebsiteFilter($websiteIds);
		if ($this->_productsFilter) {
		    $productCollection->addIdFilter($this->_productsFilter);
		}
		$this->getConditions()->collectValidatedAttributes($productCollection);

		Mage::getSingleton('core/resource_iterator')->walk(
			$productCollection->getSelect(), array(array($this, 'callbackValidateProduct')), array(
		    'attributes' => $this->getCollectedAttributes(),
		    'product' => Mage::getModel('catalog/product'),
			)
		);
	    }
	}

	$productIds = $this->_productIds;
	$rows = array();
	foreach ($productIds as $productId => $validationByWebsite) {
	    foreach ($websiteIds as $websiteId) {
		# foreach ($customerGroupIds as $customerGroupId) {
		if (empty($validationByWebsite[$websiteId])) {
		    continue;
		}
		$finalproductId[] = $productId;
		#}
	    }
	}

	return $finalproductId;
    }

    /**
     * Callback function for product matching
     *
     * @param $args
     * @return void
     */
    public function callbackValidateProduct($args) {
	$product = clone $args['product'];
	$product->setData($args['row']);

	$results = array();
	foreach ($this->_getWebsitesMap() as $websiteId => $defaultStoreId) {
	    $product->setStoreId($defaultStoreId);
	    $results[$websiteId] = (int) $this->getConditions()->validate($product);
	}
	$this->_productIds[$product->getId()] = $results;
    }

    /**
     * Prepare website to default assigned store map
     *
     * @return array
     */
    protected function _getWebsitesMap() {
	$map = array();
	foreach ($this->_app->getWebsites(true) as $website) {
	    if ($website->getDefaultStore()) {
		$map[$website->getId()] = $website->getDefaultStore()->getId();
	    }
	}
	return $map;
    }

    public function excludeMinQtyStock(array $productIdscomma, $min_stock) {
	if (!empty($productIdscomma)) {
	    $readConnection = $this->_getReadAdapter();
	    $stockTable = Mage::getSingleton("core/resource")->getTableName("cataloginventory_stock_item");
	    for ($s = 0; $s < count($productIdscomma); $s++) {
		$query = "SELECT qty FROM $stockTable WHERE product_id=$productIdscomma[$s]";
		$result = $readConnection->fetchAll($query);
		$productQty = $result[0]['qty'];
		if ($productQty >= $min_stock) {
		    $newProductIds[] = $productIdscomma[$s];
		}
	    }
	    return $newProductIds;
	} else {
	    return null;
	}
    }

    protected function _getReadAdapter() {
	return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    protected function _getWriteAdapter() {
	return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

}
