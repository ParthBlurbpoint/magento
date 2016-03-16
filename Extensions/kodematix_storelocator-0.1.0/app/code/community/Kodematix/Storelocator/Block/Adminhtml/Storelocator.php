<?php


class Kodematix_Storelocator_Block_Adminhtml_Storelocator extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_storelocator";
	$this->_blockGroup = "storelocator";
	$this->_headerText = Mage::helper("storelocator")->__("Storelocator Manager");
	$this->_addButtonLabel = Mage::helper("storelocator")->__("Add New Store");
	parent::__construct();
	
	}

}