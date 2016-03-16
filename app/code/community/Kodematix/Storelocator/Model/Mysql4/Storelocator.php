<?php
class Kodematix_Storelocator_Model_Mysql4_Storelocator extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("storelocator/storelocator", "store_id");
    }
}