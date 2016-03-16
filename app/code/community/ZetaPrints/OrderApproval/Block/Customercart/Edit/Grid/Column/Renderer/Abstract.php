<?php

class ZetaPrints_OrderApproval_Block_CustomerCart_Edit_Grid_Column_Renderer_Abstract
  extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

  protected $_item = null;
  protected $_item_renderer_block = null;

  public function render (Varien_Object $row) {
    $this->_item= parent::_getValue($row);

    $this->_item_renderer_block = Mage::app()->getLayout()
                              ->getBlockSingleton('checkout/cart_item_renderer')
                              ->setItem($this->_item);

    return $this->_toHtml();
  }

  public function getItem () {
    return $this->_item;
  }

  public function getItemRendererBlock () {
    return $this->_item_renderer_block;
  }
}

?>
