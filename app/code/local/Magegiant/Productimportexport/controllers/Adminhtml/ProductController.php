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
class Magegiant_Productimportexport_Adminhtml_ProductController extends Mage_Adminhtml_Controller_Action
{


    /**
     * init layout and set active for current menu
     *
     * @return Magegiant_Productimportexport_Adminhtml_ProductimportexportController
     */
    protected function _initAction()
    {
        
        $this->loadLayout()
            ->_setActiveMenu('productimportexport/productimportexport')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Products'),
                Mage::helper('adminhtml')->__('Import Export')
            );

        return $this;
    }

    public function indexAction()
    {
        Mage::log("This is a developer log : ".__FILE__, null, "developer.log");

        $this->loadLayout();
        $this->_setActiveMenu('productimportexport/productimportexport');


        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        //        $this->_addContent($this->getLayout()->createBlock('productimportexport/adminhtml_productfiles_edit'))
        //            ->_addLeft($this->getLayout()->createBlock('productimportexport/adminhtml_productfiles_edit_tabs'));
        $this->_addContent($this->getLayout()->createBlock('productimportexport/adminhtml_product_edit'));

        $this->renderLayout();

    }

    public function saveAction()
    {

        if ($data = $this->getRequest()->getPost()) {
            if (isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
                try {
                    /* Starting upload */
                    $uploader = new Varien_File_Uploader('filename');

                    // Any extention would work
                    $uploader->setAllowedExtensions(array('csv', 'xml'));
                    $uploader->setAllowRenameFiles(true);

                    // Set the file upload mode
                    // false -> get the file directly in the specified folder
                    // true -> get the file in the product like folders
                    //    (file.jpg will go in something like /media/f/i/file.jpg)
                    $uploader->setFilesDispersion(false);

                    // We set media as the upload dir
                    Mage::log("This is a developer log : ".__FILE__, null, "developer.log");
                    $path             = Mage::getBaseDir('var') . DS . 'import';
                    $result           = $uploader->save($path, $_FILES['filename']['name']);
                    $data['filename'] = $result['file'];
                } catch (Exception $e) {
                    $data['filename'] = $_FILES['filename']['name'];
                }
            }

            $this->_redirect('*/*/*');

        }
    }

    public function uploadAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            if (isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
                try {
                    /* Starting upload */
                    $uploader = new Varien_File_Uploader('filename');

                    // Any extention would work
                    $uploader->setAllowedExtensions(array('csv', 'xml'));
                    $uploader->setAllowRenameFiles(true);

                    // Set the file upload mode
                    // false -> get the file directly in the specified folder
                    // true -> get the file in the product like folders
                    //    (file.jpg will go in something like /media/f/i/file.jpg)
                    $uploader->setFilesDispersion(false);

                    // We set media as the upload dir
                    $path             = Mage::getBaseDir('var') . DS . 'import';
                    $result           = $uploader->save($path, $_FILES['filename']['name']);
                    $data['filename'] = $result['file'];
                } catch (Exception $e) {
                    Mage::throwException(Mage::helper('productimportexport')->__('Cannot upload file') . ' ' . $_FILES['filename']['name']);
                }
            }

        }
            $this->_redirect('*/*/import');

    }

    /*
     * Download files from var/export
     */
    public function filesAction()
    {

        $this->loadLayout();
        $this->_setActiveMenu('productimportexport/productimportexport');

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_addContent($this->getLayout()->createBlock('productimportexport/adminhtml_productfiles_edit'))
            ->_addLeft($this->getLayout()->createBlock('productimportexport/adminhtml_productfiles_edit_tabs'));

        $this->renderLayout();

    }

    public function streamdownloadAction()
    {
        $file    = $this->getRequest()->getParam('files');
        $source    = $this->getRequest()->getParam('source');

        $_helper = Mage::helper('productimportexport');
        if($source == 'import'){
            $file = $_helper->getImportPath() . DS . $file;
        } else{
            $file = $_helper->getExportPath() . DS . $file;
        }

        if (!$file OR !file_exists($file)) {
            die($this->__('File is not exist. Chmod 777 /var/ folder to get this work or contact administrator for more details.'));
        }
        error_reporting(0);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
//        echo readfile($file);
        echo file_get_contents($file);
        exit;
    }

    protected function _initProfile($idFieldName = 'id')
    {
        $this->_title($this->__('Magegiant'))
            ->_title($this->__('Products Import and Export'))
            ->_title($this->__('Profiles'));

        $profileId = (int)$this->getRequest()->getParam($idFieldName);
        $profile   = Mage::getModel('dataflow/profile');

        if ($profileId) {
            $profile->load($profileId);
            if (!$profile->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->__('The profile you are trying to save no longer exists'));
                $this->_redirect('*/*');

                return false;
            }
        }
        //        $xml_string = $profile->getData('actions_xml');
        /**
         * Todo: Support multiple stores
         */


        Mage::register('current_convert_profile', $profile);

        return $this;
    }

    public function runAction()
    {
        $this->_initProfile();
        $this->loadLayout();
        $this->renderLayout();

    }

    public function importAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }


    public function testAction(){
        $profiles = Mage::getModel('dataflow/profile')->getCollection()
            ->addFieldToFilter('entity_type',array("null"=>true));
        foreach($profiles as $profile){
        }
    }

    public function batchRunAction(){
        $this->_redirect('adminhtml/system_convert_profile/batchRun');
    }
}