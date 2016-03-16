<?php
/**
 * Store locator admin helper
 */
class Kodematix_Storelocator_Helper_Admin extends Mage_Core_Helper_Abstract
{
    /**
     * @return bool
     */
    public function isActionAllowed($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('kodematix_storelocator/kodematix_manage_storelocator/' . $action);
    }
}