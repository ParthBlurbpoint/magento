<?php
/**
 * Copyright (c) 2009-2012 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_Klarna
 * @copyright   Copyright (c) 2009-2013 Vaimo AB
 */

/** @var $installer Mage_Sales_Model_Resource_Setup */
$installer = Mage::getResourceModel('sales/setup', 'sales_setup');

$installer->startSetup();

/*
  Klarna libraries used to create the table, but in the latest update, they don't do that
  any longer. So I create it, if it doesn't already exist. 
*/

if (!$installer->getConnection()->isTableExists('klarnapclasses')) {
$table = $installer->getConnection()
    ->newTable('klarnapclasses')
    ->addColumn('eid', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'E-Id')
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Id')
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
    ), 'Type')
    ->addColumn('description', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable' => false,
    ), 'Description')
    ->addColumn('months', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
    ), 'Months')
    ->addColumn('interestrate', Varien_Db_Ddl_Table::TYPE_DECIMAL, '11,2', array(
        'nullable'  => false,
        ), 'Interest Rate')
    ->addColumn('invoicefee', Varien_Db_Ddl_Table::TYPE_DECIMAL, '11,2', array(
        'nullable'  => false,
        ), 'Invoice Fee')
    ->addColumn('startfee', Varien_Db_Ddl_Table::TYPE_DECIMAL, '11,2', array(
        'nullable'  => false,
        ), 'Start Fee')
    ->addColumn('minamount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '11,2', array(
        'nullable'  => false,
        ), 'Minimum Amount')
    ->addColumn('country', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        ), 'Country')
    ->addColumn('expire', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        ), 'Expire')
    ->addIndex('id', array('id'))
    ->setOption('charset', 'latin1') // To make it 100% compatiblae with how it was done before
    ->setOption('collate', 'latin1_swedish_ci')
    ->setComment('Klarna PClasses');

$installer->getConnection()->createTable($table);
}

$installer->endSetup();