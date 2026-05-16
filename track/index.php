<?php
#   TITLE   : Analytics Tracking Endpoint
#   DESC    : Self-contained lightweight endpoint for recording page views via JS tag
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.1.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/05/16

// Self-contained — Apache serves this directly from track/ directory.
// No session, no full bootstrap. Just DB access for recording hits.

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Serve the tracking script
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($uri, 'vm.analytics.js') !== false) {
    header("Content-Type: application/javascript; charset=UTF-8");
    header("Cache-Control: public, max-age=3600");
    readfile(dirname(__FILE__) . '/vm.analytics.js');
    exit;
}

// --- Collect tracking data ---
$store_id = $_GET['sid'] ?? '';
if (empty($store_id)) {
    http_response_code(204);
    exit;
}

// Read POST body or GET params
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $page = $body['p'] ?? '/';
    $referrer = $body['r'] ?? '';
    $title = $body['t'] ?? '';
    $event = $body['e'] ?? 'pageview';
    $screen_w = $body['w'] ?? '';
} else {
    $page = $_GET['p'] ?? '/';
    $referrer = $_GET['r'] ?? '';
    $title = $_GET['t'] ?? '';
    $event = $_GET['e'] ?? 'pageview';
    $screen_w = $_GET['w'] ?? '';
}

// Sanitize
$page = substr($page, 0, 500);
$referrer = substr($referrer, 0, 500);
$title = substr($title, 0, 255);
$event = substr($event, 0, 50);

// Compute visitor hash (IP + UA + date = daily unique)
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$today = date('Y-m-d');
$visitor_hash = hash('sha256', $ip . '|' . $ua . '|' . $today);

// Extract referrer domain
$ref_domain = '';
if (!empty($referrer)) {
    $parsed = parse_url($referrer);
    $ref_domain = $parsed['host'] ?? '';
}

// Detect device type from screen width
$device = 'desktop';
if (!empty($screen_w)) {
    $w = (int) $screen_w;
    if ($w <= 768) $device = 'mobile';
    elseif ($w <= 1024) $device = 'tablet';
}

// --- Resolve store domain directly via PDO (no framework dependency) ---
$base_dir = dirname(dirname(__FILE__));
$engine_db_path = $base_dir . "/build/vm.engine.sql";

if (!file_exists($engine_db_path)) {
    http_response_code(204);
    exit;
}

try {
    $engine_pdo = new PDO("sqlite:" . $engine_db_path);
    $engine_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $engine_pdo->prepare("SELECT domain FROM sys_websites WHERE id = ? LIMIT 1");
    $stmt->execute([$store_id]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    http_response_code(204);
    exit;
}

if (empty($site)) {
    http_response_code(204);
    exit;
}

$domain = $site['domain'];

// --- Open or create the analytics database (separate from main store DB) ---
$analytics_dir = $base_dir . "/sites/" . $domain;
if (!is_dir($analytics_dir)) {
    @mkdir($analytics_dir, 0755, true);
}

$analytics_db_path = $analytics_dir . "/analytics.data";

try {
    $pdo = new PDO("sqlite:" . $analytics_db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA journal_mode=WAL");
} catch (\Throwable $e) {
    http_response_code(204);
    exit;
}

// Ensure tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS pageviews_daily (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL,
    page VARCHAR(500) NOT NULL,
    title VARCHAR(255) DEFAULT '',
    views INTEGER DEFAULT 0,
    unique_views INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS referrers_daily (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL,
    referrer_domain VARCHAR(255) NOT NULL,
    count INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS events_daily (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    count INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS devices_daily (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL,
    device_type VARCHAR(20) NOT NULL,
    count INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS visitors_today (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL,
    visitor_hash VARCHAR(64) NOT NULL
)");

// Create unique indexes
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_pv_date_page ON pageviews_daily(date, page)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_ref_date_domain ON referrers_daily(date, referrer_domain)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_evt_date_type ON events_daily(date, event_type)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_dev_date_type ON devices_daily(date, device_type)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_vis_date_hash ON visitors_today(date, visitor_hash)");

// --- Record the hit ---

// 1. Check if this is a unique visitor today
$is_unique = false;
try {
    $stmt = $pdo->prepare("SELECT id FROM visitors_today WHERE date = ? AND visitor_hash = ? LIMIT 1");
    $stmt->execute([$today, $visitor_hash]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO visitors_today (date, visitor_hash) VALUES (?, ?)");
        $ins->execute([$today, $visitor_hash]);
        $is_unique = true;
    }
} catch (\Throwable $e) {}

// 2. Increment pageview counter
$unique_inc = $is_unique ? 1 : 0;
try {
    $stmt = $pdo->prepare(
        "INSERT INTO pageviews_daily (date, page, title, views, unique_views) VALUES (?, ?, ?, 1, ?)
         ON CONFLICT(date, page) DO UPDATE SET views = views + 1, unique_views = unique_views + ?, title = ?"
    );
    $stmt->execute([$today, $page, $title, $unique_inc, $unique_inc, $title]);
} catch (\Throwable $e) {}

// 3. Increment referrer counter
if (!empty($ref_domain)) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO referrers_daily (date, referrer_domain, count) VALUES (?, ?, 1)
             ON CONFLICT(date, referrer_domain) DO UPDATE SET count = count + 1"
        );
        $stmt->execute([$today, $ref_domain]);
    } catch (\Throwable $e) {}
}

// 4. Increment event counter
try {
    $stmt = $pdo->prepare(
        "INSERT INTO events_daily (date, event_type, count) VALUES (?, ?, 1)
         ON CONFLICT(date, event_type) DO UPDATE SET count = count + 1"
    );
    $stmt->execute([$today, $event]);
} catch (\Throwable $e) {}

// 5. Increment device counter
try {
    $stmt = $pdo->prepare(
        "INSERT INTO devices_daily (date, device_type, count) VALUES (?, ?, 1)
         ON CONFLICT(date, device_type) DO UPDATE SET count = count + 1"
    );
    $stmt->execute([$today, $device]);
} catch (\Throwable $e) {}

// 6. Purge old visitor hashes (keep only last 2 days to stay lightweight)
try {
    $pdo->exec("DELETE FROM visitors_today WHERE date < date('now', '-2 days')");
} catch (\Throwable $e) {}

// --- Return response ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Content-Type: image/gif");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    echo base64_decode("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7");
} else {
    http_response_code(204);
}
