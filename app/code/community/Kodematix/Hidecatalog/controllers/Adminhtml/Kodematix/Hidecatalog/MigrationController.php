<?php
/**
 * Kodematix
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category   Kodematix
 * @package    Kodematix_Hidecatalog
 * @copyright  Copyright (c) 2014 Parth Palkhiwala http://kodematix.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Kodematix_Hidecatalog_Adminhtml_Kodematix_Hidecatalog_MigrationController
    extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/tools/kodematix_hidecatalog');
        $this->renderLayout();
    }

    public function doStepAction()
    {
        try {
            $step = $this->getRequest()->getParam('migration_step');
            if (!$step) {
                Mage::throwException($this->__('No migration step specified.'));
            }
            Mage::helper('kodematix_hidecatalog/migration')->doStep($step);

            $this->_getSession()->addSuccess($this->__('Finished migration step "%s" successfully.', $step));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/tools/kodematix_hidecatalog');
    }
}
