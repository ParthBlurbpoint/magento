<?php

class MD_Partialpayment_Block_Adminhtml_Sales_Order_View_Tab_Installmentsummary extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface {

    protected $_app     = null;
    protected $_request = null;

    protected function _construct() {
	parent::_construct();
	$this->setTemplate('md/partialpayment/sales/order/view/tab/installmentsummary.phtml');
	$this->_app     = Mage::app();
	$this->_request = $this->_app->getRequest();
    }

    /**
     * Get Tab Label
     *
     * @return string
     */
    public function getTabLabel() {
	return Mage::helper('sales')->__('Installment Summary');
    }

    /**
     * Get Tab Title
     *
     * @return string
     */
    public function getTabTitle() {
	return Mage::helper('sales')->__('Installment Summary');
    }

    /**
     * Can Show Tab
     *
     * @return boolean
     */
    public function canShowTab() {
	return true;
    }

    /**
     * Is Hidden
     *
     * @return boolean
     */
    public function isHidden() {
	return false;
    }

    public function getOrder() {
	return Mage::registry('current_order');
    }

    public function getPayment() {
	$paymentOption = Mage::getModel('md_partialpayment/payments')->getCollection()
			     ->addFieldToFilter('order_id',array('eq'=>$this->getOrder()->getIncrementId()))
			     ->getFirstItem();
	return $paymentOption;
    }

    public function getTitle() {
	if ($_extOrderId = $this->getPayment()->getOrder()->getExtOrderId()) {
	    $_extOrderId = '[' . $_extOrderId . '] ';
	} else {
	    $_extOrderId = '';
	}
	return Mage::helper('sales')->__('Partial Payment - Order # %s %s | %s', $this->getPayment()->getOrder()->getRealOrderId(), $_extOrderId, $this->formatDate($this->getPayment()->getOrder()->getCreatedAtDate(), 'medium', true));
    }

    public function getCustomerViewUrl() {
	if ($this->getPayment()->getOrder()->getCustomerIsGuest() || !$this->getPayment()->getOrder()->getCustomerId()) {
	    return false;
	}
	return $this->getUrl('adminhtml/customer/edit', array('id' => $this->getPayment()->getOrder()->getCustomerId()));
    }

    public function getViewUrl($orderId) {
	return $this->getUrl('md_partialpayment/adminhtml_summary/view', array('id' => $orderId));
    }

}
