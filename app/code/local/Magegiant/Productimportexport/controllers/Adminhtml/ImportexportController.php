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
class Magegiant_Productimportexport_Adminhtml_ImportexportController extends Mage_Adminhtml_Controller_Action
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

    public function ajaxExportedFileAction(){
        $_helper = Mage::helper('productimportexport');
        $path = $_helper->getExportPath();

        $latest_ctime = 0;
        $latest_filename = '';

        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            $filepath = "{$path}/{$entry}";
            // could do also other checks than just checking whether the entry is a file
            if (is_file($filepath) && filectime($filepath) > $latest_ctime) {
                $latest_ctime = filectime($filepath);
                $latest_filename = $entry;
            }
        }
        $download_url = Mage::helper("adminhtml")->getUrl('productimportexportadmin/adminhtml_product/streamdownload', array('source'=>'export', 'files' => $latest_filename));
        echo '<a id="download-exported-file" href="'.$download_url.'" class="magegiant-button magegiant-button-circle magegiant-button-flat-primary">'.$this->__('Download').'</a>';
    }
}