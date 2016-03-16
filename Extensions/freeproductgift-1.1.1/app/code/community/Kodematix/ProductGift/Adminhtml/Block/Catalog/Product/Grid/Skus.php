<?php
/**
 * 
 * @author Kodematix
 *
 */
?>
<?php 
class Kodematix_ProductGift_Adminhtml_Block_Catalog_Product_Grid_Skus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{	
	public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
		if($value == "") {
			return 0;
		} else {
			$skus = explode(",", $value);
			// $Skus = implode("<br/>", $skus);
			$Skus = "";
			$count = 1;
			foreach($skus as $sku) {
				$Skus .=  $count . ". " . $sku ."<br/>";
				$count++;
			}
			return $Skus;
		}
    }
	public function filter(Varien_Data_Collection_Db $collection, Mage_Adminhtml_Block_Widget_Grid_Column $column)
    {
		Mage::log('My log entry', null, 'mylogfile.log');
	}
}
?>