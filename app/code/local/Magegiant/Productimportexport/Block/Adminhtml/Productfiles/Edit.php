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
class Magegiant_Productimportexport_Block_Adminhtml_Productfiles_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'id';
        $this->_blockGroup = 'productimportexport';
        $this->_controller = 'adminhtml_productimportexport';
        

    }
    
    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('productimportexport')->__('Import Export History');
    }


    public function getFormHtml(){
        parent::getFormHtml();

        $html = '';
        $html .= '<h2>Export files</h2>';
        $_helper = Mage::helper('productimportexport');
        $files   = scandir($_helper->getExportPath());
        $exclude = array('.', '..');
        foreach ($files as $k => $v) {
            if (in_array($v, $exclude)) {
                unset($files[$k]);
            } else {
                $html .= '<a href="' . Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/streamdownload', array('files' => $v,'source'=>'export')) . '">' . $v . '</a><br>';
            }
        }


        $html .= '<h2>Import files</h2>';
        $_helper = Mage::helper('productimportexport');
        $files   = scandir($_helper->getImportPath());
        $exclude = array('.', '..');
        foreach ($files as $k => $v) {
            if (in_array($v, $exclude)) {
                unset($files[$k]);
            } else {
                $html .= '<a href="' . Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/streamdownload', array('files' => $v ,'source'=>'import')) . '">' . $v . '</a><br>';
            }
        }

        return $html;

    }
}