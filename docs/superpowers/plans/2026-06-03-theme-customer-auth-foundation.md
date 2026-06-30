# Theme Customer Auth Foundation Implementation Plan (Sub-project D.1)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the seven customer auth endpoints reachable from existing theme fetch calls by extending `skel/api.php`, and ship a vanilla-JS drop-in helper (`skel/vm-customer.js`) that themes can adopt with a five-line snippet.

**Architecture:** Mirror sub-projects A + B. `skel/api.php` gets seven new `state=` branches that delegate to `module/customer_auth.php` and `module/customer_account.php` — no business logic is duplicated. The new JS helper owns token storage + `X-Customer-Token` header + DOM events + 401 auto-logout, so themes never touch localStorage or the header directly. Existing per-site `api.php` copies under `sites/*/api.php` get the new version synced from `skel/api.php`.

**Tech Stack:** PHP 7.4+ via existing `database_manager`, vanilla browser JS (no framework, no bundler). No new PHP or JS dependencies.

**Spec:** [docs/superpowers/specs/2026-06-03-theme-customer-auth-foundation-design.md](../specs/2026-06-03-theme-customer-auth-foundation-design.md)

---

## File Map

**Create:**

- `skel/vm-customer.js` — Vanilla JS drop-in helper, ~150 lines, IIFE exposing `window.vmCustomer`. Owns token storage, the `X-Customer-Token` header, DOM events (`vm:customer-loaded` / `-login` / `-logout`), and 401 auto-logout.
- `tests/vm-customer-harness.html` — Static HTML test page that loads `vm-customer.js` and runs register/login/logout/me/myOrders/updateProfile/changePassword through the helper, rendering green/red rows. Manual browser verification.
- `docs/theme-integration.md` — One-page guide for sub-projects D.2+ showing the five-line `<head>` snippet, the `data-customer` CSS pattern, and example login/profile form handlers.

**Modify:**

- `skel/api.php` — Append seven new `state=` branches (2 GET, 5 POST), an `extract_customer_token()` helper, an include for the customer auth + account modules, and three idempotent `createTable`/ALTER calls for `customers`, `customer_sessions`, and `orders.customer_id`.

**Sync (existing files updated from skel/):**

- `sites/<domain>/api.php` for every existing site under `sites/`. Each gets `cp -f skel/api.php sites/<domain>/api.php`. This is the deploy-time copy pattern; for D.1 we do it manually since the project has no automated sync hook.

**No changes** to:
- `module/customer_auth.php`, `module/customer_account.php` (stable foundation from A + B)
- `api/index.php` (the public store API; D.1 does not consolidate the two entry points)
- `themes/` (no theme touched; D.2+ does that work)
- `skel/vm.api.js`, `skel/vm.theme.js` (parallel files; `vm-customer.js` is separate)

---

## Notes for the implementer

- The container name is `vm-emb-sites`; project root mounted at `/var/www/html/public/`.
- Existing sites in `sites/` include `claude.test`, `debug.com`, `prestigeauto.co.za`, `reiddrop.com`, `something.less`. Each already has its own `api.php` copied from `skel/`.
- For the HTTP smoke test, the storefront-side `api.php` URL is `http://localhost:8016/sites/<domain>/api.php?state=...`. This was confirmed via curl during plan authoring on `sites/debug.com/api.php` (HTTP 200).
- The customer_auth + customer_account modules require schema (customers, customer_sessions, customer_addresses). Sub-project B's task plan covered applying the schema to existing site DBs via `services/database.install.php`. `debug.com` already had it applied during B's smoke.
- `database_manager::query()` catches `PDOException` and returns `[]`. The new branches inherit this behavior; failure cases follow the same SELECT-before-INSERT pattern documented in the modules.

---

## Task 1: Extend `skel/api.php` with customer auth endpoints

**Files:**
- Modify: `skel/api.php`
- Sync: `sites/<domain>/api.php` for every existing site

### Step 1.1: Add module includes + customer-token helper at the top

- [ ] **Step 1.1: Edit `skel/api.php`**

Find the existing includes block near the top (around lines 19-21):

```php
@include_once dirname(dirname(dirname(__FILE__))). "/scripts.php";
$db = __DB_MODULE__;
$db->override_connection(dirname(__FILE__).'/storage.data');
```

Immediately after this block (before the `// Ensure required tables exist` comment), add:

```php

// --- Customer auth module includes (sub-project D.1) ---
@include_once dirname(dirname(__FILE__)) . "/module/customer_auth.php";
@include_once dirname(dirname(__FILE__)) . "/module/customer_account.php";

// --- Customer token extraction (mirrors api/index.php helper) ---
if (!function_exists('extract_customer_token')) {
    function extract_customer_token(): ?string {
        $tok = $_SERVER['HTTP_X_CUSTOMER_TOKEN'] ?? '';
        $tok = is_string($tok) ? trim($tok) : '';
        return $tok === '' ? null : $tok;
    }
}
```

The path is `dirname(dirname(__FILE__))` because `skel/` lives one level below the project root, and a per-site `sites/<domain>/` copy of the file is ALSO one level below the project root, so the same `dirname(dirname(__FILE__))` resolves to the project root in both cases. Confirm by tracing: from `/var/www/html/public/skel/api.php`, `dirname` once → `/var/www/html/public/skel`, twice → `/var/www/html/public`. From `/var/www/html/public/sites/debug.com/api.php`, once → `/var/www/html/public/sites/debug.com`, twice → `/var/www/html/public/sites`. **That second case is wrong** — the project root is one more level up.

**Use a more robust path:** since this file gets copied to multiple depths, prefer locating the module by walking up until we find it:

```php
// --- Customer auth module includes (sub-project D.1) ---
$_vm_module_dir = null;
foreach ([dirname(__FILE__), dirname(dirname(__FILE__)), dirname(dirname(dirname(__FILE__)))] as $_vm_candidate) {
    if (is_dir($_vm_candidate . '/module') && file_exists($_vm_candidate . '/module/customer_auth.php')) {
        $_vm_module_dir = $_vm_candidate . '/module';
        break;
    }
}
if ($_vm_module_dir !== null) {
    @include_once $_vm_module_dir . '/customer_auth.php';
    @include_once $_vm_module_dir . '/customer_account.php';
}

// --- Customer token extraction (mirrors api/index.php helper) ---
if (!function_exists('extract_customer_token')) {
    function extract_customer_token(): ?string {
        $tok = $_SERVER['HTTP_X_CUSTOMER_TOKEN'] ?? '';
        $tok = is_string($tok) ? trim($tok) : '';
        return $tok === '' ? null : $tok;
    }
}
```

This works whether the file is at `skel/api.php`, `sites/<domain>/api.php`, or any other depth.

### Step 1.2: Add schema bootstrap for the new tables

- [ ] **Step 1.2: Edit `skel/api.php` — bootstrap block**

Find the existing `$db->createTable("orders", ...)` block (around lines 31-39). Immediately after the `createTable("orders", ...)` call (and before the `$db->query("INSERT INTO page_views ...")` line), append:

```php

// --- Customer auth schema (sub-project D.1) ---
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
$db->query("CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email COLLATE NOCASE)");
$db->query("CREATE TABLE IF NOT EXISTS customer_sessions (
    token TEXT PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    user_agent TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
)");
$db->query("CREATE INDEX IF NOT EXISTS idx_sessions_customer ON customer_sessions(customer_id)");

// Idempotently add customer_id to orders (same PRAGMA guard as services/database.install.php)
$order_cols = $db->query("PRAGMA table_info(orders)");
$has_customer_id = false;
foreach ($order_cols as $col) {
    if (($col['name'] ?? '') === 'customer_id') { $has_customer_id = true; break; }
}
if (!$has_customer_id) {
    $db->query("ALTER TABLE orders ADD COLUMN customer_id INTEGER REFERENCES customers(id)");
}
$db->query("CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id)");
```

This mirrors what `services/database.install.php` does for engine-managed per-site DBs, but inlined into `api.php` so that any storefront deployment self-bootstraps its customer tables on the first API request.

### Step 1.3: Add the two new GET branches

- [ ] **Step 1.3: Edit `skel/api.php` — GET block**

Find the existing `elseif ($request == "orders")` branch (around lines 128-141), then the `else` fallback (around lines 143-145) that lists endpoints. Insert the two new branches **immediately before** the `else` fallback:

```php

    // --- Customer account: GET customer_me (sub-project D.1) ---
    elseif ($request == "customer_me") {
        if (!function_exists('customer_resolve_token')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
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

    // --- Customer account: GET customer_my_orders (sub-project D.1) ---
    elseif ($request == "customer_my_orders") {
        if (!function_exists('customer_resolve_token') || !function_exists('customer_my_orders')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
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
```

Then **update the `else` fallback's endpoints list** (around line 144) to include the new GET states:

```php
    else {
        echo json_encode(["status" => "ok", "endpoints" => ["products", "product", "categories", "products_by_category", "search", "discounts", "site", "orders", "order", "customer_me", "customer_my_orders", "customer_register", "customer_login", "customer_logout", "customer_update_profile", "customer_change_password"]]);
    }
```

### Step 1.4: Add the five new POST branches

- [ ] **Step 1.4: Edit `skel/api.php` — POST block**

Find the existing `elseif ($method === 'POST')` block (around lines 148-174). It contains only an `if ($request == "order")` branch today. Inside the POST block, **after** the `order` branch's closing `}`, append the five new branches:

```php

    // --- Customer account: POST customer_register (sub-project D.1) ---
    elseif ($request == "customer_register") {
        if (!function_exists('customer_register')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
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

    // --- Customer account: POST customer_login (sub-project D.1) ---
    elseif ($request == "customer_login") {
        if (!function_exists('customer_login')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $result = customer_login($db, $email, $password, $userAgent);
        if (!$result['ok']) {
            http_response_code(($result['code'] ?? '') === 'locked' ? 429 : 401);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_logout (sub-project D.1) ---
    elseif ($request == "customer_logout") {
        if (!function_exists('customer_logout')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        echo json_encode(customer_logout($db, $token));
        exit;
    }

    // --- Customer account: POST customer_update_profile (sub-project D.1) ---
    elseif ($request == "customer_update_profile") {
        if (!function_exists('customer_resolve_token') || !function_exists('customer_update_profile')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
        $name = $input['name'] ?? null;
        $phone = $input['phone'] ?? null;
        $result = customer_update_profile($db, (int)$customer['id'], $name, $phone);
        if (!$result['ok']) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;
    }

    // --- Customer account: POST customer_change_password (sub-project D.1) ---
    elseif ($request == "customer_change_password") {
        if (!function_exists('customer_resolve_token') || !function_exists('customer_change_password')) {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Customer auth module not loaded"]);
            exit;
        }
        $token = extract_customer_token();
        $customer = customer_resolve_token($db, $token);
        if ($customer === null) {
            http_response_code(401);
            echo json_encode(["ok" => false, "error" => "Invalid or expired token"]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { $input = []; }
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
```

Note: address book endpoints (`customer_addresses`, `customer_address_*`) are NOT added in D.1 per the spec's out-of-scope list.

### Step 1.5: Syntax check

- [ ] **Step 1.5: Syntax check**

```bash
docker exec vm-emb-sites php -l /var/www/html/public/skel/api.php
```

Expected: `No syntax errors detected in /var/www/html/public/skel/api.php`.

### Step 1.6: Propagate to existing per-site copies

- [ ] **Step 1.6: Sync `skel/api.php` to every existing `sites/<domain>/api.php`**

The per-site copies were created at deploy time and don't auto-update. Sync them now:

```bash
for site in sites/*/; do
  if [ -f "$site/api.php" ]; then
    cp -f skel/api.php "$site/api.php"
    echo "synced: $site/api.php"
  fi
done
```

Expected output: one line per existing site (`claude.test`, `debug.com`, `prestigeauto.co.za`, `reiddrop.com`, `something.less`).

This is a one-time manual sync. Future deploys will pick up the changes automatically when they copy `skel/api.php`.

### Step 1.7: HTTP smoke test against debug.com

- [ ] **Step 1.7: Smoke test the new endpoints**

The per-site `api.php` URL is `http://localhost:8016/sites/<domain>/api.php?state=...`. Use `debug.com` (sub-project B's smoke already applied the schema there; the new schema bootstrap in step 1.2 should be idempotent and add no further changes).

Pick a fresh email and run the sequence:

```bash
BASE=http://localhost:8016/sites/debug.com/api.php
EMAIL="d1-$(date +%s)@x.com"

echo "=== T1: register ==="
REG=$(curl -s -X POST "$BASE?state=customer_register" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"goodpass1\",\"name\":\"D1\"}")
echo "$REG"
TOKEN=$(echo "$REG" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["token"] ?? "";')
echo "TOKEN=$TOKEN"

echo ""
echo "=== T2: customer_me with token (expect 200) ==="
curl -s -w "  HTTP %{http_code}\n" "$BASE?state=customer_me" \
  -H "X-Customer-Token: $TOKEN"; echo

echo "=== T3: customer_me without token (expect 401) ==="
curl -s -w "  HTTP %{http_code}\n" "$BASE?state=customer_me"; echo

echo "=== T4: customer_my_orders empty (expect 200 + []) ==="
curl -s -w "  HTTP %{http_code}\n" "$BASE?state=customer_my_orders" \
  -H "X-Customer-Token: $TOKEN"; echo

echo "=== T5: customer_login wrong pw (expect 401) ==="
curl -s -w "  HTTP %{http_code}\n" -X POST "$BASE?state=customer_login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"WRONG\"}"; echo

echo "=== T6: customer_login correct (expect 200 + new token) ==="
LOGIN=$(curl -s -X POST "$BASE?state=customer_login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"goodpass1\"}")
echo "$LOGIN"

echo ""
echo "=== T7: customer_update_profile empty (expect 400) ==="
curl -s -w "  HTTP %{http_code}\n" -X POST "$BASE?state=customer_update_profile" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" -d '{}'; echo

echo "=== T8: customer_update_profile name (expect 200) ==="
curl -s -w "  HTTP %{http_code}\n" -X POST "$BASE?state=customer_update_profile" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" -d '{"name":"D1 Updated"}'; echo

echo "=== T9: customer_change_password wrong (expect 401) ==="
curl -s -w "  HTTP %{http_code}\n" -X POST "$BASE?state=customer_change_password" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"WRONG","new_password":"newpassword1"}'; echo

echo "=== T10: customer_change_password happy (expect 200 + new token) ==="
CP=$(curl -s -X POST "$BASE?state=customer_change_password" \
  -H "X-Customer-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"goodpass1","new_password":"newpassword1"}')
echo "$CP"
NEW_TOKEN=$(echo "$CP" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["token"] ?? "";')

echo ""
echo "=== T11: old token dead (expect 401) ==="
curl -s -w "  HTTP %{http_code}\n" "$BASE?state=customer_me" \
  -H "X-Customer-Token: $TOKEN"; echo

echo "=== T12: new token works (expect 200) ==="
curl -s -w "  HTTP %{http_code}\n" "$BASE?state=customer_me" \
  -H "X-Customer-Token: $NEW_TOKEN"; echo

echo "=== T13: customer_logout (expect 200) ==="
curl -s -w "  HTTP %{http_code}\n" -X POST "$BASE?state=customer_logout" \
  -H "X-Customer-Token: $NEW_TOKEN"; echo

echo "=== T14: post-logout me (expect 401) ==="
curl -s -w "  HTTP %{http_code}\n" "$BASE?state=customer_me" \
  -H "X-Customer-Token: $NEW_TOKEN"; echo
```

Expected status codes: T1 200, T2 200, T3 401, T4 200, T5 401, T6 200, T7 400, T8 200, T9 401, T10 200, T11 401, T12 200, T13 200, T14 401.

If any test returns HTML instead of JSON (look for `<` at the start of the body), an output buffer is leaking pre-content. Add `while (ob_get_level() > 0) { ob_end_clean(); }` at the top of each new branch before any `echo`. This matched the fix used in the Shopify import handlers earlier in this session.

### Step 1.8: Commit

- [ ] **Step 1.8: Commit**

```bash
git add skel/api.php sites/*/api.php
git commit -m "feat: add customer auth endpoints to skel/api.php (sub-project D.1)

Seven new state= branches added to the Micro API and synced to
every existing per-site api.php copy:
- GET customer_me, customer_my_orders
- POST customer_register, customer_login, customer_logout,
  customer_update_profile, customer_change_password

All delegate to module/customer_auth.php and
module/customer_account.php — no business logic duplication.
Schema bootstrap inlined into the api.php top so any new
deployment self-provisions its customer tables on first API
request.

Smoke-tested end-to-end against sites/debug.com/api.php: register
→ me → my_orders → wrong-login (401) → correct-login → update
profile → change-password happy → old-token-dead (401) → new-token
→ logout → post-logout 401.

Sub-project D's drop-in JS helper (skel/vm-customer.js) and theme
integration guide land in the next commits."
```

---

## Task 2: Create `skel/vm-customer.js` (drop-in JS helper)

**Files:**
- Create: `skel/vm-customer.js`

### Step 2.1: Write the helper file

- [ ] **Step 2.1: Create `skel/vm-customer.js`**

Copy this verbatim. The code is exactly the implementation sketch from the spec.

```javascript
/**
 * Varsity Market Customer Auth Helper
 * Drop-in client for the customer_* endpoints in skel/api.php.
 * Owns token storage, the X-Customer-Token header, and DOM events.
 * Version: 1.0.0 (sub-project D.1)
 *
 * Usage:
 *   <script src="vm-customer.js" defer></script>
 *
 * Themes then call vmCustomer.login(...), .register(...), .me(), etc.
 * and listen for window events: vm:customer-loaded, vm:customer-login,
 * vm:customer-logout. See docs/theme-integration.md.
 */
(function () {
    const TOKEN_KEY = 'vm_customer_token';
    const CACHE_KEY = 'vm_customer_cache';

    function resolveBase() {
        const tag = document.currentScript;
        if (tag && tag.dataset && tag.dataset.apiBase) return tag.dataset.apiBase;
        if (window.VM_CUSTOMER_API_BASE) return window.VM_CUSTOMER_API_BASE;
        return new URL('api.php', window.location.href).href;
    }
    const BASE = resolveBase();

    function emit(type, detail) {
        window.dispatchEvent(new CustomEvent(type, { detail: detail || null }));
    }

    function getToken() {
        try { return localStorage.getItem(TOKEN_KEY); } catch (e) { return null; }
    }
    function setToken(t) {
        try { localStorage.setItem(TOKEN_KEY, t); } catch (e) { /* ignore */ }
    }
    function getCached() {
        try { return JSON.parse(localStorage.getItem(CACHE_KEY) || 'null'); } catch (e) { return null; }
    }
    function setCached(c) {
        try { localStorage.setItem(CACHE_KEY, JSON.stringify(c)); } catch (e) { /* ignore */ }
    }
    function clearAll() {
        try {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(CACHE_KEY);
        } catch (e) { /* ignore */ }
    }

    async function call(state, opts) {
        opts = opts || {};
        const method = opts.method || 'GET';
        const body = opts.body || null;
        const withToken = !!opts.withToken;

        const url = BASE + (BASE.indexOf('?') === -1 ? '?' : '&')
            + 'state=' + encodeURIComponent(state);
        const headers = { 'Content-Type': 'application/json' };
        if (withToken) {
            const t = getToken();
            if (t) headers['X-Customer-Token'] = t;
        }
        const fetchOpts = { method: method, headers: headers };
        if (body) fetchOpts.body = JSON.stringify(body);

        const res = await fetch(url, fetchOpts);
        if (res.status === 401) {
            clearAll();
            emit('vm:customer-logout');
            const err = new Error('Unauthenticated');
            err.code = 'unauthenticated';
            err.status = 401;
            throw err;
        }
        const data = await res.json().catch(function () { return {}; });
        if (!data.ok) {
            const err = new Error(data.error || ('HTTP ' + res.status));
            err.status = res.status;
            err.code = data.code || null;
            throw err;
        }
        return data;
    }

    const vmCustomer = {
        isLoggedIn: function () { return !!getToken(); },
        token: function () { return getToken(); },
        cached: function () { return getCached(); },

        register: async function (email, password, name, phone) {
            const r = await call('customer_register', {
                method: 'POST',
                body: { email: email, password: password, name: name || null, phone: phone || null }
            });
            setToken(r.token);
            setCached(r.customer);
            emit('vm:customer-login', { customer: r.customer });
            return r;
        },

        login: async function (email, password) {
            const r = await call('customer_login', {
                method: 'POST',
                body: { email: email, password: password }
            });
            setToken(r.token);
            setCached(r.customer);
            emit('vm:customer-login', { customer: r.customer });
            return r;
        },

        logout: async function () {
            try {
                await call('customer_logout', { method: 'POST', withToken: true });
            } catch (e) {
                // Network or 401 during logout — still clear locally.
            }
            clearAll();
            emit('vm:customer-logout');
        },

        me: async function () {
            const r = await call('customer_me', { withToken: true });
            setCached(r.customer);
            return r;
        },

        myOrders: async function () {
            return call('customer_my_orders', { withToken: true });
        },

        updateProfile: async function (name, phone) {
            const r = await call('customer_update_profile', {
                method: 'POST',
                body: { name: name || null, phone: phone || null },
                withToken: true
            });
            setCached(r.customer);
            return r;
        },

        changePassword: async function (currentPassword, newPassword) {
            const r = await call('customer_change_password', {
                method: 'POST',
                body: { current_password: currentPassword, new_password: newPassword },
                withToken: true
            });
            // change_password kills all sessions and returns a fresh token.
            setToken(r.token);
            setCached(r.customer);
            return r;
        }
    };

    // Initial bootstrap: if a token is present, resolve it.
    function init() {
        if (!getToken()) return;
        vmCustomer.me()
            .then(function (r) { emit('vm:customer-loaded', { customer: r.customer }); })
            .catch(function () { /* auto-logout already fired via 401 path */ });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.vmCustomer = vmCustomer;
})();
```

### Step 2.2: Syntax check via node

- [ ] **Step 2.2: Node syntax check**

If Node.js is available:

```bash
node --check skel/vm-customer.js
```

Expected: no output (success).

If Node isn't available locally, run it inside the container only if the container has Node — most likely it doesn't. In that case, skip and rely on the browser harness test in Task 3 to catch syntax errors.

### Step 2.3: Commit

- [ ] **Step 2.3: Commit**

```bash
git add skel/vm-customer.js
git commit -m "feat: add vm-customer.js drop-in helper (sub-project D.1)

Vanilla JS, no dependencies. IIFE exposes window.vmCustomer with
register/login/logout/me/myOrders/updateProfile/changePassword
plus sync state queries (isLoggedIn/token/cached).

Owns localStorage.vm_customer_token + vm_customer_cache + the
X-Customer-Token header on every authed request. Auto-logout on
any 401 with vm:customer-logout dispatched on window. Initial
bootstrap fires vm:customer-loaded if a valid token resolves on
page load.

Themes adopt via a five-line head snippet documented in
docs/theme-integration.md (next commit)."
```

---

## Task 3: Create the browser test harness

**Files:**
- Create: `tests/vm-customer-harness.html`

### Step 3.1: Write the harness HTML

- [ ] **Step 3.1: Create `tests/vm-customer-harness.html`**

Copy verbatim:

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>vm-customer.js test harness</title>
<style>
    body { font-family: -apple-system, sans-serif; max-width: 760px; margin: 40px auto; padding: 0 20px; }
    h1 { font-size: 18px; margin-bottom: 4px; }
    .meta { color: #666; font-size: 12px; margin-bottom: 24px; }
    .row { padding: 8px 12px; border-radius: 6px; margin: 4px 0; font-size: 13px; font-family: ui-monospace, monospace; }
    .pass { background: #e6f7eb; color: #1f6f3a; }
    .fail { background: #fde7e7; color: #8a1f1f; }
    .info { background: #f0f0f0; color: #444; }
    button { padding: 8px 14px; background: #222; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
    input { padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
    label { display: block; margin: 8px 0 4px; color: #555; font-size: 12px; }
</style>
</head>
<body>

<h1>vm-customer.js test harness</h1>
<p class="meta">Loads <code>skel/vm-customer.js</code> from this repo and exercises every method. Manual verification — no test framework.</p>

<label>API base URL (POST/GET against this; defaults to a same-origin api.php)</label>
<input type="text" id="apiBase" value="" placeholder="e.g. http://localhost:8016/sites/debug.com/api.php" size="60">
<br><br>
<button onclick="runAll()">Run all tests</button>

<div id="results" style="margin-top: 16px;"></div>

<!-- Inline override BEFORE the vm-customer.js script -->
<script>
    // Pre-set the base from the input before vm-customer.js initializes
    (function () {
        const params = new URLSearchParams(window.location.search);
        const fromQs = params.get('api');
        if (fromQs) {
            window.VM_CUSTOMER_API_BASE = fromQs;
            document.getElementById('apiBase').value = fromQs;
        }
    })();
</script>
<script src="../skel/vm-customer.js" defer></script>

<script>
const out = document.getElementById('results');
function row(cls, msg) {
    const div = document.createElement('div');
    div.className = 'row ' + cls;
    div.textContent = msg;
    out.appendChild(div);
}
function pass(msg) { row('pass', 'PASS  ' + msg); }
function fail(msg) { row('fail', 'FAIL  ' + msg); }
function info(msg) { row('info', '----  ' + msg); }

async function runAll() {
    out.innerHTML = '';
    const inputBase = document.getElementById('apiBase').value.trim();
    if (inputBase) {
        // Override at runtime by updating the URL the helper resolves to.
        // Note: vmCustomer captured BASE at script load. To re-route, we
        // reload the page with ?api=... .
        if (window.VM_CUSTOMER_API_BASE !== inputBase) {
            window.location.search = '?api=' + encodeURIComponent(inputBase);
            return;
        }
    }

    if (typeof window.vmCustomer === 'undefined') {
        fail('vmCustomer global missing — vm-customer.js did not load');
        return;
    }

    const email = 'harness-' + Date.now() + '@x.com';
    info('Test email: ' + email);

    // 1. Initial state — not logged in
    if (vmCustomer.isLoggedIn()) {
        info('Was already logged in — clearing first');
        await vmCustomer.logout();
    }
    if (!vmCustomer.isLoggedIn()) pass('initial state: isLoggedIn=false');
    else fail('initial state: still logged in after logout');

    // 2. Register
    try {
        const r = await vmCustomer.register(email, 'harnesspass1', 'Harness', '555');
        if (r.ok && r.token && r.token.length === 64) pass('register: ok=true + 64-char token');
        else fail('register: unexpected response: ' + JSON.stringify(r));
    } catch (e) {
        fail('register threw: ' + e.message);
        return;
    }

    // 3. isLoggedIn + cached
    if (vmCustomer.isLoggedIn()) pass('after register: isLoggedIn=true');
    else fail('after register: isLoggedIn=false');
    const cached = vmCustomer.cached();
    if (cached && cached.email === email) pass('after register: cached customer matches');
    else fail('after register: cached missing or wrong: ' + JSON.stringify(cached));

    // 4. me()
    try {
        const r = await vmCustomer.me();
        if (r.ok && r.customer.email === email) pass('me: returns the registered customer');
        else fail('me: unexpected: ' + JSON.stringify(r));
    } catch (e) { fail('me threw: ' + e.message); }

    // 5. myOrders empty
    try {
        const r = await vmCustomer.myOrders();
        if (r.ok && Array.isArray(r.orders) && r.orders.length === 0) pass('myOrders: empty [] for new customer');
        else fail('myOrders: unexpected: ' + JSON.stringify(r));
    } catch (e) { fail('myOrders threw: ' + e.message); }

    // 6. updateProfile
    try {
        const r = await vmCustomer.updateProfile('Updated Name', '999');
        if (r.ok && r.customer.name === 'Updated Name' && r.customer.phone === '999') pass('updateProfile: name + phone reflected');
        else fail('updateProfile: unexpected: ' + JSON.stringify(r));
    } catch (e) { fail('updateProfile threw: ' + e.message); }

    // 7. changePassword wrong current
    try {
        await vmCustomer.changePassword('WRONG', 'newpassword2');
        fail('changePassword wrong: should have thrown');
    } catch (e) {
        if (e.status === 401) pass('changePassword wrong current: throws with status=401');
        else fail('changePassword wrong: wrong error: status=' + e.status + ' code=' + e.code);
    }

    // 8. changePassword happy + token swap
    const oldToken = vmCustomer.token();
    try {
        const r = await vmCustomer.changePassword('harnesspass1', 'newpassword2');
        if (r.ok && r.token && r.token !== oldToken && r.token.length === 64) pass('changePassword: new token issued (!= old)');
        else fail('changePassword: unexpected: ' + JSON.stringify(r));
        if (vmCustomer.token() === r.token) pass('changePassword: localStorage updated with new token');
        else fail('changePassword: localStorage did not update token');
    } catch (e) { fail('changePassword threw: ' + e.message); }

    // 9. logout
    try {
        await vmCustomer.logout();
        if (!vmCustomer.isLoggedIn() && !vmCustomer.token()) pass('logout: localStorage cleared');
        else fail('logout: state not cleared');
    } catch (e) { fail('logout threw: ' + e.message); }

    // 10. me after logout
    try {
        await vmCustomer.me();
        fail('me after logout: should have thrown');
    } catch (e) {
        if (e.code === 'unauthenticated') pass('me after logout: throws unauthenticated');
        else fail('me after logout: unexpected error: ' + e.message + ' (code=' + e.code + ')');
    }

    info('Done.');
}

// Auto-run on load if api= query param is present
window.addEventListener('vm:customer-loaded', function (e) {
    info('Event: vm:customer-loaded → ' + (e.detail && e.detail.customer ? e.detail.customer.email : '?'));
});
window.addEventListener('vm:customer-login', function (e) {
    info('Event: vm:customer-login → ' + (e.detail && e.detail.customer ? e.detail.customer.email : '?'));
});
window.addEventListener('vm:customer-logout', function () {
    info('Event: vm:customer-logout');
});
</script>

</body>
</html>
```

### Step 3.2: Manually verify in the browser

- [ ] **Step 3.2: Browser smoke test the harness**

Serve the file via the running Docker stack (it's inside `tests/`, which is mounted at `/var/www/html/public/tests/`):

```bash
echo "Open this URL in a browser:"
echo "  http://localhost:8016/tests/vm-customer-harness.html?api=http://localhost:8016/sites/debug.com/api.php"
```

In the browser:

1. Page loads, "vm-customer.js test harness" title shows.
2. The API base input is pre-filled with the `?api=...` value.
3. Click **Run all tests**.
4. Expect a long list of green `PASS` rows: initial state, register, isLoggedIn after register, cached customer match, me, myOrders empty, updateProfile, changePassword wrong rejection, changePassword happy + token swap + localStorage update, logout cleared, me after logout unauthenticated. Plus event rows for `vm:customer-login` and `vm:customer-logout`.
5. If any FAIL row appears, debug. The most common failure mode is a CORS issue — if the harness is loaded from a different origin than the API base, browsers block `X-Customer-Token`. Both should be on `localhost:8016` to avoid this.

Alternative: drive via Playwright as part of the implementation if browser interaction is more convenient than a manual click. The MCP playwright tools work for this — navigate to the URL, click `button` with text "Run all tests", read the page text and check for "FAIL".

### Step 3.3: Commit

- [ ] **Step 3.3: Commit**

```bash
git add tests/vm-customer-harness.html
git commit -m "test: add vm-customer.js browser harness (sub-project D.1)

Static HTML page that loads skel/vm-customer.js and exercises
every public method against a configurable backend URL. Renders
green/red pass/fail rows for: initial state, register, cached
customer, me, myOrders empty, updateProfile, changePassword
wrong-current rejection, changePassword happy + token swap +
localStorage update, logout cleared, me-after-logout
unauthenticated. Plus DOM event capture for vm:customer-login
and vm:customer-logout.

Run via:
  http://localhost:8016/tests/vm-customer-harness.html?api=<URL>"
```

---

## Task 4: Create theme integration guide

**Files:**
- Create: `docs/theme-integration.md`

### Step 4.1: Write the guide

- [ ] **Step 4.1: Create `docs/theme-integration.md`**

Copy verbatim:

```markdown
# Theme integration guide (vm-customer.js)

This guide shows how a theme adopts the `skel/vm-customer.js` helper
to plug into the storefront's customer auth surface. Aimed at
sub-projects D.2+ (the per-theme rewiring work) and anyone porting a
new theme from scratch.

## The five-line snippet

Add to the theme's `<head>`:

```html
<script src="vm-customer.js" defer></script>
<script>
    window.addEventListener('vm:customer-loaded', function (e) {
        document.body.dataset.customer = 'logged-in';
    });
    window.addEventListener('vm:customer-login', function (e) {
        document.body.dataset.customer = 'logged-in';
    });
    window.addEventListener('vm:customer-logout', function () {
        document.body.dataset.customer = 'logged-out';
    });
</script>
```

The `vm-customer.js` file is served from the same directory the theme's
`api.php` lives in — `new URL('api.php', window.location.href)` and
`<script src="vm-customer.js">` both resolve relatively, so they
target the same per-site deployment.

## Toggle logged-in vs logged-out sections with CSS

The snippet flips `document.body.dataset.customer` between
`'logged-in'` and `'logged-out'`. Use CSS attribute selectors to
show/hide sections:

```css
[data-customer="logged-out"] .show-when-logged-in { display: none; }
[data-customer="logged-in"]  .show-when-logged-out { display: none; }
```

Default state (no token in localStorage, no event fired yet) leaves
`data-customer` unset. Decide per-theme whether unset = logged-out:

```css
/* Unset = logged-out fallback */
body:not([data-customer="logged-in"]) .show-when-logged-in { display: none; }
```

## Form handlers

### Login

```html
<form id="loginForm">
    <input name="email" type="email" required>
    <input name="password" type="password" required>
    <button type="submit">Sign in</button>
    <div class="error" hidden></div>
</form>

<script>
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const errEl = form.querySelector('.error');
    errEl.hidden = true;
    try {
        await vmCustomer.login(form.email.value, form.password.value);
        // vm:customer-login event fires; CSS flips visibility.
        form.closest('.login-modal')?.classList.remove('open');
    } catch (err) {
        errEl.textContent = err.code === 'locked'
            ? 'Account temporarily locked. Try again in a few minutes.'
            : err.message;
        errEl.hidden = false;
    }
});
</script>
```

### Register

```html
<form id="registerForm">
    <input name="email" type="email" required>
    <input name="password" type="password" required minlength="8">
    <input name="name" type="text">
    <input name="phone" type="tel">
    <button type="submit">Create account</button>
    <div class="error" hidden></div>
</form>

<script>
document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const errEl = form.querySelector('.error');
    errEl.hidden = true;
    try {
        await vmCustomer.register(
            form.email.value,
            form.password.value,
            form.name.value || null,
            form.phone.value || null
        );
    } catch (err) {
        errEl.textContent = err.message;
        errEl.hidden = false;
    }
});
</script>
```

### Logout

```html
<button id="logoutBtn">Sign out</button>

<script>
document.getElementById('logoutBtn').addEventListener('click', async function () {
    await vmCustomer.logout();
    // vm:customer-logout fires; CSS flips visibility.
});
</script>
```

### My orders view

```html
<div class="show-when-logged-in" id="myOrders">
    <h2>My Orders</h2>
    <ul id="orderList"></ul>
</div>

<script>
window.addEventListener('vm:customer-loaded', renderOrders);
window.addEventListener('vm:customer-login', renderOrders);

async function renderOrders() {
    const list = document.getElementById('orderList');
    list.innerHTML = '<li>Loading…</li>';
    try {
        const r = await vmCustomer.myOrders();
        list.innerHTML = '';
        if (r.orders.length === 0) {
            list.innerHTML = '<li>No orders yet.</li>';
            return;
        }
        r.orders.forEach(function (o) {
            const li = document.createElement('li');
            li.textContent = '#' + o.id + ' — ' + o.status + ' — ' + o.total_amount;
            list.appendChild(li);
        });
    } catch (err) {
        list.innerHTML = '<li>Error: ' + err.message + '</li>';
    }
}
</script>
```

### Profile edit

```html
<form id="profileForm" class="show-when-logged-in">
    <input name="name" type="text">
    <input name="phone" type="tel">
    <button type="submit">Save</button>
</form>

<script>
function fillProfileForm() {
    const c = vmCustomer.cached();
    if (!c) return;
    document.querySelector('#profileForm [name="name"]').value = c.name || '';
    document.querySelector('#profileForm [name="phone"]').value = c.phone || '';
}
window.addEventListener('vm:customer-loaded', fillProfileForm);
window.addEventListener('vm:customer-login', fillProfileForm);

document.getElementById('profileForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    await vmCustomer.updateProfile(form.name.value || null, form.phone.value || null);
});
</script>
```

### Change password

```html
<form id="changePwForm" class="show-when-logged-in">
    <input name="current" type="password" required>
    <input name="next" type="password" required minlength="8">
    <button type="submit">Change password</button>
    <div class="error" hidden></div>
</form>

<script>
document.getElementById('changePwForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const errEl = form.querySelector('.error');
    errEl.hidden = true;
    try {
        await vmCustomer.changePassword(form.current.value, form.next.value);
        form.reset();
    } catch (err) {
        errEl.textContent = err.message;
        errEl.hidden = false;
    }
});
</script>
```

## What the helper handles for you

- Token storage in `localStorage.vm_customer_token`.
- Cached customer object in `localStorage.vm_customer_cache` so the
  dashboard renders immediately on page load.
- `X-Customer-Token` header on every request that needs it.
- Bootstrap call: if a token is present on page load, the helper
  calls `customer_me` and fires `vm:customer-loaded` with the
  customer object.
- 401 auto-logout: any endpoint returning 401 clears the token,
  fires `vm:customer-logout`, and rejects the promise with
  `err.code === 'unauthenticated'`.
- Token swap on `changePassword`: the new token returned by the
  backend replaces the stored one transparently.

## What you still build per theme

- The login / register / logout / profile / password / my-orders
  markup and styles.
- CSS for `data-customer="logged-in"` vs `data-customer="logged-out"`
  states.
- Error display per form (the helper rejects promises; you decide
  where to render the message).
- Routing into the account dashboard from the rest of the storefront
  (header avatar, nav link, etc.).
- Pre-fill checkout fields from `vmCustomer.cached()` if you want a
  faster logged-in checkout.

## Bonus endpoints not in D.1

Address book endpoints (`customer_addresses_*`) ship in a later
D-phase. If you want to support them today, hit the public store
API at `/store-access/<store_id>/?state=customer_addresses_*` with
the same `X-Customer-Token` header.

## Common pitfalls

- **CORS** — if your theme is loaded from a different origin than
  `api.php`, browsers block `X-Customer-Token`. Keep them
  same-origin or configure CORS on the deployment.
- **localStorage in incognito** — some browsers limit or disable
  storage in private mode. The helper degrades gracefully (every
  `localStorage` call is wrapped in try/catch) but the user won't
  stay logged in across page loads.
- **Old token after password change** — the helper handles this for
  you automatically. Don't read `localStorage.vm_customer_token`
  directly; use `vmCustomer.token()` (which is always current).
```

### Step 4.2: Commit

- [ ] **Step 4.2: Commit**

```bash
git add docs/theme-integration.md
git commit -m "docs: theme integration guide for vm-customer.js (D.1)

One-page guide for sub-projects D.2+ showing the five-line head
snippet, the data-customer CSS pattern, and complete form
handlers for login/register/logout/profile/password/my-orders.
Includes the bonus-not-in-D.1 note pointing at /store-access/
for address book endpoints, and common pitfalls (CORS,
localStorage in incognito, post-change-password token swap).

Sub-project D.1 is now complete:
- skel/api.php extended (+7 endpoints, +schema bootstrap)
- skel/vm-customer.js drop-in helper
- tests/vm-customer-harness.html browser smoke
- docs/theme-integration.md guide for D.2+"
```

---

## Verification checklist

Before declaring sub-project D.1 done, confirm:

- [ ] `docker exec vm-emb-sites php -l /var/www/html/public/skel/api.php` reports no syntax errors.
- [ ] All 14 steps in Task 1.7's curl smoke return the expected status codes.
- [ ] `skel/api.php` was synced to every existing `sites/*/api.php` via the loop in Step 1.6.
- [ ] The browser harness at `tests/vm-customer-harness.html` shows all PASS rows when run against `http://localhost:8016/sites/debug.com/api.php`.
- [ ] DOM events `vm:customer-login` and `vm:customer-logout` fire as expected during the harness run (visible in the `info` rows).
- [ ] No theme file was modified. No sub-project A or B file was modified.
- [ ] No address book endpoint (`customer_addresses*`) was added — deferred per spec.
