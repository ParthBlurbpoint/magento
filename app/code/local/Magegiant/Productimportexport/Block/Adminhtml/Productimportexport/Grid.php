<?php
/**
 * Magegiant
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magegiant
 * @package     Magegiant_Productimportexport
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Productimportexport Grid Block
 * 
 * @category    Magegiant
 * @package     Magegiant_Productimportexport
 * @author      Magegiant Developer
 */
class Magegiant_Productimportexport_Block_Adminhtml_Productimportexport_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('productimportexportGrid');
        $this->setDefaultSort('productimportexport_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }
    
    /**
     * prepare collection for block to display
     *
     * @return Magegiant_Productimportexport_Block_Adminhtml_Productimportexport_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('productimportexport/productimportexport')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    /**
     * prepare columns for this grid
     *
     * @return Magegiant_Productimportexport_Block_Adminhtml_Productimportexport_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('productimportexport_id', array(
            'header'    => Mage::helper('productimportexport')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'productimportexport_id',
        ));

        $this->addColumn('title', array(
            'header'    => Mage::helper('productimportexport')->__('Title'),
            'align'     =>'left',
            'index'     => 'title',
        ));

        $this->addColumn('content', array(
            'header'    => Mage::helper('productimportexport')->__('Item Content'),
            'width'     => '150px',
            'index'     => 'content',
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('productimportexport')->__('Status'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'status',
            'type'        => 'options',
            'options'     => array(
                1 => 'Enabled',
                2 => 'Disabled',
            ),
        ));

        $this->addColumn('action',
            array(
                'header'    =>    Mage::helper('productimportexport')->__('Action'),
                'width'        => '100',
                'type'        => 'action',
                'getter'    => 'getId',
                'actions'    => array(
                    array(
                        'caption'    => Mage::helper('productimportexport')->__('Edit'),
                        'url'        => array('base'=> '*/*/edit'),
                        'field'        => 'id'
                    )),
                'filter'    => false,
                'sortable'    => false,
                'index'        => 'stores',
                'is_system'    => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('productimportexport')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('productimportexport')->__('XML'));

        return parent::_prepareColumns();
    }
    
    /**
     * prepare mass action for this grid
     *
     * @return Magegiant_Productimportexport_Block_Adminhtml_Productimportexport_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('productimportexport_id');
        $this->getMassactionBlock()->setFormFieldName('productimportexport');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'        => Mage::helper('productimportexport')->__('Delete'),
            'url'        => $this->getUrl('*/*/massDelete'),
            'confirm'    => Mage::helper('productimportexport')->__('Are you sure?')
        ));

//        $statuses = Mage::getSingleton('productimportexport/status')->getOptionArray();

//        array_unshift($statuses, array('label'=>'', 'value'=>''));
//        $this->getMassactionBlock()->addItem('status', array(
//            'label'=> Mage::helper('productimportexport')->__('Change status'),
//            'url'    => $this->getUrl('*/*/massStatus', array('_current'=>true)),
//            'additional' => array(
//                'visibility' => array(
//                    'name'    => 'status',
//                    'type'    => 'select',
//                    'class'    => 'required-entry',
//                    'label'    => Mage::helper('productimportexport')->__('Status'),
////                    'values'=> $statuses
//                ))
//        ));
        return $this;
    }
    
    /**
     * get url for each row in grid
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}