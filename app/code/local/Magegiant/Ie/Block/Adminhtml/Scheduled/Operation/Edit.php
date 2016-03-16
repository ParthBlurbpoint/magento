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
 * Scheduled operation create/edit form container
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Initialize operation form container.
     * Create operation instance from database and set it to register.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'magegiant_ie';
        $this->_mode = 'edit';
        $this->_controller = 'adminhtml_scheduled_operation';

        $operationId = (int)$this->getRequest()->getParam($this->_objectId);
        $operation = Mage::getModel('magegiant_ie/scheduled_operation');
        if ($operationId) {
            $operation->load($operationId);
        } else {
            $operation->setOperationType($this->getRequest()->getParam('type'))
                ->setStatus(true);
        }
        Mage::register('current_operation', $operation);
    }

    /**
     * Prepare page layout.
     * Set form object to container.
     *
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Edit
     */
    protected function _prepareLayout()
    {
        $operation = Mage::registry('current_operation');
        $blockName = 'magegiant_ie/adminhtml_scheduled_operation_edit_form_'
            . $operation->getOperationType();
        $formBlock = $this->getLayout()
            ->createBlock($blockName);
        if ($formBlock) {
            $this->setChild('form', $formBlock);
        } else {
            Mage::throwException(Mage::helper('magegiant_ie')->__('Invalid scheduled operation type'));
        }

        $this->_updateButton('delete', 'onclick', 'deleteConfirm(\''
            . Mage::helper('magegiant_ie')->getConfirmationDeleteMessage($operation->getOperationType())
            .'\', \'' . $this->getDeleteUrl() . '\')'
        );

        return $this;
    }

    /**
     * Get operation delete url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array(
            $this->_objectId => $this->getRequest()->getParam($this->_objectId),
            'type' => Mage::registry('current_operation')->getOperationType()
        ));
    }

    /**
     * Get page header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        $operation = Mage::registry('current_operation');
        if ($operation->getId()) {
            $action = 'edit';
        } else {
            $action = 'new';
        }
        return Mage::helper('magegiant_ie')->getOperationHeaderText(
            $operation->getOperationType(),
            $action
        );
    }
}
