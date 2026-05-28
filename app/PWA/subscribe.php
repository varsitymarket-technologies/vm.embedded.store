<?php
// api/subscribe.php — Save push subscription from browser
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['endpoint'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing endpoint']);
    exit;
}

// ── In production: store $body in your database ───────────
// Example DB insert (PDO):
//
// $pdo = new PDO('mysql:host=localhost;dbname=pwa', 'user', 'pass');
// $stmt = $pdo->prepare(
//   'INSERT INTO push_subscriptions (endpoint, p256dh, auth, created_at)
//    VALUES (?, ?, ?, NOW())
//    ON DUPLICATE KEY UPDATE p256dh=VALUES(p256dh), auth=VALUES(auth)'
// );
// $stmt->execute([
//   $body['endpoint'],
//   $body['keys']['p256dh'] ?? '',
//   $body['keys']['auth']   ?? '',
// ]);

// For this demo, just log to a file
$logFile = __DIR__ . '/../storage/subscriptions.json';
$existing = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

// Upsert by endpoint
$existing[$body['endpoint']] = [
    'endpoint' => $body['endpoint'],
    'keys'     => $body['keys'] ?? [],
    'saved_at' => date('c'),
];

@file_put_contents($logFile, json_encode(array_values($existing), JSON_PRETTY_PRINT));

echo json_encode(['status' => 'subscribed']);
