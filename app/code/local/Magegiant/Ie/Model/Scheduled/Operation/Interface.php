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
 * Scheduled operation interface
 *
 * @category    Magegiant
 * @package     Magegiant_Ie
 * @author      Magegiant Developers
 */
interface Magegiant_Ie_Model_Scheduled_Operation_Interface
{
    /**
     * Run operation through cron
     *
     * @param Magegiant_Ie_Model_Scheduled_Operation $operation
     * @return bool
     */
    function runSchedule(Magegiant_Ie_Model_Scheduled_Operation $operation);


    /**
     * Initialize operation model from scheduled operation
     *
     * @param Magegiant_Ie_Model_Scheduled_Operation $operation
     * @return object operation instance
     */
    function initialize(Magegiant_Ie_Model_Scheduled_Operation $operation);

    /**
     * Log debug data to file.
     *
     * @param mixed $debugData
     * @return object
     */
    function addLogComment($debugData);

    /**
     * Return human readable debug trace.
     *
     * @return array
     */
    function getFormatedLogTrace();
}
