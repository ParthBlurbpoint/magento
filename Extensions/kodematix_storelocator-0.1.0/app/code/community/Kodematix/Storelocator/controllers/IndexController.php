<?php
class Kodematix_Storelocator_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
		
 	 $this->loadLayout();
        
        $listBlock = $this->getLayout()->getBlock('stores.list');
        if ($listBlock) {
            $currentPage = abs(intval($this->getRequest()->getParam('p')));
            if ($currentPage < 1) {
                $currentPage = 1;
            }
            $listBlock->setCurrentPage($currentPage);
        }
        
        $this->renderLayout();
	  
    }
	public function searchAction() 
    { 
        //search form validation
        $post = $this->getRequest()->getQuery();
        if ( !empty($post) ) {
            try {
                $error = false;
                if(isset($post['country'])) {
                    if (Zend_Validate::is(trim($post['country']) , 'NotEmpty')) {
                        $error = true;
                    }
                }

                if(isset($post['state'])) {
                    if (Zend_Validate::is(trim($post['state']) , 'NotEmpty')) {
                        $error = true;
                    }
                }

                if(isset($post['city'])) {
                    if (Zend_Validate::is(trim($post['city']), 'NotEmpty')) {
                        $error = true;
                    }
                }
                if (!$error) {
                    throw new Exception('Please enter Country or State or City');
                }
            } catch (Exception $e) {

                Mage::getSingleton('core/session')->addError($e->getMessage());
                Mage::getSingleton('core/session')->setSearchFormData($post);
                $this->_redirect('*/*/*');
                return;
            }
        }

        Mage::getSingleton('core/session')->setSearchFormData(false);

        $this->loadLayout();

        $listBlock = $this->getLayout()->getBlock('stores.list');
	//	print_r($listBlock);die();
        if ($listBlock) {
            $currentPage = abs(intval($this->getRequest()->getParam('p')));
            if ($currentPage < 1) {
                $currentPage = 1;
            }
            $listBlock->setCurrentPage($currentPage);
        }

        $this->renderLayout();
    }
	public function viewAction()
    {
        $storelocatorId = $this->getRequest()->getParam('id');
        if (!$storelocatorId) {
            return $this->_forward('noRoute');
        }
		//$storeId = Mage::app()->getStore()->getId();
       
        $model = Mage::getModel('storelocator/storelocator')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($storelocatorId);
		//echo "<pre>";	print_r($model);die();

        if (!$model->getId()) {
            return $this->_forward('noRoute');
        }
		  Mage::register('store_view', $model);
		
        $this->loadLayout();
        $this->renderLayout();
    }
	
    
}