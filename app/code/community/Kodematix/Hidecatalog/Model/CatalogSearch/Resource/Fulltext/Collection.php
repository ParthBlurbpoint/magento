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

class Kodematix_Hidecatalog_Model_CatalogSearch_Resource_Fulltext_Collection
    extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
    /**
     * Add the hidecatalog filter to the select object so the number of search
     * results on the pager is correct.
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $select = parent::getSelectCountSql();
        $helper = Mage::helper('kodematix_hidecatalog');
        if ($helper->isModuleActive() && !$helper->isDisabledOnCurrentRoute()) {
            $customerGroupId = $helper->getCustomerGroupId();
            Mage::getResourceSingleton('kodematix_hidecatalog/filter')
                    ->addGroupsCatalogFilterToSelectCountSql($select, $customerGroupId, $this->getStoreId());
        }
        return $select;
    }
}
