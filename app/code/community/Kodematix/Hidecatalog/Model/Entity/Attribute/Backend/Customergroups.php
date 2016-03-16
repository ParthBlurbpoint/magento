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

class Kodematix_Hidecatalog_Model_Entity_Attribute_Backend_Customergroups
    extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract
{
    /**
     * Process the attribute value before saving
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Eav_Model_Entity_Attribute_Backend_Abstract
     */
    public function beforeSave($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $data = $object->getData($attributeCode);
        $customerGroupIds = array(
            Kodematix_Hidecatalog_Helper_Data::USE_DEFAULT,
            Kodematix_Hidecatalog_Helper_Data::USE_NONE,
        );
        $customerGroups = Mage::helper('kodematix_hidecatalog')->getGroups();
        $customerGroupIds = array_merge($customerGroupIds, array_keys($customerGroups->getItems()));

        if (!$data) {
            $data = array(Kodematix_Hidecatalog_Helper_Data::USE_DEFAULT);
        }

        if (!is_array($data)) {
            $data = explode(',', $data);
        }

        if (1 < count($data)) {
            // remove USE_DEFAULT if any other groups are selected, too
            $key = array_search(Kodematix_Hidecatalog_Helper_Data::USE_DEFAULT, $data);
            if (false !== $key) {
                unset($data[$key]);
            }

            // if USE_NONE is selected remove all other groups
            if (in_array(Kodematix_Hidecatalog_Helper_Data::USE_NONE, $data)) {
                $data = array(Kodematix_Hidecatalog_Helper_Data::USE_NONE);
            }
        }

        // validate all customer groups ids are valid
        foreach ($data as $key => $groupId) {
            if (!in_array($groupId, $customerGroupIds)) {
                unset($data[$key]);
            }
        }

        // I like it nice and tidy, this gives us sequential index numbers again as a side effect :)
        sort($data);

        $object->setData($attributeCode, implode(',', $data));
        return parent::beforeSave($object);
    }

    /**
     * Explode the saved array again, because otherwise the indexer will think the value changed,
     * even if it is the same (array(1,2,3) !== "1,2,3").
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Eav_Model_Entity_Attribute_Backend_Abstract
     */
    public function afterSave($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $data = $object->getData($attributeCode);
        if (is_string($data)) {
            $object->setData($attributeCode, explode(',', $data));
        }
        return parent::afterSave($object);
    }

    /**
     * In case the data was loaded, explode it into an array
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Eav_Model_Entity_Attribute_Backend_Abstract
     */
    public function afterLoad($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $data = $object->getData($attributeCode);

        // only explode and set the value if the attribute is set on the model
        if (null !== $data && is_string($data)) {
            $data = explode(',', $data);
            $object->setData($attributeCode, $data);
        }
        return parent::afterLoad($object);
    }
}
