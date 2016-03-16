<?php

class ZetaPrints_OrderApproval_Block_CustomerCart_Edit
  extends Mage_Adminhtml_Block_Widget_Grid_Container {

  public function __construct () {
    $this->_blockGroup = 'orderapproval';
    $this->_controller = 'customercart_edit';
    $this->_headerText = '';

    parent::__construct();

    $this->_removeButton('add');
  }
}
?>
