<?php
/**
 * @author Kodematix 
 */
$installer = $this;
$installer->startSetup();
$installer->installEntities();

$connection = $installer->getConnection();

$installer->run("

CREATE TABLE IF NOT EXISTS {$installer->getTable('giftskus')} (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `gift_product_id` bigint(20) NOT NULL,
  `gift_product_item_id` bigint(20) NOT NULL,
  `status` char(1) NOT NULL default '0',
  `created_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `update_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
    )  ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;
  ");


$installer->endSetup();
 
$installerAttributes = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$connection = $installerAttributes->getConnection();
$installerAttributes->startSetup();

	if (!$installerAttributes->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'sku_of_product_gift')) {
    $installerAttributes->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'sku_of_product_gift', array(        
        'label'                      => 'SKU of products offered',                                                
        'group'                      => 'Product Gift',                                           
        'backend'                    => '',                                     
        'type'                       => 'text',                                               
        'frontend'                   => '',                                      
        'note'                       => 'comma separated SKU(s)',                                                   
        'default'                    => '',
		'input'                      => 'text',                                             
        'input_renderer'             => '',                                   
        'source'                     => '',                                              
        'required'                   => false,                                        
        'user_defined'               => false,                                          
        'unique'                     => false,                                             
        'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,  
        'visible'                    => true,                                                     
        'visible_on_front'           => false,                                                    
        'used_in_product_listing'    => false,                                                   
        'searchable'                 => false,                                                  
        'visible_in_advanced_search' => false,                                                    
        'filterable'                 => false,                                                    
        'filterable_in_search'       => false,                                                   
        'comparable'                 => false,                                                      
        'apply_to'                   => 'simple,downloadable',                                  
        'is_configurable'            => false,                                                   
        'used_for_sort_by'           => false,                                                   
        'position'                   => 0,                                                        
        'used_for_promo_rules'       => false,                                                    
    ));
	}
	if (!$installerAttributes->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'is_product_gift_enabled')) {
    $installerAttributes->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'is_product_gift_enabled', array(        
        'label'                      => 'Having Gifts',                                                
        'group'                      => 'Product Gift',                                           
        'backend'                    => '',                                     
        'type'                       => 'int',                                               
        'frontend'                   => '',                                                   
        'default'                    => '',
		'input'                      => 'boolean', 
        'source'                     => '',                                              
        'required'                   => false,                                        
        'user_defined'               => false,                                          
        'unique'                     => false,                                             
        'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,  
        'visible'                    => true,                                                     
        'visible_on_front'           => false,                                                    
        'used_in_product_listing'    => false,                                                   
        'searchable'                 => false,                                                  
        'visible_in_advanced_search' => false,                                                    
        'filterable'                 => false,                                                    
        'filterable_in_search'       => false,                                                   
        'comparable'                 => true,                                                            
        'apply_to'                   => 'simple,downloadable',                                  
        'is_configurable'            => false,                                                   
        'used_for_sort_by'           => false,                                                   
        'position'                   => 0,                                                        
        'used_for_promo_rules'       => false,                                                    
    ));
	}
	if (!$installerAttributes->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'product_gift_active_from')) {
    $installerAttributes->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'product_gift_active_from', array(        
        'label'                      => 'Start Date',                                                
        'group'                      => 'Product Gift',                                           
        'backend'                    => '',                                     
        'type'                       => 'datetime',                                               
        'frontend'                   => '',                                                   
        'default'                    => '',
		'input'                      => 'date', 
        'source'                     => '',                                              
        'required'                   => false,                                        
        'user_defined'               => false,                                          
        'unique'                     => false,                                             
        'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,  
        'visible'                    => true,                                                     
        'visible_on_front'           => false,                                                    
        'used_in_product_listing'    => false,                                                   
        'searchable'                 => false,                                                  
        'visible_in_advanced_search' => false,                                                    
        'filterable'                 => false,                                                    
        'filterable_in_search'       => false,                                                   
        'comparable'                 => false,                                                      
        'apply_to'                   => 'simple,downloadable',                                  
        'is_configurable'            => false,                                                   
        'used_for_sort_by'           => false,                                                   
        'position'                   => 0,                                                        
        'used_for_promo_rules'       => false,                                                    
    ));
	}
	if (!$installerAttributes->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'product_gift_active_to')) {
    $installerAttributes->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'product_gift_active_to', array(        
        'label'                      => 'End Date',                                                
        'group'                      => 'Product Gift',                                           
        'backend'                    => '',                                      
        'type'                       => 'datetime',                                               
        'frontend'                   => '',                                                   
        'default'                    => '',
		'input'                      => 'date', 
        'source'                     => '',                                              
        'required'                   => false,                                        
        'user_defined'               => false,                                          
        'unique'                     => false,                                             
        'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,  
        'visible'                    => true,                                                     
        'visible_on_front'           => false,                                                    
        'used_in_product_listing'    => false,                                                   
        'searchable'                 => false,                                                  
        'visible_in_advanced_search' => false,                                                    
        'filterable'                 => false,                                                    
        'filterable_in_search'       => false,                                                   
        'comparable'                 => false,                                                      
        'apply_to'                   => 'simple,downloadable',                                  
        'is_configurable'            => false,                                                   
        'used_for_sort_by'           => false,                                                   
        'position'                   => 0,                                                        
        'used_for_promo_rules'       => false,                                                    
    ));
	}
