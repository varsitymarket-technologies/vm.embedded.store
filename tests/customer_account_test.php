<?php
// Standalone test runner for module/customer_account.php
// Usage: php tests/customer_account_test.php

require_once __DIR__ . '/../module/database.php';
require_once __DIR__ . '/../module/customer_auth.php';
require_once __DIR__ . '/../module/customer_account.php';

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
$dbPath = $tmpDir . '/customer_account_' . getmypid() . '.sqlite';
if (file_exists($dbPath)) unlink($dbPath);

$db = new database_manager($dbPath);

// Schema: full sub-project A tables + the new customer_addresses
$db->query("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    customer_email TEXT,
    customer_phone TEXT,
    customer_address TEXT,
    total_amount REAL DEFAULT 0,
    items TEXT,
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
$db->query("CREATE TABLE IF NOT EXISTS customer_addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    label TEXT,
    recipient_name TEXT NOT NULL,
    line1 TEXT NOT NULL,
    line2 TEXT,
    city TEXT NOT NULL,
    region TEXT,
    postal_code TEXT,
    country TEXT NOT NULL,
    phone TEXT,
    is_default INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
)");

// Register two real customers via sub-project A so we have real ids + tokens
$alice = customer_register($db, 'alice@x.com', 'alicepass1', 'Alice', '0111');
$bob   = customer_register($db, 'bob@x.com',   'bobpass123', 'Bob',   '0222');
$aliceId = (int)$alice['customer']['id'];
$bobId   = (int)$bob['customer']['id'];
$aliceToken = $alice['token'];

echo "== customer_account ==\n";

// === my_orders ===

// --- Test 1: my_orders empty for new customer ---
$mo = customer_my_orders($db, $aliceId);
eq(true, $mo['ok'], 'my_orders: ok=true');
eq(0, count($mo['orders']), 'my_orders: empty for new customer');

// --- Test 2: my_orders returns only orders linked to this customer ---
$db->query("INSERT INTO orders (customer_name, customer_email, total_amount, items, status, customer_id) VALUES (?, ?, ?, ?, ?, ?)",
    ['Alice', 'alice@x.com', 49.99, '[{"product_id":1,"name":"thing","price":49.99,"quantity":1}]', 'pending', $aliceId]);
$db->query("INSERT INTO orders (customer_name, customer_email, total_amount, items, status, customer_id) VALUES (?, ?, ?, ?, ?, ?)",
    ['Bob', 'bob@x.com', 99.99, '[]', 'pending', $bobId]);
$db->query("INSERT INTO orders (customer_name, customer_email, total_amount, items, status, customer_id) VALUES (?, ?, ?, ?, ?, ?)",
    ['Guest', 'guest@x.com', 10.00, '[]', 'pending', null]);

$mo2 = customer_my_orders($db, $aliceId);
eq(1, count($mo2['orders']), 'my_orders: returns only the linked alice order');
eq('Alice', $mo2['orders'][0]['customer_name'], 'my_orders: returns alice row');
eq(49.99, (float)$mo2['orders'][0]['total_amount'], 'my_orders: total_amount cast to float');
eq(true, is_array($mo2['orders'][0]['items']), 'my_orders: items decoded to array');
eq(1, $mo2['orders'][0]['items'][0]['product_id'], 'my_orders: items array has decoded product_id');

// === update_profile ===

// --- Test 3: update_profile rejects when both fields null/empty ---
$up0 = customer_update_profile($db, $aliceId, null, null);
eq(false, $up0['ok'], 'update_profile: rejected when both null');
eq(true, stripos($up0['error'], 'at least one') !== false, 'update_profile: error mentions "at least one"');

$up0b = customer_update_profile($db, $aliceId, '', '');
eq(false, $up0b['ok'], 'update_profile: rejected when both empty strings');

// --- Test 4: update_profile updates name only ---
$up1 = customer_update_profile($db, $aliceId, 'Alice Updated', null);
eq(true, $up1['ok'], 'update_profile: name-only update succeeds');
eq('Alice Updated', $up1['customer']['name'], 'update_profile: name updated');
eq('0111', $up1['customer']['phone'], 'update_profile: phone preserved');

// --- Test 5: update_profile updates phone only ---
$up2 = customer_update_profile($db, $aliceId, null, '0999');
eq(true, $up2['ok'], 'update_profile: phone-only update succeeds');
eq('Alice Updated', $up2['customer']['name'], 'update_profile: name preserved');
eq('0999', $up2['customer']['phone'], 'update_profile: phone updated');

// --- Test 6: update_profile updates both ---
$up3 = customer_update_profile($db, $aliceId, 'Alice Final', '0123');
eq(true, $up3['ok'], 'update_profile: both update succeeds');
eq('Alice Final', $up3['customer']['name'], 'update_profile: name updated');
eq('0123', $up3['customer']['phone'], 'update_profile: phone updated');

// === change_password ===

// --- Test 7: change_password wrong current password ---
$cp_wrong = customer_change_password($db, $aliceId, 'WRONG', 'newpassword1', null);
eq(false, $cp_wrong['ok'], 'change_password: wrong current rejected');
eq(true, stripos($cp_wrong['error'], 'current password') !== false, 'change_password: error mentions current password');

// --- Test 8: change_password too-short new password ---
$cp_short = customer_change_password($db, $aliceId, 'alicepass1', 'abc', null);
eq(false, $cp_short['ok'], 'change_password: short new password rejected');
eq(true, stripos($cp_short['error'], 'password') !== false, 'change_password: error mentions password');

// --- Test 9: change_password happy path ---
// Sanity: alice's current session token should still resolve before the change
$preResolve = customer_resolve_token($db, $aliceToken);
eq(true, is_array($preResolve) && $preResolve['email'] === 'alice@x.com',
    'change_password: pre-change token still resolves');

$cp = customer_change_password($db, $aliceId, 'alicepass1', 'newpassword1', 'test-agent');
eq(true, $cp['ok'], 'change_password: ok=true on success');
eq(true, is_string($cp['token']) && strlen($cp['token']) === 64,
    'change_password: returns new 64-char token');
eq(true, $cp['token'] !== $aliceToken, 'change_password: new token is different from old');

// Old token must be invalid now
$postResolve = customer_resolve_token($db, $aliceToken);
eq(null, $postResolve, 'change_password: old token no longer resolves');

// New token must work
$newResolve = customer_resolve_token($db, $cp['token']);
eq(true, is_array($newResolve) && $newResolve['email'] === 'alice@x.com',
    'change_password: new token resolves');

// Subsequent login with new password works
$login = customer_login($db, 'alice@x.com', 'newpassword1', null);
eq(true, $login['ok'], 'change_password: login with new password succeeds');

// === addresses CRUD ===

// --- Test 10: addresses list empty initially ---
$la0 = customer_addresses_list($db, $aliceId);
eq(true, $la0['ok'], 'addresses_list: ok=true');
eq(0, count($la0['addresses']), 'addresses_list: empty for new customer');

// --- Test 11: address_create first address auto-defaults ---
$ac1 = customer_address_create($db, $aliceId, [
    'recipient_name' => 'Alice',
    'line1' => '12 Example St',
    'city' => 'Cape Town',
    'country' => 'South Africa',
]);
eq(true, $ac1['ok'], 'address_create: first address ok=true');
eq(true, (bool)$ac1['address']['is_default'], 'address_create: first address is_default=true automatically');
$firstId = (int)$ac1['address']['id'];

// --- Test 12: address_create missing required field ---
$bad = customer_address_create($db, $aliceId, [
    'line1' => 'only line1',
    'city' => 'X',
    'country' => 'Y',
]);
eq(false, $bad['ok'], 'address_create: missing recipient_name rejected');
eq(true, stripos($bad['error'], 'recipient_name') !== false, 'address_create: error mentions recipient_name');

// --- Test 13: second address does NOT flip default unless asked ---
$ac2 = customer_address_create($db, $aliceId, [
    'recipient_name' => 'Alice (Work)',
    'line1' => '500 Work St',
    'city' => 'Cape Town',
    'country' => 'South Africa',
    'label' => 'Work',
]);
eq(true, $ac2['ok'], 'address_create: second address ok=true');
eq(false, (bool)$ac2['address']['is_default'], 'address_create: second address NOT default');
$secondId = (int)$ac2['address']['id'];

// --- Test 14: third address with is_default=true flips the default ---
$ac3 = customer_address_create($db, $aliceId, [
    'recipient_name' => 'Alice (Other)',
    'line1' => '99 Other St',
    'city' => 'Cape Town',
    'country' => 'South Africa',
    'is_default' => true,
]);
eq(true, $ac3['ok'], 'address_create: third address ok=true');
eq(true, (bool)$ac3['address']['is_default'], 'address_create: third address is default');
$thirdId = (int)$ac3['address']['id'];

// Old default should no longer be default
$ckFirst = $db->query("SELECT is_default FROM customer_addresses WHERE id = ?", [$firstId]);
eq(0, (int)$ckFirst[0]['is_default'], 'address_create: previous default flipped to 0');

// --- Test 15: addresses_list orders by is_default DESC, id ASC ---
$la = customer_addresses_list($db, $aliceId);
eq(3, count($la['addresses']), 'addresses_list: returns 3 addresses');
eq($thirdId, (int)$la['addresses'][0]['id'], 'addresses_list: default address first');
eq($firstId, (int)$la['addresses'][1]['id'], 'addresses_list: non-default ordered by id asc');
eq($secondId, (int)$la['addresses'][2]['id'], 'addresses_list: second non-default after first');

// --- Test 16: address_update with id not owned by alice returns 404-style ---
// First, create an address for bob
$bobAc = customer_address_create($db, $bobId, [
    'recipient_name' => 'Bob',
    'line1' => 'Bob St',
    'city' => 'BobCity',
    'country' => 'BobLand',
]);
eq(true, $bobAc['ok'], 'address_create: bob address ok');
$bobAddrId = (int)$bobAc['address']['id'];

$cross = customer_address_update($db, $aliceId, $bobAddrId, ['phone' => '9999']);
eq(false, $cross['ok'], 'address_update: cross-customer access rejected');
eq(true, stripos($cross['error'], 'not found') !== false, 'address_update: 404-style error');

// --- Test 17: address_update sets is_default flips correctly ---
$au = customer_address_update($db, $aliceId, $firstId, ['is_default' => true]);
eq(true, $au['ok'], 'address_update: ok=true');
eq(true, (bool)$au['address']['is_default'], 'address_update: target is now default');
$ckThird = $db->query("SELECT is_default FROM customer_addresses WHERE id = ?", [$thirdId]);
eq(0, (int)$ckThird[0]['is_default'], 'address_update: previous default flipped to 0');

// --- Test 18: address_set_default idempotent ---
$sd1 = customer_address_set_default($db, $aliceId, $firstId);
eq(true, $sd1['ok'], 'address_set_default: ok=true (already default)');
eq(true, (bool)$sd1['address']['is_default'], 'address_set_default: still default');

// --- Test 19: address_delete of current default promotes oldest remaining ---
$del = customer_address_delete($db, $aliceId, $firstId);
eq(true, $del['ok'], 'address_delete: ok=true');
// After deleting $firstId (was default), the oldest remaining should be promoted.
// Alice has $secondId and $thirdId remaining. $secondId has the lower id (created earlier).
$la2 = customer_addresses_list($db, $aliceId);
eq(2, count($la2['addresses']), 'address_delete: 2 addresses remain');
eq($secondId, (int)$la2['addresses'][0]['id'], 'address_delete: oldest (secondId) promoted to default');
eq(true, (bool)$la2['addresses'][0]['is_default'], 'address_delete: promoted address has is_default=true');

// --- Test 20: address_delete of last address leaves zero rows ---
$del2 = customer_address_delete($db, $aliceId, $secondId);
$del3 = customer_address_delete($db, $aliceId, $thirdId);
eq(true, $del2['ok'] && $del3['ok'], 'address_delete: last two deletions ok');
$la3 = customer_addresses_list($db, $aliceId);
eq(0, count($la3['addresses']), 'address_delete: zero rows after deleting all');

// --- Test 21: address_delete of non-owned id returns 404 ---
$delCross = customer_address_delete($db, $aliceId, $bobAddrId);
eq(false, $delCross['ok'], 'address_delete: cross-customer rejected');
eq(true, stripos($delCross['error'], 'not found') !== false, 'address_delete: 404-style error');

// Bob's address must still exist after the failed cross-customer delete
$bobCheck = $db->query("SELECT id FROM customer_addresses WHERE id = ?", [$bobAddrId]);
eq(1, count($bobCheck), 'address_delete: bob address survived cross-customer attempt');

// --- Cleanup ---
unlink($dbPath);

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
