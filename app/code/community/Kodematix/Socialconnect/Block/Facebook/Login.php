<?php

class Kodematix_Socialconnect_Block_Facebook_Login extends Mage_Core_Block_Template
{
    protected $client = null;
    protected $userData = null;
    protected $redirectUri = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('kodematix_socialconnect/facebook_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userData = Mage::registry('kodematix_socialconnect_facebook_userinfo');
        
  
        Mage::getSingleton('core/session')->setFacebookCsrf($csrf = md5(uniqid(rand(), TRUE)));
        $this->client->setState($csrf);
        
        if(!($redirect = Mage::getSingleton('customer/session')->getBeforeAuthUrl())) {
            $redirect = Mage::helper('core/url')->getCurrentUrl();      
        }        
        
    
        Mage::getSingleton('core/session')->setFacebookRedirect($redirect);        

        $this->setTemplate('kodematix/socialconnect/facebook/button.phtml');
    }

    protected function _getLinkUrl()
    {
        if(empty($this->userData)) {
            return $this->client->createAuthUrl();
        } else {
            return $this->getUrl('socialconnect/facebook/disconnect');
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
