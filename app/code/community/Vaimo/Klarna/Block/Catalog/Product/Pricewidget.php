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

class Vaimo_Klarna_Block_Catalog_Product_Pricewidget extends Mage_Core_Block_Template
{
    
    protected $_product = null;

    public function __construct()
    {
        return parent::_construct();
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = Mage::registry('product');
        }
        return $this->_product;
    }

    /*
     * Hardcoded Klarna Account method, if it fails, it will automatically try with invoice method
     *
     */
    public function getWidgetParameters()
    {
        $activef = true;
        $klarnaSetup = NULL;
        $klarnacheckout = Mage::getModel('klarna/klarnacheckout');
        $klarnacheckout->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        if ($klarnacheckout->getKlarnaCheckoutEnabled()) {
            if ($klarnacheckout->getConfigData('disable_product_widget')) {
                $activef = false;
            } else {
                $klarnaSetup = $klarnacheckout->getKlarnaSetup();
            }
        } else {
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT);
            if (!$this->getConfigData('active')) {
                $activef = false;
            } else {
                if ($klarna->getConfigData('disable_product_widget')) {
                    $activef = false;
                } else {
                    $klarnaSetup = $klarna->getKlarnaSetup();
                }
            }
        }
        if ($activef) {
            if ($klarnaSetup) {
                if ($klarnaSetup->getCountryCode() != 'NL' && $klarnaSetup->getCountryCode() != 'AT') {
                    return $klarnaSetup;
                }
            }
        }
        return NULL;
    }
    
    public function getKlarnaInvoiceFeeInfo()
    {
        return Mage::helper('klarna')->getVaimoKlarnaFeeInclVat($this->getQuote(), false);
    }

    public function getProductPriceInclVat($store = NULL)
    {
        // @TODO This only returns Incl TAX if settings are set to Display prices including TAX... Needs to be Incl VAT, always
//        return Mage::helper('tax')->getPrice($this->getProduct(), $this->getProduct()->getFinalPrice(), true);
        $product = $this->getProduct();
        $res = $product->getFinalPrice();
        $inclTax = Mage::getSingleton('tax/config')->priceIncludesTax($store);
        if (!$inclTax) {
            $taxClassId = $product->getTaxClassId();
            $request = Mage::getSingleton('tax/calculation')
                ->getRateRequest(false, false, false, $store);
            $includingPercent = Mage::getSingleton('tax/calculation')
                ->getRate($request->setProductClassId($taxClassId));
            $res = Mage::app()->getStore()->roundPrice($res * (1 + ($includingPercent / 100)));
        }
        return $res;
    }

}