<?php
#   TITLE   : Store Access API Router
#   DESC    : Public API gateway for external store access via API keys
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 2.0.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/05/15

// CORS headers set after origin check below
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key, X-Customer-Token");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Parse store ID from URL: /store-access/{store-id}/?state=endpoint
$store_id = ex(2);

if (empty($store_id)) {
    header("Access-Control-Allow-Origin: *");
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
    http_response_code(400);
    echo json_encode(["error" => "Missing store ID", "usage" => "/store-access/{store-id}/?state={endpoint}"]);
    exit;
}

// Lookup the store domain from sys_websites using the store ID
$db_engine = __DB_MODULE__;
$site = $db_engine->query("SELECT * FROM sys_websites WHERE id = ? LIMIT 1", [$store_id]);

if (empty($site)) {
    header("Access-Control-Allow-Origin: *");
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
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

// Create cart session tables in private DB
$private_db->createTable("cart_sessions", [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
    "cart_id" => "VARCHAR(64) UNIQUE",
    "items" => "TEXT DEFAULT '[]'",
    "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP",
    "updated_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
]);

$private_db->createTable("checkout_sessions", [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
    "session_id" => "VARCHAR(64) UNIQUE",
    "cart_id" => "VARCHAR(64)",
    "customer_name" => "VARCHAR(255)",
    "customer_email" => "VARCHAR(255)",
    "customer_phone" => "VARCHAR(50)",
    "customer_address" => "TEXT",
    "total_amount" => "DECIMAL(10,2)",
    "status" => "VARCHAR(50) DEFAULT 'pending'",
    "return_url" => "TEXT",
    "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
]);

// --- CORS origin check against whitelisted domains ---
$request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$cors_whitelist = $private_db->query("SELECT domain FROM cors_domains");
$allowed_origins = [];
if (!empty($cors_whitelist)) {
    foreach ($cors_whitelist as $row) {
        $allowed_origins[] = rtrim($row['domain'], '/');
    }
}

if (empty($allowed_origins)) {
    // No whitelist configured — allow all origins
    header("Access-Control-Allow-Origin: *");
} elseif (!empty($request_origin) && in_array(rtrim($request_origin, '/'), $allowed_origins, true)) {
    // Origin matches whitelist
    header("Access-Control-Allow-Origin: " . $request_origin);
    header("Vary: Origin");
} elseif (empty($request_origin)) {
    // No Origin header (server-to-server, curl, etc.) — allow through
    header("Access-Control-Allow-Origin: *");
} else {
    // Origin not whitelisted
    http_response_code(403);
    echo json_encode(["error" => "Origin not allowed", "origin" => $request_origin]);
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// --- SDK serving route (no API key required) ---
if (ex(3) === 'sdk') {
    $sdk_file = ex(4);
    if (!empty($sdk_file)) {
        $sdk_path = dirname(__FILE__) . "/sdk/" . basename($sdk_file);
        if (file_exists($sdk_path)) {
            header("Content-Type: application/javascript; charset=UTF-8");
            readfile($sdk_path);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "SDK file not found"]);
    exit;
}

// --- API key validation ---
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

// --- Customer token (optional, additive — never replaces store API key) ---
function extract_customer_token(): ?string {
    $tok = $_SERVER['HTTP_X_CUSTOMER_TOKEN'] ?? '';
    $tok = is_string($tok) ? trim($tok) : '';
    return $tok === '' ? null : $tok;
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
@include_once dirname(__FILE__) . "/../module/customer_auth.php";

// --- Helper: enrich cart items with product details ---
function enrich_cart($items_json, $public_db) {
    $items = is_array($items_json) ? $items_json : json_decode($items_json, true);
    if (!is_array($items) || empty($items)) {
        return ["items" => [], "item_count" => 0, "subtotal" => 0];
    }

    $enriched = [];
    $subtotal = 0;
    $item_count = 0;

    foreach ($items as $item) {
        $product_id = (int) ($item['product_id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 1);

        $product = $public_db->query("SELECT * FROM products WHERE id = ? LIMIT 1", [$product_id]);
        if (!empty($product)) {
            $p = $product[0];
            $price = (float) $p['price'];
            $line_total = $price * $quantity;
            $subtotal += $line_total;
            $item_count += $quantity;

            $enriched[] = [
                "product_id" => (int) $p['id'],
                "name" => $p['name'] ?? '',
                "description" => $p['description'] ?? '',
                "price" => $price,
                "image" => $p['image'] ?? '',
                "stock" => (int) ($p['stock'] ?? 0),
                "category_id" => (int) ($p['category_id'] ?? 0),
                "quantity" => $quantity,
                "line_total" => round($line_total, 2)
            ];
        }
    }

    return [
        "items" => $enriched,
        "item_count" => $item_count,
        "subtotal" => round($subtotal, 2)
    ];
}

// --- Helper: return full cart state ---
function get_cart_response($cart_id, $private_db, $public_db) {
    $cart = $private_db->query("SELECT * FROM cart_sessions WHERE cart_id = ? LIMIT 1", [$cart_id]);
    if (empty($cart)) {
        return null;
    }
    $enriched = enrich_cart($cart[0]['items'], $public_db);
    return [
        "cart_id" => $cart_id,
        "items" => $enriched['items'],
        "item_count" => $enriched['item_count'],
        "subtotal" => $enriched['subtotal']
    ];
}

// Route to the requested endpoint
$request = $_GET['state'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// =====================
// GET Requests
// =====================
if ($method === 'GET') {

    // --- Checkout page (HTML render) ---
    if ($request == "checkout") {
        $session_id = $_GET['session_id'] ?? '';
        if (empty($session_id)) {
            header("Content-Type: text/html; charset=UTF-8");
            echo render_checkout_error("Missing session ID");
            exit;
        }

        $checkout = $private_db->query("SELECT * FROM checkout_sessions WHERE session_id = ? LIMIT 1", [$session_id]);
        if (empty($checkout)) {
            header("Content-Type: text/html; charset=UTF-8");
            echo render_checkout_error("Checkout session not found");
            exit;
        }

        $checkout = $checkout[0];
        if ($checkout['status'] === 'completed') {
            header("Content-Type: text/html; charset=UTF-8");
            echo render_checkout_error("This checkout session has already been completed");
            exit;
        }

        // Load cart items
        $cart = $private_db->query("SELECT * FROM cart_sessions WHERE cart_id = ? LIMIT 1", [$checkout['cart_id']]);
        $enriched = enrich_cart($cart[0]['items'] ?? '[]', $db);

        header("Content-Type: text/html; charset=UTF-8");
        echo render_checkout_page($store_name, $store_id, $session_id, $enriched, $checkout['total_amount']);
        exit;
    }

    if ($request == "products") {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $count_result = $db->query("SELECT COUNT(*) as total FROM products");
        $total = (int) ($count_result[0]['total'] ?? 0);

        $data = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?", [$limit, $offset]);
        foreach ($data as $key => $value) {
            $data[$key]['price'] = (float) $value['price'];
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
            $data[$key]['category_id'] = (int) ($value['category_id'] ?? 0);
        }
        echo json_encode([
            "success" => true,
            "data" => $data,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "total" => $total
            ]
        ]);
    }

    elseif ($request == "product") {
        $id = $_GET['id'] ?? 0;
        // Attempt JOIN with categories for category name, fallback to plain query
        $data = [];
        try {
            $data = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? LIMIT 1", [$id]);
        } catch (\Throwable $th) {
            $data = $db->query("SELECT * FROM products WHERE id = ? LIMIT 1", [$id]);
        }
        if (empty($data)) {
            $data = $db->query("SELECT * FROM products WHERE id = ? LIMIT 1", [$id]);
        }

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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $count_result = $db->query("SELECT COUNT(*) as total FROM products WHERE category_id = ?", [$cat_id]);
        $total = (int) ($count_result[0]['total'] ?? 0);

        $data = $db->query("SELECT * FROM products WHERE category_id = ? ORDER BY id DESC LIMIT ? OFFSET ?", [$cat_id, $limit, $offset]);
        foreach ($data as $key => $value) {
            $data[$key]['price'] = (float) $value['price'];
            $data[$key]['id'] = (int) $value['id'];
            $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
        }
        echo json_encode([
            "success" => true,
            "data" => $data,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "total" => $total
            ]
        ]);
    }

    elseif ($request == "search") {
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            echo json_encode(["success" => true, "data" => [], "pagination" => ["page" => 1, "limit" => 50, "total" => 0]]);
        } else {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;
            $search_term = '%' . $query . '%';

            $count_result = $db->query("SELECT COUNT(*) as total FROM products WHERE name LIKE ? OR description LIKE ?", [$search_term, $search_term]);
            $total = (int) ($count_result[0]['total'] ?? 0);

            $data = $db->query("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?", [$search_term, $search_term, $limit, $offset]);
            foreach ($data as $key => $value) {
                $data[$key]['price'] = (float) $value['price'];
                $data[$key]['id'] = (int) $value['id'];
                $data[$key]['stock'] = (int) ($value['stock'] ?? 0);
            }
            echo json_encode([
                "success" => true,
                "data" => $data,
                "pagination" => [
                    "page" => $page,
                    "limit" => $limit,
                    "total" => $total
                ]
            ]);
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
                "store_id" => $store_id,
                "currency" => "R"
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

    elseif ($request == "cart") {
        $cart_id = $_GET['cart_id'] ?? '';
        if (empty($cart_id)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing cart_id parameter"]);
            exit;
        }
        $cart_data = get_cart_response($cart_id, $private_db, $db);
        if ($cart_data === null) {
            http_response_code(404);
            echo json_encode(["error" => "Cart not found"]);
        } else {
            echo json_encode(["success" => true, "data" => $cart_data]);
        }
    }

    // --- Customer auth: GET customer_me ---
    elseif ($request == "customer_me") {
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

    else {
        echo json_encode([
            "success" => true,
            "store" => $store_name,
            "store_id" => $store_id,
            "endpoints" => [
                "GET" => [
                    "products", "products?page=1&limit=20",
                    "product?id={id}", "categories",
                    "products_by_category?category_id={id}",
                    "search?q={query}", "discounts", "site",
                    "orders?email={email}", "cart?cart_id={id}",
                    "checkout&session_id={id}"
                ],
                "POST" => [
                    "order", "cart_create", "cart_add",
                    "cart_update", "cart_remove",
                    "checkout_create", "checkout_complete"
                ]
            ]
        ]);
    }
}

// =====================
// POST Requests
// =====================
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = []; }   // guard against null / non-array body

    if ($request == "order") {
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
    }

    elseif ($request == "cart_create") {
        $cart_id = bin2hex(random_bytes(16));
        $private_db->query("INSERT INTO cart_sessions (cart_id, items) VALUES (?, '[]')", [$cart_id]);
        echo json_encode(["success" => true, "data" => ["cart_id" => $cart_id]]);
    }

    elseif ($request == "cart_add") {
        $cart_id = $input['cart_id'] ?? '';
        $product_id = (int) ($input['product_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 1);

        if (empty($cart_id) || empty($product_id) || $quantity < 1) {
            http_response_code(400);
            echo json_encode(["error" => "Missing or invalid cart_id, product_id, or quantity"]);
            exit;
        }

        // Verify product exists
        $product = $db->query("SELECT id FROM products WHERE id = ? LIMIT 1", [$product_id]);
        if (empty($product)) {
            http_response_code(404);
            echo json_encode(["error" => "Product not found"]);
            exit;
        }

        // Load cart
        $cart = $private_db->query("SELECT * FROM cart_sessions WHERE cart_id = ? LIMIT 1", [$cart_id]);
        if (empty($cart)) {
            http_response_code(404);
            echo json_encode(["error" => "Cart not found"]);
            exit;
        }

        $items = json_decode($cart[0]['items'], true);
        if (!is_array($items)) $items = [];

        // Add or increment
        $found = false;
        foreach ($items as &$item) {
            if ((int) $item['product_id'] === $product_id) {
                $item['quantity'] = (int) $item['quantity'] + $quantity;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $items[] = ["product_id" => $product_id, "quantity" => $quantity];
        }

        $private_db->query("UPDATE cart_sessions SET items = ?, updated_at = datetime('now') WHERE cart_id = ?", [json_encode($items), $cart_id]);

        $cart_data = get_cart_response($cart_id, $private_db, $db);
        echo json_encode(["success" => true, "data" => $cart_data]);
    }

    elseif ($request == "cart_update") {
        $cart_id = $input['cart_id'] ?? '';
        $product_id = (int) ($input['product_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 0);

        if (empty($cart_id) || empty($product_id)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing cart_id or product_id"]);
            exit;
        }

        $cart = $private_db->query("SELECT * FROM cart_sessions WHERE cart_id = ? LIMIT 1", [$cart_id]);
        if (empty($cart)) {
            http_response_code(404);
            echo json_encode(["error" => "Cart not found"]);
            exit;
        }

        $items = json_decode($cart[0]['items'], true);
        if (!is_array($items)) $items = [];

        // Update or remove
        $new_items = [];
        foreach ($items as $item) {
            if ((int) $item['product_id'] === $product_id) {
                if ($quantity > 0) {
                    $item['quantity'] = $quantity;
                    $new_items[] = $item;
                }
                // quantity=0 means remove — skip adding it
            } else {
                $new_items[] = $item;
            }
        }

        $private_db->query("UPDATE cart_sessions SET items = ?, updated_at = datetime('now') WHERE cart_id = ?", [json_encode($new_items), $cart_id]);

        $cart_data = get_cart_response($cart_id, $private_db, $db);
        echo json_encode(["success" => true, "data" => $cart_data]);
    }

    elseif ($request == "cart_remove") {
        $cart_id = $input['cart_id'] ?? '';
        $product_id = (int) ($input['product_id'] ?? 0);

        if (empty($cart_id) || empty($product_id)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing cart_id or product_id"]);
            exit;
        }

        $cart = $private_db->query("SELECT * FROM cart_sessions WHERE cart_id = ? LIMIT 1", [$cart_id]);
        if (empty($cart)) {
            http_response_code(404);
            echo json_encode(["error" => "Cart not found"]);
            exit;
        }

        $items = json_decode($cart[0]['items'], true);
        if (!is_array($items)) $items = [];

        $new_items = [];
        foreach ($items as $item) {
            if ((int) $item['product_id'] !== $product_id) {
                $new_items[] = $item;
            }
        }

        $private_db->query("UPDATE cart_sessions SET items = ?, updated_at = datetime('now') WHERE cart_id = ?", [json_encode($new_items), $cart_id]);

        $cart_data = get_cart_response($cart_id, $private_db, $db);
        echo json_encode(["success" => true, "data" => $cart_data]);
    }

    elseif ($request == "checkout_create") {
        $cart_id = $input['cart_id'] ?? '';
        $return_url = $input['return_url'] ?? '';

        if (empty($cart_id)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing cart_id"]);
            exit;
        }

        // Validate cart exists and has items
        $cart = $private_db->query("SELECT * FROM cart_sessions WHERE cart_id = ? LIMIT 1", [$cart_id]);
        if (empty($cart)) {
            http_response_code(404);
            echo json_encode(["error" => "Cart not found"]);
            exit;
        }

        $items = json_decode($cart[0]['items'], true);
        if (!is_array($items) || empty($items)) {
            http_response_code(400);
            echo json_encode(["error" => "Cart is empty"]);
            exit;
        }

        // Calculate total from cart items
        $enriched = enrich_cart($items, $db);
        $total_amount = $enriched['subtotal'];

        $session_id = bin2hex(random_bytes(16));

        $private_db->query(
            "INSERT INTO checkout_sessions (session_id, cart_id, total_amount, return_url, status) VALUES (?, ?, ?, ?, 'pending')",
            [$session_id, $cart_id, $total_amount, $return_url]
        );

        // Build checkout URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $checkout_url = $protocol . "://" . $host . "/store-access/" . $store_id . "/?state=checkout&session_id=" . $session_id;

        echo json_encode([
            "success" => true,
            "data" => [
                "checkout_url" => $checkout_url,
                "session_id" => $session_id
            ]
        ]);
    }

    elseif ($request == "checkout_complete") {
        $session_id = $input['session_id'] ?? '';
        $customer_name = trim($input['customer_name'] ?? '');
        $customer_email = trim($input['customer_email'] ?? '');
        $customer_phone = trim($input['customer_phone'] ?? '');
        $customer_address = trim($input['customer_address'] ?? '');

        if (empty($session_id) || empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($customer_address)) {
            http_response_code(400);
            echo json_encode(["error" => "All fields required: session_id, customer_name, customer_email, customer_phone, customer_address"]);
            exit;
        }

        // Load checkout session
        $checkout = $private_db->query("SELECT * FROM checkout_sessions WHERE session_id = ? LIMIT 1", [$session_id]);
        if (empty($checkout)) {
            http_response_code(404);
            echo json_encode(["error" => "Checkout session not found"]);
            exit;
        }

        $checkout = $checkout[0];
        if ($checkout['status'] === 'completed') {
            http_response_code(400);
            echo json_encode(["error" => "Checkout session already completed"]);
            exit;
        }

        // Load cart items
        $cart = $private_db->query("SELECT * FROM cart_sessions WHERE cart_id = ? LIMIT 1", [$checkout['cart_id']]);
        $enriched = enrich_cart($cart[0]['items'] ?? '[]', $db);

        // Ensure orders table exists
        $db->createTable("orders", [
            "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
            "customer_name" => "VARCHAR(255)",
            "customer_email" => "VARCHAR(255)",
            "customer_phone" => "VARCHAR(50)",
            "customer_address" => "TEXT",
            "total_amount" => "DECIMAL(10,2)",
            "items" => "TEXT",
            "status" => "VARCHAR(50) DEFAULT 'pending'",
            "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
        ]);

        // Create order in public DB
        $db->query(
            "INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, total_amount, items, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', datetime('now'))",
            [$customer_name, $customer_email, $customer_phone, $customer_address, $checkout['total_amount'], json_encode($enriched['items'])]
        );

        // Get the inserted order ID
        $order = $db->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
        $order_id = (int) ($order[0]['id'] ?? 0);

        // Update checkout session status and customer info
        $private_db->query(
            "UPDATE checkout_sessions SET status = 'completed', customer_name = ?, customer_email = ?, customer_phone = ?, customer_address = ? WHERE session_id = ?",
            [$customer_name, $customer_email, $customer_phone, $customer_address, $session_id]
        );

        // Clear the cart
        $private_db->query("UPDATE cart_sessions SET items = '[]', updated_at = datetime('now') WHERE cart_id = ?", [$checkout['cart_id']]);

        // Build redirect URL
        $return_url = $checkout['return_url'] ?? '';
        $redirect_url = '';
        if (!empty($return_url)) {
            $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
            $redirect_url = $return_url . $separator . "order_id=" . $order_id . "&status=complete";
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "order_id" => $order_id,
                "redirect_url" => $redirect_url
            ]
        ]);
    }

    // --- Customer auth: POST customer_register ---
    elseif ($request == "customer_register") {
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

    // --- Customer auth: POST customer_login ---
    elseif ($request == "customer_login") {
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

    // --- Customer auth: POST customer_logout ---
    elseif ($request == "customer_logout") {
        $token = extract_customer_token();
        echo json_encode(customer_logout($db, $token));
        exit;
    }

    else {
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found"]);
    }
}

else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}

// =====================
// Checkout page renderer
// =====================
function render_checkout_error($message) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="text-center p-8">
        <div class="text-6xl mb-4">&#10060;</div>
        <h1 class="text-2xl font-bold mb-2">Checkout Error</h1>
        <p class="text-gray-400">' . htmlspecialchars($message) . '</p>
    </div>
</body>
</html>';
}

function render_checkout_page($store_name, $store_id, $session_id, $enriched, $total_amount) {
    $items_html = '';
    foreach ($enriched['items'] as $item) {
        $img_html = '';
        if (!empty($item['image'])) {
            $img_html = '<img src="' . htmlspecialchars($item['image']) . '" alt="" class="w-12 h-12 rounded-lg object-cover bg-gray-700">';
        } else {
            $img_html = '<div class="w-12 h-12 rounded-lg bg-gray-700 flex items-center justify-center text-gray-500 text-xs">N/A</div>';
        }
        $items_html .= '
        <div class="flex items-center justify-between py-3 border-b border-gray-700">
            <div class="flex items-center gap-3">
                ' . $img_html . '
                <div>
                    <p class="font-medium text-white">' . htmlspecialchars($item['name']) . '</p>
                    <p class="text-sm text-gray-400">Qty: ' . (int) $item['quantity'] . '</p>
                </div>
            </div>
            <p class="font-semibold text-white">R' . number_format($item['line_total'], 2) . '</p>
        </div>';
    }

    $total_display = number_format((float) $total_amount, 2);
    $store_name_escaped = htmlspecialchars($store_name);
    $session_id_escaped = htmlspecialchars($session_id);
    $store_id_escaped = htmlspecialchars($store_id);

    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ' . $store_name_escaped . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .brand-purple { color: #7a1aab; }
        .bg-brand-purple { background-color: #7a1aab; }
        .border-brand-purple { border-color: #7a1aab; }
        .ring-brand-purple { --tw-ring-color: #7a1aab; }
        input:focus { outline: none; border-color: #7a1aab; box-shadow: 0 0 0 2px rgba(122, 26, 171, 0.3); }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <header class="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <div class="max-w-2xl mx-auto flex items-center justify-between">
            <h1 class="text-xl font-bold brand-purple">' . $store_name_escaped . '</h1>
            <span class="text-sm text-gray-400">Secure Checkout</span>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8">
        <!-- Order Summary -->
        <div class="bg-gray-800 rounded-xl p-6 mb-6 border border-gray-700">
            <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
            <div class="divide-y divide-gray-700">
                ' . $items_html . '
            </div>
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-600">
                <span class="text-lg font-bold">Total</span>
                <span class="text-xl font-bold brand-purple">R' . $total_display . '</span>
            </div>
        </div>

        <!-- Customer Information Form -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
            <form id="checkout-form" class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Full Name *</label>
                    <input type="text" id="customer_name" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500"
                        placeholder="Your full name">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Email Address *</label>
                    <input type="email" id="customer_email" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500"
                        placeholder="email@example.com">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Phone Number *</label>
                    <input type="tel" id="customer_phone" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500"
                        placeholder="+27 XX XXX XXXX">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Shipping Address *</label>
                    <textarea id="customer_address" required rows="3"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500 resize-none"
                        placeholder="Street address, city, postal code"></textarea>
                </div>

                <div id="error-msg" class="hidden bg-red-900/50 border border-red-700 text-red-300 rounded-lg px-4 py-3 text-sm"></div>

                <button type="submit" id="submit-btn"
                    class="w-full bg-brand-purple hover:opacity-90 text-white font-semibold py-4 rounded-lg transition-opacity text-lg">
                    Complete Order
                </button>
            </form>
        </div>
    </main>

    <script>
        const form = document.getElementById("checkout-form");
        const submitBtn = document.getElementById("submit-btn");
        const errorMsg = document.getElementById("error-msg");

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            errorMsg.classList.add("hidden");
            submitBtn.disabled = true;
            submitBtn.textContent = "Processing...";

            const payload = {
                session_id: "' . $session_id_escaped . '",
                customer_name: document.getElementById("customer_name").value.trim(),
                customer_email: document.getElementById("customer_email").value.trim(),
                customer_phone: document.getElementById("customer_phone").value.trim(),
                customer_address: document.getElementById("customer_address").value.trim()
            };

            try {
                const apiKeyParam = new URLSearchParams(window.location.search).get("api_key") || "";
                let url = window.location.origin + "/store-access/' . $store_id_escaped . '/?state=checkout_complete";
                if (apiKeyParam) url += "&api_key=" + encodeURIComponent(apiKeyParam);

                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-API-Key": apiKeyParam
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (data.success && data.data) {
                    if (data.data.redirect_url) {
                        window.location.href = data.data.redirect_url;
                    } else {
                        document.querySelector("main").innerHTML = \'<div class="text-center py-16"><div class="text-5xl mb-4">&#9989;</div><h2 class="text-2xl font-bold mb-2">Order Placed Successfully</h2><p class="text-gray-400">Order #\' + data.data.order_id + \'</p></div>\';
                    }
                } else {
                    throw new Error(data.error || "Something went wrong");
                }
            } catch (err) {
                errorMsg.textContent = err.message;
                errorMsg.classList.remove("hidden");
                submitBtn.disabled = false;
                submitBtn.textContent = "Complete Order";
            }
        });
    </script>
</body>
</html>';
}
?>
