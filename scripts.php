<?php 
#   TITLE   : Application Scripts   
#   DESC    : The scripts that are handling the admin control functions 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30

define("__DB_MODULE__",initiate_database()); 
define("__DB_WEBSITE__",initiate_web_database()); 

function get_private_db($domain) {
    if (empty($domain)) return null;
    $store_hash = hash('sha256', $domain);
    $base_dir = (dirname(__FILE__));
    $private_dir = dirname($base_dir) . "/data/" . $store_hash;
    if (!is_dir(dirname($base_dir) . "/data/") && !@mkdir(dirname($base_dir) . "/data/", 0755, true)) {
        $private_dir = $base_dir . "/build/data/" . $store_hash;
    }
    if (!is_dir($private_dir)) {
        @mkdir($private_dir, 0755, true);
    }
    $db_path = $private_dir . "/" . $domain;
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key_name VARCHAR(255),
        api_key VARCHAR(255) UNIQUE,
        active INTEGER DEFAULT 1,
        last_used DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        api_key VARCHAR(255),
        endpoint VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS cors_domains (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        domain VARCHAR(255) UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

function kick_start_application($domain){    
    $key_name = htmlspecialchars(trim('STORE_WEBSITE'), ENT_QUOTES, 'UTF-8');
    $prefix = 'vm_app_';
    $api_key = $prefix . bin2hex(random_bytes(24));
    $private_pdo = get_private_db($domain);
    if ($private_pdo) {
        $stmt = $private_pdo->prepare("INSERT INTO api_keys (key_name, api_key, active) VALUES (?, ?, 1)");
        $stmt->execute([$key_name, $api_key]);
    }
    
    $cors_domain = trim($domain);
    if (!empty($cors_domain)) {
        // Normalize: strip trailing slashes, ensure scheme
        $cors_domain = rtrim($cors_domain, '/');
        if (!preg_match('#^https?://#', $cors_domain)) {
            $cors_domain = 'https://' . $cors_domain;
        }
        $private_pdo = get_private_db($domain);
        if ($private_pdo) {
            $stmt = $private_pdo->prepare("INSERT OR IGNORE INTO cors_domains (domain) VALUES (?)");
            $stmt->execute([$cors_domain]);
        }
    }

    $cors_domain = trim("localhost");
    if (!empty($cors_domain)) {
        // Normalize: strip trailing slashes, ensure scheme
        $cors_domain = rtrim($cors_domain, '/');
        if (!preg_match('#^https?://#', $cors_domain)) {
            $cors_domain = 'https://' . $cors_domain;
        }
        $private_pdo = get_private_db($domain);
        if ($private_pdo) {
            $stmt = $private_pdo->prepare("INSERT OR IGNORE INTO cors_domains (domain) VALUES (?)");
            $stmt->execute([$cors_domain]);
        }
    }

}


function get_store_keys($domain){
    $private_pdo = get_private_db($domain);
    if ($private_pdo) {
        $stmt = $private_pdo->prepare("SELECT * FROM api_keys ORDER BY created_at DESC");
        $stmt->execute();
        $api_keys = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $api_keys; 
    }
}

function get_default_keys($domain){
    $e = get_store_keys($domain);

    foreach ($e as $key => $value) {
        if ($value['key_name'] == "STORE_WEBSITE"){
            return $value['api_key']; 
        }
    }

}

function get_store_id( $domain , $db_engine = __DB_MODULE__ ){
    $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
    if (empty($store_record) && !empty($domain)) {
        $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE domain = ? LIMIT 1", [$domain]);
    }
    $store_id = $store_record[0]['id'] ?? '';
    return $store_id; 
}


function get_domain(){
    // Standard method
    $domain = $_SERVER['HTTP_HOST'] ?? false ;
    if ($domain == false){
        $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
    }

    return $domain; 
}


function map(){
    $e = [
    "auth" => "page.auth.php",
    "home" => "page.dashboard.php",
    "payments" => "page.payments.php",
    "theme" => "page.theme.php",
    "export-frame" => "page.export.frame.php",
    "export-link" => "page.export.link.php",
    "export-source" => "page.export.code.php",
];
    return $e; 
}

function account_data($index){
    @include_once "config.php"; 
    $AUTH = __ACCOUNT_INDEX__; 
    $tbl_index = $index; 
    $sql = "SELECT * FROM `sys_account` WHERE (`auth` = ?) LIMIT 1";
    $e = __DB_MODULE__->query($sql, [$AUTH]); 
    $result = $e[0][$tbl_index] ?? false;
    return $result; 
}

function website_data($index,$auth=false){
    @include_once "config.php"; 
    if ($auth == false){
        $AUTH = __ACCOUNT_INDEX__;
    }else{
        $AUTH = $auth;
    } 
    $tbl_index = $index; 
    $sql = "SELECT * FROM `sys_websites` WHERE (`account_index` = ?) LIMIT 1";
    $e = __DB_MODULE__->query($sql, [$AUTH]);  
    $result = $e[0][$tbl_index] ?? false;
    return $result; 
}

function banking_data($index){
    @include_once "config.php";
    $AUTH = __ACCOUNT_INDEX__; 
    $tbl_index = $index; 
    $sql = "SELECT * FROM `sys_banking` WHERE (`account_index` = ?) LIMIT 1";
    $e = __DB_MODULE__->query($sql, [$AUTH]); 
    
    try {    
        $signature_key = $e[0]['signature_key']; 
        $result = $e[0]['data']; 
        $output = __decryption__($result,$signature_key); 
        $output = json_decode($output,JSON_PRETTY_PRINT); 
        $output = $output[$index]; 
        return $output;    
    } catch (\Throwable $th) {
        return false;
    }
}

function admin_percentage(){
    return 20; 
}

function ex($section = 1)
{
    $x = $_SERVER['REQUEST_URI'];
    // Strip query string from the URI to ensure clean segment matching
    $x = strtok($x, '?');
    $_xm = explode("/", $x);
    return $_xm[$section] ?? '';
}


    function __account_index__(){
        try {
            
            $source = $_SESSION['vm_index']; 
            $source2 = $_SESSION['vm_key']; 

            $index = base_decryption($source); 
            $key = base64_decode($source2); 
            $e = __decryption__($index,$key); 
            return $e; 
            
        } catch (\Throwable $th) {
            return false;
        }
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

        kick_start_application($domain); 
        return true; 
    }

function initiate_web_database(){
    @include_once "config.php"; 
    if (!defined('__DOMAIN__')){
        trigger_error('Failed To Locate Defined Database');   
        return null;  
    }

    $file = dirname(__FILE__)."/sites/".__DOMAIN__."/storage.data";
    if (__DOMAIN__ == null){
        return null;
    }
    $db_file = dirname(__FILE__)."/module/database.php"; 
    @include_once $db_file;  
    $e = new database_manager($file); 
    return $e; 
}

function initiate_sensitive_database(){
    @include_once "config.php";
    if (!defined('__DOMAIN__')){
        return null;
    }
    $file = dirname(__FILE__)."/sites/".__DOMAIN__."/sensitive.data";
    $db_file = dirname(__FILE__)."/module/database.php";
    @include_once $db_file;
    $e = new database_manager($file);
    // Create the settings table if it doesn't exist
    $e->createTable("settings", [
        "key" => "VARCHAR(255) PRIMARY KEY",
        "value" => "TEXT"
    ]);
    return $e;
}


function initiate_private_database($domain){
    if (empty($domain)){
        return null;
    }
    $store_hash = hash('sha256', $domain);
    $private_dir = dirname(dirname(__FILE__))."/data/".$store_hash;
    // Fallback to local build dir if the external data dir isn't writable
    if (!is_dir(dirname(dirname(__FILE__))."/data/") && !@mkdir(dirname(dirname(__FILE__))."/data/", 0755, true)) {
        $private_dir = dirname(__FILE__)."/build/data/".$store_hash;
    }
    if (!is_dir($private_dir)){
        @mkdir($private_dir, 0755, true);
    }
    $file = $private_dir."/".$domain;
    $db_file = dirname(__FILE__)."/module/database.php";
    @include_once $db_file;
    $e = new database_manager($file);
    // Create API keys table
    $e->createTable("api_keys", [
        "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
        "key_name" => "VARCHAR(255)",
        "api_key" => "VARCHAR(255) UNIQUE",
        "active" => "INTEGER DEFAULT 1",
        "last_used" => "DATETIME",
        "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
    ]);
    // Create API access logs table
    $e->createTable("api_logs", [
        "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
        "api_key" => "VARCHAR(255)",
        "endpoint" => "VARCHAR(255)",
        "ip_address" => "VARCHAR(45)",
        "user_agent" => "TEXT",
        "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
    ]);
    // Create CORS whitelisted domains table
    $e->createTable("cors_domains", [
        "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
        "domain" => "VARCHAR(255) UNIQUE",
        "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
    ]);
    return $e;
}

function debug($output){
    file_put_contents(dirname(__FILE__).'/build/raw.debug',$output,FILE_APPEND); 
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

    $content = file_get_contents($filePath);
    $pattern = '/e\((__[A-Z_]+__)\)/';

    // 4. Perform the search
    if (preg_match_all($pattern, $content, $matches)) {
        // $matches[1] contains the values inside the capture group parentheses
        $e = $matches[1]; 
        return $matches[1];
    }

    return []; // Return empty array if no matches found
}


function load_env($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments starting with #
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Split by the first '=' found
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove surrounding quotes if they exist
        $value = trim($value, '"\'');

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

function delete_folder($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!delete_folder($dir . "/" . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}
