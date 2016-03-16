<?php
class MD_Partialpayment_Model_Mysql4_Slabs extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct() {
        $this->_init('md_partialpayment/slabs','slab_id');
    }
}

