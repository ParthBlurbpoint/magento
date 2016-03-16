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

class Vaimo_Klarna_Block_Adminhtml_Pclass_List extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_messages = array(); // Very much not the best way to do this...

    /*
     * Set template
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('vaimo/klarna/pclass/list.phtml');
    }

    public function _prepareLayout()
    {
        $msgs = Mage::getSingleton('adminhtml/session')->getMessages(true);
        $this->_messages = $msgs->getItems();
        parent::_prepareLayout();
    }

    public function getMessages()
    {
        if (sizeof($this->_messages)>0) {
            return $this->_messages;
        }
        return NULL;
    }

    /**
     * Extracts a list of PClasses to display
     *
     * @return array
     */
    public function getPClasses()
    {
        try {
            $res = array();
            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setStoreInformation();
            $klarna->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT);
            $res = $klarna->getDisplayAllPClasses();
        } catch (Mage_Core_Exception $e) {
            $res['error'] = $e->getMessage();
        }
        return $res;
    }

}
