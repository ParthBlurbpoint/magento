<?php

define('MAGENTO_ROOT', getcwd());
$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
require_once $mageFilename;
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
Mage::app();

$check = Mage::getModel('productgift/giftskus')->getCollection();
$giftCount = count($check);

print"<pre>";
print_r($check);
exit("test");
