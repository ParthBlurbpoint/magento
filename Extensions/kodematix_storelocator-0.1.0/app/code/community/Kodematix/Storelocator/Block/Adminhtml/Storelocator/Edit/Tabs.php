<?php
class Kodematix_Storelocator_Block_Adminhtml_Storelocator_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
		public function __construct()
		{
				parent::__construct();
				$this->setId("storelocator_tabs");
				$this->setDestElementId("edit_form");
				$this->setTitle(Mage::helper("storelocator")->__("Store Information"));
		}
		protected function _beforeToHtml()
		{
				$this->addTab("form_section", array(
				"label" => Mage::helper("storelocator")->__("Store Information"),
				"title" => Mage::helper("storelocator")->__("Store Information"),
				"content" => $this->getLayout()->createBlock("storelocator/adminhtml_storelocator_edit_tab_form")->toHtml(),
				));
				$this->addTab("form_section_description", array(
				"label" => Mage::helper("storelocator")->__("Store description"),
				"title" => Mage::helper("storelocator")->__("Store description"),
				"content" => $this->getLayout()->createBlock("storelocator/adminhtml_storelocator_edit_tab_description")->toHtml(),
				));
      
	  		return parent::_beforeToHtml();
		}

}