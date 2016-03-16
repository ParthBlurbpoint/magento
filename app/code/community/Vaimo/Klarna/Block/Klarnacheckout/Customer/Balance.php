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

class Vaimo_Klarna_Block_Klarnacheckout_Customer_Balance extends Mage_Core_Block_Template
{
    protected $_balanceBlock;

    /**
     * @return bool|Enterprise_CustomerBalance_Block_Checkout_Onepage_Payment_Additional
     */
    protected function _getBalanceBlock()
    {
        if ($this->_balanceBlock == null) {
            if (Mage::helper('core')->isModuleEnabled('Enterprise_CustomerBalance')) {
                $this->_balanceBlock = $this->getLayout()->getBlockSingleton('enterprise_customerbalance/checkout_onepage_payment_additional');
            } else {
                $this->_balanceBlock = false;
            }
        }

        return $this->_balanceBlock;
    }

    public function isDisplayContainer()
    {
        if ($block = $this->_getBalanceBlock()) {
            return $block->isDisplayContainer();
        }

        return false;
    }

    public function isCustomerBalanceUsed()
    {
        if ($block = $this->_getBalanceBlock()) {
            return $block->isCustomerBalanceUsed();
        }

        return false;
    }

    public function getBalance()
    {
        if ($block = $this->_getBalanceBlock()) {
            return $block->getBalance();
        }

        return 0;
    }
}