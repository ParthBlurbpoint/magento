<?php


class Kodematix_Socialconnect_Block_Google_Account extends Mage_Core_Block_Template
{
    protected $client = null;
    protected $userData = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('kodematix_socialconnect/google_applicant');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userData = Mage::registry('kodematix_socialconnect_google_userinfo');

        $this->setTemplate('kodematix/socialconnect/google/account.phtml');

    }

    protected function _hasUserData()
    {
        return (bool) $this->userData;
    }

    protected function _getGoogleId()
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
            return Mage::helper('kodematix_socialconnect/google')
                    ->getProperDimensionsPictureUrl($this->userData->id,
                            $this->userData->picture);
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
            if((strpos($this->userData->birthday, '0000')) === false) {
                $birthday = date('F j, Y', strtotime($this->userData->birthday));
            } else {
                $birthday = date(
                    'F j',
                    strtotime(
                        str_replace('0000', '1970', $this->userData->birthday)
                    )
                );
            }

            return $birthday;
        }

        return null;
    }

}
