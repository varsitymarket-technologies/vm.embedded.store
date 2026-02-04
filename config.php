<?php 
@include_once "scripts.php";

define("__ACCOUNT_INDEX__",'08693379430119870832bb2f8e2325ada293d0726aa409d025c0ab4d0c41cc34698230d50417a'); 
define("__USERNAME__",account_data('name')); 
define("__BANKING_ACCOUNT_NUMBER__",banking_data('number'));
define("__BANKING_ACCOUNT_TYPE__",banking_data('type')); 
define("__BANKING_SERVICE__",banking_data('provider')); 
define("__CURRENCY_SIGN__","R");
define("__WALLET_AMOUNT__","1,999.00"); 
define("__WALLET_PERCENTAGE__",admin_percentage()); 
define("__DOMAIN__",website_data('domain')); 
define("__WEBSITE_DOMAIN__","http://127.0.0.1:7700"); 
define("__WEBSITE_THEME__",website_data('theme')); 
define("__WEBSITE_URL__","/sites/".__DOMAIN__."/index.php"); 
define("__WEBSITE_FRAME__",__WEBSITE_DOMAIN__."/sites/".uniqid('store_')."/".hash("sha256",__USERNAME__)."/"); 


?>