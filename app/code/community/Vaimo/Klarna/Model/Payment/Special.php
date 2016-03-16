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

class Vaimo_Klarna_Model_Payment_Special extends Vaimo_Klarna_Model_Payment_Abstract
{
    protected $_code = Vaimo_Klarna_Helper_Data::KLARNA_METHOD_SPECIAL;
    protected $_formBlockType = 'klarna/form_special';
    protected $_infoBlockType = 'klarna/info_special';

    public function isAvailable( $quote = NULL )
    {
        if (parent::isAvailable($quote)==false) return false;
        try {
            $klarna = $this->_getKlarnaModel();
            $klarna->setQuote($quote, $this->_code);

            $res = $klarna->getValidCheckoutPClasses($this->_code);

            if (!$res) {
                return false;
            }

        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->logKlarnaException($e);
            return false;
        }
        return true;
    }
}
