<?php
/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magegiant.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement.html
 */


class Magegiant_Productimportexport_Model_System_Config_Profiles{


    protected function getCollection(){
        $magentoVersion = Mage::getVersion();
        if(version_compare($magentoVersion,'1.6.0.0','<')){
            $profiles = Mage::getModel('dataflow/profile')->getCollection()
            ;
        } else{
            $profiles = Mage::getModel('dataflow/profile')->getCollection()
            ->addFieldToFilter('entity_type',array("null"=>true))
            ;
        }

        foreach($profiles as $profile){
            $options[] =  array(
                'value'=>$profile->getId(),
                'label'=>$profile->getName()
            );

        }

        return $profiles;
    }

    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $profiles = $this->getCollection();
        $options = array();

        foreach($profiles as $profile){
            $options[] =  array(
                'value'=>$profile->getId(),
                'label'=>$profile->getName()
            );

        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $profiles = $this->getCollection();
        $options = array();

        foreach($profiles as $profile){
            $options[$profile->getId()] = $profile->getName();

        }

        return $options;
    }
}