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

class Vaimo_Klarna_Block_Klarnacheckout_Reward extends Mage_Core_Block_Template
{
    protected $_rewardBlock = null;

    /**
     * @return bool|Enterprise_Reward_Block_Checkout_Payment_Additional
     */
    protected function _getRewardBlock()
    {
        if ($this->_rewardBlock == null) {
            if (Mage::helper('core')->isModuleEnabled('Enterprise_Reward')) {
                $this->_rewardBlock = $this->getLayout()->getBlockSingleton('enterprise_reward/checkout_payment_additional');
            } else {
                $this->_rewardBlock = false;
            }
        }

        return $this->_rewardBlock;
    }

    public function getCanUseRewardPoints()
    {
        if ($block = $this->_getRewardBlock()) {
            return $block->getCanUseRewardPoints();
        }

        return false;
    }

    public function useRewardPoints()
    {
        if ($block = $this->_getRewardBlock()) {
            return $block->useRewardPoints();
        }

        return false;
    }

    public function getPointsBalance()
    {
        if ($block = $this->_getRewardBlock()) {
            return $block->getReward()->getPointsBalance();
        }

        return 0;
    }

    public function getCurrencyAmount()
    {
        if ($block = $this->_getRewardBlock()) {
            return $block->getReward()->getCurrencyAmount();
        }

        return 0;
    }
}