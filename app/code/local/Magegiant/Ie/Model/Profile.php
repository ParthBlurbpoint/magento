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

class Magegiant_Ie_Model_Profile extends Mage_Core_Model_Abstract{
	public function getAllProfiles(){
		$profiles =  Mage::getModel('dataflow/profile')
			->getCollection()
		;
		return $profiles;
	}

	public function getProfilesOptionArray()
	{
		$result = array(0=>'---Select profile---');
		if(!count($profiles = $this->getAllProfiles())) return $result;
		foreach($profiles as $_profile){
			$result[$_profile->getId()] = $_profile->getName();
		}
		return $result;
	}
}