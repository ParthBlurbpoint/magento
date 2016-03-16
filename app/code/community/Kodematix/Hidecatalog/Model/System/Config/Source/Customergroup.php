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

class Kodematix_Hidecatalog_Model_System_Config_Source_Customergroup
{
    /**
     * @var $_options array
     */
    protected $_options;

    /**
     * Return all customer groups as an option array.
     * The normally hidden customer groups are included, e.g. NOT LOGGED IN
     * @return array
     */
    public function toOptionArray()
    {
        if (is_null($this->_options)) {
            $this->_options = array();
            $helper = Mage::helper('kodematix_hidecatalog');
            if ($helper->getConfig('show_multiselect_field')) {
                $this->_options[] = array(
                    'value' => Kodematix_Hidecatalog_Helper_Data::USE_NONE,
                    'label' => $helper->__('[ NONE ]')
                );
                foreach (Mage::helper('kodematix_hidecatalog')->getGroups() as $group) {
                    /* @var $group Mage_Customer_Model_Group */
                    $this->_options[] = array(
                        'value' => $group->getId(),
                        'label' => $group->getCode(),
                    );
                }
            }
        }
        return $this->_options;
    }
}
