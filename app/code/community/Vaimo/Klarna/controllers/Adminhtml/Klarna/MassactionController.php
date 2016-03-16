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

class Vaimo_Klarna_Adminhtml_Klarna_MassactionController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _createInvoice($order)
    {
        $result = array();

        $qtyData = array();
        $totalQty = 0;

        /** Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            $qty = $item->getQtyShipped() - $item->getQtyInvoiced();
            if ($qty < 0) {
                $qty = 0;
            }
            $qtyData[$item->getItemId()] = $qty;
            $totalQty += $qty;
        }

        if (!$totalQty) {
            Mage::throwException('Invoice cannot be created, nothing is shipped');
        }

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($qtyData);

        if (!$invoice) {
            Mage::throwException('Failed to create invoice');
        }

        if (!$invoice->getTotalQty()) {
            Mage::throwException('Cannot create an invoice without products');
        }

        $invoice->register();
        $invoice->setEmailSent(true);
        $invoice->getOrder()->setCustomerNoteNotify(true);
        $invoice->getOrder()->setIsInProcess(true);

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        try {
            $invoice->sendEmail();
        } catch (Exception $e) {
            $result[] = 'Unable to send the invoice email';
        }

        try {
            if ($invoice->canCapture()) {
                $invoice->capture();
                $invoice->getOrder()->setIsInProcess(true);

                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
            }
        } catch (Exception $e) {
            $result[] = 'Error capturing invoice: ' . $e->getMessage();
        }
/*
        if ($order->canCancel()) {
            $order->cancel();
            $order->save();
        }
*/
        return $result;
    }

    public function captureAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            try {
                if (Mage::helper('klarna')->isMethodKlarna($order->getPayment()->getMethod())) {
                    $messages = $this->_createInvoice($order);
                } else {
                    $messages = array(Mage::helper('klarna')->__('Order ignored, does not have Klarna as payment method'));
                }
            } catch (Exception $e) {
                $messages = array($e->getMessage());
            }
            if (sizeof($messages)>0) {
                foreach ($messages as $message) {
                    Mage::getSingleton('adminhtml/session')->addError($message  . ' (' . $order->getIncrementId() . ')');
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('klarna')->__('Invoice created for order') . ' ' . $order->getIncrementId()
                );
            }
        }
        $this->_redirect('*/sales_order/index');
    }
}
