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

class Vaimo_Klarna_Model_Creditmemo_Total extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
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

    protected function _isLoggedIn()
    {
        return Mage::getSingleton('admin/session')->isLoggedIn();
    }

    protected function _getRequest($param)
    {
        return Mage::app()->getRequest()->getParam($param);
    }

    /**
     * Collect the order total
     *
     * @param object $creditmemo The Creditmemo instance to collect from
     *
     * @return Klarna_KlarnaPaymentModule_Model_Creditmemo_Total
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        /*
         * This is a horrible thing to have in this function. But since Magento doesn't have a useful event to listen to, before the creditmemo
         * calls collect, I added it here. I had it in adminhtml_sales_order_creditmemo_register_before but that's ran to late.
         * This picks up the fee amount the user selected to credit, when saving the creditmemo in Admin
         */
        $order = $creditmemo->getOrder();
        $invoice = $creditmemo->getInvoice();
        if ($this->_isLoggedIn()) {
            $data = $this->_getRequest('creditmemo');
            if ($data) {
                if (isset($data['vaimo_klarna_fee_refund'])) {
                    if ($data['vaimo_klarna_fee_refund']=='') {
                        $data['vaimo_klarna_fee_refund'] = 0;
                    }
                    $refundAmount = $data['vaimo_klarna_fee_refund'];
                    $store = $order->getStore();
                    $baseRefundAmount = $store->convertPrice($refundAmount, false);
                    $creditmemo->setVaimoKlarnaFeeRefund($refundAmount);
                    $creditmemo->setVaimoKlarnaBaseFeeRefund($baseRefundAmount);
                }
            }
        }

        if ($order) {
            $payment = $order->getPayment();
            if ($payment) {
                if (!$this->_getHelper()->isMethodKlarna($payment->getMethod())) {
                    return $this;
                }
                if (!$invoice) {
                    $klarna = Mage::getModel('klarna/klarna');
                    $klarna->setOrder($order);
                    if (!$klarna->getConfigData('disable_backend_calls')) {
                        Mage::getSingleton('adminhtml/session')->addError(
                            $this->_getHelper()->__('You must create the credit memo from the invoice, not directly from the order, to update Klarna with the credited amount/items')
                        );
                    }
                    return $this;
                }
                $info = $payment->getMethodInstance()->getInfoInstance();
                if (!$info) {
                  return $this;
                }
                if ($invoice->getTransactionId()==$info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_CAPTURED_TRANSACTION_ID)) {
                    $klarnaFee = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE) ;

                    if (!$klarnaFee){
                        return $this;
                    }

                    $klarnaFeeTax = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX);
                    $baseKlarnaFee = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE);
                    $baseKlarnaFeeTax = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE_TAX);

                    $baseCreditmemoTotal = $creditmemo->getBaseGrandTotal();
                    $creditmemoTotal = $creditmemo->getGrandTotal();

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
                    $klarnaFee = $klarnaFee - $refundedExVat;
                    $klarnaFeeTax = $klarnaFeeTax - $refundedVat;

                    $baseRefundedExVat = round($baseKlarnaFeeRefunded * $rate,0);
                    $baseRefundedVat = $baseKlarnaFeeRefunded - $baseRefundedExVat;
                    $baseKlarnaFee = $baseKlarnaFee - $baseRefundedExVat;
                    $baseKlarnaFeeTax = $baseKlarnaFeeTax - $baseRefundedVat;

                    /*
                     * Refund part, meaning how much will be refunded on this refund
                     */
                    $klarnaFeeRefund = $creditmemo->getVaimoKlarnaFeeRefund();
                    $baseKlarnaFeeRefund = $creditmemo->getVaimoKlarnaBaseFeeRefund();

                    if ($klarnaFeeRefund || $klarnaFeeRefund===0 || $klarnaFeeRefund==="0") {
                        $baseCreditmemoTotal = $baseCreditmemoTotal + $baseKlarnaFeeRefund;
                        $creditmemoTotal = $creditmemoTotal + $klarnaFeeRefund;

                        $klarnaFee = round($klarnaFeeRefund * $rate,0);
                        $baseKlarnaFee = round($baseKlarnaFeeRefund * $rate,0);
                    } else {
                        $baseCreditmemoTotal = $baseCreditmemoTotal + $baseKlarnaFee + $baseKlarnaFeeTax;
                        $creditmemoTotal = $creditmemoTotal + $klarnaFee + $klarnaFeeTax;
                    }

                    /*
                     * Update totals and Klarna Amount fields on Creditmemo
                     */
                    $creditmemo->setVaimoKlarnaBaseFee($baseKlarnaFee);
                    $creditmemo->setVaimoKlarnaFee($klarnaFee);
                    $creditmemo->setKlarnaFeeRefunded($klarnaFeeRefunded);

                    $creditmemo->setBaseGrandTotal($baseCreditmemoTotal);
                    $creditmemo->setGrandTotal($creditmemoTotal);
                }
            }
        }

        return $this;
    }
}
