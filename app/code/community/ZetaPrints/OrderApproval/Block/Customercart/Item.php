<?php

class ZetaPrints_OrderApproval_Block_CustomerCart_Item
  extends Mage_Core_Block_Template {

  public function getImages () {
    if (!Mage::helper('orderapproval')->isWebToPrintInstalled())
      return false;

    $options = $this
                 ->getQuoteItem()
                 ->getBuyRequest();

    if (!$guids = $options->getData('zetaprints-previews'))
      return false;

    $guids = explode(',', $guids);

    $helper = Mage::helper('webtoprint/personalization-form');

    $images = array();

    foreach ($guids as $guid)
      $images[] = $helper->get_preview_url($guid);

    return $images;
  }

  public function getPdfProof () {
    if (!Mage::helper('orderapproval')->isWebToPrintInstalled())
      return false;

    $options = $this
                 ->getQuoteItem()
                 ->getBuyRequest();

    if (!$path = $options->getData('zetaprints-order-lowres-pdf'))
      return false;

    return Mage::getStoreConfig('webtoprint/settings/url') . $path;
  }

  public function getApproveUrl () {
    $path = 'orderapproval/customercart/updateApprovalState';

    $params = array(
      'item' => $this
                  ->getQuoteItem()
                  ->getId(),
      'state' => ZetaPrints_OrderApproval_Helper_Data::APPROVED
    );

    return $this->getUrl($path, $params);
  }

  public function getDeclineUrl ($external = false) {
    $itemId = $this
                ->getQuoteItem()
                ->getId();

    if ($external)
      return $this->getUrl('orderapproval/customercart/item',
                           array('id' => $itemId));

    $path = 'orderapproval/customercart/updateApprovalState';

    $params = array(
      'item' => $itemId,
      'state' => ZetaPrints_OrderApproval_Helper_Data::DECLINED
    );

    return $this->getUrl($path, $params);
  }
}
?>
