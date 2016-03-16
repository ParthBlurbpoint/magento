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

abstract class Vaimo_Klarna_Model_Api_Abstract extends Varien_Object
{
    protected $_klarnaSetup = NULL;

    protected $_transport = NULL;

    public function setTransport($model)
    {
        $this->_transport = $model;
    }
    
    protected function _getTransport()
    {
        return $this->_transport;
    }
    
    /**
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/cart')->getQuote();
    }

    protected function _isMobile()
    {
        if (@class_exists('Mobile_Detect')) {
            $detect = new Mobile_Detect();
            return $detect->isMobile();
        }

        return false;
    }

    protected function _addUserDefinedVariables(&$create)
    {
        $json = $this->_getTransport()->getConfigData('user_defined_json');
        if ($json && $json != " ") {
            $extras = Mage::helper('klarna')->JsonDecode($json);
            if (is_array($extras)) {
                $create = array_merge_recursive($create, $extras);
            } else {
                Mage::helper('klarna')->logDebugInfo($extras . ": " . $json);
            }
        }
    }
}
