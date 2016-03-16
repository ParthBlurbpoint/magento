<?php
/**
 * 
 * @author Kodematix
 *
 */
?>
<?php 
class Kodematix_ProductGift_Adminhtml_Block_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid
{
	 protected function _prepareCollection()
    {
		/* To override the admin product grid */
		$store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('sku_of_product_gift')
            ->addAttributeToSelect('is_product_gift_enabled');
            // ->addAttributeToSelect('sku_of_product_gift');
 
        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $collection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        }
        if ($store->getId()) {
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $adminStore
            );
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
        }
        else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
		
        $this->setCollection($collection);
		/* To make the admin product grid filters work - start*/
        if ($this->getCollection()) {

            $this->_preparePage();

            $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
            $dir      = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
            $filter   = $this->getParam($this->getVarNameFilter(), null);

            if (is_null($filter)) {
                $filter = $this->_defaultFilter;
            }

            if (is_string($filter)) {
                $data = array();
                $filter = base64_decode($filter);
                parse_str(urldecode($filter), $data);
                $this->_setFilterValues($data);
            } else if ($filter && is_array($filter)) {
                $this->_setFilterValues($filter);
            } else if(0 !== sizeof($this->_defaultFilter)) {
                $this->_setFilterValues($this->_defaultFilter);
            }

            if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
                $dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
                $this->_columns[$columnId]->setDir($dir);
                $column = $this->_columns[$columnId]->getFilterIndex() ?
                    $this->_columns[$columnId]->getFilterIndex() : $this->_columns[$columnId]->getIndex();
                $this->getCollection()->setOrder($column , $dir);
            }

            $this->getCollection()->load();
            $this->_afterLoadCollection();
        }
		/* To make the admin product grid filters work - end */
        $this->getCollection()->addWebsiteNamesToResult();
        return $this;
		  
	} 
    protected function _prepareColumns()
    {	
	   if(Mage::helper('core')->isModuleOutputEnabled('Kodematix_ProductGift') && Mage::getStoreConfigFlag('productgiftsection/productgiftgroup/productgiftstatus'))
	   {
		$this->addColumnAfter('is_product_gift_enabled', array(
            'header' => Mage::helper('catalog')->__('Gift Product'),
            'width' => '80px',
			'type'  => 'options',
            'index' => 'is_product_gift_enabled',
			'options' => array( 0 => 'No', 1 => 'Yes')
        ),'name'); 
		
		$this->addColumnAfter('sku_of_product_gift', array(
            'header' => Mage::helper('catalog')->__("Gift Product Sku's"),
            'width' => '80px',
            'index' => 'sku_of_product_gift',
			'renderer' => 	'Kodematix_ProductGift_Adminhtml_Block_Catalog_Product_Grid_Skus'
        ),'name');
	   }
        parent::_prepareColumns();
    }
}
