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

class Vaimo_Klarna_Block_Klarnacheckout_Discount extends Mage_Core_Block_Template
{
    public function _beforeToHtml()
    {
        parent::_beforeToHtml();
        try {
            if (class_exists('Enterprise_GiftCard_Model_Giftcard', true)) {
            /** @var Mage_Core_Block_Template $giftCardAccountBlock */
                $giftCardAccountBlock = $this->getLayout()->createBlock(
                    'enterprise_giftcardaccount/checkout_cart_giftcardaccount', 'checkout.cart.giftcardaccount'
                );
                if ($giftCardAccountBlock) {
                    $giftCardAccountBlock->setTemplate('vaimo/klarna/klarnacheckout/discount/giftcardaccount.phtml');
                    $this->setChild('giftcards', $giftCardAccountBlock);
                }
            }
        } catch (Exception $e) {
        }
        return $this;
    }
}
