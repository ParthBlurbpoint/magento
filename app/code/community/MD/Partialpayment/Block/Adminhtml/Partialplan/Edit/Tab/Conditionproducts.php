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
class MD_Partialpayment_Block_Adminhtml_Partialplan_Edit_Tab_Conditionproducts extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
	parent::__construct();
	$this->setId('partialplan_conditionproducts');
	$this->setDefaultSort('entity_id');
	$this->setDefaultDir('DESC');
	$this->setSaveParametersInSession(true);
	$this->setUseAjax(true);
    }

    protected function _getStore() {
	$storeId = (int) $this->getRequest()->getParam('store', 0);
	return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection() {
	$ruleId     = $this->getRequest()->getParam('id');
	$condition  = unserialize(Mage::getModel('md_partialpayment/rule')->load($ruleId)->getConditionsSerialized());
	$store	    = $this->_getStore();
	$productIds = Mage::getModel('md_partialpayment/rule')->getCollection()
		->addFieldToFilter('rule_id',array('eq'=>$ruleId))
		->addFieldToSelect('product_ids')
		->getFirstItem();

	$productIds = $productIds->getData('product_ids');
	$productIds = explode(',',$productIds);
	$collection = Mage::getModel('catalog/product')->getCollection()
		->addAttributeToFilter('entity_id',array('in'=>$productIds))
		->addAttributeToSelect('sku')
		->addAttributeToSelect('name')
		->addAttributeToSelect('attribute_set_id')
		->addAttributeToSelect('type_id');
	
	if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
	    $collection->joinField('qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
	}

	if ($store->getId()) {
	    $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
	    $collection->addStoreFilter($store);
	    $collection->joinAttribute(
		    'name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore
	    );
	    $collection->joinAttribute(
		    'custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId()
	    );
	    $collection->joinAttribute(
		    'status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId()
	    );
	    $collection->joinAttribute(
		    'visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId()
	    );
	    $collection->joinAttribute(
		    'price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId()
	    );
	} else {
	    $collection->addAttributeToSelect('price');
	    $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
	    $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
	}

	$this->setCollection($collection);

	parent::_prepareCollection();
	$this->getCollection()->addWebsiteNamesToResult();
	return $this;
    }

    protected function _prepareColumns() {
	$helper   = Mage::helper('md_partialpayment');
	$currency = (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);

	$this->addColumn('entity_id', array(
	    'header' => Mage::helper('catalog')->__('ID'),
	    'width' => '50px',
	    'type' => 'number',
	    'index' => 'entity_id',
	));
	
	$this->addColumn('name', array(
	    'header' => Mage::helper('catalog')->__('Name'),
	    'index' => 'name',
	));

	$store = $this->_getStore();
	
	if ($store->getId()) {
	    $this->addColumn('custom_name', array(
		'header' => Mage::helper('catalog')->__('Name in %s', $store->getName()),
		'index' => 'custom_name',
	    ));
	}

	$this->addColumn('type', array(
	    'header' => Mage::helper('catalog')->__('Type'),
	    'width' => '60px',
	    'index' => 'type_id',
	    'type' => 'options',
	    'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
	));

	$sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
		->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
		->load()
		->toOptionHash();

	$this->addColumn('set_name', array(
	    'header' => Mage::helper('catalog')->__('Attrib. Set Name'),
	    'width' => '100px',
	    'index' => 'attribute_set_id',
	    'type' => 'options',
	    'options' => $sets,
	));

	$this->addColumn('sku', array(
	    'header' => Mage::helper('catalog')->__('SKU'),
	    'width' => '80px',
	    'index' => 'sku',
	));

	$store = $this->_getStore();
	$this->addColumn('price', array(
	    'header' => Mage::helper('catalog')->__('Price'),
	    'type' => 'price',
	    'currency_code' => $store->getBaseCurrency()->getCode(),
	    'index' => 'price',
	));

	if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
	    $this->addColumn('qty', array(
		'header' => Mage::helper('catalog')->__('Qty'),
		'width' => '100px',
		'type' => 'number',
		'index' => 'qty',
	    ));
	}

	$this->addColumn('visibility', array(
	    'header' => Mage::helper('catalog')->__('Visibility'),
	    'width' => '70px',
	    'index' => 'visibility',
	    'type' => 'options',
	    'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
	));

	$this->addColumn('status', array(
	    'header' => Mage::helper('catalog')->__('Status'),
	    'width' => '70px',
	    'index' => 'status',
	    'type' => 'options',
	    'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
	));

	if (!Mage::app()->isSingleStoreMode()) {
	    $this->addColumn('websites', array(
		'header' => Mage::helper('catalog')->__('Websites'),
		'width' => '100px',
		'sortable' => false,
		'index' => 'websites',
		'type' => 'options',
		'options' => Mage::getModel('core/website')->getCollection()->toOptionHash(),
	    ));
	}

	return parent::_prepareColumns();
    }

    public function getGridUrl() {
	return $this->getUrl('*/*/conditionproducts', array('_current' => true));
    } 

}
