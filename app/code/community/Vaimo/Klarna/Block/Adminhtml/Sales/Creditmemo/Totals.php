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

class Vaimo_Klarna_Block_Adminhtml_Sales_Creditmemo_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function initTotals()
    {        
        $parent = $this->getParentBlock();
        $creditmemo = $parent->getCreditmemo();
        if ($creditmemo) {
            if ($creditmemo->getVaimoKlarnaFee()) {
                $fee = new Varien_Object();
                $fee->setLabel(Mage::helper('klarna')->getKlarnaFeeLabel($creditmemo->getStore()));
                $config = Mage::getSingleton('klarna/tax_config');
                if ($config->displaySalesKlarnaFeeInclTax($creditmemo->getStoreId())) {
                    $fee->setValue($creditmemo->getVaimoKlarnaFee() + $creditmemo->getVaimoKlarnaFeeTax());
                    $fee->setBaseValue($creditmemo->getVaimoKlarnaBaseFee() + $creditmemo->getVaimoKlarnaBaseFeeTax());
                } else {
                    $fee->setValue($creditmemo->getVaimoKlarnaFee());
                    $fee->setBaseValue($creditmemo->getVaimoKlarnaBaseFee());
                }
                $fee->setCode('vaimo_klarna_fee');
                $parent->addTotal($fee, 'subtotal');
            }
        }
        return $this;
    }
}