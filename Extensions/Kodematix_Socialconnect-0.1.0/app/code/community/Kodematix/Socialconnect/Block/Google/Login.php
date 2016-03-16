<?php

class Kodematix_Socialconnect_Block_Google_Login extends Mage_Core_Block_Template
{
    protected $client = null;
    protected $oauth2 = null;
    protected $userData = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('kodematix_socialconnect/google_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userData = Mage::registry('kodematix_socialconnect_google_userinfo');

      
        Mage::getSingleton('core/session')->setGoogleCsrf($csrf = md5(uniqid(rand(), TRUE)));
        $this->client->setState($csrf);
        
        if(!($redirect = Mage::getSingleton('customer/session')->getBeforeAuthUrl())) {
            $redirect = Mage::helper('core/url')->getCurrentUrl();      
        }        
        
        
        Mage::getSingleton('core/session')->setGoogleRedirect($redirect);        

        $this->setTemplate('kodematix/socialconnect/google/button.phtml');
    }

    protected function _getLinkUrl()
    {
        if(empty($this->userData)) {
            return $this->client->createAuthUrl();
        } else {
            return $this->getUrl('socialconnect/google/disconnect');
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
