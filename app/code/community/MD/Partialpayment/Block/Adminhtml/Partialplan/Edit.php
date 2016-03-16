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
class MD_Partialpayment_Block_Adminhtml_Partialplan_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    /**
     * constructor
     *
     * @access public
     * @return void
     * @author Mage Delight
     */
    public function __construct() {
	parent::__construct();
	$this->_blockGroup = 'md_partialpayment';
	$this->_controller = 'adminhtml_partialplan';
	$this->_updateButton('save', 'label', Mage::helper('md_partialpayment')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('md_partialpayment')->__('Delete'));
         
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

	$this->_formScripts[] = "
            function saveAndContinueEdit() {
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * get the edit form header
     *
     * @access public
     * @return string
     * @author Mage Delight
     */
    public function getHeaderText() {
	if (Mage::registry('current_partialplan') && Mage::registry('current_partialplan')->getId()) {
	    return Mage::helper('md_partialpayment')->__(
			    "Edit Partial Plan Rule '%s'", $this->escapeHtml(Mage::registry('current_partialplan')->getTitle())
	    );
	} else {
	    return Mage::helper('md_partialpayment')->__('Add Partial Plan Rule');
	}
    }
    
    public function getHeaderCssClass() {
        return 'icon-head head-promo-quote';
    }

}
