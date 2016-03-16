<?php 
class Etailthis_Productimport_Block_Stockandpricebutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $html = $this->_appendJs();
		 $html .= '<div id="sinchimport_status_template" name="sinchimport_status_template" style="display:none">';//none
		// $html .= $this->_getStatusTemplateHtml();
		 $html .= '</div>';
        $html .= $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Run Now Stock & price !')
                    ->setOnClick("start_update_stock()")
                    ->toHtml();

        return $html;
    }

	
	 protected function _appendJs()
		{
		  $post_url = "'".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).''.'productimport/index/StockAndPrice';
			     $html = "
				 
        <script>
            function start_update_stock(){
				 status_div=document.getElementById('sinchimport_status_template');   
                    status_div.style.display='';
				 new Ajax.Request($post_url', 
				 {
					method:     'get',
					onSuccess: function(transport){
		 			
					if (transport.responseText){
						  status_div.style.display='none';

						alert(transport.responseText);
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

?>