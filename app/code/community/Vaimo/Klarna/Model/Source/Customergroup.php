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

class Vaimo_Klarna_Model_Source_Customergroup extends Vaimo_Klarna_Model_Source_Abstract
{
    /**
     * Customer groups options array
     *
     * @var null|array
     */
    protected $_options;
    
    protected function _getCustomerGroups()
    {
        return Mage::getResourceModel('customer/group_collection')
                ->loadData()->toOptionArray();
    }

    /**
     * Retrieve customer groups as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = $this->_getCustomerGroups();

            //Preserve original sort order
            $tmpOptions = array();
            foreach($this->_options as $option) {
                if (empty($tmpOptions)) {
                    $tmpOptions[] = array('value' => Vaimo_Klarna_Helper_Data::KLARNA_CHECKOUT_ALLOW_ALL_GROUP_ID,
                                        'label' => $this->_getHelper()->__('All customer groups'));
                }
                $tmpOptions[] = $option;
            }

            $this->_options = $tmpOptions;
        }

        return $this->_options;
    }
}