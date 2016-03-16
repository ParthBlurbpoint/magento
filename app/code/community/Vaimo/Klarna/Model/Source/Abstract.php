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

abstract class Vaimo_Klarna_Model_Source_Abstract
{
    protected $_moduleHelper = NULL;
    protected $_coreHelper = NULL;
    protected $_adminhtmlHelper = NULL;
    
    /**
     * constructor
     *
     * @param  $moduleHelper
     * @param  $coreHelper
     * @param  $adminhtmlHelper
     */
    public function __construct($moduleHelper = NULL, $coreHelper = NULL, $adminhtmlHelper = NULL)
    {
        $this->_moduleHelper = $moduleHelper;
        if ($this->_moduleHelper==NULL) {
            $this->_moduleHelper = Mage::helper('klarna');
        }
        $this->_coreHelper = $coreHelper;
        if ($this->_coreHelper==NULL) {
            $this->_coreHelper = Mage::helper('core');
        }
        $this->_adminhtmlHelper = $adminhtmlHelper;
        if ($this->_adminhtmlHelper==NULL) {
            $this->_adminhtmlHelper = Mage::helper('adminhtml');
        }
    }
    
    protected function _getHelper()
    {
        return $this->_moduleHelper;
    }

    protected function _getCoreHelper()
    {
        return $this->_coreHelper;
    }

    protected function _getAdminhtmlHelper()
    {
        return $this->_adminhtmlHelper;
    }

}
