<?php
class MD_Partialpayment_Model_Mysql4_Slabs_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct() {
        parent::_construct();
        $this->_init('md_partialpayment/slabs');
    }
}

