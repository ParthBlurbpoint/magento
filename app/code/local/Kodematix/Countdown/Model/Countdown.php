<?php

class Kodematix_Countdown_Model_Countdown extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('countdown/countdown');
    }
}