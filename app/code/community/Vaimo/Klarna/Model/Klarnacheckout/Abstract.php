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

abstract class Vaimo_Klarna_Model_Klarnacheckout_Abstract extends Vaimo_Klarna_Model_Transport_Abstract
{
    protected $_coreHttpHelper = NULL;
    protected $_coreUrlHelper = NULL;
    protected $_customerHelper = NULL;

    public function __construct($setStoreInfo = true, $moduleHelper = NULL, $coreHttpHelper = NULL, $coreUrlHelper = NULL, $customerHelper = NULL)
    {
        parent::__construct($setStoreInfo, $moduleHelper);

        $this->_coreHttpHelper = $coreHttpHelper;
        if ($this->_coreHttpHelper==NULL) {
            $this->_coreHttpHelper = Mage::helper('core/http');
        }
        $this->_coreUrlHelper = $coreUrlHelper;
        if ($this->_coreUrlHelper==NULL) {
            $this->_coreUrlHelper = Mage::helper('core/url');
        }
        $this->_customerHelper = $customerHelper;
        if ($this->_customerHelper==NULL) {
            $this->_customerHelper = Mage::helper('customer');
        }
    }

    protected function _getCoreHttpHelper()
    {
        return $this->_coreHttpHelper;
    }

    protected function _getCoreUrlHelper()
    {
        return $this->_coreUrlHelper;
    }

    protected function _getCustomerHelper()
    {
        return $this->_customerHelper;
    }

    protected function _loadQuoteByKey($id, $key)
    {
        return Mage::getModel('sales/quote')->load($id, $key);
    }

    protected function _loadOrderByKey($id, $key = 'quote_id')
    {
        return Mage::getModel('sales/order')->load($id, $key);
    }

    protected function _findAlreadyCreatedOrder($id)
    {
        return $this->_loadOrderByKey($id)->getId();
    }

    protected function _loadCustomer($id)
    {
        return Mage::getModel('customer/customer')->load($id);
    }

    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    protected function _loadCustomerByEmail($email, $store)
    {
        return Mage::getModel('customer/customer')->setStore($store)->loadByEmail($email);
    }

    protected function _loadCustomerAddress($id)
    {
        return Mage::getModel('customer/address')->load($id);
    }

    protected function _getServiceQuote($quote)
    {
        return Mage::getModel('sales/service_quote', $quote);
    }

    protected function _addToSubscription($email)
    {
        Mage::getModel('newsletter/subscriber')->subscribe($email);
    }

    protected function _prepareNewCustomerQuote(Mage_Sales_Model_Quote $quote)
    {
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

        /** @var $customer Mage_Customer_Model_Customer */
        $customer = $quote->getCustomer();
        $customerBilling = $billing->exportCustomerAddress();
        $customer->addAddress($customerBilling);
        $billing->setCustomerAddress($customerBilling);
        $customerBilling->setIsDefaultBilling(true);

        if ($shipping && !$shipping->getSameAsBilling()) {
            $customerShipping = $shipping->exportCustomerAddress();
            $customer->addAddress($customerShipping);
            $shipping->setCustomerAddress($customerShipping);
            $customerShipping->setIsDefaultShipping(true);
        } else {
            $customerBilling->setIsDefaultShipping(true);
        }

//        Mage::helper('core')->copyFieldset('checkout_onepage_quote', 'to_customer', $quote, $customer);

        $customer->setFirstname($customerBilling->getFirstname());
        $customer->setLastname($customerBilling->getLastname());
        $customer->setEmail($customerBilling->getEmail());

        $password = $customer->generatePassword();
        $customer->setPassword($password);
        $customer->setConfirmation($password);

//        $customer->setPassword($customer->decryptPassword($quote->getPasswordHash()));
//        $customer->setPasswordHash($customer->hashPassword($customer->getPassword()));
        $quote->setCustomer($customer)->setCustomerId(true);
    }

    protected function _prepareCustomerQuote(Mage_Sales_Model_Quote $quote)
    {
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();
        $customer = $this->_loadCustomer($quote->getCustomerId());

        if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
            $customerBilling = $billing->exportCustomerAddress();
            $customer->addAddress($customerBilling);
            $billing->setCustomerAddress($customerBilling);
        }

        if ($shipping && !$shipping->getSameAsBilling() &&
            (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())) {
            $customerShipping = $shipping->exportCustomerAddress();
            $customer->addAddress($customerShipping);
            $shipping->setCustomerAddress($customerShipping);
        }

        if (isset($customerBilling) && !$customer->getDefaultBilling()) {
            $customerBilling->setIsDefaultBilling(true);
        }

        if ($shipping && isset($customerShipping) && !$customer->getDefaultShipping()) {
            $customerShipping->setIsDefaultShipping(true);
        } else if (isset($customerBilling) && !$customer->getDefaultShipping()) {
            $customerBilling->setIsDefaultShipping(true);
        }

        $quote->setCustomer($customer);
    }

    protected function _involveNewCustomer(Mage_Sales_Model_Quote $quote)
    {
        /** @var $customer Mage_Customer_Model_Customer */
        $customer = $quote->getCustomer();

        if ($customer->isConfirmationRequired()) {
            $customer->sendNewAccountEmail('confirmation', '', $quote->getStoreId());
        } else {
            $customer->sendNewAccountEmail('registered', '', $quote->getStoreId());
        }

        return $this;
    }

}
