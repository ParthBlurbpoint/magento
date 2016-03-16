<?php
class Kodematix_Countdown_Block_Adminhtml_Renderer_Countertime extends Varien_Data_Form_Element_Date
{
   
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
        $this->setTime(true);
    }
 
  
    public function getFormat()
    {
        return Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
}