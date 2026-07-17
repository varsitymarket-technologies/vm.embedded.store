<?php
session_start();

$testDb = sys_get_temp_dir() . '/build-site-customer-' . getmypid() . '.sqlite';
if (file_exists($testDb)) {
    @unlink($testDb);
}
putenv('CUSTOMER_STORE_DB_PATH=' . $testDb);

require_once __DIR__ . '/../build/site/customer-store.php';

$register = customer_store_register('demo@example.com', 'StrongPass123', 'Demo User', '0111234567');
if (!$register['ok']) {
    fwrite(STDERR, 'register failed: ' . ($register['error'] ?? 'unknown') . PHP_EOL);
    exit(1);
}

$login = customer_store_login('demo@example.com', 'StrongPass123');
if (!$login['ok']) {
    fwrite(STDERR, 'login failed: ' . ($login['error'] ?? 'unknown') . PHP_EOL);
    exit(1);
}

$me = customer_store_current_customer();
if (empty($me['email']) || $me['email'] !== 'demo@example.com') {
    fwrite(STDERR, 'current customer lookup failed: ' . var_export($me, true) . PHP_EOL);
    exit(1);
}

echo 'Customer store auth flow is working.' . PHP_EOL;
