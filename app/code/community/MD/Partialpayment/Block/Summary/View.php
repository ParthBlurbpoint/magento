<?php
/**
* Magedelight
* Copyright (C) 2014 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Partialpayment
* @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
class MD_Partialpayment_Block_Summary_View extends Mage_Core_Block_Template
{
    public function __construct() {
        parent::__construct();
        $this->setTemplate('md/partialpayment/summary/view.phtml');
        $paymentId = $this->getRequest()->getParam('payment_id');
        $payment = Mage::getModel('md_partialpayment/payments')->load($paymentId);
        $installments = Mage::getResourceModel('md_partialpayment/summary_collection')
                        ->addFieldToSelect('*')
                        ->addFieldToFilter('payment_id', $paymentId)
                        ->setOrder('summary_id', 'asc');
        
        $this->setSummary($installments);
        $this->setPayment($payment);
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $pager = $this->getLayout()->createBlock('page/html_pager', 'md.partialpayment.summary.pager')
            ->setCollection($this->getSummary());
        $this->setChild('pager', $pager);
        $this->getSummary()->load();
        return $this;
    }
    
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
    
    public function getPayAction($summary)
    {
        return $this->getUrl('*/*/pay', array('summary_id' => $summary->getId()));
    }
    
    public function getBackUrl()
    {
        return $this->getUrl('md_partialpayment/summary/list');
    }
    
    public function getTitle()
    {
        return Mage::helper('md_partialpayment')->__('Installment Summary for Order # %s',$this->getPayment()->getOrderId());
    }
}

