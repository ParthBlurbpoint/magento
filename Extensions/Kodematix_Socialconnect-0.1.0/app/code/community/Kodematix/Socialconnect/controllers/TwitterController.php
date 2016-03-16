<?php

class Kodematix_Socialconnect_TwitterController extends Mage_Core_Controller_Front_Action
{
    protected $referer = null;

    public function requestAction()
    {
        $client = Mage::getSingleton('kodematix_socialconnect/twitter_applicant');
        if(!($client->isEnabled())) {
            Mage::helper('kodematix_socialconnect')->errornotfound($this);
        }

        $client->fetchRequestToken();
    }   

    public function connectAction()
    {
        try {
            $this->_connectCallback();
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        if(!empty($this->referer)) {
            $this->_redirectUrl($this->referer);
        } else {
            Mage::helper('kodematix_socialconnect')->errornotfound($this);
        }
    }
    
    public function disconnectAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        try {
            $this->_disconnectCallback($customer);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        if(!empty($this->referer)) {
            $this->_redirectUrl($this->referer);
        } else {
            Mage::helper('kodematix_socialconnect')->errornotfound($this);
        }
    }  
    
    protected function _disconnectCallback(Mage_Customer_Model_Customer $customer) {
        $this->referer = Mage::getUrl('socialconnect/account/twitter');  
        
        Mage::helper('kodematix_socialconnect/twitter')->disconnect($customer);

        Mage::getSingleton('core/session')
            ->addSuccess(
                $this->__('You have successfully disconnected twitter account.')
            );
    }     

    protected function _connectCallback() {
        if (!($params = $this->getRequest()->getParams())
            ||
            !($requestToken = unserialize(Mage::getSingleton('core/session')
                ->getTwitterRequestToken()))
            ) {
   
            return;
        }

        $this->referer = Mage::getSingleton('core/session')->getTwitterRedirect();
        
        if(isset($params['denied'])) {
            Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Twitter Connect process aborted.')
                    );
            
            return;
        }       

        $client = Mage::getSingleton('kodematix_socialconnect/twitter_applicant');

        $token = $client->getAccessToken();
        
        $userData = (object) array_merge(
                (array) ($userData = $client->api('/account/verify_credentials.json', 'GET', array('skip_status' => true))),
                array('email' => sprintf('%s@twitter-user.com', strtolower($userData->screen_name)))
        );

        $customersByTwitterId = Mage::helper('kodematix_socialconnect/twitter')
            ->getCustomersByTwitterId($userData->id);

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
     
            if($customersByTwitterId->count()) {
              
                Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Your Twitter account is already connected.')
                    );

                return;
            }

      
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            Mage::helper('kodematix_socialconnect/twitter')->connectByTwitterId(
                $customer,
                $userData->id,
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your Twitter account is now connected to store accout.You can Access by twitter login button with store.')
            );

            return;
        }

        if($customersByTwitterId->count()) {
          
            $customer = $customersByTwitterId->getFirstItem();

            Mage::helper('kodematix_socialconnect/twitter')->loginByCustomer($customer);

            Mage::getSingleton('core/session')
                ->addSuccess(
                    $this->__('You have successfully logged in using your Twitter account.')
                );

            return;
        }

        $customersByEmail = Mage::helper('kodematix_socialconnect/twitter')
            ->getCustomersByEmail($userData->email);

        if($customersByEmail->count()) {
            
            $customer = $customersByEmail->getFirstItem();

            Mage::helper('kodematix_socialconnect/twitter')->connectByTwitterId(
                $customer,
                $userData->id,
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('We have discovered you already have an account at our store. Your Twitter account is now connected to your store account.')
            );

            return;
        }

      
        if(empty($userData->name)) {
            throw new Exception(
                $this->__('Sorry, could not find your Twitter last name. Please try again.')
            );
        }

        Mage::helper('kodematix_socialconnect/twitter')->connectByCreatingAccount(
            $userData->email,
            $userData->name,
            $userData->id,
            $token
        );

        Mage::getSingleton('core/session')->addSuccess(
            $this->__('Your Twitter account is now connected to your new user accout at our store. Now you can login using our Twitter Connect button.')
        );        
        Mage::getSingleton('core/session')->addNotice(
            sprintf($this->__('Since Twitter does not support third-party access to your email address, we were unable to send you your store accout credentials. To be able to login using store account credentials you will need to update your email address and password using our <a href="%s">Edit Account Information</a>.'), Mage::getUrl('customer/account/edit'))
        );        
    }

}
