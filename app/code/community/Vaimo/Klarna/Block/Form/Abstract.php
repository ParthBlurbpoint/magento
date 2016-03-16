<?php
/**
 * Copyright (c) 2009-2014 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_Klarna
 * @copyright   Copyright (c) 2009-2014 Vaimo AB
 */

class Vaimo_Klarna_Block_Form_Abstract extends Mage_Payment_Block_Form
{
    protected $_pclasses = array();
    
    public function __construct()
    {
        parent::__construct();
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getPClasses()
    {
        try {
            $method = $this->getMethod()->getCode();
            if (isset($this->_pclasses[$method])) {
                return $this->_pclasses[$method];
            }
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $res = $klarna->getValidCheckoutPClasses($method);
            $data = $this->getMethod()->getInfoInstance();
            if ($data) {
                $id = $data->getAdditionalInformation(Vaimo_Klarna_Helper_Data::KLARNA_INFO_FIELD_PAYMENT_PLAN);
                if ($id) {
                    foreach ($res as &$pclass) {
                        if ($pclass['id']==$id) {
                            $pclass['default'] = true;
                        } else {
                            $pclass['default'] = false;
                        }
                    }
                }
            }
            $this->_pclasses[$method] = $res;
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $res = NULL;
        }
        return $res;
    }
    
    public function getCheckoutService()
    {
        try {
            $method = $this->getMethod()->getCode();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $res = $klarna->getCheckoutService($method);
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $res = NULL;
        }
        if ($res==NULL) {
            $res = array(false); // On purpouse!
        }
        return $res;
    }

    public function needAddressSearch()
    {
        try {
            $method = $this->getMethod()->getCode();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $res = $klarna->useGetAddresses();
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $res = false;
        }
        return $res;
    }

    public function needDateOfBirth()
    {
        try {
            $method = $this->getMethod()->getCode();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $res = $klarna->needDateOfBirth();
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $res = false;
        }
        return $res;
    }

    public function needGender()
    {
        try {
            $method = $this->getMethod()->getCode();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $res = $klarna->needGender();
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $res = false;
        }
        return $res;
    }

    public function needConsent()
    {
        try {
            $method = $this->getMethod()->getCode();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $res = $klarna->needConsent();
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $res = false;
        }
        return $res;
    }
    
    public function needExtraPaymentPlanInformaton()
    {
        try {
            $method = $this->getMethod()->getCode();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $res = $klarna->needExtraPaymentPlanInformaton();
        } catch (Mage_Core_Exception $e) {
            Mage::helper('klarna')->logKlarnaException($e);
            $res = false;
        }
        return $res;
    }
    
    protected function _getCurrentField($field, $default = '')
    {
        try {
            $res = $default;
            $data = $this->getMethod()->getInfoInstance();
            if ($data) {
                $res = $data->getAdditionalInformation($field);
            }
        } catch (Mage_Core_Exception $e) {
            $res = $default;
        }
        return $res;
    }

    public function getCurrentPno()
    {
        return $this->_getCurrentField('pno');
    }

    public function getCurrentGender()
    {
        return $this->_getCurrentField('gender');
    }

    public function getCurrentPhonenumber()
    {
        $quote = $this->getQuote();
        $address = $quote->getBillingAddress();
        $res = $address->getTelephone();
        if (!$res) {
            $address = $quote->getShippingAddress();
            $res = $address->getTelephone();
        }
        if (!$res) {
            $res = $this->_getCurrentField('phonenumber');
        }
        if ($res=='-') $res = ''; // Magento seems to default to '-'
        return $res;
    }

    public function getCurrentDobYear()
    {
        return $this->_getCurrentField('dob_year');
    }

    public function getCurrentDobMonth()
    {
        return $this->_getCurrentField('dob_month');
    }

    public function getCurrentDobDay()
    {
        return $this->_getCurrentField('dob_day');
    }

    public function getPClassHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/pclass.phtml');
        return $this->toHtml();
    }

    public function getPersonalNumberHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/personalnumber.phtml');
        return $this->toHtml();
    }

    public function getDateOfBirthHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/dateofbirth.phtml');
        return $this->toHtml();
    }

    public function getGenderHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/gender.phtml');
        return $this->toHtml();
    }

    public function getConsentHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/consent.phtml');
        return $this->toHtml();
    }

    public function getPhonenumberHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/phonenumber.phtml');
        return $this->toHtml();
    }

    public function getAddressresultHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/addressresult.phtml');
        return $this->toHtml();
    }

    public function getPaymentplanInformationHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/paymentplan_information.phtml');
        return $this->toHtml();
    }

    public function getNotificationsHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/notifications.phtml');
        return $this->toHtml();
    }

    public function getCheckoutServiceHtml()
    {
        $this->setTemplate('vaimo/klarna/form/children/checkoutservice.phtml');
        return $this->toHtml();
    }

    /**
     * Return ajax url for address search
     *
     * @return string
     */
    public function getAjaxAddressSearchUrl()
    {
        return Mage::getSingleton('core/url')->getUrl('klarna/address/search', array('_secure' => true));
    }

    /**
     * Return ajax url for extra information
     *
     * @return string
     */
    public function getAjaxPaymentPlanInformationUrl()
    {
        return Mage::getSingleton('core/url')->getUrl('klarna/paymentplan/information', array('_secure' => true));
    }

    public function getKlarnaLogotype($width)
    {
        $method = $this->getMethod()->getCode();
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setQuote($this->getQuote(), $method);
        return $klarna->getKlarnaLogotype($width, Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_POSITION_CHECKOUT);
    }
    
    public function useServiceLogotypes($serviceMethod, $width)
    {
        $res = '';
        if (isset($serviceMethod['logo'])) {
            foreach ($serviceMethod['logo'] as $logo) {
                $res .= '<img src="' . $logo . '?width=' . $width . '"/>';
            }
        }
        return $res;
    }

    public function getKlarnaSetup()
    {
        $method = $this->getMethod()->getCode();
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setQuote($this->getQuote(), $method);
        return $klarna->getKlarnaSetup();
    }

    public function getDefaultPaymentPlanInformation($method)
    {
        $res = NULL;
        try {
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setQuote($this->getQuote(), $method);
            $pclasses = $klarna->getValidCheckoutPClasses($method);
            if ($pclasses) {
                foreach ($pclasses as $pclass) {
                    if ($pclass['default']) {
                        $res = $klarna->getPClassDetails($pclass['id']);
                    }
                }
            }
        } catch (Mage_Core_Exception $e) {
            $res = NULL;
        }
        return $res;
    }

    public function getStoreId()
    {
        $res = $this->getQuote()->getStoreId();
        return $res;
    }

    public function formatPrice($price)
    {
        $res = Mage::app()->getStore($this->getQuote()->getStore())->formatPrice($price);
        return $res;
    }

    public function getKlarnaInvoiceFeeInfo()
    {
        return Mage::helper('klarna')->getVaimoKlarnaFeeInclVat($this->getQuote(), false);
    }

    public function getTermsUrlLink()
    {
        $method = $this->getMethod()->getCode();
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setQuote($this->getQuote(), $method);
        $url = $klarna->getConfigData('terms_url');
        return Mage::helper('klarna')->getTermsUrlLink($url);
    }

    /**
     * First draft to show logos instead of title/descpription in checkout
     * This function is called in checkout/onepage/payment/methods.phtml
     * - not sure if it is being used anywhere else (which could be a problem)
     * Needs css to 'work' - see checkout.css in skin../vaimo/klarna/css
     * @todo would be better to send height as logo parameter for frontend/css
     */
    public function getMethodLabelAfterHtml()
    {
        $str = "";
        $method = $this->getMethod()->getCode();
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setQuote($this->getQuote(), $method);
        if (Mage::helper('klarna')->showTitleAsTextOnly()==false) {
            $str = '<img src="'.$klarna->getKlarnaLogotype(75, Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_POSITION_CHECKOUT).'" alt="" title="" />';
            $str .= $klarna->getMethodTitleWithFee(Mage::helper('klarna')->getVaimoKlarnaFeeInclVat($this->getQuote(), false));
        }
        return $str;
    }

    public function shippingSameAsBilling()
    {
        $method = $this->getMethod()->getCode();
        $klarna = Mage::getModel('klarna/klarna');
        $klarna->setQuote($this->getQuote(), $method);
        $res = $klarna->shippingSameAsBilling();
        return $res;
    }

}

