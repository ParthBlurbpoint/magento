<?php
/**
 * Kodematix
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category   Kodematix
 * @package    Kodematix_Hidecatalog
 * @copyright  Copyright (c) 2014 Parth Palkhiwala http://kodematix.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Kodematix_Hidecatalog_Model_Entity_Attribute_Frontend_Customergroups
    extends Mage_Eav_Model_Entity_Attribute_Frontend_Abstract
{
    public function getLabel()
    {
        $storeId = (int)Mage::app()->getRequest()->getParam('store', 0);
        $helper = Mage::helper('kodematix_hidecatalog');
        $setting = $helper->getModeSettingByEntityType($this->getAttribute()->getEntityTypeId(), $storeId);
        $label = '';
        switch ($setting) {
            case Kodematix_Hidecatalog_Helper_Data::MODE_HIDE_BY_DEFAULT:
                // Show products by default
                $label = $helper->__('Show to Groups');
                break;
            case Kodematix_Hidecatalog_Helper_Data::MODE_SHOW_BY_DEFAULT:
                $label = $helper->__('Hide from Groups');
                break;
        }

        return $label;
    }
}
