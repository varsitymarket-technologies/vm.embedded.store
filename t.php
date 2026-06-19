<?php 


@include_once dirname(__FILE__)."/config.php";


@include_once "marketing.php";

$store_identity = ['name'=>'Reiddrop','email'=>'reiddrop@gmail.com','phone'=>'+27 82 123 4567']; 
$domain = 'reiddrop.com';
$business = ['description'=>'123 Main Street','industry'=>'Cape Town','country'=>'South Africa'];
$e = send_notification_webhook($store_identity,$domain, $business) 

?>