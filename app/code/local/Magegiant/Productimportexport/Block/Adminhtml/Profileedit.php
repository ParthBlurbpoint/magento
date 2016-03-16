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

class Magegiant_Productimportexport_Block_Adminhtml_Profileedit extends Mage_Adminhtml_Block_System_Convert_Profile_Edit_Tab_Edit{

    const VAR_PROFILE_ACTIONS_FILE   = 'product_profile_library.json';

    public function getProfileActionsLibrary()
    {
        $file = Mage::getBaseDir('var') . DS . 'magegiant' . DS . self::VAR_PROFILE_ACTIONS_FILE;
        if(!is_file($file)) {
            Mage::throwException($this->__('File %s is not exist',$file));
        }
        return $this->parserProfiles(file_get_contents($file));
    }

    protected function parserProfiles($content)
    {
        if(!$content) return null;
        $profiles = json_decode($content,true);
        $result = array();
        $i=0;
        foreach($profiles as $profile){
            $i++;
            $result[] = array(
                'id'=>$i,
                'title'=>$profile['title'],
                'actions_xml'=>base64_decode($profile['actions_xml'])
            );
        }
        return $result;

    }
}