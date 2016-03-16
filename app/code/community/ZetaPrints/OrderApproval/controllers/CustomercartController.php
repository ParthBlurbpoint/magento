<?php

class ZetaPrints_OrderApproval_CustomerCartController
  extends Mage_Core_Controller_Front_Action {

  const XML_APPROVED_TEMPLATE = 'orderapproval/email/approved_items_template';
  const XML_DECLINED_TEMPLATE = 'orderapproval/email/declined_items_template';

  /**
   * Check customer authentication for some actions
   */
  public function preDispatch () {
    parent::preDispatch();

    if (!$this->getRequest()->isDispatched())
      return;

    if (!Mage::getSingleton('customer/session')->authenticate($this))
      $this->setFlag('', 'no-dispatch', true);
  }

  public function allAction () {
    if (!Mage::helper('orderapproval')->getApprover()) {
      $this->_redirect('404');

      return;
    }

    $this->loadLayout();

    $this
      ->getLayout()
      ->getBlock('head')
      ->setTitle($this->__('Carts of customers'));

    if ($block = $this->getLayout()->getBlock('customer.account.link.back'))
      $block->setRefererUrl($this->_getRefererUrl());

    $this->renderLayout();
  }

  public function editAction () {
    $helper = Mage::helper('orderapproval');
    if (! $approver = $helper->getApprover()) {
      $this->_redirect('404');

      return;
    }

    if (! $customer_id = $this->getRequest()->getParam('customer')) {
      $this->_redirect('');
      return;
    }

    if (!$helper->getCustomerWithApprover($customer_id, $approver)) {
      $this->_redirect('');
      return;
    }

    $quote = Mage::getModel('sales/quote')
              ->setStoreId(Mage::app()->getStore()->getId())
              ->loadByCustomer($customer_id);

    if (!$quote->getId()) {
      $this->_redirect('orderapproval/customercart/all');

      return;
    }

    $this->loadLayout()
      ->getLayout()
      ->getBlock('order-approval.cart.edit')
      ->setCustomerQuote($quote);

    if ($block = $this->getLayout()->getBlock('customer.account.link.back'))
      $block->setRefererUrl($this->_getRefererUrl());

    $this->renderLayout();
  }

  public function itemAction () {
    $helper = Mage::helper('orderapproval');

    if (!(($id = $this->getRequest()->getParam('id'))
          && ($approver = $helper->getApprover()))) {
      $this->_redirect('');
      return;
    }

    $item = Mage::getModel('sales/quote_item')->load($id);

    if(!$item->getId()) {
      $this->_redirect('');
      return;
    }

    $quote = Mage::getModel('sales/quote')->load($item->getQuoteId());

    if (!$quote->getId()) {
      $this->_redirect('');
      return;
    }

    $customer = $helper
                  ->getCustomerWithApprover($quote->getCustomerId(), $approver);

    if (!$customer) {
      $this->_redirect('');
      return;
    }

    $optionCollection = Mage::getModel('sales/quote_item_option')
                          ->getCollection()
                          ->addItemFilter($item);

    $item->setOptions($optionCollection->getOptionsByItem($item));

    $this->loadLayout();

    $layout = $this->getLayout();

    $layout
      ->getBlock('order-approval.cart.item')
      ->setQuoteItem($item)
      ->setCustomer($customer);

    $layout
      ->getBlock('order-approval.cart.item.customer-info')
      ->setCustomer($customer);

    if ($block = $this->getLayout()->getBlock('customer.account.link.back'))
      $block->setRefererUrl($this->_getRefererUrl());

    $this->renderLayout();
  }

  public function updateApprovalStateAction () {
    $helper = Mage::helper('orderapproval');

    if (!(($item_id = $this->getRequest()->getParam('item'))
          && ($approver = $helper->getApprover()))) {
      $this->_redirect('');
      return;
    }

    $state = (int) $this->getRequest()->getParam('state');

    if ($state != ZetaPrints_OrderApproval_Helper_Data::APPROVED
        && $state != ZetaPrints_OrderApproval_Helper_Data::DECLINED) {

      $this->_redirect('');
      return;
    }

    $item = Mage::getModel('sales/quote_item')->load($item_id);

    if(!$item->getId()) {
      $this->_redirect('');
      return;
    }

    $quote = Mage::getModel('sales/quote')->load($item->getQuoteId());

    if (!$quote->getId()) {
      $this->_redirect('');
      return;
    }

    $customerId = $quote->getCustomerId();

    if (!$customer = $helper->getCustomerWithApprover($customerId, $approver)) {
      $this->_redirect('');
      return;
    }

    $message = $this->getRequest()->getParam('message');

    //Update or add approval status notice to the item
    $helper->addNoticeToApprovedItem($item, $state, $message);

    $item->setQuote($quote)->setApproved($state)->save();

    $optionCollection = Mage::getModel('sales/quote_item_option')
                          ->getCollection()
                          ->addItemFilter($item);

    $item->setOptions($optionCollection->getOptionsByItem($item));

    $settings = new Varien_Object(array(
      'email_notification' => true
    ));

    Mage::dispatchEvent(
      'orderapproval_quote_state_updated',
      array(
        'quote' => $quote,
        'state' => $state,
        'settings' => $settings
      )
    );

    $emails = array($customer->getEmail());
    $names = array($customer->getName());

    if ($state == ZetaPrints_OrderApproval_Helper_Data::APPROVED) {
      $template = Mage::getStoreConfig(self::XML_APPROVED_TEMPLATE);

      $emails[] = Mage::getStoreConfig('trans_email/ident_custom1/email');
      $names[] = Mage::getStoreConfig('trans_email/ident_custom1/name');

      Mage::getSingleton('core/session')
        ->addNotice($this->__('Product was succesfully approved'));
    } else {
      $template = Mage::getStoreConfig(self::XML_DECLINED_TEMPLATE);

      Mage::getSingleton('core/session')
        ->addNotice($this->__('Product was declined'));
    }

    if ($settings->getData('email_notification'))
      Mage::getModel('core/email_template')
        ->sendTransactional(
          $template,
          'sales',
          $emails,
          $names,
          array(
            'items' => array($item),
            'number_of_items' => 1,
            'customer' => $customer,
          )
        );

    $this->_redirect('*/*/edit', array('customer' => $quote->getCustomerId()));
  }

  public function massUpdateApprovalStateAction () {
    if (! $item_ids = $this->getRequest()->getParam('items')) {
      $this->_redirect('');
      return;
    }

    $state = (int) $this->getRequest()->getParam('state');

    if ($state != ZetaPrints_OrderApproval_Helper_Data::APPROVED
        && $state != ZetaPrints_OrderApproval_Helper_Data::DECLINED) {

      $this->_redirect('');
      return;
    }

    $helper = Mage::helper('orderapproval');

    if (! $approver = $helper->getApprover()) {
      $this->_redirect('');
      return;
    }

    $item_ids = explode(',', $item_ids);

    // We can not retrieve several quote items without setting quote
    // with its collection
    // Looks like a bug in the M.
    //$items = Mage::getModel('sales/quote_item')->getCollection();
    //$items->getSelect()->where('item_id in (?)', $item_ids);
    //Mage::log(count($items));
    //$this->_redirect('*/*/edit');

    $quote = null;
    $customer = null;

    $emails = array();
    $names = array();

    $items = array();

    foreach ($item_ids as $item_id) {
      $item = Mage::getModel('sales/quote_item')->load($item_id);

      if(!$item->getId())
        continue;

      if (!$quote) {
        $quote = Mage::getModel('sales/quote')->load($item->getQuoteId());

        if (!$quote->getId())
          break;

        $customerId = $quote->getCustomerId();
        $customer = $helper->getCustomerWithApprover($customerId, $approver);

        if (!$customer) {
          $this->_redirect('');
          return;
        }

        $emails[] = $customer->getEmail();
        $names[] = $customer->getName();
      }

      //Update or add approval status notice to the item
      $helper->addNoticeToApprovedItem($item, $state);

      $item->setQuote($quote)->setApproved($state)->save();

      $optionCollection = Mage::getModel('sales/quote_item_option')
                          ->getCollection()
                          ->addItemFilter($item);

      $item->setOptions($optionCollection->getOptionsByItem($item));

      $items[] = $item;
    }

    $settings = new Varien_Object(array(
      'email_notification' => true
    ));

    Mage::dispatchEvent(
      'orderapproval_quote_state_updated',
      array(
        'quote' => $quote,
        'state' => $state,
        'settings' => $settings
      )
    );

    if ($state == ZetaPrints_OrderApproval_Helper_Data::APPROVED) {
      $template = Mage::getStoreConfig(self::XML_APPROVED_TEMPLATE);

      $emails[] = Mage::getStoreConfig('trans_email/ident_custom1/email');
      $names[] = Mage::getStoreConfig('trans_email/ident_custom1/name');

      Mage::getSingleton('core/session')
        ->addNotice($this->__('Selected products were succesfully approved'));
    } else {
      $template = Mage::getStoreConfig(self::XML_DECLINED_TEMPLATE);

      Mage::getSingleton('core/session')
        ->addNotice($this->__('Selected products were declined'));
    }

    if ($settings->getData('email_notification'))
      Mage::getModel('core/email_template')
        ->sendTransactional(
          $template,
          'sales',
          $emails,
          $names,
          array(
            'items' => $items,
            'number_of_items' => count($items),
            'customer' => $customer,
          )
        );

    $this->_redirect('*/*/edit', array('customer' => $quote->getCustomerId()));
  }
}

?>
