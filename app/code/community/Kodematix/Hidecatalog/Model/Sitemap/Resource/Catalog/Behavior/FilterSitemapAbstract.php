<?php


abstract class Kodematix_Hidecatalog_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapAbstract
{
    /**
     * @var Kodematix_Hidecatalog_Model_Resource_Filter
     */
    protected $_groupsCatalogFilter;

    /**
     * @var Kodematix_Hidecatalog_Helper_Data
     */
    protected $_groupsCatalogHelper;

    /**
     * @var int The store id specified during the last call to getCollection()
     */
    protected $_storeId;

    /**
     * DI getter
     *
     * @return Kodematix_Hidecatalog_Model_Resource_Filter
     */
    protected function _getGroupsCatalogFilter()
    {
        if (!$this->_groupsCatalogFilter) {
            $this->_groupsCatalogFilter = Mage::getResourceModel('kodematix_hidecatalog/filter');
        }
        return $this->_groupsCatalogFilter;
    }

    /**
     * DI getter
     *
     * @return Kodematix_Hidecatalog_Helper_Data
     */
    protected function _getGroupsCatalogHelper()
    {
        if (!$this->_groupsCatalogHelper) {
            $this->_groupsCatalogHelper = Mage::helper('kodematix_hidecatalog');
        }
        return $this->_groupsCatalogHelper;
    }

    /**
     * DI setter
     *
     * @param Kodematix_Hidecatalog_Model_Resource_Filter $filter
     */
    public function setGroupsCatalogResourceFilter(Kodematix_Hidecatalog_Model_Resource_Filter $filter)
    {
        $this->_groupsCatalogFilter = $filter;
    }

    /**
     * DI setter
     *
     * @param Kodematix_Hidecatalog_Helper_Data $helper
     */
    public function setGroupsCatalogHelper(Kodematix_Hidecatalog_Helper_Data $helper)
    {
        $this->_groupsCatalogHelper = $helper;
    }

    /**
     * Set the store Id
     *
     * @param int $storeId
     * @return array
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
    }

    /**
     * Add the NOT LOGGED IN group filter to the sitemap collection load select instance
     * 
     * @param Varien_Db_Select $select
     */
    public function addNotLoggedInGroupFilter(Varien_Db_Select $select)
    {
        $helper = $this->_getGroupsCatalogHelper();
        if ($helper->isModuleActive($this->_storeId)) {
            $groupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
            $this->_addFilter($select, $groupId, $this->_storeId);
        }
    }

    /**
     * Call the appropriate method on the hidecatalog filter depending on entity type.
     * 
     * @param Varien_Db_Select $select
     * @param int $groupId
     * @param int $storeId
     */
    abstract protected function _addFilter(Varien_Db_Select $select, $groupId, $storeId);
} 