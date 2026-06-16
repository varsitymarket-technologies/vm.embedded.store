<?php 


@include_once dirname(__FILE__)."/config.php";



function _get_store_id( $domain , $db_engine = __DB_MODULE__ ){
    $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
    if (empty($store_record) && !empty($domain)) {
        $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE domain = ? LIMIT 1", [$domain]);
    }

    
    $store_id = $store_record[0]['id'] ?? '';
    return $store_id; 
}

echo _get_store_id("secrets.state");

?>