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

class Vaimo_Klarna_Block_Klarnacheckout_Ga extends Mage_GoogleAnalytics_Block_Ga
{
    /**
     * Render information about specified orders and their items
     *
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiEcommerce.html#_gat.GA_Tracker_._addTrans
     * @return string
     */
    protected function _getOrdersTrackingCode()
    {
        $result = parent::_getOrdersTrackingCode();

        /** @var Klarna_Checkout_Order $klarnaOrder */
        // Not happy with this, but I guess we can't solve it in other ways.
        // can use it without getting hold of and use the actual KlarnaOrder
        if ($klarnaOrder = $this->getKlarnaCheckoutOrder()) {
            $klarnaCode = array();

            if ($klarnaOrder->offsetExists('order_id')) { // KCO UK

                $transactionId = $klarnaOrder['order_id'];
                $grandTotal = $klarnaOrder->offsetExists('order_amount') ? $klarnaOrder['order_amount'] / 100 : 0;
                $taxAmount = $klarnaOrder->offsetExists('order_tax_amount') ? $klarnaOrder['order_tax_amount'] / 100 : 0;
                $items = $klarnaOrder->offsetExists('order_lines') ? $klarnaOrder->offsetGet('order_lines') : array();
                $shippingAmount = 0;

                foreach ($items as $item) {
                    if (isset($item['type']) && $item['type'] == 'shipping_fee' && isset($item['total_amount'])) {
                        $shippingAmount += $item['total_amount'] / 100;
                        $grandTotal -= $item['total_amount'] / 100;
                    }
                }

                $address = $klarnaOrder->offsetExists('shipping_address') ? $klarnaOrder->offsetGet('shipping_address') : array();
                $city = isset($address['city']) ? $address['city'] : '';
                $region = '';
                $country = isset($address['country']) ? strtoupper($address['country']) : '';

            } else {

                $transactionId = $klarnaOrder->offsetExists('reservation') ? $klarnaOrder->offsetGet('reservation') : '';
                $cart = $klarnaOrder->offsetExists('cart') ? $klarnaOrder->offsetGet('cart') : array();
                $items = (isset($cart['items']) && is_array($cart['items'])) ? $cart['items'] : array();
                $grandTotal = isset($cart['total_price_excluding_tax']) ? $cart['total_price_excluding_tax'] / 100 : 0;
                $taxAmount = isset($cart['total_tax_amount']) ? $cart['total_tax_amount'] / 100 : 0;
                $shippingAmount = 0;

                foreach ($items as $item) {
                    if (isset($item['type']) && $item['type'] == 'shipping_fee' && isset($item['total_price_including_tax'])) {
                        $shippingAmount += $item['total_price_including_tax'] / 100;
                        $grandTotal -= $item['total_price_including_tax'] / 100;
                    }
                }

                $address = $klarnaOrder->offsetExists('shipping_address') ? $klarnaOrder->offsetGet('shipping_address') : array();
                $city = isset($address['city']) ? $address['city'] : '';
                $region = '';
                $country = isset($address['country']) ? strtoupper($address['country']) : '';
            }

            $klarnaCode[] = sprintf("_gaq.push(['_addTrans', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);",
                $transactionId,
                Mage::app()->getStore()->getFrontendName(),
                number_format($grandTotal, 2, '.', ''),
                number_format($taxAmount, 2, '.', ''),
                number_format($shippingAmount, 2, '.', ''),
                $this->jsQuoteEscape($city),
                $this->jsQuoteEscape($region),
                $this->jsQuoteEscape($country)
            );

            foreach ($items as $item) {
                if (isset($item['type']) && $item['type'] == 'physical') {
                    $sku = isset($item['reference']) ? $item['reference'] : '';
                    $name = isset($item['name']) ? $item['name'] : '';
                    $price = isset($item['unit_price']) ? $item['unit_price'] / 100 : '';
                    $quantity = isset($item['quantity']) ? $item['quantity'] : '';

                    $klarnaCode[] = sprintf("_gaq.push(['_addItem', '%s', '%s', '%s', '%s', '%s', '%s']);",
                        $transactionId,
                        $this->jsQuoteEscape($sku),
                        $this->jsQuoteEscape($name),
                        null, // there is no "category" defined for the order item
                        number_format($price, 2, '.', ''),
                        number_format($quantity, 2, '.', '')
                    );
                }
            }

            $klarnaCode[] = "_gaq.push(['_trackTrans']);";

            if ($result) {
                $result .= implode("\n", $klarnaCode);
            }  else {
                $result = implode("\n", $klarnaCode);
            }
        }

        return $result;
    }
}
