<?php 
#   TITLE   : Application Pages   
#   DESC    : The Application pages re-routing feature 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30

$data = map(); 

@$file = $data[ex(1)] ?? $data['auth']; 

if ((ex(1) !== "auth")){
    if (empty(__ACCOUNT_INDEX__)){
        $file = $data["auth"]; 
    }else if ((account_data('auth') !== __ACCOUNT_INDEX__)){
        $file = $data["auth"]; 
    }else if (website_data('domain') == null){
        $file = "page.setup.php"; 
    }
}

#Include The Web File 
@include_once dirname(__FILE__)."/pages/".$file;

?>