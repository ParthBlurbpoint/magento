<?php
class Etailthis_Productimport_Block_Startimportbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{

   protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = Mage::getBaseUrl().'productimport/index/updatecsv';
		
        $this->setElement($element);
  		 $html = $this->_appendJs();
        $html .= '<div id="sinchimport_status_template" name="sinchimport_status_template" style="display:none">';//none
       // $html .= $this->_getStatusTemplateHtml();
        $html .= '<input type="hidden" name="siteurl" id="siteurl" value="Mage::getBaseUrl();"></div>';
        $html .= $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Force Product Import Now')
					// ->setOnClick($this->getUrl('productimport/index/updatecsv')) //setLocation('$url')
                    ->setOnClick("start_sinch_import();")
                    ->toHtml();

        return $html;
    } 
	
	 protected function _appendJs()
		{
			$post_url = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess';
		//	$post_url2 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess1';
			$post_url2 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport1.php';
			$post_url3 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport2.php';
		//	$post_url3 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/product/startImportProcess2';
			//$post_url3 ="'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/product/startImportProcess2';
			$post_url4 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport3.php';
			$post_url5 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport4.php';
			$post_url6 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport5.php';
			$post_url7 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport6.php';
			$post_url8 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport7.php';
			$post_url9 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'Productimport8.php';
			
			//$post_url5 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess4';
			//$post_url6 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess5';
			//$post_url7 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess6';
			//$post_url8 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess7';
			//$post_url9 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess8';
			//$post_url10 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess9';
		//	$post_url11 = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/startImportProcess10';

			  $html = "
				 
        <script>
            function start_sinch_import(){
				
				
				 status_div=document.getElementById('sinchimport_status_template');   
                    status_div.style.display='';
                  var siteurl = document.getElementById('siteurl').value;
				    
				 new Ajax.Request($post_url', 
				 {
					
					method:     'get',
					
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						alert(transport.responseText);
					
					if(transport.status == 200){
						step1(); 
						step2();
						step3();
						step4();
						step5();
						step6();
						step7();
						step8();
					}
						//alert('SucessFully Import Product');
					}
					}
				
				});
			 }
			 function step1(){
				  status_div=document.getElementById('sinchimport_status_template');   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url2', 
				 {
					 
					method:     'get',
					
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						//alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			   function step2(){
				 
				  status_div=document.getElementById('sinchimport_status_template');   
                   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url3', 
				 {
					 
					method:     'get',
					
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						////alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			   function step3(){
				
				  status_div=document.getElementById('sinchimport_status_template');   
                   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url4', 
				 {
					 
					method:     'get',
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						//alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			   function step4(){
				 
				  status_div=document.getElementById('sinchimport_status_template');   
                   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url5', 
				 {
					 
					method:     'get',
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						//alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			   function step5(){
				
				  status_div=document.getElementById('sinchimport_status_template');   
                   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url6', 
				 {
					 
					method:     'get',
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						//alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			   function step6(){
				 
				  status_div=document.getElementById('sinchimport_status_template');   
                   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url7', 
				 {
					 
					method:     'get',
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						//alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			   function step7(){
				
				  status_div=document.getElementById('sinchimport_status_template');   
                   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url8', 
				 {
					 
					method:     'get',
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						//alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			   function step8(){
				
				  status_div=document.getElementById('sinchimport_status_template');   
                   
                  var siteurl = document.getElementById('siteurl').value;
				 new Ajax.Request($post_url9', 
				 {
					 
					method:     'get',
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';
 						//alert(transport.responseText);
						//alert('SucessFully Import Product');
					}
					}
				
				});
			  }
			  
			  
        </script>
        ";
        return $html;
		}
        
}
