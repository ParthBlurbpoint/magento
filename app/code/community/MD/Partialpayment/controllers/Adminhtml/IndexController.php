<?php
/**
* Magedelight
* Copyright (C) 2014 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Partialpayment
* @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
class MD_Partialpayment_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function exportAction()
    {
        $mage_csv = new Varien_File_Csv();
        $path = Mage::getBaseDir().DS.'var'.DS.'md'.DS.'partialpayment'.DS.'export'.DS;
		$dir = new Varien_Io_File($path);
		$dir->mkdir($path,0777,true);
        
            $filename = 'Partial_Payment_Options_Export.csv';
            $path .= $filename;
				$data = Mage::getResourceModel('md_partialpayment/options')->getExportData();
				$mage_csv->saveData($path, $data);
				$csvdata = $this->getString($path);
            return $this->_prepareDownloadResponse($filename,$csvdata);
    }
    
    public function getString($file)
	{
		$fileObj = new Varien_Io_File();
		
		$result = $fileObj->read($file);
		return $result;
	}
}

