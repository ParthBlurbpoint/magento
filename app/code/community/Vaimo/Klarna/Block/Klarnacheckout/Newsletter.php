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

class Vaimo_Klarna_Block_Klarnacheckout_Newsletter extends Mage_Core_Block_Template
{
    /**
     * Checks if newsletter subscribe is enabled.
     * More as a backwards compatibility, getType does the same
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)Mage::getStoreConfig(Vaimo_Klarna_Helper_Data::KLARNA_CHECKOUT_ENABLE_NEWSLETTER) > 0;
    }

    /**
     * Gets the newsletter subscribe type; Subscribe or Don't subscribe
     *
     * @return int
     */
    public function getType()
    {
        return (int)Mage::getStoreConfig(Vaimo_Klarna_Helper_Data::KLARNA_CHECKOUT_ENABLE_NEWSLETTER);
    }

    public function getLabel()
    {
        switch ($this->getType()) {
            case Vaimo_Klarna_Helper_Data::KLARNA_CHECKOUT_NEWSLETTER_SUBSCRIBE:
                return $this->__('Subscribe to newsletter');
            case Vaimo_Klarna_Helper_Data::KLARNA_CHECKOUT_NEWSLETTER_DONT_SUBSCRIBE:
                return $this->__('Don\'t subscribe to newsletter');
        }

        return '';
    }

    public function isChecked()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();

        switch ($this->getType()) {
            case Vaimo_Klarna_Helper_Data::KLARNA_CHECKOUT_NEWSLETTER_SUBSCRIBE:
                return (bool)$quote->getKlarnaCheckoutNewsletter();
            case Vaimo_Klarna_Helper_Data::KLARNA_CHECKOUT_NEWSLETTER_DONT_SUBSCRIBE:
                return (bool)!$quote->getKlarnaCheckoutNewsletter();
        }

        return false;
    }
}