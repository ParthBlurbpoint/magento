<?php
include_once("app/Mage.php");
ini_set("display_errors",1);
error_reporting(E_ALL | E_STRICT);
Mage::app();
Mage::init();
$order_id = 15904; //use your own order id 
$order = Mage::getModel("sales/order")->load($order_id); 

/*	print"<pre>";
	print_r($order);
	print"</pre>";
	exit;*/
$ordered_items = $order->getAllItems(); 

//echo count($ordered_items);
foreach($ordered_items as $_item)
{
	$attributesArray = $_item->getProductOptions();
	//echo $sku = $_item->getProduct()->getSku();
	$from = $attributesArray["options"][0]["value"];
	$to = $attributesArray["options"][1]["value"];

	/*["options"][0] === TO*/
	/*["options"][1] === FROM*/

	/*print"<pre>";
	print_r($attributesArray["options"][0]);
	print_r($attributesArray["options"][1]);*/

	$attributesArray["options"][0]["value"] = $to;
	$attributesArray["options"][1]["value"] = $from;
	$attributesArray["options"][0]["print_value"] = $to;
	$attributesArray["options"][1]["print_value"] = $from;
	$attributesArray["options"][0]["option_value"] = $to;
	$attributesArray["options"][1]["option_value"] = $from;

	/*print_r($attributesArray["options"][0]);
	print_r($attributesArray["options"][1]);
	//print_r($_item->getData());
	print"</pre>";*/
	$_item->setProductOptions($attributesArray);
}
$order->save();
foreach($ordered_items as $_item)
{
	$attributesArray = $_item->getProductOptions();
	print"<pre>";
	print_r($attributesArray["options"][0]);
	print_r($attributesArray["options"][1]);
}

exit("parth")
?>