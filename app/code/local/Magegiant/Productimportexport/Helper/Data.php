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
 * Productimportexport Helper
 *
 * @category    MageGiant
 * @package     MageGiant_Productimportexport
 * @author      MageGiant Developer
 */
class Magegiant_Productimportexport_Helper_Data extends Mage_Core_Helper_Abstract
{

	const PIE_EXPORT_PRODUCTS_ALL    = 'magegiant_product_export_all.csv';
	const PIE_EXPORT_PRODUCTS_BASIC  = 'magegiant_product_export_basic.csv';
	const PIE_EXPORT_PRODUCTS_STOCKS = 'magegiant_product_export_stock.csv';

	const COL_CROSSSELL                  = 'crosssell_associated_skus';
	const COL_UPSELL                     = 'upsell_associated_skus';
	const COL_RELATED                    = 'related_associated_skus';
	const COL_GROUPED                    = 'grouped_associated_skus';
	const COL_TIER_PRICE                 = 'tier_prices';
	const COL_CUSTOM_OPTIONS             = 'custom_options';
	const COL_PRODUCT_TAGS               = 'product_tags';
	const COL_DOWNLOADABLE_LINKS         = 'downloadable_links';
	const COL_DOWNLOADABLE_SAMPLES       = 'downloadable_samples';
	const COL_CONFIGURABLE_ATTRIBUTES    = 'configurable_attributes';
	const COL_CONFIGURABLE_ASSOCIATED    = 'configurable_associated';
	const COL_CONFIGURABLE_SUPER_PRICING = 'super_attribute_pricing';
	const COL_BUNDLE_OPTIONS             = 'bundle_options';
	const COL_BUNDLE_SELECTIONS          = 'bundle_selections';
	const COL_GROUPED_PRICE              = 'group_price';
	const COL_REVIEWS                    = 'reviews';

	const LOG_FILE = 'ImportExport.log';
	protected $_config;

	public function __construct()
	{
		$this->_initConfig();
	}

	protected function _initConfig()
	{
		if (!$this->_config) {
			$this->_config = Mage::getSingleton('productimportexport/config');
		}

		return $this->_config;
	}

	public function isEnabled()
	{
		return $this->_config->isEnabled();
	}

	public function getExportPath()
	{
		return Mage::getBaseDir('var') . DS . 'export';
	}

	public function getImportPath()
	{
		return Mage::getBaseDir('var') . DS . 'import';
	}

	public function getExportProductAll()
	{
		return self::PIE_EXPORT_PRODUCTS_ALL;
	}

	public function getExportProductBasic()
	{
		return self::PIE_EXPORT_PRODUCTS_BASIC;
	}

	public function getExportProductStock()
	{
		return self::PIE_EXPORT_PRODUCTS_STOCKS;
	}

	public function getExportAllProfileId()
	{
		return $this->_config->getProductExportAll();
	}

	public function getExportBasicProfileId()
	{
		return $this->_config->getProductExportBasic();
	}

	public function getExportStocksProfileId()
	{
		return $this->_config->getProductExportStock();
	}


	public function getImportProfileId()
	{
		return $this->_config->getProductImport();
	}

	public function formatSizeUnits($bytes)
	{
		if ($bytes >= 1073741824) {
			$bytes = number_format($bytes / 1073741824, 2) . ' GB';
		} elseif ($bytes >= 1048576) {
			$bytes = number_format($bytes / 1048576, 2) . ' MB';
		} elseif ($bytes >= 1024) {
			$bytes = number_format($bytes / 1024, 2) . ' KB';
		} elseif ($bytes > 1) {
			$bytes = $bytes . ' bytes';
		} elseif ($bytes == 1) {
			$bytes = $bytes . ' byte';
		} else {
			$bytes = '0 bytes';
		}

		return $bytes;
	}

	public function getFileDate($filename)
	{
		return date("F d Y H:i:s.", filemtime($filename));
	}


	public function slug($string, $seperator = '-', $allowANSIOnly = true, $isLowerCase = true)
	{
		$pattern = array(
			"a" => "á|à|ạ|ả|ã|Á|À|Ạ|Ả|Ã|ă|ắ|ằ|ặ|ẳ|ẵ|Ă|Ắ|Ằ|Ặ|Ẳ|Ẵ|â|ấ|ầ|ậ|ẩ|ẫ|Â|Ấ|Ầ|Ậ|Ẩ|Ẫ",
			"o" => "ó|ò|ọ|ỏ|õ|Ó|Ò|Ọ|Ỏ|Õ|ô|ố|ồ|ộ|ổ|ỗ|Ô|Ố|Ồ|Ộ|Ổ|Ỗ|ơ|ớ|ờ|ợ|ở|ỡ|Ơ|Ớ|Ờ|Ợ|Ở|Ỡ",
			"e" => "é|è|ẹ|ẻ|ẽ|É|È|Ẹ|Ẻ|Ẽ|ê|ế|ề|ệ|ể|ễ|Ê|Ế|Ề|Ệ|Ể|Ễ",
			"u" => "ú|ù|ụ|ủ|ũ|Ú|Ù|Ụ|Ủ|Ũ|ư|ứ|ừ|ự|ử|ữ|Ư|Ứ|Ừ|Ự|Ử|Ữ",
			"i" => "í|ì|ị|ỉ|ĩ|Í|Ì|Ị|Ỉ|Ĩ",
			"y" => "ý|ỳ|ỵ|ỷ|ỹ|Ý|Ỳ|Ỵ|Ỷ|Ỹ",
			"d" => "đ|Đ",
			"c" => "ç",
		);
		while (list($key, $value) = each($pattern)) {
			$string = preg_replace('/' . $value . '/i', $key, $string);
		}
		if ($allowANSIOnly) {

			$string = preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', $seperator, ''), $string);
		}

		if ($isLowerCase) {
			$string = strtolower($string);
		}

		return $string;
	}

	public function randKey($leng)
	{
		for (
			$code_length = $leng, $newcode = ''; strlen($newcode) < $code_length; $newcode .= chr(!rand(0, 2) ? rand(48, 57) : (!rand(0, 1) ? rand(65, 90) : rand(97, 122))))
			;

		return $newcode;
	}


	public function formatFileDate($file)
	{
		if (!is_file($file)) return null;

		return date("F d Y H:i:s.", filemtime($file));
	}

	public static function log($e, $class = null)
	{
		if (empty($e)) return;
		if (!is_string($e)) {
			$msg = $e->getCode() . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' or Class '. get_class($class) . ':' . $e->getLine();
			Mage::log($msg, null, self::LOG_FILE);
		} else {
			Mage::log($e, null, self::LOG_FILE);
		}
	}

}