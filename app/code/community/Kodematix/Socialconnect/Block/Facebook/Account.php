<?php


class Kodematix_Socialconnect_Block_Facebook_Account extends Mage_Core_Block_Template
{
    protected $client = null;
    protected $userData = null;
    protected function _construct() {
        parent::_construct();
        $this->client = Mage::getSingleton('kodematix_socialconnect/facebook_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }
        $this->userData = Mage::registry('kodematix_socialconnect_facebook_userinfo');
        $this->setTemplate('kodematix/socialconnect/facebook/account.phtml');
    }

    protected function _hasUserData()
    {
        return (bool) $this->userData;
    }

    protected function _getFacebookId()
    {
        return $this->userData->id;
    }

    protected function _getStatus()
    {
        if(!empty($this->userData->link)) {
            $link = '<a href="'.$this->userData->link.'" target="_blank">'.
                    $this->htmlEscape($this->userData->name).'</a>';
        } else {
            $link = $this->userData->name;
        }

        return $link;
    }

    protected function _getEmail()
    {
        return $this->userData->email;
    }

    protected function _getPicture()
    {
        if(!empty($this->userData->picture)) {
            return Mage::helper('kodematix_socialconnect/facebook')
                    ->getProperDimensionsPictureUrl($this->userData->id,
                            $this->userData->picture->data->url);
        }

        return null;
    }

    protected function _getName()
    {
        return $this->userData->name;
    }

    protected function _getGender()
    {
        if(!empty($this->userData->gender)) {
            return ucfirst($this->userData->gender);
        }

        return null;
    }

    protected function _getBirthday()
    {
        if(!empty($this->userData->birthday)) {
            $birthday = date('F j, Y', strtotime($this->userData->birthday));
            return $birthday;
        }

        return null;
    }

}