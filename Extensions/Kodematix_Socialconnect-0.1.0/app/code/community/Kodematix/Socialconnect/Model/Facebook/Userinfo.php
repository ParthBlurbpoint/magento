<?php

class Kodematix_Socialconnect_Model_Facebook_Userinfo
{
    protected $client = null;
    protected $userData = null;

    public function __construct() {
        if(!Mage::getSingleton('customer/session')->isLoggedIn())
            return;

        $this->client = Mage::getSingleton('kodematix_socialconnect/facebook_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if(($socialconnectFid = $customer->getKodematixSocialconnectFid()) &&
                ($socialconnectFtoken = $customer->getKodematixSocialconnectFtoken())) {
            $helper = Mage::helper('kodematix_socialconnect/facebook');

            try{
                $this->client->setAccessToken($socialconnectFtoken);
                $this->userData = $this->client->api(
                    '/me',
                    'GET',
                    array(
                        'fields' =>
                        'id,name,first_name,last_name,link,birthday,gender,email,picture.type(large)'
                    )
                );

            } catch(FacebookOAuthException $e) {
                $helper->disconnect($customer);
                Mage::getSingleton('core/session')->addNotice($e->getMessage());
            } catch(Exception $e) {
                $helper->disconnect($customer);
                Mage::getSingleton('core/session')->addError($e->getMessage());
            }

        }
    }

    public function getUserData()
    {
        return $this->userData;
    }
}