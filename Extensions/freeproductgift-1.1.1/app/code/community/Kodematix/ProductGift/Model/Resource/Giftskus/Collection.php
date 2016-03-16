<?php
/**
 * 
 * @author Kodematix 
 */
class Kodematix_ProductGift_Model_Resource_Giftskus_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('productgift/giftskus');
    }
}

?>