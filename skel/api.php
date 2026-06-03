<?php
#   TITLE   : Micro API
#   DESC    : This will act as the websites micro api services.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.2.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/04/24

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

@include_once dirname(dirname(dirname(__FILE__))). "/scripts.php";
$db = __DB_MODULE__;
$db->override_connection(dirname(__FILE__).'/storage.data');

// --- Customer auth module includes (sub-project D.1) ---
$_vm_module_dir = null;
foreach ([dirname(__FILE__), dirname(dirname(__FILE__)), dirname(dirname(dirname(__FILE__)))] as $_vm_candidate) {
    if (is_dir($_vm_candidate . '/module') && file_exists($_vm_candidate . '/module/customer_auth.php')) {
        $_vm_module_dir = $_vm_candidate . '/module';
        break;
    }
}
if ($_vm_module_dir !== null) {
    @include_once $_vm_module_dir . '/customer_auth.php';
    @include_once $_vm_module_dir . '/customer_account.php';
}

// --- Customer token extraction (mirrors api/index.php helper) ---
if (!function_exists('extract_customer_token')) {
    function extract_customer_token(): ?string {
        $tok = $_SERVER['HTTP_X_CUSTOMER_TOKEN'] ?? '';
        $tok = is_string($tok) ? trim($tok) : '';
        return $tok === '' ? null : $tok;
    }
}

// Ensure required tables exist
$db->createTable("page_views", [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
    "user_agent" => "TEXT",
    "ip_address" => "TEXT",
    "state" => "VARCHAR(255)",
    "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
]);
$db->createTable("orders", [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
    "customer_name" => "VARCHAR(255)",
    "customer_email" => "VARCHAR(255)",
    "total_amount" => "DECIMAL(10,2)",
    "items" => "TEXT",
    "status" => "VARCHAR(50) DEFAULT 'pending'",
    "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
]);

// --- Customer auth schema (sub-project D.1) ---
$db->query("CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    name TEXT,
    phone TEXT,
    email_verified INTEGER NOT NULL DEFAULT 0,
    failed_login_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->query("CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email COLLATE NOCASE)");
$db->query("CREATE TABLE IF NOT EXISTS customer_sessions (
    token TEXT PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    user_agent TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
)");
$db->query("CREATE INDEX IF NOT EXISTS idx_sessions_customer ON customer_sessions(customer_id)");

// Idempotently add customer_id to orders (same PRAGMA guard as services/database.install.php)
$order_cols = $db->query("PRAGMA table_info(orders)");
$has_customer_id = false;
foreach ($order_cols as $col) {
    if (($col['name'] ?? '') === 'customer_id') { $has_customer_id = true; break; }
}
if (!$has_customer_id) {
    $db->query("ALTER TABLE orders ADD COLUMN customer_id INTEGER REFERENCES customers(id)");
}
$db->query("CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id)");

$db->query("INSERT INTO page_views (user_agent, ip_address, state) VALUES (?, ?, ?)", [$_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '', $_GET['state'] ?? 'index']);

$request = $_GET['state'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// GET Requests
if ($method === 'GET') {
    if ($request == "products") {
        $data = $db->query("SELECT * FROM products ORDER BY id DESC");
        foreach ($data as $key => $value) {
            $data[$key]['price'] = (float) $value['price'];
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
            $data[$key]['category_id'] = (int) ($value['category_id'] ?? 0);
        }
        echo json_encode($data);
    }

    elseif ($request == "product") {
        $id = $_GET['id'] ?? 0;
        $data = $db->query("SELECT * FROM products WHERE id = ?", [$id]);
        if ($data) {
            $data[0]['price'] = (float) $data[0]['price'];
            $data[0]['id'] = (int) $data[0]['id'];
            $data[0]['stock'] = (int) ($data[0]['stock'] ?? 0);
            $data[0]['category_id'] = (int) ($data[0]['category_id'] ?? 0);
            echo json_encode($data[0]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Product not found"]);
        }
    }

    elseif ($request == "categories") {
        $data = $db->query("SELECT * FROM categories ORDER BY name ASC");
        foreach ($data as $key => $value) {
            $data[$key]['id'] = (int) $value['id'];
        }
        echo json_encode($data);
    }

    elseif ($request == "products_by_category") {
        $cat_id = $_GET['category_id'] ?? 0;
        $data = $db->query("SELECT * FROM products WHERE category_id = ? ORDER BY id DESC", [$cat_id]);
        foreach ($data as $key => $value) {
            $data[$key]['price'] = (float) $value['price'];
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
        }
        echo json_encode($data);
    }

    elseif ($request == "search") {
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            echo json_encode([]);
        } else {
            $search_term = '%' . $query . '%';
            $data = $db->query("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC", [$search_term, $search_term]);
            foreach ($data as $key => $value) {
                $data[$key]['price'] = (float) $value['price'];
                $data[$key]['id'] = (int) $value['id'];
                $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
            }
            echo json_encode($data);
        }
    }

    elseif ($request == "discounts") {
        $data = $db->query("SELECT * FROM discounts WHERE active = 1 ORDER BY id DESC");
        if (!$data) $data = [];
        foreach ($data as $key => $value) {
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['percentage'] = (float) ($value['percentage'] ?? 0);
        }
        echo json_encode($data);
    }

    elseif ($request == "site") {
        $site_name = function_exists('website_data') ? website_data('name') : 'My Store';
        echo json_encode([
            "name" => $site_name,
            "currency" => defined('__CURRENCY_SIGN__') ? __CURRENCY_SIGN__ : '$',
            "domain" => $_SERVER['HTTP_HOST']
        ]);
    }

    elseif ($request == "orders") {
        $email = $_GET['email'] ?? '';
        if (!empty($email)) {
            $data = $db->query("SELECT id, customer_name, total_amount, items, status, created_at FROM orders WHERE customer_email = ? ORDER BY created_at DESC", [$email]);
        } else {
            $data = [];
        }
        foreach ($data as $key => $value) {
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['total_amount'] = (float) $value['total_amount'];
            $data[$key]['items'] = json_decode($value['items'] ?? '[]', true);
        }
        echo json_encode($data);
    }

    // --- Customer account: GET customer_me (sub-project D.1) ---
    elseif ($request == "customer_me") {
        if (!function_exists('customer_resolve_token')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        echo json_encode(["ok" => true, "customer" => $customer]);
        exit;
    }

    // --- Customer account: GET customer_my_orders (sub-project D.1) ---
    elseif ($request == "customer_my_orders") {
        if (!function_exists('customer_resolve_token') || !function_exists('customer_my_orders')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        echo json_encode(customer_my_orders($db, (int)$customer['id']));
        exit;
    }

    else {
        echo json_encode(["status" => "ok", "endpoints" => ["products", "product", "categories", "products_by_category", "search", "discounts", "site", "orders", "order", "customer_me", "customer_my_orders", "customer_register", "customer_login", "customer_logout", "customer_update_profile", "customer_change_password"]]);
    }
}

// POST Requests
elseif ($method === 'POST') {
    if ($request == "order") {
        $input = json_decode(file_get_contents('php://input'), true);

        $customer_name = $input['name'] ?? '';
        $customer_email = $input['email'] ?? '';
        $total_amount = $input['total'] ?? 0;
        $items = json_encode($input['items'] ?? []);

        if (empty($customer_name) || empty($total_amount)) {
            http_response_code(400);
            echo json_encode(["error" => "Incomplete order data"]);
            exit;
        }

        $sql = "INSERT INTO orders (customer_name, customer_email, total_amount, items, status, created_at) VALUES (?, ?, ?, ?, 'pending', datetime('now'))";
        $result = $db->query($sql, [$customer_name, $customer_email, $total_amount, $items]);

        if ($result) {
            echo json_encode(["success" => true, "message" => "Order placed successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to save order"]);
        }
    }

    // --- Customer account: POST customer_register (sub-project D.1) ---
    elseif ($request == "customer_register") {
        if (!function_exists('customer_register')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $name = $input['name'] ?? null;
        $phone = $input['phone'] ?? null;
        $result = customer_register($db, $email, $password, $name, $phone);
        if (!$result['ok']) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_login (sub-project D.1) ---
    elseif ($request == "customer_login") {
        if (!function_exists('customer_login')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $result = customer_login($db, $email, $password, $userAgent);
        if (!$result['ok']) {
            http_response_code(($result['code'] ?? '') === 'locked' ? 429 : 401);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_logout (sub-project D.1) ---
    elseif ($request == "customer_logout") {
        if (!function_exists('customer_logout')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        echo json_encode(customer_logout($db, $token));
        exit;
    }

    // --- Customer account: POST customer_update_profile (sub-project D.1) ---
    elseif ($request == "customer_update_profile") {
        if (!function_exists('customer_resolve_token') || !function_exists('customer_update_profile')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
        $name = $input['name'] ?? null;
        $phone = $input['phone'] ?? null;
        $result = customer_update_profile($db, (int)$customer['id'], $name, $phone);
        if (!$result['ok']) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_change_password (sub-project D.1) ---
    elseif ($request == "customer_change_password") {
        if (!function_exists('customer_resolve_token') || !function_exists('customer_change_password')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
        $currentPw = $input['current_password'] ?? '';
        $newPw = $input['new_password'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $result = customer_change_password($db, (int)$customer['id'], $currentPw, $newPw, $userAgent);
        if (!$result['ok']) {
            http_response_code(stripos($result['error'] ?? '', 'current password') !== false ? 401 : 400);
        }
        echo json_encode($result);
        exit;
    }
}
?>