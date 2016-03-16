<?php

class Kodematix_Storelocator_Adminhtml_StorelocatorController extends Mage_Adminhtml_Controller_Action
{
		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("storelocator/storelocator")->_addBreadcrumb(Mage::helper("adminhtml")->__("Storelocator  Manager"),Mage::helper("adminhtml")->__("Storelocator Manager"));
				return $this;
		}
		public function indexAction() 
		{
			    $this->_title($this->__("Storelocator"));
			    $this->_title($this->__("Manager Storelocator"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{		    
			    $this->_title($this->__("Storelocator"));
				$this->_title($this->__("Storelocator"));
			    $this->_title($this->__("Edit Item"));
				
				$id = $this->getRequest()->getParam("id");
				
				$model = Mage::getModel("storelocator/storelocator")->load($id);
			//	echo "<pre>";print_r($model);die();
				if ($model->getId()) {
					Mage::register("storelocator_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("storelocator/storelocator");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Storelocator Manager"), Mage::helper("adminhtml")->__("Storelocator Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Storelocator Description"), Mage::helper("adminhtml")->__("Storelocator Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("storelocator/adminhtml_storelocator_edit"))->_addLeft($this->getLayout()->createBlock("storelocator/adminhtml_storelocator_edit_tabs"));
					$this->renderLayout();
				} 
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("storelocator")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("Storelocator"));
		$this->_title($this->__("Storelocator"));
		$this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("storelocator/storelocator")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("storelocator_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("storelocator/storelocator");

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Storelocator Manager"), Mage::helper("adminhtml")->__("Storelocator Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Storelocator Description"), Mage::helper("adminhtml")->__("Storelocator Description"));


		$this->_addContent($this->getLayout()->createBlock("storelocator/adminhtml_storelocator_edit"))->_addLeft($this->getLayout()->createBlock("storelocator/adminhtml_storelocator_edit_tabs"));

		$this->renderLayout();

		}
		public function saveAction()
		{

			$post_data=$this->getRequest()->getPost();


				if ($post_data) {

					try {
						
						  $path = Mage::getBaseDir('media') . DS . 'storelocatore';
						  $uploader = new Varien_File_Uploader('store_logo');
						  $uploader->setAllowedExtensions(array('jpg','png','gif'));
						  $uploader->setAllowRenameFiles(false);
						  $uploader->setFilesDispersion(false);
						  $destFile = $path.$_FILES['store_logo']['name'];
						  $filename = $uploader->getNewFileName($destFile);
						  $uploader->save($path, $filename);
					
						  $post_data['store_logo']='storelocatore/'.$filename;

						

						$model = Mage::getModel("storelocator/storelocator")
						->addData($post_data)
						->setId($this->getRequest()->getParam("id"))
						->save();
						
						

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Storelocator was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setStorelocatorData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setStorelocatorData($this->getRequest()->getPost());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					return;
					}

				}
				$this->_redirect("*/*/");
		}



		public function deleteAction()
		{
				if( $this->getRequest()->getParam("id") > 0 ) {
					try {
						$model = Mage::getModel("storelocator/storelocator");
						$model->setId($this->getRequest()->getParam("id"))->delete();
						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item was successfully deleted"));
						$this->_redirect("*/*/");
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					}
				}
				$this->_redirect("*/*/");
		}

		
		public function massRemoveAction()
		{
			try {
				$ids = $this->getRequest()->getPost('store_ids', array());
				foreach ($ids as $id) {
                      $model = Mage::getModel("storelocator/storelocator");
					  $model->setId($id)->delete();
				}
				Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item(s) was successfully removed"));
			}
			catch (Exception $e) {
				Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
			}
			$this->_redirect('*/*/');
		}
			
		/**
		 * Export order grid to CSV format
		 */
		public function exportCsvAction()
		{
			$fileName   = 'storelocator.csv';
			$grid       = $this->getLayout()->createBlock('storelocator/adminhtml_storelocator_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
		} 
		/**
		 *  Export order grid to Excel XML format
		 */
		public function exportExcelAction()
		{
			$fileName   = 'storelocator.xml';
			$grid       = $this->getLayout()->createBlock('storelocator/adminhtml_storelocator_grid');
			$this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
		}
}
