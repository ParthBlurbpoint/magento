<?php
/**
 * OrderApproval
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
 * @package    ZetaPrints_OrderApproval
 * @copyright  Copyright (c) 2010 ZetaPrints Ltd. http://www.zetaprints.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ZetaPrints_OrderApproval_Model_Entity_Attribute_Source_Approvers
  extends Mage_Eav_Model_Entity_Attribute_Source_Boolean {

  public function getAllOptions ($includeDefault = true) {
    if (!is_null($this->_options))
      return $this->_options;

    $this->_options = array(array('label' => '', 'value' => null));

    if ($includeDefault)
      $this->_options[] = array(
        'label' => Mage::helper('orderapproval')->__('Default approver'),
        'value' => ZetaPrints_OrderApproval_Helper_Data::DEFAULT_APPROVER
      );

    $approvers = Mage::getResourceModel('customer/customer_collection')
      ->addAttributeToFilter('is_approver', 1)
      ->addNameToSelect()
      ->load();

    foreach ($approvers as $approver)
      $this->_options[] = array(
        'label' => $approver->getName() . ' ('. $approver->getEmail() . ')',
        'value' => $approver->getId() );

    return $this->_options;
  }
}

?>
