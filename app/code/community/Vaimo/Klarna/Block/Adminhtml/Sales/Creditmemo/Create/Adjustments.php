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

class Vaimo_Klarna_Block_Adminhtml_Sales_Creditmemo_Create_Adjustments extends Mage_Adminhtml_Block_Template
{
    protected $_source;
    /**
     * Initialize creditmemo agjustment totals
     *
     * @return Mage_Tax_Block_Sales_Order_Tax
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_source  = $parent->getSource();
        $total = new Varien_Object(array(
            'code'      => 'adjust_klarna_fee',
            'block_name'=> $this->getNameInLayout()
        ));
        $parent->removeTotal('vaimo_klarna_fee');
        $parent->addTotalBefore($total, 'agjustments'); // Yes, misspelled in Magento Core
        return $this;
    }

    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Get credit memo shipping amount depend on configuration settings
     * @return float
     */
    public function getKlarnaInvoiceFeeAmount()
    {
        $fee = NULL;
        $creditmemo = $this->getSource();
        if ($creditmemo) {
            if ($creditmemo->getVaimoKlarnaFee()!==NULL) {
                $fee = $creditmemo->getVaimoKlarnaFee() + $creditmemo->getVaimoKlarnaFeeTax();
                $fee = Mage::app()->getStore()->roundPrice($fee);
            }
        }
        return $fee;
    }

    /**
     * Get label for shipping total based on configuration settings
     * @return string
     */
    public function getKlarnaInvoiceFeeLabel()
    {
        $config = Mage::getSingleton('klarna/tax_config');
        $source = $this->getSource();
        if ($config->displaySalesKlarnaFeeInclTax($source->getOrder()->getStoreId())) {
            $label = Mage::helper('klarna')->getKlarnaFeeLabel($source->getOrder()->getStore()) . " " . $this->helper('klarna')->__('Refund') . ' ' . Mage::helper("tax")->getIncExcTaxLabel(true);
        } elseif ($config->displaySalesKlarnaFeeBoth($source->getOrder()->getStoreId())) {
            $label = Mage::helper('klarna')->getKlarnaFeeLabel($source->getOrder()->getStore()) . " " . $this->helper('klarna')->__('Refund') . ' ' . Mage::helper("tax")->getIncExcTaxLabel(false);
        } else {
            $label = Mage::helper('klarna')->getKlarnaFeeLabel($source->getOrder()->getStore()) . " " . $this->helper('klarna')->__('Refund');
        }
        return $label;
    }
}
