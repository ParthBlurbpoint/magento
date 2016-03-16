<?php
/**
 * Magegiant
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magegiant
 * @package     Magegiant_Productimportexport
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Productimportexport Adminhtml Block
 * 
 * @category    Magegiant
 * @package     Magegiant_Productimportexport
 * @author      Magegiant Developer
 */
class Magegiant_Productimportexport_Block_Adminhtml_Productfiles extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_product_download';
        $this->_blockGroup = 'productdownloadexport';
        $this->_headerText = Mage::helper('productimportexport')->__('Download Exports');
//        $this->_addButtonLabel = Mage::helper('productimportexport')->__('Add Item');
        parent::__construct();
    }
}