<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    
    DROP TABLE IF EXISTS `{$installer->getTable('md_partialpayment/options')}`;
    CREATE TABLE `{$installer->getTable('md_partialpayment/options')}`(
        `option_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
        `product_id` int(11) NOT NULL COMMENT 'Product id',
        `store_id` int(11) NOT NULL COMMENT 'Store id',
        `status` smallint(5) NOT NULL DEFAULT '1' COMMENT 'Partial Payment Status',
        `initial_payment_amount` decimal(12,4) DEFAULT NULL COMMENT 'Starting Payment Amount',
        `additional_payment_amount` decimal(12,4) DEFAULT NULL COMMENT 'Additional amount for payment',
        `installment_type` tinyint(2) DEFAULT NULL COMMENT 'Installment type',
        `installments` smallint(5) DEFAULT NULL COMMENT 'No of Installments',
        `frequency_payment` enum('weekly','quarterly','monthly') DEFAULT NULL COMMENT 'Frequency of payment',
        PRIMARY KEY (`option_id`)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_option_selected` smallint(3) NOT NULL DEFAULT '0' COMMENT 'Customer choose partial payment option or not';
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_installment_count` smallint(5) DEFAULT NULL COMMENT 'Total installment counts';
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_paid_amount` decimal(12,4) DEFAULT NULL COMMENT 'Total paid amount';
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_due_amount` decimal(12,4) DEFAULT NULL COMMENT 'Installment due amount';
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_frequency` enum('weekly','quarterly','monthly') DEFAULT NULL COMMENT 'Frequency of payment';
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_amount_due_after_date` decimal(12,4) DEFAULT NULL COMMENT 'Amount to be paid on due date';    
        ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_next_installment_date` date DEFAULT NULL COMMENT 'Next installment date';
            
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_option_selected` smallint(3) NOT NULL DEFAULT '0' COMMENT 'Customer choose partial payment option or not';
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_installment_count` smallint(5) DEFAULT NULL COMMENT 'Total installment counts';
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_paid_amount` decimal(12,4) DEFAULT NULL COMMENT 'Total paid amount';
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_due_amount` decimal(12,4) DEFAULT NULL COMMENT 'Installment due amount';
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_frequency` enum('weekly','quarterly','monthly') DEFAULT NULL COMMENT 'Frequency of payment';    
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_amount_due_after_date` decimal(12,4) DEFAULT NULL COMMENT 'Amount to be paid on due date';
        ALTER TABLE `{$installer->getTable('sales/quote_address_item')}` ADD `partialpayment_next_installment_date` date DEFAULT NULL COMMENT 'Next installment date';
            
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_option_selected` smallint(3) NOT NULL DEFAULT '0' COMMENT 'Customer choose partial payment option or not';
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_installment_count` smallint(5) DEFAULT NULL COMMENT 'Total installment counts';
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_paid_amount` decimal(12,4) DEFAULT NULL COMMENT 'Total paid amount';
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_due_amount` decimal(12,4) DEFAULT NULL COMMENT 'Installment due amount';
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_frequency` enum('weekly','quarterly','monthly') DEFAULT NULL COMMENT 'Frequency of payment';    
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_amount_due_after_date` decimal(12,4) DEFAULT NULL COMMENT 'Amount to be paid on due date';
        ALTER TABLE `{$installer->getTable('sales/order_item')}` ADD `partialpayment_next_installment_date` date DEFAULT NULL COMMENT 'Next installment date';
");
$installer->endSetup();

