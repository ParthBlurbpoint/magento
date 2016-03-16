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

class Vaimo_Klarna_Checkout_KlarnaController extends Mage_Core_Controller_Front_Action
{
    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    protected function _checkPaymentMethod()
    {
        // set payment method
        $quote = $this->_getQuote();

        if ($quote->getPayment()->getMethod() != Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT) {
            if ($quote->isVirtual()) {
                $quote->getBillingAddress()->setPaymentMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            } else {
                $quote->getShippingAddress()->setPaymentMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            }
            $quote->getPayment()->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        }

        return $this;
    }

    protected function _checkShippingMethod()
    {
        // set shipping method
        $quote = $this->_getQuote();
        $shippingAddress = $quote->getShippingAddress();
        if (!$quote->isVirtual() && $shippingAddress && !$shippingAddress->getShippingMethod()) {
            $quote->setRemoteIp($quote->getRemoteIp());
            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest(
                $quote->getShippingAddress(),
                $quote->getBillingAddress(),
                NULL,
                $quote->getStoreId()
            );
            $shippingAddress->setCountryId($request->getCountryId());
            $shippingAddress->setRegionId($request->getRegionId());
            $shippingAddress->setPostcode($request->getPostcode());
            // Massive workaround... because Shipping Origin is per website, not store...
            if (Mage::helper('klarna')->getDefaultCountry()!=$shippingAddress->getCountryId()) {
                if (!$shippingAddress->getRegionId() && !$shippingAddress->getPostcode()) {
                    $shippingAddress->setCountryId(Mage::helper('klarna')->getDefaultCountry());
                }
            }
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectTotals();
            $shippingAddress->collectShippingRates();
            $rates = $shippingAddress->getGroupedAllShippingRates();
            foreach ($rates as $carrierRates) {
                foreach ($carrierRates as $rate) {
                    $shippingAddress->setShippingMethod($rate->getCode());
                    $quote->setTotalsCollectedFlag(false);
                    break;
                }
                break;
            }
        }

        return $this;
    }

    protected function _checkNewsletter()
    {
        $quote = $this->_getQuote();
        $klarna = Mage::getModel('klarna/klarnacheckout');
        $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        $klarna->checkNewsletter();
        return $this;
    }

    public function othermethodAction()
    {
        $quote = $this->_getQuote();
        // @todo find active method, not just Invoice automatically
        $quote->getPayment()->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE);
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $quote->save();
        $this->_getSession()->setKlarnaUseOtherMethods(true);
        if (Mage::helper('klarna')->isOneStepCheckout()) {
            $this->_redirect('onestepcheckout');
        } else {
            $this->_redirect('checkout/onepage');
        }
    }

    public function kcomethodAction()
    {
        $quote = $this->_getQuote();
        $quote->getPayment()->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $quote->save();
        $this->_getSession()->setKlarnaUseOtherMethods(false);
        if (Mage::helper('klarna')->isOneStepCheckout()) {
            $this->_redirect('onestepcheckout');
        } else {
            $this->_redirect('checkout/onepage');
        }
    }

    public function indexAction()
    {
        $quote = $this->_getQuote();
        $klarna = Mage::getModel('klarna/klarnacheckout');
        $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        if (!$klarna->getKlarnaCheckoutEnabled()) {
            if (Mage::helper('klarna')->isOneStepCheckout()) {
                $this->_redirect('onestepcheckout');
            } else {
                $this->_redirect('checkout/onepage');
            }
            return;
        }

        $quote = $this->_getQuote();

        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }

        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');

            $this->_getSession()->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }

        $this->_checkPaymentMethod();
        $this->_checkShippingMethod();
        $this->_checkNewsletter();

        $quote->collectTotals();
        $quote->save();

        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('Klarna Checkout'));
        $this->renderLayout();
    }

    public function subscribeToNewsletterAction()
    {
        $quote = $this->_getQuote();
        $subscribe = $this->getRequest()->getParam('subscribe_to_newsletter');
        $quote->setKlarnaCheckoutNewsletter($subscribe);
        $quote->save();
    }

    protected function _updateAddressField($address, $field, $value)
    {
        $res = false;
        if ($value && $address->getData($field)!=$value) {
            $address->setData($field, $value);
            $res = true;
        }
        return $res;
    }

    public function addressUpdateAction()
    {
        $result = false;
        $quote = $this->_getQuote();

        $firstname = $this->getRequest()->getParam('firstname');
        $lastname = $this->getRequest()->getParam('lastname');
        $street = $this->getRequest()->getParam('street');
        $postcode = $this->getRequest()->getParam('postcode');
        $city = $this->getRequest()->getParam('city');
        $region = strtoupper($this->getRequest()->getParam('region'));
        $telephone = $this->getRequest()->getParam('telephone');
        $country = strtoupper($this->getRequest()->getParam('country'));

        $country_id = NULL;
        $region_id = NULL;

        if ($country) {
            $countryRec = Mage::getModel('directory/country')->loadByCode($country, 'iso3_code');
            if ($countryRec) {
                $country_id = $countryRec->getId();
            }
        }
        if ($region && $country_id) {
            $regionRec = Mage::getModel('directory/region')->loadByCode($region, $country_id);
            if ($regionRec) {
                $region_id = $regionRec->getId();
            }
        }

        $address = $quote->getShippingAddress();

        if ($this->_updateAddressField($address, 'firstname', $firstname)) $result = true;
        if ($this->_updateAddressField($address, 'lastname', $lastname)) $result = true;
        if ($this->_updateAddressField($address, 'street', $street)) $result = true;
        if ($this->_updateAddressField($address, 'postcode', $postcode)) $result = true;
        if ($this->_updateAddressField($address, 'city', $city)) $result = true;
        if ($this->_updateAddressField($address, 'telephone', $telephone)) $result = true;
        if ($this->_updateAddressField($address, 'country_id', $country_id)) $result = true;
        if ($this->_updateAddressField($address, 'region_id', $region_id)) $result = true;
        if ($result) {
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->save();
        }
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function taxshippingupdateAction()
    {
        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
        $checkoutId = $this->getRequest()->getParam('klarna_order');
        Mage::helper('klarna')->logKlarnaApi('taxshippingupdate callback received for ID ' . $checkoutId);

        $quote = Mage::getModel('sales/quote')->load($checkoutId, 'klarna_checkout_id');
        $klarna = Mage::getModel('klarna/klarnacheckout');
        $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);

        $post_body = file_get_contents('php://input');
        $data = json_decode($post_body, true);
        Mage::helper('klarna')->logDebugInfo('taxshippingupdate data', $data);

        $result = $klarna->updateTaxAndShipping($data);

        Mage::helper('klarna')->logDebugInfo('taxshippingupdate response', $result);
        $this->getResponse()->setBody(Zend_Json::encode($result));
        
        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);
    }

    public function validateFailedAction()
    {
        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);

        $checkoutId = $this->getRequest()->getParam('klarna_order');
        $quote = Mage::getModel('sales/quote')->load($checkoutId, 'klarna_checkout_id');
        $payment = $quote->getPayment();
        $errors = $payment->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_VALIDATE_ERRORS);
        Mage::helper('klarna')->logKlarnaApi('failedAction errors: ' . $errors);
        if ($errors) {
            $payment->unsAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_VALIDATE_ERRORS);
            $payment->save();
            $this->_getSession()->addError($errors);
        }

        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        $this->_redirect('checkout/cart');
        return;
    }
    
    public function validateAction()
    {
        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);

        $checkoutId = $this->getRequest()->getParam('klarna_order');
        $quote = Mage::getModel('sales/quote')->load($checkoutId, 'klarna_checkout_id');
        $klarna = Mage::getModel('klarna/klarnacheckout');
        $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);

        Mage::helper('klarna')->logKlarnaApi('validateAction checkout id: ' . $checkoutId);

        $result = $klarna->validateQuote();

        Mage::helper('klarna')->logKlarnaApi('validateAction result = ' . $result);
        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

        if ($result !== true) {
            $payment = $quote->getPayment();

            if ($payment->getId()) {
                $payment->setAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_VALIDATE_ERRORS, $result);
                $payment->save();
            }

            $this->getResponse()
                ->setHttpResponseCode(303)
                ->setHeader('Location', Mage::getUrl('checkout/klarna/validateFailed', array('klarna_order' => $checkoutId)));
        }
        $this->getResponse()
            ->setHttpResponseCode(200);
    }

    public function pushAction()
    {
        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
        $checkoutId = $this->getRequest()->getParam('klarna_order');
        $quote = Mage::getModel('sales/quote')->load($checkoutId, 'klarna_checkout_id');

        /** @var Vaimo_Klarna_Model_Klarnacheckout $klarna */
        $klarna = Mage::getModel('klarna/klarnacheckout');
        $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        if (substr($checkoutId, -1, 1) == '/') {
            $checkoutId = substr($checkoutId, 0, strlen($checkoutId) - 1);
        }
        Mage::helper('klarna')->logKlarnaApi('pushAction checkout id: ' . $checkoutId);
        if (!$quote->getId()) {
            Mage::helper('klarna')->logKlarnaApi('pushAction checkout quote not found!');
        }

        if ($checkoutId) {
            try {
                // createOrder returns the order if successful, otherwise an error string
                $result = $klarna->createOrder($checkoutId);

                if (is_object($result)) {
                    Mage::helper('klarna')->logKlarnaApi('pushAction order created successfully, order id: ' . $result->getId());
                } else {
                    Mage::helper('klarna')->logKlarnaApi($result);
                }
            } catch (Exception $e) {
                Mage::helper('klarna')->logKlarnaException($e);
            }
        }
        Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);
    }

    public function successAction()
    {
        try {
            Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $checkoutId = $this->_getSession()->getKlarnaCheckoutId();
            $quote = Mage::getModel('sales/quote')->load($checkoutId, 'klarna_checkout_id');
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            Mage::helper('klarna')->logKlarnaApi('successAction checkout id: ' . $checkoutId);

            if (!$checkoutId) {
                Mage::helper('klarna')->logKlarnaApi('successAction checkout id is empty, so we do nothing');
                //$this->_redirect('');
                //return;
                exit(1);
            }

            $status = $klarna->getCheckoutStatus($checkoutId);
            $canDisplaySuccess = $status == 'checkout_complete' || $status == 'created';

            if (!$canDisplaySuccess) {
                Mage::helper('klarna')->logKlarnaApi('successAction ERROR: order not created: ' . $status);
                $this->_redirect('');
                return;
            } else {
                Mage::helper('klarna')->logKlarnaApi('successAction displaying success');
            }

            // close the quote if push hasn't closed it already
            $quote = $this->_getQuote();
            if ($quote->getId() && $quote->getIsActive()) {
                Mage::helper('klarna')->logKlarnaApi('successAction closing quote');
                /** @var Mage_Core_Model_Resource $resource */
                $resource = Mage::getSingleton('core/resource');
                $read = $resource->getConnection('core_read');
                $read->update($resource->getTableName('sales/quote'), array('is_active' => 0), 'entity_id = ' . $quote->getId());
            }

            $this->_getSession()->setLastQuoteId($quote->getId());
            $this->_getSession()->clearHelperData();
            $this->_getSession()->clear();
            $this->_getCart()->unsetData('quote');

            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->getLayout()->getBlock('head')->setTitle($this->__('Klarna Checkout'));

// This is KCO specific for the current API... This must find another solution
            if ($block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('google_analytics')) {
                $block->setKlarnaCheckoutOrder($klarna->getActualKlarnaOrder());
            }

            $this->renderLayout();

            $this->_getSession()->setKlarnaCheckoutId('');
            $this->_getSession()->setKlarnaUseOtherMethods(false);
            Mage::helper('klarna')->logKlarnaApi('successAction displayed success');
            Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);
        } catch (Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
        }
    }

    public function saveShippingMethodAction()
    {
        $resultMessage = array();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $resultMessage['shipping_method'] = $data;

            try {
                $result = $this->_getOnepage()->saveShippingMethod($data);
                if (!$result) {
                    Mage::dispatchEvent(
                       'klarnacheckout_controller_klarna_save_shipping_method',
                        array(
                             'request' => $this->getRequest(),
                             'quote'   => $this->_getOnepage()->getQuote()));
                    $this->_getOnepage()->getQuote()->collectTotals()->save();
                }
/*
// This code should work just as well, it won't call all the saveShippingMethod functions
                $quote = $this->_getQuote();
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setShippingMethod($data);
                Mage::dispatchEvent(
                   'klarnacheckout_controller_klarna_save_shipping_method',
                    array(
                         'request' => $this->getRequest(),
                         'quote'   => $quote
                         )
                    );
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals()->save();
*/
            }
            catch (Exception $e) {
                $resultMessage['error'] = $e->getMessage();
            }

            $resultMessage['success'] = 'Shipping method successfully saved';
        }

        if ($this->getRequest()->isXmlHttpRequest()) { 
            $this->getResponse()->setBody(Zend_Json::encode($resultMessage));
        } else {
            $this->_redirect('checkout/klarna');
        }
    }

    public function addGiftCardAction()
    {
    	$resultMessage = array();
        $data = $this->getRequest()->getPost();
        if (isset($data['giftcard_code'])) {
            $code = $data['giftcard_code'];
            try {
                Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
                    ->loadByCode($code)
                    ->addToCart(false);

                $quote = $this->_getQuote();
                $quote->collectTotals();
                $quote->save();

                $this->_getSession()->addSuccess(
                    $this->__('Gift Card "%s" was added.', Mage::helper('core')->htmlEscape($code))
                );
                $resultMessage['success'] = $this->__('Gift Card "%s" was added.', Mage::helper('core')->htmlEscape($code));
            } catch (Mage_Core_Exception $e) {
                Mage::dispatchEvent('enterprise_giftcardaccount_add', array('status' => 'fail', 'code' => $code));
                $this->_getSession()->addError(
                    $e->getMessage()
                );
                $resultMessage['error'] = $e->getMessage();
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Cannot apply gift card.'));
                $resultMessage['error'] = $this->__('Cannot apply gift card.');
            }
        }

        $this->_checkShippingMethod();

        if ($this->getRequest()->isXmlHttpRequest()) {
        	$this->getResponse()->setBody(Zend_Json::encode($resultMessage));
        } else {
        	$this->_redirect('checkout/klarna');
        }
    }

    public function removeGiftCardAction()
    {
        $resultMessage = array();
        if ($code = $this->getRequest()->getParam('code')) {
            try {
                Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
                    ->loadByCode($code)
                    ->removeFromCart(false);

                $quote = $this->_getQuote();
                $quote->collectTotals();
                $quote->save();

                $this->_getSession()->addSuccess(
                    $this->__('Gift Card "%s" was removed.', Mage::helper('core')->htmlEscape($code))
                );
                $resultMessage['success'] = $this->__('Gift Card "%s" was removed.', Mage::helper('core')->htmlEscape($code));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError(
                    $e->getMessage()
                );
                $resultMessage['error'] = $e->getMessage();
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Cannot remove gift card.'));
                $resultMessage['error'] = $this->__('Cannot remove gift card.');
            }
        }

        $this->_checkShippingMethod();

        if ($this->getRequest()->isXmlHttpRequest()) {
        	$this->getResponse()->setBody(Zend_Json::encode($resultMessage));
        } else {
        	$this->_redirect('checkout/klarna');
        }
    }

    public function getKlarnaWrapperHtmlAction()
    {
        $layout = (int) $this->getRequest()->getParam('klarna_layout');

        if ($layout == 1 && !empty($layout)) {
            $blockName = 'klarna_sidebar';
        }
        else {
            $blockName = 'klarna_default';
        }

        $this->loadLayout('checkout_klarna_index');

        $block = $this->getLayout()->getBlock($blockName);
        $cartHtml = $block->toHtml();

        $result['update_sections'] = array(
            'name' => 'klarna_sidebar',
            'html' => $cartHtml
        );

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function getKlarnaCheckoutAction()
    {
        $this->loadLayout('checkout_klarna_index');

        $block = $this->getLayout()->getBlock('checkout');
        $klarnaCheckoutHtml = $block->toHtml();

        $result['update_sections'] = array(
            'name' => 'klarna_checkout',
            'html' => $klarnaCheckoutHtml
        );

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /**
     * Copy of _updateShoppingCart in Mage_Checkout_CartController but with ajax response and
     * functionality to work on checkout page. (Tried to keep as standard as possible)
     */
    public function cartUpdatePostAction()
    {
        $result = array();

        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }
                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData);

                // Addon to check qty vs stock to support ajax response
                $items = $cart->getQuote()->getItemsCollection();

                foreach ($items as $item) {
                    $item->checkData();
                }
                $errors = $cart->getQuote()->getErrors();
                $messages = array();

                foreach ($errors as $error) {
                    $messages[] = $error->getCode();
                }

                if (count($messages) > 0) {
                    Mage::throwException(implode(', ', $messages));
                }

                $cart->save();

                // Addon for ajax to redirect to cart
                if ($this->_getCart()->getSummaryQty() <= 0) {
                    $result['redirect_url'] = Mage::getUrl('checkout/cart');
                }
            }
            $this->_getSession()->setCartWasUpdated(true);
            $result['success'] = $this->__('Shopping cart updated successfully.');

        } catch (Mage_Core_Exception $e) {
            $result['error'] = Mage::helper('core')->escapeHtml($e->getMessage());
        } catch (Exception $e) {
            $result['error'] = Mage::helper('core')->escapeHtml($e->getMessage());
            Mage::logException($e);
        }

        $this->_checkShippingMethod();
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function couponPostAction()
    {
        $result = array();

        try {
            $couponCode = (string)trim($this->getRequest()->getParam('coupon_code'));
            $gc = (string)trim($this->getRequest()->getParam('gc'));

            // Remove GC
            if (isset($gc) && $gc != '') {
                $gcs = $this->_getQuote()->getGiftcertCode();

                if (!$gc || !$gcs || strpos($gcs, $gc) === false) {
                    Mage::throwException('Invalid request.');
                }

                $gcsArr = array();

                foreach (explode(',', $gcs) as $gc1) {
                    if (trim($gc1) !== $gc) {
                        $gcsArr[] = $gc1;
                    }
                }

                $this->_getQuote()->setGiftcertCode(join(',', $gcsArr))->save();
                $result['success'] = $this->__("Gift certificate was removed from your order.");
            } else {
                $isGiftcertActive = Mage::helper('core')->isModuleEnabled('Unirgy_Giftcert') || Mage::helper('core')->isModuleEnabled('Icommerce_Giftcert');

                if ($isGiftcertActive) {
                    $cert = Mage::getModel('ugiftcert/cert')->load($couponCode, 'cert_number');
                } else {
                    $cert = new Varien_Object();
                }

                // If giftcert, add giftcert
                if ($isGiftcertActive && $cert->getId() && $cert->getStatus() == 'A' && $cert->getBalance() > 0) {
                    $helper = Mage::helper('ugiftcert');
                    try {
                        $quote = $this->_getQuote();
                        if (Mage::getStoreConfig('ugiftcert/default/use_conditions')) {
                            $valid = $this->_validateConditions($cert, $quote);
                            if (!$valid) {
                                $result['error'] = $helper->__("Gift certificate '%s' cannot be used with your cart items", $cert->getCertNumber());
                            }
                        }
                        $cert->addToQuote($quote);
                        $quote->collectTotals()->save();
                        $result['success'] = $helper->__("Gift certificate '%s' was applied to your order.", $cert->getCertNumber());
                    } catch (Exception $e) {
                        $result['error'] = $helper->__("Gift certificate '%s' could not be applied to your order.", $cert->getCertNumber());
                    }
                } else {
                    // Just plain coupon code
                    if ($this->getRequest()->getParam('remove') == 1) {
                        $couponCode = '';
                    }
                    $oldCouponCode = $this->_getQuote()->getCouponCode();

                    if (!strlen($couponCode) && !strlen($oldCouponCode)) {
                        throw new Exception($this->__('No coupon code was submitted.'));
                    }

                    $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
                    $this->_getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
                        ->collectTotals()
                        ->save();

                    if ($couponCode) {
                        if ($couponCode == $this->_getQuote()->getCouponCode()) {
                            $result['success'] = $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode));
                        } else {
                            $result['error'] = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
                        }
                    } else {
                        $result['success'] = $this->__('Coupon code was canceled successfully.');
                    }
                }
            }
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        $this->_checkShippingMethod();
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function rewardPostAction()
    {
        $useRewardPoints = $this->getRequest()->getParam('use_reward_points');
        $result = array();

        $quote = $this->_getQuote();
        $quote->setUseRewardPoints((bool)$useRewardPoints);

        if ($quote->getUseRewardPoints()) {
            /* @var $reward Enterprise_Reward_Model_Reward */
            $reward = Mage::getModel('enterprise_reward/reward')
                ->setCustomer($quote->getCustomer())
                ->setWebsiteId($quote->getStore()->getWebsiteId())
                ->loadByCustomer();

            $minPointsBalance = (int)Mage::getStoreConfig(
                Enterprise_Reward_Model_Reward::XML_PATH_MIN_POINTS_BALANCE,
                $quote->getStoreId()
            );

            if ($reward->getId() && $reward->getPointsBalance() >= $minPointsBalance) {
                $quote->setRewardInstance($reward);
                $quote->collectTotals();
                $quote->save();
                $result['success'] = $this->__('Reward points used');
            } else {
                $quote->setUseRewardPoints(false)->collectTotals()->save();
                $result['success'] = $this->__('Reward points unused');
            }
        } else {
            $quote->setUseRewardPoints(false)->collectTotals()->save();
            $result['success'] = $this->__('Reward points unused');
        }

        $this->_checkShippingMethod();
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function customerBalancePostAction()
    {
        $shouldUseBalance = $this->getRequest()->getParam('use_customer_balance', false);
        $result = array();

        $quote = $this->_getQuote();
        $quote->setUseCustomerBalance($shouldUseBalance);

        if ($shouldUseBalance) {
            $store = Mage::app()->getStore($quote->getStoreId());
            $balance = Mage::getModel('enterprise_customerbalance/balance')
                ->setCustomerId($quote->getCustomerId())
                ->setWebsiteId($store->getWebsiteId())
                ->loadByCustomer();
            if ($balance) {
                $quote->setCustomerBalanceInstance($balance);
                $result['success'] = $this->__('Store credit used');
            } else {
                $quote->setUseCustomerBalance(false);
                $result['success'] = $this->__('Store credit unused');
            }
        } else {
            $result['success'] = $this->__('Store credit unused');
        }

        $quote->collectTotals()->save();
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
}