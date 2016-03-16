<?php
/**
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the  License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @copyright   Copyright (c) 2014 Magegiant
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Import model
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Model_Import extends Mage_ImportExport_Model_Import
	implements Magegiant_Ie_Model_Scheduled_Operation_Interface
{
	/**
	 * Reindex indexes by process codes.
	 *
	 * @return Magegiant_Ie_Model_Import
	 */
	public function reindexAll()
	{
		if (!isset(self::$_entityInvalidatedIndexes[$this->getEntity()])) {
			return $this;
		}

		$indexers = self::$_entityInvalidatedIndexes[$this->getEntity()];
		foreach ($indexers as $indexer) {
			$indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode($indexer);
			if ($indexProcess) {
				$indexProcess->reindexEverything();
			}
		}

		return $this;
	}

	/**
	 * Run import through cron
	 *
	 * @param Magegiant_Ie_Model_Scheduled_Operation $operation
	 * @return bool
	 */
	public function runSchedule(Magegiant_Ie_Model_Scheduled_Operation $operation)
	{


		//Check disable
		if (!$operation->getStatus()) return;
		$profileId = $operation->getProfileId();
		$profile   = Mage::getModel('dataflow/profile');
		$profile->load($profileId);
		if (!$profile->getId()) {
			Mage::getSingleton('adminhtml/session')->addError('ERROR: Incorrect profile id');
		}


		/**
		 * override directory, file name
		 * <var name="path">var/export</var>
		 * <var name="filename"><![CDATA[export_products_basic.csv]]></var>
		 */
		$fileInfo = $operation->getFileInfo();
		$xml      = $profile->getActionsXml();

		if (!$this->_canImport($fileInfo['file_name'])) {
			Mage::getSingleton('adminhtml/session')->addError('Import file is not allowed. Support .csv and .xml files.');

		}

		$xmlAddFile = '
			<action type="dataflow/convert_adapter_io" method="load">
				<var name="type">file</var>
				<var name="path">' . $fileInfo['file_path'] . '</var>
				<var name="filename"><![CDATA[' . $fileInfo['file_name'] . ']]></var>
				<var name="format"><![CDATA['.$this->getFileExtension($fileInfo['file_name']).']]></var>
			</action>
		';

		$xml .= $xmlAddFile;

		$profile->setActionsXml($xml);

		Mage::register('current_convert_profile', $profile);
		$profile->run();
		$batchModel = Mage::getSingleton('dataflow/batch');

		$result = (bool)($batchModel->getId());

		if (!$batchModel->getId()) {
			Mage::getModel('magegiant_ie/scheduled_operation')->reportFailToEmail($operation);
		}

		if ($result) {
			$this->reindexAll();
		}

		return (bool)$result;
	}

	/**
	 * Initialize import instance from scheduled operation
	 *
	 * @param Magegiant_Ie_Model_Scheduled_Operation $operation
	 * @return Magegiant_Ie_Model_Import
	 */
	public function initialize(Magegiant_Ie_Model_Scheduled_Operation $operation)
	{
		$this->setData(array(
			'entity'                 => $operation->getEntityType(),
			'behavior'               => $operation->getBehavior(),
			'operation_type'         => $operation->getOperationType(),
			'run_at'                 => $operation->getStartTime(),
			'scheduled_operation_id' => $operation->getId()
		));

		return $this;
	}

	public function getFileExtension($file_name)
	{
		return substr(strrchr($file_name, '.'), 1);
	}

	protected function _allowedFiles()
	{
		return array('csv', 'xml');
	}

	protected function _canImport($file)
	{
		$ext = $this->getFileExtension($file);

		return in_array($ext, $this->_allowedFiles());
	}

}
