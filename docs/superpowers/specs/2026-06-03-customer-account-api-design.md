# Customer-Side API Expansion — Design (Sub-project B)

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co
**Parent decomposition:** Second of four sub-projects from the broader
"themes become Shopify-style online stores" initiative. Builds directly on
sub-project A (customer auth backend). Remaining sub-projects:

- C. Theme audit + storefront baseline
- D. Wire theme dashboards to the real auth + API

## Summary

Add the storefront-side account API endpoints that an authenticated customer
needs to manage their own data: list their orders, update name/phone, change
password, and CRUD a reusable address book. All endpoints are gated by the
`X-Customer-Token` header introduced in sub-project A and never accept a
customer_id in the request body — the customer is resolved from the token.

## Goals

- Replace the email-leaking guest-orders pattern with an auth-gated
  `customer_my_orders` for logged-in customers (the public
  `orders?email=X` endpoint stays for guest checkout but is no longer
  the only way to see one's history).
- Provide a reusable address book in a new `customer_addresses` table so
  themes (sub-project D) and checkout can pre-fill from saved addresses
  instead of asking for the same fields every order.
- Allow profile self-service (name / phone / password) without involving
  the store owner.
- Match the auth-module pattern from sub-project A: pure-PHP module with
  `database_manager $db` as the first argument so logic is unit-tested in
  isolation; the API layer in `api/index.php` is a thin JSON wrapper.

## Non-goals

- No email change. Email is the identity; changing it touches uniqueness +
  re-verification and is deferred until email verification ships.
- No deprecation of the existing `orders?email=X` endpoint. It stays for
  guest-checkout lookup; clients can migrate to `customer_my_orders` when
  they want auth-gated history.
- No pagination on `my_orders` — capped at the 100 most recent orders to
  bound payload size.
- No ISO country code or postal code format validation. `country` is
  free-text; future tax/shipping work can layer validation on top.
- No phone format validation.
- No "log out all other sessions" UI beyond the implicit one done by
  `customer_change_password`.
- No 2FA / one-time codes.
- No address-level sharing across customers.
- No SDK updates for `api/sdk/vm-store.js` — sub-project D will add SDK
  methods when wiring themes.

## User flow

A logged-in storefront customer (sub-project D theme code, or a curl test
during this sub-project) performs operations against the new endpoints.
Every request carries:

- `Authorization: Bearer <store_api_key>` — existing public store key.
- `X-Customer-Token: <customer_token>` — issued by sub-project A's
  register/login endpoints, stored in `localStorage` under
  `vm_customer_token`.

The API layer resolves the customer via `customer_resolve_token($db, $token)`
on every request. If null, the endpoint returns 401 and the client clears
its stored token.

Eight operations are added (see [Endpoint surface](#endpoint-surface)).

## Component split

**New files:**

- `module/customer_account.php` — Pure-PHP module exposing all post-login
  account operations. Mirrors `module/customer_auth.php` from sub-project A.
  Every public function takes `database_manager $db` and the resolved
  `int $customerId` as the first two arguments. Internal helpers handle
  address normalization, default-flag bookkeeping, and the
  promote-oldest-on-default-delete invariant.

- `tests/customer_account_test.php` — Standalone PHP test runner using the
  same `eq()` helper as `tests/customer_auth_test.php` and
  `tests/shopify_csv_parser_test.php`. Boots an isolated SQLite under
  `tests/tmp/`, applies the schema (Task-1 fragments plus the new
  `customer_addresses` table), registers a customer via the existing
  `customer_register` to get a real customer id, then exercises every
  public function with ~40 assertions.

**Modified files:**

- `services/database.install.php` — Append one more `CREATE TABLE IF NOT
  EXISTS` for `customer_addresses` plus its index, alongside the
  sub-project A schema fragments already there.

- `api/index.php` — Add 8 new `state=customer_*` branches inside the
  existing GET and POST routing blocks. Each branch:
  1. Reads `X-Customer-Token` via the existing `extract_customer_token()`
     helper.
  2. Resolves the token via `customer_resolve_token($db, $token)`.
  3. On null → return 401 and exit.
  4. Reads any body params (POST only, via the existing `$input` decode).
  5. Delegates to the corresponding module function.
  6. Sets the appropriate HTTP status code on failure.
  7. Echoes `json_encode($result)` and exits.

**No changes** to `vm-admin/`, `themes/`, `pages/`, `skel/`, or
`module/customer_auth.php`. Sub-project A is treated as a stable foundation.

## Data model

One new table; existing `customers` and `orders` are not modified.

```sql
CREATE TABLE IF NOT EXISTS customer_addresses (
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
);
CREATE INDEX IF NOT EXISTS idx_addresses_customer ON customer_addresses(customer_id);
```

`ON DELETE CASCADE` is real because sub-project A's commit `9b63d39` turned
on `PRAGMA foreign_keys = ON` for every connection. When a customer is
deleted, their addresses are removed too.

### Default-address invariant

At most one address per customer has `is_default = 1`. The invariant is
held by the module rather than by a CHECK constraint (SQLite handles
correlated-subselect CHECK awkwardly):

- **On create**: if `is_default` is true OR this is the customer's first
  address, clear the previous default (`UPDATE ... SET is_default = 0
  WHERE customer_id = ? AND id != newly-inserted`) and set the new row's
  flag.
- **On update**: if the update sets `is_default = true` AND the row was
  not already default, clear the previous default first, then set the
  target.
- **On delete**: if the deleted row was the default AND any other rows
  remain, promote the oldest (lowest id) remaining row to default.
- **On set_default**: if the row was not already default, clear the
  previous default and set the target. Idempotent if already default.

All four code paths run inside `BEGIN TRANSACTION` / `COMMIT` so a crash
mid-flip cannot leave two defaults.

## Endpoint surface

All eight endpoints share these properties:

- `Authorization: Bearer <store_api_key>` required (existing check at
  `api/index.php` lines 121-146 still gates).
- `X-Customer-Token: <customer_token>` required; resolved via
  `customer_resolve_token($db, $token)`. On null → 401
  `{"ok":false,"error":"Invalid or expired token"}` and exit.
- Response body shape: `{"ok": bool, "error"?: string, ...payload}`.

| Endpoint | Method | Body | Success response |
|---|---|---|---|
| `customer_my_orders` | GET | — | `{ok:true, orders:[...]}` ordered by `created_at DESC`, capped at 100. |
| `customer_update_profile` | POST | `{name?, phone?}` | `{ok:true, customer:{...}}` (public view). |
| `customer_change_password` | POST | `{current_password, new_password}` | `{ok:true, customer:{...}, token, expires_at}`. All prior sessions deleted; a fresh session is issued for this request. |
| `customer_addresses` | GET | — | `{ok:true, addresses:[...]}` ordered by `is_default DESC, id ASC`. |
| `customer_address_create` | POST | `{recipient_name, line1, city, country, line2?, region?, postal_code?, phone?, label?, is_default?}` | `{ok:true, address:{...}}`. |
| `customer_address_update` | POST | `{id, ...same fields}` | `{ok:true, address:{...}}`. |
| `customer_address_delete` | POST | `{id}` | `{ok:true}`. |
| `customer_address_set_default` | POST | `{id}` | `{ok:true, address:{...}}`. Idempotent. |

### `customer_my_orders` response shape (per order)

```json
{
  "id": 42,
  "customer_name": "Alice",
  "customer_email": "alice@x.com",
  "customer_phone": "0123",
  "customer_address": "12 Example St, ...",
  "total_amount": 199.95,
  "items": [{ "product_id": 1, "name": "...", "price": 19.99, "quantity": 1 }],
  "status": "pending",
  "created_at": "2026-06-03 10:00:00"
}
```

`items` is JSON-decoded from the `orders.items` TEXT column. Fields like
`customer_phone` and `customer_address` are present when the column exists
on the row (some legacy orders may not have them); missing values are
returned as `null`.

### Profile / address public view

The module returns address rows with their database fields plus
`is_default` cast to `bool`:

```json
{
  "id": 7,
  "label": "Home",
  "recipient_name": "Alice",
  "line1": "12 Example St",
  "line2": null,
  "city": "Cape Town",
  "region": null,
  "postal_code": "8001",
  "country": "South Africa",
  "phone": "0123",
  "is_default": true,
  "created_at": "2026-06-03 11:00:00"
}
```

`customer_id` is NOT returned in the address payload — it's an internal
foreign key, not useful to the client, and including it would leak the
customer's internal id beyond strictly needed.

## Module function signatures

```php
customer_my_orders(database_manager $db, int $customerId): array
customer_update_profile(database_manager $db, int $customerId, ?string $name, ?string $phone): array
customer_change_password(database_manager $db, int $customerId, string $currentPassword, string $newPassword, ?string $userAgent): array
customer_addresses_list(database_manager $db, int $customerId): array
customer_address_create(database_manager $db, int $customerId, array $fields): array
customer_address_update(database_manager $db, int $customerId, int $addressId, array $fields): array
customer_address_delete(database_manager $db, int $customerId, int $addressId): array
customer_address_set_default(database_manager $db, int $customerId, int $addressId): array
```

Internal helpers (not in the API surface):

- `customer_address_normalize(array $fields, bool $isCreate): array` —
  trims each field, validates required-for-create fields
  (`recipient_name`, `line1`, `city`, `country`), returns
  `['ok' => true, 'data' => array]` or `['ok' => false, 'error' => '...']`.
- `customer_address_clear_default(database_manager $db, int $customerId, int $exceptAddressId): void`
  — sets `is_default = 0` for the customer's other addresses.
- `customer_address_promote_oldest(database_manager $db, int $customerId): void`
  — selects the lowest-id address for the customer and sets
  `is_default = 1`. No-op if no rows remain.
- `customer_address_public_view(array $row): array` — strips `customer_id`,
  casts `is_default` to bool, returns canonical address shape.

`customer_change_password` reuses `customer_create_session` from
`module/customer_auth.php`. The two modules share that function via
`require_once 'customer_auth.php'` at the top of `customer_account.php`.

## Failure modes & guards

| Scenario | Behavior |
|---|---|
| Missing/invalid `X-Customer-Token` | 401 `{"ok":false,"error":"Invalid or expired token"}`. Token returned as null by `customer_resolve_token`. |
| `update_profile` with both name and phone null/empty | 400 `"At least one of name or phone must be provided"`. |
| `update_profile` with name = empty string | Treated as "do not change name" (so an UPDATE only sets the provided fields). |
| `change_password` with wrong current password | 401 `"Current password is incorrect"`. Does NOT touch `failed_login_attempts` (already-authenticated user, not a login). |
| `change_password` with new password < 8 chars | 400 `"Password must be at least 8 characters"`. |
| `change_password` happy path | (1) `DELETE FROM customer_sessions WHERE customer_id = ?`, (2) `UPDATE customers SET password_hash = ? WHERE id = ?`, (3) `customer_create_session(...)` for this request, (4) return new `{customer, token, expires_at}`. Old token is dead immediately. |
| `address_create` missing any required field (`recipient_name`, `line1`, `city`, `country`) | 400 `"<field> is required"` for the first missing field. |
| `address_create` first address for the customer | Automatically `is_default = 1` regardless of request flag. |
| `address_create` with `is_default = true` and existing addresses | New row becomes default; previous default flipped to `is_default = 0`. Transaction wraps both writes. |
| `address_update` with id not owned by customer | 404 `"Address not found"`. Same response as nonexistent id; no enumeration. |
| `address_update` setting `is_default = true` on a non-default row | Previous default flipped to 0, target set to 1, in transaction. |
| `address_update` of the only field — e.g. just `phone` | Allowed; updates only provided keys. |
| `address_delete` of id not owned by customer | 404 `"Address not found"`. |
| `address_delete` of the only address | Allowed. No promotion; customer has zero addresses afterward. |
| `address_delete` of current default with others present | Row deleted, then `customer_address_promote_oldest` sets the lowest remaining id as default. Both in transaction. |
| `address_set_default` on already-default address | Idempotent — clears (no-op), sets (no-op), returns `{ok:true, address}`. |
| Any DB write fails inside a transaction | `database_manager::query()` swallows the `PDOException` and returns `[]`. Transactions still COMMIT cleanly because no exception bubbles. This is the same limitation called out in sub-project A; data integrity holds because each multi-row operation is followed by a re-SELECT to detect a missing row (where applicable). |

## Security notes

- **Customer id is never accepted from the request body.** Every endpoint
  computes `$customerId` from `customer_resolve_token($db, $token)` after
  resolving the bearer. A malicious client cannot pass `customer_id=99`
  to read another customer's data.
- **Ownership checks on every mutation.** `customer_addresses` queries
  always include `WHERE customer_id = ?`. A request for someone else's
  address id returns 404, matching the response for a truly nonexistent
  id. No enumeration channel.
- **Password change kills all sessions.** Any token issued before the
  change becomes invalid immediately. Real-world threat: if an attacker
  has stolen a token, the legitimate owner can revoke it by changing
  their password, even if they can't see active sessions explicitly.
- **No new email verification logic.** Profile updates don't touch the
  `email` column.
- **Generic error for "address not owned" vs "address doesn't exist".**
  Avoids leaking whether an id exists for some other customer.
- **No CSRF concern** — same reasoning as sub-project A. Customer token
  is in a custom header (`X-Customer-Token`), not a cookie; browser
  cross-origin POSTs cannot set custom headers without CORS preflight,
  which is gated by `Access-Control-Allow-Headers`.

## Testing

`tests/customer_account_test.php` — standalone PHP runner, exit 0 on
pass and 1 on fail. Uses an `eq()` helper identical to
`customer_auth_test.php`. The runner:

1. Creates a tmp DB at `tests/tmp/customer_account_<pid>.sqlite`.
2. Boots `database_manager` against it and runs the relevant schema
   statements inline (the three sub-project A statements plus the new
   `customer_addresses` table).
3. Registers a customer via `customer_register` from `customer_auth.php`
   to get a real `customer_id` and a working session token.
4. Asserts:
   - `my_orders` with no orders returns `[]`.
   - Insert two orders manually — one with the customer's `customer_id`,
     one without (NULL). `my_orders` returns only the linked one.
   - `update_profile` rejects when both name and phone are null/empty.
   - `update_profile` with name only updates name and leaves phone
     unchanged.
   - `update_profile` with phone only updates phone.
   - `update_profile` with both updates both.
   - `change_password` with wrong current password returns the 401-style
     error.
   - `change_password` with too-short new password returns the validation
     error.
   - `change_password` happy path: returns a new token; the OLD token no
     longer resolves via `customer_resolve_token`; the new token resolves
     and returns the same customer.
   - First `address_create` becomes default automatically even when the
     request doesn't ask for it.
   - Second `address_create` without `is_default` does NOT flip the
     default.
   - Second `address_create` with `is_default = true` flips the default;
     the prior default row's flag is now 0.
   - `address_create` missing `recipient_name` returns 400 with the field
     name in the error.
   - `address_update` of someone else's address id returns 404 (the test
     registers a second customer to make this real).
   - `address_update` setting `is_default = true` swaps the default.
   - `address_set_default` is idempotent: calling it twice on the same id
     keeps that id as default.
   - `address_delete` of the current default with others present
     auto-promotes the oldest remaining (lowest id).
   - `address_delete` of the last address leaves zero rows.
   - `addresses_list` ordering: default first, then by id ascending.
5. Tears down the tmp DB on exit.

Targeting ~40 assertions, all green, exit 0.

API endpoints are smoke-tested manually via curl after implementation —
the helper functions carry all the logic, and the endpoints are thin
JSON wrappers, just like sub-project A.

## Open questions

None. Approved in brainstorming on 2026-06-03.
