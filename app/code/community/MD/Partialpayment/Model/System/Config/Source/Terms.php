<?php
/**
* Magedelight
* Copyright (C) 2014 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Partialpayment
* @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
class MD_Partialpayment_Model_System_Config_Source_Terms 
{
    public function toOptionArray()
    {
        $options = array();
        $options[] = array('label'=>Mage::helper('md_partialpayment')->__('---Select---'),'value'=>'');
        $blocks = Mage::getModel('cms/block')->getCollection()
                                        ->addFieldToFilter('is_active',array('eq'=>1))
                                        ->addFieldToSelect('block_id')
                                        ->addFieldToSelect('title');
        
        foreach($blocks as $_block){
            $options[] = array('label'=>$_block->getTitle(),'value'=>$_block->getId());
        }
        
        return $options;
    }
}

