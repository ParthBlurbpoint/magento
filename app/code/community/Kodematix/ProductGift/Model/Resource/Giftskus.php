<?php
/**
 * 
 * @author Kodematix 
 */
class Kodematix_ProductGift_Model_Resource_Giftskus extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
    {    
    	$this->_init('productgift/giftskus', 'id');
    }	
}