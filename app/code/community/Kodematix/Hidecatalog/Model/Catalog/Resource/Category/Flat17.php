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

class Kodematix_Hidecatalog_Model_Catalog_Resource_Category_Flat17
    extends Mage_Catalog_Model_Resource_Category_Flat
{
    /**
     * We need to rewrite this class to be able to filter hidden categories if the
     * flat catalog category is enabled.
     * 
     * This is the version of the rewrite for Magento 1.6 and 1.7.
     * In Magento 1.8 the method signature changed.
     *
     * @param Mage_Catalog_Model_Category|int $parentNode
     * @param integer $recursionLevel
     * @param integer $storeId
     * @return Mage_Catalog_Model_Resource_Category_Flat
     */
    protected function _loadNodes($parentNode = null, $recursionLevel = 0, $storeId = 0)
    {
        $nodes = parent::_loadNodes($parentNode, $recursionLevel, $storeId);

        /* @var $helper Kodematix_Hidecatalog_Helper_Data */
        $helper = Mage::helper('kodematix_hidecatalog');
        if ($helper->isModuleActive() && !$helper->isDisabledOnCurrentRoute()) {
            // Filter out hidden nodes
            if (count($nodes) > 0) {
                $nodeIds = array_keys($nodes);
                $visibleIds = Mage::getResourceSingleton('kodematix_hidecatalog/filter')
                        ->getVisibleIdsFromEntityIdList(
                            Mage_Catalog_Model_Category::ENTITY, $nodeIds, $storeId, $helper->getCustomerGroupId()
                        );
                $nodes = array_intersect_key($nodes, array_flip($visibleIds));
            }
        }
        return $nodes;
    }
}
