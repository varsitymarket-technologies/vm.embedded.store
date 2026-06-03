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
