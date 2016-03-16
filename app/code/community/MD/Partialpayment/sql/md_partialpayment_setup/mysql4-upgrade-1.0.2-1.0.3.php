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
$installer = $this;
$installer->startSetup();
$installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('md_partialpayment/rule')}`;
        CREATE TABLE `{$installer->getTable('md_partialpayment/rule')}`(
            `rule_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Rule Unique Id',
            `title` varchar(250) NOT NULL COMMENT 'Rule Title',
            `rule_status` int(1) NOT NULL COMMENT 'Rule Status',
            `initial_payment_amount_type` varchar(1) NOT NULL DEFAULT 'F' COMMENT 'Amount Type',
            `initial_payment_amount` decimal(10,2) NOT NULL DEFAULT '0' COMMENT 'Initial payment amount',
            `conditions_serialized` mediumblob COMMENT 'Serialized Conditions',
	    `product_ids` mediumtext COMMENT 'Product Ids matching rule condition',
	    `installment_settings` blob COMMENT 'Installment settings serialized',
	    `priority` int(10) NOT NULL DEFAULT '0' COMMENT 'Installment settings serialized',
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Update Timestamp',
        PRIMARY KEY (`rule_id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;
	
	ALTER TABLE `{$installer->getTable('sales/quote_address')}` ADD `partialpayment_due_amount` decimal(12,4) NULL COMMENT 'Partial Payment Due Amount';
	    
	ALTER TABLE `{$installer->getTable('sales/quote_item')}` ADD `partialpayment_option_intial_amount` decimal(12,4) NULL COMMENT 'Partial Payment Option Initial Payment Amount';
");

$installer->endSetup();

