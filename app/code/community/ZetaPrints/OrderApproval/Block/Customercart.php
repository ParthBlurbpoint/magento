<?php
/**
 * Magento
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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mage
 * @package  Mage_Sales
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales order history block
 *
 * @category   Mage
 * @package Mage_Sales
 * @author    Magento Core Team <core@magentocommerce.com>
 */

class ZetaPrints_OrderApproval_Block_CustomerCart
  extends Mage_Core_Block_Template {

  public function __construct () {
    parent::__construct();
    
    $this->setTemplate('order-approval/cart.phtml');

    $approverId = Mage::getSingleton('customer/session')
                    ->getCustomer()
                    ->getId();

    $groups = Mage::getResourceModel('customer/group_collection')
                ->addFieldToFilter('approver_id', $approverId)
                ->load();

    $_customers = array();

    foreach ($groups as $group)
      $_customers += Mage::getResourceModel('customer/customer_collection')
                       ->addNameToSelect()
                       ->addAttributeToFilter('group_id', $group->getId())
                       ->getItems();

    $_customers += Mage::getResourceModel('customer/customer_collection')
                     ->addNameToSelect()
                     ->addAttributeToFilter('approver', $approverId)
                     ->getItems();

    $customers = new Varien_Data_Collection();

    foreach ($_customers as $customer) {
      $quote = Mage::getModel('sales/quote')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->loadByCustomer($customer->getId());

      $declinedItems = Mage::getModel('sales/quote_item')
        ->getCollection()
        ->addFieldToFilter('approved', 2)
        ->setQuote($quote);

      $approvedItems = Mage::getModel('sales/quote_item')
                         ->getCollection()
                         ->addFieldToFilter('approved', 1)
                         ->setQuote($quote);

      $unapprovedItemsNumber = count($quote->getItemsCollection())
                                 - count($approvedItems)
                                 - count($declinedItems);

      if ($unapprovedItemsNumber) {
        $customer->setUnapprovedItemsNumber($unapprovedItemsNumber);

        $customers->addItem($customer);
      }
    }

    $this->setCustomers($customers);

    Mage::app()
      ->getFrontController()
      ->getAction()
      ->getLayout()
      ->getBlock('root')
      ->setHeaderTitle(Mage::helper('orderapproval')->__('Carts of customers'));
  }

  protected function _prepareLayout () {
    parent::_prepareLayout();

    $pager = $this
               ->getLayout()
               ->createBlock('page/html_pager', 'order-approval.cart.pager')
               ->setCollection($this->getCustomers());

    $this->setChild('pager', $pager);

    return $this;
  }

  public function getPagerHtml () {
    return $this->getChildHtml('pager');
  }

  public function getEditUrl ($customer) {
    return $this->getUrl('*/*/edit', array('customer' => $customer->getId()));
  }
}
