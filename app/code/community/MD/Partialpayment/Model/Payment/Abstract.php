<?php
class MD_Partialpayment_Model_Payment_Abstract
{
    protected $_order = null;
    protected $_summary = null;
    protected $_payments = null;
    protected $_requestArea = '';
    protected $_fullCapture = false;
    
    public function setOrder($order)
    {
        if(is_null($this->_order)){
            $this->_order = $order;
        }
        return $this;
    }
    public function setPaymentRequestArea($area = null){
        if(!is_null($area)){
            $this->_requestArea = $area.'_';
        }
        return $this;
    }
    
    public function getPaymentRequestArea(){
        return $this->_requestArea;
    }
    
    public function getOrder()
    {
        return $this->_order;
    }
    
    public function getPayments()
    {
        return $this->_payments;
    }
    
    public function getSummary()
    {
        return $this->_summary;
    }
    
    public function setPayments($payments)
    {
        if(is_null($this->_payments)){
            $this->_payments = $payments;
        }
        return $this;
    }
    
    public function setSummary($summary){
        if(is_null($this->_summary)){
            $this->_summary = $summary;
        }
        return $this;
    }
    
    public function pay($details){
        return $this;
    }
    
    public function getDetails()
    {
        return $this;
    }
    
    public function getCCName($code = null){
        $configCcCards = Mage::getSingleton('payment/config')->getCcTypes();
        if($code && array_key_exists($code, $configCcCards)){
            return $configCcCards[$code];
        }
        return null; 
    }
    
    public function setIsFullCapture($fullCapture = false)
    {
        $this->_fullCapture = $fullCapture;
        return $this;
    }
    
    public function getIsFullCapture()
    {
        return $this->_fullCapture;
    }        
}

