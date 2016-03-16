<?php

/**
 * Magedelight
 * Copyright (C) 2015 Magedelight <info@magedelight.com>
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
 * @copyright Copyright (c) 2015 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */
class MD_Partialpayment_Adminhtml_PartialplanController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {
	$this->loadLayout();
	$this->_setActiveMenu('md_partialpayment');
	$this->getLayout()->getBlock('head')->setTitle(Mage::helper('md_partialpayment')->__('Partial Plan'));
	$this->renderLayout();
    }

    protected function _isAllowed() {
	return Mage::getSingleton('admin/session')->isAllowed('md_partialpayment/partialplan');  
    }
    
    public function newAction() {
	$this->_forward('edit');
    }

    public function editAction() {
	$partialPlanRuleId = (int) $this->getRequest()->getParam('id');
	$partialPlan = Mage::getModel('md_partialpayment/rule');

	if ($partialPlanRuleId) {
	    $partialPlan->load($partialPlanRuleId);
	}

	Mage::register('current_partialplan', $partialPlan);

	if ($partialPlanRuleId && !$partialPlan->getId()) {
	    $this->_getSession()->addError(
		    Mage::helper('md_partialpayment')->__('This Partial Plan Rule no longer exists.')
	    );
	    $this->_redirect('*/*/');
	    return;
	}

	$data = Mage::getSingleton('adminhtml/session')->getPartialPlanData(true);
	if (!empty($data)) {
	    $partialPlan->setData($data);
	}

	/* start product condition tab */
	$partialPlan->getConditions()->setJsFormObject('rule_conditions_fieldset');
	$partialPlan->getActions()->setJsFormObject('rule_actions_fieldset');
	/* end product condition tab */

	Mage::register('md_partialpayment_data', $partialPlan);

	$this->loadLayout();

	$this->getLayout()->getBlock('head')
		->setCanLoadExtJs(true)
		->setCanLoadRulesJs(true);

	$this->_title(Mage::helper('md_partialpayment')->__('report'))
		->_title(Mage::helper('md_partialpayment')->__('Add Partial Plan Rule'));

	if ($partialPlan->getId()) {
	    $this->_title($partialPlan->getTitle());
	} else {
	    $this->_title(Mage::helper('md_partialpayment')->__('Add Partial Plan Rule'));
	}

	if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
	    $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
	}
	$this->renderLayout();
    }

    public function saveAction() {
	if ($data = $this->getRequest()->getPost()) {
	    try {
		$partialPlanRuleId = (int) $this->getRequest()->getParam('id');
		$partialPlan	   = Mage::getModel('md_partialpayment/rule');

		if (!empty($partialPlanRuleId)) {
		    $partialPlan->load($partialPlanRuleId);
		}
		
		//installment settings start
		$installments = $data['partialpayment']['slabs'];
		if(!empty($installments)) {
		    $installments = serialize($installments);
		} else {
		    $installments = null;
		}
		//installment settings end
		
		//conditions start
		$data['conditions'] = $data['rule']['conditions'];
		unset($data['rule']);

		$validateResult = $partialPlan->validateData(new Varien_Object($data));
		$autoApply	= false;

		if (!empty($data['auto_apply'])) {
		    $autoApply = true;
		    unset($data['auto_apply']);
		}
		$partialPlan->loadPost($data);
		//conditions ends
		
		foreach($data as $key=>$value) {
		    if($key == 'partialpayment'){
			$partialPlan->setInstallmentSettings($installments);
		    } else {
			$partialPlan->setData($key,$value);
		    }		    
		}
		
		$productIds = $partialPlan->getMatchingProductIds();
		$min_stock  = isset($data['minimum_stock']) ? $data['minimum_stock'] : "";

		if (!empty($min_stock)) {
		    $productIds = $partialPlan->excludeMinQtyStock($productIds, $min_stock);
		}

		if (!empty($productIds)) {
		    $productIdscomma = implode(",", $productIds);
		} else {
		    $productIdscomma = null;
		}
		$partialPlan->setProductIds($productIdscomma);
		$partialPlan->save();
		
		$partialPlanRuleId = $partialPlan->getId();

		$this->_getSession()->addSuccess('Rule successfully saved !!');
	    } catch (Exception $ex) {
		$this->_getSession()->addError($ex->getMessage());
	    }
	}
	$storeId      = $this->getRequest()->getParam('store');
	$redirectBack = $this->getRequest()->getParam('back', false);

	if ($redirectBack) {
	    $this->_redirect('*/*/edit', array(
		'id' => $partialPlanRuleId,
		'_current' => true
	    ));
	} else {
	    $this->_redirect('*/*/', array('store' => $storeId));
	}
    }

    public function deleteAction() {
	$ruleId = $this->getRequest()->getParam('id');
	$this->_forward('massDelete', null, null, array('partialpayment' => array($ruleId)));
    }

    public function massDeleteAction() {
	$partialPlanRuleIds = $this->getRequest()->getParam('partialpayment');
	if (!is_array($partialPlanRuleIds)) {
	    $this->_getSession()->addError($this->__('Please select rule(s).'));
	} else {
	    if (!empty($partialPlanRuleIds)) {
		foreach ($partialPlanRuleIds as $partialPlanRuleId) {
		    Mage::getModel('md_partialpayment/rule')->load($partialPlanRuleId)->delete();
		}
	    }
	    $this->_getSession()->addSuccess('Rule(s) successfully deleted !!');
	}
	$this->_redirect('*/*/index');
    }
    
    public function massStatusAction() {
	$partialPlanRuleIds = $this->getRequest()->getParam('partialpayment');
	$status		    = (int)$this->getRequest()->getParam('status');
	
	if (!is_array($partialPlanRuleIds)) {
	    $this->_getSession()->addError($this->__('Please select rule(s).'));
	} else {
	    if (!empty($partialPlanRuleIds)) {
		foreach ($partialPlanRuleIds as $partialPlanRuleId) {
		    $model	   = Mage::getModel('md_partialpayment/rule')->load($partialPlanRuleId);
		    $currentStatus = (int)$model->getRuleStatus();
		    
		    if($currentStatus != $status) {
			$model->setRuleStatus($status)->save();
		    }
		}
	    }
	    $this->_getSession()->addSuccess('Rule(s) successfully updated !!');
	}
	
	$this->_redirect('*/*/index');
    }

    public function newConditionHtmlAction() {
	$id = $this->getRequest()->getParam('id');
	$typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
	$type = $typeArr[0];

	$model = Mage::getModel($type)
		->setId($id)
		->setType($type)
		->setRule(Mage::getModel('catalogrule/rule'))
		->setPrefix('conditions');
	if (!empty($typeArr[1])) {
	    $model->setAttribute($typeArr[1]);
	}

	if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
	    $model->setJsFormObject($this->getRequest()->getParam('form'));
	    $html = $model->asHtmlRecursive();
	} else {
	    $html = '';
	}
	$this->getResponse()->setBody($html);
    }

    public function conditionproductsAction() {
	$this->loadLayout()
		->renderLayout();
    }
}
