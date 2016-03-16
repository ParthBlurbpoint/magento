<?php
class MD_Partialpayment_Block_Sagepay_Redirect_Server extends Mage_Core_Block_Template
{
    protected $_postParams = array();
    protected $_paymentsId = null;
    protected $_fullPayment = 0;
    
    public function setPostParams($params = array())
    {
        $this->_postParams = $params;
        return $this;
    }
    
    public function getPostParams()
    {
        return $this->_postParams;
    }
    
    protected function _toHtml()
    {
        $posts = $this->getPostParams();
        
        $summaryId = $posts['payment_summary'];
        $paymentsId = $posts['payment_id'];
        $fullPaymentFlag = (array_key_exists('full_payment',$posts) && $posts['full_payment'] == 1) ? true: false;
        
        $summary = (!is_int($summaryId) && $summaryId > 0) ? Mage::getModel("md_partialpayment/summary")->load($summaryId): null;
        $payments = ($summary) ? $summary->getPayments(): Mage::getModel("md_partialpayment/payments")->load($paymentsId);
        $order = $payments->getOrder();
        
        $method = $posts['partial']['method'];
        $info = $posts[$method];
        $posts['method'] = $method;
        
        
        $requestObject = Mage::getModel("md_partialpayment/payment_sagepay_sagePayServer")
                                    ->setSummary($summary)
                                    ->setPayments($payments)
                                    ->setOrder($order)
                                    ->setIsFullCapture($fullPaymentFlag)
                                    ->getRequestParams($info);
        
        $form = new Varien_Data_Form();
        $form->setAction($requestObject->getData('request_url'))
            ->setId('sagepay_server_redirect')
            ->setName('sagepay_server_redirect')
            ->setMethod('POST')
            ->setUseContainer(true);
        
        foreach ($requestObject->getData() as $field=>$value) {
            if($field == 'request_url'){
                    continue;
                }
                $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $idSuffix = Mage::helper('core')->uniqHash();
            $submitButton = new Varien_Data_Form_Element_Submit(array(
                'value'    => $this->__('Click here if you are not redirected within 10 seconds...'),
            ));
            $id = "submit_to_server_button_{$idSuffix}";
            $submitButton->setId($id);
            $form->addElement($submitButton);
            $html = '<html><body>';
            $html.= $this->__('You will be redirected to the PayPal website in a few seconds.');
            $html.= $form->toHtml();
            $html.= '<script type="text/javascript">document.getElementById("sagepay_server_redirect").submit();</script>';
            $html.= '</body></html>';
            return $html;
    }
}

