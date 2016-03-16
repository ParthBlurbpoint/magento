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

class Vaimo_Klarna_Block_Info_Abstract extends Mage_Payment_Block_Info
{
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

    public function getAdditionalInfo($field = NULL)
    {
        $data = $this->getMethod()->getInfoInstance();
        if ($data) {
            return $data->getAdditionalInformation($field);
        }
        return NULL;
    }

    public function getMethodTitle()
    {
        $res = '';
        $data = $this->getMethod()->getInfoInstance();
        if ($data) {
            $klarna = Mage::getModel('klarna/klarna');
            $method = $data->getAdditionalInformation('method');
            if ($data->getOrder()) {
                $klarna->setOrder($data->getOrder());
                $res = $klarna->getMethodTitleWithFee();
            } else {
                $klarna->setQuote($this->getQuote(), $method);
                $res = $klarna->getMethodTitleWithFee(Mage::helper('klarna')->getVaimoKlarnaFeeInclVat($this->getQuote(), false));
            }
        }
        return $res;
    }

    public function getKlarnaFeeLabel()
    {
        $data = $this->getMethod()->getInfoInstance();
        if ($data) {
            if ($data->getOrder()) {
                return Mage::helper('klarna')->getKlarnaFeeLabel($data->getOrder()->getStore());
            } else {
                return Mage::helper('klarna')->getKlarnaFeeLabel($data->getQuote()->getStore());
            }
        }
        return Mage::helper('klarna')->getKlarnaFeeLabel();
    }
    
    public function formatPrice($price)
    {
        $data = $this->getMethod()->getInfoInstance();
        if ($data) {
            if ($data->getOrder()) {
                $res = Mage::app()->getStore($data->getOrder()->getStore())->formatPrice($price);
            } elseif ($data->getQuote()) {
                $res = Mage::app()->getStore($data->getQuote()->getStore())->formatPrice($price);
            } else {
                $res = Mage::app()->getStore()->formatPrice($price);
            }
        } else {
            $res = Mage::app()->getStore()->formatPrice($price);
        }
        return $res;
    }

    public function getPaymentInfo()
    {
        try {
            $res = new Varien_Object($this->getAdditionalInfo());
        } catch (Mage_Core_Exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $res;
    }

    public function somethingLeftToCapture()
    {
        $data = $this->getMethod()->getInfoInstance();
        if ($data) {
            if ($data->getOrder()) {
                if ($data->getOrder()->getPayment()->canCapture()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getKlarnaInvoiceLink($id)
    {
        $data = $this->getMethod()->getInfoInstance();
        if ($data) {
            $domain = ($data->getAdditionalInformation('klarna_reservation_host') === 'BETA') ? 'testdrive': 'online';
            $link = 'https://' . $domain . '.klarna.com/invoices/' . $id . '.pdf';
        } else {
            $link = $id;
        }
        return $link;
    }

    public function getInvoiceFeeHtml()
    {
        $this->setTemplate('vaimo/klarna/info/children/invoicefee.phtml');
        return $this->toHtml();
    }

    public function getInvoicesHtml()
    {
        $this->setTemplate('vaimo/klarna/info/children/invoices.phtml');
        return $this->toHtml();
    }

    public function getReservationHtml()
    {
        $this->setTemplate('vaimo/klarna/info/children/reservation.phtml');
        return $this->toHtml();
    }

    public function getReferenceHtml()
    {
        $this->setTemplate('vaimo/klarna/info/children/reference.phtml');
        return $this->toHtml();
    }

    public function getPaymentPlanHtml()
    {
        $this->setTemplate('vaimo/klarna/info/children/paymentplan.phtml');
        return $this->toHtml();
    }

    public function getKlarnaLogotype($width)
    {
        $method = $this->getMethod()->getCode();
        $klarna = Mage::getModel('klarna/klarna');
        $data = $this->getMethod()->getInfoInstance();
        if ($data) {
            if ($data->getOrder()) {
                $klarna->setOrder($data->getOrder(), $method);
            } else {
                $klarna->setQuote($data->getQuote(), $method);
            }
        } else {
            $klarna->setQuote($this->getQuote(), $method);
        }
        return $klarna->getKlarnaLogotype($width, Vaimo_Klarna_Helper_Data::KLARNA_LOGOTYPE_POSITION_FRONTEND);
    }

    public function getMethodCode()
    {
        return $this->getMethod()->getCode();
    }

}

