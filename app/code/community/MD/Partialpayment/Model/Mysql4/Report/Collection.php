<?php
class MD_Partialpayment_Model_Mysql4_Report_Collection extends Mage_Sales_Model_Mysql4_Report_Collection_Abstract
{
    public function __construct()
    {
        //parent::_construct();
        
        //$this->_init('md_partialpayment/report_collection');
        $this->setModel('adminhtml/report_item');
        
        $this->_resource = Mage::getResourceModel('md_partialpayment/payments');
        
        $this->setConnection($this->getResource()->getReadConnection());
        //die("Hello");
        $this->_applyFilters = false;
    }
    
    protected function _getSelectedColumns()
    {
        
        $adapter = $this->getConnection();
        
        if ('month' == $this->_period) {
            $this->_periodFormat = $adapter->getDateFormatSql('updated_at', '%Y-%m');
        } elseif ('year' == $this->_period) {
            $this->_periodFormat = $adapter->getDateExtractSql('updated_at', Varien_Db_Adapter_Interface::INTERVAL_YEAR);
        } else {
            $this->_periodFormat = $adapter->getDateFormatSql('updated_at', '%Y-%m-%d');
        }
        
        if (!$this->isTotals() && !$this->isSubTotals()) {
                $this->_selectedColumns = array(
                    'period'         =>  $this->_periodFormat,
                    'customer_name'    => 'customer_name',
                    'customer_email'    => 'customer_email',
                    'paid_installments' => 'SUM(paid_installments)',
                    'due_installments' => 'SUM(due_installments)',
                    'paid_amount'     => 'SUM(paid_amount)',
                    'due_amount'   => 'SUM(due_amount)'
                );
            }
            
        if ($this->isTotals()) {
            $this->_selectedColumns = $this->getAggregatedColumns();
        }
        if ($this->isSubTotals()) {
            $this->_selectedColumns = $this->getAggregatedColumns() + array('period' => $this->_periodFormat);
        }
        $this->_selectedColumns['period'] = $this->_periodFormat;
        return $this->_selectedColumns;
    }
    
    protected function _initSelect()
    {
        $this->getSelect()->from(
            array('main_table'=>$this->getResource()->getMainTable()),
            $this->_getSelectedColumns()
       );
                /*$this->getSelect()->join(
             array('ac'=>$this->getTable('md_partialpayment/summary')),'main_table.payment_id=ac.payment_id',array()  
       );*/
                /*$this->getSelect()->join(
             array('oI'=>$this->getTable('sales/order_item')),'main_table.order_item_id=oI.item_id',array()  
       );*/
        if ($this->isSubTotals()) {
            $this->getSelect()->group($this->_periodFormat);
        } else {
            if (!$this->isTotals()) {
                $this->getSelect()->group(
                    array(
                         'updated_at',
                         //'code'
                    )
                );
            }
        }
       return $this; 
    }
    
    protected function _applyDateRangeFilter()
    {
        
        if (!is_null($this->_from)) {
            $this->getSelect()->where('updated_at >= ?', $this->_from);
        }
        if (!is_null($this->_to)) {
            $this->getSelect()->where('updated_at <= ?', $this->_to);
        }
        return $this;
    }
}
