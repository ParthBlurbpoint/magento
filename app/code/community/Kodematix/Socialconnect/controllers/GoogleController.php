<?php

class Kodematix_Socialconnect_GoogleController extends Mage_Core_Controller_Front_Action
{
    protected $referer = null;

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
        $this->referer = Mage::getUrl('socialconnect/account/google');        
        
        Mage::helper('kodematix_socialconnect/google')->disconnect($customer);
        
        Mage::getSingleton('core/session')
            ->addSuccess(
                $this->__('You have successfully disconnected your Google account.')
            );
    }

    protected function _connectCallback() {
        $errorCode = $this->getRequest()->getParam('error');
        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
        if(!($errorCode || $code) && !$state) {
       
            return;
        }
        
        $this->referer = Mage::getSingleton('core/session')->getGoogleRedirect();

        if(!$state || $state != Mage::getSingleton('core/session')->getGoogleCsrf()) {
            return;
        }

        if($errorCode) {
          
            if($errorCode === 'access_denied') {
                Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Google Connect process aborted.')
                    );

                return;
            }

            throw new Exception(
                sprintf(
                    $this->__('Sorry, "%s" error occured. Please try again.'),
                    $errorCode
                )
            );

            return;
        }

        if ($code) {
        
            $client = Mage::getSingleton('kodematix_socialconnect/google_applicant');

            $userData = $client->api('/userinfo');
            $token = $client->getAccessToken();

            $customersByGoogleId = Mage::helper('kodematix_socialconnect/google')
                ->getCustomersByGooglePlusId($userData->id);

            if(Mage::getSingleton('customer/session')->isLoggedIn()) {
          
                if($customersByGoogleId->count()) {
                   
                    Mage::getSingleton('core/session')
                        ->addNotice(
                            $this->__('Your Google account is already connected.')
                        );

                    return;
                }

             
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                Mage::helper('kodematix_socialconnect/google')->connectByGoogleId(
                    $customer,
                    $userData->id,
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('Your Google account is now connected to your store accout. You can Access by google login button with store.')
                );

                return;
            }

            if($customersByGoogleId->count()) {
        
                $customer = $customersByGoogleId->getFirstItem();

                Mage::helper('kodematix_socialconnect/google')->loginByCustomer($customer);

                Mage::getSingleton('core/session')
                    ->addSuccess(
                        $this->__('You have successfully logged in using your Google account.')
                    );

                return;
            }

            $customersByEmail = Mage::helper('kodematix_socialconnect/facebook')
                ->getCustomersByEmail($userData->email);

            if($customersByEmail->count())  {
              
                $customer = $customersByEmail->getFirstItem();
                
                Mage::helper('kodematix_socialconnect/google')->connectByGoogleId(
                    $customer,
                    $userData->id,
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('We have discovered you already have an account at our store. Your Google account is now connected to your store account.')
                );

                return;
            }

            if(empty($userData->given_name)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Google first name. Please try again.')
                );
            }

            if(empty($userData->family_name)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Google last name. Please try again.')
                );
            }

            Mage::helper('kodematix_socialconnect/google')->connectByCreatingAccount(
                $userData->email,
                $userData->given_name,
                $userData->family_name,
                $userData->id,
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your Google account is now connected to your new user accout at our store. Now you can login using our Google Connect button or using store account credentials you will receive to your email address.')
            );
        }
    }

}