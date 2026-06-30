# Customer Auth Backend Implementation Plan (Sub-project A)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add real customer accounts to each per-site storefront — DB tables, an auth module, and four API endpoints (`customer_register`, `customer_login`, `customer_logout`, `customer_me`) that issue/validate bearer tokens via an `X-Customer-Token` header.

**Architecture:** Pure-PHP auth module under `module/customer_auth.php` holds all business logic and is unit-tested against an isolated SQLite database. Schema migration lives in `services/database.install.php` and is idempotent (CREATE TABLE IF NOT EXISTS + PRAGMA-guarded ALTER). The API layer in `api/index.php` is a thin JSON wrapper around the module. The customer auth layer is additive — it never bypasses the existing store-level API key check.

**Tech Stack:** PHP 7.4+, PDO + SQLite (existing `database_manager`), standalone PHP test runner using `eq()` helper (same pattern as `tests/shopify_csv_parser_test.php`). No new dependencies.

**Spec:** [docs/superpowers/specs/2026-06-03-customer-auth-backend-design.md](../specs/2026-06-03-customer-auth-backend-design.md)

---

## File Map

**Create:**
- `module/customer_auth.php` — Pure-PHP module exposing `customer_register`, `customer_login`, `customer_logout`, `customer_resolve_token`, plus internal helpers. Every function takes `database_manager $db` as the first argument so it is testable in isolation.
- `tests/customer_auth_test.php` — Standalone test runner. Boots an in-memory-ish SQLite under `tests/tmp/`, runs the install SQL, exercises every public function and edge case.

**Modify:**
- `services/database.install.php` — Adds three statements after the existing `$db->query($sql_settings)` call: create `customers`, create `customer_sessions`, idempotently add `customer_id` to `orders`.
- `api/index.php` — Adds an `extract_customer_token()` helper near line 121 (next to the store API-key extraction) and four new `state=customer_*` branches inside the existing routing block.

**No changes** to `vm-admin/`, `themes/`, `pages/`, or `skel/`. Customer-side JS adoption happens in sub-project D.

**Deviation from spec:** The spec listed `customer_hash_password` and `customer_verify_password` as helpers. The plan inlines `password_hash($pw, PASSWORD_DEFAULT)` and `password_verify(...)` directly because the wrappers add no logic and each has only one caller. Behavior is unchanged.

---

## Notes on testing & running

- The project ships with no PHP test framework. Tests are standalone scripts that use a tiny `eq($expected, $actual, $msg)` helper, mirroring `tests/shopify_csv_parser_test.php`.
- Tests run either locally (`php tests/customer_auth_test.php`) or inside Docker (`docker compose exec vm-emb-sites php /var/www/html/public/tests/customer_auth_test.php`). The container mounts the project root at `/var/www/html/public/`.
- `database_manager::query()` (in `module/database.php`) catches `PDOException` and returns `[]`. That means INSERT errors are silent — duplicate-email detection must SELECT before INSERT and re-SELECT after to confirm success. The spec covers this explicitly.

---

## Task 1: Schema migration in `database.install.php`

**Files:**
- Modify: `services/database.install.php` (after line 97, append the new statements before the final `?>` comment block)

### Step 1.1: Add the schema statements

- [ ] **Step 1.1: Edit `services/database.install.php`**

Find the existing line near the bottom:

```php
$db->query($sql_settings);
//echo "Table 'settings' checked/created.\n";
```

Immediately after this block (before the commented-out demo `INSERT INTO products` lines), append:

```php

// 6. Customers Table (sub-project A)
$sql_customers = "CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    name TEXT,
    phone TEXT,
    email_verified INTEGER NOT NULL DEFAULT 0,
    failed_login_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$db->query($sql_customers);
$db->query("CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email)");

// 7. Customer Sessions Table (bearer tokens)
$sql_customer_sessions = "CREATE TABLE IF NOT EXISTS customer_sessions (
    token TEXT PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    user_agent TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
)";
$db->query($sql_customer_sessions);
$db->query("CREATE INDEX IF NOT EXISTS idx_sessions_customer ON customer_sessions(customer_id)");

// 8. Idempotently add customer_id to orders
$order_cols = $db->query("PRAGMA table_info(orders)");
$has_customer_id = false;
foreach ($order_cols as $col) {
    if (($col['name'] ?? '') === 'customer_id') {
        $has_customer_id = true;
        break;
    }
}
if (!$has_customer_id) {
    $db->query("ALTER TABLE orders ADD COLUMN customer_id INTEGER REFERENCES customers(id)");
}
$db->query("CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id)");
```

### Step 1.2: Syntax check

- [ ] **Step 1.2: Verify PHP syntax**

```bash
php -l services/database.install.php
```

Expected: `No syntax errors detected in services/database.install.php`.

### Step 1.3: Run install against an existing per-site DB twice (idempotency check)

- [ ] **Step 1.3: Verify idempotency**

Pick any existing site DB to verify against (the demo flow used earlier created `sites/claude.test/storage.data`; `sites/something.less/storage.data` also exists). Run the install code path manually using a tiny one-off PHP script that mimics what `database.install.php` does:

```bash
docker compose exec vm-emb-sites php -r '
define("__ANCHOR_SITE__", "claude.test");
require_once "/var/www/html/public/services/database.install.php";
echo "first run ok\n";
require_once "/var/www/html/public/services/database.install.php";
echo "second run ok\n";
'
```

Expected output:
```
first run ok
second run ok
```

If the second run reports an error like "duplicate column name: customer_id", the PRAGMA guard is wrong — fix and re-run. The `require_once` makes the second include a no-op for the *include* itself, but the install file is already procedural so the test still doesn't double-add. To force a true second pass, use two separate one-liners:

```bash
docker compose exec vm-emb-sites php -r '
define("__ANCHOR_SITE__", "claude.test");
require_once "/var/www/html/public/services/database.install.php";
'
docker compose exec vm-emb-sites php -r '
define("__ANCHOR_SITE__", "claude.test");
require_once "/var/www/html/public/services/database.install.php";
'
```

Both invocations should exit cleanly with no error logged. To confirm the tables exist:

```bash
docker compose exec vm-emb-sites sqlite3 /var/www/html/public/sites/claude.test/storage.data ".schema customers" 2>/dev/null \
  || docker compose exec vm-emb-sites php -r '
require_once "/var/www/html/public/module/database.php";
$db = new database_manager("/var/www/html/public/sites/claude.test/storage.data");
print_r($db->query("PRAGMA table_info(customers)"));
print_r($db->query("PRAGMA table_info(customer_sessions)"));
$cols = $db->query("PRAGMA table_info(orders)");
foreach ($cols as $c) echo $c["name"] . "\n";
'
```

The output should list the `customers` and `customer_sessions` columns, and the `orders` columns must include `customer_id`.

### Step 1.4: Commit

- [ ] **Step 1.4: Commit**

```bash
git add services/database.install.php
git commit -m "feat: add customers, customer_sessions tables, customer_id on orders

Adds the per-site schema for sub-project A (customer auth backend).
The ALTER on orders is guarded by a PRAGMA table_info check so
re-running install.php on an existing DB is a no-op."
```

---

## Task 2: Build the `customer_auth.php` module (TDD)

**Files:**
- Create: `tests/customer_auth_test.php`
- Create: `module/customer_auth.php`

### Step 2.1: Write the failing test runner

- [ ] **Step 2.1: Create `tests/customer_auth_test.php`**

This is the entire test file. It boots an isolated tmp SQLite DB, runs the schema, seeds a guest order, then exercises every public surface.

```php
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
```

### Step 2.2: Run the test — expect failure

- [ ] **Step 2.2: Run and confirm it fails**

```bash
php tests/customer_auth_test.php
```

Expected: PHP error like `Failed opening required '.../module/customer_auth.php'` because the module doesn't exist yet. That confirms the test reaches the require.

### Step 2.3: Implement `module/customer_auth.php`

- [ ] **Step 2.3: Create `module/customer_auth.php`**

This is the entire file:

```php
<?php
#   TITLE   : Customer Auth Module
#   DESC    : Per-site customer accounts and bearer-token sessions.
#             All public functions take database_manager $db as the first
#             argument so they are testable in isolation against an
#             isolated SQLite file.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES

if (!defined('CUSTOMER_AUTH_LOCKOUT_THRESHOLD')) {
    define('CUSTOMER_AUTH_LOCKOUT_THRESHOLD', 5);
}
if (!defined('CUSTOMER_AUTH_LOCKOUT_MINUTES')) {
    define('CUSTOMER_AUTH_LOCKOUT_MINUTES', 15);
}
if (!defined('CUSTOMER_AUTH_SESSION_DAYS')) {
    define('CUSTOMER_AUTH_SESSION_DAYS', 30);
}
if (!defined('CUSTOMER_AUTH_MIN_PASSWORD_LEN')) {
    define('CUSTOMER_AUTH_MIN_PASSWORD_LEN', 8);
}

/**
 * Register a new customer.
 *
 * @return array{ok:bool, error?:string, customer?:array, token?:string, expires_at?:string}
 */
function customer_register(database_manager $db, string $email, string $password, ?string $name = null, ?string $phone = null): array
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address'];
    }
    if (strlen($password) < CUSTOMER_AUTH_MIN_PASSWORD_LEN) {
        return ['ok' => false, 'error' => 'Password must be at least ' . CUSTOMER_AUTH_MIN_PASSWORD_LEN . ' characters'];
    }

    // SELECT-before-INSERT collision check (database_manager swallows
    // PDOExceptions, so we can't rely on the UNIQUE constraint to error).
    $existing = $db->query("SELECT id FROM customers WHERE email = ? LIMIT 1", [$email]);
    if (!empty($existing)) {
        return ['ok' => false, 'error' => 'Email already registered'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->query("INSERT INTO customers (email, password_hash, name, phone) VALUES (?, ?, ?, ?)",
        [$email, $hash, $name, $phone]);

    // Re-SELECT to retrieve the new id. If empty, the INSERT silently
    // failed (likely a race with another register call).
    $newRow = $db->query("SELECT * FROM customers WHERE email = ? LIMIT 1", [$email]);
    if (empty($newRow)) {
        return ['ok' => false, 'error' => 'Email already registered'];
    }
    $customer = $newRow[0];
    $customerId = (int)$customer['id'];

    customer_backfill_orders($db, $customerId, $email);

    $session = customer_create_session($db, $customerId, null);

    return [
        'ok' => true,
        'customer' => customer_public_view($customer),
        'token' => $session['token'],
        'expires_at' => $session['expires_at'],
    ];
}

/**
 * Verify credentials and issue a session.
 *
 * @return array{ok:bool, error?:string, customer?:array, token?:string, expires_at?:string}
 */
function customer_login(database_manager $db, string $email, string $password, ?string $userAgent = null): array
{
    $email = strtolower(trim($email));
    $row = $db->query("SELECT * FROM customers WHERE email = ? LIMIT 1", [$email]);
    if (empty($row)) {
        return ['ok' => false, 'error' => 'Invalid email or password'];
    }
    $customer = $row[0];
    $customerId = (int)$customer['id'];

    if (!empty($customer['locked_until'])) {
        $lockedUntil = strtotime($customer['locked_until']);
        if ($lockedUntil !== false && $lockedUntil > time()) {
            return ['ok' => false, 'error' => 'Account temporarily locked. Try again later.'];
        }
    }

    if (!password_verify($password, $customer['password_hash'])) {
        $newCount = (int)$customer['failed_login_attempts'] + 1;
        $db->query("UPDATE customers SET failed_login_attempts = ? WHERE id = ?",
            [$newCount, $customerId]);
        if ($newCount >= CUSTOMER_AUTH_LOCKOUT_THRESHOLD) {
            $db->query("UPDATE customers SET locked_until = datetime('now', '+" . CUSTOMER_AUTH_LOCKOUT_MINUTES . " minutes') WHERE id = ?",
                [$customerId]);
        }
        return ['ok' => false, 'error' => 'Invalid email or password'];
    }

    // Success — reset counters and issue session.
    $db->query("UPDATE customers SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?",
        [$customerId]);
    $session = customer_create_session($db, $customerId, $userAgent);

    // Re-fetch so locked/counter fields reflect the reset state in the response.
    $fresh = $db->query("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);
    $customer = !empty($fresh) ? $fresh[0] : $customer;

    return [
        'ok' => true,
        'customer' => customer_public_view($customer),
        'token' => $session['token'],
        'expires_at' => $session['expires_at'],
    ];
}

/**
 * Look up a customer by their session token. Slides expiry forward on hit.
 *
 * @return array|null Customer row (public view) or null if token is missing/expired.
 */
function customer_resolve_token(database_manager $db, ?string $token): ?array
{
    if ($token === null || $token === '') return null;

    $rows = $db->query(
        "SELECT c.* FROM customer_sessions cs
         JOIN customers c ON c.id = cs.customer_id
         WHERE cs.token = ? AND cs.expires_at > datetime('now')
         LIMIT 1",
        [$token]
    );
    if (empty($rows)) return null;

    $db->query(
        "UPDATE customer_sessions
            SET last_used_at = datetime('now'),
                expires_at = datetime('now', '+" . CUSTOMER_AUTH_SESSION_DAYS . " days')
          WHERE token = ?",
        [$token]
    );

    return customer_public_view($rows[0]);
}

/**
 * Invalidate a session token. Idempotent.
 */
function customer_logout(database_manager $db, ?string $token): array
{
    if (!empty($token)) {
        $db->query("DELETE FROM customer_sessions WHERE token = ?", [$token]);
    }
    return ['ok' => true];
}

// --- Internal helpers (not part of the public API surface) ---

function customer_generate_token(): string
{
    return bin2hex(random_bytes(32));
}

function customer_create_session(database_manager $db, int $customerId, ?string $userAgent): array
{
    $token = customer_generate_token();
    $db->query(
        "INSERT INTO customer_sessions (token, customer_id, expires_at, user_agent)
         VALUES (?, ?, datetime('now', '+" . CUSTOMER_AUTH_SESSION_DAYS . " days'), ?)",
        [$token, $customerId, $userAgent]
    );
    $row = $db->query("SELECT expires_at FROM customer_sessions WHERE token = ?", [$token]);
    $expiresAt = !empty($row) ? $row[0]['expires_at'] : '';
    return ['token' => $token, 'expires_at' => $expiresAt];
}

function customer_backfill_orders(database_manager $db, int $customerId, string $email): void
{
    $db->query(
        "UPDATE orders SET customer_id = ?
          WHERE LOWER(customer_email) = LOWER(?) AND customer_id IS NULL",
        [$customerId, $email]
    );
}

/**
 * Strip secrets from a raw customer row before returning to callers.
 */
function customer_public_view(array $row): array
{
    return [
        'id' => (int)($row['id'] ?? 0),
        'email' => $row['email'] ?? '',
        'name' => $row['name'] ?? null,
        'phone' => $row['phone'] ?? null,
        'email_verified' => (bool)($row['email_verified'] ?? 0),
        'created_at' => $row['created_at'] ?? null,
    ];
}
```

### Step 2.4: Run the test — expect pass

- [ ] **Step 2.4: Run the test**

```bash
php tests/customer_auth_test.php
```

Expected: every line prints `PASS`, summary `N passed, 0 failed`, exit code 0. The test file above has ~30 assertions; the exact total isn't important — what matters is **0 failed**.

If any assertion fails, fix the module (not the test) and re-run. Do not proceed until green.

### Step 2.5: Commit

- [ ] **Step 2.5: Commit**

```bash
git add module/customer_auth.php tests/customer_auth_test.php
git commit -m "feat: add customer_auth module with register/login/logout/resolve

Pure-PHP module with all business logic for per-site customer
accounts: bcrypt password hashing, 64-char hex bearer tokens with
30-day sliding expiry, per-account lockout after 5 failed logins
for 15 minutes, and case-insensitive backfill of pre-account
guest orders by email match.

Tested via tests/customer_auth_test.php (~30 assertions covering
happy paths, invalid input, duplicate email, lockout, sliding
expiry, and logout idempotency)."
```

---

## Task 3: Wire up the API endpoints in `api/index.php`

**Files:**
- Modify: `api/index.php` (add helper near line 121, add four `state` branches inside existing routing)

### Step 3.1: Add the `extract_customer_token()` helper

- [ ] **Step 3.1: Edit `api/index.php`**

Find the existing block around lines 120–137 that extracts the store API key:

```php
// --- API key validation ---
$api_key = '';
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $api_key = $_SERVER['HTTP_X_API_KEY'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (strpos($auth, 'Bearer ') === 0) {
        $api_key = substr($auth, 7);
    }
} elseif (isset($_GET['api_key'])) {
    $api_key = $_GET['api_key'];
}
```

Immediately **after** the existing `if (empty($api_key)) { ... exit; }` block (around line 137), but BEFORE the `$key_record = $private_db->query(...)` line, add:

```php

// --- Customer token (optional, additive — never replaces store API key) ---
function extract_customer_token(): ?string {
    $tok = $_SERVER['HTTP_X_CUSTOMER_TOKEN'] ?? '';
    $tok = is_string($tok) ? trim($tok) : '';
    return $tok === '' ? null : $tok;
}
```

Also update the `Access-Control-Allow-Headers` header at line 10 so browser preflight requests accept the new header. The existing line is:

```php
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key");
```

Change to:

```php
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key, X-Customer-Token");
```

### Step 3.2: Require the auth module

- [ ] **Step 3.2: Add the require near the top of the routing block**

Find the line near 168:

```php
@include_once dirname(__FILE__) . "/../module/database.php";
$db = new database_manager($public_db_path);
```

Immediately after `$db = new database_manager(...);`, add:

```php
@include_once dirname(__FILE__) . "/../module/customer_auth.php";
```

The `@include_once` matches the project's existing pattern.

### Step 3.3: Add the four customer state branches

- [ ] **Step 3.3: Edit `api/index.php`**

The file has separate `if ($method === 'GET')` and `elseif ($method === 'POST')` blocks. Register/Login/Logout are POST; `customer_me` is GET.

**Inside the GET block:** find the closing `}` of the existing GET routing (after the last GET endpoint, before `elseif ($method === 'POST')`). Just before that closing brace, add:

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

**Inside the POST block:** the existing POST block starts with `$input = json_decode(file_get_contents('php://input'), true);`. Find the last existing POST endpoint, then before the closing `}` of the elseif, add:

```php

    // --- Customer auth: POST customer_register ---
    if ($request == "customer_register") {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $name = $input['name'] ?? null;
        $phone = $input['phone'] ?? null;
        $result = customer_register($db, $email, $password, $name, $phone);
        if (!$result['ok']) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer auth: POST customer_login ---
    if ($request == "customer_login") {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $result = customer_login($db, $email, $password, $userAgent);
        if (!$result['ok']) {
            http_response_code(stripos($result['error'] ?? '', 'locked') !== false ? 429 : 401);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer auth: POST customer_logout ---
    if ($request == "customer_logout") {
        $token = extract_customer_token();
        echo json_encode(customer_logout($db, $token));
        exit;
    }
```

### Step 3.4: Syntax check

- [ ] **Step 3.4: Syntax check**

```bash
php -l api/index.php
```

Expected: `No syntax errors detected in api/index.php`.

### Step 3.5: Smoke-test the endpoints via curl

- [ ] **Step 3.5: End-to-end smoke test**

Use the existing `claude.test` site (created during the earlier demo flow). First, get a valid store-level API key. Check what's in the engine DB:

```bash
docker compose exec vm-emb-sites php -r '
require_once "/var/www/html/public/module/database.php";
$pdb = new database_manager("/var/www/html/public/build/vm.engine.sql");
print_r($pdb->query("SELECT api_key, domain FROM api_keys WHERE active = 1 LIMIT 5"));
' 2>/dev/null
```

(If the schema differs, adjust the SELECT to whatever columns exist. The point is to find one active key for a site whose `sites/{domain}/storage.data` we can hit.)

Set `KEY=...` and `DOMAIN=...` in your shell from the result, then:

**Register:**

```bash
curl -i -X POST "http://localhost:8016/api/$DOMAIN/?state=customer_register" \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"smoke@x.com","password":"smoketest!","name":"Smoke"}'
```

Expected: HTTP 200, body like `{"ok":true,"customer":{"id":...,"email":"smoke@x.com",...},"token":"...64hex...","expires_at":"..."}`.

Capture the token: `TOKEN=<token from above>`.

**Me with the token:**

```bash
curl -i "http://localhost:8016/api/$DOMAIN/?state=customer_me" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"
```

Expected: HTTP 200, `{"ok":true,"customer":{...}}`.

**Me without the token:**

```bash
curl -i "http://localhost:8016/api/$DOMAIN/?state=customer_me" \
  -H "Authorization: Bearer $KEY"
```

Expected: HTTP 401, `{"ok":false,"error":"Invalid or expired token"}`.

**Login with the same email/password:**

```bash
curl -i -X POST "http://localhost:8016/api/$DOMAIN/?state=customer_login" \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"smoke@x.com","password":"smoketest!"}'
```

Expected: HTTP 200, new token in the response.

**Login with wrong password:**

```bash
curl -i -X POST "http://localhost:8016/api/$DOMAIN/?state=customer_login" \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"smoke@x.com","password":"WRONG"}'
```

Expected: HTTP 401, `{"ok":false,"error":"Invalid email or password"}`.

**Logout:**

```bash
curl -i -X POST "http://localhost:8016/api/$DOMAIN/?state=customer_logout" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"
```

Expected: HTTP 200, `{"ok":true}`.

**Me after logout (should now 401):**

```bash
curl -i "http://localhost:8016/api/$DOMAIN/?state=customer_me" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"
```

Expected: HTTP 401.

If any of these return unexpected status codes or HTML instead of JSON, debug. The most likely culprit if HTML appears: another `ob_start()` somewhere in the include chain, similar to what we fixed for the Shopify import. The mitigation pattern is `while (ob_get_level() > 0) { ob_end_clean(); }` before `header('Content-Type: application/json')`.

If the API endpoints already don't suffer from the buffer issue (api/index.php doesn't include `interface.php`), they should respond with pure JSON without that workaround. Confirm by checking the first character of the response body — if it's `<` instead of `{`, add the buffer-clean line at the top of each new branch.

### Step 3.6: Commit

- [ ] **Step 3.6: Commit**

```bash
git add api/index.php
git commit -m "feat: add customer_register/login/logout/me API endpoints

Four new state= branches in api/index.php wrapping the
customer_auth module. Customer bearer is sent in
X-Customer-Token; the store-level Authorization: Bearer header
is still required. CORS allow-list updated. Smoke-tested via
curl end-to-end."
```

---

## Verification checklist

Before declaring sub-project A done, confirm:

- [ ] `php -l services/database.install.php`, `php -l module/customer_auth.php`, `php -l api/index.php` all report no syntax errors.
- [ ] `php tests/customer_auth_test.php` exits 0 with all PASS.
- [ ] Running `database.install.php` twice in a row on an existing per-site DB produces no errors (the PRAGMA-guarded ALTER on `orders` is a no-op the second time).
- [ ] All 7 curl steps in Step 3.5 return the expected status codes and JSON bodies.
- [ ] On a fresh registration, the response contains a 64-character hex token; the same token used in `customer_me` returns the customer; after logout it returns 401.
- [ ] When an `orders` row exists with email `X` and a customer registers with email `X`, the order's `customer_id` is set to the new customer's id.
- [ ] No changes were made to themes, admin pages, or skel files. This sub-project is purely backend.
