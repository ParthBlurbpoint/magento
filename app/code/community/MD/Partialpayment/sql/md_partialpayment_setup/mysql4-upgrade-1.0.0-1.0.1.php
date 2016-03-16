<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    DROP TABLE IF EXISTS `{$installer->getTable('md_partialpayment/payments')}`;
    DROP TABLE IF EXISTS `{$installer->getTable('md_partialpayment/summary')}`;
    CREATE TABLE `{$installer->getTable('md_partialpayment/payments')}`(
        `payment_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
        `order_id` varchar(50) DEFAULT NULL COMMENT 'Order Increment Id',
        `order_item_id` int(11) NOT NULL COMMENT 'Order Item Id',
        `store_id` int(11) NOT NULL COMMENT 'Store ID', 
        `paid_amount` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT 'Installment Paid Amount',
        `due_amount` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT 'Due Installment Amount',
        `customer_id` int(11) DEFAULT NULL COMMENT 'Customer ID', 
        `customer_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'Customer Name',
        `customer_email` varchar(255) DEFAULT NULL COMMENT 'Customer Email Address',
        `paid_installments` smallint(5) NOT NULL DEFAULT '0' COMMENT 'Total Paid Installments',
        `due_installments` smallint(5) NOT NULL DEFAULT '0' COMMENT 'Total Due Installments',
        `last_installment_date` date DEFAULT NULL COMMENT 'Date of Last Installment',
        `next_installment_date` date DEFAULT NULL COMMENT 'Date of Next Installment',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Date',
        `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Updated Date',
        PRIMARY KEY (`payment_id`)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    CREATE TABLE `{$installer->getTable('md_partialpayment/summary')}`(
        `summary_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
        `payment_id` int(11) NOT NULL COMMENT 'Installment Payment Id',
        `amount` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT 'Paid Amount',
        `due_date` date DEFAULT NULL COMMENT 'Installment Due Date',
        `paid_date` date DEFAULT NULL COMMENT 'Installment Paid Date',
        `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Invoice Status',
        `payment_fail_count` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Total count of failure',
        `transaction_id` varchar(250) DEFAULT NULL COMMENT 'Payment Transaction Id',
        `transaction_details` text COMMENT 'Transaction Details',
        `payment_method` varchar(250) DEFAULT NULL COMMENT 'Payment Method',
        PRIMARY KEY (`summary_id`)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
");
$installer->endSetup();
        

