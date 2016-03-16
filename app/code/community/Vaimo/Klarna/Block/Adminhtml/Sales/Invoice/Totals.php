<?php
/**
 * Copyright (c) 2009-2014 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_Klarna
 * @copyright   Copyright (c) 2009-2014 Vaimo AB
 */

class Vaimo_Klarna_Block_Adminhtml_Sales_Invoice_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function initTotals()
    {        
        $parent = $this->getParentBlock();
        $order = $this->getOrder();
        $payment = $order->getPayment();
        
        if (Mage::helper('klarna')->isMethodKlarna($payment->getMethod())) {
            $klarnaFeeExclVAT = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE);
            if ($klarnaFeeExclVAT) {
                $invoice = $parent->getInvoice();
                if ($payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_CAPTURED_TRANSACTION_ID)==$invoice->getTransactionId()) {
                    $klarnaFeeVAT = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX);
                    $baseKlarnaFeeExclVAT = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE);
                    $baseKlarnaFeeVAT = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE_TAX);

                    $fee = new Varien_Object();
                    $fee->setLabel(Mage::helper('klarna')->getKlarnaFeeLabel($order->getStore()));
                    $config = Mage::getSingleton('klarna/tax_config');
                    if ($config->displaySalesKlarnaFeeInclTax($order->getStoreId())) {
                        $fee->setValue($klarnaFeeExclVAT + $klarnaFeeVAT);
                        $fee->setBaseValue($baseKlarnaFeeExclVAT + $baseKlarnaFeeVAT);
                    } else {
                        $fee->setValue($klarnaFeeExclVAT);
                        $fee->setBaseValue($baseKlarnaFeeExclVAT);
                    }
                    $fee->setCode('vaimo_klarna_fee');
                    $parent->addTotal($fee, 'subtotal');
                }
            }
        }
        return $this;
    }
}