<?php


class Kodematix_Socialconnect_FacebookController extends Mage_Core_Controller_Front_Action
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
        $this->referer = Mage::getUrl('socialconnect/account/facebook');  
        
        Mage::helper('kodematix_socialconnect/facebook')->disconnect($customer);

        Mage::getSingleton('core/session')
            ->addSuccess(
                $this->__('You have successfully disconnected your Facebook account.')
            );
    }

    protected function _connectCallback() {
        $errorCode = $this->getRequest()->getParam('error');
        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
        if(!($errorCode || $code) && !$state) {
        
            return;
        }
        
        $this->referer = Mage::getSingleton('core/session')
            ->getFacebookRedirect();

        if(!$state || $state != Mage::getSingleton('core/session')->getFacebookCsrf()) {
            return;
        }

        if($errorCode) {
           
            if($errorCode === 'access_denied') {
                Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Facebook Connect process aborted.')
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
        
            $client = Mage::getSingleton('kodematix_socialconnect/facebook_applicant');

            $userData = $client->api('/me');
            $token = $client->getAccessToken();

            $customersByFacebookId = Mage::helper('kodematix_socialconnect/facebook')
                ->getCustomersByFacebookId($userData->id);

            if(Mage::getSingleton('customer/session')->isLoggedIn()) {
               
                if($customersByFacebookId->count()) {
                    
                    Mage::getSingleton('core/session')
                        ->addNotice(
                            $this->__('Your Facebook account is already connected.')
                        );

                    return;
                }

             
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                Mage::helper('kodematix_socialconnect/facebook')->connectByFacebookId(
                    $customer,
                    $userData->id,
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('Your Facebook account is now connected to your store accout.You can Access by facebook login button with store.')
                );

                return;
            }

            if($customersByFacebookId->count()) {
            
                $customer = $customersByFacebookId->getFirstItem();

                Mage::helper('kodematix_socialconnect/facebook')->loginByCustomer($customer);

                Mage::getSingleton('core/session')
                    ->addSuccess(
                        $this->__('You have successfully logged in using your Facebook account.')
                    );

                return;
            }

            $customersByEmail = Mage::helper('kodematix_socialconnect/facebook')
                ->getCustomersByEmail($userData->email);

            if($customersByEmail->count()) {                
           
                $customer = $customersByEmail->getFirstItem();
                
                Mage::helper('kodematix_socialconnect/facebook')->connectByFacebookId(
                    $customer,
                    $userData->id,
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('We have discovered you already have an account at our store. Your Facebook account is now connected to your store account.')
                );

                return;
            }

       
            if(empty($userData->first_name)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Facebook first name. Please try again.')
                );
            }

            if(empty($userData->last_name)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Facebook last name. Please try again.')
                );
            }

            Mage::helper('kodematix_socialconnect/facebook')->connectByCreatingAccount(
                $userData->email,
                $userData->first_name,
                $userData->last_name,
                $userData->id,
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your Facebook account is now connected to your new user accout at our store. Now you can login using our Facebook Connect button or using store account credentials you will receive to your email address.')
            );
        }
    }

}