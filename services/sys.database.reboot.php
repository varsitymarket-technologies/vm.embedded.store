<?php
#   TITLE   : System Database Restoration   
#   DESC    : This script is required to handle the main database restoration function.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/02

// Ensure the build directory exists as scripts.php expects it for the database file
$buildDir = dirname( dirname(__FILE__) ).'/build/'; 
if (!is_dir($buildDir)) {
    mkdir($buildDir, 0777, true);
}

$buildFile = $buildDir."vm.engine.sql"; 
if (!file_exists($buildFile)){
    file_put_contents($buildFile,null); 
}

require_once dirname(dirname(__FILE__)).'/scripts.php';

// Access the database object initialized in scripts.php
$db = __DB_MODULE__;
$db->override_connection($buildFile); 

$sql_account = "CREATE TABLE IF NOT EXISTS sys_account (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    image TEXT,
    hash_key TEXT,
    auth TEXT UNIQUE,
    data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)"; 

$sql_banking = "CREATE TABLE IF NOT EXISTS sys_banking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_index TEXT,
    signature_key TEXT,
    data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_index) REFERENCES sys_account(auth)
)"; 

$sql_auth = "CREATE TABLE IF NOT EXISTS sys_auth (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL,
    value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (key) REFERENCES sys_account(auth)
)";

$sql_websites = "CREATE TABLE IF NOT EXISTS sys_websites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    domain TEXT NOT NULL,
    theme TEXT,
    hash_key TEXT,
    account_index TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_index) REFERENCES sys_account(auth)
)"; 


// Execute creation queries

$db->query($sql_account); 
$account_columns = $db->query("PRAGMA table_info(sys_account)");
$account_column_names = [];
foreach ($account_columns as $account_column) {
    $account_column_names[] = $account_column['name'] ?? '';
}
if (!in_array('password_hash', $account_column_names, true)) {
    $db->query("ALTER TABLE sys_account ADD COLUMN password_hash TEXT");
}
if (!in_array('email_verified', $account_column_names, true)) {
    $db->query("ALTER TABLE sys_account ADD COLUMN email_verified INTEGER NOT NULL DEFAULT 0");
}
$db->query("CREATE TABLE IF NOT EXISTS sys_account_otp (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_auth TEXT NOT NULL,
    otp_code TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    verified INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (account_auth) REFERENCES sys_account(auth) ON DELETE CASCADE
)");
$db->query("CREATE INDEX IF NOT EXISTS idx_sys_account_otp_auth ON sys_account_otp(account_auth)");

#Restore The Banking Table
$db->query($sql_banking); 
$db->query($sql_auth); 
#Restore The Websites Table
$db->query($sql_websites);
?>