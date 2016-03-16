<?php
/**
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the  License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @copyright   Copyright (c) 2014 Magegiant
 * @license     http://magegiant.com/license-agreement/
 */

$installer = $this;

/**
 * Create table 'magegiant_ie/scheduled_operation'
 */
//$table = $installer->getConnection()
//    ->newTable($installer->getTable('magegiant_ie/scheduled_operation'))
//    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
//        'identity'  => true,
//        'unsigned'  => true,
//        'nullable'  => false,
//        'primary'   => true,
//        ), 'Id')
//    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
//        'nullable'  => false,
//        ), 'Operation Name')
//    ->addColumn('operation_type', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
//        'nullable'  => false,
//        ), 'Operation')
//    ->addColumn('entity_type', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
//        'nullable'  => false,
//        ), 'Entity')
//    ->addColumn('behavior', Varien_Db_Ddl_Table::TYPE_TEXT, 15, array(
//        'nullable'  => true
//        ), 'Behavior')
//    ->addColumn('start_time', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
//        'nullable'  => false,
//        ), 'Start Time')
//    ->addColumn('freq', Varien_Db_Ddl_Table::TYPE_TEXT, 1, array(
//        'nullable'  => false,
//        ), 'Frequency')
//    ->addColumn('force_import', Varien_Db_Ddl_Table::TYPE_SMALLINT, 1, array(
//        'nullable'  => false,
//        ), 'Force Import')
//    ->addColumn('file_info', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
//        'nullable'  => true,
//        ), 'File Information')
//    ->addColumn('details', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
//        'nullable'  => true,
//        ), 'Operation Details')
//    ->addColumn('entity_attributes', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
//        'nullable'  => true,
//        ), 'Entity Attributes')
//    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, 1, array(
//        'nullable'  => false,
//        ), 'Status')
//    ->addColumn('is_success', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
//        'nullable'  => false,
//        'default'   => Magegiant_Ie_Model_Scheduled_Operation_Data::STATUS_PENDING
//        ), 'Is Success')
//    ->addColumn('last_run_date', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
//        'nullable'  => true,
//        ), 'Last Run Date')
//    ->addColumn('email_receiver', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
//        'nullable'  => false,
//        ), 'Email Receiver')
//    ->addColumn('email_sender', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
//        'nullable'  => false,
//        ), 'Email Receiver')
//    ->addColumn('email_template', Varien_Db_Ddl_Table::TYPE_TEXT, 250, array(
//        'nullable'  => false,
//        ), 'Email Template')
//    ->addColumn('email_copy', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
//        'nullable'  => true,
//        ), 'Email Copy')
//    ->addColumn('email_copy_method', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
//        'nullable'  => false,
//        ), 'Email Copy Method')
//    ->setComment('Scheduled Import/Export Table');
//$installer->getConnection()->createTable($table);
//
//$installer->getConnection()
//	->modifyColumn($installer->getTable('magegiant_ie/scheduled_operation'), 'force_import', array(
//		'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
//		'nullable' => false,
//		'default'  => '0'
//	));


$installer->run("
CREATE TABLE IF NOT EXISTS `{$installer->getTable('magegiant_ie/scheduled_operation')}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Id',
  `name` varchar(255) NOT NULL COMMENT 'Operation Name',
  `operation_type` varchar(50) NOT NULL COMMENT 'Operation',
  `entity_type` varchar(50) DEFAULT NULL COMMENT 'Entity',
  `behavior` varchar(15) DEFAULT NULL COMMENT 'Behavior',
  `start_time` varchar(10) NOT NULL COMMENT 'Start Time',
  `freq` varchar(1) NOT NULL COMMENT 'Frequency',
  `force_import` smallint(6) NOT NULL DEFAULT '0',
  `file_info` text COMMENT 'File Information',
  `details` varchar(255) DEFAULT NULL COMMENT 'Operation Details',
  `entity_attributes` text COMMENT 'Entity Attributes',
  `status` smallint(6) NOT NULL COMMENT 'Status',
  `is_success` smallint(6) NOT NULL DEFAULT '2' COMMENT 'Is Success',
  `last_run_date` timestamp NULL DEFAULT NULL COMMENT 'Last Run Date',
  `email_receiver` varchar(150) NOT NULL COMMENT 'Email Receiver',
  `email_sender` varchar(150) NOT NULL COMMENT 'Email Receiver',
  `email_template` varchar(250) NOT NULL COMMENT 'Email Template',
  `email_copy` varchar(255) DEFAULT NULL COMMENT 'Email Copy',
  `email_copy_method` varchar(10) NOT NULL COMMENT 'Email Copy Method',
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Scheduled Import/Export Table';

");