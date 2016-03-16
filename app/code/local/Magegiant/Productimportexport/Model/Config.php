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
class Magegiant_Productimportexport_Model_Config extends Mage_Core_Model_Abstract
{
    const XML_PATH_GENERAL_ENABLE = 'productimportexport/general/enable';


    const XML_PATH_PRODUCT_EXPORT_ALL = 'productimportexport/product/export_all';
    const XML_PATH_PRODUCT_EXPORT_BASIC = 'productimportexport/product/export_basic';
    const XML_PATH_PRODUCT_EXPORT_STOCK = 'productimportexport/product/export_stock';
    const XML_PATH_PRODUCT_IMPORT = 'productimportexport/product/import';
    const XML_PATH_GENERAL_GENERATE_SKU = 'productimportexport/product/generate_sku';
    const XML_PATH_GENERAL_SKU_LENGTH = 'productimportexport/product/sku_length';
    const XML_PATH_GENERAL_GENERATE_URL = 'productimportexport/product/generate_url';

    protected function getConfig($name)
    {
        if (!$name) return;
        $storeId = Mage::app()->getStore()->getId();

        return Mage::getStoreConfig($name, $storeId);
    }

    public function isEnabled()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_ENABLE);
    }

    public function getProductExportAll()
    {
        return $this->getConfig(self::XML_PATH_PRODUCT_EXPORT_ALL);
    }

    public function getProductExportBasic()
    {
        return $this->getConfig(self::XML_PATH_PRODUCT_EXPORT_BASIC);
    }

    public function getProductExportStock()
    {
        return $this->getConfig(self::XML_PATH_PRODUCT_EXPORT_STOCK);
    }

    public function getProductImport()
    {
        return $this->getConfig(self::XML_PATH_PRODUCT_IMPORT);
    }

    public function getGenerateSku()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_GENERATE_SKU);
    }

    public function getGenerateSkuLength()
    {
        $length = $this->getConfig(self::XML_PATH_GENERAL_SKU_LENGTH);
        if (!is_numeric($length) OR $length > 0) $length = 18;
        return $length;
    }

    public function getGenerateUrl()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_GENERATE_URL);
    }


}