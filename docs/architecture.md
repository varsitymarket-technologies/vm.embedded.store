# Architecture

A high-level tour of how a request enters the engine, where storage
lives, and how the storefront and admin sides fit together.

## Request flow

`index.php` is the single front controller. Every request hits it.

```text
HTTP request → index.php → ex() parses URL segments → route to:

  /home              → pages/page.home.php (storefront wizard / dashboard)
  /auth              → pages/page.auth.php
  /payments          → pages/page.payments.php
  /theme             → pages/page.theme.php (public theme preview)
  /export-frame      → services/export.frame.php
  /export-link       → services/export.link.php
  /export-source     → services/export.source.php

  /apk               → apk/index.php
  /app               → app/index.php           (PWA client)
  /vm-admin/...      → vm-admin/index.php      (admin panel)
  /store-access/<id> → api/index.php           (public store API)
  /store-proxy       → api/proxy.php           (CORS proxy for external clients)
  /sync-github       → module/github.php
```

Inside `index.php`:

1. `session_start()` is called.
2. `.register.php` runs (database bootstrap + theme library sync from
   the remote `embedded-themes` repo).
3. `config.php` runs and defines global constants from the engine DB
   (`__ACCOUNT_INDEX__`, `__USERNAME__`, `__DOMAIN__`, `__WEBSITE_THEME__`,
   `__CURRENCY_SIGN__`, etc.).
4. `scripts.php` provides helpers (`ex()`, `initiate_*` database
   factories, `account_data()`, `website_data()`).
5. A simple top-level switch routes to the matching page or subsystem.

## Storage layers

Three independent SQLite files, each with its own purpose:

### 1. Engine DB — `build/vm.engine.sql`

The control plane. Tracks accounts, banking, auth, and the registry
of sites this installation hosts.

| Table | Purpose |
|---|---|
| `sys_account` | Operator accounts (the people who run stores). |
| `sys_banking` | Payout configuration per account. |
| `sys_auth` | Session/auth records for operator login. |
| `sys_websites` | Registry of every storefront — id, domain, owner, active theme. |

### 2. Per-site DB — `sites/<domain>/storage.data`

One per storefront. Holds everything customer-visible.

| Table | Purpose |
|---|---|
| `products` | Catalog. `category_id` FK to `categories`. |
| `categories` | Category list. |
| `sales` | Flash sale definitions. |
| `orders` | Order history. `customer_id` FK to `customers` (added in sub-project A). |
| `settings` | Store-level key/value config. |
| `customers` | Per-site customer accounts (sub-project A). |
| `customer_sessions` | Bearer-token sessions for customers (sub-project A). |
| `customer_addresses` | Customer address book (sub-project B). |

Per-site DBs are created lazily by `services/database.install.php`
when a customer wizard completes. The install script is idempotent —
re-running it on an existing DB is a no-op.

### 3. Private DB — `data/<sha256(domain)>/<domain>`

Lives outside the web root. Holds material that must never be served
to a browser directly.

| Table | Purpose |
|---|---|
| `api_keys` | Store-level API keys for the public store API. `active` flag, `last_used` timestamp. |
| `api_logs` | Per-request audit log (key, endpoint, IP, user agent). |
| `cors_domains` | Optional CORS allow-list per store. |
| `cart_sessions` | Server-side cart state keyed by `cart_id`. |
| `checkout_sessions` | Server-side checkout state keyed by `session_id`. |

If `data/` isn't writable, the engine falls back to `build/data/`.

## Theme engine

Themes are static HTML/JS that the engine serves directly to the
client. They are NOT rendered server-side.

### Theme library

The `themes/` directory is git-ignored locally. On every request,
`.register.php` syncs themes from the
[varsitymarket-technologies/embedded-themes](https://github.com/varsitymarket-technologies/embedded-themes)
repository using hash-based change detection so re-pulls are cheap.

Each theme directory:

```text
themes/<theme-name>/
├── index.html       Full HTML+CSS+JS bundle (single file, ~600-1100 lines)
├── interface       Optional alternative rendering
├── poster.png      Preview thumbnail (used by the theme picker)
└── .version_hash   Git change-detection tag
```

### Runtime: skel/

The shared runtime that every theme imports lives in `skel/`:

- **`vm.theme.js`** — `VMTheme` class. Hash-based SPA routing
  (`#shop`, `#product/123`, `#cart`, `#checkout`, `#search/q`).
  Methods: `handleRouting()`, `renderShop()`, `renderProduct()`,
  `renderCart()`, `renderCheckout()`, `bindData()`, `showToast()`.
  Reads `window.StoreConfig` (name, currency, domain, apiEndpoint).
- **`vm.api.js`** — `VMApi` class wrapping `fetch` against
  `/store-access/{id}/?state=...`. Methods: `getProducts()`,
  `getProduct(id)`, `getCategories()`, `searchProducts()`,
  `getOrders(email)`, `placeOrder()`, `getSiteInfo()`. Also exports
  `VMCart` (localStorage-backed cart).

Themes use a class-based binding pattern: an element with
`class="js-fieldname"` gets its content set to the matching field
from the data object passed to `bindData()`.

### Custom themes

A user can upload a raw HTML file via the admin's Themes page. The
file lands at `sites/<domain>/builder.cache.html` and the theme
marker is set to `__custom__`. The Page Builder edits it in place.

## Admin panel

`/vm-admin/<domain>/<page>` — the per-store admin.

- `vm-admin/index.php` runs `session_start()` and includes
  `interface.php`.
- `interface.php` opens an output buffer, renders the sidebar +
  header chrome, then includes `routes.php` which dispatches to
  `routes/page.<name>.php`.
- Auth + per-store ownership check live in `routes.php`. A logged-in
  operator can only see admin pages for sites they own.

Routes: `home`, `auth`, `payments`, `theme`, `products`, `categories`,
`users` (customers), `discounts`, `sales`, `delivery`, `logistics`,
`orders`, `builder`, `settings`, `analytics`, `deploy`, `forms`.

A subtlety worth knowing: `interface.php` calls `ob_start()` at the
top of every admin page render. Pages that emit JSON (e.g., the
Shopify import handlers) must call `while (ob_get_level() > 0) {
ob_end_clean(); }` before `echo` to discard the buffered HTML.

## Public store API

`/store-access/{store_id}/?state={endpoint}` — the headless surface
that themes and external clients use.

- Entry point: `api/index.php`, included from the front controller
  when the URL starts with `store-access`.
- Auth gate: `Authorization: Bearer <store_api_key>` (or
  `X-API-Key`, or `?api_key=` query param). Key validated against
  `api_keys` in the private DB.
- Per-request audit log written to `api_logs`.
- Customer endpoints additionally check `X-Customer-Token` via
  `customer_resolve_token()`.

See [api.md](api.md) for the endpoint reference.

## Module layer

Reusable PHP under `module/`:

| File | Responsibility |
|---|---|
| `database.php` | `database_manager` — PDO + SQLite wrapper with `query($sql, $params): array`. Catches `PDOException` and returns `[]`. Enables `PRAGMA foreign_keys = ON` on every connection. |
| `customer_auth.php` | Per-site customer accounts: register, login, logout, resolve token. 30-day sliding bearer-token sessions. Per-account lockout. |
| `customer_account.php` | Post-login operations: my_orders, update_profile, change_password (kills all sessions), address book CRUD with default-flag invariant. |
| `shopify_csv_parser.php` | Pure function `parse_shopify_csv($path)` — groups Shopify CSV rows by Handle, emits one product per variant. |
| `github.php` | GitHub OAuth + deploy integration. |
| `vm.github.php` | Higher-level GitHub helpers used by the deploy admin page. |

Each module is designed to take its dependencies as arguments (most
take `database_manager $db` as the first parameter), which makes them
unit-testable against an isolated SQLite file. See [tests/](../tests/)
for examples.

## Where to read next

- [Quick start](quickstart.md) — get a store running.
- [API reference](api.md) — call the storefront API.
- [Admin features](admin-features.md) — what the admin can do.
