<?php

/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magegiant.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement.html
 */
class Magegiant_Productimportexport_Block_Adminhtml_Productfiles_Edit_Tab_Download extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare tab form's information
     *
     * @return Magegiant_Productimportexport_Block_Adminhtml_Productimportexport_Edit_Tab_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        if (Mage::getSingleton('adminhtml/session')->getProductimportexportData()) {
            $data = Mage::getSingleton('adminhtml/session')->getProductimportexportData();
            Mage::getSingleton('adminhtml/session')->setProductimportexportData(null);
        } elseif (Mage::registry('productimportexport_data')) {
            $data = Mage::registry('productimportexport_data')->getData();
        }
        $fieldset = $form->addFieldset('productimportexport_form', array(
            'legend'  => Mage::helper('productimportexport')->__('Download Export files'),
        ));

        echo '<h2>Export</h2>';
        $_helper = Mage::helper('productimportexport');
        $files   = scandir($_helper->getExportPath());
        $exclude = array('.', '..');
        foreach ($files as $k => $v) {
            if (in_array($v, $exclude)) {
                unset($files[$k]);
            } else {
                echo '<a href="' . Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/streamdownload', array('files' => $v)) . '">' . $v . '</a><br>';
            }
        }


        $form->setValues($data);

        return parent::_prepareForm();
    }
}