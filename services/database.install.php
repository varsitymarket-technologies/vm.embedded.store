<?php
#   TITLE   : Database Installation   
#   DESC    : This script is required to handle the main database restoration function.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30

// Ensure the build directory exists as scripts.php expects it for the database file
$anchor_sites = "reiddrop.com"; 
$buildDir = dirname( dirname(__FILE__) ).'/sites/'.$anchor_sites; 
if (!is_dir($buildDir)) {
    mkdir($buildDir, 0777, true);
}

require_once '../scripts.php';

$buildFile = $buildDir."/storage.data"; 
// Access the database object initialized in scripts.php
$db = __DB_MODULE__;
$db->override_connection($buildFile); 

echo "Starting database installation...\n\n";

// 1. Categories Table
$sql_categories = "CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    image TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

// 2. Products Table
$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    image TEXT,
    stock INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
)";

// 3. Sales Table
$sql_sales = "CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    percentage REAL DEFAULT 0,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

// 4. Orders Table
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    customer_email TEXT,
    total_amount REAL DEFAULT 0,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

// 5. Settings Table
$sql_settings = "CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

// Execute creation queries
// Assuming database_manager has a query() method
$db->query($sql_categories);
echo "Table 'categories' checked/created.\n";

$db->query($sql_products);
echo "Table 'products' checked/created.\n";

$db->query($sql_sales);
echo "Table 'sales' checked/created.\n";

$db->query($sql_orders);
echo "Table 'orders' checked/created.\n";

$db->query($sql_settings);
echo "Table 'settings' checked/created.\n";

echo "\nInstallation complete.";
?>