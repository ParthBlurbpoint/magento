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
 * Export model
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Model_Export extends Mage_ImportExport_Model_Export
	implements Magegiant_Ie_Model_Scheduled_Operation_Interface
{
	/**
	 * Run export through cron
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

//		$userModel = Mage::getModel('admin/user');
//		$userModel->setUserId(1);
//		Mage::getSingleton('admin/session')->setUser($userModel);
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
		$profile->setActionsXml($xml);

		/*End override file, path*/

		Mage::register('current_convert_profile', $profile);
		$profile->run();
		$batchModel = Mage::getSingleton('dataflow/batch');

		if (!$batchModel->getId()) {
			Mage::getModel('magegiant_ie/scheduled_operation')->reportFailToEmail($operation);
		}

		return (bool)($batchModel->getId());
	}

	/**
	 * Initialize export instance from scheduled operation
	 *
	 * @param Magegiant_Ie_Model_Scheduled_Operation $operation
	 * @return Magegiant_Ie_Model_Export
	 */
	public function initialize(Magegiant_Ie_Model_Scheduled_Operation $operation)
	{
		$fileInfo  = $operation->getFileInfo();
		$attrsInfo = $operation->getEntityAttributes();
		$data      = array(
			'entity'                 => $operation->getEntityType(),
//			'file_format'            => $fileInfo['file_format'],
			'export_filter'          => $attrsInfo['export_filter'],
			'operation_type'         => $operation->getOperationType(),
			'run_at'                 => $operation->getStartTime(),
			'scheduled_operation_id' => $operation->getId()
		);
		if (isset($attrsInfo['s	kip_attr'])) {
			$data['skip_attr'] = $attrsInfo['skip_attr'];
		}
		$this->setData($data);

		return $this;
	}

	/**
	 * Get file name for scheduled running
	 *
	 * @return string file name without extension
	 */
	public function getScheduledFileName()
	{
		return Mage::getModel('core/date')->date('Y-m-d_H-i-s') . '_' . $this->getOperationType()
		. '_' . $this->getEntity();
	}


}
