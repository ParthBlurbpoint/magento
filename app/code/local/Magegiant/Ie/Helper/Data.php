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
 * Ie data helper
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
class Magegiant_Ie_Helper_Data extends Mage_ImportExport_Helper_Data
{
	const LOG_FILE = 'ImportExport.log';

    /**
     * Get operation header text
     *
     * @param string $type   operation type
     * @param string $action
     * @return string
     */
    public function getOperationHeaderText($type, $action = 'new')
    {
        $title = '';
        switch ($type) {
            case 'import':
                if ($action == 'edit') {
                    $title = $this->__('Edit Scheduled Import');
                } else {
                    $title = $this->__('New Scheduled Import');
                }
                break;
            case 'export':
                if ($action == 'edit') {
                    $title = $this->__('Edit Scheduled Export');
                } else {
                    $title = $this->__('New Scheduled Export');
                }
                break;
        }

        return $title;
    }

    /**
     * Get seccess operation save message
     *
     * @param string $type   operation type
     * @return string
     */
    public function getSuccessSaveMessage($type)
    {
        switch ($type) {
            case 'import':
                $message = $this->__('The scheduled import has been saved.');
                break;
            case 'export':
                $message = $this->__('The scheduled export has been saved.');
                break;
        }

        return $message;
    }

    /**
     * Get seccess operation delete message
     *
     * @param string $type   operation type
     * @return string
     */
    public function getSuccessDeleteMessage($type)
    {
        switch ($type) {
            case 'import':
                $message = $this->__('The scheduled import has been deleted.');
                break;
            case 'export':
                $message = $this->__('The scheduled export has been deleted.');
                break;
        }

        return $message;
    }

    /**
     * Get confirmation message
     *
     * @param string $type   operation type
     * @return string
     */
    public function getConfirmationDeleteMessage($type)
    {
        switch ($type) {
            case 'import':
                $message = $this->__('Are you sure you want to delete this scheduled import?');
                break;
            case 'export':
                $message = $this->__('Are you sure you want to delete this scheduled export?');
                break;
        }

        return $message;
    }

    /**
     * Get notice operation message
     *
     * @param string $type   operation type
     * @return string
     */
    public function getNoticeMessage($type)
    {
        $message = '';
        if ($type == 'import') {
            $maxUploadSize = $this->getMaxUploadSize();
            $message = $this->__('Total size of the file must not exceed %s', $maxUploadSize);
        }
        return $message;
    }
}
