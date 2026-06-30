# Customer Auth Backend — Design (Sub-project A)

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co
**Parent decomposition:** This is the first of four sub-projects derived from the broader "themes become Shopify-style online stores" request. The other three are:

- B. Customer-side API expansion (my-orders, addresses, profile)
- C. Theme audit + storefront baseline
- D. Wire theme dashboards to the real auth + API

## Summary

Add real customer accounts to each per-site storefront. A customer can
register, log in, and stay logged in for 30 days via a bearer token sent in
an `X-Customer-Token` header. New customers automatically inherit any
previous guest orders that share their email. Email verification, password
reset, and address management are explicitly out of scope for this
sub-project.

## Goals

- Provide a foundation that sub-projects B and D can build on without
  rework: every authenticated request can resolve a customer row.
- Match the existing API style (`api/index.php`, `state=...` routing,
  JSON envelopes with `ok` / `error`).
- Keep the public store-level API key flow untouched — customer auth is an
  additive layer.
- Migrate existing per-site SQLite databases idempotently; never drop a
  table or column.

## Non-goals

- No email verification send / token flow. The `email_verified` column
  exists but defaults to 0 and is never checked. A future sub-project will
  wire the sender once the SMTP code path is built.
- No password reset / "forgot password".
- No address book, no profile editing (sub-project B).
- No theme UI changes (sub-project D).
- No IP-based rate limiting — only per-account lockout.
- No refresh-token / short-lived access-token split. A single long-lived
  bearer with sliding expiry is sufficient.
- No OAuth / social login.

## User flow

A customer-side JS (theme code in sub-project D, or a curl smoke test in
this sub-project) performs:

1. `POST /api/{domain}/?state=customer_register` with
   `{ email, password, name?, phone? }`.
2. Server validates input, hashes the password, inserts a `customers` row,
   creates a `customer_sessions` row with a fresh random token, backfills
   any matching guest orders, and responds `{ ok: true, customer, token,
   expires_at }`.
3. Client stores the token in `localStorage` under `vm_customer_token`.
4. On subsequent requests the client sends `X-Customer-Token: <token>`
   alongside the existing `Authorization: Bearer <store_api_key>`.
5. `GET /api/{domain}/?state=customer_me` returns `{ ok: true, customer }`
   when the token is valid; `{ ok: false, error: 'Invalid or expired
   token' }` with HTTP 401 otherwise.
6. `POST /api/{domain}/?state=customer_logout` deletes the session row;
   subsequent uses of the token return 401.

The store-level API key flow is unchanged. Every request still validates
the store key. The customer token is an additional, optional header that
binds the request to a customer. Endpoints in sub-project B will require
both.

## Component split

**New files:**

- `module/customer_auth.php` — Pure-PHP module exposing the customer auth
  surface. All functions take a `database_manager $db` as their first
  argument so they can be unit-tested against an isolated SQLite file.
  Exposed functions:
  - `customer_register(database_manager $db, string $email, string $password, ?string $name, ?string $phone): array`
  - `customer_login(database_manager $db, string $email, string $password, ?string $userAgent): array`
  - `customer_logout(database_manager $db, string $token): array`
  - `customer_resolve_token(database_manager $db, string $token): ?array`
  - Internal helpers: `customer_hash_password`, `customer_verify_password`,
    `customer_generate_token`, `customer_backfill_orders`,
    `customer_create_session`.
- `tests/customer_auth_test.php` — Standalone PHP test runner using the
  same `eq()` helper pattern as `tests/shopify_csv_parser_test.php`. Spins
  up a temp SQLite DB, runs the install SQL, exercises the module.
- `tests/fixtures/seed_orders.sql` — Optional small SQL snippet inserting
  a guest order with a known email so the backfill test has something to
  match.

**Modified files:**

- `services/database.install.php` — Adds three new statements:
  1. `CREATE TABLE IF NOT EXISTS customers ...`
  2. `CREATE TABLE IF NOT EXISTS customer_sessions ...`
  3. An idempotent `ALTER TABLE orders ADD COLUMN customer_id INTEGER`
     guarded by a `PRAGMA table_info(orders)` check that skips the ALTER
     when the column already exists.

- `api/index.php` — Adds four new `elseif ($state === 'customer_*')`
  branches alongside the existing `state` switch (products, categories,
  etc.). Each branch:
  1. Calls a thin helper that reads the request body (JSON or form).
  2. Delegates to the corresponding `customer_auth.php` function.
  3. Emits a JSON response with the appropriate HTTP status code.

  Also adds one helper near the top of the file:
  `extract_customer_token()` — reads `X-Customer-Token` or
  `HTTP_X_CUSTOMER_TOKEN` and returns the string or null.

**No changes** to `vm-admin/`, `themes/`, `pages/`, or `skel/`. The
customer-side JS in themes will adopt the new endpoints in sub-project D.

## Data model

Three schema changes, all in the per-site database at
`sites/{domain}/storage.data`. The `__DB_MODULE__` (main engine DB) is
NOT touched — customer accounts are per-site, mirroring Shopify's model.

```sql
-- 1. New table: customers
CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    name TEXT,
    phone TEXT,
    email_verified INTEGER NOT NULL DEFAULT 0,
    failed_login_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);

-- 2. New table: customer_sessions
CREATE TABLE IF NOT EXISTS customer_sessions (
    token TEXT PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    user_agent TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_sessions_customer ON customer_sessions(customer_id);

-- 3. Idempotent column add on existing orders table
--    Implemented in PHP as:
--    $cols = $db->query("PRAGMA table_info(orders)");
--    $has = false; foreach ($cols as $c) if ($c['name'] === 'customer_id') $has = true;
--    if (!$has) $db->query("ALTER TABLE orders ADD COLUMN customer_id INTEGER REFERENCES customers(id)");
--    $db->query("CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id)");
```

### Field notes

- `customers.email` uses `COLLATE NOCASE` so `Alice@x.com` and
  `alice@x.com` collide on the unique index and on lookups.
- `customers.password_hash` is the output of `password_hash($pw,
  PASSWORD_DEFAULT)`. PHP's default is bcrypt today; the column is sized to
  accommodate the longer Argon2 output if PHP's default changes.
- `customer_sessions.token` is a 64-char hex string from
  `bin2hex(random_bytes(32))`. Random, unguessable, suitable for use as a
  primary key.
- `expires_at` is computed as `datetime('now', '+30 days')` at session
  creation. On every successful `customer_resolve_token` call the row's
  `last_used_at` is set to now and `expires_at` is reset to now+30 days —
  sliding expiry.
- `orders.customer_id` is nullable so existing guest orders remain valid.
  Backfill on register sets it where emails match.

## Auth flow details

### Register (`customer_register`)

```text
Input: $email, $password, $name?, $phone?

1. Trim/lowercase $email. Validate format: filter_var($email, FILTER_VALIDATE_EMAIL).
   On invalid → return ['ok' => false, 'error' => 'Invalid email address'].
2. Validate $password length: >= 8 chars. Otherwise → 'Password must be at least 8 characters'.
3. Hash password: $hash = password_hash($password, PASSWORD_DEFAULT).
4. Check email uniqueness FIRST (because database_manager::query() swallows
   PDOExceptions, we can't rely on the UNIQUE constraint surfacing as an error):
     SELECT id FROM customers WHERE email = ? LIMIT 1
   If a row exists → return ['ok' => false, 'error' => 'Email already registered'].
   Otherwise INSERT:
     INSERT INTO customers (email, password_hash, name, phone) VALUES (?, ?, ?, ?)
   Then re-SELECT to retrieve the new id; if the SELECT returns no row, the
   INSERT silently failed (likely a race with another register call) — return
   ['ok' => false, 'error' => 'Email already registered'].
5. Resolve the new customer's id:
     SELECT id FROM customers WHERE email = ? ORDER BY id DESC LIMIT 1
6. Backfill orders (customer_backfill_orders):
     UPDATE orders SET customer_id = :id
       WHERE LOWER(customer_email) = LOWER(:email) AND customer_id IS NULL
7. Create session (customer_create_session): generate token, INSERT into
   customer_sessions with expires_at = now+30d.
8. Return ['ok' => true, 'customer' => {id, email, name, phone,
   email_verified: false, created_at}, 'token', 'expires_at'].
```

### Login (`customer_login`)

```text
Input: $email, $password, $userAgent

1. SELECT * FROM customers WHERE email = ? LIMIT 1
2. If no row → return ['ok' => false, 'error' => 'Invalid email or password']
   with HTTP 401. (Same error as wrong password, to avoid email enumeration.)
3. If locked_until > NOW() → return ['ok' => false, 'error' => 'Account
   temporarily locked. Try again later.'] with HTTP 429.
4. password_verify($password, row.password_hash).
5. On failure:
     UPDATE customers SET failed_login_attempts = failed_login_attempts + 1
       WHERE id = ?
   Then if new count >= 5:
     UPDATE customers SET locked_until = datetime('now', '+15 minutes')
       WHERE id = ?
   Return same generic error.
6. On success:
     UPDATE customers SET failed_login_attempts = 0, locked_until = NULL
       WHERE id = ?
   Create session, return ['ok' => true, 'customer', 'token', 'expires_at'].
```

### Resolve token (`customer_resolve_token`)

```text
Input: $token

1. If $token is empty/null → return null.
2. SELECT cs.*, c.id AS cust_id, c.email, c.name, c.phone, c.email_verified
     FROM customer_sessions cs
     JOIN customers c ON c.id = cs.customer_id
     WHERE cs.token = ? AND cs.expires_at > datetime('now')
     LIMIT 1
3. If no row → return null.
4. Slide expiry:
     UPDATE customer_sessions
        SET last_used_at = datetime('now'),
            expires_at   = datetime('now', '+30 days')
      WHERE token = ?
5. Return the customer hash (id, email, name, phone, email_verified).
```

### Logout (`customer_logout`)

```text
Input: $token

1. DELETE FROM customer_sessions WHERE token = ?
2. Return ['ok' => true]. Idempotent — succeeds even if token didn't exist.
```

### Helpers

- `customer_generate_token(): string` — returns `bin2hex(random_bytes(32))`.
- `customer_hash_password($pw)` — `password_hash($pw, PASSWORD_DEFAULT)`.
- `customer_verify_password($pw, $hash)` — `password_verify($pw, $hash)`.
- `customer_create_session($db, $customerId, $userAgent)` — INSERTs into
  `customer_sessions`, returns `['token', 'expires_at']`.
- `customer_backfill_orders($db, $customerId, $email)` — runs the UPDATE
  described above; no return value.

## API surface

All four endpoints live in `api/index.php` alongside existing `state=`
branches. Every endpoint still requires a valid store-level API key (the
existing `Authorization: Bearer <store_api_key>` check). Customer endpoints
additionally read `X-Customer-Token` where stated.

### `POST ?state=customer_register`

**Request body** (JSON or form-encoded; the existing handler reads both):
```json
{ "email": "alice@x.com", "password": "hunter22!", "name": "Alice", "phone": "0123" }
```

**Success (200):**
```json
{
  "ok": true,
  "customer": {
    "id": 12,
    "email": "alice@x.com",
    "name": "Alice",
    "phone": "0123",
    "email_verified": false,
    "created_at": "2026-06-03T13:45:00Z"
  },
  "token": "9f3c...64hex",
  "expires_at": "2026-07-03T13:45:00Z"
}
```

**Failure (400):** `{ "ok": false, "error": "Invalid email address" }` /
"Password must be at least 8 characters" / "Email already registered".

### `POST ?state=customer_login`

**Request body:** `{ "email": "...", "password": "..." }`.

**Success (200):** same shape as register success.

**Failures:**
- 401 `{ "ok": false, "error": "Invalid email or password" }`
- 429 `{ "ok": false, "error": "Account temporarily locked. Try again later.", "code": "locked" }`

The `code` field is a structured marker the API layer uses to decide between 429 and 401 without pattern-matching on the error message. Clients may also key UI behavior off `code` rather than parsing `error`.

### `GET ?state=customer_me`

**Headers:** `X-Customer-Token: <token>` required.

**Success (200):** `{ "ok": true, "customer": { id, email, name, phone, email_verified, created_at } }`

**Failure (401):** `{ "ok": false, "error": "Invalid or expired token" }`

### `POST ?state=customer_logout`

**Headers:** `X-Customer-Token: <token>` required.

**Success (200):** `{ "ok": true }`

**No 401** — logout is idempotent; even an unknown token returns ok so
clients can blindly clear their local storage.

### `extract_customer_token()` helper

Reads `$_SERVER['HTTP_X_CUSTOMER_TOKEN']`. Returns the string (trimmed) or
null. Mirrors the existing store-key extraction at api/index.php:121.

## Failure modes & guards

| Scenario | Behavior |
|---|---|
| Duplicate email at register | SELECT-before-INSERT to detect collision (database_manager swallows PDOExceptions, so we can't rely on UNIQUE constraint feedback). Returns "Email already registered". |
| Race: two register calls with same email | First wins on INSERT. Second's SELECT-before-INSERT may miss the race if interleaved; UNIQUE constraint catches it at INSERT time; the swallowed exception leaves the resulting SELECT-by-id empty, which we detect and return as "Email already registered". |
| 5 wrong passwords on login | `locked_until = now + 15 minutes`. Subsequent attempts return 429 until the window expires. Counters reset on next successful login. |
| Expired token on `customer_me` | Returns 401 `{ ok: false, error: 'Invalid or expired token' }`. Client should drop the token from localStorage. |
| Logout with unknown token | Returns 200 `{ ok: true }`. Idempotent. |
| Backfill races with concurrent guest checkout | Backfill only updates `customer_id IS NULL` rows. Concurrent checkout writes new rows with `customer_id = NULL` AFTER the backfill — those don't get linked retroactively, which is the correct behavior (the checkout was anonymous). |
| Re-running database.install.php on existing DB | All new CREATE TABLE statements use `IF NOT EXISTS`. The ALTER on `orders` is guarded by a `PRAGMA table_info` check, so re-running is a no-op. |
| Customer endpoint called without store API key | Existing `api/index.php:133` returns the standard 401 "API key required". Customer auth never bypasses store auth. |

## Security notes

- Bearer token in localStorage is XSS-vulnerable by design. Mitigation:
  short-to-medium TTL with easy server-side revocation via `logout`.
  Sub-project D's theme work will need to keep theme HTML XSS-free; that's
  a theme-author responsibility.
- Email enumeration: login returns the same error for both unknown email
  and wrong password. Registration intentionally returns a distinct error
  because hiding it would silently break account creation UX.
- Password complexity: minimum 8 characters. No upper/lower/digit
  requirement to keep the spec focused. Sub-projects can extend later.
- Account lockout is per-account, not per-IP, so it does not stop a
  distributed brute-force across many emails. A future IP rate limit can
  layer on top.
- No CSRF token because the customer endpoints are JSON over a custom
  header (`X-Customer-Token`), not cookie-authenticated. A browser
  cross-origin POST cannot set custom headers without CORS preflight,
  which is gated by `Access-Control-Allow-Headers` in
  `api/index.php`.

## Testing

`tests/customer_auth_test.php` — standalone PHP runner, exit 0 on pass
and 1 on fail. Uses an `eq()` helper identical to the existing parser
tests. The runner:

1. Creates a tmp DB file under `tests/tmp/customer_auth_<pid>.sqlite`.
2. Boots `database_manager` against it and runs the three CREATE/ALTER
   statements inline in the test file. We do NOT factor a shared
   `customer_auth_install_sql()` helper — there are only two callers
   (install + test), and inlining mirrors the simplicity of the existing
   parser test.
3. Pre-seeds an `orders` row with `customer_email = 'backfill@x.com'`,
   `customer_id IS NULL`.
4. Asserts:
   - Register with a valid email creates a row and a session token; the
     pre-seeded order's `customer_id` is now set.
   - Register again with the same email returns `Email already registered`.
   - Register with a 3-char password returns the validation error.
   - Login with the wrong password 5 times triggers a lockout; the 6th
     attempt returns 429.
   - Login with the correct password resets the lockout counters.
   - `customer_resolve_token` with the issued token returns the customer
     row; the session's `expires_at` slides forward.
   - `customer_logout` deletes the session; `customer_resolve_token`
     returns null thereafter.
5. Tears down the tmp DB on exit.

API endpoints are smoke-tested manually via `curl` after implementation —
the helper functions carry all the logic, and the endpoints are thin
JSON envelopes.

## Open questions

None. Approved in brainstorming on 2026-06-03.
