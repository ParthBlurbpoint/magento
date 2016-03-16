<?php

class Kodematix_Storelocator_Model_Storelocator extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("storelocator/storelocator");

    }
	  public function storeExists($storeName, $storelocatorId = null)
    {
        $result = $this->_getResource()->storeExists($storeName, $storelocatorId);
        return (is_array($result) && count($result) > 0) ? true : false;
    }
	public function uploadAndImport($object)
    {
         $resultObj = $this->_getResource()->uploadAndImport($object);
         if($resultObj){
             return $resultObj;
         }
    }

}
	 