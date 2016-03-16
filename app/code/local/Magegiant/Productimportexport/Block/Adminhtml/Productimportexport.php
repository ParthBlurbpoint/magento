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
class Magegiant_Productimportexport_Block_Adminhtml_Productimportexport extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_productimportexport';
        $this->_blockGroup = 'productimportexport';
        $this->_headerText = Mage::helper('productimportexport')->__('Item Manager');
        $this->_addButtonLabel = Mage::helper('productimportexport')->__('Add Item');
        parent::__construct();
    }
}