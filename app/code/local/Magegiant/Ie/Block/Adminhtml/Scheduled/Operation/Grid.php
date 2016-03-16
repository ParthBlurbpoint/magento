<?php
/**
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the  License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @copyright   Copyright (c) 2014 Magegiant
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Scheduled operation grid
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Initialize grid object
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('operationGrid');
        $this->_controller = 'adminhtml_scheduled_operation';
        $this->setUseAjax(true);

        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
    }

    /**
     * Prepare grid collection object
     *
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('magegiant_ie/scheduled_operation_collection');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Grid columns definition
     *
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('name', array(
            'header'        => Mage::helper('magegiant_ie')->__('Name'),
            'index'         => 'name',
            'type'          => 'text',
            'escape'        => true
        ));

        $dataModel = Mage::getSingleton('magegiant_ie/scheduled_operation_data');
        $this->addColumn('operation_type', array(
            'header'        => Mage::helper('magegiant_ie')->__('Operation'),
            'width'         => '30px',
            'index'         => 'operation_type',
            'type'          => 'options',
            'options'       => $dataModel->getOperationsOptionArray()
        ));

//        $this->addColumn('entity_type', array(
//            'header'        => Mage::helper('magegiant_ie')->__('Entity type'),
//            'index'         => 'entity_type',
//            'type'          => 'options',
//            'options'       => $dataModel->getEntitiesOptionArray()
//        ));

        $this->addColumn('last_run_date', array(
            'header'        => Mage::helper('magegiant_ie')->__('Last Run Date'),
            'index'         => 'last_run_date',
            'type'          => 'datetime'
        ));

        $this->addColumn('freq', array(
            'header'        => Mage::helper('magegiant_ie')->__('Frequency'),
            'index'         => 'freq',
            'type'          => 'options',
            'options'       => $dataModel->getFrequencyOptionArray(),
            'width'         => '100px'
        ));

        $this->addColumn('status', array(
            'header'        => Mage::helper('magegiant_ie')->__('Status'),
            'index'         => 'status',
            'type'          => 'options',
            'options'       => $dataModel->getStatusesOptionArray()
        ));

        $this->addColumn('is_success', array(
            'header'        => Mage::helper('magegiant_ie')->__('Last Outcome'),
            'index'         => 'is_success',
            'type'          => 'options',
            'width'         => '200px',
            'options'       => $dataModel->getResultOptionArray()
        ));

        $this->addColumn('action', array(
            'header'    => Mage::helper('magegiant_ie')->__('Action'),
            'width'     => '50px',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => array(
                array(
                    'caption' => Mage::helper('magegiant_ie')->__('Edit'),
                    'url'     => array(
                        'base'=>'*/*/edit',
                    ),
                    'field'   => 'id'
                ),
                array(
                    'caption' => Mage::helper('magegiant_ie')->__('Run'),
                    'url'     => array(
                        'base'=> '*/scheduled_operation/cron',
                    ),
                    'field'   => 'operation'
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'index'     => 'id',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Get row url
     *
     * @param Magegiant_Ie_Model_Scheduled_Operation
     * @return string
     */
    public function getRowUrl($operation)
    {
        return $this->getUrl('*/*/edit', array(
            'id' => $operation->getId(),
        ));
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    /**
     * Prepare batch actions
     *
     * @return Magegiant_Ie_Block_Adminhtml_Scheduled_Operation_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('operation');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('magegiant_ie')->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('magegiant_ie')->__('Are you sure you want to delete the selected scheduled imports/exports?')
        ));

        $statuses = Mage::getSingleton('magegiant_ie/scheduled_operation_data')
            ->getStatusesOptionArray();
        $this->getMassactionBlock()->addItem('status', array(
            'label'=> Mage::helper('magegiant_ie')->__('Change status'),
            'url'  => $this->getUrl('*/*/massChangeStatus', array('_current' => true)),
            'additional' => array(
               'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('magegiant_ie')->__('Status'),
                    'values' => $statuses
                )
             )
        ));

        return $this;
    }
}
