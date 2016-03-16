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

class Vaimo_Klarna_Block_Klarnacheckout_Autofill extends Mage_Core_Block_Template
{
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getDisplayAutofill()
    {
        try {
            $res = true;
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            $res = $klarna->shouldDisplayAutofillWarning();
        } catch (Exception $e) {
            $res = false;
        }
        return $res;
    }
    
    public function getTermsLink()
    {
        try {
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            $merchantId = $klarna->getKlarnaSetup()->getMerchantId();
            $lnk = 'https://cdn.klarna.com/1.0/shared/content/legal/terms/' . $merchantId .'/de_de/checkout';
            $res = '<a href="' . $lnk . '">' . $this->helper('klarna')->__('Nutzungsbedingungen') . '</a>';
        } catch (Exception $e) {
            $res = $this->helper('klarna')->__('Nutzungsbedingungen');
        }
        return $res;
    }
    
}