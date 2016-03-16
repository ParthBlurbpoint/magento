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

class Vaimo_Klarna_Block_Klarnacheckout_Klarnacheckout extends Mage_Core_Block_Template
{
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getKlarnaHtml()
    {
        try {
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($this->getQuote(), Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            $html = $klarna->getKlarnaOrderHtml(null, true, true);
        } catch (Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $html = $e->getMessage();
            if (!$html) {
                $extraInfo = '';
                if ($e->getInternalMessage()) {
                    if (stristr($e->getInternalMessage(), 'Bad format')) {
                        $extraInfo = ' (' . $e->getInternalMessage() . ')';
                    }
                }
                $html = Mage::helper('klarna')->__(
                    'Klarna Checkout is not responding properly. Please try again in a while or choose another payment method.' . 
                    $extraInfo
                );
            }
        }

        return $html;
    }
}