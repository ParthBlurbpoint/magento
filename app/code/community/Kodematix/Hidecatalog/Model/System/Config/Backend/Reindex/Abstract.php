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

abstract class Kodematix_Hidecatalog_Model_System_Config_Backend_Reindex_Abstract
    extends Mage_Core_Model_Config_Data
{
    /**
     * Return the indexer code for this backend entity
     *
     * @abstract
     * @return string
     */
    abstract protected function _getIndexerCode();

    /**
     * Set the index to require reindex
     *
     * @return void
     */
    protected function _afterSave()
    {
        if ($this->isValueChanged()) {
            $indexerCode = $this->_getIndexerCode();
            $process = Mage::getModel('index/indexer')->getProcessByCode($indexerCode);
            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
    }
}
