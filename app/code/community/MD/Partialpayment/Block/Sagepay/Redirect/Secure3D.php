<?php
class MD_Partialpayment_Block_Sagepay_Redirect_Secure3D extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $sessionSpace = ($this->getRequest()->getParam("area") == "adminhtml") ? "adminhtml": "sagepaysuite";
        $prefix = ($this->getRequest()->getParam("area") == "adminhtml") ? "adminhtml_": "";
        if($this->getRequest()->getParam("area") == "adminhtml"){
            $sessionVandorTxId = $this->getRequest()->getParam("vandor_tx_code");
            $isFull = (int)$this->getRequest()->getParam("full_payment",null);
            $summaryId = $this->getRequest()->getParam("summary_id");
            $paymentsId = $this->getRequest()->getParam("payments_id");
        }else{
            $sessionVandorTxId = Mage::getSingleton($sessionSpace.'/session')->getVandorTxCode(true);
            $isFull = (int)Mage::getSingleton($sessionSpace.'/session')->getFullPayment(true);
            $summaryId = Mage::getSingleton($sessionSpace.'/session')->getSummaryId(true);
            $paymentsId = Mage::getSingleton($sessionSpace.'/session')->getPaymentsId(true);
        }
        $form = new Varien_Data_Form();
        $loaded = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')->loadByVendorTxCode($sessionVandorTxId);
        $form->setAction($loaded->getAcsurl())
            ->setId('sagepay_3d_redirect')
            ->setName('sagepay_3d_redirect')
            ->setMethod('POST')
            ->setUseContainer(true);
        
        $form->addField('MD', 'hidden', array('name'=>'MD', 'value'=>$loaded->getMd()));
        $form->addField('PaReq', 'hidden', array('name'=>'PaReq', 'value'=>$loaded->getPareq()));
        $params = array (
				'_secure' => true,
				'storeid' => Mage::app()->getStore()->getId(),
				'v'        => $sessionVandorTxId,
                                'summary_id'=>$summaryId,
                                'payments_id'=>$paymentsId,
                                'full'=>$isFull
			);
			$postUrl = Mage::getModel('core/url')->addSessionParam()->getUrl('md_partialpayment/'.$prefix.'sagepay/callback3d', $params);
			$form->addField('TermUrl', 'hidden', array (
				'name' => 'TermUrl',
				'value' => $postUrl
			));
        $idSuffix = Mage::helper('core')->uniqHash();
            $submitButton = new Varien_Data_Form_Element_Submit(array(
                'value'    => $this->__('Loading 3D secure form...'),
            ));
            $id = "submit_to_3dSecure_button_{$idSuffix}";
            $submitButton->setId($id);
            $form->addElement($submitButton);
            $html = '<html><body>';
            $html.= $this->__('Loading 3D secure form...');
            $html.= $form->toHtml();
            $html.= '<script type="text/javascript">document.getElementById("sagepay_3d_redirect").submit();</script>';
            $html.= '</body></html>';
            return $html;
    }
}

