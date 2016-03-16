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
class MD_Partialpayment_Block_Adminhtml_Partialplan_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
    protected function _prepareForm() {
	$form = new Varien_Data_Form(array(
	    'id'     => 'edit_form',
	    'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
	    'method' => 'post',
	    'enctype' => 'multipart/form-data'
		)
	);

	$form->setUseContainer(true);
	$this->setForm($form);
	return parent::_prepareForm();
    }

}
