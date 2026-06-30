<?php
// Standalone test runner for module/customer_auth.php
// Usage: php tests/customer_auth_test.php

require_once __DIR__ . '/../module/database.php';
require_once __DIR__ . '/../module/customer_auth.php';

$pass = 0;
$fail = 0;

function eq($expected, $actual, string $msg): void {
    global $pass, $fail;
    if ($expected === $actual) {
        echo "  PASS  $msg\n";
        $pass++;
    } else {
        echo "  FAIL  $msg\n";
        echo "        expected: " . var_export($expected, true) . "\n";
        echo "        actual:   " . var_export($actual, true) . "\n";
        $fail++;
    }
}

// --- Bootstrap tmp DB ---
$tmpDir = __DIR__ . '/tmp';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
$dbPath = $tmpDir . '/customer_auth_' . getmypid() . '.sqlite';
if (file_exists($dbPath)) unlink($dbPath);

$db = new database_manager($dbPath);

// Apply schema (the three statements that database.install.php adds)
$db->query("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    customer_email TEXT,
    total_amount REAL DEFAULT 0,
    status TEXT DEFAULT 'pending',
    customer_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
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
$db->query("CREATE TABLE IF NOT EXISTS customer_sessions (
    token TEXT PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    user_agent TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
)");

// Seed a guest order whose email matches the customer we'll register
$db->query("INSERT INTO orders (customer_name, customer_email, total_amount) VALUES (?, ?, ?)",
    ['Backfill Buyer', 'backfill@x.com', 42.00]);

echo "== customer_auth ==\n";

// --- Test 1: register happy path ---
$r = customer_register($db, 'alice@x.com', 'hunter22!', 'Alice', '0123');
eq(true, $r['ok'], 'register: ok=true on valid input');
eq(true, is_array($r['customer']) && $r['customer']['email'] === 'alice@x.com',
    'register: returns customer row with email');
eq('Alice', $r['customer']['name'], 'register: name preserved');
eq(false, (bool)$r['customer']['email_verified'], 'register: email_verified defaults to false');
eq(true, is_string($r['token']) && strlen($r['token']) === 64,
    'register: returns 64-char hex token');

// --- Test 2: register rejects invalid email ---
$bad = customer_register($db, 'not-an-email', 'longenough', 'X', null);
eq(false, $bad['ok'], 'register: invalid email rejected');
eq(true, stripos($bad['error'], 'email') !== false, 'register: error mentions email');

// --- Test 3: register rejects short password ---
$short = customer_register($db, 'short@x.com', 'abc', 'X', null);
eq(false, $short['ok'], 'register: short password rejected');
eq(true, stripos($short['error'], 'password') !== false, 'register: error mentions password');

// --- Test 4: register rejects duplicate email (case-insensitive) ---
$dup = customer_register($db, 'ALICE@x.com', 'anotherpass', 'A2', null);
eq(false, $dup['ok'], 'register: duplicate email rejected (case-insensitive)');
eq(true, stripos($dup['error'], 'already registered') !== false,
    'register: error mentions already registered');

// --- Test 5: backfill links the pre-seeded order ---
$bf = customer_register($db, 'backfill@x.com', 'goodpassword', 'BF', null);
eq(true, $bf['ok'], 'backfill register: ok=true');
$linked = $db->query("SELECT customer_id FROM orders WHERE customer_email = ?", ['backfill@x.com']);
eq(1, count($linked), 'backfill: one matching order row');
eq((int)$bf['customer']['id'], (int)$linked[0]['customer_id'],
    'backfill: pre-seeded order now has correct customer_id');

// --- Test 6: login happy path ---
$login = customer_login($db, 'alice@x.com', 'hunter22!', 'test-agent');
eq(true, $login['ok'], 'login: ok=true on correct creds');
eq('alice@x.com', $login['customer']['email'], 'login: returns customer');
eq(true, is_string($login['token']) && strlen($login['token']) === 64,
    'login: returns new 64-char token');

// --- Test 7: login wrong password generic error + counter increments ---
for ($i = 1; $i <= 4; $i++) {
    $wrong = customer_login($db, 'alice@x.com', 'wrong' . $i, 'test-agent');
    eq(false, $wrong['ok'], "login: wrong password attempt $i rejected");
}
$row = $db->query("SELECT failed_login_attempts FROM customers WHERE email = ?", ['alice@x.com']);
eq(4, (int)$row[0]['failed_login_attempts'], 'login: 4 failed attempts counted');

// --- Test 8: 5th wrong attempt locks the account ---
$lock = customer_login($db, 'alice@x.com', 'wrong5', 'test-agent');
eq(false, $lock['ok'], 'login: 5th wrong attempt still rejected');
$locked = $db->query("SELECT locked_until FROM customers WHERE email = ?", ['alice@x.com']);
eq(true, !empty($locked[0]['locked_until']), 'login: locked_until set after 5 failures');

// --- Test 9: locked account refuses correct password ---
$blocked = customer_login($db, 'alice@x.com', 'hunter22!', 'test-agent');
eq(false, $blocked['ok'], 'login: blocked even with correct password while locked');
eq(true, stripos($blocked['error'], 'locked') !== false, 'login: error mentions locked');

// --- Test 10: unlock manually for further tests, then login resets counters ---
$db->query("UPDATE customers SET locked_until = NULL, failed_login_attempts = 0 WHERE email = ?",
    ['alice@x.com']);
$ok = customer_login($db, 'alice@x.com', 'hunter22!', 'test-agent');
eq(true, $ok['ok'], 'login: succeeds after manual unlock');
$reset = $db->query("SELECT failed_login_attempts FROM customers WHERE email = ?", ['alice@x.com']);
eq(0, (int)$reset[0]['failed_login_attempts'], 'login: counters reset on success');

// --- Test 11: resolve_token returns customer for valid token, null for bad ---
$tok = $ok['token'];
$resolved = customer_resolve_token($db, $tok);
eq(true, is_array($resolved) && $resolved['email'] === 'alice@x.com',
    'resolve_token: valid token returns customer');
eq(null, customer_resolve_token($db, 'definitely-not-a-real-token'),
    'resolve_token: unknown token returns null');
eq(null, customer_resolve_token($db, ''), 'resolve_token: empty string returns null');

// --- Test 12: resolve_token slides expires_at forward ---
$before = $db->query("SELECT expires_at FROM customer_sessions WHERE token = ?", [$tok]);
// Manually back-date the session to confirm sliding works
$db->query("UPDATE customer_sessions SET expires_at = datetime('now', '+1 day') WHERE token = ?", [$tok]);
$slid = customer_resolve_token($db, $tok);
eq(true, is_array($slid), 'resolve_token: sliding still resolves the customer');
$after = $db->query("SELECT expires_at FROM customer_sessions WHERE token = ?", [$tok]);
// After resolve, expires_at should be ~30 days out, not +1 day
$secondsOut = strtotime($after[0]['expires_at']) - time();
eq(true, $secondsOut > 86400 * 25, 'resolve_token: expires_at slid >25 days into the future');

// --- Test 13: logout deletes the session ---
$lo = customer_logout($db, $tok);
eq(true, $lo['ok'], 'logout: ok=true');
eq(null, customer_resolve_token($db, $tok), 'logout: token no longer resolves');

// --- Test 14: logout with unknown token is idempotent ---
$lo2 = customer_logout($db, 'unknown-token');
eq(true, $lo2['ok'], 'logout: unknown token still returns ok=true');

// --- Cleanup ---
unlink($dbPath);

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
