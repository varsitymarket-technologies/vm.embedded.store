<?php
// api/data.php — Simple JSON REST endpoint
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Allow only GET/POST
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── GET /api/data.php ─────────────────────────────────────
if ($method === 'GET') {
    echo json_encode([
        'status'    => 'ok',
        'server'    => 'PHP ' . PHP_VERSION,
        'timestamp' => date('c'),
        'message'   => 'Hello from the PHP PWA API!',
        'items'     => ['Apple', 'Banana', 'Cherry'],
    ]);
    exit;
}

// ── POST /api/data.php ────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (!$body) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body']);
        exit;
    }

    // Echo back with server-side timestamp
    echo json_encode([
        'status'    => 'received',
        'echo'      => $body,
        'saved_at'  => date('c'),
    ]);
    exit;
}
