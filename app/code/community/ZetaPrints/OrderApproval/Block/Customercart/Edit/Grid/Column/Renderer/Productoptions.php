<?php

class ZetaPrints_OrderApproval_Block_CustomerCart_Edit_Grid_Column_Renderer_ProductOptions
  extends ZetaPrints_OrderApproval_Block_Customercart_Edit_Grid_Column_Renderer_Abstract {

  public function __construct() {
    parent::__construct();

    $this->setTemplate('order-approval/cart/edit/grid/column/renderer/product-options.phtml');
  }

  public function getOptionList () {
    return $this->getItemRendererBlock()->getOptionList();
  }

  public function getFormatedOptionValue ($option) {
    return $this->getItemRendererBlock()->getFormatedOptionValue($option);
  }

  public function getMessages () {
    return $this->getItemRendererBlock()->getMessages();
  }

  public function getProductName () {
    return $this->getItemRendererBlock()->getProductName();
  }
}

?>
