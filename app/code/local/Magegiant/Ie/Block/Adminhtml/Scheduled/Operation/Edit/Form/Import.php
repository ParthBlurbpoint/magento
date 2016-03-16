<?php
/**
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the  License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @copyright   Copyright (c) 2014 Magegiant
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Scheduled import create/edit form
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form_Import
    extends Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form
{
    /**
     * Prepare form for import operation
     *
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form_Import
     */
    protected function _prepareForm()
    {
        $this->setGeneralSettingsLabel(Mage::helper('magegiant_ie')->__('Import Settings'));
        $this->setFileSettingsLabel(Mage::helper('magegiant_ie')->__('Import File Information'));
        $this->setEmailSettingsLabel(Mage::helper('magegiant_ie')->__('Import Failed Emails'));

        parent::_prepareForm();
        $form = $this->getForm();

		$fieldset = $form->getElement('operation_settings');

//        $fieldset->addField('behavior', 'select', array(
//            'name'      => 'behavior',
//            'title'     => Mage::helper('magegiant_ie')->__('Import Behavior'),
//            'label'     => Mage::helper('magegiant_ie')->__('Import Behavior'),
//            'required'  => true,
//            'values'    => Mage::getModel('importexport/source_import_behavior')->toOptionArray()
//        ), 'entity');
//
//        $fieldset->addField('force_import', 'select', array(
//            'name'      => 'force_import',
//            'title'     => Mage::helper('magegiant_ie')->__('On Error'),
//            'label'     => Mage::helper('magegiant_ie')->__('On Error'),
//            'required'  => true,
//            'values'    => Mage::getSingleton('magegiant_ie/scheduled_operation_data')
//                ->getForcedImportOptionArray()
//        ), 'freq');

        $form->getElement('email_template')
            ->setValues(Mage::getModel('adminhtml/system_config_source_email_template')
                ->setPath('magegiant_ie_import_failed')
                ->toOptionArray()
            );

        $form->getElement('file_settings')->addField('file_name', 'text', array(
            'name'      => 'file_info[file_name]',
            'title'     => Mage::helper('magegiant_ie')->__('File Name'),
            'label'     => Mage::helper('magegiant_ie')->__('File Name'),
            'required'  => true
        ), 'file_path');

        $operation = Mage::registry('current_operation');
        $this->_setFormValues($operation->getData());

        return $this;
    }
}
