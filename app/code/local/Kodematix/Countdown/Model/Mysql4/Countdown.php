<?php

class Kodematix_Countdown_Model_Mysql4_Countdown extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('countdown/countdown', 'countdown_id');
    }
}