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

$installer->addAttribute('quote', 'klarna_checkout_id', array(
    'type'      => 'varchar',
    'required'  => 0,
    'comment'   => 'Klarna Checkout Id',
));

$installer->addAttribute('quote', 'klarna_checkout_newsletter', array(
    'type'      => 'smallint',
    'required'  => 0,
    'comment'   => 'Sign up for Newsletter',
));

$installer->addAttribute('quote_address', 'care_of', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length'    => 255,
    'required'  => false,
    'comment'   => 'Care of',
));

$installer->addAttribute('order_address', 'care_of', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length'    => 255,
    'required'  => false,
    'comment'   => 'Care of',
));

$installer->addAttribute('customer_address', 'care_of', array(
    'label'     => 'Care of',
    'type'      => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'length'    => 255,
    'input'     => 'text',
    'visible'   => true,
    'required'  => false
));

Mage::getSingleton('eav/config')
    ->getAttribute('customer_address', 'care_of')
    ->setData('used_in_forms', array('adminhtml_customer_address', 'customer_address_edit', 'customer_register_address'))
    ->setSortOrder(55)
    ->save();

$installer->endSetup();