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
class Magegiant_Productimportexport_Model_Convert_Parser_Product_Gallery extends Magegiant_Productimportexport_Model_Convert_Parser_Product
{
	public function _canExportGallery()
	{
		return version_compare($this->getMagentoVersion(), '1.5.0.0', '<');
	}

	public function run(){
		$this->parseGallery();
	}

	public function parseGallery()
	{
		if ($this->_canExportGallery()) return;
		$gallery = $this->_getGalleries();
		if (!count($gallery)) return;
		$data = implode(self::MULTI_DELIMITER_SHORT, $gallery);
		$this->addToRow('gallery', $data);
	}


	public function _getGalleries()
	{
		$return = array();
		foreach ($this->_getGalleryCollection() as $_img) {
			$return[] = $_img->getFile();
		}

		return $return;
	}

	public function _getGalleryCollection()
	{
		return $this->getCurrentProduct()->getMediaGalleryImages();
	}
}