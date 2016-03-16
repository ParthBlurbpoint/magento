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
class MD_Partialpayment_Block_Adminhtml_Partialplan_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    protected $_helper = null;

    public function __construct() {
	parent::__construct();
	$this->_helper = Mage::helper('md_partialpayment');
	$this->setId('partialplanGrid');
	$this->setUseAjax(false);
	$this->setDefaultSort('rule_id');
	$this->setDefaultDir('DESC');
	$this->setSaveParametersInSession(true);
    }

    public function _prepareCollection() {
	$collection = Mage::getModel('md_partialpayment/rule')->getCollection();
	$this->setCollection($collection);
	return parent::_prepareCollection();
    }
    
    protected function _prepareColumns() {
	$this->addColumn('rule_id', array(
	    'header' => $this->_helper->__('Id'),
	    'index'  => 'rule_id',
	    'width'  => '50px',
	    'type'   => 'number',
	));

	$this->addColumn('title', array(
	    'header' => $this->_helper->__('Title'),
	    'index'  => 'title',
	));

	$this->addColumn('rule_status', array(
	    'header'  => $this->_helper->__('Status'),
	    'index'   => 'rule_status',
	    'type'    => 'options',
	    'options' => array(
		1     => Mage::helper('md_partialpayment')->__('Enabled'),
		2     => Mage::helper('md_partialpayment')->__('Disabled'),
	    ),
	));

	$this->addColumn('initial_payment_amount_type', array(
	    'header'  => $this->_helper->__('Initial Payment Amount Type'),
	    'index'   => 'initial_payment_amount_type',
	    'values'  => Mage::getModel('md_partialpayment/system_config_source_payment_type')->toOptionArray(),
	    'type'    => 'options',
	    'options' => Mage::getModel('md_partialpayment/system_config_source_payment_type')->toOptionArray(),
	    'width'  => '50px',   
	));

	$store = $this->_getStore();
	$this->addColumn('initial_payment_amount', array(
	    'header'	    => $this->_helper->__('Initial Payment Amount'),
	    'index'	    => 'initial_payment_amount',
	    'type'	    => 'price',
	    'currency_code' => $store->getBaseCurrency()->getCode(),
	));
	
	$this->addColumn('priority', array(
	    'header' => $this->_helper->__('Priority'),
	    'index'  => 'priority',
	    'width'  => '50px',
	    'type'   => 'number',
	));

	$this->addColumn('action', array(
	    'header'  => Mage::helper('md_partialpayment')->__('Action'),
	    'type'    => 'action',
	    'getter'  => 'getId',
	    'actions' => array (
		array (
		    'caption'	  => Mage::helper('md_partialpayment')->__('View'),
		    'url'	  => array('base' => 'md_partialpayment/adminhtml_partialplan/edit'),
		    'field'	  => 'id',
		    'data-column' => 'action',
		)
	    ),
	    'filter'    => false,
	    'sortable'  => false,
	    'index'	=> 'stores',
	    'is_system' => true,
	    'width'     => '50px',
	));
	
	return parent::_prepareColumns();
    }

    protected function _prepareMassaction() {
	$this->setMassactionIdField('payment_id');
	$this->getMassactionBlock()->setFormFieldName('partialpayment');
	
	$this->getMassactionBlock()->addItem('delete', array(
	    'label'   => $this->_helper->__('Delete'),
	    'url'     => $this->getUrl('*/*/massDelete'),
	    'confirm' => 'Are you sure?'
	));
	
	$statuses = array(
		1 => Mage::helper('md_partialpayment')->__('Enabled'),
		2 => Mage::helper('md_partialpayment')->__('Disabled'),
	);

        array_unshift($statuses, array('label'=>'', 'value'=>''));
	
        $this->getMassactionBlock()->addItem('status', array(
             'label'		  => Mage::helper('md_partialpayment')->__('Change status'),
             'url'		  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional'         => array(
                    'visibility'  => array(
                         'name'   => 'status',
                         'type'   => 'select',
                         'class'  => 'required-entry',
                         'label'  => Mage::helper('md_partialpayment')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
	
	return parent::_prepareMassaction();
    }

    public function decorateAmount($value, $row, $column, $isExport) {
	$storeId = $row->getStoreId();

	return Mage::helper('core')->currencyByStore($value, $storeId, true, false);
    }

    public function getRowUrl($row) {
	return $this->getUrl('md_partialpayment/adminhtml_partialplan/edit', array('id' => $row->getId()));
    }

    protected function _getStore() {
	$storeId = (int) $this->getRequest()->getParam('store', 0);
	return Mage::app()->getStore($storeId);
    }    
}
