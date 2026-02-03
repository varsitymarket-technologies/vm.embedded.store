<?php 
#   TITLE   : Application Scripts   
#   DESC    : The scripts that are handling the admin control functions 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30

define("__DB_MODULE__",initiate_database()); 
@define("__DB_WEBSITE__",initiate_web_database()); 

function ex($section = 1)
{
    $url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

    $x = $_SERVER['REQUEST_URI'];
    $_xm = explode("/", $x);
    return $_xm[$section];
}

function base_encryption($plaintext) {
    $key= create_enc_key(); 
    $e = __encryption__($plaintext,$key); 
    return $e; 
}

function base_decryption($ciphertext) {
    $key= create_enc_key(); 
    $e = __decryption__($ciphertext,$key); 
    return $e; 
}

function __encryption__($data,$key){
    $plaintext = $data; 
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $ciphertext);
}

function __decryption__($data,$key) {
    $ciphertext = $data; 
    $ciphertext = base64_decode($ciphertext ?? '');
    $iv = substr($ciphertext, 0, openssl_cipher_iv_length('aes-256-cbc'));
    $plaintext = openssl_decrypt(substr($ciphertext, openssl_cipher_iv_length('aes-256-cbc')), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $plaintext;
}



function create_enc_key(){
    $default = 'POIETRWQITHASURTO3985HD8JD7549DYH58FY'; 
    return $default; 
}


    function database_services($domain){
        if (!defined('__ANCHOR_SITE__')){
            define("__ANCHOR_SITE__",$domain); 
        }
        $service = dirname(__FILE__)."/services/database.install.php"; 
        @include_once $service; 
        return true; 
    }

    function website_services($domain,$theme){
        if (!defined('__ANCHOR_SITE__')){
            define("__ANCHOR_SITE__",$domain); 
        }
        if (!defined('__ANCHOR_THEME__')){
            define("__ANCHOR_THEME__",$theme); 
        }
        $service = dirname(__FILE__)."/services/website.install.php"; 
        @include_once $service; 
        return true; 
    }

function initiate_web_database(){
    if (!defined('__DOMAIN__')){
        //trigger_error('Failed To Locate Defined Database');   
        return null;  
    }

    $file = dirname(__FILE__)."/sites/".__DOMAIN__."/storage.data";
    $db_file = dirname(__FILE__)."/module/database.php"; 
    @include_once $db_file;  
    $e = new database_manager($file); 
    return $e; 
}

function initiate_database(){
    $file = dirname(__FILE__)."/build/vm.engine.sql";
    $db_file = dirname(__FILE__)."/module/database.php"; 
    @include_once $db_file;  
    $e = new database_manager($file); 
    return $e; 
}

function slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

function extract_theme_nodes($filePath) {
    // 1. Check if the file exists to avoid errors
    if (!file_exists($filePath)) {
        return "Error: File not found.";
    }

    // 2. Read the file content into a string
    $content = file_get_contents($filePath);

    /**
     * 3. The Regex Pattern:
     * e\(          -> Matches the literal 'e('
     * (            -> Starts a capture group
     * __[A-Z_]+__ -> Matches double underscores, uppercase letters/underscores, then double underscores
     * )            -> Ends the capture group
     * \)           -> Matches the literal closing ')'
     */
    $pattern = '/e\((__[A-Z_]+__)\)/';

    // 4. Perform the search
    if (preg_match_all($pattern, $content, $matches)) {
        // $matches[1] contains the values inside the capture group parentheses
        $e = $matches[1]; 
        return $matches[1];
    }

    return []; // Return empty array if no matches found
}
