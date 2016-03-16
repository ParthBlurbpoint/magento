<?php

class Kodematix_Storelocator_Block_Adminhtml_Storelocator_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

		public function __construct()
		{
				parent::__construct();
				$this->setId("storelocatorGrid");
				$this->setDefaultSort("store_id");
				$this->setDefaultDir("DESC");
				$this->setSaveParametersInSession(true);
		}

		protected function _prepareCollection()
		{
				$collection = Mage::getModel("storelocator/storelocator")->getCollection();
				$this->setCollection($collection);
				return parent::_prepareCollection();
		}
		protected function _prepareColumns()
		{
				$this->addColumn("store_id", array(
				"header" => Mage::helper("storelocator")->__("ID"),
				"align" =>"right",
				"width" => "50px",
			    "type" => "number",
				"index" => "store_id",
				));
                
				$this->addColumn("name", array(
				"header" => Mage::helper("storelocator")->__("Storename"),
				"index" => "name",
				));
				$this->addColumn("country", array(
				"header" => Mage::helper("storelocator")->__("Country"),
				"index" => "country",
				 "sortable" =>true,
          		  "index"=>"country",
            		"type" => "country",
				
				));
				$this->addColumn("state", array(
				"header" => Mage::helper("storelocator")->__("State"),
				"index" => "state",
				));
				$this->addColumn("city", array(
				"header" => Mage::helper("storelocator")->__("City"),
				"index" => "city",
				));
				$this->addColumn("zipcode", array(
				"header" => Mage::helper("storelocator")->__("Zipcode"),
				"index" => "zipcode",
				));
			$this->addColumn('status', array(
			'header' => Mage::helper('storelocator')->__('Status'),
			'index' => 'status',
			'type' => 'options',
			'options'=>Kodematix_Storelocator_Block_Adminhtml_Storelocator_Grid::getOptionArray6(),				
			));
	
						
			$this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV')); 
			$this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
			   return $this->getUrl("*/*/edit", array("id" => $row->getId()));
		}


		
		protected function _prepareMassaction()
		{
			$this->setMassactionIdField('store_id');
			$this->getMassactionBlock()->setFormFieldName('store_ids');
			$this->getMassactionBlock()->setUseSelectAll(true);
			$this->getMassactionBlock()->addItem('remove_storelocator', array(
					 'label'=> Mage::helper('storelocator')->__('Remove Storelocator'),
					 'url'  => $this->getUrl('*/adminhtml_storelocator/massRemove'),
					 'confirm' => Mage::helper('storelocator')->__('Are you sure?')
				));
			return $this;
		}
			
		static public function getOptionArray5()
		{
            $data_array=array(); 
			$data_array[0]='All Store Views';
			$data_array[1]='Main  Website';
			$data_array[2]='Default store View';
			$data_array[3]='Main Website Store';
            return($data_array);
		}
		static public function getValueArray5()
		{
            $data_array=array();
			foreach(Kodematix_Storelocator_Block_Adminhtml_Storelocator_Grid::getOptionArray5() as $k=>$v){
               $data_array[]=array('value'=>$k,'label'=>$v);		
			}
            return($data_array);

		}
		
		static public function getOptionArray6()
		{
            $data_array=array(); 
			$data_array[0]='Enable';
			$data_array[1]='Disable';
            return($data_array);
		}
		static public function getValueArray6()
		{
            $data_array=array();
			foreach(Kodematix_Storelocator_Block_Adminhtml_Storelocator_Grid::getOptionArray6() as $k=>$v){
               $data_array[]=array('value'=>$k,'label'=>$v);		
			}
            return($data_array);

		}
	
}