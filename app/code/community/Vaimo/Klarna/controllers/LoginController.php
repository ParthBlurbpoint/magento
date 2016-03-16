<?php
/**
 * Created by JetBrains PhpStorm.
 * User: arne
 * Date: 2011-04-06
 * Time: 11.34
 * To change this template use File | Settings | File Templates.
 */

class Vaimo_Klarna_LoginController extends Mage_Core_Controller_Front_Action
{
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function loginPostAction()
    {
        // Get params
        $req = $this->getRequest();
        $email = $req->getParam("email");
        $pass = $req->getParam("pass");
        $is_new = $req->getParam("is_new");
        $is_new = $is_new && $is_new !== "false";

        // Handle login / customer create logic
        $ws_id = Mage::app()->getWebsite()->getId();
        $cust = Mage::getModel("customer/customer");
        $cust->setData("website_id", $ws_id);
        $csess = Mage::getSingleton("customer/session");
        $cust->loadByEmail($email);
        $r = array();
        $r["r_code"] = 1;
        $blk = new Mage_Core_Block_Template;

        if ($is_new) {
            if ($cust->getId()) {
                // Already exists
                $r["r_code"] = -1;
                $r["message"] = $blk->__("A customer with this email already exists");
            } else {
                // Create a new one
                $cust->setData("email", $email);
                $cust->setPassword($pass);
                $cust->save();
                if ($cust->getId()) {
                    $csess->setCustomerAsLoggedIn($cust);
                    $r["message"] = $blk->__("Created + logged in");
                }
            }
        } else {
            if (!$cust->getId()) {
                $r["r_code"] = -2;
                $r["message"] = $blk->__("No customer by this name exist");
            } else {
                $logged_in = false;
                try {
                    $logged_in = $csess->login($email, $pass);
                } catch (Exception $e) {
                }
                if ($logged_in) {
                    $r["message"] = $blk->__("Logged in");
                    $r["reload_checkout"] = 1;
                } else {
                    $r["r_code"] = -3;
                    $r["message"] = $blk->__("Wrong password");
                }
            }
        }

        if ($r["r_code"]>0 && ($blk_type = $req->getParam("block"))) {
            $tmplt = $req->getParam("template");
            $tmplt = $tmplt ? $tmplt : $blk_type . ".phtml";
            $block = Mage::app()->getLayout()->createBlock($blk_type);
            $r["block_html"] = $block->setTemplate($tmplt)->toHtml();
        }

        $this->getResponse()->setBody(Zend_Json::encode($r));
    }

    public function forgotPasswordPostAction()
    {
        $email = $this->getRequest()->getPost('email');

        if (empty($email)) {
            echo $this->__("You must enter your e-mail address");
        }

        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                echo Mage::helper('customer')->__('Invalid email address.');
                $this->_getSession()->setForgottenEmail($email);
                //$this->_getSession()->addError($this->__('Invalid email address.'));

                return;
            }

            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($customer->getId()) {
                try {
                    $newPassword = $customer->generatePassword();
                    $customer->changePassword($newPassword, false);
                    $customer->sendPasswordReminderEmail();
                    //$this->_getSession()->addSuccess($this->__('A new password has been sent.'));
                    $message = 1;
                    echo $message;
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            } else {
                echo $this->__("This email address was not found in our records.");
                //$this->_getSession()->addError($this->__('This email address was not found in our records.'));
                $this->_getSession()->setForgottenEmail($email);
            }
        }
    }

    /**
     * Check if the user has logged in or not. Return error message when login failed.
     */
    public function checkAction()
    {
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            echo "1";
        } else {
            foreach($session->getMessages(true)->getErrors() as $message) {
                echo $message->getText();
            }
        }
    }
}
