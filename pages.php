<?php 
#   TITLE   : Application Pages   
#   DESC    : The Application pages re-routing feature 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30

$data = map(); 

@$file = $data[ex(1)] ?? $data['auth']; 

if (empty(__ACCOUNT_INDEX__)) {
    $file = "page.auth.php";
} else if ((account_data('auth') !== __ACCOUNT_INDEX__)) {
    $file = "page.auth.php";
}


// --- Domain & Store Ownership Check ---
$db_engine = __DB_MODULE__;
$domain = __DOMAIN__;

// Verify the logged-in user owns the store they're trying to access
$store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
$owned_domain = $store_record[0]['domain'] ?? null;

if ($file !== "page.auth.php"){    
    if (empty($owned_domain)) {
        $file = "page.setup.php";
    }else if (empty($domain)) {
        $file = "page.setup.php";
    }else if (empty($owned_domain)) {
        $file = "page.setup.php";
    }else{
        $file = "page.dashboard.php";
    }
}

#Include The Web File 
@include_once dirname(__FILE__)."/pages/".$file;

?>