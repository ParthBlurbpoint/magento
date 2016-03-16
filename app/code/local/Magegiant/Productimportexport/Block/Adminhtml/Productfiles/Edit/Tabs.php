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
 * Productimportexport Edit Tabs Block
 * 
 * @category    Magegiant
 * @package     Magegiant_Productimportexport
 * @author      Magegiant Developer
 */
class Magegiant_Productimportexport_Block_Adminhtml_Productfiles_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('productimportexport_tabs');
        $this->setDestElementId('edit_form');
//        $this->setTitle(Mage::helper('productimportexport')->__('Item Information'));
    }
    
    /**
     * prepare before render block to html
     *
     * @return Magegiant_Productimportexport_Block_Adminhtml_Productfiles_Edit_Tabs
     */
    protected function _beforeToHtml()
    {
//        $this->addTab('form_section', array(
//            'label'     => Mage::helper('productimportexport')->__('Upload files'),
//            'title'     => Mage::helper('productimportexport')->__('Upload files'),
//            'content'   => $this->getLayout()
//                                ->createBlock('productimportexport/adminhtml_productfiles_edit_tab_form')
//                                ->toHtml(),
//        ));
//
//        $this->addTab('download_section', array(
//            'label'     => Mage::helper('productimportexport')->__('Download Export files'),
//            'title'     => Mage::helper('productimportexport')->__('Download Export files'),
//            'content'   => $this->getLayout()
//                                ->createBlock('productimportexport/adminhtml_productfiles_edit_tab_download')
//                                ->toHtml(),
//        ));
        return parent::_beforeToHtml();
    }
}