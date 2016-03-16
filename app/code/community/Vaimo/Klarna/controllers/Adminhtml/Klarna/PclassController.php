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

class Vaimo_Klarna_Adminhtml_Klarna_PclassController extends Mage_Adminhtml_Controller_Action
{
    public function dispatchAction()
    {
    }

    public function updateAction()
    {
        $result = array('error' => '', 'message' => '');
        try {
            $request = $this->getRequest();

            $klarna = Mage::getModel('klarna/klarna');
            $klarna->setStoreInformation();
            $klarna->setMethod(Vaimo_Klarna_Helper_Data::KLARNA_METHOD_ACCOUNT);
            $klarna->reloadAllPClasses();

            $block = Mage::getSingleton('core/layout')
                        ->createBlock('klarna/adminhtml_pclass_list');;

            $result['html'] = $block->toHtml();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result = Mage::helper('core')->jsonEncode($result);
        Mage::app()->getResponse()->setBody($result);
    }
    
}
