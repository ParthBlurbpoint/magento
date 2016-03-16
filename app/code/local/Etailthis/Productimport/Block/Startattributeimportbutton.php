<?php 
class Etailthis_Productimport_Block_Startattributeimportbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('catalog/product'); //

  		$html = $this->_appendJs();
        $html .= '<div id="sinchimport_status_template" name="sinchimport_status_template" style="display:none">';//none
      //  $html .= $this->_getStatusTemplateHtml();
        $html .= '</div>';

        $html .= $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Force Attribute Import Now')
                    ->setOnClick("start_attribute_import();")
                    ->toHtml();

        return $html;
    }
		protected function _getStatusTemplateHtml()
    {
        $run_pic=Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."adminhtml/default/default/images/sinchimport_run.gif";
        $html="
           <ul> 
            <li>
               Start Import
               &nbsp
               <span id='sinchimport_start_import'> 
                <img src='".$run_pic."'
                 alt='Sinch Import run' /> 
               </span> 
            </li>   
            <li>
               Download files   
               &nbsp
               <span id='sinchimport_upload_files'> 
                <img src='".$run_pic."'
                 alt='Download files' /> 
               </span> 
            </li>   
            <li>
               Parse Categories   
               &nbsp
               <span id='sinchimport_parse_categories'> 
                <img src='".$run_pic."'
                 alt='Parse Categories' /> 
               </span> 
            </li>   
            <li>
               Parse Category Features   
               &nbsp
               <span id='sinchimport_parse_category_features'> 
                <img src='".$run_pic."'
                 alt='Parse Category Features' /> 
               </span> 
            </li>   
            <li>
               Parse Distributors   
               &nbsp
               <span id='sinchimport_parse_distributors'> 
                <img src='".$run_pic."'
                 alt='Parse Distributors' /> 
               </span> 
            </li>   
            <li>
               Parse EAN Codes   
               &nbsp
               <span id='sinchimport_parse_ean_codes'> 
                <img src='".$run_pic."'
                 alt='Parse EAN Codes' /> 
               </span> 
            </li>   
            <li>
               Parse Manufacturers   
               &nbsp
               <span id='sinchimport_parse_manufacturers'> 
                <img src='".$run_pic."'
                 alt='Parse Manufacturers'' /> 
               </span> 
            </li>   
            <li>
               Parse Related Products   
               &nbsp
               <span id='sinchimport_parse_related_products'> 
                <img src='".$run_pic."'
                 alt='Parse Related Products' /> 
               </span> 
            </li>   
            <li>
               Parse Product Features   
               &nbsp
               <span id='sinchimport_parse_product_features'>  
                <img src='".$run_pic."'
                 alt='Parse Product Features' /> 
               </span> 
            </li>   
            <li>
               Parse Products   
               &nbsp
               <span id='sinchimport_parse_products'>  
                <img src='".$run_pic."'
                 alt='Parse Products' /> 
               </span> 
            </li>   
            <li>
               Parse Pictures Gallery   
               &nbsp
               <span id='sinchimport_parse_pictures_gallery'>  
                <img src='".$run_pic."'
                 alt='Parse Pictures Gallery' /> 
               </span> 
            </li>   
            <li>
               Parse Restricted Values   
               &nbsp
               <span id='sinchimport_parse_restricted_values'>  
                <img src='".$run_pic."'
                 alt='Parse Restricted Values' /> 
               </span> 
            </li>   
            <li>
               Parse Stock And Prices   
               &nbsp
               <span id='sinchimport_parse_stock_and_prices'>  
                <img src='".$run_pic."'
                 alt='Parse Stock And Prices' /> 
               </span> 
            </li>   
            <li>
               Generate category filters   
               &nbsp
               <span id='sinchimport_generate_category_filters'>  
                <img src='".$run_pic."'
                 alt='Generate category filters' /> 
               </span> 
            </li>   
            <li>
               Indexing data   
               &nbsp
               <span id='sinchimport_indexing_data'>  
                <img src='".$run_pic."'
                 alt='Indexing data' /> 
               </span> 
            </li>   
            <li>
               Import finished   
               &nbsp
               <span id='sinchimport_import_finished'>  
                <img src='".$run_pic."'
                 alt='Import finished' /> 
               </span> 
            </li>   

           </ul>
        ";
        return $html;
    }
	
	 protected function _appendJs()
		{
		  $post_url = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/AttributeImport';
	
			     $html = "
				 
        <script>
            function start_attribute_import(){

				 //set_run_icon();
				
				 status_div=document.getElementById('sinchimport_status_template');   
                   // curr_status_div=document.getElementById('sinchimport_current_status_message'); 
                   // curr_status_div.style.display='none';
                    status_div.style.display='';
//                    status_div.innerHTML='';
                  
				 new Ajax.Request($post_url', 
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
			   function set_run_icon(){
		run_pic='<img src=\"".Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."adminhtml/default/default/images/sinchimport_run.gif\""."/>';	
		document.getElementById('sinchimport_start_import').innerHTML=run_pic;
                document.getElementById('sinchimport_upload_files').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_categories').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_category_features').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_distributors').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_ean_codes').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_manufacturers').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_related_products').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_product_features').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_products').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_pictures_gallery').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_restricted_values').innerHTML=run_pic;
                document.getElementById('sinchimport_parse_stock_and_prices').innerHTML=run_pic;
                document.getElementById('sinchimport_generate_category_filters').innerHTML=run_pic;
                document.getElementById('sinchimport_indexing_data').innerHTML=run_pic;
                document.getElementById('sinchimport_import_finished').innerHTML=run_pic;
		
		

	    }	
			 
        </script>
        ";
        return $html;
		}
        
}

?>