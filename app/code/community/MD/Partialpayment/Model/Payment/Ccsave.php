<?php
class MD_Partialpayment_Model_Payment_Ccsave extends MD_Partialpayment_Model_Payment_Abstract
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
            $summary->setTransactionDetails(serialize($details));
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
        $transactionDetails = unserialize($this->getSummary()->getTransactionDetails());
        $order = $this->getSummary()->getPayments()->getOrder();
        $details = array();
        $helper = Mage::helper('md_partialpayment');
        if(is_array($transactionDetails) && count($transactionDetails) > 0){
            
            
            if(array_key_exists('cc_type',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Credit Card Type').':</b> '.$this->getCCName($transactionDetails['cc_type']);
            }
            
            if(array_key_exists('cc_number',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Credit Card Number').':</b> '.$transactionDetails['cc_number'];
            }
            
            if(array_key_exists('cc_owner',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Name on the Card').':</b> '.$transactionDetails['cc_owner'];
            }
            
            if(array_key_exists('cc_cid',$transactionDetails)){
                $details[] = '<b>'.$helper->__('Card Verification Number').':</b> '.$transactionDetails['cc_cid'];
            }
            
            if(array_key_exists('cc_exp_month',$transactionDetails) && array_key_exists('cc_exp_year',$transactionDetails)){
                $month = (strlen($transactionDetails['cc_exp_month']) <= 1) ? '0'.$transactionDetails['cc_exp_month']: $transactionDetails['cc_exp_month'];
                $year = $transactionDetails['cc_exp_year'];
                $details[] = '<b>'.$helper->__('Expiration Date').':</b> '.$month.' / '.$year;
            }
            
            $details[] = $helper->__('Order was placed using <b>%s</b>', $order->getBaseCurrencyCode());
            
        }
        
        return $details;
    }
}

