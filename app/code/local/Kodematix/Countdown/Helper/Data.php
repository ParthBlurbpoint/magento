<?php

class Kodematix_Countdown_Helper_Data extends Mage_Core_Helper_Abstract
{
	
		public $timerattribute = "countdown";
		public $messageattribute = "countdowntext";
	public function getConfig($path, $store = null)
	{
		if($store == null){
			$store = Mage::app()->getStore()->getId();
		}
		return Mage::getStoreConfig("countdown/countdown/".$path,$store);
	}
	public function isEnabled()
	{
		return $this->getConfig("enabled");
	}
	
	public function getSecondColor()
	{
		return $this->getConfig("secondcolor");
	}
	
	public function getMinuteColor()
	{
		return $this->getConfig("minutecolor");
	}
	
	public function getHourColor()
	{
		return $this->getConfig("hourcolor");
	}
	
	public function getDayColor()
	{
		return $this->getConfig("daycolor");
	}
	
	  public function getTime($product)
				{
					//$product = $this->getProduct();
					if(($producttime = $product->getData($this->timerattribute)) != null){
						$producttime = strtotime($producttime);
						return date("Y m d H:i:s",$producttime);
					}
					return null;
				}
	
	
	public function getDeliveryMessage($product)
    {
    	//$product = $this->getProduct();
    	if(($productmessage = $product->getData($this->messageattribute)) != null){
    		return $productmessage;
    	}
    	return null;
    }
	
}


