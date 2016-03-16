<?php
$servername = "192.168.1.14";
$username = "root";
$password = "";

$con = mysqli_connect($servername,$username,$password,"magento");

// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
 else
 {
 	echo "SuccessFull";
 }
?>