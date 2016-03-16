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
class MD_Partialpayment_Block_Adminhtml_Report_Grid extends Mage_Adminhtml_Block_Report_Grid_Abstract
{
    protected $_columnGroupBy = 'period';
    public function __construct()
    {
        parent::__construct();
        $this->setCountTotals(true);
        $this->setCountSubTotals(true);
        $this->setEmptyCellLabel(Mage::helper('md_partialpayment')->__('No records found for this period.'));
    }


    public function getResourceCollectionName()
    {
        return 'md_partialpayment/report_collection';
    }
    
    protected function _prepareCollection()
    {
        
        $filterData = $this->getFilterData();
        if ($filterData->getData('from') == null || $filterData->getData('to') == null) {
            $this->setCountTotals(false);
            $this->setCountSubTotals(false);
            return parent::_prepareCollection();
        }
        
        $resourceCollection = Mage::getResourceModel('md_partialpayment/report_collection');
        
            $resourceCollection->setPeriod($filterData->getData('period_type'))
            ->setDateRange($filterData->getData('from', null), $filterData->getData('to', null))
            ->setAggregatedColumns($this->_getAggregatedColumns());
        $this->_addCustomFilter($resourceCollection, $filterData);
        
        if ($this->_isExport) {
            $this->setCollection($resourceCollection);
            return $this;
        }
        
        if ($filterData->getData('show_empty_rows', false)) {
            Mage::helper('reports')->prepareIntervalsCollection(
                $this->getCollection(),
                $filterData->getData('from', null),
                $filterData->getData('to', null),
                $filterData->getData('period_type')
            );
        }
        
        if ($this->getCountTotals()) {
            $totalsCollection = Mage::getResourceModel($this->getResourceCollectionName())
                ->setPeriod($filterData->getData('period_type'))
                ->setDateRange($filterData->getData('from', null), $filterData->getData('to', null))
                ->setAggregatedColumns($this->_getAggregatedColumns())
                ->isTotals(true);

            $this->_addCustomFilter($totalsCollection, $filterData);

            foreach ($totalsCollection as $item) {
                $this->setTotals($item);
                break;
            }
        }
        $this->getCollection()->setColumnGroupBy($this->_columnGroupBy);
        $this->getCollection()->setResourceCollection($resourceCollection);
        
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $helper = Mage::helper('md_partialpayment');
        $this->addColumn('period', array(
                                            'header'            => $helper->__('Period'),
                                            'index'             => 'period',
                                            'width'             => 100,
                                            'sortable'          => false,
                                            'period_type'       => $this->getPeriodType(),
                                            'renderer'          => 'adminhtml/report_sales_grid_column_renderer_date',
                                            'totals_label'      => $helper->__('Total'),
                                            'subtotals_label'   => $helper->__('Subtotal'),
                                            'html_decorators' => array('nobr'),
                                       ));
        $this->addColumn('customer_name', array(
                                             'header'    => $helper->__('Customer Name'),
                                             'sortable'  => false,
                                             'index'     => 'customer_name'
                                        ));
        
        $this->addColumn('customer_email', array(
                                             'header'    => $helper->__('Customer Email'),
                                             'sortable'  => false,
                                             'index'     => 'customer_email'
                                        ));
        
        $currencyCode = $this->getCurrentCurrencyCode();
        $this->addColumn('paid_amount', array(
                                                 'header'        => $helper->__('Paid Amount'),
                                                 'sortable'      => false,
                                                 'type'          => 'currency',
                                                 'renderer'      => 'md_partialpayment/adminhtml_report_renderer_currency',
                                                 'currency_code' => $currencyCode,
                                                 'total'         => 'sum',
                                                 'index'         => 'paid_amount'
                                            ));
        $this->addColumn('due_amount', array(
                                                 'header'        => $helper->__('Due Amount'),
                                                 'sortable'      => false,
                                                 'type'          => 'currency',
                                                 'renderer'      => 'md_partialpayment/adminhtml_report_renderer_currency',
                                                 'currency_code' => $currencyCode,
                                                 'total'         => 'sum',
                                                 'index'         => 'due_amount'
                                            ));
        $this->addColumn('paid_installments', array(
                                             'header'    => $helper->__('Paid Installments'),
                                             'sortable'  => false,
                                             'total'         => 'sum',
                                             'index'     => 'paid_installments'
                                        ));
        
        $this->addColumn('due_installments', array(
                                             'header'    => $helper->__('Due Installments'),
                                             'sortable'  => false,
                                             'total'         => 'sum',
                                             'index'     => 'due_installments'
                                        ));
        
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
    
    protected function _addCustomFilter($collection, $filterData) {
        if ($filterData->getAction()) {
            $action = $filterData->getAction();
            if($action){
                $collection->addFieldToFilter('action',array('eq'=>$action));
            }
        }
        parent::_addCustomFilter($collection, $filterData);
    }
}
