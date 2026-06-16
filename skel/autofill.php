<?php 


function construct_config_web_structure(){
    $file = (__FILE__);
    $domain_ = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR;
    $domain = str_ireplace($domain_, '',dirname($file));


    $skel_proto = "http"; 
    $skel_domain = get_domain();
    $store_id = get_store_id($domain); 
    $store_api_keys = get_default_keys($domain); 
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