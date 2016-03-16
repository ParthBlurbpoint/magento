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

class Vaimo_Klarna_Model_Quote_Tax extends Mage_Sales_Model_Quote_Address_Total_Tax
{
    protected $_moduleHelper = NULL;
    protected $_salesHelper = NULL;

    protected $_taxCalculation = NULL;
    protected $_taxConfig = NULL;

    /**
     * constructor
     *
     * @param  $moduleHelper
     * @param  $salesHelper
     * @param  $taxCalculation
     * @param  $taxConfig
     */
    public function __construct($moduleHelper = NULL, $salesHelper = NULL,
                                $taxCalculation = NULL, $taxConfig = NULL)
    {
        $this->setCode('vaimo_klarna_fee_tax');

        $this->_moduleHelper = $moduleHelper;
        if ($this->_moduleHelper==NULL) {
            $this->_moduleHelper = Mage::helper('klarna');
        }
        $this->_salesHelper = $salesHelper;
        if ($this->_salesHelper==NULL) {
            $this->_salesHelper = Mage::helper('sales');
        }
        $this->_taxCalculation = $taxCalculation;
        if ($this->_taxCalculation==NULL) {
            $this->_taxCalculation = Mage::getSingleton('tax/calculation');
        }
        $this->_taxConfig = $taxConfig;
        if ($this->_taxConfig==NULL) {
            $this->_taxConfig = Mage::getSingleton('tax/config');
        }
    }

    protected function _getHelper()
    {
        return $this->_moduleHelper;
    }

    protected function _getSalesHelper()
    {
        return $this->_salesHelper;
    }

    protected function _getTaxCalculation()
    {
        return $this->_taxCalculation;
    }

    protected function _getTaxConfig()
    {
        return $this->_taxConfig;
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if ($this->_getHelper()->collectQuoteRunParentFunction()) {
//            parent::collect($address);
        }

        if ($address->getQuote()->getId() == NULL) {
          return $this;
        }
        
        if ($address->getAddressType() != "shipping") {
          return $this;
        }

        if (!$address->getVaimoKlarnaFee()) {
          return $this;
        }

        if (!$this->_getHelper()->isMethodKlarna($address->getQuote()->getPayment()->getMethod())) {
            return $this;
        }

        $items = $address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        $quote = $address->getQuote();
        $custTaxClassId = $quote->getCustomerTaxClassId();
        $store = $quote->getStore();
        $taxCalculationModel = $this->_getTaxCalculation();
        $request = $taxCalculationModel->getRateRequest($address, $quote->getBillingAddress(), $custTaxClassId, $store);
        $klarnaFeeTaxClass = $this->_getHelper()->getTaxClass($store);

        $klarnaFeeTax      = 0;
        $klarnaFeeBaseTax  = 0;

        if ($klarnaFeeTaxClass) {
            if ($rate = $taxCalculationModel->getRate($request->setProductClassId($klarnaFeeTaxClass))) {

                $klarnaFeeTax = $taxCalculationModel->calcTaxAmount($address->getVaimoKlarnaFee(), $rate, false, true);
                $klarnaFeeBaseTax = $taxCalculationModel->calcTaxAmount($address->getVaimoKlarnaBaseFee(), $rate, false, true);
                
                if ($this->_getHelper()->collectQuoteUseExtraTaxInCheckout()) {
                    $address->setExtraTaxAmount($address->getExtraTaxAmount() + $klarnaFeeTax);
                    $address->setBaseExtraTaxAmount($address->getBaseExtraTaxAmount() + $klarnaFeeBaseTax);
                } else {
                    $address->setTaxAmount($address->getTaxAmount() + $klarnaFeeTax);
                    $address->setBaseTaxAmount($address->getBaseTaxAmount() + $klarnaFeeBaseTax);

                    $address->setGrandTotal($address->getGrandTotal() + $klarnaFeeTax);
                    $address->setBaseGrandTotal($address->getBaseGrandTotal() + $klarnaFeeBaseTax);
                }
            }
        }
        
        $address->setVaimoKlarnaFeeTax($klarnaFeeTax);
        $address->setVaimoKlarnaBaseFeeTax($klarnaFeeBaseTax);

        $quote->setVaimoKlarnaFeeTax($klarnaFeeTax);
        $quote->setVaimoKlarnaBaseFeeTax($klarnaFeeBaseTax);

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $store = $address->getQuote()->getStore();

        if ($this->_getTaxConfig()->displayCartSubtotalBoth($store) || $this->_getTaxConfig()->displayCartSubtotalInclTax($store)) {
            if ($address->getSubtotalInclTax() > 0) {
                $subtotalInclTax = $address->getSubtotalInclTax();
            } else {
                $subtotalInclTax = $address->getSubtotal()+$address->getTaxAmount()-$address->getShippingTaxAmount()-$address->getVaimoKlarnaFeeTax();
            }

            $address->addTotal(array(
                'code'      => 'subtotal',
                'title'     => $this->_getSalesHelper()->__('Subtotal'),
                'value'     => $subtotalInclTax,
                'value_incl_tax' => $subtotalInclTax,
                'value_excl_tax' => $address->getSubtotal(),
            ));
        }
        return $this;
    }

}
