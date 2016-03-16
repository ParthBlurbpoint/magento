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

class Vaimo_Klarna_Block_System_Config_Form_Field_Position_Attribute_Set extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_positionRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn('position', array(
            'label' => Mage::helper('klarna')->__('Position'),
            'renderer' => $this->_getPositionRenderer(),
        ));

        $this->addColumn('key', array(
            'label' => Mage::helper('klarna')->__('Key'),
            'style' => 'width:80px',
        ));

        $this->addColumn('value', array(
            'label' => Mage::helper('klarna')->__('Value'),
            'style' => 'width:80px',

        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('klarna')->__('Add Value');
    }

    protected function _getPositionRenderer()
    {
        if (!$this->_positionRenderer) {
            $this->_positionRenderer = $this->getLayout()->createBlock(
                'klarna/system_config_form_field_position', '',
                array('is_render_to_js_template' => true)
            );
            $this->_positionRenderer->setExtraParams('style="width:150px"');
        }

        return $this->_positionRenderer;
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        // Setting select/option data, so that the correct options would be selected after load
        foreach ($row->getData() as $key => $value) {
            if (!empty($this->_columns[$key]['renderer'])) {
                $renderer = $this->_columns[$key]['renderer'];
                if ($renderer instanceof Mage_Core_Block_Html_Select) {
                    $hash = $renderer->calcOptionHash($value);
                    $row->setData('option_extra_attr_' . $hash, 'selected');
                }
            }
        }
    }
}