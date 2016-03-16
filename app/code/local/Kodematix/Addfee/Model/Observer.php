<?php
class Kodematix_Addfee_Model_Observer
{
	public function modifyFeeAfterOrder($observer)
	{
		$event = $observer->getEvent()->getName();
		$quote = $observer->getEvent()->getQuote();
		$order = $observer->getEvent()->getOrder();
		
		$order->setOrderFee(100);
		$order->setOrderNtotal(1000);
		
		Mage::log("Order Fee : ".$order->getOrderFee());
		Mage::log("Order Total : ".$order->getOrderNtotal());
	}
}
?>