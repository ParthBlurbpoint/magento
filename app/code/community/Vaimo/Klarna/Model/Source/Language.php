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

class Vaimo_Klarna_Model_Source_Language extends Vaimo_Klarna_Model_Source_Abstract
{

    public function toOptionArray()
    {
        return array(
            array('value' => '',   'label' => $this->_getCoreHelper()->__('Default')),
            array('value' => 'nb', 'label' => $this->_getCoreHelper()->__('Norwegian')),
            array('value' => 'sv', 'label' => $this->_getCoreHelper()->__('Swedish')),
            array('value' => 'fi', 'label' => $this->_getCoreHelper()->__('Finnish')),
            array('value' => 'dk', 'label' => $this->_getCoreHelper()->__('Danish')),
            array('value' => 'nl', 'label' => $this->_getCoreHelper()->__('Dutch')),
            array('value' => 'de', 'label' => $this->_getCoreHelper()->__('German')),
        );
    }

}
