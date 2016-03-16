<?php

/**
 * Source model for admin accounts config option
 *
 * @category ZetaPrints
 * @package ZetaPrints_OrderApproval
 * @author Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */

class ZetaPrints_OrderApproval_Model_System_Config_Source_Admins {

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    $admins = Mage::getResourceModel('admin/user_collection')
                ->addFieldToFilter('is_active', '1');

    $options = array();

    //Add empty option
    $options[] = array(
      'value' => '',
      'label' => null
    );

    foreach ($admins as $admin)
      $options[] = array(
        'value' => $admin->getId(),
        'label' => $admin->getName() . ' ('. $admin->getEmail() . ')'
      );

    return $options;
  }
}

?>
