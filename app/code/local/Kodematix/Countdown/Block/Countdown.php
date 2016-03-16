<?php
class Kodematix_Countdown_Block_Countdown extends Mage_Catalog_Block_Product_View
{
	public $timerattribute = "countdown";
	public $messageattribute = "countdowntext";
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getCountdown()     
     { 
        if (!$this->hasData('countdown')) {
            $this->setData('countdown', Mage::registry('countdown'));
        }
        return $this->getData('countdown');
        
    }
    
    public function getTime()
    {
    	$product = $this->getProduct();
    	if(($producttime = $product->getData($this->timerattribute)) != null){
    		$producttime = strtotime($producttime);
    		return date("Y m d H:i:s",$producttime);
    	}
    	return null;
    }
    
    public function getDeliveryMessage()
    {
    	$product = $this->getProduct();
    	if(($productmessage = $product->getData($this->messageattribute)) != null){
    		return $productmessage;
    	}
    	return null;
    }
   
    public function _toHtml()
    {
    	if(!Mage::helper("countdown")->isEnabled()){
    		return null;
    	}
    	$html = parent::_toHtml();
    	return $html;
    }
}