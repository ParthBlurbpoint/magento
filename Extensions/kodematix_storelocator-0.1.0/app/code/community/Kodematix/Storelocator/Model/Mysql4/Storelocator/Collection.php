<?php
    class Kodematix_Storelocator_Model_Mysql4_Storelocator_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {

		public function _construct(){
			$this->_init("storelocator/storelocator");
			  $this->_map['fields']['storelocator_id'] = 'main_table.storelocator_id';
      		  $this->_map['fields']['store']   = 'store_table.store_id';
		}
		
		    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }

            if (!is_array($store)) {
                $store = array($store);
            }

            if ($withAdmin) {
                $store[] = Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $this->addFilter('store', array('in' => $store), 'public');
        }
        return $this;
    }
    
	   public function addStatusFilter()
    {
        return $this->addFieldToFilter('status', 1);
    }
  public function prepareForList($page)
    {
        //Set collection page size
        $this->setPageSize(Mage::helper('storelocator')->getStoresPerPage());
        //Set current page
        $this->setCurPage($page);
        //Set select order
        $this->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC);
        return $this;
	}
   /*     public function addStoreFilter($store, $withAdmin = true)
    {
		
        if (!$this->getFlag('store_filter_added')) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }

            if (!is_array($store)) {
                $store = array($store);
            }

            if ($withAdmin) {
                $store[] = Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $this->addFilter('store', array('in' => $store), 'public');
        }
        return $this;
    }
    */
     /**
     * Join store relation table if there is store filter
     */
    protected function _renderFiltersBefore()
    {
        if ($this->getFilter('store')) {
            $this->getSelect()->join(
                array('store_table' => $this->getTable('storelocator')),
                'main_table.storelocator_id = store_table.storelocator_id',
                array()
            );
        }
        return parent::_renderFiltersBefore();
    }
    }
	 