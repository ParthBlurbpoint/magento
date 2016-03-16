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

/**
 * This is the only file in the module that loads and uses the Klarna library folder
 * It should never be instantiated by itself, it can, but for readability one should not
 * No Klarna specific variables, constants or functions should be used outside this class
 *
 * @class Vaimo_Klarna_Model_Api
 */
class Vaimo_Klarna_Model_Api extends Varien_Object
{
    /**
     * @return Vaimo_Klarna_Model_Api_Xmlrpc
     */
    protected function _getKlarnaPaymentMethodXmlRpcApi()
    {
        return Mage::getSingleton('klarna/api_xmlrpc');
    }

    /**
     * @return Vaimo_Klarna_Model_Api_Rest
     */
    protected function _getKlarnaCheckOutRestApi($apiVersion)
    {
        return Mage::getSingleton('klarna/api_rest')->setApiVersion($apiVersion);
    }

    /**
     * @return Vaimo_Klarna_Model_Api_Kco
     */
    protected function _getKlarnaCheckOutOriginalApi()
    {
        return Mage::getSingleton('klarna/api_kco');
    }

    /**
     * @param string|int $storeId
     *
     * @return string|bool
     */
    protected function _getKlarnaCheckOutApiVersion($storeId)
    {
        return Mage::getStoreConfig('payment/vaimo_klarna_checkout/api_version', $storeId);
    }

    /**
     * @param string|int $storeId
     * @param string $method
     * @param null|string $call
     *
     * @return null|Vaimo_Klarna_Model_Api_Kco|Vaimo_Klarna_Model_Api_Rest|Vaimo_Klarna_Model_Api_Xmlrpc
     */
    public function getApiInstance($storeId, $method, $call = NULL)
    {
        switch ($method) {
            case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_INVOICE:
            case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT:
            case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL:
                return $this->_getKlarnaPaymentMethodXmlRpcApi();
                break;
            case Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT:
                $apiVersion = $this->_getKlarnaCheckOutApiVersion($storeId);
                if ($apiVersion == Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_UK ||
                    $apiVersion == Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_USA) {
                    return $this->_getKlarnaCheckOutRestApi($apiVersion);
                } else {
                    switch ($call) {
                        case Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_RESERVE:
                        case Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_KCODISPLAY_ORDER:
                        case Vaimo_Klarna_Helper_Data::KLARNA_API_CALL_KCOCREATE_ORDER:
                            return $this->_getKlarnaCheckOutOriginalApi();
                            break;
                        default:
                            return $this->_getKlarnaPaymentMethodXmlRpcApi();
                            break;
                    }
                }
                break;
        }

        return null;
    }
}
