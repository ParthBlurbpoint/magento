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
 * Operation Data model
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Model_Scheduled_Operation_Data
{
    const STATUS_PENDING = 2;

    /**
     * Get statuses option array
     *
     * @return array
     */
    public function getStatusesOptionArray()
    {
        return array(
            1 => Mage::helper('magegiant_ie')->__('Enabled'),
            0 => Mage::helper('magegiant_ie')->__('Disabled'),
        );
    }

    /**
     * Get operations option array
     *
     * @return array
     */
    public function getOperationsOptionArray()
    {
        return array(
            'import' => Mage::helper('magegiant_ie')->__('Import'),
            'export' => Mage::helper('magegiant_ie')->__('Export')
        );
    }

    /**
     * Get frequencies option array
     *
     * @return array
     */
    public function getFrequencyOptionArray()
    {
        return array(
            Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_DAILY   => Mage::helper('magegiant_ie')->__('Daily'),
            Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY  => Mage::helper('magegiant_ie')->__('Weekly'),
            Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY => Mage::helper('magegiant_ie')->__('Monthly'),
        );
    }

    /**
     * Get server types option array
     *
     * @return array
     */
    public function getServerTypesOptionArray()
    {
        return array(
            'file'  => Mage::helper('magegiant_ie')->__('Local Server'),
            'ftp'   => Mage::helper('magegiant_ie')->__('Remote FTP')
        );
    }

    /**
     * Get file modes option array
     *
     * @return array
     */
    public function getFileModesOptionArray()
    {
        return array(
            FTP_BINARY  => Mage::helper('magegiant_ie')->__('Binary'),
            FTP_ASCII   => Mage::helper('magegiant_ie')->__('ASCII'),
        );
    }

    /**
     * Get forced import option array
     *
     * @return array
     */
    public function getForcedImportOptionArray()
    {
        return array(
            0 => Mage::helper('magegiant_ie')->__('Stop Import'),
            1 => Mage::helper('magegiant_ie')->__('Continue Processing'),
        );
    }

    /**
     * Get operation result option array
     *
     * @return array
     */
    public function getResultOptionArray()
    {
        return array(
            0  => Mage::helper('magegiant_ie')->__('Failed'),
            1  => Mage::helper('magegiant_ie')->__('Successful'),
            self::STATUS_PENDING  => Mage::helper('magegiant_ie')->__('Pending')
        );
    }

    /**
     * Get entities option array
     *
     * @param string $type
     * @return array
     */
    public function getEntitiesOptionArray($type = null)
    {
        $entitiesPath = Mage_ImportExport_Model_Import::CONFIG_KEY_ENTITIES;
        $importEntities = Mage_ImportExport_Model_Config::getModelsArrayOptions($entitiesPath);

        $entitiesPath = Mage_ImportExport_Model_Export::CONFIG_KEY_ENTITIES;
        $entities = Mage_ImportExport_Model_Config::getModelsArrayOptions($entitiesPath);

        switch ($type) {
            case 'import':
                return $importEntities;
            case 'export':
                return $entities;
            default:
                foreach ($importEntities as $key => &$entityName) {
                    $entities[$key] = $entityName;
                }
                return $entities;
        }
    }
}
