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

class Vaimo_Klarna_Model_Creditmemo_Tax extends Mage_Sales_Model_Order_Creditmemo_Total_Tax
{
    protected $_moduleHelper = NULL;

    /**
     * constructor
     *
     * @param  $moduleHelper
     */
    public function __construct($moduleHelper = NULL)
    {
        $this->_moduleHelper = $moduleHelper;
        if ($this->_moduleHelper==NULL) {
            $this->_moduleHelper = Mage::helper('klarna');
        }
    }

    protected function _getHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Collect the order total
     *
     * @param object $creditmemo The Creditmemo instance to collect from
     *
     * @return Klarna_KlarnaPaymentModule_Model_Creditmemo_Tax
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $invoice = $creditmemo->getInvoice();
        if ($order && $invoice) {
            $payment = $order->getPayment();
            if ($payment) {
                if (!$this->_getHelper()->isMethodKlarna($payment->getMethod())) {
                    return $this;
                }
                $info = $payment->getMethodInstance()->getInfoInstance();
                if (!$info) {
                  return $this;
                }
                if ($invoice->getTransactionId()==$info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_CAPTURED_TRANSACTION_ID)) {

                    $klarnaFeeTax = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX);

                    if (!$klarnaFeeTax){
                        return $this;
                    }

                    $klarnaFee = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE) ;
                    $baseKlarnaFeeTax = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE_TAX);

                    $baseCreditmemoTax = $creditmemo->getBaseTaxAmount();
                    $creditmemoTax = $creditmemo->getTaxAmount();

                    /*
                     * Refunded part, meaing how much was refunded before
                     */
                    $klarnaFeeRefunded = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_REFUNDED);
                    $store = $order->getStore();
                    $baseKlarnaFeeRefunded = $store->convertPrice($klarnaFeeRefunded, false);

                    if ($klarnaFeeRefunded < ($klarnaFee + $klarnaFeeTax)) {
                        $rate = $klarnaFee / ($klarnaFee + $klarnaFeeTax);
                    } else {
                        return $this;
                    }

                    $refundedExVat = round($klarnaFeeRefunded * $rate,0);
                    $refundedVat = $klarnaFeeRefunded - $refundedExVat;
                    $klarnaFeeTax = $klarnaFeeTax - $refundedVat;

                    $baseRefundedExVat = round($baseKlarnaFeeRefunded * $rate,0);
                    $baseRefundedVat = $baseKlarnaFeeRefunded - $baseRefundedExVat;
                    $baseKlarnaFeeTax = $baseKlarnaFeeTax - $baseRefundedVat;

                    /*
                     * Refund part, meaning how much will be refunded on this refund
                     */
                    $klarnaFeeRefund = $creditmemo->getVaimoKlarnaFeeRefund();
                    $baseKlarnaFeeRefund = $creditmemo->getVaimoKlarnaBaseFeeRefund();

                    if ($klarnaFeeRefund || $klarnaFeeRefund===0 || $klarnaFeeRefund==="0") {
                        $refundExVat = round($klarnaFeeRefund * $rate,0);
                        $klarnaFeeTax = $klarnaFeeRefund - $refundExVat;

                        $baseRefundExVat = round($baseKlarnaFeeRefund * $rate,0);
                        $baseKlarnaFeeTax = $baseKlarnaFeeRefund - $baseRefundExVat;
                    }

                    /*
                     * Update totals and Klarna Amount fields on Creditmemo
                     */
                    $baseCreditmemoTax = $baseCreditmemoTax + $baseKlarnaFeeTax;
                    $creditmemoTax = $creditmemoTax + $klarnaFeeTax;

                    $creditmemo->setBaseTaxAmount($baseCreditmemoTax);
                    $creditmemo->setTaxAmount($creditmemoTax);

                    $creditmemo->setVaimoKlarnaBaseFeeTax($baseKlarnaFeeTax);
                    $creditmemo->setVaimoKlarnaFeeTax($klarnaFeeTax);
                }
            }
        }

    }
}
