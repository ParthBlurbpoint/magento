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
 * Scheduled export create/edit form
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form_Export
    extends Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form
{
    /**
     * Prepare form for export operation
     *
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form_Export
     */
    protected function _prepareForm()
    {
        $this->setGeneralSettingsLabel(Mage::helper('magegiant_ie')->__('Export Settings'));
		$this->setFileSettingsLabel(Mage::helper('magegiant_ie')->__('Export File Information'));
		$this->setEmailSettingsLabel(Mage::helper('magegiant_ie')->__('Export Failed Emails'));

        parent::_prepareForm();
        $form = $this->getForm();
        $operation = Mage::registry('current_operation');

        $fieldset = $form->getElement('operation_settings');
//        $fieldset->addField('file_format', 'select', array(
//            'name'      => 'file_info[file_format]',
//            'title'     => Mage::helper('magegiant_ie')->__('File Format'),
//            'label'     => Mage::helper('magegiant_ie')->__('File Format'),
//            'required'  => true,
//            'values'    => Mage::getModel('importexport/source_export_format')->toOptionArray()
//        ), 'entity');

//		$form->getElement('file_settings')
//			->removeField('file_path');

//		$form->removeField('file_path');

		$form->getElement('file_settings')->addField('file_name', 'text', array(
			'name'      => 'file_info[file_name]',
			'title'     => Mage::helper('magegiant_ie')->__('File Name'),
			'label'     => Mage::helper('magegiant_ie')->__('File Name'),
			'required'  => true
		), 'file_path');


		$form->getElement('email_template')
            ->setValues(Mage::getModel('adminhtml/system_config_source_email_template')
                ->setPath('magegiant_ie_export_failed')
                ->toOptionArray()
            );

//        $form->getElement('entity')
//            ->setData('onchange', 'editForm.getFilter();');
//
//        $fieldset = $form->addFieldset('export_filter_grid_container', array(
//            'legend' => Mage::helper('magegiant_ie')->__('Entity Attributes'),
//            'fieldset_container_id' => 'export_filter_container'
//        ));

//        if ($operation->getId()) {
//            $fieldset->setData('html_content', $this->_getFilterBlock($operation)->toHtml());
//        }

        $this->_setFormValues($operation->getData());

        return $this;
    }

    /**
     * Return block instance with specific attribute fields
     *
     * @param Magegiant_Ie_Model_Scheduled_Operation $operation
     * @return Magegiant_Ie_Block_Adminhtml_Export_Filter
     */
    protected function _getFilterBlock($operation)
    {
        $export = $operation->getInstance();
        $block = $this->getLayout()
            ->createBlock('magegiant_ie/adminhtml_export_filter')
            ->setOperation($export);

        $export->filterAttributeCollection($block->prepareCollection($export->getEntityAttributeCollection()));
        return $block;
    }
}
