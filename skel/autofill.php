<?php 


function construct_config_web_structure(){
    $skel_proto = "http"; 
    $skel_domain = "localhost:8016";
    $store_id = get_store_id("laurencia.com"); 
    $store_api_keys = get_default_keys("laurencia.com"); 
    $data_set = ""; 

    $value = "__SYSTEM_API__"; 
    $data_set .= 'define("'.$value.'","'.$skel_proto.'://'.$skel_domain.'/store-access/'.$store_id.'/");'.PHP_EOL;
    $value = "__SYSTEM_API_KEYS__"; 
    $data_set .= 'define("'.$value.'","'.$store_api_keys.'");'.PHP_EOL;
    $value = "__STORE_INDEX__"; 
    $data_set .= 'define("'.$value.'","'.$store_id.'");'.PHP_EOL;
    $value = "__SYSTEM_JS_API__" ; 
        $data_set .= 'define("'.$value.'","'.$skel_proto.'://'.$skel_domain.'/skel/vm.api.js");'.PHP_EOL;
    $value = "__SYSTEM_JS_THEME__" ; 
        $data_set .= 'define("'.$value.'","'.$skel_proto.'://'.$skel_domain.'/skel/vm.theme.js");'.PHP_EOL;
     $value = "__SYSTEM_JS_CONNECT__" ; 
        $data_set .= 'define("'.$value.'","'.$skel_proto.'://'.$skel_domain.'/skel/vm.connect.js");'.PHP_EOL;
     $value = "__SYSTEM_CURRENCY__" ; 
        $data_set .= 'define("'.$value.'","'.__CURRENCY_SIGN__.'");'.PHP_EOL;
     $value = "__SITE_TITLE__" ; 
        $data_set .= 'define("'.$value.'","'.website_data('name').'");'.PHP_EOL;

    return $data_set; 
}


?>