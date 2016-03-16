<?php

class Kodematix_Socialconnect_Block_Twitter_Login extends Mage_Core_Block_Template
{
    protected $client = null;
    protected $userData = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('kodematix_socialconnect/twitter_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userData = Mage::registry('kodematix_socialconnect_twitter_userinfo');

        if(!($redirect = Mage::getSingleton('customer/session')->getBeforeAuthUrl())) {
            $redirect = Mage::helper('core/url')->getCurrentUrl();      
        }

    
        Mage::getSingleton('core/session')->setTwitterRedirect($redirect);

        $this->setTemplate('kodematix/socialconnect/twitter/button.phtml');
    }

    protected function _getLinkUrl()
    {
        if(empty($this->userData)) {
            return $this->client->createAuthUrl();
        } else {
            return $this->getUrl('socialconnect/twitter/disconnect');
        }
    }

    protected function _getLinkText()
    {
        if(empty($this->userData)) {
            if(!($text = Mage::registry('kodematix_socialconnect_button_text'))){
                $text = $this->__('Connect');
            }
        } else {
            $text = $this->__('Disconnect');
        }
        
        return $text;
    }

}
