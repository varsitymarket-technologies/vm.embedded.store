# state=order — Persist customer_id + Phone + Address

**Date:** 2026-06-04
**Status:** Approved
**Owner:** keenan@doneros.co
**Parent decomposition:** Standalone backend follow-up. Closes a gap documented in every D-series spec (D.2 austin through D.6 lafromage, D.3.x batch, and all 5 D.6-style cousin ports). Each port noted that themes were sending `customer_id` and address fields to `state=order` but the backend silently dropped them.

## Summary

Bring the legacy `state=order` POST handler up to parity with the newer `checkout_complete` handler in `api/index.php`. The endpoint already accepts `name`, `email`, `total`, `items`; this upgrade adds optional `customer_phone` and address fragments, and derives `customer_id` from the `X-Customer-Token` header (server-side, secure).

Apply identically to both files that ship a `state=order` handler:

- `skel/api.php` — per-site copy used by deployed sites.
- `api/index.php` — host-side legacy handler used by admin and cross-site flows. (`checkout_complete` in the same file is a separate, newer handler with its own session-based flow; left alone.)

No theme changes. No new client contracts. No breaking changes to the response shape.

## Non-goals

- Modifying any theme.
- Touching `checkout_complete` (the newer handler that uses checkout_sessions).
- Changing `customer_my_orders` (already reads `customer_phone`/`customer_address` with null-coalesce).
- Adding individual address columns (street/city/postal/country). The schema stays single-column `customer_address` TEXT, matching `checkout_complete`'s schema.
- Backfilling existing orders. `customer_backfill_orders` (called during `customer_register`) already links pre-existing orders to a new customer's id by email.

## Components

Two backend files. No new files. No new modules.

1. `skel/api.php` — `state=order` block (currently lines 243-266). Replaced.
2. `api/index.php` — `state=order` block (currently lines 518-545). Replaced with the same logic, leveraging the existing `extract_customer_token()` helper (already in scope there).
3. `tests/customer_account_test.php` — extended with 7 new assertions covering the upgraded endpoint behavior.

## Endpoint contract

### Request

POST `api.php?state=order` or `api/index.php?state=order` with JSON body.

Required (unchanged):
- `name` (string) — customer name. Falls back to token's `customer.name` if empty AND a valid token is present.
- `total` (number) — order total. Required.

Strongly recommended:
- `email` (string) — customer email. Falls back to token's `customer.email` if empty AND a valid token is present.
- `items` (array OR JSON string) — line items.

Optional, new (server reads if present, silently ignores otherwise):
- `customer_phone` OR `phone` (string) — phone number. Either key accepted.
- `addr_street`, `addr_city`, `addr_postal`, `addr_country`, `addr_province` (or `addr_state` as a synonym) — address fragments. Any subset accepted.

Optional header:
- `X-Customer-Token` — if present AND resolves to a valid customer, server uses `customer['id']` as `customer_id`. The body's `customer_id` field (if any) is ignored for security. If the token is invalid or absent, `customer_id` is stored as NULL.

### Response

Unchanged shapes:

| Outcome | Status | Body |
|---|---|---|
| Success | 200 | `{"success": true, "message": "Order placed successfully"}` |
| Missing `name` or `total` | 400 | `{"error": "Incomplete order data"}` |
| DB insert failure | 500 | `{"error": "Failed to save order"}` |

No new fields. Existing clients that don't look at customer_id continue to work unchanged. Clients that send the new fields get their data persisted.

## Server-side logic (canonical)

The same shape applies to both files. The only difference: `api/index.php` already has `extract_customer_token()` and `customer_resolve_token()` loaded unconditionally, so the `function_exists` guards in `skel/api.php` are not needed there.

```php
if ($request == "order") {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = []; }

    $customer_name  = trim($input['name'] ?? '');
    $customer_email = trim($input['email'] ?? '');
    $total_amount   = $input['total'] ?? 0;
    $items = is_array($input['items'] ?? null)
                ? json_encode($input['items'])
                : ($input['items'] ?? '[]');

    // Optional phone (accept either key).
    $customer_phone = trim($input['customer_phone'] ?? $input['phone'] ?? '');

    // Build customer_address from whatever address fragments arrived.
    // Order: street, city, postal, province|state, country.
    $addr_parts = array_filter([
        trim($input['addr_street']   ?? ''),
        trim($input['addr_city']     ?? ''),
        trim($input['addr_postal']   ?? ''),
        trim($input['addr_province'] ?? $input['addr_state'] ?? ''),
        trim($input['addr_country']  ?? ''),
    ], fn($s) => $s !== '');
    $customer_address = implode(', ', $addr_parts);

    // Derive customer_id from token (NEVER trust body customer_id).
    $customer_id = null;
    // In skel/api.php only; api/index.php skips this guard.
    if (function_exists('customer_resolve_token') && function_exists('extract_customer_token')) {
        $token = extract_customer_token();
        if ($token) {
            $c = customer_resolve_token($db, $token);
            if (is_array($c) && isset($c['id'])) {
                $customer_id = (int)$c['id'];
                // Auto-fill name/email from token if client omitted them.
                if ($customer_name === '')  $customer_name  = $c['name']  ?? '';
                if ($customer_email === '') $customer_email = $c['email'] ?? '';
            }
        }
    }

    if (empty($customer_name) || empty($total_amount)) {
        http_response_code(400);
        echo json_encode(["error" => "Incomplete order data"]);
        exit;
    }

    // Ensure orders table exists with the full schema.
    $db->createTable("orders", [
        "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
        "customer_id" => "INTEGER",
        "customer_name" => "VARCHAR(255)",
        "customer_email" => "VARCHAR(255)",
        "customer_phone" => "VARCHAR(50)",
        "customer_address" => "TEXT",
        "total_amount" => "DECIMAL(10,2)",
        "items" => "TEXT",
        "status" => "VARCHAR(50) DEFAULT 'pending'",
        "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
    ]);

    // Migration for legacy tables that pre-date the new columns.
    // PRAGMA-guarded so we don't spam error-file.log with duplicate-column
    // exceptions on every request after the migration has run.
    $existing = $db->query("PRAGMA table_info(orders)");
    $colNames = $existing ? array_column($existing, 'name') : [];
    $migrations = [
        'customer_id'      => 'INTEGER',
        'customer_phone'   => 'VARCHAR(50)',
        'customer_address' => 'TEXT',
    ];
    foreach ($migrations as $col => $type) {
        if (!in_array($col, $colNames, true)) {
            $db->executeSql("ALTER TABLE orders ADD COLUMN {$col} {$type}");
        }
    }

    $sql = "INSERT INTO orders (customer_id, customer_name, customer_email, customer_phone, customer_address, total_amount, items, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', datetime('now'))";
    $result = $db->query($sql, [
        $customer_id, $customer_name, $customer_email,
        $customer_phone, $customer_address, $total_amount, $items
    ]);

    if ($result) {
        echo json_encode(["success" => true, "message" => "Order placed successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to save order"]);
    }
}
```

## Migration approach

The PRAGMA check runs once per `state=order` request. After the migration has run on a given DB, the column is in `colNames` and the ALTER is skipped — no error log noise. For brand-new sites where `createTable` builds the schema directly, the PRAGMA loop finds all columns present and does nothing.

Two-step approach (createTable + targeted ALTERs) handles both:
- Fresh sites: `createTable` builds the full schema; PRAGMA loop is a no-op.
- Legacy sites: `createTable` is idempotent so it doesn't re-create; PRAGMA loop catches and ALTERs the missing columns.

## Security model

- `customer_id` is **never** trusted from the body. It is derived server-side from `X-Customer-Token` via `customer_resolve_token`.
- A malicious client cannot attribute orders to other customers by sending a forged `customer_id` field — the field is ignored.
- An unauthenticated client (no token) gets `customer_id = NULL`. This matches the current behavior for guest checkouts.
- Body's `name`/`email` are still trusted as-is (they were always free-form), but when a valid token is present they are AUTO-FILLED from the token if the client omitted them. This is convenience for logged-in clients that already have the data server-side.

## Side effects

- **`customer_my_orders` immediately benefits** — orders placed via the upgraded endpoint with a token attached will appear in the customer's dashboard. No change needed in `customer_my_orders` itself; it already reads `customer_phone`/`customer_address` with null-coalesce.
- **`customer_backfill_orders`** (runs on `customer_register`) will now have a smaller backfill scope, since fewer orders will be missing `customer_id` going forward. Existing logic still handles legacy orders correctly.
- **No theme code changes.** Themes already send these fields per their D-series ports. They were just being silently dropped.

## Testing approach

### PHP unit tests

Extend `tests/customer_account_test.php` with 7 new assertions covering the upgraded handler:

1. **Token-derived customer_id** — POST `state=order` with a valid `X-Customer-Token` for customer 7; assert stored row has `customer_id = 7`.
2. **Address concatenation** — POST with `addr_street`, `addr_city`, `addr_postal`, `addr_country`; assert stored `customer_address` matches `"street, city, postal, country"`.
3. **Phone persistence** — POST with `customer_phone`; assert stored `customer_phone` matches.
4. **Body customer_id ignored** — POST with body `customer_id: 999` and NO token; assert stored `customer_id` is NULL.
5. **Token wins over body claim** — POST with body `customer_id: 999` AND valid token for customer 7; assert stored `customer_id = 7`.
6. **No address fields** — POST with no address fragments; assert stored `customer_address` is empty string (no crash).
7. **Migration idempotence** — Pre-create an `orders` table with only the legacy columns; POST `state=order` twice; assert the second call doesn't spam `build/error-file.log` (file size unchanged between calls 2 and 3).

The tests use the existing `eq()` helper pattern from `tests/customer_account_test.php`.

### Browser smoke (manual / optional)

Two already-shipped themes get a quick verification once the backend ships:

1. **oaklyn** — login → add to cart → checkout (fills address fields) → check orders table directly via `php services/sys.database.php` or similar to confirm `customer_id`, `customer_phone`, `customer_address` are populated.
2. **ourchieve** — same flow on a different theme to confirm cross-theme parity.

These smokes are not blocking; the unit tests are the canonical verification.

## File scope

**Modified:**
- `skel/api.php` — `state=order` block replaced.
- `api/index.php` — `state=order` block replaced.
- `tests/customer_account_test.php` — 7 new assertions added.

**Untouched:**
- All theme files.
- `module/customer_auth.php`, `module/customer_account.php` — already support the new fields.
- `checkout_complete` handler in `api/index.php`.

**Created:**
- None.

## Known follow-ups (not addressed)

1. **Per-address-field columns** — `customer_address` is a single TEXT column. A later upgrade could split into individual columns for queryability.
2. **Phone normalization** — phone is stored as-sent (free-form). No E.164 conversion.
3. **`customer_backfill_orders` audit** — could log how many orders it backfills per register for monitoring. Out of scope here.

## Open questions resolved during brainstorming

- **customer_id source** → Server-derived from `X-Customer-Token` only (secure)
- **Address storage** → Single concatenated `customer_address` TEXT column (matches existing schema)
- **Apply locations** → Both `skel/api.php` AND `api/index.php` (parity across deployment contexts)
- **Migration approach** → PRAGMA check before ALTER to avoid log spam
