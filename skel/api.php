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

    else {
        echo json_encode(["status" => "ok", "endpoints" => ["products", "product", "categories", "products_by_category", "search", "discounts", "site", "orders", "order"]]);
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
}
?>