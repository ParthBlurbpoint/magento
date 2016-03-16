<?php
/**
 * MageGiant
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MageGiant.com license that is
 * available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    MageGiant
 * @package     MageGiant_Productimportexport
 * @copyright   Copyright (c) 2014 MageGiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Productimportexport Observer Model
 * 
 * @category    MageGiant
 * @package     MageGiant_Productimportexport
 * @author      MageGiant Developer
 */
class Magegiant_Productimportexport_Model_Observer
{
    /**
     * process controller_action_predispatch event
     *
     * @return Magegiant_Productimportexport_Model_Observer
     */
    public function controllerActionPredispatch($observer)
    {
        $action = $observer->getEvent()->getControllerAction();
        return $this;
    }

	public function parseExtraData($observer){
//		$product = $observer->getEvent()->getProduct();
		Mage::getSingleton('productimportexport/convert_parser_product_configurable')->parseConfigurableProducts();
		Mage::getSingleton('productimportexport/convert_parser_product_category')->parseCategories();
		Mage::getSingleton('productimportexport/convert_parser_product_groupPrice')->parseGroupPrice();
		Mage::getSingleton('productimportexport/convert_parser_product_tierPrice')->parseTierPrice();
		Mage::getSingleton('productimportexport/convert_parser_product_related')->parseRelated();
		Mage::getSingleton('productimportexport/convert_parser_product_upsell')->parseUpsell();
		Mage::getSingleton('productimportexport/convert_parser_product_crosssell')->parseCrosssell();
		Mage::getSingleton('productimportexport/convert_parser_product_grouped')->parseGroupedProducts();
		Mage::getSingleton('productimportexport/convert_parser_product_bundle')->parseBundleProducts();
		Mage::getSingleton('productimportexport/convert_parser_product_gallery')->parseGallery();
	}
}