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

class Vaimo_Klarna_Block_Klarnacheckout_Othermethod extends Mage_Core_Block_Template
{
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /*
     * Should perhaps be isButtonNameImage... But this is ok as well
     *
     */
    public function isButtonNameUrl($name)
    {
        try {
            $res = false;
            if ($name) {
                $arr = explode(':', $name);
                foreach ($arr as $a) {
                    if ($a=='http') {
                        $res = true;
                    }
                    break;
                }
            }
        } catch (Exception $e) {
        }

        return $res;
    }
    
    public function getOthermethodButtonName()
    {
        try {
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            $res = $klarna->getConfigData('label_other_button');
            if (!$res) {
                $res = Mage::helper('klarna')->__('Other Payment Methods');
            }
        } catch (Exception $e) {
            $res = Mage::helper('klarna')->__('Other Payment Methods');
        }

        return $res;
    }

    public function isButtonEnabled()
    {
        try {
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            $res = $klarna->getConfigData('enable_other_button');
        } catch (Exception $e) {
            $res = true;
        }

        return $res;
    }
    
    /**
     * Should not have been in here, but didn't want to create a new block for this one 
     * function. So I added it here instead, as it's being loaded at the same time...
     */
    public function triggerChangedJSInputId()
    {
        try {
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            if ($klarna->getConfigData('enable_trigger_changed_js')) {
                if ($klarna->getConfigData('enable_postcode_update')) {
                    $res = 'klarna-checkout-shipping-update-postcode';
                } else {
                    $res = 'klarna-checkout-shipping-update';
                }
            }
        } catch (Exception $e) {
            $res = false;
        }

        return $res;
    }
    
}