<?php
class MD_Partialpayment_Block_Sagepay_Form_SagePayServer extends Mage_Core_Block_Template
{
    protected function _prepareLayout() {
        $this->setChild('token.cards.li',$this->getLayout()->createBlock('md_partialpayment/sagepay_form_tokenList', 'token.cards.li')->setCanUseToken($this->canUseToken())->setPaymentMethodCode('sagepayserver'));
        return parent::_prepareLayout();
    }
    
    public function getTokenCards($methodCode = null) {
        if($this->getRequest()->getControllerName() == "adminhtml_summary"){
            $allCards = $this->helper('md_partialpayment')->loadCustomerCards($this->getRequest()->getParam("id",null));
        }else{
            $allCards = $this->helper('sagepaysuite/token')->loadCustomerCards($methodCode);
        }

        return $allCards;
    }

    public function canUseToken() {
        if($this->getRequest()->getControllerName() == "adminhtml_summary"){
            return true;
        }
        $ret = Mage::getModel('sagepaysuite/sagePayToken')->isEnabled();

        if(!$this->helper('sagepaysuite')->creatingAdminOrder()) {
            $ret = $ret && (Mage::getModel('checkout/type_onepage')->getCheckoutMethod() != 'guest');
        }

        return $ret;
    }
    
    protected function _construct()
    {
        parent::_construct();

		$this->setTemplate('md/partialpayment/summary/payment/form/sagePayServer.phtml');
    }

	public function allowGiftAid()
	{
		return (bool)((int)Mage::getStoreConfig('sagepaysuite/sagepayserver/allow_gift_aid') === 1);
	}

	public function getCcImg($cc)
	{
		return $this->helper('sagepaysuite')->getCcImage($this->helper('sagepaysuite')->getCardLabel($cc->getCardType(), false));
	}

    /*
    * Whether switch/solo card type available
    */
    public function hasSsCardType()
    {
        $availableTypes = explode(',', Mage::getStoreConfig('sagepaysuite/sagepayserver/cctypesSagePayDirectPro'));
        $ssPresenations = array_intersect(array('SOLO', 'SWITCH', 'MAESTRO'), $availableTypes);
        if ($availableTypes && count($ssPresenations) > 0) {
            return true;
        }
        return false;
    }

    public function getProtxAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypesProtx();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigDataProtx('cctypesSagePayDirectPro');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

        /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcStartMonths()
    {
       $months = $this->getData('cc_start_months');
       if (is_null($months)) {
           $months[0] =  $this->__('Month');
           $months = array_merge($months, $this->_getConfig()->getMonths());
           $this->setData('cc_start_months', $months);
       }
       return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcStartYears()
    {
       $years = $this->getData('cc_start_years');

       if(is_null($years))
       {
               $years = $this->_getConfig()->getYearsStart();
               $years = array(0 => $this->__('Year'))+$years;
               $this->setData('cc_start_years', $years);
       }

       return $years;
    }


    public function getCcAvailableTypes()
    {
        $types = Mage::getModel('sagepaysuite/config')->getCcTypesSagePayDirect();

        $availableTypes = Mage::getStoreConfig('payment/' . $this->getMethodCode() . '/cctypesSagePayDirectPro');

        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);

            foreach ($types as $code=>$name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }
        return $types;
    }
}

