<?php

class Kodematix_Socialconnect_Block_Register extends Mage_Core_Block_Template
{
    protected $clientGoogle = null;
    protected $clientFacebook = null;
    protected $clientTwitter = null;
    
    protected $numEnabled = 0;
    protected $numShown = 0;

    protected function _construct() {
        parent::_construct();

        $this->clientGoogle = Mage::getSingleton('kodematix_socialconnect/google_applicant');
        $this->clientFacebook = Mage::getSingleton('kodematix_socialconnect/facebook_applicant');
        $this->clientTwitter = Mage::getSingleton('kodematix_socialconnect/twitter_applicant');

        if( !$this->_googleEnabled() &&
            !$this->_facebookEnabled() &&
            !$this->_twitterEnabled())
            return;

        if($this->_googleEnabled()) {
            $this->numEnabled++;
        }

        if($this->_facebookEnabled()) {
            $this->numEnabled++;
        }

        if($this->_twitterEnabled()) {
            $this->numEnabled++;
        }

        Mage::register('kodematix_socialconnect_button_text', $this->__('REGISTER'));

        $this->setTemplate('kodematix/socialconnect/register.phtml');
    }

    protected function _getColSet()
    {
        return 'col'.$this->numEnabled.'-set';
    }

    protected function _getCol()
    {
        return 'col-'.++$this->numShown;
    }

    protected function _googleEnabled()
    {
        return (bool) $this->clientGoogle->isEnabled();
    }

    protected function _facebookEnabled()
    {
        return (bool) $this->clientFacebook->isEnabled();
    }

    protected function _twitterEnabled()
    {
        return (bool) $this->clientTwitter->isEnabled();
    }

}