<?php class Etailthis_Productimport_Model_Productimport extends Mage_Core_Model_Config_Data
{
	public function save()
    {
	  $mystring = trim($this->getValue());  
	  $findme   =  'etailthis.com';
	  $pos = strpos($mystring, $findme);
		if ($pos === false) {
			 Mage::throwException(Mage::helper('adminhtml')->__('Invalid domain name', $mystring));
		}else{
			return parent::save();
		}
        return $this;  
     
    }
}
?>