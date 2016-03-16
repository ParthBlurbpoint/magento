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

$table = $this->getTable('customer/customer_group');

$data = array(
  'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
  'unsigned' => true,
  'nullable' => true,
  'default' => null,
  'comment' => 'Default approver for the group'
);

$this
  ->getConnection()
  ->addColumn($table, 'approver_id', $data);

$this->endSetup();

?>
