<?php 
#   TITLE   : Application Pages   
#   DESC    : The Application pages re-routing feature 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30

$data = [
    "auth" => "page.auth.php",
    "home" => "page.dashboard.php",
    "payments" => "page.payments.php",
    "theme" => "page.theme.php",
]; 

@$file = $data[ex(1)] ?? $data['auth']; 

#Include The Web File 
@include_once dirname(__FILE__)."/pages/".$file; 
?>