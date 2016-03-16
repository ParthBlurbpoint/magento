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

class Vaimo_Klarna_Model_Invoice_Tax extends Mage_Sales_Model_Order_Invoice_Total_Abstract
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

    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();

        if ($order->hasInvoices() != 0) {
            return $this;
        }

        $payment = $order->getPayment();

        if (!$payment) {
          return $this;
        }

        if (!$this->_getHelper()->isMethodKlarna($payment->getMethod())) {
            return $this;
        }

        $info = $payment->getMethodInstance()->getInfoInstance();

        if (!$info) {
          return $this;
        }

        if (!$this->_getHelper()->collectInvoiceAddTaxToInvoice()) {
            return $this;
        }

        $klarnaFeeTax =  $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX);

        if (!$klarnaFeeTax){
            return $this;
        }

        $baseKlarnaFeeTax = $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE_TAX);

        $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount()+$baseKlarnaFeeTax);
        $invoice->setTaxAmount($invoice->getTaxAmount()+$klarnaFeeTax);

        $invoice->setVaimoKlarnaBaseFeeTax($baseKlarnaFeeTax);
        $invoice->setVaimoKlarnaFeeTax($klarnaFeeTax);

        $order->setVaimoKlarnaBaseFeeTaxInvoiced($baseKlarnaFeeTax);
        $order->setVaimoKlarnaFeeTaxInvoiced($klarnaFeeTax);

        return $this;
    }
}
