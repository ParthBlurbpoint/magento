<?php

class Bintime_Sinchimport_Model_Resource_Mysql4_Layer_Filter_Feature extends Mage_Core_Model_Mysql4_Abstract
{
    protected $resultTable = 'SinchFilterResult';

    protected static $lastResultTable = false;

    protected $filterAplied = false;

    /**
     * Initialize connection and define main table name
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/ice_feature', 'category_feature_id');
    }

    protected function _getTableName($type, $id = 0)
    {
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
        switch ($type) {
            case 'result':
                    $id = (int)$id;
                    return $tablePrefix . $this->resultTable . "_$id";
                break;
            case 'search':
                    return $tablePrefix . $this->searchTable;
                break;
            default:
                $resource = Mage::getSingleton('core/resource');
                return $resource->getTableName($type);
        }

    }

    /**
     * Подготавливает фильтр к поиску
     *
     * @param Bintime_Icelayered_Model_Layer_Filter_Feature $filter
     * @param string $value Значение, которму должен соответствовать атрибут
     * @return string
     */
    protected function _prepareSearch($filter, $value = null)
    {
        Varien_Profiler::start(__METHOD__);
        $catId = $filter->getLayer()->getCurrentCategory()->getId();
        $connection = $this->_getReadAdapter();

        $cfid = 0;
        if (!is_null($value)) {
            $feature  = $filter->getAttributeModel();
            $cfid = $feature['category_feature_id'];
        }
        $resultTable = $this->_getTableName('result', $cfid);
        //TODO: this table must be temporary
        $sql = "
        CREATE  TABLE IF NOT EXISTS `{$resultTable}`(
	    `entity_id` int(10) unsigned,
            `category_id` int(10) unsigned,
            `product_id` int,
            `sinch_category_id` int,
            `name` varchar(255),
            `image` varchar(255),
            `supplier_id` int,
            `category_feature_id` int,
            `feature_id` int,
            `feature_name` varchar(255),
            `feature_value` text
        );
            ";
        $connection->exec($sql);
       
        $sql = "TRUNCATE TABLE {$resultTable}";
        $connection->exec($sql);

        $featuresTable = $this->_getTableName('FilterListOfFeatures');
        $sql = "TRUNCATE TABLE `$featuresTable`";
        $connection->exec($sql);

        $feature = $filter->getAttributeModel();
        if ($feature['limit_direction'] != 1 && $feature['limit_direction'] != 2) {
            if (!is_null($value)) {
                $sql = "INSERT INTO `$featuresTable` (category_feature_id, feature_value) VALUES (?)";
                $sql = $connection->quoteInto($sql, array($cfid, $value));
                $connection->exec($sql);
            }
            $params = 'null, null';
        }
        else {
            $bounds = explode(',', $value);

            $params = $bounds[0] != '-' ? (int)$bounds[0] : 'null';
            $params .= ', ';
            $params .= $bounds[1] != '-' ? (int)$bounds[1] : 'null';
        }
        //$connection->query("CALL `filter_icecat_products_s`($cfid, $catId,0,$cfid, $params)"));
        $tablePrefix = (string)Mage::app()->getConfig()->getTablePrefix();
        $result = $connection->raw_query("CALL ".$this->_getTableName('filter_sinch_products_s')."($cfid, $catId,0, $cfid, $params, '$tablePrefix')");
        Varien_Profiler::stop(__METHOD__);
        return $resultTable;
    }

    
    /**
     * Apply attribute filter to product collection
     *
     * @param Bintime_Icelayered_Model_Layer_Filter_Feature $filter
     * @param string $value
     * @return Bintime_Icelayered_Model_Resource_Mysql4_Layer_Filter_Feature
     */
    public function applyFilterToCollection($filter, $value)
    {
        Varien_Profiler::start(__METHOD__);
        $searchTable = $this->_prepareSearch($filter, $value);
        self::$lastResultTable = $searchTable;
        
        $collection = $filter->getLayer()->getProductCollection();
        $feature  = $filter->getAttributeModel();
        $connection = $this->_getReadAdapter();

        $collection->getSelect()->join(
            $searchTable,
            "{$searchTable}.entity_id = e.entity_id",
            array()
        );

        Varien_Profiler::stop(__METHOD__);
        return $this;
    }

    /**
     * Retrieve array with products counts per attribute option
     *
     * @param Bintime_Icelayered_Model_Layer_Filter_Feature $filter
     * @return array
     */
    public function getCount($filter)
    {
        Varien_Profiler::start(__METHOD__);

        // clone select from collection with filters
        $select = clone $filter->getLayer()->getProductCollection()->getSelect();
        
        // reset columns, order and limitation conditions
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $connection = $this->_getReadAdapter();
        $feature  = $filter->getAttributeModel();
        $tableAlias = 'idx_' . $feature['category_feature_id'];

        $conditions = array(
            "{$tableAlias}.entity_id = e.entity_id",
            //"{$tableAlias}.category_feature_id = {$feature['category_feature_id']}",
        );
            
        $select->joinInner(
                            array($tableAlias => $this->_getTableName('stINch_products_feature_'.$feature['category_feature_id'])),
                            join(' AND ', $conditions),
                            array('value', 'count' => "COUNT(e.entity_id)")
                          )
            ->group("{$tableAlias}.value");
		
        $tablePattern = $this->_getTableName('stINch_products_feature_'.$feature['category_feature_id']);
        $query = "SHOW TABLES LIKE '$tablePattern'";
        $featureTables = $connection->fetchCol($query);
        $presentFeatures = array();
        foreach($featureTables as $t) {
            if (preg_match("#$tablePattern#", $t, $matches)) {
                $table_feat_exist=true;
            }
        }

        Varien_Profiler::stop(__METHOD__);
	if($table_feat_exist){
	        return $connection->fetchPairs($select);
	}else{
		return null;
	}

    }

    public function getIntervalsCount($filter, $interval)
    {
        Varien_Profiler::start(__METHOD__);

        // clone select from collection with filters
        $select = clone $filter->getLayer()->getProductCollection()->getSelect();

        // reset columns, order and limitation conditions
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        $connection = $this->_getReadAdapter();
        $feature  = $filter->getAttributeModel();
        $tableAlias = 'idx_' . $feature['category_feature_id'];

        $conditions = array(
            "{$tableAlias}.entity_id = e.entity_id",
            //"{$tableAlias}.category_feature_id = {$feature['category_feature_id']}",
        );

        $select->joinInner(
                            array($tableAlias => $this->_getTableName('icecat_products_feature_'.$feature['category_feature_id'])),
                            join(' AND ', $conditions),
                            array('count' => "COUNT(e.entity_id)")
                          );
        if (isset($interval['low'], $interval['high'])) {
            $select->where('CAST('.$tableAlias.'.value AS SIGNED) >= ?', $interval['low'])->where('CAST('.$tableAlias.'.value AS SIGNED) < ?', $interval['high']);
        }
        else if (isset($interval['low'])) {
            $select->where('CAST('.$tableAlias.'.value AS SIGNED) >= ?', $interval['low']);
        }
        else if (isset($interval['high'])) {
            $select->where('CAST('.$tableAlias.'.value AS SIGNED) < ?', $interval['high']);
        }
        $count = $connection->fetchOne($select);
        Varien_Profiler::stop(__METHOD__);
        return $count;
    }

 function getIntervalsCountDescending($filter, $interval)
    {
        Varien_Profiler::start(__METHOD__);

        // clone select from collection with filters
        $select = clone $filter->getLayer()->getProductCollection()->getSelect();

        // reset columns, order and limitation conditions
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        $connection = $this->_getReadAdapter();
        $feature  = $filter->getAttributeModel();
        $tableAlias = 'idx_' . $feature['category_feature_id'];

        $conditions = array(
            "{$tableAlias}.entity_id = e.entity_id",
            //"{$tableAlias}.category_feature_id = {$feature['category_feature_id']}",
        );

        $select->joinInner(
                            array($tableAlias => $this->_getTableName('icecat_products_feature_'.$feature['category_feature_id'])),
                            join(' AND ', $conditions),
                            array('count' => "COUNT(e.entity_id)")
                          );
        if (isset($interval['low'], $interval['high'])) {
            $select->where('CAST('.$tableAlias.'.value AS SIGNED) >= ?', $interval['low'])->where('CAST('.$tableAlias.'.value AS SIGNED) < ?', $interval['high']);
        }
        else if (isset($interval['low'])) {
            $select->where('CAST('.$tableAlias.'.value AS SIGNED) >= ?', $interval['low']);
        }
        else if (isset($interval['high'])) {
            $select->where('CAST('.$tableAlias.'.value AS SIGNED) < ?', $interval['high']);
        }
       $count = $connection->fetchOne($select);

        Varien_Profiler::stop(__METHOD__);
        return $count;
    }



    /*
     * INDEXES FOLLOW.
     */

    /**
     * Запускает процедуры индексации.
     * Слушает событие Mage::dispatchEvent('iceimport_model_import_after',..)
     * @param <type> $observer
     */
    public function reindex($observer)
    {
        $this->splitProductsFeature();
    }
    

    /**
     * Разбивает таблицу icecat_product_feature на таблицы вида icecat_product_feature_%category_feature_id% для каждой фичи.
     * 
     */
    public function splitProductsFeature()
    {
        Mage::log(__METHOD__ . " start at ".  date('d-m-Y H:i:s'), null, 'sinchlayered.log');
        $featureIds = $this->getProductFeatures4indexig();

        $resource = Mage::getSingleton('core/resource');
//        $tProudctsFeature = $resource->getTableName("icecat_products_feature");
        $connection = $this->_getWriteAdapter();

        //удаление таблиц с фичами не используемыми больше для навигации.
        $tablePattern = $resource->getTableName('stINch_products_feature_');
        $query = "SHOW TABLES LIKE '%$tablePattern%'";
        $featureTables = $connection->fetchCol($query);
        $presentFeatures = array();
        foreach($featureTables as $t) {
            if (preg_match("#$tablePattern(\d+)#", $t, $matches)) {
                $presentFeatures[] = $matches[1];
            }
        }

        $features2delete = array_diff($presentFeatures, $featureIds);
        if (count($features2delete)) {
            foreach ($features2delete as & $drop) {
                $drop = $tablePattern . $drop;
            }
            $dropSql = "DROP TABLE " . implode(',', $features2delete);
            $connection->exec($dropSql);
        }
        //
        
        //Создание таблиц с фичами используемыми в навигации.
        $i = 0; $storeId = Mage::app()->getStore()->getId(); $websiteId = Mage::app()->getStore(true)->getWebsite()->getId();
        foreach ($featureIds as $featureId) {
            $tFeature = $resource->getTableName("stINch_products_feature_$featureId");
	    $query = "DROP TABLE IF EXISTS $tFeature";
	    $connection->exec($query);

            $query = "CREATE TABLE IF NOT EXISTS $tFeature (
			  `entity_id` int(11) default NULL,
			  `feature_id` int(11) NOT NULL,
			  `product_id` int(11) default NULL,
			  `category_feature_id` int(11) default NULL,
			  `value` text,
			  `presentation_value` text,
			  INDEX (`feature_id`),
			  KEY `category_feature_id` (`category_feature_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8
            ";
            $connection->exec($query);

            $query = "TRUNCATE TABLE $tFeature";
            $connection->exec($query);

            /*$query = "
                INSERT INTO $tFeature (feature_id, product_id, category_feature_id, value, presentation_value)
                SELECT feature_id, product_id, category_feature_id, value, presentation_value FROM $tProudctsFeature
                WHERE category_feature_id = $featureId
                ";*/
            $query = "
                      REPLACE INTO $tFeature (entity_id,feature_id, product_id, category_feature_id, value, presentation_value)
                      SELECT E.entity_id, RV.category_feature_id,E.entity_id, RV.category_feature_id, RV.text , RV.text
                      FROM ".Mage::getSingleton('core/resource')->getTableName('catalog_product_entity')." E
                      INNER JOIN ".Mage::getSingleton('core/resource')->getTableName('catalog_category_product_index')." PCind
                      ON (E.entity_id = PCind.product_id AND PCind.store_id='{$storeId}' AND PCind.visibility IN(2,4))
                      INNER JOIN ".Mage::getSingleton('core/resource')->getTableName('catalog_product_index_price')." AS price_index 
                      ON price_index.entity_id = E.entity_id AND price_index.website_id = '{$websiteId}' AND price_index.customer_group_id = 0
                      INNER JOIN ".Mage::getSingleton('core/resource')->getTableName('stINch_products')." PR
                      ON (PR.store_product_id = E.store_product_id)
                      INNER JOIN ".Mage::getSingleton('core/resource')->getTableName('stINch_product_features')." PF
                      ON (PR.sinch_product_id = PF.sinch_product_id )
                      INNER JOIN ".Mage::getSingleton('core/resource')->getTableName('stINch_restricted_values')." RV 
                      ON (PF.restricted_value_id=RV.restricted_value_id AND RV.category_feature_id= $featureId)
                      GROUP BY E.entity_id;
                    ";
            $connection->exec($query);
        }
        Mage::log(__METHOD__ . " end at ".  date('d-m-Y H:i:s'), null, 'sinchlayered.log');
    }

    /**
     * Список фич подлежащих индексации.
     * 
     * @return array
     */
    private function getProductFeatures4indexig()
    {
        $connection = $this->_getReadAdapter();

        $tCatFeature =  Mage::getSingleton('core/resource')->getTableName('stINch_categories_features');
        $query = "SELECT category_feature_id FROM $tCatFeature ";

        return $connection->fetchCol($query);
    }
}
