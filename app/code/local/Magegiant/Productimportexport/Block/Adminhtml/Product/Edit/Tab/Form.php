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
 * Productimportexport Edit Form Content Tab Block
 *
 * @category    Magegiant
 * @package     Magegiant_Productimportexport
 * @author      Magegiant Developer
 */
class Magegiant_Productimportexport_Block_Adminhtml_Productfiles_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
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
            'legend' => Mage::helper('productimportexport')->__('Upload product files to import')
        ));


        $fieldset->addField('filename', 'file', array(
            'label'    => Mage::helper('productimportexport')->__('File'),
            'required' => false,
            'name'     => 'filename',
        ));

        echo '<h2>Import</h2>';
        $_helper = Mage::helper('productimportexport');
        $files   = scandir($_helper->getImportPath());
        $exclude = array('.', '..');
        foreach ($files as $k => $v) {
            if (in_array($v, $exclude)) {
                unset($files[$k]);
            } else {
                echo '<a href="' . Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/streamdownload', array('file' => base64_encode($v))) . '">' . $v . '</a><br>';
            }
        }

        $form->setValues($data);

        return parent::_prepareForm();
    }
}