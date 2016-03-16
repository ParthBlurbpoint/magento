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
class MD_Partialpayment_Block_Adminhtml_Sales_Order_Totals_Partial extends Mage_Core_Block_Template
{
    public function initTotals()
    {
        $parent        = $this->getParentBlock();
        $order	       = $parent->getOrder();
        $source	       = $parent->getSource();
        $totalPaid     = 0;
        $totalDue      = 0;
        $partialExists = false;
	
        foreach($order->getAllVisibleItems() as $_items) {
            if($_items->getPartialpaymentOptionSelected() == 1) {
                $partialExists = true;
                $totalPaid    += $_items->getPartialpaymentPaidAmount() * $_items->getQtyOrdered();
            } else {
                $totalPaid    += $_items->getRowTotal();
            }
        }
	if(!Mage::getStoreConfig('md_partialpayment/general/shipping_tax_installment')) {
	    $totalPaid += $order->getTaxAmount();
	    $totalPaid += $order->getShippingAmount();
	}  
	
        //$totalPaid -= $order->getDiscountAmount();        
        $totalDue   = max(($order->getGrandTotal() - $totalPaid),0);
        $finalPaid  = max($totalPaid,0);
        
        if($partialExists) {
            $totals = new Varien_Object(array(
                'code'      => 'md_partialpayment_paid',
                'strong'    => true,
                'value'     => $finalPaid,
                'base_value'=> $finalPaid,
                'label'     => Mage::helper('md_partialpayment')->__('Amount Paying Now'),

            ));
            $this->getParentBlock()->addTotal($totals);
            $totals = new Varien_Object(array(
                'code'      => 'md_partialpayment_due',
                'strong'    => true,
                'value'     => $totalDue,
                'base_value'=> $totalDue,
                'label'     => Mage::helper('md_partialpayment')->__('Amount To be Paid Later'),

            ));
            $this->getParentBlock()->addTotal($totals);
        }
        return $this;
    }
}

