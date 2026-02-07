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

#Restore The Banking Table
$db->query($sql_banking); 
$db->query($sql_auth); 
#Restore The Websites Table
$db->query($sql_websites);
exit(); 
?>