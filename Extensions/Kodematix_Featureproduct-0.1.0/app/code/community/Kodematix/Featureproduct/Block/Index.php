<?php   
class Kodematix_Featureproduct_Block_Index extends Mage_Core_Block_Template{   

 public function featuretotalProduct() {
        $collection = Mage::getModel('catalog/product');
        $products = $collection->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('is_featured', 1)
                ->addAttributeToFilter('status', 1)
                ->load();
        return $products;
    }

public function getStoreconfig() {
        $enable = Mage::getStoreConfig('featureproduct/featureproduct_general/enabled');
        
		//horizontal_carousels_setting
		$horizontal_carousels_setting_title = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/title');
        $horizontal_carousels_setting_limit = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/product_no');
        $horizontal_carousels_setting_slide_itemsonpage = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/slide_itemsonpage');
        $horizontal_carousels_setting_slide_auto = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/slide_auto');
        $horizontal_carousels_setting_slide_navigation = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/slide_navigation');
		
		$slideWidth = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/slideWidth');
		$minSlides = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/minSlides');
		$maxSlides = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/maxSlides');
		$autoslider = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/autoslider');
		$slider_mode = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/slider_mode');
		$slider_speed = Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/slider_speed');
		$slider_margin =Mage::getStoreConfig('featureproduct/horizontal_carousels_setting/slide_Margin');
        $featuredValues = array(
			//Genneral setting
			'enabled' => $enable,
			//horizontal_carousels_setting
			'horizontal_carousels_setting_title' => $horizontal_carousels_setting_title,
			'horizontal_carousels_setting_limit' => $horizontal_carousels_setting_limit,
			'horizontal_carousels_setting_slide_itemsonpage' => $horizontal_carousels_setting_slide_itemsonpage,
			'horizontal_carousels_setting_slide_auto' => $horizontal_carousels_setting_slide_auto,
			'horizontal_carousels_setting_slide_navigation' => $horizontal_carousels_setting_slide_navigation,
			'slideWidth' => $slideWidth,
			'minSlides' => $minSlides,
			'maxSlides' =>$maxSlides,
			'autoslider' =>$autoslider,
			'slider_mode' =>$slider_mode,
			'slider_speed' =>$slider_speed,
			'slider_margin' =>$slider_margin,
		);
        return $featuredValues;
    }

}


