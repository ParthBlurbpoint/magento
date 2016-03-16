<?php
class Etailthis_Productimport_Model_StartCron{	
	public function fullproductimport(){
		$date = date('d-m-y H:i:s');
		Mage::log($date,null,'date.log');
	
	} 
}