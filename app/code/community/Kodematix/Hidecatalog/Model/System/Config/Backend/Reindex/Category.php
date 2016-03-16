<?php
/**
 * Kodematix
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category   Kodematix
 * @package    Kodematix_Hidecatalog
 * @copyright  Copyright (c) 2014 Parth Palkhiwala http://kodematix.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Kodematix_Hidecatalog_Model_System_Config_Backend_Reindex_Category
    extends Kodematix_Hidecatalog_Model_System_Config_Backend_Reindex_Abstract
{
    /**
     * Return the indexer code
     *
     * @return string
     * @see Kodematix_Hidecatalog_Model_System_Config_Backend_Mode_Abstract::_afterSave()
     */
    protected function _getIndexerCode()
    {
        return 'hidecatalog_category';
    }
}
