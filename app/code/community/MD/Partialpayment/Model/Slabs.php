<?php
class MD_Partialpayment_Model_Slabs extends Mage_Core_Model_Abstract
{
    const INSTALLMENT_SLAB_PRICE_FIXED = 0;
    const INSTALLMENT_SLAB_PRICE_PERCENTAGE = 1;
    
    
    public function _construct() {
        parent::_construct();
        $this->_init('md_partialpayment/slabs');
    }
    
    public function getSlabsByProduct(Mage_Catalog_Model_Product $product)
    {
        if($product instanceof Mage_Catalog_Model_Product)
        {
            $storeId = ($product->getStoreId()) ? $product->getStoreId(): 0;
            $collection = $this->getCollection()
                                    ->addFieldToFilter('product_id',array('eq'=>$product->getId()))
                                    ->addFieldToFilter('store_id',array('eq'=>$storeId))
                                    ->setOrder('unit','ASC');
            return $collection;
        }
        return null;
    }
    
}

