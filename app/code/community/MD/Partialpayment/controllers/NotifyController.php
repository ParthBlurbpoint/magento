<?php
class MD_Partialpayment_NotifyController extends Mage_Core_Controller_Front_Action
{
    public function relayResponseAction()
    {
        $data = $this->getRequest()->getPost();
        $summaryId = $this->getRequest()->getParam('summary_id', null);
        Mage::getModel('md_partialpayment/payment_authorizenet_directpost')->process($data, $summaryId);
        Mage::log($data,false,'local_response.log');
    }
}

