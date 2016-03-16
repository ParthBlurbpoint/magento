<?php

/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magegiant.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement.html
 */
class Magegiant_Productimportexport_Model_Convert_Parser_Product_TierPrice extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{
	public function parseTierPrice()
	{
		if (!$this->_canParse()) return;
		if (!count($data = $this->getTierPrices())) return;
		$this->addToRow(
			Magegiant_Productimportexport_Helper_Data::COL_TIER_PRICE,
			Mage::helper('core')->jsonEncode($data)
		);
	}

	public function _canParse()
	{
		return true;
	}

	public function getTierPrices()
	{
		$tiers = $this->getCurrentProduct()->getData('tier_price');
		if (!is_array($tiers) OR !count($tiers)) return;
		$return = array();
		foreach ($tiers as $_item) {
			$return[] = array(
				'cust_group' => $_item['cust_group'],
				'price_qty'  => $_item['price_qty'],
				'price'      => $_item['price']
			);
		}

		return $return;
	}

	public function run()
	{
		$this->parseTierPrice();
	}

}
