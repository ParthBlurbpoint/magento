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


class Vaimo_Klarna_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /*
     * Klarna requires the invoice details, lines etc, to perform capture
     * It's not known in the capture event, so I pick it up here
     * The item list is put onto the payment object, not stored, but it
     * will be available in the capture method
     *
     * @param Varien_Event_Observer $observer
     */
    public function prePaymentCapture($observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setPayment($payment);

        if (!Mage::helper('klarna')->isMethodKlarna($payment->getMethod())) {
            return $this;
        }

        $invoice = $observer->getEvent()->getInvoice();
        $klarna->setInvoice($invoice);
        $itemList = $klarna->createItemListCapture();
        $payment->setKlarnaItemList($itemList);
    }

    /*
     * A cleanup function, if Klarna is selected, payment is saved and then
     * another method is selected and payment saved, then we cleanup the
     * additional information fields we set
     *
     * @param Varien_Event_Observer $observer
     */
    public function cleanAdditionalInformation($observer)
    {
        $payment = $observer->getEvent()->getPayment();
        if ($payment) {
            $data = $observer->getEvent()->getInput();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($payment->getQuote());
            $klarna->clearInactiveKlarnaMethodsPostvalues($data,$data->getMethod());
        }
    }

    /*
     * We remove pno from quote, after the sales order was successfully placed
     *
     * @param Varien_Event_Observer $observer
     */
    public function cleanPnoFromQuote($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if ($quote) {
            if (!$quote->getIsActive()) {
                $payment = $quote->getPayment();
                if ($payment) {
                    if (Mage::helper('klarna')->isMethodKlarna($payment->getMethod())) {
                        if ($payment->getAdditionalInformation('pno')) {
                            $payment->unsAdditionalInformation('pno');
                        }
                    }
                }
            }
        }
    }

    /*
     * If customer used other payment methods to checkout, it should default back to Klarna Checkout, if used
     *
     * @param Varien_Event_Observer $observer
     */
    public function resetCheckoutCookie($observer)
    {
        $this->_getSession()->setKlarnaUseOtherMethods(false);
    }

    /*
     * Making sure the status is Klarna Reserved after a
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkForKlarnaStatusChange($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $method = $order->getPayment()->getMethod();
        if (Mage::helper('klarna')->isMethodKlarna($method)) {
            $orderOriginal = Mage::getModel('sales/order')->load($order->getId());
            if ($orderOriginal->getState()==Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                if ($order->getState()==Mage_Sales_Model_Order::STATE_PROCESSING) {
                    $klarna = Mage::getModel('klarna/klarna');
                    $klarna->setOrder($order);
                    $order->setStatus($klarna->getConfigData('order_status'));
                }
            }
        }
    }

// KLARNA CHECKOUT FROM HERE

    public function customerAddressFormat(Varien_Event_Observer $observer)
    {
        $type = $observer->getEvent()->getType();

        if (strpos($type->getDefaultFormat(), 'care_of') === false) {
            $defaultFormat = explode("\n", $type->getDefaultFormat());

            if (is_array($defaultFormat)) {
                $result = array();

                foreach ($defaultFormat as $key => $value) {
                    $result[] = $value;
                    if ($key == 0) {
                        $result[] = '{{depend care_of}}c/o {{var care_of}}<br />{{/depend}}';
                    }
                }

                $type->setDefaultFormat(implode("\n", $result));
            }
        }
    }

    public function checkLaunchKlarnaCheckout($observer)
    {
        if (!$this->_getSession()->getKlarnaUseOtherMethods()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            if ($klarna->getKlarnaCheckoutEnabled()) {
                $controllerAction = $observer->getControllerAction();
                $controllerAction->getResponse()
                    ->setRedirect(Mage::getUrl('checkout/klarna'))
                    ->sendResponse();
                exit;
            }
        }
    }

    /**
     * Odd way of figuring out when to reset the session variable
     * Idea at first was to have a permanent switch of payment methods
     * but now the code goes back to KCO (if active) as soon as any non
     * checkout controller runs.
     *
     * @param $observer
     */
    public function checkDisableUseOtherMethods($observer)
    {
        if ($this->_getSession()->getKlarnaUseOtherMethods()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
            if ($klarna->getConfigData('auto_reset_other_method_button')) {
                $controller = $observer->getEvent()->getControllerAction();
                $class = get_class($controller);
                $action = $controller->getRequest()->getActionName();
                $clearFlag = false;
                if ($action!='noRoute') {
                    if ((!stristr($class, 'checkout') && !stristr($class, 'ajax') && !stristr($class, 'klarna')) ||
                        (stristr($class, 'checkout') && !stristr($class, 'ajax') &&  stristr($class, 'cart'))) {
                        $clearFlag = true;
                    }
                }
                if ((stristr($class, 'customer_account') && stristr($action, 'loginPost'))) {
                    $clearFlag = false;
                }
                if ($clearFlag) {
                    Mage::helper('klarna')->logKlarnaDebug('checkDisableUseOtherMethods clearFlag is true. class = ' . $class . ' and action = ' . $action);
                    $this->_getSession()->setKlarnaUseOtherMethods(false);
                    $payment = $quote->getPayment();
                    $payment->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
                    // This code was added because Magento does something very strange when we call getShippingRates
                    // in available.phtml
                    // It sometimes changes shipping amount to incl tax, which means total shipping is increased
                    // by shipping tax. This causes Klarna Checkout to reply with an error, since totals don't match
                    // Easiest solution was to clear selected shipping method when switching...
                    $quote->getShippingAddress()->setShippingMethod(NULL)->setShippingDescription(NULL);
                    $quote->collectTotals()->save();
                }
            }
        }
    }

    public function addMassAction($observer)
    {
        if (get_class($observer->getEvent()->getBlock()) == 'Mage_Adminhtml_Block_Widget_Grid_Massaction') {
            if ($observer->getEvent()->getBlock()->getRequest()->getControllerName() == 'sales_order') {
                $store = Mage::app()->getStore();
                $path = "*/klarna_massaction/capture";
                $params = array(
                    '_secure' => $store->isAdminUrlSecure()
                );
                $url = $store->getUrl($path,$params);
                $observer->getEvent()->getBlock()->addItem('klarna_mass_capture', array(
                    'label'=> Mage::helper('sales')->__('Invoice Klarna Orders'),
                    'url'  => $url,
                ));
            }
        }
    }
}
