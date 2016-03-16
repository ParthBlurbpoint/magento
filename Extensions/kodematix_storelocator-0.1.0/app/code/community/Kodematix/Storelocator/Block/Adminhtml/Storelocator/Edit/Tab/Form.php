<?php
class Kodematix_Storelocator_Block_Adminhtml_Storelocator_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
	 protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
    }
    
		protected function _prepareForm()
		{

				$form = new Varien_Data_Form();
				$this->setForm($form);
				$fieldset = $form->addFieldset("storelocator_form", array("legend"=>Mage::helper("storelocator")->__("Store information")));

						$fieldset->addField("name", "text", array(
						"label" => Mage::helper("storelocator")->__("Storename"),
						"required" => true,
						"name" => "name",
						));
						$fieldset->addField('country', 'select', array(
							"name"  => "country",
							"label"     => "Country",
							"required" => true,
							'values'    => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(), 
						));
					
						$fieldset->addField("state", "text", array(
						"label" => Mage::helper("storelocator")->__("State"),
						"required" => true,
						"name" => "state",
						));
					
						$fieldset->addField("city", "text", array(
						"label" => Mage::helper("storelocator")->__("City"),
						"required" => true,
						"name" => "city",
						));
					
						$fieldset->addField("zipcode", "text", array(
						"label" => Mage::helper("storelocator")->__("Zipcode"),
						"required" => true,
						"name" => "zipcode",
						));
									
						 $fieldset->addField("storeview", "select", array(
						"label"     => Mage::helper("storelocator")->__("Storeview"),
						"values"  => Kodematix_Storelocator_Block_Adminhtml_Storelocator_Grid::getValueArray5(),
						"name" => "storeview",
						));				
						 $fieldset->addField("status", "select", array(
						"label"     => Mage::helper("storelocator")->__("Status"),
						"values"   => Kodematix_Storelocator_Block_Adminhtml_Storelocator_Grid::getValueArray6(),
						"name" => "status",
						));
						$fieldset->addField("street_address","text", array(
						"label" => Mage::helper("storelocator")->__("Street Address"),
						"name" => "street_address",
						));
						$fieldset->addField("phone","text", array(
						"label" => Mage::helper("storelocator")->__("Phone No"),
						"name" => "phone",
						));
						$fieldset->addField("fax","text", array(
						"label" => Mage::helper("storelocator")->__("Fax"),
						"name" => "fax",
						));
						$fieldset->addField("url","text", array(
						"label" => Mage::helper("storelocator")->__("Url"),
					    "class"     => "validate-clean-url",
						"required" => true,
						"name" => "url",
						));
						$fieldset->addField("email","text", array(
						"label" => Mage::helper("storelocator")->__("Email"),
						"class" => "validate-email",
						"name" => "email",
						));
						$fieldset->addField("store_logo","image", array(
						"label" => Mage::helper("storelocator")->__("Store Logo"),
						"name" => "store_logo",
					    "note"      => "Allowed extensions are jpg, jpeg, gif, png",
						));
						/*$fieldset->addField("description","textarea", array(
						"label" => Mage::helper("storelocator")->__("Description"),
						"name" => "description",
						))*/
					/* $fieldset->addField('description', 'editor', array(
							'label'     => Mage::helper('storelocator')->__('Description'),
							'title' => Mage::helper('storelocator')->__('Description'),
							'name'      => 'description',
							'config'    => Mage::getSingleton('cms/wysiwyg_config')->getConfig(),
							'style'     => 'height:20em; width:43em',
							
						));*/
						$fieldset->addField("trading_hours","text", array(
						"label" => Mage::helper("storelocator")->__("Trading Hours"),
						"name" => "trading_hours",
						));
						$fieldset->addField("radius","text", array(
						"label" => Mage::helper("storelocator")->__("Radius"),
						"name" => "radius",
						"class" => "validate-number",
						"required"  => true,
						));
						$fieldset->addField("latitude","text", array(
						"label" => Mage::helper("storelocator")->__("Latitude"),
						"name" => "latitude",
						"class" => "validate-number",
						"required"  => true,
						));
						$fieldset->addField("longitude","text", array(
						"label" => Mage::helper("storelocator")->__("Longitude"),
						"name" => "longitude",
						"required"  => true,
						));
						$zoomLevelConfigValue = Mage::getStoreConfig('tab1/general/zoom_level');
						$fieldset->addField("zoom_level","text", array(
						"label" => Mage::helper("storelocator")->__("Zoom Level"),
						"name" => "zoom_level",
						"required"  => true,
						));
					
				if (Mage::getSingleton("adminhtml/session")->getStorelocatorData())
				{
					$form->setValues(Mage::getSingleton("adminhtml/session")->getStorelocatorData());
					Mage::getSingleton("adminhtml/session")->setStorelocatorData(null);
				} 
				elseif(Mage::registry("storelocator_data")) {
				    $form->setValues(Mage::registry("storelocator_data")->getData());
				}
				return parent::_prepareForm();
		}
}
