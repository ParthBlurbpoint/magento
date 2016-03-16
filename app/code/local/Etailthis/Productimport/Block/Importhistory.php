<?php
class Etailthis_Productimport_Block_Importhistory extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('sinchimport/index'); //
        $this->setElement($element);

        $html = $this->_appendJs();

        $html .= '<div id="sinchimport_status_template" name="sinchimport_status_template" style="display:none">';//none
        $html .= $this->_getStatusTemplateHtml();
        $html .= '</div>';

        $start_import_button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Force Import now')
            ->setOnClick("start_sinch_import()") //setLocation('$url')
            ->toHtml();

        $html .= $start_import_button;    
//        $html .= $url;
        $dataConf = Mage::getConfig();//('sinchimport_root');//getConfig()->getNode()->asXML();
    //    $html .= "<pre>".var_export($dataConf, true)."</pre>";
        

		$resource = Mage::getSingleton('core/resource');
       $readConnection = $resource->getConnection('core_read');
        $query = 'SELECT * FROM   etailthis_productimport  WHERE 1 ORDER BY id DESC    LIMIT 5';
        $results = $readConnection->fetchAll($query);
     
        $import_history=$results;

        $css_arr=array(
            'Failed'        => 'sinch-error',
            'Run'           => 'sinch-run',
            'Successful'    => 'sinch-success'
        );
        
        $html=
        ' 
        <style type="text/css">
        .sinch-error {
            font-weight: bold; 
            color: #D40707 ; 
            text-align: center;
            margin: 5px 0;
        }

        .sinch-success {
            color: green;
            font-weight: bold; 
            text-align: center;
            margin: 5px 0;
        }

        .sinch-run {
            color: blue;
            font-weight: bold;
            text-align: center;
            margin: 5px 0;
        }

    
        table.history {
            border-collapse: collapse;
            width: 100%;
        }

        table.history th {
            border: solid 1px #6F8992;
            background-color: #6F8992;
            color: #fff;
            font-weight: bold;
            padding: 2px 3px;
        }

        table.history td {
            border: 1px solid #333;
            padding: 2px 3px;
        }
        </style>

            <!--Table for import history-->
            <div class="comment">'.($last_success_import? "Your last successful feed import was at ".$last_success_import: "Your import never finished with success" ).'</div>
            <table class="history">
            <thead>
            <tr>
            <th>Import Start</th>
	    <th>Import Finish</th>	
	    <th nowrap>Import Type</th>	
           
            <th nowrap>Number of products</th>
            </tr>
            </thead>
            <tbody>';
        foreach($import_history as $item){
        $html.='
            <tr>
            <td nowrap>'.$item['Import_Start'].'</td>
            <td nowrap>'.$item['Import_Finish'].'</td>
	    	<td nowrap>'.$item['Import_Type'].'</td>		
            <td>'.$item['Status_Number_of_products'].'</td>
            </tr>
            ';
         }
          $html.='  
            </tbody>
            </table>
        ';


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
               Upload Files   
               &nbsp
               <span id='sinchimport_upload_files'> 
                <img src='".$run_pic."'
                 alt='Upload Files' /> 
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
        $post_url=$this->getUrl('sinchimport/ajax');
        $post_url_upd=$this->getUrl('sinchimport/ajax/UpdateStatus');
        $html = "
        <script>
            function start_sinch_import(){
                    status_div=document.getElementById('sinchimport_status_template');    
                    status_div.style.display='';
//                    status_div.innerHTML='';
                    sinch = new Sinch('$post_url','$post_url_upd');
                    sinch.startSinchImport();

                    //
            }
 var Sinch = Class.create();
 Sinch.prototype = {

initialize: function(postUrl, postUrlUpd) {
                this.postUrl = postUrl; //'https://techatcost.com/purchases/ajax/';
                this.postUrlUpd = postUrlUpd;
                this.failureUrl = document.URL;
                // unique user session ID
                this.SID = null;
                // object with event message data
                this.objectMsg = null;
                this.prevMsg = '';
                // interval object
                this.updateTimer = null;
                // default shipping code. Display on errors

                 elem = 'checkoutSteps';
                 clickableEntity = '.head';

                // overwrite Accordion class method
                var headers = $$('#' + elem + ' .section ' + clickableEntity);
                headers.each(function(header) {
                        Event.observe(header,'click',this.sectionClicked.bindAsEventListener(this));
                        }.bind(this));
            },
startSinchImport: function () {
                 _this = this;
                 new Ajax.Request(this.postUrl,
                         {
method:'post',
parameters: '',
requestTimeout: 10,
/*
onLoading:function(){
  alert('onLoading');
  },
  onLoaded:function(){
  alert('onLoaded');
  },
*/
onSuccess: function(transport) {
var response = transport.responseText || null;
_this.SID = response;
if (_this.SID) {
_this.updateTimer = setInterval(function(){_this.updateEvent();},20000);
$('session_id').value = _this.SID;
} else {
alert('Can not get your session ID. Please reload the page!');
}
},
onTimeout: function() { alert('Can not get your session ID. Timeout!'); },
    onFailure: function() { alert('Something went wrong...') }
    });

},

updateEvent: function () {
                 _this = this;
                 new Ajax.Request(this.postUrlUpd,
                         {
method: 'post',
parameters: {session_id: this.SID},
onSuccess: function(transport) {
_this.objectMsg = transport.responseText.evalJSON();
_this.prevMsg = _this.objectMsg.message;
if(_this.prevMsg!=''){
   _this.updateStatusHtml();
}

if (_this.objectMsg.error == 1) {
// Do something on error
_this.clearUpdateInterval();
}

if (_this.objectMsg.finished == 1) {
 _this.objectMsg.message='Import finished';
 _this.updateStatusHtml();
_this.clearUpdateInterval();

}

},
onFailure: this.ajaxFailure.bind(),
    });
},

updateStatusHtml: function(){
    message=this.objectMsg.message.toLowerCase();
    mess_id='sinchimport_'+message.replace(/\s+/g, '_');    
    if(!document.getElementById(mess_id)){
         alert(mess_id+' - not exist');
    }     
    else{
    //    alert (mess_id+' - exist');
        $(mess_id).innerHTML='<img src=\"".Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."adminhtml/default/default/images/sinchimport_yes.gif"."\"/>'
    }     
    htm=$('sinchimport_status_template').innerHTML;
//    $('sinchimport_status_template').innerHTML=htm+'<br>'+this.objectMsg.message;
},

ajaxFailure: function(){
                     this.clearUpdateInterval();     
                     location.href = this.failureUrl;
},

clearUpdateInterval: function () {
                             clearInterval(this.updateTimer);
},


 }
        </script>
        ";
        return $html;
    }

}
