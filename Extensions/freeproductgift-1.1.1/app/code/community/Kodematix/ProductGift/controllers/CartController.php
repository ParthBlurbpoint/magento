<?php
/**
 * Product Gift cart controller
 * @author Kodematix 
 */
require_once 'Mage/Checkout/controllers/CartController.php';
class Kodematix_ProductGift_CartController extends Mage_Checkout_CartController
{
    const XML_PATH_ENABLED  = 'productgiftsection/productgiftgroup/productgiftstatus';
   
    public function addAction()
    {        
        $cart   = $this->_getCart();
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');
			  
            
            
            if (!$product) {
                $this->_goBack();
                return;
            }

            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }
                
            $cart->save();
            
            $this->_getSession()->setCartWasUpdated(true);
            
            $free_product = $this->getRequest()->getParam('free_gift');
            
            $isGiftAvl=Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), 'sku_of_product_gift',Mage::app()->getStore()->getStoreId());
            $isGiftEn=Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), 'is_product_gift_enabled',Mage::app()->getStore()->getStoreId());
            $isGiftFrom=strtotime(Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), 'product_gift_active_from',Mage::app()->getStore()->getStoreId()));
            $isGiftTo=  strtotime(Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), 'product_gift_active_to',Mage::app()->getStore()->getStoreId()));
            
            if(empty($free_product) && $isGiftEn && !empty($isGiftAvl))
            {
                $allSku = explode(",", $isGiftAvl);
                $firstSku = $allSku[0];
                //$free_product = $this->getRequest()->getParam('free_gift');
                 $firstProductId = Mage::getModel("catalog/product")->getIdBySku($firstSku);
                 Mage::getSingleton('core/session')->setMyGiftId($firstProductId);
                 $free_product=$firstProductId;
                
            }
            elseif(empty($isGiftFrom) && empty($isGiftTo) && $isGiftEn && !empty($isGiftAvl))
            {
              
            }
           
            
            if(!empty($free_product) && Mage::helper('core')->isModuleOutputEnabled('Kodematix_ProductGift') && Mage::getStoreConfigFlag(self::XML_PATH_ENABLED))
            { 
                Mage::getSingleton('core/session')->setMyGiftId($free_product);
                $productAddId=$product->getId();
                $quoteId= Mage::getSingleton('checkout/session')->getQuoteId();//add ltr
                $read1 = Mage::getSingleton("core/resource")->getConnection("core_read");
                $quoteSelect=$read1->fetchAll("select item_id,MAX(updated_at) from sales_flat_quote_item where quote_id='$quoteId' and product_id='$productAddId'");
                $itemQuoteGift=(int)$quoteSelect[0]['item_id']+1;
                $productId=$product->getId();
               
                $write = Mage::getSingleton("core/resource")->getConnection("core_write");
                $read = Mage::getSingleton("core/resource")->getConnection("core_read");
                $querySelect=$read->fetchAll("select * from giftskus where quote_id='$quoteId' and product_id='$productId'");
                
                if($querySelect==NULL){
                 
                $query = "insert into giftskus (quote_id, product_id, gift_product_id, gift_product_item_id) values (:quote_id, :product_id, :gift_product_id, :gift_product_item_id)";
                
                $binds = array(
                    'quote_id'     => $quoteId,
                    'product_id'   => $productId,
                    'gift_product_id'  => $free_product,
                    'gift_product_item_id' => $itemQuoteGift
                );
                
                $write->query($query, $binds);
                }
                
                $queryUpdate="UPDATE `sales_flat_quote_item` SET `product_id`=$free_product WHERE `item_id`=$itemQuoteGift";
                //print_r($queryUpdate);exit;
                $write->query($queryUpdate);
                
            }
            /**
             * @todo remove wishlist observer processAddToCart
             */
            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );

            if (!$this->_getSession()->getNoCartRedirect(true)) {
                if (!$cart->getQuote()->getHasError()) {
                    $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                    $this->_getSession()->addSuccess($message);
                }
                $this->_goBack();
            }
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            $url = $this->_getSession()->getRedirectUrl(true);
            if ($url) {
                $this->getResponse()->setRedirect($url);
            } else {
                $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
            }
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart.'));
            Mage::logException($e);
            $this->_goBack();
        }
    }
    /**
     * Update product configuration for a cart item
     */
    public function updateItemOptionsAction()
    {   
        $cart   = $this->_getCart();
        $id = (int) $this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();
        
        $sessionItemId = (int) $this->getRequest()->getParam('id');
        Mage::getSingleton('core/session')->setItemIdSession($sessionItemId);
        $free_product = $this->getRequest()->getParam('free_gift');
        Mage::getSingleton('core/session')->setMyGiftId($free_product);
       
        Mage::getSingleton('core/session')->setGiftQty($params['qty']);
        if (!isset($params['options'])) {
            $params['options'] = array();
        }
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $quoteItem = $cart->getQuote()->getItemById($id);
            if (!$quoteItem) {
                Mage::throwException($this->__('Quote item is not found.'));
            }

            $item = $cart->updateItem($id, new Varien_Object($params));
            if (is_string($item)) {
                Mage::throwException($item);
            }
            if ($item->getHasError()) {
                Mage::throwException($item->getMessage());
            }

            $related = $this->getRequest()->getParam('related_product');
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_update_item_complete',
                array('item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
            if (!$this->_getSession()->getNoCartRedirect(true)) {
                if (!$cart->getQuote()->getHasError()) {
                    $message = $this->__('%s was updated in your shopping cart.', Mage::helper('core')->escapeHtml($item->getProduct()->getName()));
                    $this->_getSession()->addSuccess($message);
                }
                $this->_goBack();
            }
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $this->_getSession()->addNotice($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->_getSession()->addError($message);
                }
            }

            $url = $this->_getSession()->getRedirectUrl(true);
            if ($url) {
                $this->getResponse()->setRedirect($url);
            } else {
                $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
            }
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot update the item.'));
            Mage::logException($e);
            $this->_goBack();
        }
        $this->_redirect('*/*');
    }
   
     public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $giftId=$id+1;
        $queryDelete="delete from giftskus where gift_product_item_id =$giftId";
        $write->query($queryDelete);
        if ($id) {
            try {
                $this->_getCart()->removeItem($id)
                  ->save();
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Cannot remove the item.'));
                Mage::logException($e);
            }
        }
        $this->_redirectReferer(Mage::getUrl('*/*'));
    }
     public function updatePostAction()
    {   

        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');

        switch ($updateAction) {
            case 'empty_cart':
                $this->_emptyShoppingCart();
                
                break;
            case 'update_qty':
                $this->_updateShoppingCart();
                break;
            default:
                $this->_updateShoppingCart();
        }

        $this->_goBack();
    }
     protected function _emptyShoppingCart()
    {
        try {
            $this->_getCart()->truncate()->save();
            $this->_getSession()->setCartWasUpdated(true);
            $quoteId= Mage::getSingleton('checkout/session')->getQuoteId();
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            $queryDelete="delete from giftskus where quote_id=".$quoteId;
            //print_r($queryUpdate);exit;
            $write->query($queryDelete);
        } catch (Mage_Core_Exception $exception) {
            $this->_getSession()->addError($exception->getMessage());
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot update shopping cart.'));
        }
    }

}


