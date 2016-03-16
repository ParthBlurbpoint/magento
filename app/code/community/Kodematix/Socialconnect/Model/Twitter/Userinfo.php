<?php

class Kodematix_Socialconnect_Model_Twitter_Userinfo
{
    protected $client = null;
    protected $userData = null;

    public function __construct() {
        if(!Mage::getSingleton('customer/session')->isLoggedIn())
            return;

        $this->client = Mage::getSingleton('kodematix_socialconnect/twitter_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if(($socialconnectTid = $customer->getKodematixSocialconnectTid()) &&
                ($socialconnectTtoken = $customer->getKodematixSocialconnectTtoken())) {
            $helper = Mage::helper('kodematix_socialconnect/twitter');

            try{
                $this->client->setAccessToken($socialconnectTtoken);
                
                $this->userData = $this->client->api('/account/verify_credentials.json', 'GET', array('skip_status' => true)); 

            }  catch (Kodematix_Socialconnect_TwitterOAuthException $e) {
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