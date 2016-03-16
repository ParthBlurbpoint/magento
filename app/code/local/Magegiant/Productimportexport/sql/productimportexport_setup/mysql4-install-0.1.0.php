<?php
/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageGiant.com license that is
 * available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    MageGiant
 * @package     MageGiant_Productimportexport
 * @copyright   Copyright (c) 2014 MageGiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement/
 */

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$magentoVersion = Mage::getVersion();
$installer->startSetup();

/**
 * create productimportexport table
 */
$installer->run("


");

if(version_compare($magentoVersion,'1.6.0.0','>=')){
  $storeId = Mage::app()->getStore()->getId();
  //insert default profile
  $file = Mage::getBaseDir('var') . DS . 'magegiant' . DS . 'default_product_profiles.json';
  if (is_file($file)) {
      $profile = Mage::getModel('dataflow/profile');
      $content = file_get_contents($file);
      if ($content) {
          $_profiles = json_decode($content, true);
          foreach ($_profiles as $_profile) {
              $data    = array(
                  'name'        => $_profile['title'] .  ' [DO NOT Remove]',
                  'actions_xml' => base64_decode($_profile['actions_xml'])
              );

              $exist = Mage::getModel('dataflow/profile')
                  ->getCollection()
                  ->addFieldToFilter('name',$data['name'])
                  ->getFirstItem();

              if(!$exist->getData() AND !$exist->getId()){
                  $profile = Mage::getModel('dataflow/profile')->setData($data)->save();
                  Mage::getModel('core/config')->saveConfig($_profile['key'], $profile->getId(), 'default');
              } else{
                  $exist->setActionsXml($data['actions_xml']);
                  $exist->save();
                  Mage::getModel('core/config')->saveConfig($_profile['key'], $exist->getId(), 'default');
              }
          }

      }

  }
}//end if compare

try {
	chmod(Mage::getBaseDir('media') . DS . 'import', 0777);
} catch (Exception $e) {
}

$installer->endSetup();

