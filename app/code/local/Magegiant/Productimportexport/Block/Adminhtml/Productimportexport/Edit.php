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
 * Productimportexport Edit Block
 * 
 * @category     Magegiant
 * @package     Magegiant_Productimportexport
 * @author      Magegiant Developer
 */
class Magegiant_Productimportexport_Block_Adminhtml_Productimportexport_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'id';
        $this->_blockGroup = 'productimportexport';
        $this->_controller = 'adminhtml_productimportexport';
        
        $this->_updateButton('save', 'label', Mage::helper('productimportexport')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('productimportexport')->__('Delete Item'));
        
        $this->_addButton('saveandcontinue', array(
            'label'        => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'    => 'saveAndContinueEdit()',
            'class'        => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('productimportexport_content') == null)
                    tinyMCE.execCommand('mceAddControl', false, 'productimportexport_content');
                else
                    tinyMCE.execCommand('mceRemoveControl', false, 'productimportexport_content');
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }
    
    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('productimportexport_data')
            && Mage::registry('productimportexport_data')->getId()
        ) {
            return Mage::helper('productimportexport')->__("Edit Item '%s'",
                                                $this->htmlEscape(Mage::registry('productimportexport_data')->getTitle())
            );
        }
        return Mage::helper('productimportexport')->__('Add Item');
    }
}