<?php
	
class Kodematix_Storelocator_Block_Adminhtml_Storelocator_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
		public function __construct()
		{

				parent::__construct();
				$this->_objectId = "store_id";
				$this->_blockGroup = "storelocator";
				$this->_controller = "adminhtml_storelocator";
				$this->_updateButton("save", "label", Mage::helper("storelocator")->__("Save Store"));
				$this->_updateButton("delete", "label", Mage::helper("storelocator")->__("Delete Store"));

				$this->_addButton("saveandcontinue", array(
					"label"     => Mage::helper("storelocator")->__("Save And Continue Edit"),
					"onclick"   => "saveAndContinueEdit()",
					"class"     => "save",
				), -100);



				$this->_formScripts[] = "

							function saveAndContinueEdit(){
								editForm.submit($('edit_form').action+'back/edit/');
							}
						";
		}

		public function getHeaderText()
		{
				if( Mage::registry("storelocator_data") && Mage::registry("storelocator_data")->getId() ){

				    return Mage::helper("storelocator")->__("Edit Store '%s'", $this->htmlEscape(Mage::registry("storelocator_data")->getId()));

				} 
				else{

				     return Mage::helper("storelocator")->__("Add Store");

				}
		}
		protected function _prepareLayout() {
    parent::_prepareLayout();
    if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
        $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
    }
}

}