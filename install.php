<?php
// Ensure the build directory exists as scripts.php expects it for the database file
$buildDir = __DIR__ . '/build';
if (!is_dir($buildDir)) {
    mkdir($buildDir, 0777, true);
}

require_once 'scripts.php';

// Access the database object initialized in scripts.php
$db = __DB_MODULE__;

echo "Starting database installation...<br>";

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
echo "Table 'categories' checked/created.<br>";

$db->query($sql_products);
echo "Table 'products' checked/created.<br>";

$db->query($sql_sales);
echo "Table 'sales' checked/created.<br>";

$db->query($sql_orders);
echo "Table 'orders' checked/created.<br>";

$db->query($sql_settings);
echo "Table 'settings' checked/created.<br>";

echo "Installation complete.";
?>