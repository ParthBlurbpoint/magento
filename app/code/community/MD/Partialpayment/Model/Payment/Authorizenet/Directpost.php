<?php
class MD_Partialpayment_Model_Payment_Authorizenet_Directpost  extends MD_Partialpayment_Model_Payment_Authorizenet
{
    protected $_paymentModel = array(
        'authorizenet_directpost' => 'authorizenet/directpost',
    );
    
    protected $_responseCodesMap = array(
        'authorizenet_directpost' => array(
            1=>1,
            2=>4,
            3=>2,
            4=>3
        ),
    );
    
    public function processResponse($response = array(),$summaryId = null)
    {
        if(!is_null($summaryId) && count($response) > 0){
            $summary = Mage::getModel('md_partialpayment/summary')->load($summaryId);
            $methodObject = Mage::getModel($this->_paymentModel['authorizenet_directpost']);
            $methodMD5Hash = $methodObject->getConfigData('trans_md5');
            $apiLogin = $methodObject->getConfigData('login');
            $amount = (!$summary->getAmount()) ? '0.00': number_format($summary->getAmount(), 2);
            
            $localHash = strtoupper(md5($methodMD5Hash . $apiLogin . $response['x_trans_id'] . $amount));
            $responseMD5Hash = $response['x_MD5_Hash'];
            
            if(!$methodMD5Hash || !$apiLogin || !($localHash == $responseMD5Hash)){
                
            }
        }
    }
    
    public function generateRequestSign($merchantApiLoginId, $merchantTransactionKey, $amount, $currencyCode, $fpSequence, $fpTimestamp)
    {
        if (phpversion() >= '5.1.2') {
            return hash_hmac("md5",
                $merchantApiLoginId . "^" .
                $fpSequence . "^" .
                $fpTimestamp . "^" .
                $amount . "^" .
                $currencyCode, $merchantTransactionKey
            );
        }

        return bin2hex(mhash(MHASH_MD5,
            $merchantApiLoginId . "^" .
            $fpSequence . "^" .
            $fpTimestamp . "^" .
            $amount . "^" .
            $currencyCode, $merchantTransactionKey
        ));
    }
    
    public function _buildRequest($data, $methodObject){
        $order = $this->getOrder();
        $summary = $this->getSummary();
        
        /* we are checking that whether customer hase choosed full payment option */
        if($this->getIsFullCapture()){
            /* if full payment is selected then total due amount of partial payment will be captured. */
            $captureAmount = $this->getPayments()->getDueAmount();
        }else{
            /* if regular payment is selected then current summary amount of partial payment will be captured. */
            $captureAmount = $summary->getAmount();
        }
        
        $fpTimestamp = time();
        
        $request = new Varien_Object();
        $request->setXVersion('3.1')
                ->setXDelimData('False')
                ->setXRelayResponse('TRUE')
                ->setXTestRequest($methodObject->getConfigData('test') ? 'TRUE' : 'FALSE')
                ->setXLogin($methodObject->getConfigData('login'))
                //->setXTranKey($methodObject->getConfigData('trans_key'))
                ->setXType('AUTH_ONLY')
                ->setXMethod($methodObject::REQUEST_METHOD_CC)
                ->setXRelayUrl(Mage::getUrl('md_partialpayment/summary/relayResponse',array('summary_id'=>$summary->getId())))
                ->setXFpSequence($summary->getId())
                ->setXInvoiceNum($order->getIncrementId())
                ->setXAmount($captureAmount,2)
                ->setXCurrencyCode($order->getBaseCurrencyCode());
        
        if (!empty($order)) {
            $billing = $order->getBillingAddress();
            if (!empty($billing)) {
                $request->setXFirstName($billing->getFirstname())
                    ->setXLastName($billing->getLastname())
                    ->setXCompany($billing->getCompany())
                    ->setXAddress($billing->getStreet(1))
                    ->setXCity($billing->getCity())
                    ->setXState($billing->getRegion())
                    ->setXZip($billing->getPostcode())
                    ->setXCountry($billing->getCountry())
                    ->setXPhone($billing->getTelephone())
                    ->setXFax($billing->getFax())
                    ->setXCustId($order->getCustomerId())
                    ->setXCustomerIp($order->getRemoteIp())
                    ->setXCustomerTaxId($billing->getTaxId())
                    ->setXEmail($order->getCustomerEmail())
                    ->setXEmailCustomer($methodObject->getConfigData('email_customer'))
                    ->setXMerchantEmail($methodObject->getConfigData('merchant_email'));
            }
            $shipping = $order->getShippingAddress();
            if (!empty($shipping)) {
                $request->setXShipToFirstName($shipping->getFirstname())
                    ->setXShipToLastName($shipping->getLastname())
                    ->setXShipToCompany($shipping->getCompany())
                    ->setXShipToAddress($shipping->getStreet(1))
                    ->setXShipToCity($shipping->getCity())
                    ->setXShipToState($shipping->getRegion())
                    ->setXShipToZip($shipping->getPostcode())
                    ->setXShipToCountry($shipping->getCountry());
            }
        }
        if(isset($data['cc_number'])){
            $request->setXCardNum($data['cc_number'])
                ->setXExpDate(sprintf('%02d-%04d', $data['cc_exp_month'], $data['cc_exp_year']));
                //->setXCardCode($data['cc_type']);
        }
        $hash = $this->generateRequestSign(
            $methodObject->getConfigData('login'),
            $methodObject->getConfigData('trans_key'),
            $methodObject->getXAmount(),
            $request->getXCurrencyCode(),
            $request->getXFpSequence(),
            $fpTimestamp
        );
        $request->setXFpTimestamp($fpTimestamp)
                ->setXFpHash($hash);
        return $request;
    }
}
