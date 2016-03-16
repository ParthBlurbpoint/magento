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
 * Scheduled operation create/edit form
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
abstract class Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare general form for scheduled operation
     *
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form
     */
    protected function _prepareForm()
    {
        $operation = Mage::registry('current_operation');
        $form = new Varien_Data_Form(array(
            'id'     => 'edit_form',
            'name'   => 'scheduled_operation'
        ));
        // settings information
        $this->_addGeneralSettings($form, $operation);

        // file information
        $this->_addFileSettings($form, $operation);

        // email notifications
        $this->_addEmailSettings($form, $operation);

        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setAction($this->getUrl('*/*/save'));

        $this->setForm($form);
        if (is_array($operation->getStartTime())) {
            $operation->setStartTime(join(',', $operation->getStartTime()));
        }
        $operation->setStartTime(str_replace(':', ',', $operation->getStartTime()));

        return $this;
    }

    /**
     * Add general information fieldset to form
     *
     * @param Varien_Data_Form $form
     * @param Magegiant_Ie_Model_Scheduled_Operation $operation
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form
     */
    protected function _addGeneralSettings($form, $operation)
    {
        $fieldset = $form->addFieldset('operation_settings', array(
            'legend' => $this->getGeneralSettingsLabel()
        ));

        if ($operation->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name'      => 'id',
                'required'  => true
            ));
        }
        $fieldset->addField('operation_type', 'hidden', array(
            'name'     => 'operation_type',
            'required' => true
        ));

        $fieldset->addField('name', 'text', array(
            'name'      => 'name',
            'title'     => Mage::helper('magegiant_ie')->__('Name'),
            'label'     => Mage::helper('magegiant_ie')->__('Name'),
            'required'  => true
        ));

        $profiles = Mage::getModel('magegiant_ie/profile')
            ->getProfilesOptionArray();

        $fieldset->addField('profile_id', 'select', array(
            'name'      => 'profile_id',
            'title'     => Mage::helper('magegiant_ie')->__('Profile'),
            'label'     => Mage::helper('magegiant_ie')->__('Profile'),
            'required'  => true,
            'values'    => $profiles
        ));

        $fieldset->addField('start_time', 'time', array(
            'name'      => 'start_time',
            'title'     => Mage::helper('magegiant_ie')->__('Start Time'),
            'label'     => Mage::helper('magegiant_ie')->__('Start Time'),
            'required'  => true,
        ));

        $fieldset->addField('freq', 'select', array(
            'name'      => 'freq',
            'title'     => Mage::helper('magegiant_ie')->__('Frequency'),
            'label'     => Mage::helper('magegiant_ie')->__('Frequency'),
            'required'  => true,
            'values'    => Mage::getSingleton('magegiant_ie/scheduled_operation_data')
                ->getFrequencyOptionArray()
        ));

        $fieldset->addField('status', 'select', array(
            'name'      => 'status',
            'title'     => Mage::helper('magegiant_ie')->__('Status'),
            'label'     => Mage::helper('magegiant_ie')->__('Status'),
            'required'  => true,
            'values'    => Mage::getSingleton('magegiant_ie/scheduled_operation_data')
                ->getStatusesOptionArray()
        ));

        return $this;
    }

    /**
     * Add file information fieldset to form
     *
     * @param Varien_Data_Form $form
     * @param Magegiant_Ie_Model_Scheduled_Operation $operation
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form
     */
    protected function _addFileSettings($form, $operation)
    {
        $fieldset = $form->addFieldset('file_settings', array(
            'legend' => $this->getFileSettingsLabel()
        ));

        $fieldset->addField('file_path', 'text', array(
            'name'      => 'file_info[file_path]',
            'title'     => Mage::helper('magegiant_ie')->__('File Directory'),
            'label'     => Mage::helper('magegiant_ie')->__('File Directory'),
            'required'  => true,
            'note'      => Mage::helper('magegiant_ie')->__('For Type "Local Server" use relative path to Magento installation, e.g. var/export, var/import, var/export/some/dir')
        ));
//
//        $fieldset->addField('host', 'text', array(
//            'name'      => 'file_info[host]',
//            'title'     => Mage::helper('magegiant_ie')->__('FTP Host[:Port]'),
//            'label'     => Mage::helper('magegiant_ie')->__('FTP Host[:Port]'),
//            'class'     => 'ftp-server server-dependent'
//        ));
//
//        $fieldset->addField('user', 'text', array(
//            'name'      => 'file_info[user]',
//            'title'     => Mage::helper('magegiant_ie')->__('User Name'),
//            'label'     => Mage::helper('magegiant_ie')->__('User Name'),
//            'class'     => 'ftp-server server-dependent'
//        ));
//
//        $fieldset->addField('password', 'password', array(
//            'name'      => 'file_info[password]',
//            'title'     => Mage::helper('magegiant_ie')->__('Password'),
//            'label'     => Mage::helper('magegiant_ie')->__('Password'),
//            'class'     => 'ftp-server server-dependent'
//        ));
//
//        $fieldset->addField('file_mode', 'select', array(
//            'name'      => 'file_info[file_mode]',
//            'title'     => Mage::helper('magegiant_ie')->__('File Mode'),
//            'label'     => Mage::helper('magegiant_ie')->__('File Mode'),
//            'values'    => Mage::getSingleton('magegiant_ie/scheduled_operation_data')
//                ->getFileModesOptionArray(),
//            'class'     => 'ftp-server server-dependent'
//        ));
//
//        $fieldset->addField('passive', 'select', array(
//            'name'      => 'file_info[passive]',
//            'title'     => Mage::helper('magegiant_ie')->__('Passive Mode'),
//            'label'     => Mage::helper('magegiant_ie')->__('Passive Mode'),
//            'values'    => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
//            'class'     => 'ftp-server server-dependent'
//        ));

        return $this;
    }

    /**
     * Add file information fieldset to form
     *
     * @param Varien_Data_Form $form
     * @param Magegiant_Ie_Model_Scheduled_Operation $operation
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form
     */
    protected function _addEmailSettings($form, $operation)
    {
        $fieldset = $form->addFieldSet('email_settings', array(
            'legend' => $this->getEmailSettingsLabel()
        ));

        $emails = Mage::getModel('adminhtml/system_config_source_email_identity')->toOptionArray();
        $fieldset->addField('email_receiver', 'select', array(
            'name'      => 'email_receiver',
            'title'     => Mage::helper('magegiant_ie')->__('Failed Email Receiver'),
            'label'     => Mage::helper('magegiant_ie')->__('Failed Email Receiver'),
            'values'    => $emails
        ));

        $fieldset->addField('email_sender', 'select', array(
            'name'      => 'email_sender',
            'title'     => Mage::helper('magegiant_ie')->__('Failed Email Sender'),
            'label'     => Mage::helper('magegiant_ie')->__('Failed Email Sender'),
            'values'    => $emails
        ));

        $fieldset->addField('email_template', 'select', array(
            'name'      => 'email_template',
            'title'     => Mage::helper('magegiant_ie')->__('Failed Email Template'),
            'label'     => Mage::helper('magegiant_ie')->__('Failed Email Template')
        ));

        $fieldset->addField('email_copy', 'text', array(
            'name'      => 'email_copy',
            'title'     => Mage::helper('magegiant_ie')->__('Send Failed Email Copy To'),
            'label'     => Mage::helper('magegiant_ie')->__('Send Failed Email Copy To')
        ));

        $fieldset->addField('email_copy_method', 'select', array(
            'name'      => 'email_copy_method',
            'title'     => Mage::helper('magegiant_ie')->__('Send Failed Email Copy Method'),
            'label'     => Mage::helper('magegiant_ie')->__('Send Failed Email Copy Method'),
            'values'    => Mage::getModel('adminhtml/system_config_source_email_method')->toOptionArray()
        ));

        return $this;
    }

    /**
     * Set values to form from operation model
     *
     * @param array $data
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit_Form|bool
     */
    protected function _setFormValues(array $data)
    {
        if (!is_object($this->getForm())) {
            return false;
        }
        if (isset($data['file_info'])) {
            $fileInfo = $data['file_info'];
            unset($data['file_info']);
            if (is_array($fileInfo)) {
                $data = array_merge($data, $fileInfo);
            }
        }
        if (isset($data['entity_type'])) {
            $data['entity'] = $data['entity_type'];
        }
        $this->getForm()->setValues($data);
        return $this;
    }
}
