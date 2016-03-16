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

class Vaimo_Klarna_Block_Page_Html_Logo extends Mage_Core_Block_Template
{
    protected function _construct()
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

    public function getAvailableMethods()
    {
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setQuote($this->getQuote());
        $res = $klarna->getAvailableMethods();
        return $res;
    }

    public function getAllMethodLogos($methods, $width, $useBoth = true)
    {
        $res = array();
        $invoice_found = false;
        $account_found = false;
        $checkout_found = false;
        $logoPosition = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_POSITION_FRONTEND;
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setQuote($this->getQuote());

        // Branding change, always show same logotype, except for Klarna Checkout
        foreach ($methods as $method) {
            if ($method==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT) {
                $checkout_found = true;
            } else {
                $invoice_found = true;
            }
        }
        if ($checkout_found) {
            $res = array($klarna->getKlarnaLogotype($width, $logoPosition, Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_CHECKOUT));
        } elseif ($invoice_found) {
            $res = array($klarna->getKlarnaLogotype($width, $logoPosition, Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BASIC));
        }

        /*
        foreach ($methods as $method) {
            $klarna->setMethod($method);
            if ($method==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE) {
                $logoType = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_INVOICE;
                $invoice_found = true;
            } elseif ($method==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT) {
                $logoType = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_ACCOUNT;
                $account_found = true;
            } elseif ($method==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT) {
                $logoType = Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_CHECKOUT;
                $checkout_found = true;
            } else {
                continue;
            }
            $res[] = $klarna->getKlarnaLogotype($width, $logoPosition, $logoType);
        }
        if ($checkout_found) {
            $res = array($klarna->getKlarnaLogotype($width, $logoPosition, Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_CHECKOUT));
        } elseif ($invoice_found && $account_found && $useBoth) {
            $res = array($klarna->getKlarnaLogotype($width, $logoPosition, Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_TYPE_BOTH));
        }
        */

        return $res;
    }
}