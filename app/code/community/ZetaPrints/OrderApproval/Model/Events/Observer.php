<?php

class ZetaPrints_OrderApproval_Model_Events_Observer {

  const XML_PATH_NEW_ITEMS_TEMPLATE
    = 'orderapproval/email/items_to_approve_template';

  public function mark_quote_item ($observer) {

    //Mark item as approved by default
    $item = $observer
              ->getEvent()
              ->getQuoteItem()
              ->setApproved(true);

    $customer_session = Mage::getSingleton('customer/session');

    //NOTE: customer is never logged in admin area, so the item will stay marked
    //as approved when order is created in admin interface and order approval
    //won't be applied
    if (!$customer_session->isLoggedIn())
      return;

    $customer = $customer_session->getCustomer();

    if (!$customer->getId())
      return;

    if (!Mage::helper('orderapproval')->getApproverForCustomer($customer))
      return;

    $item->setApproved(false);
  }

  public function remove_approved_items_from_quote ($observer) {
    //Get quote
    $quote = $observer->getEvent()->getQuote();

    //If the quote is not active then it means that shopping cart contained
    //only approved items and all of them were checked out.
    if (!$quote->getIsActive())
      return;

    //If shopping cart contained both approved and not approved items,
    //then remove all approved items from the cart. After it cart will contain
    //only not approved items for futher processing.

    //For every item from the quote check...
    foreach ($quote->getAllVisibleItems() as $item)
      //... if it's approved then...
      if ($item->getApproved())
        //...remove it from the cart
        $quote->removeItem($item->getId(), true);

    $quote
      ->getItemsCollection()
      ->save();
  }

  public function check_for_not_sent_items ($observer) {
    //Get customer session
    $customer_session = Mage::getSingleton('customer/session');

    //Check if customer is logged
    if (!$customer_session->isLoggedIn())
      return;

    //Get customer object from the session
    $customer = $customer_session->getCustomer();

    //Check if customer object was loaded already
    if (!$customer->getId())
      return;

    //Check if customer has approver
    if (!Mage::helper('orderapproval')->getApproverForCustomer($customer))
      return;

    $helper = Mage::helper('orderapproval');

    //Get cart object
    $cart = Mage::getSingleton('checkout/cart');

    //If shopping cart contains items that were not sent to approver then ...
    if ($helper->hasNotSentItems($cart->getQuote()->getItemsCollection())) {
      $msg = 'Approval request for all added items will be sent out when you '
             . 'proceed to checkout.';

      //... show notice to the customer
      $cart
        ->getCheckoutSession()
        ->addNotice($helper->__($msg));
    }
  }

  public function addCartsMenuItem ($observer) {
    //Get customer session
    $customer_session = Mage::getSingleton('customer/session');

    //Check if customer is logged
    if (!$customer_session->isLoggedIn())
      return;

    //Get customer object from the session
    $customer = $customer_session->getCustomer();

    //Check if customer object was loaded already
    if (!$customer->getId())
      return;

    //Check if customer is approver
    if (!$customer->getIsApprover())
      return;

    //Get current controller
    $controller = Mage::app()
                    ->getFrontController()
                    ->getAction();

    //Add link to the list of carts into navigation block
    $controller
      ->getLayout()
      ->getBlock('customer_account_navigation')
      ->addLink('order-approval', 'order-approval/carts/all',
                                             $controller->__('Order Approval'));
  }

  public function addApproverToGroup ($observer) {
    $block = $observer->getBlock();

    if (!($block instanceof Mage_Adminhtml_Block_Customer_Group_Edit_Form))
      return;

    $form = $block->getForm();

    $helper = Mage::helper('orderapproval');

    $legend = $helper->__('Order Approval');

    $fieldset = $form->addFieldset('orderapproval_fieldset',
                                   array('legend' => $legend));

    $approvers
      = Mage::getModel('orderapproval/entity_attribute_source_approvers')
          ->getAllOptions(false);

    $label = $helper->__('Default approver');

    $field = array(
      'name' => 'approver_id',
      'label' => $label,
      'title' => $label,
      'values' => $approvers,
      'value' => Mage::registry('current_group')->getApproverId()
    );

    $fieldset->addField('approver_id', 'select', $field);
  }

  public function rememberApproverForGroup ($observer) {
    $controller = $observer->getControllerAction();

    $approverId = (int) $controller
                          ->getRequest()
                          ->getParam('approver_id');

    Mage::register('orderapproval_approver_id_for_group', $approverId);
  }

  public function saveApproverForGroup ($observer) {
    $approverId = Mage::registry('orderapproval_approver_id_for_group');

    if ($approverId !== null)
      $observer
        ->getEvent()
        ->getObject()
        ->setApproverId($approverId);
  }

  public function useOnlyApprovedItemsOnCheckout ($observer) {
    $controller = $observer->getControllerAction();

    if ($controller->getRequest()->getRequestedControllerName() != 'onepage')
      return;

    $controller
      ->getOnepage()
      ->getQuote()
      ->useOnlyApprovedItems();
  }

  public function processItemsBeforeCheckout ($observer) {
    $controller = $observer->getControllerAction();

    $customer_session = Mage::getSingleton('customer/session');

    if (!$customer_session->isLoggedIn())
      return;

    $quote = $controller
               ->getOnepage()
               ->getQuote();

    //Temporarely unset 'items_collection' in quote ot load all items
    //We need all items to process their approval statuses
    $approvedItems = $quote->getItemsCollection();
    $quote->setItemsCollection(null);

    $customer = $customer_session->getCustomer();

    $helper = Mage::helper('orderapproval');

    //If customer was loaded successfully and has approver field
    //and the field is not empty then...
    if ($customer->getId()
        && $approver = $helper->getApproverForCustomer($customer)) {

      $items_to_approve = array();

      //Filter unapproved items which were not mentioned in previous e-mails
      //For every item in the quote...
      foreach ($quote->getItemsCollection() as $item)
        //... check that it is not approved alred then...
        if (!$item->getApproved()) {
          //... get info options model
          $option_model = $item->getOptionByCode('info_buyRequest');

          //Get option values from the model
          $options = unserialize($option_model->getValue());

          //Check if zetaprints-approval-email-was-sent option exists and
          //has true value then...
          if (isset($options['zetaprints-approval-email-was-sent'])
              && $options['zetaprints-approval-email-was-sent'] == true)
            //... pass the item.
            continue;

          //Add unapproved item to the list for further processing
          $items_to_approve[] = $item;
        }

      if (count($items_to_approve)) {
        foreach ($items_to_approve as $item) {
          //Declare option for item
          $option = array(
            'label' => $controller->__('Order approval status:'),
            'value' => $controller->__('Pending approval') );

          //Check if additional options exist...
          if ($option_model = $item->getOptionByCode('additional_options')) {
            //... then get its value
            $options = unserialize($option_model->getValue());

            //Check if approval status option doesn't exist
            if (!isset($options['approval_status'])) {
              //... then add approval status option to additional options
              $options['approval_status'] = $option;

              //and save additional options
              $option_model->setValue(serialize($options))->save();
            }
          } else {
            //... else create additional options with approval status option
            //for the item
            Mage::getModel('sales/quote_item_option')
              ->setData(array(
                          'code' => 'additional_options',
                          'value' => serialize(
                            array('approval_status' => $option) )) )
              ->setItem($item)
              ->save();
          }
        }

        $email_template  = Mage::getModel('core/email_template');

        $cart_url = Mage::getUrl('orderapproval/customercart/edit',
                                 array('customer' => $customer->getId()));

        $template = Mage::getStoreConfig(self::XML_PATH_NEW_ITEMS_TEMPLATE);

        $email_template->sendTransactional(
          $template,
          'sales',
          $approver->getEmail(),
          $approver->getName(),
          array(
            'items' => $items_to_approve,
            'number_of_items' => count($items_to_approve),
            'approver' => $approver,
            'customers_shopping_cart_url' => $cart_url ));

        //If e-mail was sent sucessfully then...
        if ($email_template->getSentSuccess()) {
          //.. for every item from the list...
          foreach ($items_to_approve as $item) {
            //... get info options model
            $option_model = $item->getOptionByCode('info_buyRequest');

            //Get option values from the model
            $options = unserialize($option_model->getValue());

            //Set zetaprints-approval-email-was-sent option to true
            $options['zetaprints-approval-email-was-sent'] = true;

            //Save options model
            $option_model->setValue(serialize($options))->save();
          }

          //Show notice for customer about sending request to approver
          Mage::getSingleton('checkout/session')
            ->addNotice($controller->__('Approval request sent to')
                        . ' '
                        . $approver->getEmail() );

          //If there're both approved and not approved items in the cart
          //then...
          if (count($approvedItems)
              && $unapproved_items_number =
                  count($quote->getItemsCollection())
                    - count($approvedItems))
            //...display notice about number of unapproved items
            //in customer's cart
            Mage::getSingleton('checkout/session')->addNotice(
                $unapproved_items_number . $controller
                  ->__(' unapproved item(s) remain in your shopping cart.') );

          //If there's only unapproved items in the cart then...
          if (count($quote->getItemsCollection())
                                                == count($items_to_approve)) {
            //... redirect to shopping cart page

            //Add support for Magento < 1.7
            if (method_exists($controller, 'setRedirectWithCookieCheck'))
              $controller->setRedirectWithCookieCheck('checkout/cart');
            else
              $controller
                ->getResponse()
                ->setRedirect(Mage::getUrl('checkout/cart'));

            return;
          }
        }
      } else
        //else if there's no items to approve without sent emails and
        //shopping cart is empty then...
        if (!count($approvedItems))
          //... show notice for customer
          Mage::getSingleton('checkout/session')
            ->addNotice($controller
                   ->__('Please, wait for your purchase to be approved.') );
        else
          // else if there's unapproved items then...
          if ($unapproved_items_number =
                count($quote->getItemsCollection())
                  - count($approvedItems))
            //...display notice about number of unapproved items
            //in customer's cart
            Mage::getSingleton('checkout/session')
              ->addNotice($unapproved_items_number . $controller
                ->__(' unapproved item(s) remain in your shopping cart.') );
    } else {
      //... else mark all items as approved and remove approval statuses
      //from them

      //For every item from the quote check...
      foreach ($quote->getItemsCollection() as $item)
        //... if it's not approved then ...
        if (!$item->getApproved()) {
          //... if it has additional options...
          if ($option_model = $item->getOptionByCode('additional_options')) {
            //... get its value
            $options = unserialize($option_model->getValue());

            //Remove approval status
            unset($options['approval_status']);

            //and update value of additional options
            $option_model->setValue(serialize($options));
          }

          //Mark the item as approved
          $item->setApproved(true);
        }

      //Save items from the quote
      $quote->getItemsCollection()->save();
    }

    //Set approved items to 'items_collection' back
    $quote->setItemsCollection($approvedItems);
  }
}

?>
