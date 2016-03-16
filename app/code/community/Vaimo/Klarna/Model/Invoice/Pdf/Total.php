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

class Vaimo_Klarna_Model_Invoice_Pdf_Total extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    protected $_moduleHelper = NULL;

    protected $_taxConfig = NULL;

    /**
     * constructor
     *
     * @param  $moduleHelper
     * @param  $taxHelper
     * @param  $taxConfig
     */
    public function __construct($moduleHelper = NULL, $taxHelper = NULL)
    {
        $this->_moduleHelper = $moduleHelper;
        if ($this->_moduleHelper==NULL) {
            $this->_moduleHelper = Mage::helper('klarna');
        }
        $this->_taxHelper = $taxHelper;
        if ($this->_taxHelper==NULL) {
            $this->_taxHelper = Mage::helper('tax');
        }
    }

    protected function _getHelper()
    {
        return $this->_moduleHelper;
    }

    protected function _getTaxHelper()
    {
        return $this->_taxHelper;
    }
    
    public function setTaxConfig($confObj)
    {
        $this->_taxConfig = $confObj;
    }

    protected function _getTaxConfig()
    {
        if ($this->_taxConfig==NULL) {
            $this->_taxConfig = Mage::getSingleton('klarna/tax_config');
        }
        return $this->_taxConfig;
    }

    public function getTotalsForDisplay()
    {
        $amount = $this->getAmount();
        $amount = $this->getOrder()->formatPriceTxt($amount);
        if ($this->getAmountPrefix()) {
            $amount = $this->getAmountPrefix() . $amount;
        }

        $label = $this->_getHelper()->__($this->getTitle()) . ':';
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        $info =  $this->getOrder()->getPayment()->getMethodInstance()->getInfoInstance();
        $amountInclTax = $this->getAmount();
        $amountInclTax += $info ?  $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX) : 0;
        $amountInclTax = $this->getOrder()->formatPriceTxt($amountInclTax);

        $store = $this->getOrder()->getStore();
        $config = $this->_getTaxConfig();
        if ($config->displaySalesKlarnaFeeInclTax($store->getId())) {
            $totals = array(array(
                'amount'    => $amountInclTax,
                'label'     => $label,
                'font_size' => $fontSize
            ));
        } elseif ($config->displaySalesKlarnaFeeBoth($store->getId())) {
            $totals = array(
                array(
                    'amount'    => $amount,
                    'label'     => $this->_getTaxHelper()->__('Invoice fee (Excl. Tax)') . ':',
                    'font_size' => $fontSize
                ),
                array(
                    'amount'    => $amountInclTax,
                    'label'     => $this->_getTaxHelper()->__('Invoice fee (Incl. Tax)') . ':',
                    'font_size' => $fontSize
                ),
            );
        } else {
            $totals = array(array(
                'amount'    => $amount,
                'label'     => $label,
                'font_size' => $fontSize
            ));
        }

        return $totals;
    }

    /**
     * Check if we can display total information in PDF
     *
     * @return bool
     */
    public function canDisplay()
    {
        $amount = $this->getAmount();
        return ($this->getDisplayZero() || ($amount != 0));
    }

    /**
     * Get Total amount from source
     *
     * @return float
     */
    public function getAmount()
    {
        $info =  $this->getOrder()->getPayment()->getMethodInstance()->getInfoInstance();
        if (!$info) {
            return 0;
        }

        $fee =  $info->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE);

        if (!$fee){
            return 0;
        }

        return $fee;
    }
}
