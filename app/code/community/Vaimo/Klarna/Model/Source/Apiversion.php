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
class Vaimo_Klarna_Model_Source_Apiversion extends Vaimo_Klarna_Model_Source_Abstract
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_STD, 'label' => $this->_getHelper()->__('KCO V.2')),
            array('value' => Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_UK,  'label' => $this->_getHelper()->__('KCO V.3 (UK)')),
            array('value' => Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_USA, 'label' => $this->_getHelper()->__('KCO V.3.1')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_STD => $this->_getHelper()->__('KCO V.2'),
            Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_UK  => $this->_getHelper()->__('KCO V.3 (UK)'),
            Vaimo_Klarna_Helper_Data::KLARNA_KCO_API_VERSION_USA => $this->_getHelper()->__('KCO V.3.1'),
        );
    }
}