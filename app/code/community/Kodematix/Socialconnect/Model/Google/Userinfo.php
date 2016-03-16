<?php

class Kodematix_Socialconnect_Model_Google_Userinfo
{
    protected $client = null;
    protected $userData = null;

    public function __construct() {
        if(!Mage::getSingleton('customer/session')->isLoggedIn())
            return;

        $this->client = Mage::getSingleton('kodematix_socialconnect/google_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if(($socialconnectGid = $customer->getKodematixSocialconnectGid()) &&
                ($socialconnectGtoken = $customer->getKodematixSocialconnectGtoken())) {
            $helper = Mage::helper('kodematix_socialconnect/google');

            try{
                $this->client->setAccessToken($socialconnectGtoken);

                $this->userData = $this->client->api('/userinfo');

                $customer->setKodematixSocialconnectGtoken($this->client->getAccessToken());
                $customer->save();

            } catch(Kodematix_Socialconnect_GoogleOAuthException $e) {
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