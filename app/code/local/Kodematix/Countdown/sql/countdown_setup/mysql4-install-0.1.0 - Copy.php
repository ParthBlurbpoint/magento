<?php

$installer = $this;

$installer->startSetup();

$eavConfig = Mage::getSingleton('eav/config');

$entityCode = Mage_Catalog_Model_Product::ENTITY;

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$setup->addAttribute('catalog_product','countdown', array(
		'type'              => 'datetime',
		'backend'           => 'countdown/attribute_countertime',
		'frontend'          => '',
		'label'             => 'Countdown Date',
		'input'             => 'date',
		'frontend_class'    => 'validate-date',
		'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
		'visible'           => true,
		'required'          => false,
		'user_defined'      => false,
		'default'           => null,
		'searchable'        => false,
		'filterable'        => false,
		'comparable'        => false,
		'visible_on_front'  => true,
		'used_in_product_listing'  =>1,
		'used_for_sort_by'  => true,
		'unique'            => false,
		'group'             => 'General',
));

$setup->addAttribute('catalog_product','countdowntext', array(
		'type'              => 'varchar',
		'backend'           => '',
		'frontend'          => '',
		'label'             => 'Countdown Text',
		'input'             => 'text',
		'frontend_class'    => '',
		'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
		'visible'           => true,
		'required'          => false,
		'user_defined'      => false,
		'default'           => null,
		'searchable'        => false,
		'filterable'        => false,
		'comparable'        => false,
		'visible_on_front'  => true,
		'used_in_product_listing' =>1,
		'used_for_sort_by'  => true,
		'unique'            => false,
		'group'             => 'General',
));


$attribute = $eavConfig->getAttribute($entityCode, 'countdown');

$attribute->setData('frontend_input_renderer', 'countdown/adminhtml_renderer_countertime');

$attribute->save();

$installer->endSetup(); 