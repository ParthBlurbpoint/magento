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
  * This function is no longer used! It will not be removed, as it was once used as a frontend model
  * If an attribute exists that has this as it's frontend model it will cause a crash if removed
  *
  */

class Vaimo_Klarna_Block_System_Config_Form_Field_Position extends Mage_Core_Block_Html_Select
{
    public function getOptions()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'label' => Mage::helper('klarna')->__('GUI - Options'),
                    'value' =>  Vaimo_Klarna_Helper_Data::KLARNA_EXTRA_VARIABLES_GUI_OPTIONS
                ),
                array(
                    'label' => Mage::helper('klarna')->__('GUI - Layout'),
                    'value' =>  Vaimo_Klarna_Helper_Data::KLARNA_EXTRA_VARIABLES_GUI_LAYOUT
                ),
                array(
                    'label' => Mage::helper('klarna')->__('Options'),
                    'value' =>  Vaimo_Klarna_Helper_Data::KLARNA_EXTRA_VARIABLES_OPTIONS
                ),
            );
        }

        return $this->_options;
    }

    public function getName()
    {
        return $this->getData('name') ? $this->getData('name') : $this->getInputName();
    }
}