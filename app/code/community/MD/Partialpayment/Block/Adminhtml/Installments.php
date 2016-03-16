<?php
class MD_Partialpayment_Block_Adminhtml_Installments extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $this->setElement($element);
        $output = '<script type="text/javascript">//<![CDATA[' . "\n";
        $output .= '    var xml_form_template = \'' . str_replace("'", "\'", $this->_getRowEditHtml()) .'\';' . "\n";
        $output .= 'Validation.add(\'validate-partial-price\',\'\')';
        $output .= '//]]></script>' . "\n";
        $output .= '<input type="hidden" name="' . $this->getElement()->getName() . '" value="">';
        $output .= '<table id="xml_container" style="border-collapse:collapse;"><tbody>';
        $output .= $this->_getHeaderHtml();
        if ($this->getElement()->getData('value')) {
            foreach ($this->getElement()->getData('value/price_type') as $elementIndex => $elementName) {
                $output .= $this->_getRowHtml($elementIndex);
            }
        }
        $output .= '<tr><td colspan="3" style="padding: 4px 0;">';
        $output .= $this->_getAddButtonHtml();
        $output .= '</td></tr>';
        $output .= '</tbody></table>';
        $output .= '<p class="note"><span>'.Mage::helper('md_partialpayment')->__('Minimum installments should be greater than one.').'</span></p>';
        return $output;
    }
    
    protected function _getHeaderHtml() {
        $output = '<tr>';
        $output .= '<th style="padding: 2px; text-align: center;width:75px;">';
        $output .= Mage::helper('md_partialpayment')->__('No of Installments');
        $output .= '</th>';
        $output .= '<th style="padding: 2px; text-align: center;width:95px;">';
        $output .= Mage::helper('md_partialpayment')->__('Additional Fee Type');
        $output .= '</th>';
        $output .= '<th style="padding: 2px; text-align: center;width:75px;">';
        $output .= Mage::helper('md_partialpayment')->__('Additional Fee');
        $output .= '</th>';
        $output .= '<th>&nbsp;</th>';
        $output .= '</tr>';
        return $output;
    }
    
    protected function _getRowHtml($index = 0) {
        $priceType = $this->getElement()->getData('value/price_type/' . $index);
        $output = '<tr>';
        $output .= '<td style="padding: 2px 0;">';
        $output .= '<input type="text" class="input-text required-entry validate-greater-than-zero" style="margin-right:10px;width:75px;" name="' . $this->getElement()->getName() . '[unit][]" value="' . $this->getElement()->getData('value/unit/' . $index) . '" />';
        $output .= '</td>';
        $output .= '<td style="padding: 2px 0;">';
        $output .= '<select name="' . $this->getElement()->getName() . '[price_type][]"  style="margin-right:10px;width:95px;">';
        $output .= '<option value="0"';
        if($priceType == 0){
            $output .= ' selected="selected"';
        }
        $output .= '>'.Mage::helper('md_partialpayment')->__('Fixed').'</option>';
        $output .= '<option value="1"';
        if($priceType == 1){
            $output .= ' selected="selected"';
        }
        $output .= '>'.Mage::helper('md_partialpayment')->__('Percentage').'</option>';
        $output .= '</select>';
        $output .= '</td>';
        $output .= '<td style="padding: 2px 0;">';
        $output .= '<input type="text" class="input-text required-entry validate-zero-or-greater" style="margin-right:10px;width:75px;" name="' . $this->getElement()->getName() . '[price][]" value="' . $this->getElement()->getData('value/price/' . $index) . '" />';
        $output .= '</td>';
        $output .= '<td style="padding: 2px 4px;width:75px;">';
        $output .= $this->_getRemoveButtonHtml();
        $output .= '</td>';
        $output .= '</tr>';
        return $output;
    }
    
    protected function _getRowEditHtml() {
        $output = '<tr>';
        $output .= '<td style="padding: 2px 0;">';
        $output .= '<input type="text" class="input-text required-entry validate-greater-than-zero" style="margin-right:10px;width:75px;" name="' . $this->getElement()->getName() . '[unit][]" />';
        $output .= '</td>';
        $output .= '<td style="padding: 2px 0;">';
        $output .= '<select name="' . $this->getElement()->getName() . '[price_type][]"  style="margin-right:10px;width:75px;" >';
        $output .= '<option value="0">'.Mage::helper('md_partialpayment')->__('Fixed').'</option>';
        $output .= '<option value="1">'.Mage::helper('md_partialpayment')->__('Percentage').'</option>';
        $output .= '</select>';
        $output .= '</td>';
        $output .= '<td style="padding: 2px 0;">';
        $output .= '<input type="text" class="input-text required-entry validate-zero-or-greater" style="margin-right:10px;width:75px;" name="' . $this->getElement()->getName() . '[price][]" />';
        $output .= '</td>';
        $output .= '<td style="padding: 2px 4px;width:75px;">';
        $output .= $this->_getRemoveButtonHtml();
        $output .= '</td>';
        $output .= '</tr>';
        return $output;
    }
    
    protected function _getAddButtonHtml() {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('add')
            ->setLabel($this->__('Add Installments'))
            ->setOnClick("Element.insert($(this).up('tr'), {before: xml_form_template});")
            ->toHtml();
    }

    protected function _getRemoveButtonHtml() {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('delete v-middle')
            ->setLabel($this->__('Delete'))
            ->setOnClick("Element.remove($(this).up('tr'))")
            ->toHtml();
    }
}

