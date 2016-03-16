<?php
/* SOAP V2 CONNECTION DETAILS */
$office = 'http://dev.custostactical.com/index.php/api/v2_soap?wsdl=1';
$user = '98516b3d0';
$key = 'd41d8cd9';

$cli = new SoapClient($office);

/* SET SESSION */
$sessionId = $cli->login((object)array('username' => $user, 'apiKey' => $key));

// GRAB ORDER LIST
//$result = $cli->salesOrderList((object)array('sessionId' => $sessionId->result, 'filters' => null));
//var_dump($result->result);

// GRAB ORDER INFO 
$result = $cli->salesOrderInfo((object)array('sessionId' => $sessionId->result,'orderIncrementId' => '100000004')); 
//other order# for testing is 100000003 which contains just configurable products

print "<pre>";
print_r($result->result->items);
exit;