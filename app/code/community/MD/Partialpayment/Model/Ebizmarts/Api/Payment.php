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
if(Mage::helper('core')->isModuleEnabled('Ebizmarts_SagePaySuite') && Mage::helper('core')->isModuleOutputEnabled('Ebizmarts_SagePaySuite'))
{
    class MD_Partialpayment_Model_Ebizmarts_Api_Payment extends Ebizmarts_SagePaySuite_Model_Api_Payment
    {
        public function getQuoteDb($sessionQuote) {
            $hasPartialItem = false;

            $grandTotal = $sessionQuote->getGrandTotal();
                $baseGrandTotal = $sessionQuote->getBaseGrandTotal();
            foreach($sessionQuote->getAllVisibleItems() as $item){
                    if($item->getPartialpaymentOptionSelected() == 1){
                        $hasPartialItem = true;
                        $amount = $item->getPartialpaymentPaidAmount();/* - $item->getTaxAmount();*/
                        $baseAmount = $item->getPartialpaymentPaidAmount();/* - $item->getBaseTaxAmount();*/

                        $grandTotal -= $item->getRowTotal();
                        $baseGrandTotal -= $item->getBaseRowTotal();

                        $grandTotal += $amount;
                        $baseGrandTotal += $baseAmount;
                    }
                }
                if($hasPartialItem){
                    $sessionQuote->setGrandTotal($grandTotal);
                    $sessionQuote->setBaseGrandTotal($baseGrandTotal);
                }
            return $sessionQuote;
        }
        protected function _getQuote() {
            $opQuote = Mage::getSingleton('checkout/type_onepage')->getQuote();
            $adminQuote = Mage::getSingleton('adminhtml/session_quote')->getQuote();

            $rqQuoteId = Mage::app()->getRequest()->getParam('qid');
            if ($adminQuote->hasItems() === false && (int) $rqQuoteId) {
                $opQuote->setQuote(Mage::getModel('sales/quote')->loadActive($rqQuoteId));
            }
            $hasPartialItem = false;
            if($adminQuote->hasItems() !== true){
                $grandTotal = $opQuote->getGrandTotal();
                $baseGrandTotal = $opQuote->getBaseGrandTotal();
                foreach($opQuote->getAllVisibleItems() as $item){
                    if($item->getPartialpaymentOptionSelected() == 1){
                        $hasPartialItem = true;
                        $amount = $item->getPartialpaymentPaidAmount();/* - $item->getTaxAmount();*/
                        $baseAmount = $item->getPartialpaymentPaidAmount();/* - $item->getBaseTaxAmount();*/

                        $grandTotal -= $item->getRowTotal();
                        $baseGrandTotal -= $item->getBaseRowTotal();

                        $grandTotal += $amount;
                        $baseGrandTotal += $baseAmount;
                    }
                }
                if($hasPartialItem){
                    $opQuote->setGrandTotal($grandTotal);
                    $opQuote->setBaseGrandTotal($baseGrandTotal);
                }
            }

            return ($adminQuote->hasItems() === true) ? $adminQuote : $opQuote;
        }
    }
}
