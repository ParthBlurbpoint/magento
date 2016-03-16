<?php
$installer = $this;
$installer->startSetup();
$installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('md_partialpayment/slabs')}`;
        CREATE TABLE `{$installer->getTable('md_partialpayment/slabs')}`(
            `slab_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
            `product_id` int(11) NOT NULL COMMENT 'Product id',
            `store_id` int(11) NOT NULL COMMENT 'Store id',
            `unit` int(11) NOT NULL DEFAULT '1' COMMENT 'slab unit',
            `price_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '',
            `price` decimal(12,4) NOT NULL COMMENT 'Slab price',
        PRIMARY KEY (`slab_id`),
        UNIQUE KEY `PARTIALPAYMENT_IDX` (`product_id`,`store_id`,`unit`,`price_type`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;
        
        ALTER TABLE `{$installer->getTable('md_partialpayment/options')}` ADD `use_config_installments` tinyint(3) NOT NULL DEFAULT '0' COMMENT 'USe installment from config';
        ALTER TABLE `{$installer->getTable('md_partialpayment/payments')}` DROP `order_item_id`;
        ALTER TABLE `{$installer->getTable('md_partialpayment/payments')}` ADD `full_payment` TINYINT(2) NOT NULL DEFAULT '0' AFTER `next_installment_date`;
        ALTER TABLE `{$installer->getTable('md_partialpayment/payments')}` ADD `full_payment_data` text DEFAULT NULL AFTER `full_payment`;
            
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_price_type` tinyint(2) DEFAULT NULL COMMENT 'Partialpayment installment configured price type';    
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_price` decimal(12,4) DEFAULT NULL COMMENT 'Partialpayment installment configured price';
            
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_price_type` tinyint(2) DEFAULT NULL COMMENT 'Partialpayment installment configured price type';    
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_price` decimal(12,4) DEFAULT NULL COMMENT 'Partialpayment installment configured price';
            
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_price_type` tinyint(2) DEFAULT NULL COMMENT 'Partialpayment installment configured price type';    
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_price` decimal(12,4) DEFAULT NULL COMMENT 'Partialpayment installment configured price';
            
        ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `md_customer_profile_id` int(11) NULL DEFAULT NULL COMMENT 'Partial Payment Customer Profile Id';
        ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `md_payment_profile_id` int(11) NULL DEFAULT NULL COMMENT 'Partial Payment Customer Payment Profile Id';
            
        ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `md_customer_profile_id` int(11) NULL DEFAULT NULL COMMENT 'Partial Payment Customer Profile Id';
        ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `md_payment_profile_id` int(11) NULL DEFAULT NULL COMMENT 'Partial Payment Customer Payment Profile Id';
            
        ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `md_partialpayment_full_cart` tinyint(2) DEFAULT '0' COMMENT 'Customer has selected full cart as  partial payment or not';
        ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `md_partialpayment_price_type` tinyint(2) DEFAULT NULL COMMENT 'Full Cart Price Type';
        ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `md_partialpayment_price` decimal(12,4) DEFAULT NULL COMMENT 'Full Cart Price';
        ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `md_partialpayment_installments_count` int(5) DEFAULT NULL COMMENT 'Full Cart Installment count';
        
        ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `md_partialpayment_additional` decimal(12,4) DEFAULT NULL COMMENT 'Partial payment total additional charges';
        ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `md_partialpayment_surcharge` decimal(12,4) DEFAULT NULL COMMENT 'Partial payment total installment charges';
        
        ALTER TABLE `{$installer->getTable('sales/quote_address')}` ADD `md_partialpayment_additional` decimal(12,4) DEFAULT NULL COMMENT 'Partial payment total additional charges';
        ALTER TABLE `{$installer->getTable('sales/quote_address')}` ADD `md_partialpayment_surcharge` decimal(12,4) DEFAULT NULL COMMENT 'Partial payment total installment charges';
        
        ALTER TABLE `{$installer->getTable('sales/order')}` ADD `md_partialpayment_full_cart` tinyint(2) DEFAULT '0' COMMENT 'Customer has selected full cart as  partial payment or not';
        ALTER TABLE `{$installer->getTable('sales/order')}` ADD `md_partialpayment_price_type` tinyint(2) DEFAULT NULL COMMENT 'Full Cart Price Type';
        ALTER TABLE `{$installer->getTable('sales/order')}` ADD `md_partialpayment_price` decimal(12,4) DEFAULT NULL COMMENT 'Full Cart Price';
        ALTER TABLE `{$installer->getTable('sales/order')}` ADD `md_partialpayment_installments_count` int(5) DEFAULT NULL COMMENT 'Full Cart Installment count';
            
        ALTER TABLE `{$installer->getTable('sales/order')}` ADD `md_partialpayment_additional` decimal(12,4) DEFAULT NULL COMMENT 'Partial payment total additional charges';
        ALTER TABLE `{$installer->getTable('sales/order')}` ADD `md_partialpayment_surcharge` decimal(12,4) DEFAULT NULL COMMENT 'Partial payment total installment charges';
            
        ALTER TABLE `{$this->getTable("md_partialpayment/options")}` DROP `additional_payment_amount`;
        ALTER TABLE `{$this->getTable("md_partialpayment/options")}` DROP `installments`;
        ALTER TABLE `{$this->getTable("md_partialpayment/options")}` DROP `frequency_payment`;
            

");
$installer->endSetup();

