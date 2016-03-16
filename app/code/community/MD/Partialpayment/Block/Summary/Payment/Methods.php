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
class MD_Partialpayment_Block_Summary_Payment_Methods extends Mage_Core_Block_Template
{
    protected $_summaryId = null;
    protected $_summary = null;
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('md/partialpayment/summary/payment/methods.phtml');
    }
    
    public function setSummaryId($id){
        $this->_summaryId = $id;
        return $this;
    }
    
    public function getSummaryId()
    {
        return $this->_summaryId;
    }
    
    public function getSummary()
    {
        if($this->_summaryId && is_null($this->_summary)){
            $this->_summary = Mage::getModel('md_partialpayment/summary')->load($this->_summaryId);
        }
        return $this->_summary;
    }
    
    public function getSummaryPayments()
    {
        return $this->getSummary()->getPayments();
    }
    
    public function getActiveMethods()
    {
        $methods = Mage::getSingleton('payment/config')->getActiveMethods();
        $partialMethods = array();
        foreach($methods as $code=>$method){
            if(Mage::helper('md_partialpayment')->isAllowedMethod($code)){
                $partialMethods[$code] = $method;
            }
        }
        return $partialMethods;
    }
}

