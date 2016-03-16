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

class Kodematix_Hidecatalog_Model_System_Config_Source_HiddenEntityHandling
{
    const HIDDEN_ENTITY_HANDLING_NOROUTE = '404';
    const HIDDEN_ENTITY_HANDLING_REDIRECT = '302';
    const HIDDEN_ENTITY_HANDLING_REDIRECT_PARENT = '302-parent';

    public function toOptionArray()
    {
        $helper = Mage::helper('kodematix_hidecatalog');
        return array(
            array(
                'value' => self::HIDDEN_ENTITY_HANDLING_NOROUTE,
                'label' => $helper->__('Show 404 Page')
            ),
            array(
                'value' => self::HIDDEN_ENTITY_HANDLING_REDIRECT,
                'label' => $helper->__('Redirect to target route')
            ),
            array(
                'value' => self::HIDDEN_ENTITY_HANDLING_REDIRECT_PARENT,
                'label' => $helper->__('Redirect to parent directory')
            )
        );
    }
}
