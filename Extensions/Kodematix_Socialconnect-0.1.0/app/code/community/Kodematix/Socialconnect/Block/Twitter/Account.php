<?php


class Kodematix_Socialconnect_Block_Twitter_Account extends Mage_Core_Block_Template
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

        $this->setTemplate('kodematix/socialconnect/twitter/account.phtml');

    }

    protected function _hasUserData()
    {
        return (bool) $this->userData;
    }

    protected function _getTwitterId()
    {
        return $this->userData->id;
    }

    protected function _getStatus()
    {
        return '<a href="'.sprintf('https://twitter.com/%s', $this->userData->screen_name).'" target="_blank">'.
                    $this->htmlEscape($this->userData->screen_name).'</a>';
    }

    protected function _getPicture()
    {
        if(!empty($this->userData->profile_image_url)) {
            return Mage::helper('kodematix_socialconnect/twitter')
                    ->getProperDimensionsPictureUrl($this->userData->id,
                            $this->userData->profile_image_url);
        }

        return null;
    }

    protected function _getName()
    {
        return $this->userData->name;
    }

}
