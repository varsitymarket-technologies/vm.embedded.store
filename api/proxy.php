<?php
#   TITLE   : Store Access API Proxy
#   DESC    : Proxies requests to a remote store-access API (avoids CORS issues for external engines)
#   VERSION : 1.0.0

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key, X-Target-Base");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Target base URL is passed via X-Target-Base header or ?target_base= param
$target_base = $_SERVER['HTTP_X_TARGET_BASE'] ?? ($_GET['target_base'] ?? '');

if (empty($target_base)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing target_base parameter"]);
    exit;
}

// Validate target_base is a real URL
if (!filter_var($target_base, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid target_base URL"]);
    exit;
}

// Build the target URL — forward store_id segment and all query params
$store_id = ex(2);
$target_url = rtrim($target_base, '/') . '/' . $store_id . '/';

// Forward all query params except target_base
$params = $_GET;
unset($params['target_base']);
if (!empty($params)) {
    $target_url .= '?' . http_build_query($params);
}

// Forward headers
$headers = ["Content-Type: application/json"];
if (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $headers[] = "X-API-Key: " . $_SERVER['HTTP_X_API_KEY'];
}
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers[] = "Authorization: " . $_SERVER['HTTP_AUTHORIZATION'];
}

// Make the proxied request
$ch = curl_init($target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo json_encode(["error" => "Proxy request failed", "detail" => $error]);
    exit;
}

http_response_code($http_code);
echo $response;
