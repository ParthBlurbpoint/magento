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
class Magegiant_Productimportexport_Block_Adminhtml_Product_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId   = 'id';
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
        return Mage::helper('productimportexport')->__('Products Import Export');
    }


    public function getFormHtml()
    {
        parent::getFormHtml();
        //create event here

        $_helper = Mage::helper('productimportexport');
        $html    = '
            <div id="magegiant-ie-wrapper">
            <span class="magegiant-ie-item">
                <a  onclick="TINY.box.show({iframe:\'' . Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/run', array('id' => $_helper->getExportAllProfileId(), 'action' => 'export', 'files' => $_helper->getExportProductAll())) . '\',boxid:\'frameless\',width:750,height:450,fixed:true,maskid:\'bluemask\',maskopacity:40,closejs:function(){closeJS()}})" href="#export"  class="magegiant-button magegiant-button-circle magegiant-button-flat-primary" title="Export All Products with All Atrributes">'.$this->__('Export').'<br> All</a>
            </span>

            <span class="magegiant-ie-item">
                <a onclick="TINY.box.show({iframe:\'' . Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/run', array('id' => $_helper->getExportBasicProfileId(), 'action' => 'export', 'files' => $_helper->getExportProductBasic())) . '\',boxid:\'frameless\',width:750,height:450,fixed:true,maskid:\'bluemask\',maskopacity:40,closejs:function(){closeJS()}})" href="#export" class="magegiant-button magegiant-button-circle magegiant-button-flat-action"  title="Export All Products with Basic information">'.$this->__('Basic').'</a><br>
            </span>

            <span class="magegiant-ie-item">
                <a onclick="TINY.box.show({iframe:\'' . Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/run', array('id' => $_helper->getExportStocksProfileId(), 'action' => 'export', 'files' => $_helper->getExportProductStock())) . '\',boxid:\'frameless\',width:750,height:450,fixed:true,maskid:\'bluemask\',maskopacity:40,closejs:function(){closeJS()}})" href="#export" class="magegiant-button magegiant-button-circle magegiant-button-flat-highlight"  title="Export All Products for checking Stock">'.$this->__('Stock').'</a>
            </span>

            <span class="magegiant-ie-item">
                <a href="#import"  onclick="TINY.box.show({iframe:\'' . Mage::helper("adminhtml")->getUrl("productimportexportadmin/adminhtml_product/import") . '\',boxid:\'frameless\',width:750,height:450,fixed:true,maskid:\'bluemask\',maskopacity:40})" class="magegiant-button magegiant-button-circle magegiant-button-flat-caution"  title="Import Products">'.$this->__('Import').'</a>
            </span>
            </div>

            <script type="text/javascript">
            function openJS(){}
            function closeJS(){
            }
            </script>
        ';

        return $html;
    }
}