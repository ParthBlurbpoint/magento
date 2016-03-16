<?php
class MD_Partialpayment_Model_Payment_Checkmo extends MD_Partialpayment_Model_Payment_Abstract
{
    public function pay($details = null)
    {
        $area = ($this->getPaymentRequestArea() == 'adminhtml_') ? 'adminhtml': 'core';
        if($this->getIsFullCapture()){
            $dueSummaryCollection = $this->getPayments()->getDueInstallmentCollections();
            if($dueSummaryCollection){
                foreach($dueSummaryCollection as $_summary){
                    $_summary->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_PROCESS);
                    $_summary->setPaymentMethod($details['method']);
                    $_summary->setPaidDate(Mage::getSingleton('core/date')->gmtDate());
                    $_summary->setId($_summary->getId())->save();
                }
                $this->getPayments()->setFullPayment(1)->setId($this->getPayments()->getId())->save();
                Mage::getSingleton($area.'/session')->addSuccess(Mage::helper('md_partialpayment')->__('Your Payment Has Been Submitted for review.'));
            }
        }else{
            $summary = $this->getSummary();

            $summary->setStatus(MD_Partialpayment_Model_Summary::PAYMENT_PROCESS);
            $summary->setPaymentMethod($details['method']);
            $summary->setPaidDate(date('Y-m-d'));
            try{
                $summary->setId($summary->getId())->save();
                Mage::getSingleton($area.'/session')->addSuccess(Mage::helper('md_partialpayment')->__('Your Payment Has Been Submitted for review.'));
            }catch(Exception $e){
                Mage::getSingleton($area.'/session')->addError($e->getMessage());
            }
        }
        return $this;
    } 
    public function getDetails()
    {
        return '';
    }
    
    public function getResponseText()
    {
        return '';
    }
}

