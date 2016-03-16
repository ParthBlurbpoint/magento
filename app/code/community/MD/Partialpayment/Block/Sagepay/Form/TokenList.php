<?php
class MD_Partialpayment_Block_Sagepay_Form_TokenList extends Mage_Core_Block_Template
{
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('md/partialpayment/summary/payment/form/sagepayTokenList.phtml');
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml() {
        if (!$this->getCanUseToken()) {
            return '';
        }
        return parent::_toHtml();
    }

    public function getAvailableTokenCards($methodCode = null) {
        if($this->getRequest()->getControllerName() == "adminhtml_summary"){
            $allCards = $this->helper('md_partialpayment')->loadCustomerCards($this->getRequest()->getParam("id",null));
        }else{
            $allCards = $this->helper('sagepaysuite/token')->loadCustomerCards($methodCode);
        }
        return $allCards;
    }
}

