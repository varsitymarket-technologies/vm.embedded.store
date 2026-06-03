# Customer-Side API Expansion Implementation Plan (Sub-project B)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add eight auth-gated customer-side API endpoints (`customer_my_orders`, `customer_update_profile`, `customer_change_password`, plus address book CRUD + set_default) backed by a pure-PHP `customer_account` module and a new `customer_addresses` table.

**Architecture:** Mirror sub-project A. A pure-PHP module under `module/customer_account.php` holds the business logic and is unit-tested in isolation against a tmp SQLite. Schema change lives in `services/database.install.php` (idempotent CREATE). The API layer in `api/index.php` is a thin JSON wrapper that resolves the bearer token first, then delegates to the module. Customer id is always taken from the resolved token — never from request bodies.

**Tech Stack:** PHP 7.4+, PDO + SQLite via existing `database_manager`, standalone PHP test runner using the `eq()` helper pattern from `tests/customer_auth_test.php`. No new dependencies.

**Spec:** [docs/superpowers/specs/2026-06-03-customer-account-api-design.md](../specs/2026-06-03-customer-account-api-design.md)

---

## File Map

**Create:**

- `module/customer_account.php` — Pure-PHP module exposing 8 public functions (`customer_my_orders`, `customer_update_profile`, `customer_change_password`, `customer_addresses_list`, `customer_address_create`, `customer_address_update`, `customer_address_delete`, `customer_address_set_default`) plus internal helpers (`customer_address_normalize`, `customer_address_clear_default`, `customer_address_promote_oldest`, `customer_address_public_view`). Requires `customer_auth.php` for `customer_create_session`.

- `tests/customer_account_test.php` — Standalone PHP runner. Boots a tmp SQLite under `tests/tmp/`, applies the schema, registers a customer via `customer_register` to get a real id, then exercises every public function with ~40 assertions.

**Modify:**

- `services/database.install.php` — Append one more `CREATE TABLE IF NOT EXISTS` for `customer_addresses` plus its index, after the sub-project A schema fragments at the bottom of the file.

- `api/index.php` — Add 8 new `state=customer_*` branches inside the existing GET (1 branch) and POST (7 branches) routing blocks. All eight resolve `X-Customer-Token` via the existing `extract_customer_token()` + `customer_resolve_token()` and delegate to the module.

**No changes** to `vm-admin/`, `themes/`, `pages/`, `skel/`, or `module/customer_auth.php`. Sub-project A is treated as stable.

---

## Notes on testing & running

- Reuses the standalone-runner pattern from `tests/customer_auth_test.php`.
- Local: `php tests/customer_account_test.php`. Docker: `docker compose exec vm-emb-sites php /var/www/html/public/tests/customer_account_test.php`. Container name `vm-emb-sites`, project mounted at `/var/www/html/public/`.
- The test file registers a real customer via `customer_register` from sub-project A's module to get a real `customer_id` + working token, so the test exercises the actual integration surface.

---

## Task 1: Schema migration for `customer_addresses`

**Files:**
- Modify: `services/database.install.php` (append at the bottom, after the sub-project A schema additions)

### Step 1.1: Append the schema statements

- [ ] **Step 1.1: Edit `services/database.install.php`**

Find the existing block at the bottom of the file added by sub-project A — it ends with:

```php
$db->query("CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id)");
```

Immediately after this line (still BEFORE the commented-out demo `#$sql = "INSERT INTO products ..."` lines), append:

```php

// 9. Customer Addresses Table (sub-project B)
$sql_customer_addresses = "CREATE TABLE IF NOT EXISTS customer_addresses (
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
)";
$db->query($sql_customer_addresses);
$db->query("CREATE INDEX IF NOT EXISTS idx_addresses_customer ON customer_addresses(customer_id)");
```

### Step 1.2: Syntax check

- [ ] **Step 1.2: Syntax check**

```bash
php -l services/database.install.php
```

Or Docker: `docker compose exec vm-emb-sites php -l /var/www/html/public/services/database.install.php`. Expected: `No syntax errors detected`.

### Step 1.3: Idempotency check against an existing per-site DB

- [ ] **Step 1.3: Verify idempotency**

Run the install code path twice on a known site DB (`claude.test` or `something.less`):

```bash
docker compose exec vm-emb-sites php -r '
define("__ANCHOR_SITE__", "claude.test");
require_once "/var/www/html/public/services/database.install.php";
echo "first run ok\n";
'
docker compose exec vm-emb-sites php -r '
define("__ANCHOR_SITE__", "claude.test");
require_once "/var/www/html/public/services/database.install.php";
echo "second run ok\n";
'
```

Both should print `first run ok` / `second run ok` with no fatal errors. Then confirm the table exists:

```bash
docker compose exec vm-emb-sites php -r '
require_once "/var/www/html/public/module/database.php";
$db = new database_manager("/var/www/html/public/sites/claude.test/storage.data");
echo "-- customer_addresses cols --\n";
foreach ($db->query("PRAGMA table_info(customer_addresses)") as $c) echo " " . $c["name"] . "\n";
'
```

Expected output lists: `id, customer_id, label, recipient_name, line1, line2, city, region, postal_code, country, phone, is_default, created_at`.

### Step 1.4: Commit

- [ ] **Step 1.4: Commit**

```bash
git add services/database.install.php
git commit -m "feat: add customer_addresses table (sub-project B)

New per-site table for the customer address book that sub-project
B's API endpoints (customer_addresses_*) will read and write. FK
to customers with ON DELETE CASCADE — addresses are removed when
the customer is. Idempotent CREATE TABLE IF NOT EXISTS pattern."
```

---

## Task 2: Build the `customer_account.php` module (TDD)

**Files:**
- Create: `tests/customer_account_test.php`
- Create: `module/customer_account.php`

### Step 2.1: Write the failing test runner

- [ ] **Step 2.1: Create `tests/customer_account_test.php`**

This is the entire test file. Copy verbatim.

```php
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
```

### Step 2.2: Run the test — expect failure

- [ ] **Step 2.2: Run and confirm it fails**

```bash
php tests/customer_account_test.php
```

Expected: PHP error like `Failed opening required '.../module/customer_account.php'` — confirms the test reaches the require.

### Step 2.3: Implement `module/customer_account.php`

- [ ] **Step 2.3: Create `module/customer_account.php`**

Entire file content (copy verbatim):

```php
<?php
#   TITLE   : Customer Account Module
#   DESC    : Post-login customer account operations: order history,
#             profile updates, password change, and address book CRUD.
#             All public functions take database_manager $db and the
#             resolved int $customerId as the first two arguments so
#             they are testable in isolation. Customer id is never
#             accepted from request bodies — the API layer resolves it
#             from the X-Customer-Token bearer first.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES

require_once __DIR__ . '/customer_auth.php';

if (!defined('CUSTOMER_ACCOUNT_MAX_ORDERS')) {
    define('CUSTOMER_ACCOUNT_MAX_ORDERS', 100);
}

// ===== Orders =====

/**
 * Return the most recent orders for this customer (capped at
 * CUSTOMER_ACCOUNT_MAX_ORDERS), with items decoded.
 *
 * @return array{ok:bool, orders:array<int,array>}
 */
function customer_my_orders(database_manager $db, int $customerId): array
{
    // SELECT * because customer_phone / customer_address are added to
    // the orders table dynamically at checkout time (see api/index.php
    // checkout_complete handler). A site with no orders yet may not
    // have those columns, and naming them explicitly would make the
    // SELECT fail with a swallowed PDOException. Read fields with
    // null-coalesce so missing columns return null cleanly.
    $rows = $db->query(
        "SELECT * FROM orders WHERE customer_id = ?
          ORDER BY created_at DESC, id DESC
          LIMIT " . (int)CUSTOMER_ACCOUNT_MAX_ORDERS,
        [$customerId]
    );

    $orders = [];
    foreach ($rows as $r) {
        $orders[] = [
            'id' => (int)$r['id'],
            'customer_name' => $r['customer_name'] ?? null,
            'customer_email' => $r['customer_email'] ?? null,
            'customer_phone' => $r['customer_phone'] ?? null,
            'customer_address' => $r['customer_address'] ?? null,
            'total_amount' => (float)$r['total_amount'],
            'items' => json_decode($r['items'] ?? '[]', true) ?: [],
            'status' => $r['status'] ?? 'pending',
            'created_at' => $r['created_at'] ?? null,
        ];
    }

    return ['ok' => true, 'orders' => $orders];
}

// ===== Profile =====

/**
 * Update name and/or phone. Email and password are intentionally NOT
 * touched by this endpoint.
 *
 * @return array{ok:bool, error?:string, customer?:array}
 */
function customer_update_profile(database_manager $db, int $customerId, ?string $name, ?string $phone): array
{
    $nameSet  = ($name !== null && $name !== '');
    $phoneSet = ($phone !== null && $phone !== '');
    if (!$nameSet && !$phoneSet) {
        return ['ok' => false, 'error' => 'At least one of name or phone must be provided'];
    }

    $sets = [];
    $params = [];
    if ($nameSet)  { $sets[] = 'name = ?';  $params[] = $name; }
    if ($phoneSet) { $sets[] = 'phone = ?'; $params[] = $phone; }
    $params[] = $customerId;

    $db->query("UPDATE customers SET " . implode(', ', $sets) . " WHERE id = ?", $params);

    $fresh = $db->query("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);
    if (empty($fresh)) {
        return ['ok' => false, 'error' => 'Customer not found'];
    }
    return ['ok' => true, 'customer' => customer_public_view($fresh[0])];
}

/**
 * Change the customer's password. Requires the current password. On
 * success, deletes ALL existing sessions and issues a fresh one for the
 * current request.
 *
 * @return array{ok:bool, error?:string, customer?:array, token?:string, expires_at?:string}
 */
function customer_change_password(database_manager $db, int $customerId, string $currentPassword, string $newPassword, ?string $userAgent): array
{
    $row = $db->query("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);
    if (empty($row)) {
        return ['ok' => false, 'error' => 'Customer not found'];
    }
    $customer = $row[0];

    if (!password_verify($currentPassword, $customer['password_hash'])) {
        return ['ok' => false, 'error' => 'Current password is incorrect'];
    }
    if (strlen($newPassword) < CUSTOMER_AUTH_MIN_PASSWORD_LEN) {
        return ['ok' => false, 'error' => 'Password must be at least ' . CUSTOMER_AUTH_MIN_PASSWORD_LEN . ' characters'];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    // Kill every existing session for this customer, THEN write the new
    // hash, THEN issue a fresh session for this request. Any in-flight
    // stolen token is dead before the next request.
    $db->query("DELETE FROM customer_sessions WHERE customer_id = ?", [$customerId]);
    $db->query("UPDATE customers SET password_hash = ? WHERE id = ?", [$newHash, $customerId]);
    $session = customer_create_session($db, $customerId, $userAgent);

    $fresh = $db->query("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);
    return [
        'ok' => true,
        'customer' => customer_public_view($fresh[0]),
        'token' => $session['token'],
        'expires_at' => $session['expires_at'],
    ];
}

// ===== Addresses =====

/**
 * List the customer's addresses, default first, then by id ascending.
 *
 * @return array{ok:bool, addresses:array<int,array>}
 */
function customer_addresses_list(database_manager $db, int $customerId): array
{
    $rows = $db->query(
        "SELECT * FROM customer_addresses WHERE customer_id = ?
          ORDER BY is_default DESC, id ASC",
        [$customerId]
    );
    $out = [];
    foreach ($rows as $r) $out[] = customer_address_public_view($r);
    return ['ok' => true, 'addresses' => $out];
}

/**
 * Create a new address. First address for the customer becomes the
 * default automatically. is_default=true on subsequent addresses flips
 * the previous default to 0.
 *
 * @return array{ok:bool, error?:string, address?:array}
 */
function customer_address_create(database_manager $db, int $customerId, array $fields): array
{
    $norm = customer_address_normalize($fields, true);
    if (!$norm['ok']) {
        return ['ok' => false, 'error' => $norm['error']];
    }
    $data = $norm['data'];

    // Decide if this address should be default
    $existingCount = (int)($db->query("SELECT COUNT(*) AS c FROM customer_addresses WHERE customer_id = ?", [$customerId])[0]['c'] ?? 0);
    $shouldBeDefault = ($existingCount === 0) || !empty($fields['is_default']);

    $db->query("BEGIN TRANSACTION");
    try {
        $db->query(
            "INSERT INTO customer_addresses
                (customer_id, label, recipient_name, line1, line2, city, region, postal_code, country, phone, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $customerId,
                $data['label'],
                $data['recipient_name'],
                $data['line1'],
                $data['line2'],
                $data['city'],
                $data['region'],
                $data['postal_code'],
                $data['country'],
                $data['phone'],
                $shouldBeDefault ? 1 : 0,
            ]
        );

        // Get new id by re-SELECT (database_manager has no lastInsertId).
        $newRow = $db->query(
            "SELECT id FROM customer_addresses WHERE customer_id = ? ORDER BY id DESC LIMIT 1",
            [$customerId]
        );
        $newId = !empty($newRow) ? (int)$newRow[0]['id'] : 0;

        if ($shouldBeDefault && $newId > 0) {
            customer_address_clear_default($db, $customerId, $newId);
        }

        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to create address'];
    }

    $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$newId]);
    if (empty($full)) {
        return ['ok' => false, 'error' => 'Failed to create address'];
    }
    return ['ok' => true, 'address' => customer_address_public_view($full[0])];
}

/**
 * Update fields on an existing address owned by the customer. Setting
 * is_default=true flips the previous default to 0. 404 returned for
 * address ids not owned by this customer.
 *
 * @return array{ok:bool, error?:string, address?:array}
 */
function customer_address_update(database_manager $db, int $customerId, int $addressId, array $fields): array
{
    // Ownership check (also disambiguates "doesn't exist" vs "not yours")
    $existing = $db->query(
        "SELECT * FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1",
        [$addressId, $customerId]
    );
    if (empty($existing)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }

    $norm = customer_address_normalize($fields, false);
    if (!$norm['ok']) {
        return ['ok' => false, 'error' => $norm['error']];
    }
    $data = $norm['data'];

    $sets = [];
    $params = [];
    // Each field in $data is included only if the input array specified it.
    // The normalize function trims provided fields; we only UPDATE columns
    // the caller actually mentioned.
    $columns = ['label', 'recipient_name', 'line1', 'line2', 'city', 'region', 'postal_code', 'country', 'phone'];
    foreach ($columns as $col) {
        if (array_key_exists($col, $fields)) {
            $sets[] = "$col = ?";
            $params[] = $data[$col];
        }
    }

    $flipDefault = !empty($fields['is_default']) && (int)$existing[0]['is_default'] === 0;
    if ($flipDefault) {
        $sets[] = "is_default = 1";
    }

    $db->query("BEGIN TRANSACTION");
    try {
        if (!empty($sets)) {
            $params[] = $addressId;
            $db->query("UPDATE customer_addresses SET " . implode(', ', $sets) . " WHERE id = ?", $params);
        }
        if ($flipDefault) {
            customer_address_clear_default($db, $customerId, $addressId);
        }
        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to update address'];
    }

    $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$addressId]);
    if (empty($full)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }
    return ['ok' => true, 'address' => customer_address_public_view($full[0])];
}

/**
 * Delete an address. If it was the default and other rows exist, the
 * oldest remaining row is auto-promoted to default. 404 returned for
 * address ids not owned by this customer.
 *
 * @return array{ok:bool, error?:string}
 */
function customer_address_delete(database_manager $db, int $customerId, int $addressId): array
{
    $existing = $db->query(
        "SELECT id, is_default FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1",
        [$addressId, $customerId]
    );
    if (empty($existing)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }
    $wasDefault = (int)$existing[0]['is_default'] === 1;

    $db->query("BEGIN TRANSACTION");
    try {
        $db->query("DELETE FROM customer_addresses WHERE id = ?", [$addressId]);
        if ($wasDefault) {
            customer_address_promote_oldest($db, $customerId);
        }
        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to delete address'];
    }

    return ['ok' => true];
}

/**
 * Mark the given address as the customer's default, clearing any
 * previous default. Idempotent if already default.
 *
 * @return array{ok:bool, error?:string, address?:array}
 */
function customer_address_set_default(database_manager $db, int $customerId, int $addressId): array
{
    $existing = $db->query(
        "SELECT id, is_default FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1",
        [$addressId, $customerId]
    );
    if (empty($existing)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }

    if ((int)$existing[0]['is_default'] === 1) {
        // Already default — idempotent no-op
        $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$addressId]);
        return ['ok' => true, 'address' => customer_address_public_view($full[0])];
    }

    $db->query("BEGIN TRANSACTION");
    try {
        $db->query("UPDATE customer_addresses SET is_default = 1 WHERE id = ?", [$addressId]);
        customer_address_clear_default($db, $customerId, $addressId);
        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to set default'];
    }

    $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$addressId]);
    return ['ok' => true, 'address' => customer_address_public_view($full[0])];
}

// ===== Internal helpers =====

/**
 * Validate and trim address input. On create, required fields must be
 * non-empty. On update, only the provided fields are validated.
 *
 * @return array{ok:bool, error?:string, data?:array}
 */
function customer_address_normalize(array $fields, bool $isCreate): array
{
    $required = ['recipient_name', 'line1', 'city', 'country'];
    $optional = ['label', 'line2', 'region', 'postal_code', 'phone'];

    if ($isCreate) {
        foreach ($required as $r) {
            $val = isset($fields[$r]) ? trim((string)$fields[$r]) : '';
            if ($val === '') {
                return ['ok' => false, 'error' => "$r is required"];
            }
        }
    } else {
        // On update, if a required field is PRESENT, it must not be empty.
        foreach ($required as $r) {
            if (array_key_exists($r, $fields)) {
                $val = trim((string)$fields[$r]);
                if ($val === '') {
                    return ['ok' => false, 'error' => "$r must not be empty"];
                }
            }
        }
    }

    $data = [];
    foreach (array_merge($required, $optional) as $col) {
        if (array_key_exists($col, $fields)) {
            $val = $fields[$col];
            $data[$col] = ($val === null) ? null : trim((string)$val);
            if ($data[$col] === '') $data[$col] = null;
        } else {
            $data[$col] = null;
        }
    }
    return ['ok' => true, 'data' => $data];
}

/**
 * Set is_default = 0 on all addresses for this customer EXCEPT the one
 * passed in. Used by create/update/set_default to flip the default
 * cleanly inside a transaction.
 */
function customer_address_clear_default(database_manager $db, int $customerId, int $exceptAddressId): void
{
    $db->query(
        "UPDATE customer_addresses SET is_default = 0
          WHERE customer_id = ? AND id != ?",
        [$customerId, $exceptAddressId]
    );
}

/**
 * Pick the lowest-id remaining address for the customer and set it as
 * default. No-op if no rows remain. Called after deleting the current
 * default.
 */
function customer_address_promote_oldest(database_manager $db, int $customerId): void
{
    $row = $db->query(
        "SELECT id FROM customer_addresses WHERE customer_id = ? ORDER BY id ASC LIMIT 1",
        [$customerId]
    );
    if (!empty($row)) {
        $db->query("UPDATE customer_addresses SET is_default = 1 WHERE id = ?", [(int)$row[0]['id']]);
    }
}

/**
 * Strip customer_id and cast is_default to bool before returning to
 * callers. Mirrors customer_public_view for customer rows.
 */
function customer_address_public_view(array $row): array
{
    return [
        'id' => (int)($row['id'] ?? 0),
        'label' => $row['label'] ?? null,
        'recipient_name' => $row['recipient_name'] ?? '',
        'line1' => $row['line1'] ?? '',
        'line2' => $row['line2'] ?? null,
        'city' => $row['city'] ?? '',
        'region' => $row['region'] ?? null,
        'postal_code' => $row['postal_code'] ?? null,
        'country' => $row['country'] ?? '',
        'phone' => $row['phone'] ?? null,
        'is_default' => (bool)($row['is_default'] ?? 0),
        'created_at' => $row['created_at'] ?? null,
    ];
}
```

### Step 2.4: Run the test — expect pass

- [ ] **Step 2.4: Run the test**

```bash
php tests/customer_account_test.php
```

Or Docker: `docker compose exec vm-emb-sites php /var/www/html/public/tests/customer_account_test.php`.

Expected: every assertion prints PASS, summary `N passed, 0 failed`, exit 0.

If any assertion fails, fix the MODULE (not the test) and re-run. Do not proceed until all green.

### Step 2.5: Commit

- [ ] **Step 2.5: Commit**

```bash
git add module/customer_account.php tests/customer_account_test.php
git commit -m "feat: add customer_account module with profile, password, address book

Pure-PHP module with all post-login customer operations: my_orders,
update_profile, change_password (kills all sessions then issues a
fresh one), and addresses CRUD with module-enforced default-address
invariant. Customer id is always taken from the caller — never from
input fields.

Tested via tests/customer_account_test.php (~40 assertions covering
empty/populated my_orders, profile update validation, password change
with old-token revocation, address create/update/delete with default
flipping and cross-customer 404)."
```

---

## Task 3: Wire up the API endpoints in `api/index.php`

**Files:**
- Modify: `api/index.php`

### Step 3.1: Require the account module

- [ ] **Step 3.1: Edit `api/index.php`**

Find the existing line that includes `customer_auth.php` (near line 177):

```php
@include_once dirname(__FILE__) . "/../module/customer_auth.php";
```

Immediately after it, add:

```php
@include_once dirname(__FILE__) . "/../module/customer_account.php";
```

### Step 3.2: Add the GET `customer_my_orders` branch

- [ ] **Step 3.2: Add GET branch inside the GET block**

Find the existing GET `customer_me` branch (added in sub-project A):

```php
    // --- Customer auth: GET customer_me ---
    if ($request == "customer_me") {
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
```

Immediately AFTER this `customer_me` block (still inside the GET block), append:

```php

    // --- Customer account: GET customer_my_orders ---
    if ($request == "customer_my_orders") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        echo json_encode(customer_my_orders($db, (int)$customer['id']));
        exit;
    }

    // --- Customer account: GET customer_addresses ---
    if ($request == "customer_addresses") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        echo json_encode(customer_addresses_list($db, (int)$customer['id']));
        exit;
    }
```

### Step 3.3: Add the POST branches

- [ ] **Step 3.3: Add POST branches inside the POST block**

Find the existing POST `customer_logout` branch (added in sub-project A):

```php
    // --- Customer auth: POST customer_logout ---
    if ($request == "customer_logout") {
        $token = extract_customer_token();
        echo json_encode(customer_logout($db, $token));
        exit;
    }
```

Immediately AFTER this `customer_logout` block (still inside the POST block), append the six new POST branches:

```php

    // --- Customer account: POST customer_update_profile ---
    if ($request == "customer_update_profile") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $name = $input['name'] ?? null;
        $phone = $input['phone'] ?? null;
        $result = customer_update_profile($db, (int)$customer['id'], $name, $phone);
        if (!$result['ok']) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_change_password ---
    if ($request == "customer_change_password") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $currentPw = $input['current_password'] ?? '';
        $newPw = $input['new_password'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $result = customer_change_password($db, (int)$customer['id'], $currentPw, $newPw, $userAgent);
        if (!$result['ok']) {
            http_response_code(stripos($result['error'] ?? '', 'current password') !== false ? 401 : 400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_address_create ---
    if ($request == "customer_address_create") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $result = customer_address_create($db, (int)$customer['id'], is_array($input) ? $input : []);
        if (!$result['ok']) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_address_update ---
    if ($request == "customer_address_update") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $addressId = (int)($input['id'] ?? 0);
        if ($addressId <= 0) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => "id is required"]);
            exit;
        }
        $result = customer_address_update($db, (int)$customer['id'], $addressId, $input);
        if (!$result['ok']) {
            http_response_code(stripos($result['error'] ?? '', 'not found') !== false ? 404 : 400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_address_delete ---
    if ($request == "customer_address_delete") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $addressId = (int)($input['id'] ?? 0);
        if ($addressId <= 0) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => "id is required"]);
            exit;
        }
        $result = customer_address_delete($db, (int)$customer['id'], $addressId);
        if (!$result['ok']) {
            http_response_code(stripos($result['error'] ?? '', 'not found') !== false ? 404 : 400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_address_set_default ---
    if ($request == "customer_address_set_default") {
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $addressId = (int)($input['id'] ?? 0);
        if ($addressId <= 0) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => "id is required"]);
            exit;
        }
        $result = customer_address_set_default($db, (int)$customer['id'], $addressId);
        if (!$result['ok']) {
            http_response_code(stripos($result['error'] ?? '', 'not found') !== false ? 404 : 400);
        }
        echo json_encode($result);
        exit;
    }
```

### Step 3.4: Syntax check

- [ ] **Step 3.4: Syntax check**

```bash
php -l api/index.php
```

Or Docker: `docker compose exec vm-emb-sites php -l /var/www/html/public/api/index.php`. Expected: `No syntax errors detected`.

### Step 3.5: End-to-end curl smoke tests

- [ ] **Step 3.5: Smoke test the endpoints**

The implementation should be tested against the demo store the user already has. Find an active store-level API key + domain (refresh from the sub-project A pattern):

```bash
docker compose exec vm-emb-sites php -r '
require_once "/var/www/html/public/module/database.php";
$pdb = new database_manager("/var/www/html/public/build/vm.engine.sql");
foreach ($pdb->query("SELECT api_key, domain FROM api_keys WHERE active = 1 LIMIT 5") as $r) echo $r["domain"] . " " . $r["api_key"] . "\n";
'
```

Set `KEY=<value>`, `DOMAIN=<value>`, and `BASE=http://localhost:8016/store-access/<store_id_or_domain>` based on what your routing reveals. The sub-project A smoke tests already proved the route is `http://localhost:8016/store-access/<store_id>/` — use the same.

Note: pre-existing site DBs that haven't seen `database.install.php` since Task 1 of THIS sub-project will be missing the `customer_addresses` table. Apply it directly to the target DB before running the smoke tests:

```bash
docker compose exec vm-emb-sites php -r '
define("__ANCHOR_SITE__", "debug.com");
require_once "/var/www/html/public/services/database.install.php";
echo "schema applied\n";
'
```

Register a fresh test customer to get a token:

```bash
EMAIL="acct-$(date +%s)@x.com"
REG=$(curl -s -X POST "$BASE/?state=customer_register" \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"goodpass1\",\"name\":\"Acct\"}")
echo "$REG"
TOKEN=$(echo "$REG" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["token"] ?? "";')
echo "token: $TOKEN"
```

**Test 1 — my_orders empty:**

```bash
curl -i "$BASE/?state=customer_my_orders" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"
```

Expected: HTTP 200, body `{"ok":true,"orders":[]}`.

**Test 2 — update_profile:**

```bash
curl -i -X POST "$BASE/?state=customer_update_profile" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated Name","phone":"5555"}'
```

Expected: HTTP 200, body `{"ok":true,"customer":{...,"name":"Updated Name","phone":"5555",...}}`.

**Test 3 — update_profile rejects empty:**

```bash
curl -i -X POST "$BASE/?state=customer_update_profile" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

Expected: HTTP 400, body `{"ok":false,"error":"At least one of name or phone must be provided"}`.

**Test 4 — address_create first becomes default:**

```bash
curl -i -X POST "$BASE/?state=customer_address_create" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"recipient_name":"Acct","line1":"12 Test St","city":"Cape Town","country":"South Africa"}'
```

Expected: HTTP 200, body includes `"is_default":true`. Note the `"id"` field — store it as `ADDR1`.

**Test 5 — addresses list:**

```bash
curl -i "$BASE/?state=customer_addresses" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"
```

Expected: HTTP 200, body `{"ok":true,"addresses":[{...,"is_default":true,...}]}` with one entry.

**Test 6 — address_update missing id:**

```bash
curl -i -X POST "$BASE/?state=customer_address_update" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"phone":"9999"}'
```

Expected: HTTP 400, body `{"ok":false,"error":"id is required"}`.

**Test 7 — address_update non-existent id:**

```bash
curl -i -X POST "$BASE/?state=customer_address_update" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"id":99999,"phone":"9999"}'
```

Expected: HTTP 404, body `{"ok":false,"error":"Address not found"}`.

**Test 8 — change_password wrong current:**

```bash
curl -i -X POST "$BASE/?state=customer_change_password" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"WRONG","new_password":"newpassword1"}'
```

Expected: HTTP 401, body `{"ok":false,"error":"Current password is incorrect"}`.

**Test 9 — change_password happy path:**

```bash
CP=$(curl -s -X POST "$BASE/?state=customer_change_password" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"goodpass1","new_password":"newpassword1"}')
echo "$CP"
NEW_TOKEN=$(echo "$CP" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["token"] ?? "";')
echo "new token: $NEW_TOKEN"
```

Expected: HTTP 200, body includes `"ok":true` and a new `token`. The old `$TOKEN` is now dead.

**Test 10 — old token dead after change_password:**

```bash
curl -i "$BASE/?state=customer_my_orders" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"
```

Expected: HTTP 401, body `{"ok":false,"error":"Invalid or expired token"}`.

**Test 11 — new token works:**

```bash
curl -i "$BASE/?state=customer_my_orders" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $NEW_TOKEN"
```

Expected: HTTP 200, body `{"ok":true,"orders":[]}`.

If any test returns the wrong status code or HTML instead of JSON, debug. The most likely failure mode is the same buffer-pollution issue from earlier — `api/index.php` was clean in sub-project A, so it should still be clean here. If HTML appears, add `while (ob_get_level() > 0) { ob_end_clean(); }` at the top of each new branch before `http_response_code`/`echo`.

### Step 3.6: Commit

- [ ] **Step 3.6: Commit**

```bash
git add api/index.php
git commit -m "feat: add customer-side account API endpoints

Eight new state= branches wrapping the customer_account module:
- GET customer_my_orders, customer_addresses
- POST customer_update_profile, customer_change_password,
  customer_address_create/update/delete/set_default

All require X-Customer-Token; customer id is always resolved from
the token, never trusted from input. Smoke-tested end-to-end:
register → update_profile → address_create → addresses → update
(404 for unknown id) → change_password → old token dead →
new token works."
```

---

## Verification checklist

Before declaring sub-project B done, confirm:

- [ ] `php -l services/database.install.php`, `php -l module/customer_account.php`, `php -l api/index.php` all report no syntax errors.
- [ ] `php tests/customer_account_test.php` exits 0 with all PASS.
- [ ] Running `database.install.php` twice on an existing per-site DB produces no errors.
- [ ] All 11 curl steps in Step 3.5 return the expected status codes and JSON bodies.
- [ ] After change_password, the old `X-Customer-Token` returns 401 on a subsequent request; the new token works.
- [ ] On a fresh customer, the first address_create returns a row with `"is_default":true`.
- [ ] `address_update` of a non-existent or other-customer's id returns 404 with `"Address not found"`.
- [ ] No changes were made to themes, admin pages, skel files, or sub-project A files. This sub-project is purely backend.
