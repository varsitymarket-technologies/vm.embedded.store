<?php 
#   TITLE   : Micro API    
#   DESC    : This will act as the websites micro api services.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.1.0
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/04/18

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
$db->createTable("page_views", [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
    "user_agent" => "TEXT",
    "ip_address" => "TEXT",
    "state" => "VARCHAR(255)",
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
        }
        echo json_encode($data);
    }

    elseif ($request == "site") {
        // Read site name from website_data helper if available
        $site_name = function_exists('website_data') ? website_data('name') : 'My Store';
        echo json_encode([
            "name" => $site_name,
            "currency" => defined('__CURRENCY_SIGN__') ? __CURRENCY_SIGN__ : '$',
            "domain" => $_SERVER['HTTP_HOST']
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
        $items = json_encode($input['items'] ?? []); // Store items as JSON
        
        if (empty($customer_name) || empty($total_amount)) {
            http_response_code(400);
            echo json_encode(["error" => "Incomplete order data"]);
            exit;
        }

        $sql = "INSERT INTO orders (customer_name, customer_email, total_amount, status, created_at) VALUES (?, ?, ?, 'pending', datetime('now'))";
        $result = $db->query($sql, [$customer_name, $customer_email, $total_amount]);
        
        if ($result) {
            echo json_encode(["success" => true, "message" => "Order placed successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to save order"]);
        }
    }
}
?>