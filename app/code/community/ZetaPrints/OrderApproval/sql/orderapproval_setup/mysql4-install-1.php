<?php
/**
 * OrderApprove
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_OrderApprove
 * @copyright  Copyright (c) 2010 ZetaPrints Ltd. http://www.zetaprints.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */

$this->startSetup();

//$this->removeAttribute('customer', 'is_approver');

$this->addAttribute('customer', 'is_approver',
  array(
    'type'              => 'int',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Is approver',
    'input'             => 'select',
    'class'             => '',
    'source'            => 'eav/entity_attribute_source_boolean',
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'default'           => 0,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'visible_in_advanced_search' => false,
    'unique'            => false,
    'position'          => 1 ));

//$this->removeAttribute('customer', 'approver');

$this->addAttribute('customer', 'approver',
  array(
    'type'              => 'int',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Approver',
    'input'             => 'select',
    'class'             => '',
    'source'            => 'orderapproval/entity_attribute_source_approvers',
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'default'           => 0,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'visible_in_advanced_search' => false,
    'unique'            => false,
    'position'          => 1 ));

//$this->run("
//  ALTER TABLE {$this->getTable('sales/quote_item')}
//  DROP COLUMN `approved`");

$this->run("
  ALTER TABLE {$this->getTable('sales/quote_item')}
  ADD COLUMN `approved` bool default true; ");

$this->endSetup();

?>
