<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Quickview
 */
class Amasty_Quickview_Block_Config extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('amasty/amquickview/config.phtml');
    }

    public function javascriptParams()
    {
        $params = array(
            'url'           =>  $this->_getControllerUrl(),
            'text'          =>  $this->_getViewText(),
            'css'           =>  Mage::getStoreConfig('amquickview/general/custom_css_styles'),
            'item_selector' =>  Mage::getStoreConfig('amquickview/general/item_selector')
        );

        return Zend_Json::encode($params);
    }
    
    private function _getControllerUrl()
    {
        $url = $this->getUrl('amquickview/ajax/view');
        if (isset($_SERVER['HTTPS']) && 'off' != $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "")
        {
            $url = str_replace('http:', 'https:', $url);
        }

        return $url;
    }

    private function _getViewText(){
        return '<img class="am-quickview-icon" src="' .
                        Mage::getDesign()->getSkinUrl('images/amasty/amquickview/len.png',array('_area'=>'frontend')) .
                    '"/> ' .
                    $this->__('QUICK VIEW');
    }

}