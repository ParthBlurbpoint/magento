<?php 
class Etailthis_Productimport_Block_Startcategoryimportbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
		 $html = $this->_appendJs();
        $html .= '<div id="sinchimport_status_template" name="sinchimport_status_template" style="display:none">';//none
      //  $html .= $this->_getStatusTemplateHtml();
        $html .= '</div>';
        $html .= $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Force Category Import Now')
					->setOnClick("start_category_import();")
                   // ->setOnClick("setLocation('$url')")
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
			 $post_url = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/categoryImport';
			     $html = "
				 
        <script>
            function start_category_import(){

				// set_run_icon();
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
					alert('Category Import Sucessfully');
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
             
                document.getElementById('sinchimport_indexing_data').innerHTML=run_pic;
                document.getElementById('sinchimport_import_finished').innerHTML=run_pic;
		
		

	    }	
			 
        </script>
        ";
        return $html;
		}
        
}

?>