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

abstract class Vaimo_Klarna_Model_Klarna_Abstract extends Vaimo_Klarna_Model_Transport_Abstract
{
    /*
     * Temporary storage of fields that eventually will be added to payments additional_info
     *
     */
    protected $_additionalInfo = NULL;

    /*
     * Contains the item lines that will be sent to Klarna
     * goods list is the actual products
     * extras contain things like discounts, shipping costs etc
     */
    protected $_goods_list = array();
    protected $_extras = array();

    /*
     * A manipulated array of html post variables
     * Used only in this file
     *
     */
    protected $_postValues = array();

    protected $_entGWHelper = NULL;
    protected $_salesHelper = NULL;
    protected $_taxCalculation = NULL;

    public function __construct($setStoreInfo = true, $moduleHelper = NULL, $entGWHelper = NULL, $salesHelper = NULL, $taxCalculation = NULL)
    {
        parent::__construct($setStoreInfo, $moduleHelper);

        $this->_entGWHelper = $entGWHelper;
        if ($this->_entGWHelper==NULL) {
            $this->_entGWHelper = $moduleHelper; // entGWHelper only used as a transaltor, if different GW is used than Magento, it will just not translate it
            try {
                if (class_exists("Enterprise_GiftWrapping_Helper_Data", true)) {
                    $this->_entGWHelper = Mage::helper('enterprise_giftwrapping');
                }
            } catch (Exception $e) {
            }
        }
        $this->_salesHelper = $salesHelper;
        if ($this->_salesHelper==NULL) {
            $this->_salesHelper = Mage::helper('sales');
        }
        $this->_taxCalculation = $taxCalculation;
        if ($this->_taxCalculation==NULL) {
            $this->_taxCalculation = Mage::getSingleton('tax/calculation');
        }
    }
    
    protected function _getEntGWHelper()
    {
        return $this->_entGWHelper;
    }

    protected function _getSalesHelper()
    {
        return $this->_salesHelper;
    }

    protected function _getTaxCalculation()
    {
        return $this->_taxCalculation;
    }

    protected function _setAdditionalInformation($data, $value = NULL)
    {
        if (!$data) return;
        if ($value && !is_array($data)) {
            if ($this->_additionalInfo) {
                $this->_additionalInfo->setData($data, $value);
            } else {
                $this->_additionalInfo = new Varien_Object(array($data => $value));
            }
        } else {
            if ($this->_additionalInfo) {
                $this->_additionalInfo->setData($data);
            } else {
                $this->_additionalInfo = new Varien_Object($data);
            }
        }
    }
    
    protected function _unsetAdditionalInformation($field)
    {
        if ($this->_additionalInfo) {
            $this->_additionalInfo->unsetData($field);
        }
    }
    
    protected function _getAdditionalInformation($field = '')
    {
        if ($this->_additionalInfo) {
            return $this->_additionalInfo->getData($field);
        } else {
            return NULL;
        }
    }
        
    public function getGoodsList()
    {
        return $this->_goods_list;
    }

    public function getExtras()
    {
        return $this->_extras;
    }

    /**
     * Get the Personal Number associated to this purchase
     *
     * @return string
     */
    public function getPNO()
    {
        if ($this->needDateOfBirth()) {
            if ((array_key_exists("dob_day", $this->_getAdditionalInformation()))
                && (array_key_exists("dob_month", $this->_getAdditionalInformation()))
                && (array_key_exists("dob_year", $this->_getAdditionalInformation()))
            ) {
                return $this->_getAdditionalInformation("dob_day")
                    . $this->_getAdditionalInformation("dob_month")
                    . $this->_getAdditionalInformation("dob_year");
            }
        } elseif (array_key_exists("pno", $this->_getAdditionalInformation())
            && strlen($this->_getAdditionalInformation("pno")) > 0
        ) {
            return $this->_getAdditionalInformation("pno");
        }
        return "";
    }

    /**
     * Get the gender associated to this purchase
     *
     * @return null|int
     */
    public function getGender()
    {
        if ($this->needGender() && array_key_exists("gender", $this->_getAdditionalInformation())) {
            return $this->_getAdditionalInformation("gender");
        }
        return null;
    }

    /**
     * Get the payment plan associated to this purchase
     *
     * @return int
     */
    public function getPaymentPlan()
    {
        if ((array_key_exists(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN, $this->_getAdditionalInformation()))
            && ($this->getOrder()->getPayment()->getMethod() !== Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE)
        ) {
            return (int)$this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN);
        }
        return -1;
    }

    protected function _loadCustomerById($id)
    {
        return Mage::getModel('customer/customer')->load($id);
    }
    
    protected function _loadProductById($id)
    {
        return Mage::getModel('catalog/product')->load($id);
    }
    
    /**
     * Returns the tax rate
     *
     * @param int $taxClass The tax class to get the rate for
     *
     * @return double The tax rate
     */
    protected function _getTaxRate($taxClass)
    {
        // Load the customer so we can retrevice the correct tax class id
        $customer = $this->_loadCustomerById($this->getOrder()->getCustomerId());
        $calculation = $this->_getTaxCalculation();
        $request = $calculation->getRateRequest(
            $this->getShippingAddress(),
            $this->getBillingAddress(),
            $customer->getTaxClassId(),
            $this->getOrder()->getStore()
        );
        return $calculation->getRate($request->setProductClassId($taxClass));
    }
    
    protected function _checkBundles(&$item, $product)
    {
        if ($item->getProductType()==Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            if ($product->getPriceType()==Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                $item->setPriceInclTax(0);
            }
        }
    }

    /**
     * Create the goods list for Reservations
     *
     * @param array $items The items to add to the goods list
     *
     * @return void
     */
    protected function _createGoodsList($items = null)
    {
        if ($items === null) {
            $items = $this->getOrder()->getAllVisibleItems();
        }

        $taxRate = NULL;

        foreach ($items as $item) {

            if ($this->_getHelper()->shouldItemBeIncluded($item)==false) continue;

            //For handling the different activation
            $qty = $item->getQtyOrdered(); //Standard
            if (!isset($qty)) {
                $qty = $item->getQty(); //Advanced
            }
            $id = $item->getProductId();
            $product = $this->_loadProductById($id);

            $this->_checkBundles($item, $product);

            $taxRate = $this->_getTaxRate($product->getTaxClassId());

            $this->_goods_list[] =
                array(
                    "qty" => $qty,
                    "sku" => $item->getSku(),
                    "name" => $item->getName(),
                    "price" => $item->getPriceInclTax(),
                    "total_amount" => $item->getRowTotalInclTax(),
                    "total_tax_amount" => $item->getTaxAmount(),
                    "tax" => $taxRate,
                    "discount" => 0,
                    "flags" => Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_NORMAL,
                );
        }

        //Only add discounts and etc for unactivated orders
        if ($this->getOrder()->hasInvoices() <= 1) {
            $this->_addExtraFees($taxRate);
        }
    }

    /**
     * Create the goods list for Refunds
     *
     * @param array $items The items to add to the goods list
     *
     * @return void
     */
    protected function _createRefundGoodsList($items = null)
    {
        if ($items === null) {
            $this->_getHelper()->logKlarnaApi('_createRefundGoodsList got no items. Order: ' . $this->getOrder()->getIncrementId());
        }

        $taxRate = NULL;

        if ($items) {
            foreach ($items as $item) {
                $qty = $item->getQty();
                $id = $item->getProductId();
                $product = $this->_loadProductById($id);

                $taxRate = $this->_getTaxRate($product->getTaxClassId());

                $this->_goods_list[] =
                    array(
                        "qty" => $qty,
                        "sku" => $item->getSku(),
                        "name" => $item->getName(),
                        "price" => $item->getPriceInclTax(),
                        "total_amount" => $item->getRowTotalInclTax(),
                        "total_tax_amount" => $item->getTaxAmount(),
                        "tax" => $taxRate,
                        "discount" => 0,
                        "flags" => Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_NORMAL,
                    );
            }
        }
        // Add same extra fees as original order, then remove the ones that should not be refunded
        $this->_addExtraFees($taxRate);
        foreach ($this->getExtras() as $id => $extra) {
            if (isset($extra['flags'])) {
                switch ($extra['flags']) {
                    case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_HANDLING_FEE:
                        if ($this->getCreditmemo()->getVaimoKlarnaFeeRefund()>0) { // If not full invoice fee refunded, it will use refund amount instead
                            $this->_extras[$id]['original_price'] = $this->_extras[$id]['price'];
                            $this->_extras[$id]['price'] = $this->getCreditmemo()->getVaimoKlarnaFeeRefund();
                        } else {
                            unset($this->_extras[$id]);
                        }
                        break;
                    case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_SHIPPING_FEE;
                        if ($this->getCreditmemo()->getShippingAmount()>0) { // If not full shipping refunded, it will use refund amount instead
                            $this->_extras[$id]['original_price'] = $this->_extras[$id]['price'];
                            $this->_extras[$id]['price'] = $this->getCreditmemo()->getShippingAmount();
                        } else {
                            unset($this->_extras[$id]);
                        }
                        break;
                    case Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_NORMAL:
                        unset($this->_extras[$id]);
                        break;
                    default:
                        unset($this->_extras[$id]);
                        break;
                }

            } else {
                unset($this->_extras[$id]);
            }
        }
    }

    /**
     * Returns the total handling fee included in extras
     *
     * @return decimal
     */
    protected function _feeAmountIncluded()
    {
        $res = 0;
        foreach ($this->getExtras() as $extra) {
            if (isset($extra['flags'])) {
                if ($extra['flags']==Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_HANDLING_FEE) {
                    $res = $res + $extra['price'];
                }
            }
        }
        return $res;
    }

    /**
     * Add all possible fees and discounts.
     *
     * @return void
     */
    protected function _addExtraFees($taxRate)
    {
        $this->_addInvoiceFee();

        $this->_addShippingFee();

        $this->_addGiftCard();

        $this->_addCustomerBalance();

        $this->_addRewardCurrency();

        $this->_addGiftWrapPrice();

        $this->_addGiftWrapItemPrice();

        $this->_addGwPrintedCardPrice();

        $this->_addDiscount($taxRate);

    }

    /**
     * Add the Gift Wrap Order price to the goods list
     *
     * @return void
     */
    protected function _addGiftWrapPrice()
    {
        if ($this->getOrder()->getGwPrice() <= 0) {
            return;
        }

        $price = $this->getOrder()->getGwPrice();
        $tax = $this->getOrder()->getGwTaxAmount();

        $sku = $this->_getHelper()->__('gw_order');

        $name = $this->_getEntGWHelper()->__("Gift Wrapping for Order");
        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $name,
            "price" => $price + $tax,
            "total_amount" => $price + $tax,
            "total_tax_amount" => $tax,
        );
    }

    /**
     * Add the Gift Wrap Item price to the goods list
     *
     * @return void
     */
    protected function _addGiftWrapItemPrice()
    {
        if ($this->getOrder()->getGwItemsPrice() <= 0) {
            return;
        }

        $price = $this->getOrder()->getGwItemsPrice();
        $tax = $this->getOrder()->getGwItemsTaxAmount();

        $name = $this->_getEntGWHelper()->__("Gift Wrapping for Items");

        $sku = $this->_getHelper()->__('gw_items');

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $name,
            "price" => $price + $tax,
            "total_amount" => $price + $tax,
            "total_tax_amount" => $tax,
        );
    }

    /**
     * Add the Gift Wrap Printed Card to the goods list
     *
     * @return void
     */
    protected function _addGwPrintedCardPrice()
    {
        if ($this->getOrder()->getGwPrintedCardPrice() <= 0) {
            return;
        }

        $price = $this->getOrder()->getGwPrintedCardPrice();
        $tax = $this->getOrder()->getGwPrintedCardTaxAmount();

        $name = $this->_getEntGWHelper()->__("Printed Card");

        $sku = $this->_getHelper()->__('gw_printed_card');

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $name,
            "price" => $price + $tax,
            "total_amount" => $price + $tax,
            "total_tax_amount" => $tax,
        );
    }

    /**
     * Add the gift card amount to the goods list
     *
     * @return void
     */
    protected function _addGiftCard()
    {
        if ($this->getOrder()->getGiftCardsAmount() <= 0) {
            return;
        }

        $sku = $this->_getHelper()->__('gift_card');

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $this->_getHelper()->__('Gift Card'),
            "price" => ($this->getOrder()->getGiftCardsAmount() * -1),
            "total_amount" => ($this->getOrder()->getGiftCardsAmount() * -1),
            "total_tax_amount" => 0,
        );
    }

    /**
     * Add the customer balance to the goods list
     *
     * @return void
     */
    protected function _addCustomerBalance()
    {
        if ($this->getOrder()->getCustomerBalanceAmount() <= 0) {
            return;
        }

        $sku = $this->_getHelper()->__('customer_balance');

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $this->_getHelper()->__("Customer Balance"),
            "price" => ($this->getOrder()->getCustomerBalanceAmount() * -1),
            "total_amount" => ($this->getOrder()->getCustomerBalanceAmount() * -1),
            "total_tax_amount" => 0,
        );
    }

    /**
     * Add a reward currency amount to the goods list
     *
     * @return void
     */
    protected function _addRewardCurrency()
    {
        if ($this->getOrder()->getRewardCurrencyAmount() <= 0) {
            return;
        }

        $sku = $this->_getHelper()->__('reward');

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $this->_getHelper()->__('Reward'),
            "price" => ($this->getOrder()->getRewardCurrencyAmount() * -1),
            "total_amount" => ($this->getOrder()->getRewardCurrencyAmount() * -1),
            "total_tax_amount" => 0,
        );
    }

    /**
     * Add the invoice fee to the goods list
     *
     * @return void
     */
    protected function _addInvoiceFee()
    {
        if ($this->getOrder()->getPayment()->getMethod() != Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE) {
            return;
        }
        if ($this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE)==0) {
            return;
        }

        $sku = $this->_getHelper()->__('invoice_fee');

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $this->_getHelper()->getKlarnaFeeLabel($this->getOrder()->getStore()),
            "price" => $this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE) + $this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX),
            "tax" => $this->_getHelper()->getVaimoKlarnaFeeVatRate($this->getOrder()),
            "flags" => Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_HANDLING_FEE,
            "total_amount" => $this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE) + $this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX),
            "total_tax_amount" => $this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX),
        );
    }

    /**
     * Add the shipment fee to the goods list
     *
     * @return void
     */
    protected function _addShippingFee()
    {
        if ($this->getOrder()->getShippingInclTax() <= 0) {
            return;
        }
        $taxClass = $this->_getConfigDataCall('tax/classes/shipping_tax_class', $this->getOrder()->getStoreId());

        $sku = $this->getOrder()->getShippingMethod();

        if (!$sku || $this->getMethod()==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT) {
            $sku = $this->_getHelper()->__('shipping');
        }

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $this->getOrder()->getShippingDescription(),
            "price" => $this->getOrder()->getShippingInclTax(),
            "tax" => $this->_getTaxRate($taxClass),
            "flags" => Vaimo_Klarna_Helper_Data::KLARNA_FLAG_ITEM_SHIPPING_FEE,
            "total_amount" => $this->getOrder()->getShippingInclTax(),
            "total_tax_amount" => $this->getOrder()->getShippingAmount(),
        );
    }

    /**
     * Add the discount to the goods list
     *
     * @param $taxRate is the VAT rate of the LAST product in cart... Not perfect of course, but better than no VAT. It must be an official rate, can't be median
     * @return void
     */
    protected function _addDiscount($taxRate)
    {
        if ($this->getOrder()->getDiscountAmount() >= 0) {
            return;
        }
        // Instead of calculating discount from order etc, we now simply use the amounts we are adding to goods list
        
        //calculate grandtotal and subtotal with all possible fees and extra costs
        $subtotal = $this->getOrder()->getSubtotalInclTax();
		$grandtotal = $this->getOrder()->getGrandTotal();
		
		//if fee is added, add to subtotal
		//if discount is added, like cards and such, add to grand total
		foreach ($this->_extras as $extra) {
			if ($extra['price'] > 0) {
				$subtotal+= $extra['price'];
			} else if ($extra['price'] < 0) {
				$grandtotal+= $extra['price'];
			}
		}
        
        //now check what the actual discount incl vat is
        $amount = $grandtotal - $subtotal; //grand total is always incl tax

        $sku = $this->getOrder()->getDiscountDescription();

        if (!$sku || $this->getMethod()==Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT) {
            $sku = $this->_getHelper()->__('discount');
        }

        $this->_extras[] = array(
            "qty" => 1,
            "sku" => $sku,
            "name" => $this->_getSalesHelper()->__('Discount (%s)', $sku),
            "price" => $amount,
            "tax" => $taxRate,
            "total_amount" => $amount,
            "total_tax_amount" => 0,
        );
    }
    
    protected function _getStores()
    {
        return Mage::app()->getStores();
    }

    /**
     * Get the store information to use for fetching new PClasses
     *
     * @param storeIds a comma separated list of stores as a filter which ones to include
     *
     * @return array of store ids where Klarna is active
     */
    protected function _getKlarnaActiveStores()
    {
        $result = array();
        foreach ($this->_getStores() as $store) {
            if (!$store->getConfig('payment/' . Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT . '/active')
                && !$store->getConfig('payment/' . Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL . '/active')
            ) {
                continue;
            }
            $result[] = $store->getId();
        }
        return $result;
    }

    /*
     * We do not want to save things in the database that doesn't need to be there
     * Personal ID is also removed from database
     *
     * @return void
     */
    protected function _cleanAdditionalInfo()
    {
        if (array_key_exists("pno", $this->_getAdditionalInformation())) {
            $pno = $this->_getAdditionalInformation("pno");
            if (strlen($pno) > 0) {
                $this->getPayment()->unsAdditionalInformation("pno");
            }
            $this->_getHelper()->dispatchReserveInfo($this->getOrder(), $pno);
        }
        if (array_key_exists("consent", $this->_getAdditionalInformation())) {
            if ($this->_getAdditionalInformation("consent")=="NO") {
                $this->getPayment()->unsAdditionalInformation("consent");
            }
        }
        if (array_key_exists("gender", $this->_getAdditionalInformation())) {
            if ($this->_getAdditionalInformation("gender")=="-1") {
                $this->getPayment()->unsAdditionalInformation("gender");
            }
        }
    }

    /**
     * Update a Magento address with an array containing address information
     *
     * @param array $addr  The addr to use
     * @param string $updateWhichAddress address type to update
     *
     * @return void
     */
    protected function _updateWithSelectedAddress($addr, $updateWhichAddress = Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
    {
        $selAddr = new Varien_Object($addr);
        if ($updateWhichAddress==Mage_Sales_Model_Quote_Address::TYPE_SHIPPING) {
            $address = $this->getShippingAddress();
        } else {
            $address = $this->getBillingAddress();
        }
        $street = $selAddr->getStreet();

        if ($selAddr->getHouseNumber()) {
            $street .= " " . $selAddr->getHouseNumber();
        }
        if ($selAddr->getHouseExtension()) {
            $street .= " " . $selAddr->getHouseExtension();
        }

        // If it's a company purchase set company name.
        $company = $selAddr->getCompanyName();
        if ($company!="" && $this->isCompanyAllowed()) {
            $address->setCompany($company);
        } else {
            $address->setFirstname($selAddr->getFirstName())
                ->setLastname($selAddr->getLastName())
                ->setCompany('');
        }

        $address->setPostcode($selAddr->getZip())
            ->setStreet(trim($street))
            ->setCity($selAddr->getCity())
            ->save();
    }

    /**
     * Update a Magento address with another Magento address and save it.
     *
     * @return void
     */
    public function updateBillingAddress()
    {
        $this->getBillingAddress()->setFirstname($this->getShippingAddress()->getFirstname())
            ->setLastname($this->getShippingAddress()->getLastname())
            ->setPostcode($this->getShippingAddress()->getPostcode())
            ->setStreet($this->getShippingAddress()->getStreet())
            ->setCity($this->getShippingAddress()->getCity())
            ->setTelephone($this->getShippingAddress()->getTelephone())
            ->setCountry($this->getShippingAddress()->getCountry())
            ->setCompany($this->getShippingAddress()->getCompany())
            ->save();
    }

    /**
     * Update a Magento address with another Magento address and save it.
     *
     * @return void
     */
    public function updateShippingAddress()
    {
        $this->getShippingAddress()->setFirstname($this->getBillingAddress()->getFirstname())
            ->setLastname($this->getBillingAddress()->getLastname())
            ->setPostcode($this->getBillingAddress()->getPostcode())
            ->setStreet($this->getBillingAddress()->getStreet())
            ->setCity($this->getBillingAddress()->getCity())
            ->setTelephone($this->getBillingAddress()->getTelephone())
            ->setCountry($this->getBillingAddress()->getCountry())
            ->setCompany($this->getBillingAddress()->getCompany())
            ->save();
    }

    /**
     * Get a usable email address
     *
     * @param string $customerSessionEmail email of current user
     *
     * @return string
     */
    public function getEmailValue($customerSessionEmail)
    {
        //Get the email address from the address object if its set
        $addressEmail = $this->getShippingAddress()->getEmail();
        if (strlen($addressEmail) > 0) {
            return $addressEmail;
        }

        //Otherwise we have to pick up the customers email from the session
        $sessionEmail = $customerSessionEmail;
        if (strlen($sessionEmail) > 0) {
            return $sessionEmail;
        }

        //For guests and new customers there wont be any email on the
        //customer object in the session or their shipping address, so we
        //have to fall back and get the email from their billing address.
        return $this->getBillingAddress()->getEmail();
    }

    /**
     * Check that Date of birth has been supplied if required.
     *
     * @return bool
     */
    protected function _checkDateOfBirth()
    {
        try {
            $data = $this->getInfoInstance();
            if (!$data->getAdditionalInformation('dob_day') ||
                !$data->getAdditionalInformation('dob_month') ||
                !$data->getAdditionalInformation('dob_year')) {
                return false;
            }
            if ($data->getAdditionalInformation('dob_day') === "00" ||
                $data->getAdditionalInformation('dob_month') === "00" ||
                $data->getAdditionalInformation('dob_year') === "00" ) {
                return false;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
            return false;
        }
        return true;
    }

    protected function _checkField($field)
    {
        try {
            $data = $this->getInfoInstance();
            if (!$data->getAdditionalInformation($field)) {
                return false;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
            return false;
        }
        return true;
    }

    /**
     * Check that consent has been given if needed.
     *
     * @return bool
     */
    protected function _checkConsent()
    {
        try {
            $data = $this->getInfoInstance();
            if ((!$data->getAdditionalInformation("consent"))
                || ($data->getAdditionalInformation("consent") !== "consent")) {
                return false;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
            return false;
        }
        return true;
    }

    /**
     * Check that gender has been selected
     *
     * @return bool
     */
    protected function _checkGender()
    {
        try {
            $data = $this->getInfoInstance();
            if (($data->getAdditionalInformation("gender")!=="0")
             && ($data->getAdditionalInformation("gender")!=="1")) {
                return false;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
            return false;
        }
        return true;
    }

    /**
     * Make sure phonenumber is not blank.
     *
     * @return bool
     */
    protected function _checkPhone()
    {
        return $this->_checkField("phonenumber");
    }

    /**
     * Make sure pno is not blank.
     *
     * @return bool
     */
    protected function _checkPno()
    {
        return $this->_checkField("pno");
    }

    protected function _getReservationNo()
    {
        $res = $this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_RESERVATION_ID);
        if ($this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_ORDER_ID)) {
            $res = $this->_getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_ORDER_ID);
        }
        return $res;
    }

    // Moved from the deleted Tools file
    
    /**
     * Collect the post values that are relevant to the payment method
     *
     * @param array  $data    The post values to save
     * @param string $method  The payment method
     *
     * @return void
     */
    public function addPostValues($data, $method = NULL)
    {
        foreach ($data as $key => $value) {
            if ($method) {
                $key = str_replace($method . "_", "", $key);
            }
            if ($this->_getHelper()->isKlarnaField($key)) {
                $this->_postValues[$key] = $value;
            } else {
                $this->_getHelper()->logDebugInfo('Field ignored: ' . $key);
            }
        }
    }

    /*
     * This is required when using one step checkout, as it seems to post all fields for all klarna methods
     * This removes all fields containing names of the not selected klarna methods
     *
     * @param object  $data   Contains the data array containing the post values to clean
     * @param string $method  The payment method
     *
     * @return void
     */
    public function clearInactiveKlarnaMethodsPostvalues($dataObj, $method = NULL)
    {

        if ($method) {
            $methods = $this->_getHelper()->getSupportedMethods();
            $methodsToClear = array();
            foreach ($methods as $m) {
                if ($m!=$method) {
                    $methodsToClear[] = $m;
                }
            }
            $data = $dataObj->getData();
            foreach ($data as $key => $value) {
                foreach ($methodsToClear as $m) {
                    if (stristr($key, $m)!=false) {
                        unset($data[$key]);
                    }
                }
            }
            $dataObj->setData($data);
        }
    }

    public function unsPostvalue($key)
    {
        unset($this->_postValues[$key]);
    }

    /**
     * Set Magento additional info.
     *
     * Based on cleaned post values
     *
     * @param Mage_Payment_Model_Info $info   payment info instance
     *
     * @return void
     */
    public function updateAdditionalInformation($info)
    {
        foreach ($this->_postValues as $key => $value) {
            if ($value==='') {
                if ($info->getAdditionalInformation($key)) {
                    $info->unsAdditionalInformation($key);
                }
                continue;
            }
            $info->setAdditionalInformation($key, $value);
        }
    }

    /**
     * Update a Magento address with post values and save it
     * Even if they entered with two address lines, we update back to Magento only for first street line
     *
     * @param object $address The Magento address
     * @param string $specific_field To update only one field
     *
     * @return void
     */
    protected function _updateAddress($address, $specific_field = NULL)
    {
        if (array_key_exists("street", $this->_postValues)) {
            if ($specific_field==NULL || $specific_field=='street') {
                $street = $this->_postValues["street"];
                if (array_key_exists("house_number", $this->_postValues)) {
                    $street .=  " " . $this->_postValues["house_number"];
                }
                if (array_key_exists("house_extension", $this->_postValues)) {
                    $street .= " " . $this->_postValues["house_extension"];
                }
                $address->setStreet($this->_getHelper()->decode(trim($street)));
            }
        }

        if (array_key_exists("first_name", $this->_postValues)) {
            if ($specific_field==NULL || $specific_field=='first_name') {
                $address->setFirstname($this->_getHelper()->decode($this->_postValues["first_name"]));
            }
        }

        if (array_key_exists("last_name", $this->_postValues)) {
            if ($specific_field==NULL || $specific_field=='last_name') {
                $address->setLastname($this->_getHelper()->decode($this->_postValues["last_name"]));
            }
        }

        if (array_key_exists("zipcode", $this->_postValues)) {
            if ($specific_field==NULL || $specific_field=='zipcode') {
                $address->setPostcode($this->_getHelper()->decode($this->_postValues["zipcode"]));
            }
        }

        if (array_key_exists("city", $this->_postValues)) {
            if ($specific_field==NULL || $specific_field=='city') {
                $address->setCity($this->_getHelper()->decode($this->_postValues["city"]));
            }
        }

        if (array_key_exists("phonenumber", $this->_postValues)) {
            if ($specific_field==NULL || $specific_field=='phonenumber') {
                $address->setTelephone($this->_getHelper()->decode($this->_postValues["phonenumber"]));
            }
        }

        if ($specific_field==NULL || $specific_field=='company') {
            $address->setCompany($this->_getCompanyName($address, $this->_postValues));
        }

        $address->save();
    }

    /**
     * Get company name if possible.
     *
     * @param object $address The Magento address
     *
     * @return string Company name or empty string.
     */
    protected function _getCompanyName($address)
    {
        if ($this->isCompanyAllowed() === false) {
            return '';
        }

        if (array_key_exists('invoice_type', $this->_postValues)
            && $this->_postValues['invoice_type'] !== 'company'
        ) {
            return '';
        }

        // If there is a company name in the POST, update it on the address.
        if (array_key_exists('company_name', $this->_postValues)) {
            return $this->_getHelper()->decode($this->_postValues['company_name']);
        }

        // Otherwise keep what is on the address.
        return $address->getCompany();
    }

    public function getPostValues($key)
    {
        if ($key) {
            if (isset($this->_postValues[$key])) {
                return $this->_postValues[$key];
            } else {
                return NULL;
            }
        }
        return $this->_postValues;
    }

    public function setPaymentFee($quote)
    {
        if ($quote->getVaimoKlarnaFee()) {
            $this->addPostValues(array(
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE  => $quote->getVaimoKlarnaFee(),
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX => $quote->getVaimoKlarnaFeeTax(),
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE  => $quote->getVaimoKlarnaBaseFee(),
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE_TAX => $quote->getVaimoKlarnaBaseFeeTax(),
                        ));
        } else {
            $this->addPostValues(array(
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE  => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_FEE_TAX => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE  => '',
                        Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_BASE_FEE_TAX => '',
                        ));
        }
    }

    protected function _createShippingDetails($order, $invoiceItems)
    {
        $res = NULL;
        /**
         * If customer selects to create shipment directly from invoice, it will generate the
         * shipment AFTER the invoice. So I check the post here, in order to use that information.
         */
        if (isset($_POST)) {
            $usePostShipment = false;
            if (isset($_POST['invoice'])) {
                if (isset($_POST['invoice']['do_shipment'])) {
                    if ($_POST['invoice']['do_shipment']=="1") {
                        $usePostShipment = true;
                    }
                }
            }
            if ($usePostShipment) {
                if (isset($_POST['tracking'])) {
                    foreach ($_POST['tracking'] as $tracking) {
                        $title = Mage::helper('klarna')->__("Unknown");
                        $number = "";
                        if (isset($tracking['title'])) {
                            $title = $tracking['title'];
                        }
                        if (isset($tracking['number'])) {
                            $number = $tracking['number'];
                        }
                        $shippingDetail = array(
                            'tracking_number' => $number,
                            'shipping_company' => $title,
                        );
                        if (!$res) {
                            $res = array();
                        }
                        $res[] = $shippingDetail;
                    }
                }
            }
        }
        foreach ($order->getShipmentsCollection() as $_shipment) {
            $shippingDetail = NULL;
            foreach ($_shipment->getItemsCollection() as $item) {
                foreach ($invoiceItems as $invoiceItem) {
                    if ($item->getOrderItemId()==$invoiceItem->getOrderItemId()) {
                        foreach ($_shipment->getTracksCollection() as $tracking) {
                            $shippingDetail = array(
                                'tracking_number' => $tracking->getTrackNumber(),
                                //'tracking_url' => $this->helper('shipping')->getTrackingPopupUrlBySalesModel($order), //Mage::getModel('core/url')->getUrl('sales/order/track', array('order_id' => $order->getId())),
                                'shipping_company' => $tracking->getTitle(),
                            );
                            if (!$res) {
                                $res = array();
                            }
                            $res[] = $shippingDetail;
                        }
                    }
                    if ($shippingDetail) {
                        break;
                    }
                }
                if ($shippingDetail) {
                    break;
                }
            }
        }
        return $res;
    }
}
