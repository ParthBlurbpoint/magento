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

class Kodematix_Hidecatalog_Model_System_Config_Source_Mode_Category
{
    /**
     * Return the mode options for the category configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('kodematix_hidecatalog');
        return array(
            array(
                'value' => Kodematix_Hidecatalog_Helper_Data::MODE_SHOW_BY_DEFAULT,
                'label' => $helper->__('Show categories by default')
            ),
            array(
                'value' => Kodematix_Hidecatalog_Helper_Data::MODE_HIDE_BY_DEFAULT,
                'label' => $helper->__('Hide categories by default')
            ),
        );
    }
}
