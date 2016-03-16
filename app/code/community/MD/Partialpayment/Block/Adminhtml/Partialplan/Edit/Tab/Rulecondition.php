<?php

/**
 * Magedelight
 * Copyright (C) 2014 Magedelight <info@magedelight.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category MD
 * @package MD_Partialpayment
 * @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */
class MD_Partialpayment_Block_Adminhtml_Partialplan_Edit_Tab_Rulecondition extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface {

    public function getTabLabel() {
	return Mage::helper('md_partialpayment')->__('Conditions');
    }

    public function getTabTitle() {
	return Mage::helper('md_partialpayment')->__('Conditions');
    }

    public function canShowTab() {
	return true;
    }

    public function isHidden() {
	return false;
    }

    protected function _prepareForm() {
	$form  = new Varien_Data_Form();
	$model = Mage::registry('md_partialpayment_data');
	
	$model->getConditions()->setJsFormObject('rule_conditions_fieldset');
	$form->setHtmlIdPrefix('rule_');

	$renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
		->setTemplate('promo/fieldset.phtml')
		->setNewChildUrl($this->getUrl('md_partialpayment/adminhtml_partialplan/newConditionHtml/form/rule_conditions_fieldset'
	));

	$fieldset = $form->addFieldset('conditions_fieldset', array('legend'=>Mage::helper('adminhtml')->__('')))->setRenderer($renderer); 
	
	$fieldset->addField('conditions', 'text', array(
	    'name' => 'conditions',
	    'label' => Mage::helper('md_partialpayment')->__('Conditions'),
	    'title' => Mage::helper('md_partialpayment')->__('Conditions'),
	    'required' => true,
	))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));

	$form->setValues($model->getData());
	$this->setForm($form);

	return parent::_prepareForm();
    }

}
