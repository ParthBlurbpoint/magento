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
class MD_Partialpayment_Block_Paypal_Standard_Redirect  extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $isPartialFullSelected = false;
        if($this->getPaymentRequestArea() == 'adminhtml'){
            $isPartialFullSelected = false;
            $summaryId = Mage::getSingleton('adminhtml/session')->getPartialSummaryId(true);
        }else{
           $isPartialFullSelected = (boolean)Mage::getSingleton('core/session')->getPartialFullSelected(true);
           $summaryId = Mage::getSingleton('core/session')->getPartialSummaryId(true); 
        }
        $p = $this->getPageNo();
        $limit = $this->getPagerLimit();
        $summary = ($summaryId) ? Mage::getModel('md_partialpayment/summary')->load($summaryId): null;
        $payments = ($summary->getId()) ? $summary->getPayments(): null;
        $order = ($payments->getId()) ? $payments->getOrder(): null;
        
        if($summary && $payments && $order){
            
            $paypalRequestsFields = Mage::getModel('md_partialpayment/payment_paypal_standard')
                                        ->setSummary($summary)
                                        ->setPayments($payments)
                                        ->setIsFullCapture($isPartialFullSelected)
                                        ->setOrder($order)
                                        ->setPageNo($p)
                                        ->setPagerLimit($limit)
                                        ->setPaymentRequestArea($this->getPaymentRequestArea())
                                        ->getRequestObject();
            
            $form = new Varien_Data_Form();
            $form->setAction($paypalRequestsFields->getRedirectUrl())
            ->setId('paypal_standard_checkout')
            ->setName('paypal_standard_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
            
            foreach ($paypalRequestsFields->getData() as $field=>$value) {
                if($field == 'redirect_url'){
                    continue;
                }
                $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
            }
            $idSuffix = Mage::helper('core')->uniqHash();
            $submitButton = new Varien_Data_Form_Element_Submit(array(
                'value'    => $this->__('Click here if you are not redirected within 10 seconds...'),
            ));
            $id = "submit_to_paypal_button_{$idSuffix}";
            $submitButton->setId($id);
            $form->addElement($submitButton);
            $html = '<html><body>';
            $html.= $this->__('You will be redirected to the PayPal website in a few seconds.');
            $html.= $form->toHtml();
            $html.= '<script type="text/javascript">document.getElementById("paypal_standard_checkout").submit();</script>';
            $html.= '</body></html>';
            return $html;
        }
    }
}

