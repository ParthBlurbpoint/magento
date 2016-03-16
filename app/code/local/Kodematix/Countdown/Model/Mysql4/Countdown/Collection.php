<?php

class Kodematix_Countdown_Model_Mysql4_Countdown_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('countdown/countdown');
    }
}