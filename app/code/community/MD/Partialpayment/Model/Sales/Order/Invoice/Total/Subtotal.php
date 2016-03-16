<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class MD_Partialpayment_Model_Sales_Order_Invoice_Total_Subtotal extends Mage_Sales_Model_Order_Invoice_Total_Subtotal
{
    /**
     * Collect invoice subtotal
     *
     * @param   Mage_Sales_Model_Order_Invoice $invoice
     * @return  Mage_Sales_Model_Order_Invoice_Total_Subtotal
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $subtotal	       = 0;
        $baseSubtotal	       = 0;
        $subtotalInclTax       = 0;
        $baseSubtotalInclTax   = 0;
        $totalWeeeDiscount     = 0;
        $totalBaseWeeeDiscount = 0;
        $order		       = $invoice->getOrder();
	$paymentTransId	       = $order->getPayment()->getLastTransId();
	$taxAlreadyPaidFlag    = false;
	$totalPaid	       = 0; 
	$controllerName	       = Mage::app()->getRequest()->getControllerName();
	$route		       = Mage::app()->getRequest()->getRouteName();
	
	if($controllerName == 'sales_order_invoice' && $route=='adminhtml') {
	    if (!isset($paymentTransId)) {
		$isFullSelected     = $order->getMdPartialpaymentFullCart();

		if($isFullSelected == 1) {
		    $taxAlreadyPaidFlag = true;
		}
	    }
	}
	
        foreach ($invoice->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy()) {
                continue;
            }

	    if($controllerName == 'sales_order_invoice' && $route=='adminhtml') {
		if (!isset($paymentTransId)) {
		    $isPartialPaymentApplied = (boolean)$item->getOrderItem()->getData('partialpayment_option_selected');

		    if($isPartialPaymentApplied == 1) {
			$taxAlreadyPaidFlag = true;
		    }
		}
	    }
	    
            $item->calcRowTotal();

	    if($taxAlreadyPaidFlag == true) {
		$totalPaid = $order->getTotalPaid() - $order->getTaxAmount() - $order->getShippingAmount() - $order->getShippingTaxAmount();		
		$subtotal	       -= $order->getDiscountAmount();
		$baseSubtotal	       -= $order->getBaseDiscountAmount();
		$subtotalInclTax       -= $order->getDiscountAmount();
		$baseSubtotalInclTax   -= $order->getDiscountAmount();
	    } else {
		$subtotal	       += $item->getRowTotal();
		$baseSubtotal	       += $item->getBaseRowTotal();
		$subtotalInclTax       += $item->getRowTotalInclTax();
		$baseSubtotalInclTax   += $item->getBaseRowTotalInclTax();
		$totalWeeeDiscount     += $item->getOrderItem()->getDiscountAppliedForWeeeTax();
		$totalBaseWeeeDiscount += $item->getOrderItem()->getBaseDiscountAppliedForWeeeTax();
	    }
        }

	$subtotal              += $totalPaid;
	$baseSubtotal	       += $totalPaid;
	$subtotalInclTax       += $totalPaid;
	$baseSubtotalInclTax   += $totalPaid;
	
        $allowedSubtotal	= $order->getSubtotal() - $order->getSubtotalInvoiced();
        $baseAllowedSubtotal	= $order->getBaseSubtotal() - $order->getBaseSubtotalInvoiced();
        $allowedSubtotalInclTax = $allowedSubtotal + $order->getHiddenTaxAmount() + $totalWeeeDiscount
				+ $order->getTaxAmount() - $order->getTaxInvoiced() - $order->getHiddenTaxInvoiced();
        $baseAllowedSubtotalInclTax = $baseAllowedSubtotal + $order->getBaseHiddenTaxAmount() + $totalBaseWeeeDiscount
				+ $order->getBaseTaxAmount() - $order->getBaseTaxInvoiced() 
				- $order->getBaseHiddenTaxInvoiced();

        /**
         * Check if shipping tax calculation is included to current invoice.
         */
        $includeShippingTax = true;
        foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
            if ($previousInvoice->getShippingAmount() && !$previousInvoice->isCanceled()) {
                $includeShippingTax = false;
                break;
            }
        }

        if ($includeShippingTax) {
            $allowedSubtotalInclTax     -= $order->getShippingTaxAmount();
            $baseAllowedSubtotalInclTax -= $order->getBaseShippingTaxAmount();
        } else {
            $allowedSubtotalInclTax     += $order->getShippingHiddenTaxAmount();
            $baseAllowedSubtotalInclTax += $order->getBaseShippingHiddenTaxAmount();
        }
	
	if($totalPaid <= 0) {
	    if ($invoice->isLast()) {
		$subtotal	     = $allowedSubtotal;
		$baseSubtotal	     = $baseAllowedSubtotal;
		$subtotalInclTax     = $allowedSubtotalInclTax;
		$baseSubtotalInclTax = $baseAllowedSubtotalInclTax;
	    } else {
		$subtotal	     = min($allowedSubtotal, $subtotal);
		$baseSubtotal        = min($baseAllowedSubtotal, $baseSubtotal);
		$subtotalInclTax     = min($allowedSubtotalInclTax, $subtotalInclTax);
		$baseSubtotalInclTax = min($baseAllowedSubtotalInclTax, $baseSubtotalInclTax);
	    }
        }
        

        $invoice->setSubtotal($subtotal);
        $invoice->setBaseSubtotal($baseSubtotal);
        $invoice->setSubtotalInclTax($subtotalInclTax);
        $invoice->setBaseSubtotalInclTax($baseSubtotalInclTax);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $subtotal);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseSubtotal);
	
        return $this;
    }
}
