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
class MD_Partialpayment_Block_Adminhtml_Partialplan_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

    /**
     * Initialize Tabs
     *
     * @access public
     * @author Mage Delight
     */
    public function __construct() {
	parent::__construct();
	$this->setId('partialplan_tabs');
	$this->setDestElementId('edit_form');
	$this->setTitle(Mage::helper('md_partialpayment')->__('Partial Plan Rule'));
    }

    /**
     * before render html
     *
     * @access protected
     * @return MD_Salesbycat_Block_Adminhtml_Mdsalescat_Edit_Tabs
     * @author Mage Delight
     */
    protected function _beforeToHtml() {
	$this->addTab(
		'form_partialplan', array(
	    'label' => Mage::helper('md_partialpayment')->__('Rule Information'),
	    'title' => Mage::helper('md_partialpayment')->__('Rule Information'),
	    'content' => $this->getLayout()->createBlock('md_partialpayment/adminhtml_partialplan_edit_tab_form')
		    ->toHtml(),
		)
	);

	$this->addTab(
		'conditions', array(
	    'label' => Mage::helper('md_partialpayment')->__('Conditions'),
	    'title' => Mage::helper('md_partialpayment')->__('Conditions'),
	    'content' => $this->getLayout()->createBlock('md_partialpayment/adminhtml_partialplan_edit_tab_conditionstab')
		    ->toHtml(),
		)
	);

	return parent::_beforeToHtml();
    }

    /**
     * Retrieve mdsalescat entity
     *
     * @access public
     * @return MD_Salesbycat_Model_Mdsalescat
     * @author Mage Delight
     */
    public function getMdsalescat() {
	return Mage::registry('current_partialplan');
    }

}
