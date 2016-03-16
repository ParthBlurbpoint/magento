<?php
/**
 * Catalog Layer Price Filter resource model
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Sergey Stepanchuk 
 */
class  Bintime_Sinchimport_Model_Resource_Layer_Filter_Price extends Mage_Catalog_Model_Resource_Eav_Mysql4_Layer_Filter_Price
{
    public function applyFilterToCollectionMinMaxPrice($filter, $minPrice, $maxPrice)
    {
        $collection = $filter->getLayer()->getProductCollection();
        $collection->addPriceData($filter->getCustomerGroupId(), $filter->getWebsiteId());

        $select     = $collection->getSelect();
        $response   = $this->_dispatchPreparePriceEvent($filter, $select);

        $table      = $this->_getIndexTableAlias();
        $additional = join('', $response->getAdditionalCalculations());
        $rate       = $filter->getCurrencyRate();
        $priceExpr  = new Zend_Db_Expr("(({$table}.min_price {$additional}) * {$rate})");

        $select->where($priceExpr . ' >= ?', $minPrice);
        if($maxPrice!='*'){
           $select ->where($priceExpr . ' < ?', $maxPrice);
        }
        return $this;
    }

    public function getCountMinMaxPrice($filter, $minPrice, $maxPrice)
    {
        $select     = $this->_getSelect($filter);
        $connection = $this->_getReadAdapter();
        $response   = $this->_dispatchPreparePriceEvent($filter, $select);
        $table      = $this->_getIndexTableAlias();

        $additional = join('', $response->getAdditionalCalculations());
        $rate       = $filter->getCurrencyRate();

        /**
         * Check and set correct variable values to prevent SQL-injections
         */
        $rate       = floatval($rate);




        $countExpr  = new Zend_Db_Expr('COUNT(*)');
        $rangeExpr  = new Zend_Db_Expr("FLOOR(({$table}.min_price {$additional}) * {$rate}) ");
        $select->where($rangeExpr . ' >= ?', $minPrice);
        if($maxPrice!='*'){
             $select->where($rangeExpr . ' < ?', $maxPrice);
        }
        $select->columns(array(
                    'count' => $countExpr
                    ));

        $count=$connection->fetchPairs($select);
        return key($count);
    }
    
    protected function _getSelect($filter)
    {
        $collection = $filter->getLayer()->getProductCollection();
        $collection->addPriceData($filter->getCustomerGroupId(), $filter->getWebsiteId());

        // clone select from collection with filters
        $select = clone $collection->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        return $select;
    }

}
