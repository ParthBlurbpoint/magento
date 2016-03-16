<?php
class Kodematix_Splitorder_Model_Observer
{

	public function splitOrder ( $observer ) 
	{
		$is_enabled = true;
		$oldQuote = Mage::getSingleton("checkout/session")->getQuote();
		$quoteItems = $oldQuote->getAllItems();
		$backOrder = false;

		// to check all quote items and see if quantity is less than 0
		foreach ( $quoteItems as $item ) 
		{
			$productId = $item->getProductId();
			$productInvryCount = Mage::getModel('cataloginventory/stock_item')
			->loadByProduct($item->getProduct())
			->getQty();

			if( $productInvryCount < 0 )
			{
				$backOrder = true;
			}
		}

		if ( $backOrder && $is_enabled)
		{
			$splitOrder = Mage::getModel('splitorder/SplitOrder');
			$olderQuote = $this->_getQuote();

			// to get all the quote items and customer variables
			$quoteItems = $olderQuote->getAllItems();
			$store = Mage::app()->getStore('default');
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			$session = Mage::getSingleton('customer/session', array('name'=>'frontend'));

			//create a new quote and assign a customer to that quote who has placed the order
			$quote = Mage::getModel('sales/quote');
			$quote->setStore($store);
			$quote->assignCustomer($customer);

			foreach ( $quoteItems as $item ) 
			{
				$productId = $item->getProductId();
				$productInvryCount = Mage::getModel('cataloginventory/stock_item')
				->loadByProduct($item->getProduct())
				->getQty();

				if( $productInvryCount < 0 )
				{
					/* remove the item fro which need to split the order */
					$buyRequest = $item->getBuyRequest();
					$quote->addProduct($item->getProduct(), $buyRequest);
					$olderQuote->removeItem($item->getId())->save();
					$olderQuote->collectTotals()->save();
				}
			}

			// save the shipping and billing addresses
			$existingShipAddress = $olderQuote->getShippingAddress();
			$existingBillAddress = $olderQuote->getBillingAddress();
			$paymentData = Mage::app()->getRequest()->getPost();

			if($session->isLoggedIn())
			{
				$shippingAddress = $quote->getShippingAddress();

			}
			else 
			{
				$quote->setBillingAddress($olderQuote->getBillingAddress());
				$quote->setShippingAddress($olderQuote->getShippingAddress());
				$shippingAddress = $quote->getShippingAddress();
			}

			$shippingAddress->setCollectShippingRates(true)
			->collectShippingRates()
			->setShippingMethod($olderQuote->getShippingAddress()->getShippingMethod());
			// Set the payment method
			$quote->getPayment()->importData(array('method' => 'checkmo'));
			$quote->collectTotals()->save();
			$service = Mage::getModel('sales/service_quote', $quote);
			$service->submitAll();
			$quote->save();
			$order = $service->getOrder()->getId();

			$olderQuote = $this->_getQuote();
			$olderQuote->getShippingAddress()->unsetData('cached_items_all');
			$olderQuote->getShippingAddress()->unsetData('cached_items_nominal');
			$olderQuote->getShippingAddress()->unsetData('cached_items_nonnominal');

			$olderQuote->getShippingAddress()->setCollectShippingRates(true)
			->collectShippingRates();

			$olderQuote->setTotalsCollectedFlag(false)->collectTotals()->save();
		}
		
	}

	public function _getQuote()
	{
		return Mage::getSingleton("checkout/session")->getQuote();
	}

}
?>