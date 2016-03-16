<?php

class Kodematix_Socialconnect_Model_Resource_Setup extends Mage_Eav_Model_Entity_Setup
{
    protected $_customerAttributes = array();

    public function setSocialCustomerAttributes($customerAttributes)
    {
        $this->_customerAttributes = $customerAttributes;

        return $this;
    }
    
 
    public function installSocialCustomerAttributes()
    {        
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->addAttribute('customer', $code, $attr);
        }

        return $this;
    }

    public function removeSocialCustomerAttributes()
    {
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->removeAttribute('customer', $code);
        }

        return $this;
    }  
}
