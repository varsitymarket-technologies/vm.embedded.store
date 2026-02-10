<?php 
@include_once "scripts.php";

define("__ACCOUNT_INDEX__",__account_index__()); 
define("__USERNAME__",account_data('name')); 
define("__BANKING_ACCOUNT_NUMBER__",banking_data('number'));
define("__BANKING_ACCOUNT_TYPE__",banking_data('type')); 
define("__BANKING_SERVICE__",banking_data('provider')); 
define("__CURRENCY_SIGN__","R");
define("__WALLET_AMOUNT__","0.00"); 
define("__WALLET_PERCENTAGE__",admin_percentage()); 
define("__DOMAIN__",website_data('domain')); 
define("__WEBSITE_DOMAIN__", "http://".get_domain().""); 
define("__WEBSITE_THEME__",website_data('theme')); 
define("__WEBSITE_URL__","/sites/".__DOMAIN__."/index.php"); 
define("__WEBSITE_FRAME__",__WEBSITE_DOMAIN__."/sites/".uniqid('store_')."/".hash("sha256",__USERNAME__)."/"); 

?>