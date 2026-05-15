<?php
#   TITLE   : Store Access API Router
#   DESC    : Public API gateway for external store access via API keys
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/05/15

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Parse store ID from URL: /store-access/{store-id}/?state=endpoint
$store_id = ex(2);

if (empty($store_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing store ID", "usage" => "/store-access/{store-id}/?state={endpoint}"]);
    exit;
}

// Lookup the store domain from sys_websites using the store ID
$db_engine = __DB_MODULE__;
$site = $db_engine->query("SELECT * FROM sys_websites WHERE id = ? LIMIT 1", [$store_id]);

if (empty($site)) {
    http_response_code(404);
    echo json_encode(["error" => "Store not found"]);
    exit;
}

$domain = $site[0]['domain'];
$store_name = $site[0]['name'];

// Initialize the private database (API keys, logs — stored outside web root)
$private_db = initiate_private_database($domain);

if (!$private_db) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to initialize store"]);
    exit;
}

// Validate API key from header or query parameter
$api_key = '';
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $api_key = $_SERVER['HTTP_X_API_KEY'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (strpos($auth, 'Bearer ') === 0) {
        $api_key = substr($auth, 7);
    }
} elseif (isset($_GET['api_key'])) {
    $api_key = $_GET['api_key'];
}

if (empty($api_key)) {
    http_response_code(401);
    echo json_encode(["error" => "API key required", "hint" => "Pass via X-API-Key header, Authorization: Bearer {key}, or ?api_key={key}"]);
    exit;
}

// Verify API key against private database
$key_record = $private_db->query("SELECT * FROM api_keys WHERE api_key = ? AND active = 1 LIMIT 1", [$api_key]);

if (empty($key_record)) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid or revoked API key"]);
    exit;
}

// Update last_used timestamp
$private_db->query("UPDATE api_keys SET last_used = datetime('now') WHERE api_key = ?", [$api_key]);

// Log the API request
$private_db->query("INSERT INTO api_logs (api_key, endpoint, ip_address, user_agent) VALUES (?, ?, ?, ?)", [
    $api_key,
    $_GET['state'] ?? 'index',
    $_SERVER['REMOTE_ADDR'] ?? '',
    $_SERVER['HTTP_USER_AGENT'] ?? ''
]);

// Connect to the store's public database (products, categories, etc.)
$public_db_path = dirname(__FILE__) . "/../sites/" . $domain . "/storage.data";

if (!file_exists($public_db_path)) {
    http_response_code(404);
    echo json_encode(["error" => "Store data not found"]);
    exit;
}

@include_once dirname(__FILE__) . "/../module/database.php";
$db = new database_manager($public_db_path);

// Route to the requested endpoint
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
        echo json_encode(["success" => true, "data" => $data]);
    }

    elseif ($request == "product") {
        $id = $_GET['id'] ?? 0;
        $data = $db->query("SELECT * FROM products WHERE id = ?", [$id]);
        if ($data) {
            $data[0]['price'] = (float) $data[0]['price'];
            $data[0]['id'] = (int) $data[0]['id'];
            $data[0]['stock'] = (int) ($data[0]['stock'] ?? 0);
            $data[0]['category_id'] = (int) ($data[0]['category_id'] ?? 0);
            echo json_encode(["success" => true, "data" => $data[0]]);
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
        echo json_encode(["success" => true, "data" => $data]);
    }

    elseif ($request == "products_by_category") {
        $cat_id = $_GET['category_id'] ?? 0;
        $data = $db->query("SELECT * FROM products WHERE category_id = ? ORDER BY id DESC", [$cat_id]);
        foreach ($data as $key => $value) {
            $data[$key]['price'] = (float) $value['price'];
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
        }
        echo json_encode(["success" => true, "data" => $data]);
    }

    elseif ($request == "search") {
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            echo json_encode(["success" => true, "data" => []]);
        } else {
            $search_term = '%' . $query . '%';
            $data = $db->query("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC", [$search_term, $search_term]);
            foreach ($data as $key => $value) {
                $data[$key]['price'] = (float) $value['price'];
                $data[$key]['id'] = (int) $value['id'];
                $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
            }
            echo json_encode(["success" => true, "data" => $data]);
        }
    }

    elseif ($request == "discounts") {
        $data = $db->query("SELECT * FROM discounts WHERE active = 1 ORDER BY id DESC");
        if (!$data) $data = [];
        foreach ($data as $key => $value) {
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['percentage'] = (float) ($value['percentage'] ?? 0);
        }
        echo json_encode(["success" => true, "data" => $data]);
    }

    elseif ($request == "site") {
        echo json_encode([
            "success" => true,
            "data" => [
                "name" => $store_name,
                "store_id" => $store_id
            ]
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
        echo json_encode(["success" => true, "data" => $data]);
    }

    else {
        echo json_encode([
            "success" => true,
            "store" => $store_name,
            "store_id" => $store_id,
            "endpoints" => [
                "GET" => ["products", "product?id={id}", "categories", "products_by_category?category_id={id}", "search?q={query}", "discounts", "site", "orders?email={email}"],
                "POST" => ["order"]
            ]
        ]);
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

        // Ensure orders table exists
        $db->createTable("orders", [
            "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
            "customer_name" => "VARCHAR(255)",
            "customer_email" => "VARCHAR(255)",
            "total_amount" => "DECIMAL(10,2)",
            "items" => "TEXT",
            "status" => "VARCHAR(50) DEFAULT 'pending'",
            "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
        ]);

        $sql = "INSERT INTO orders (customer_name, customer_email, total_amount, items, status, created_at) VALUES (?, ?, ?, ?, 'pending', datetime('now'))";
        $result = $db->query($sql, [$customer_name, $customer_email, $total_amount, $items]);

        echo json_encode(["success" => true, "message" => "Order placed successfully"]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found"]);
    }
}

else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>
