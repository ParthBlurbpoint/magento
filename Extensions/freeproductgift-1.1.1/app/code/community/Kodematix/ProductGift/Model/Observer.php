<?php
/**
 *
 * @author Kodematix
 * @description : this is the observer file for checkout events
 *
 */

class Kodematix_ProductGift_Model_Observer extends Mage_Core_Model_Abstract{ 
	protected $_dsr=null;
	const XML_PATH_ENABLED  = 'productgiftsection/productgiftgroup/productgiftstatus';	

	/**
     * Get id of gift added
     *
     * @return Mage_Checkout_Model_Session
     */
	public function addGift($observer)
	{
		
		
		
		$check = Mage::getModel('productgift/giftskus')->getCollection();
//		print_r($check);
		$giftCount = count($check);

if($giftCount > 0):
	
        if(Mage::helper('core')->isModuleOutputEnabled('Kodematix_ProductGift') && Mage::getStoreConfigFlag(self::XML_PATH_ENABLED)){
		$productData = $observer->getEvent()->getProduct();
		$quoteData = $observer->getEvent()->getQuoteItem();

		if (!$productData->getData('is_product_gift_enabled')) return $this;
		// Checking if Gift Active time is expired
		$giftActiveFrom = $productData->getData('product_gift_active_from');
		$giftActiveTo = $productData->getData('product_gift_active_to');
		$timestamp1 = strtotime($giftActiveFrom);
		$timeNow = time();
		$timestamp2 = strtotime($giftActiveTo);
                
                if(empty($timestamp1) && empty($timestamp2))
                {
                    
                }
		elseif(!($timeNow > $timestamp1 && $timeNow < $timestamp2)){
		   return $this;
		}
		// Fetch all cart items
		$obj=Mage::getSingleton('checkout/cart');
		$quoteItem = $obj->getQuote();
		// Get All Gift options available 
		$skuGift=$productData->getData('sku_of_product_gift');
		$skuOfGiftItem = explode(',',$skuGift);
		//Check the Quantity of free gift added to cart
		if ($quoteData->getParentItem()){
			$qtyCurrent=$quoteData->getParentItem()->getQty();
			$qtyActual=$quoteData->getParentItem()->getQtyToAdd();
		}else{
			$qtyCurrent=$quoteData->getQty();
			$qtyActual=$quoteData->getQtyToAdd();
		}
		//Get the selected gift product From Session
		$giftCustomId = Mage::getSingleton('core/session')->getMyGiftId();
		$cartItems = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();

	
		if(isset($giftCustomId)){
		  $prodGift = Mage::getModel('catalog/product')->load($giftCustomId); 
		}
		  else{
		  $prodGift = Mage::getModel('catalog/product')->loadByAttribute('sku', $skuOfGiftItem[0]);
		}


		
          if ($qtyCurrent != $qtyActual) { 			
                foreach ($quoteItem->getItemsCollection() as $it) {				
                        $ops=$it->getOptionByCode('gift_for_product_id');
                        if($ops) {
                                if ($ops->getValue()==$quoteData->getProduct()->getId()) {
                                        $it->setQty($qtyCurrent);
                                        $quoteItem->save();
                                        return $this;
                                }					
                        }			
                } 
          }
		
            $prodGiftId = array($prodGift->getId()); 
            $oldi=$quoteItem->getItemByProduct($prodGift);
            if ($oldi) {  
                    $option = Mage::getModel('sales/quote_item_option')
                    ->setProductId($prodGift->getId())
                    ->setCode('gift_for_product_id')
                    ->setProduct($prodGift)
                    ->setValue(null);
                    $oldi->addOption($option);
                    $quoteItem->save();
            }

            $gicu=$obj->addProductsByIds($prodGiftId);
            $giftItem = $quoteItem->getItemByProduct($prodGift);
            
            $option = Mage::getModel('sales/quote_item_option')
            ->setProductId($prodGift->getId())
            ->setCode('gift_for_product_id')
            ->setProduct($prodGift) 
            ->setValue($quoteData->getProduct()->getId());

            $giftItem->addOption($option);
            $item = $giftItem;
            $additionalOptions = array(
                array(
                    'code'  => 'my_code',
                    'label' => 'Gift of '.$productData->getName(),
                    'value' => 'Free Product!!!'
                )
            );
            $item->addOption(
                array(
                     'code'  => 'additional_options',
                     'value' => serialize($additionalOptions),
                )
            );
            $options=$giftItem->getOptionsByCode();
            // Updating the price of free Gift product to Zero
            $giftItem->setCustomPrice(0);
            $giftItem->setOriginalCustomPrice(0);
            $giftItem->getProduct()->setIsSuperMode(true);
            $giftItem->setQty($qtyCurrent);
            $quoteItem->save();  
			Mage::getSingleton('core/session')->unsMyGiftId();
				unset($giftCustomId);
			return $this;
		}
		endif;
	}
	/**
	 * Update the products in Cart
     * Get id of gift added
     *
     * @return Mage_Checkout_Model_Session
     */
	public function updatePGift($observer) {
		$check = Mage::getModel('productgift/giftskus')->getCollection();
//		print_r($check);
		$giftCount = count($check);

if($giftCount > 0):
	     if(Mage::helper('core')->isModuleOutputEnabled('Kodematix_ProductGift') && Mage::getStoreConfigFlag(self::XML_PATH_ENABLED)){
            $productData = $observer->getEvent()->getProduct();
            $free_product = Mage::getSingleton('core/session')->getMyGiftId();
            $quoteId= Mage::getSingleton('checkout/session')->getQuoteId();//add ltr
            $productId=$productData->getId();
            $quoteItemId=Mage::getSingleton('core/session')->getItemIdSession()+1;    
            $giftQty=Mage::getSingleton('core/session')->getGiftQty();
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            $querySelect = Mage::getModel('productgift/giftskus')->getCollection()
                    ->addFieldToFilter('product_id',$productId)
                    ->addFieldToFilter('quote_id',$quoteId)
                    ->getFirstItem();
            $isModelSet=$querySelect->getId();
 
            if(!empty($isModelSet)){
               $queryUpdate="UPDATE `sales_flat_quote_item` SET `product_id`=$free_product,`qty`=$giftQty WHERE `item_id`=$quoteItemId";
               $write->query($queryUpdate);
            }

		return $this;
		}
		endif;
	}
	/**
	 * Delete the products in Cart
     * Get id of gift added
     *
     * @return Mage_Checkout_Model_
     */
	public function deletePGift($observer) {
                             
		$quoteItem=$observer->getEvent()->getQuoteItem(); 
		$productId= Mage::getModel('catalog/product')->load($quoteItem->getProduct()->getId() );
		$cil=$quoteItem->getChildren();
		if (!empty($cil)) {
			$idd= current($quoteItem->getChildren())->getProduct()->getId() ;
		} 
                else {
			$idd=$quoteItem->getProduct()->getId() ;
		}
		$is = $productId->getData('is_product_gift_enabled');
		if (!$is) return $this;
		
		$obj=Mage::getSingleton('checkout/cart');
		$quoteItemNew=$obj->getQuote();
		foreach ($quoteItemNew->getItemsCollection() as $it) {	
			$ops=$it->getOptionByCode('gift_for_product_id');
			if($ops) {
				if ($ops->getValue()==$idd) {
					$obj->removeItem($it->getItemId());
					$quoteItemNew->save();
					return $this;
				}	
			}
		}
		return $this;
	}
	/**
	 * Check SKU are valid in Admin
     * Get id of gift added
     *
     * @return Mage_Checkout_Model_Session
     */
	public function checkSKU($observer) {
        if(Mage::helper('core')->isModuleOutputEnabled('Kodematix_ProductGift') && Mage::getStoreConfigFlag(self::XML_PATH_ENABLED)){    
             $productItem = $observer->getEvent()->getProduct();
             $productsku =$productItem->getSku();
            $productEnabled =$productItem->getIsProductGiftEnabled();
            if($productEnabled){
                $skuGift=$productItem->getData('sku_of_product_gift');
                   if(empty($skuGift))
                {
                    $productItem->setData('is_product_gift_enabled','0');
                    $productItem->save();
               
                   return;
                    
                }
                $productSkus = explode(',',$skuGift);
				$productSkusUnique = array_unique($productSkus);
				if(count($productSkus)!=count($productSkusUnique))
				{
				  Mage::throwException("Duplicate Sku's are not allowed");
				} 
                $collection = Mage::getModel('catalog/product')->getCollection();
                $collection->addAttributeToSelect('is_product_gift_enabled');
                $collection->addAttributeToSelect('sku_of_product_gift');	
                $collection->addFieldToFilter(array(
                        array('attribute'=>'is_product_gift_enabled','eq'=>'1'),
                ));
                $collection->addFieldToFilter(array(
                        array('attribute'=>'sku_of_product_gift','like'=>'%'.$productsku.'%'),
                ));
            
                foreach($collection->getData() as $dataSku)
                { 
                  $collectionSkus = explode(',',$dataSku['sku_of_product_gift']);
                    if(in_array($productsku,$collectionSkus))
                    {
                         Mage::throwException('This product is already a gift product');
                    }
                }

               if (!$productItem->getData('is_product_gift_enabled')) return $this;
              
               $FromGift=strtotime($productItem->getData('product_gift_active_from'));
               $ToGift=strtotime($productItem->getData('product_gift_active_to'));
               
               foreach($productSkus as $sku):
			           $skuLoad=Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                        if(!empty($skuLoad) && Mage::getModel('catalog/product')->loadByAttribute('sku', $sku)->getData('type_id')!='simple'){Mage::throwException('Only simple products are allowed as a gift');           }
                       if (!Mage::getModel('catalog/product')->loadByAttribute('sku', $sku)) Mage::throwException('SKU for gift is invalid!');
                       if(Mage::getModel('catalog/product')->loadByAttribute('sku', $sku)->getData('is_product_gift_enabled'))
                       {
                           Mage::throwException('SKU : '.$sku.' is having a gift product');
                       }
               endforeach;
               if($FromGift>$ToGift){
                   Mage::throwException('Invalid From and To Dates.');
               }
               elseif((empty($FromGift) && !empty($ToGift)) || (!empty($FromGift) && empty($ToGift))){
                   Mage::throwException('Invalid Dates.');
               }
            }
		 }	
       }
 
      public function updtatePGiftA($observer) {
               if(Mage::helper('core')->isModuleOutputEnabled('Kodematix_ProductGift') && Mage::getStoreConfigFlag(self::XML_PATH_ENABLED)){
               $dat=$observer->getEvent()->getInfo();
               $itemList=array();
               $read = Mage::getSingleton("core/resource")->getConnection("core_read");
               $querySelect=$read->fetchAll("select gift_product_item_id from giftskus");
               foreach($querySelect as $testData){
                   array_push($itemList,$testData[gift_product_item_id]-1);
                }
               
               $prev_i=null;
               foreach ($dat as $itemId => $itemInfo){
                 
                       if(in_array($itemId,$itemList)){
                         
                            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
                            $giftQty=$itemInfo['qty'];
                            $itemIdNext=(int)$itemId+1;
                            $queryUpdate="UPDATE `sales_flat_quote_item` SET `qty`=$giftQty WHERE `item_id`=$itemIdNext";
                            $write->query($queryUpdate);
                       }
                      
                              
               } 
          
               return $this;
			   }
       }
       public function noUpdateGift($observer)
       {
            $controllerAction = $observer->getControllerAction();
            $request = $controllerAction->getRequest();
            $controllerName = $request->getControllerName();
            $actionName = $request->getActionName();
            $itemid = $request->getParam('id');
            if($controllerName=='cart' && ($actionName == 'configure' || $actionName == 'delete'))
            {
                    $giftItem = Mage::getModel('productgift/giftskus')->getCollection()
                    ->addFieldToFilter('gift_product_item_id',$itemid)
                    ->getFirstItem();
                   $giftItemId=$giftItem->getId();
                   if(!empty($giftItemId))
                   {

                       Mage::app()->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
                       Mage::app()->getResponse()->sendResponse();
                       exit;
                   }
            }
			if($controllerName=='order' && ($actionName == 'reorder'))
			{
	        $orderId = $request->getParam('order_id');
			$order = Mage::getModel('sales/order')->load($orderId);
                        $items = $order->getAllItems();
                        $flagSimple='';
                        foreach ($items as $itemId => $item)
                        { 
						    if(number_format($item->getPrice(), 2, '.', '')=="0.00" && $item->getProductType()=="simple" && $flagSimple!=NULL && $item->getParentItemId()==NULL)
							{
							 Mage::getSingleton('checkout/session')->addError("Order having free product(s). Please add products manually.");
						     Mage::app()->getResponse()->setRedirect(Mage::getUrl(''));
							 Mage::app()->getResponse()->sendResponse();
							 exit;
							}

							$flagSimple=$item->getName();
                        }

			}

       }
	   public function checkoutValidation($observer)
       {
           $cart = Mage::getModel('checkout/cart')->getQuote();
           $flag='';
           $flagName='';
           foreach ($cart->getAllItems() as $item) 
           {
                $giftItemId=array();
                $giftItem = Mage::getModel('productgift/giftskus')->getCollection()
                           ->addFieldToFilter('gift_product_item_id',$item->getId())
                           ->getFirstItem();
                $productId=$giftItem->getProductId();
                if(!empty($productId))
                {
                    if($flag==$productId)
                    { 
                       $giftSkus=Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'sku_of_product_gift',Mage::app()->getStore()->getStoreId()); 
                       $isGiftEn=Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'is_product_gift_enabled',Mage::app()->getStore()->getStoreId());
                       $isGiftFrom=strtotime(Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'product_gift_active_from',Mage::app()->getStore()->getStoreId()));
                       $isGiftTo=  strtotime(Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'product_gift_active_to',Mage::app()->getStore()->getStoreId()));
                       $skus = explode(',',$giftSkus);
                       if(in_array($item->getSku(),$skus)!=1 || !$isGiftEn || ($isGiftTo<time() && !empty($isGiftTo)) || ($isGiftFrom>time() && !empty($isGiftFrom)) || !Mage::helper('core')->isModuleOutputEnabled('Kodematix_ProductGift') || !Mage::getStoreConfigFlag(self::XML_PATH_ENABLED))
                       {
                           Mage::throwException($flagName.' - Gift product offer is expired. Please remove it from your cart.');
                       }
                    }
                }
			                $giftItemId[]=$giftItem->getProductId();
                $flag=$item->getProductId();
                $flagName=$item->getName();
           }
		   
          return;
       }

       public function salesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
		{
			$quoteItem = $observer->getItem();
			if ($additionalOptions = $quoteItem->getOptionByCode('additional_options')) {
				$orderItem = $observer->getOrderItem();
				$options = $orderItem->getProductOptions();
				$options['additional_options'] = unserialize($additionalOptions->getValue());
				$orderItem->setProductOptions($options);
			}
		}
}