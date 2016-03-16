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
 * Scheduled operation grid container
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Block_Adminhtml_Scheduled_Operation extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_addButtonLabel = Mage::helper('magegiant_ie')->__('Add Scheduled Export');

        $this->_addButton('add_new_import', array(
            'label'   => Mage::helper('magegiant_ie')->__('Add Scheduled Import'),
            'onclick' => "setLocation('" . $this->getUrl('*/*/new', array('type' => 'import')) . "')",
            'class'   => 'add'
        ));

        $this->_blockGroup = 'magegiant_ie';
        $this->_controller = 'adminhtml_scheduled_operation';
        $this->_headerText = Mage::helper('magegiant_ie')->__('Scheduled Import/Export');
    }

    /**
     * Get create url
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new', array('type' => 'export'));
    }
}
