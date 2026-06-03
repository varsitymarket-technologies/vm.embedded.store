# Public store API

Every storefront client talks to the same endpoint:

```
POST/GET http://<host>/store-access/<store_id>/?state=<endpoint>
```

`<store_id>` is the numeric id from `sys_websites`. Find it via the
admin or by querying the engine DB.

## Auth

### Store-level API key (required on every request)

Send one of:

```
Authorization: Bearer <store_api_key>
X-API-Key: <store_api_key>
?api_key=<store_api_key>
```

The key is validated against `api_keys` in the per-store private DB.
Keys are created in the admin under Settings → Developer.

Missing key → `HTTP 401 {"error":"API key required"}`.
Invalid key → `HTTP 403 {"error":"Invalid or revoked API key"}`.

### Customer token (required on per-customer endpoints)

After a successful `customer_register` or `customer_login`, the
client stores the returned `token` (64-char hex) and sends it on
subsequent customer requests:

```
X-Customer-Token: <customer_token>
```

The token has a 30-day sliding expiry — every successful resolve
extends it. Server-side revocation: `customer_logout` deletes the
session row, and `customer_change_password` deletes all sessions
for the account before issuing a new one.

Missing/expired token → `HTTP 401 {"ok":false,"error":"Invalid or expired token"}`.

## Response envelope

All endpoints return JSON with `ok` indicating success/failure:

```json
{ "ok": true,  ...payload }
{ "ok": false, "error": "...", "code"?: "locked" }
```

The optional `code` field appears on responses where the API layer
keys behavior off it (e.g., `code: "locked"` triggers the 429 status
on `customer_login` lockout).

## HTTP status codes

| Code | When |
|---|---|
| 200 | Success. |
| 400 | Validation failure (missing field, malformed body, etc.). |
| 401 | Missing/invalid auth (store key OR customer token) or wrong credentials. |
| 403 | Store API key recognized but disabled. |
| 404 | Resource not found (also returned for cross-customer resource access — no enumeration). |
| 429 | Account temporarily locked. |
| 500 | Engine failure. |

## Endpoint reference

### Storefront (no customer auth required)

| Method | `state=` | Body | Returns |
|---|---|---|---|
| GET | `products` | `?page=N&limit=N` | Paginated catalog. |
| GET | `product` | `?id=N` | Single product. |
| GET | `categories` | — | All categories. |
| GET | `products_by_category` | `?category_id=N&page&limit` | Filtered + paginated. |
| GET | `search` | `?q=...` | LIKE search on name/description. |
| GET | `discounts` | — | Active discount codes. |
| GET | `site` | — | Store name, currency, domain. |
| GET | `orders` | `?email=...` | **Public** guest-order lookup by email. Kept for backwards compatibility; logged-in customers should use `customer_my_orders`. |
| GET | `cart` | `?cart_id=...` | Cart contents. |
| GET | `checkout` | `?session_id=...` | Server-rendered HTML checkout page. |
| POST | `order` | `{name, email, total, items[]}` | Place a guest order. |
| POST | `cart_create` | — | Create a server-side cart, returns `cart_id`. |
| POST | `cart_add` | `{cart_id, product_id, quantity}` | Add a line. |
| POST | `cart_update` | `{cart_id, product_id, quantity}` | Update line quantity. |
| POST | `cart_remove` | `{cart_id, product_id}` | Remove a line. |
| POST | `checkout_create` | `{cart_id, total_amount}` | Create a checkout session. |
| POST | `checkout_complete` | `{session_id, customer_name, customer_email, customer_phone, customer_address}` | Finalize an order. |

### Customer auth (sub-project A)

All four endpoints require the store API key. The customer endpoints
that need an authenticated user additionally require `X-Customer-Token`.

| Method | `state=` | Body | Returns | Notes |
|---|---|---|---|---|
| POST | `customer_register` | `{email, password, name?, phone?}` | `{ok, customer, token, expires_at}` | Auto-backfills any pre-existing guest orders whose email matches. |
| POST | `customer_login` | `{email, password}` | `{ok, customer, token, expires_at}` | 5 wrong passwords → 15-min lockout (429 with `"code":"locked"`). |
| POST | `customer_logout` | — | `{ok:true}` | Sends `X-Customer-Token`. Idempotent — unknown tokens still return 200. |
| GET | `customer_me` | — | `{ok, customer}` | Sends `X-Customer-Token`. Slides expiry on every call. |

Validation:

- Email must pass `filter_var(..., FILTER_VALIDATE_EMAIL)`.
- Password minimum 8 characters.
- Email lookup is case-insensitive (`COLLATE NOCASE` on the column).

Spec: [docs/superpowers/specs/2026-06-03-customer-auth-backend-design.md](superpowers/specs/2026-06-03-customer-auth-backend-design.md).

### Customer account (sub-project B)

All eight require both `Authorization: Bearer <store_key>` AND
`X-Customer-Token: <customer_token>`. Customer id is always resolved
from the token — never trusted from the body.

| Method | `state=` | Body | Returns | Notes |
|---|---|---|---|---|
| GET | `customer_my_orders` | — | `{ok, orders:[...]}` | Up to 100 most recent. Items field is JSON-decoded. |
| POST | `customer_update_profile` | `{name?, phone?}` | `{ok, customer}` | Rejects empty body with 400 ("At least one of name or phone..."). Email never touched. |
| POST | `customer_change_password` | `{current_password, new_password}` | `{ok, customer, token, expires_at}` | Wrong current → 401. Short new → 400. Success deletes ALL sessions and returns a fresh token. |
| GET | `customer_addresses` | — | `{ok, addresses:[...]}` | Default first, then by id ascending. |
| POST | `customer_address_create` | `{recipient_name, line1, city, country, line2?, region?, postal_code?, phone?, label?, is_default?}` | `{ok, address}` | First address auto-defaults. `is_default:true` flips the existing default. |
| POST | `customer_address_update` | `{id, ...same fields}` | `{ok, address}` | 404 for unknown/cross-customer id. Updates only the keys in the body. |
| POST | `customer_address_delete` | `{id}` | `{ok}` | Deleting the current default auto-promotes the oldest remaining address. |
| POST | `customer_address_set_default` | `{id}` | `{ok, address}` | Idempotent. |

Spec: [docs/superpowers/specs/2026-06-03-customer-account-api-design.md](superpowers/specs/2026-06-03-customer-account-api-design.md).

## Example: full account flow

```bash
KEY=vm_live_...your_store_api_key...
BASE=http://localhost:8016/store-access/1

# 1. Register
REG=$(curl -s -X POST "$BASE/?state=customer_register" \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@x.com","password":"hunter22!","name":"Alice"}')
TOKEN=$(echo "$REG" | jq -r .token)

# 2. Add an address
curl -s -X POST "$BASE/?state=customer_address_create" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"recipient_name":"Alice","line1":"12 Example St","city":"Cape Town","country":"South Africa"}'

# 3. Fetch profile
curl -s "$BASE/?state=customer_me" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"

# 4. Change password — old token dies
curl -s -X POST "$BASE/?state=customer_change_password" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"hunter22!","new_password":"newpassword1"}'

# 5. Logout
curl -s -X POST "$BASE/?state=customer_logout" \
  -H "Authorization: Bearer $KEY" \
  -H "X-Customer-Token: $TOKEN"
```

## SDK serving (no auth)

The JS SDK file is served from `/store-access/<id>/sdk/vm-store.js`
without the API key check. Themes and external sites embed it via:

```html
<script src="https://your-domain.com/store-access/1/sdk/vm-store.js"></script>
```

The SDK reads the store id from its own `<script src>` URL.

## Where to read next

- [Architecture](architecture.md) — how requests get to this layer.
- [Quick start](quickstart.md) — local Docker setup.
- [Admin features](admin-features.md) — how to provision the API
  keys customers will use.
