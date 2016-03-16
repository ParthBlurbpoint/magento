<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Quickview
 */
class Amasty_Quickview_Block_Catalog_Product_Price extends Mage_Catalog_Block_Product_Price
{
    private $_confProduct = null;

    public function _toHtml() {        
		$price = '<div style="display: none !important;" id="amasty-product-id-' .  $this->getProduct()->getId() . '" ></div>';
        return $price . parent::_toHtml();
    }

}

