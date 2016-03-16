<?php

$installer = $this;

$config = Mage::getSingleton('eav/config');
$store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$attribute_names = array('is_approver', 'approver');

$data = array(
  'is_user_defined'   => 0,
  'is_system'         => 1,
  'is_visible'        => 1,
  'sort_order'        => 120,
  'adminhtml_only'    => 1,
  'is_required'       => 0,
  'used_in_forms' => array('adminhtml_customer') );

foreach ($attribute_names as $name) {
  $attribute = $config->getAttribute('customer', $name);
  $attribute->setWebsite($store->getWebsite());
  $attribute->addData($data);
  $attribute->save();
}

?>
