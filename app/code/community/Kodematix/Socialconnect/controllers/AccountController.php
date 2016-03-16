<?php

class Kodematix_Socialconnect_AccountController extends Mage_Core_Controller_Front_Action
{
    
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }    

    public function googleAction()
    {        
        $userData = Mage::getSingleton('kodematix_socialconnect/google_userinfo')
                ->getUserData();
        
        Mage::register('kodematix_socialconnect_google_userinfo', $userData);
        
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function facebookAction()
    {        
        $userData = Mage::getSingleton('kodematix_socialconnect/facebook_userinfo')
            ->getUserData();
        
        Mage::register('kodematix_socialconnect_facebook_userinfo', $userData);
        
        $this->loadLayout();
        $this->renderLayout();
    }    
    
    public function twitterAction()
    {        
        
        if(!($userData = Mage::getSingleton('customer/session')
                ->getKodematixSocialconnectTwitterUserinfo())) {
            $userData = Mage::getSingleton('kodematix_socialconnect/twitter_userinfo')
                ->getUserData();
            
            Mage::getSingleton('customer/session')->setKodematixSocialconnectTwitterUserinfo($userData);
        }
        
        Mage::register('kodematix_socialconnect_twitter_userinfo', $userData);
        
        $this->loadLayout();
        $this->renderLayout();
    }    
    
}
