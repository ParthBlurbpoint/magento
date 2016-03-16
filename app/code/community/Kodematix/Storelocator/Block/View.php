<?php
class Kodematix_Storelocator_Block_View extends Mage_Core_Block_Template
{ 
    protected $_store;
    protected function _getBackUrlQueryParams($additionalParams = array())
    {
        return array_merge(array('p' => $this->getPage()), $additionalParams);
    }
    public function getBackUrl()
    {
        return $this->getUrl('*/', array('_query' => $this->_getBackUrlQueryParams()));
    }
    public function getImageUrl($store, $width)
    {
        return Mage::helper('storelocator/image')->resize($store, $width);
    }
    public function getZoomLevel($storelocator=NULL)
    {
        //Return store zoom level
        if(!is_null($storelocator)) {
            if($storelocator->getZoomLevel()){
                return $storelocator->getZoomLevel();
            } else {
                // Return default config zoom level
                return Mage::helper('storelocator')->getConfigZoomLevel();
            }
        } else {
            // Return default config zoom level
            return Mage::helper('storelocator')->getConfigZoomLevel();
        }
        return;
    }
    public function getRadius($storelocator=NULL)
    {
        //Return store radius
        if(!is_null($storelocator)) {
            if($storelocator->getRadius()){
                return $storelocator->getRadius();
            } else {
                // Return default config radius
                return Mage::helper('storelocator')->getConfigRadius();
            }
        } else {
            // Return default config radius
            return Mage::helper('storelocator')->getConfigRadius();
        }
        return;
    }
    public function getCountryName($countryCode)
    {
        return $this->helper('storelocator')->getCountryNameByCode($countryCode);
    }
}