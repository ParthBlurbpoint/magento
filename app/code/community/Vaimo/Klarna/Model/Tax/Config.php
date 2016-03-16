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

class Vaimo_Klarna_Model_Tax_Config extends Mage_Tax_Model_Config
{
    // tax classes
    const CONFIG_XML_PATH_KLARNA_FEE_TAX_CLASS = 'tax/classes/vaimo_klarna_fee_tax_class';

    // tax calculation
    const CONFIG_XML_PATH_KLARNA_FEE_INCLUDES_TAX = 'tax/calculation/vaimo_klarna_fee_includes_tax';

    /**
     * Prices display settings
     */
    const CONFIG_XML_PATH_DISPLAY_KLARNA_FEE      = 'tax/display/vaimo_klarna_fee'; // Not used?

    /**
     * Shopping cart display settings
     */
    const XML_PATH_DISPLAY_CART_KLARNA_FEE    = 'tax/cart_display/vaimo_klarna_fee';

    /**
     * Shopping cart display settings
     */
    const XML_PATH_DISPLAY_SALES_KLARNA_FEE    = 'tax/sales_display/vaimo_klarna_fee';

    /**
     * @var $_klarnaFeePriceIncludeTax bool
     */
    protected $_klarnaFeePriceIncludeTax = null;
    
    /*
     * Will call normal Mage::getStoreConfig
     * It's in it's own function, so it can be mocked in tests
     * 
     * @param string $field
     * @param string $storeId
     *
     * @return string
     */
    protected function _getConfigDataCall($field, $storeId)
    {
        return Mage::getStoreConfig($field, $storeId);
    }
    
    /**
     * Get tax class id specified for klarna fee tax estimation
     *
     * @param   store $store
     * @return  int
     */
    public function getKlarnaFeeTaxClass($store=null)
    {
        return (int)$this->_getConfigDataCall(self::CONFIG_XML_PATH_KLARNA_FEE_TAX_CLASS, $store);
    }

    /**
     * Get klarna fee methods prices display type
     *
     * @param   store $store
     * @return  int
     */
    public function getKlarnaFeePriceDisplayType($store = null)
    {
        return (int)$this->_getConfigDataCall(self::CONFIG_XML_PATH_DISPLAY_KLARNA_FEE, $store);
    }

    /**
     * Check if shiping prices include tax
     *
     * @param   store $store
     * @return  bool
     */
    public function klarnaFeePriceIncludesTax($store = null)
    {
        if ($this->_klarnaFeePriceIncludeTax === null) {
            $this->_klarnaFeePriceIncludeTax = (bool)$this->_getConfigDataCall(
                self::CONFIG_XML_PATH_KLARNA_FEE_INCLUDES_TAX,
                $store
            );
        }
        return $this->_klarnaFeePriceIncludeTax;
    }

    public function displayCartKlarnaFeeInclTax($store = null)
    {
        return $this->_getConfigDataCall(self::XML_PATH_DISPLAY_CART_KLARNA_FEE, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displayCartKlarnaFeeExclTax($store = null)
    {
        return $this->_getConfigDataCall(self::XML_PATH_DISPLAY_CART_KLARNA_FEE, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displayCartKlarnaFeeBoth($store = null)
    {
        return $this->_getConfigDataCall(self::XML_PATH_DISPLAY_CART_KLARNA_FEE, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displaySalesKlarnaFeeInclTax($store = null)
    {
        return $this->_getConfigDataCall(self::XML_PATH_DISPLAY_SALES_KLARNA_FEE, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displaySalesKlarnaFeeExclTax($store = null)
    {
        return $this->_getConfigDataCall(self::XML_PATH_DISPLAY_SALES_KLARNA_FEE, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displaySalesKlarnaFeeBoth($store = null)
    {
        return $this->_getConfigDataCall(self::XML_PATH_DISPLAY_SALES_KLARNA_FEE, $store) == self::DISPLAY_TYPE_BOTH;
    }
}

