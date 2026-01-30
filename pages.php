<?php 
$data = [
    "auth" => "page.auth.php",
    "home" => "page.dashboard.php",
    "payments" => "page.payments.php",
]; 

@$file = $data[ex(1)] ?? $data['auth']; 

#Include The Web File 
@include_once dirname(__FILE__)."/pages/".$file; 
?>