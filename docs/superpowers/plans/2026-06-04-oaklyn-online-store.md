# oaklyn Online Store Port Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add real customer accounts (login/register/dashboard) to `themes/oaklyn/interface` and delete the legacy email-based order-lookup pages. Existing 4-step checkout gets a small upgrade to attach `customer_id` and prefill identity for logged-in customers.

**Architecture:** Single-file vanilla-JS SPA (~2856 lines). Two new full-page views (`view-account`, `view-dashboard`), two deletions (`view-track`, `view-orders`). Auth via `customerAuth = {user, token}` + `vm_customer_token` / `vm_customer_cache` localStorage. Same canonical data-layer pattern as anti/lafromage (`fetchStore` + `postStore`).

**Tech Stack:** Vanilla JS, Tailwind via CDN, Cormorant Garamond / Jost / DM Mono fonts, CSS custom properties (`--ivory`, `--cream`, `--espresso`, `--gold`, `--stone`, `--white`). Hash-based routing via a `router.navigate(view, params)` object.

---

## API contract recap

| Endpoint | Method | Request | Success response |
|---|---|---|---|
| `api.php?state=customer_login` | POST | JSON `{email, password}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_register` | POST | JSON `{email, password, name?, phone?}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_logout` | POST | Header `X-Customer-Token` | `{ok:true}` |
| `api.php?state=customer_me` | GET | Header `X-Customer-Token` | `{ok:true, customer}` |
| `api.php?state=customer_my_orders` | GET | Header `X-Customer-Token` | `[ {id, customer_name, total_amount, items, status, created_at}, ... ]` (raw array) |
| `api.php?state=order` | POST | JSON `{name, email, total, items, customer_id?}` | `{success:true, message}` |

Failure: `{ok:false, error}` + 4xx for auth/me/my_orders; `{error}` + 4xx for `state=order`.

---

## File scope

**Modify (one file):**
- `themes/oaklyn/interface` (~2856 lines as of HEAD `c88a52b`)

**Do not touch:**
- `themes/oaklyn/index.html`, `themes/oaklyn/autofill.json`
- Backend / admin / other themes

---

## Pre-flight

- [ ] **Step 0a: Container up**

Run: `docker ps --format "{{.Names}} {{.Status}}"` — expected: `vm-emb-sites Up ...`. If absent, `docker-compose up -d`.

- [ ] **Step 0b: Page reachable**

Browser: `http://localhost:8016/themes/oaklyn/interface` — expected: oaklyn editorial home, header nav, announcement bar. No JS errors.

- [ ] **Step 0c: Git state**

Run: `git status && git log -1 --oneline` — expected: branch `master`, HEAD at `c88a52b` (oaklyn spec) or later. Theme commits use `git add -f`.

---

## Task 1: Augment `fetchStore` + add `postStore` + legacy cleanup

**Files:**
- Modify: `themes/oaklyn/interface` — the `<script>` block (around line 2074 onward).

This task adds data-layer plumbing. No UI changes.

- [ ] **Step 1.1: Replace `fetchStore` with token-aware version + add `postStore`**

Find this block (search for `async function fetchStore`):

```js
        async function fetchStore(state, params = {}) {
            const url = new URL("api.php", window.location.href);
            url.searchParams.set("state", state);
            for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);
            try {
                const res = await fetch(url, { signal: AbortSignal.timeout(4000) });
                if (!res.ok) throw new Error(res.status);
                return await res.json();
            } catch {
                return getMockData(state, params);
            }
        }
```

Replace with:

```js
        async function fetchStore(state, params = {}) {
            const url = new URL("api.php", window.location.href);
            url.searchParams.set("state", state);
            for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);
            const headers = {};
            const token = localStorage.getItem('vm_customer_token');
            if (token) headers['X-Customer-Token'] = token;
            try {
                const res = await fetch(url, { signal: AbortSignal.timeout(4000), headers });
                if (!res.ok) throw new Error(res.status);
                return await res.json();
            } catch {
                return getMockData(state, params);
            }
        }

        async function postStore(state, body = {}) {
            const url = new URL("api.php", window.location.href);
            url.searchParams.set("state", state);
            const headers = { 'Content-Type': 'application/json' };
            const token = localStorage.getItem('vm_customer_token');
            if (token) headers['X-Customer-Token'] = token;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers,
                    body: JSON.stringify(body),
                    signal: AbortSignal.timeout(8000)
                });
                const data = await res.json().catch(() => ({}));
                return { ok: res.ok && (data.ok !== false), status: res.status, data, error: data.error || null };
            } catch (e) {
                return { ok: false, status: 0, data: {}, error: e.message };
            }
        }
```

- [ ] **Step 1.2: Insert legacy storage cleanup before existing state declarations**

Find this exact line (oaklyn's cart object declaration — search for `items: JSON.parse(localStorage.getItem("oaklyn_cart")`):

```js
            items: JSON.parse(localStorage.getItem("oaklyn_cart") || "[]"),
```

This line is inside the `cart` object literal. We need to insert the cleanup BEFORE the cart object. Search backwards from the above line to find `const cart = {` or similar opening — capture the immediate enclosing block.

Actually, simplest anchor: find the line `function showToast(msg, type = "success") {` (the first function after `postStore` insertion). Insert the cleanup BEFORE that function. Replace:

```js
        function showToast(msg, type = "success") {
```

with:

```js
        // One-time migration / proactive cleanup (defensive for shared-origin pollution).
        try { localStorage.removeItem('vm_user_session'); } catch {}
        try { localStorage.removeItem('vm_user_history'); } catch {}

        function showToast(msg, type = "success") {
```

- [ ] **Step 1.3: Static verification**

Run: `grep -c "postStore" themes/oaklyn/interface` — expected: `1` (declaration only).
Run: `grep -c "X-Customer-Token" themes/oaklyn/interface` — expected: `2` (fetchStore + postStore).
Run: `grep -c "vm_user_session" themes/oaklyn/interface` — expected: `1` (cleanup only; oaklyn has no other references).
Run: `grep -c "vm_user_history" themes/oaklyn/interface` — expected: `1` (cleanup only).

- [ ] **Step 1.4: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): token-aware fetchStore + postStore + legacy storage cleanup"
```

---

## Task 2: Add `customerAuth` state + helpers + `bootCustomer`

**Files:**
- Modify: `themes/oaklyn/interface` — insert `customerAuth` declaration alongside cart, add `isLoggedIn` / `applyCustomerToDom` / `bootCustomer` functions.

- [ ] **Step 2.1: Insert `customerAuth` declaration after the `cart` object**

Find the cart object — search for the closing pattern of cart (look for `clear()` method, then `}` then a blank line). The cart object spans multiple methods. Find the closing `};` of the `cart` declaration. The line immediately after is likely `function showToast(...)` (Task 1 inserted cleanup there too).

Anchor: find this exact block (Task 1 inserted these lines):

```js
        // One-time migration / proactive cleanup (defensive for shared-origin pollution).
        try { localStorage.removeItem('vm_user_session'); } catch {}
        try { localStorage.removeItem('vm_user_history'); } catch {}

        function showToast(msg, type = "success") {
```

Replace with:

```js
        // One-time migration / proactive cleanup (defensive for shared-origin pollution).
        try { localStorage.removeItem('vm_user_session'); } catch {}
        try { localStorage.removeItem('vm_user_history'); } catch {}

        let customerAuth = {
            user: (() => { try { return JSON.parse(localStorage.getItem('vm_customer_cache') || 'null'); } catch { return null; } })(),
            token: localStorage.getItem('vm_customer_token')
        };

        function isLoggedIn() {
            return !!(customerAuth && customerAuth.user);
        }

        function applyCustomerToDom() {
            document.body.dataset.customer = isLoggedIn() ? 'in' : 'out';
            const link = document.getElementById('nav-account-link');
            if (!link) return;
            if (isLoggedIn()) {
                const name = (customerAuth.user.name || customerAuth.user.email || 'Account').toString();
                link.textContent = name.length > 12 ? name.substring(0, 12) + '…' : name;
                link.setAttribute('href', '#dashboard');
            } else {
                link.textContent = 'Account';
                link.setAttribute('href', '#account');
            }
        }

        async function bootCustomer() {
            applyCustomerToDom();

            if (!customerAuth.token) {
                document.dispatchEvent(new CustomEvent('vm:customer-logout'));
                return;
            }
            try {
                const res = await fetch(new URL("api.php?state=customer_me", window.location.href), {
                    headers: { 'X-Customer-Token': customerAuth.token },
                    signal: AbortSignal.timeout(4000)
                });
                if (res.status === 401) {
                    localStorage.removeItem('vm_customer_token');
                    localStorage.removeItem('vm_customer_cache');
                    customerAuth.user = null;
                    customerAuth.token = null;
                    applyCustomerToDom();
                    document.dispatchEvent(new CustomEvent('vm:customer-logout'));
                    const cur = document.querySelector('.view-section.active')?.id || '';
                    if (cur === 'view-dashboard') {
                        router.navigate('home');
                    }
                    return;
                }
                if (!res.ok) throw new Error('boot failed: ' + res.status);
                const data = await res.json();
                if (data && data.ok && data.customer) {
                    customerAuth.user = data.customer;
                    localStorage.setItem('vm_customer_cache', JSON.stringify(data.customer));
                    applyCustomerToDom();
                    document.dispatchEvent(new CustomEvent('vm:customer-login', { detail: data.customer }));
                }
            } catch (e) {
                // Network failure: keep cached user, do not log out.
            }
        }

        function showToast(msg, type = "success") {
```

- [ ] **Step 2.2: Static verification**

Run: `grep -c "let customerAuth" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "function isLoggedIn" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "function applyCustomerToDom" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "async function bootCustomer" themes/oaklyn/interface` — expected: `1`.

`bootCustomer` references `router.navigate` which exists in oaklyn (verified at planning time). Code is correct even though router is below in the file — `bootCustomer` is only called at boot, after router is defined.

- [ ] **Step 2.3: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): add customerAuth state + isLoggedIn + applyCustomerToDom + bootCustomer"
```

---

## Task 3: Add `login` / `register` / `logout` methods

**Files:**
- Modify: `themes/oaklyn/interface` — insert auth action methods after `bootCustomer`.

- [ ] **Step 3.1: Insert auth methods after `bootCustomer`**

Find this exact block (the closing of `bootCustomer` + the `showToast` opener — placed by Task 2):

```js
            } catch (e) {
                // Network failure: keep cached user, do not log out.
            }
        }

        function showToast(msg, type = "success") {
```

Replace with:

```js
            } catch (e) {
                // Network failure: keep cached user, do not log out.
            }
        }

        async function login(email, password) {
            const result = await postStore('customer_login', { email, password });
            if (result.ok && result.data.token && result.data.customer) {
                customerAuth.token = result.data.token;
                customerAuth.user = result.data.customer;
                localStorage.setItem('vm_customer_token', result.data.token);
                localStorage.setItem('vm_customer_cache', JSON.stringify(result.data.customer));
                applyCustomerToDom();
                document.dispatchEvent(new CustomEvent('vm:customer-login', { detail: result.data.customer }));
                return { ok: true };
            }
            return { ok: false, error: result.error || 'Sign-in failed. Check your email and password.' };
        }

        async function register(email, password, name) {
            const result = await postStore('customer_register', { email, password, name });
            if (result.ok && result.data.token && result.data.customer) {
                customerAuth.token = result.data.token;
                customerAuth.user = result.data.customer;
                localStorage.setItem('vm_customer_token', result.data.token);
                localStorage.setItem('vm_customer_cache', JSON.stringify(result.data.customer));
                applyCustomerToDom();
                document.dispatchEvent(new CustomEvent('vm:customer-login', { detail: result.data.customer }));
                return { ok: true };
            }
            return { ok: false, error: result.error || 'Could not create account.' };
        }

        async function logout() {
            try { await postStore('customer_logout', {}); } catch {}
            customerAuth.token = null;
            customerAuth.user = null;
            localStorage.removeItem('vm_customer_token');
            localStorage.removeItem('vm_customer_cache');
            applyCustomerToDom();
            document.dispatchEvent(new CustomEvent('vm:customer-logout'));
            router.navigate('home');
        }

        function showToast(msg, type = "success") {
```

- [ ] **Step 3.2: Static verification**

Run: `grep -c "async function login" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "async function register" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "async function logout" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "postStore(" themes/oaklyn/interface` — expected: `4` (declaration + 3 callsites: customer_login, customer_register, customer_logout).

- [ ] **Step 3.3: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): add login/register/logout methods"
```

---

## Task 4: Insert `view-account` HTML section

**Files:**
- Modify: `themes/oaklyn/interface` — insert new `<section id="view-account">` in the body.

- [ ] **Step 4.1: Find anchor for insertion**

The new section is inserted immediately AFTER `<section id="view-search" class="view-section">...</section>` and BEFORE `<section id="view-about" class="view-section">...</section>`. Find this exact block:

```html
    <section id="view-about" class="view-section">
```

This is the opening of view-about (the section that exists right after view-search).

- [ ] **Step 4.2: Insert the new view-account markup BEFORE the `view-about` opener**

Replace the line `    <section id="view-about" class="view-section">` with:

```html
    <section id="view-account" class="view-section">
        <div class="page-inner" style="max-width:560px;margin:0 auto;padding:80px 32px;">
            <p style="font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.3em;text-transform:uppercase;color:var(--stone);text-align:center;margin-bottom:16px;">Account</p>
            <h1 class="font-serif" style="font-size:48px;font-weight:300;text-align:center;margin-bottom:48px;color:var(--espresso);">Welcome.</h1>

            <div style="display:flex;border-bottom:1px solid var(--cream);margin-bottom:32px;">
                <button data-tab="login" type="button" class="auth-tab" style="flex:1;background:none;border:none;cursor:pointer;padding:16px 0;font-family:'DM Mono',monospace;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--espresso);border-bottom:2px solid var(--gold);">Sign In</button>
                <button data-tab="register" type="button" class="auth-tab" style="flex:1;background:none;border:none;cursor:pointer;padding:16px 0;font-family:'DM Mono',monospace;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--stone);border-bottom:2px solid transparent;">Create Account</button>
            </div>

            <div class="auth-mode-login">
                <form class="js-login-form" data-form="login" style="display:flex;flex-direction:column;gap:20px;">
                    <label style="display:block;">
                        <span style="display:block;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:var(--stone);margin-bottom:8px;">Email</span>
                        <input type="email" name="email" required style="width:100%;background:var(--white);border:1px solid var(--cream);padding:14px 16px;font-family:'Jost',sans-serif;font-size:14px;color:var(--espresso);outline:none;">
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:var(--stone);margin-bottom:8px;">Password</span>
                        <input type="password" name="password" required style="width:100%;background:var(--white);border:1px solid var(--cream);padding:14px 16px;font-family:'Jost',sans-serif;font-size:14px;color:var(--espresso);outline:none;">
                    </label>
                    <button type="submit" class="btn-primary" style="margin-top:12px;">Sign In →</button>
                    <p class="js-error" data-err="login" style="display:none;font-family:'DM Mono',monospace;font-size:11px;color:#a64545;letter-spacing:0.1em;"></p>
                </form>
            </div>

            <div class="auth-mode-register" style="display:none;">
                <form class="js-register-form" data-form="register" style="display:flex;flex-direction:column;gap:20px;">
                    <label style="display:block;">
                        <span style="display:block;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:var(--stone);margin-bottom:8px;">Name</span>
                        <input type="text" name="name" style="width:100%;background:var(--white);border:1px solid var(--cream);padding:14px 16px;font-family:'Jost',sans-serif;font-size:14px;color:var(--espresso);outline:none;">
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:var(--stone);margin-bottom:8px;">Email</span>
                        <input type="email" name="email" required style="width:100%;background:var(--white);border:1px solid var(--cream);padding:14px 16px;font-family:'Jost',sans-serif;font-size:14px;color:var(--espresso);outline:none;">
                    </label>
                    <label style="display:block;">
                        <span style="display:block;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:var(--stone);margin-bottom:8px;">Password (8+ characters)</span>
                        <input type="password" name="password" required minlength="8" style="width:100%;background:var(--white);border:1px solid var(--cream);padding:14px 16px;font-family:'Jost',sans-serif;font-size:14px;color:var(--espresso);outline:none;">
                    </label>
                    <button type="submit" class="btn-primary" style="margin-top:12px;">Create Account →</button>
                    <p class="js-error" data-err="register" style="display:none;font-family:'DM Mono',monospace;font-size:11px;color:#a64545;letter-spacing:0.1em;"></p>
                </form>
            </div>
        </div>
    </section>

    <section id="view-about" class="view-section">
```

- [ ] **Step 4.3: Static verification**

Run: `grep -c 'id="view-account"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'data-tab="login"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'data-tab="register"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'data-form="login"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'data-form="register"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'auth-mode-login' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'auth-mode-register' themes/oaklyn/interface` — expected: `1`.

- [ ] **Step 4.4: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): insert view-account section (sign-in / create tabs)"
```

---

## Task 5: Insert `view-dashboard` HTML section

**Files:**
- Modify: `themes/oaklyn/interface` — insert new `<section id="view-dashboard">` AFTER `view-account` (inserted in Task 4).

- [ ] **Step 5.1: Insert view-dashboard after view-account**

Find this exact block (the end of view-account that Task 4 created, plus the start of view-about):

```html
        </div>
    </section>

    <section id="view-about" class="view-section">
```

(There may be multiple matches of `</section>` patterns in the file. Disambiguate by looking for the `</section>` that immediately precedes `<section id="view-about"`.)

Replace with:

```html
        </div>
    </section>

    <section id="view-dashboard" class="view-section">
        <div class="page-inner" style="max-width:960px;margin:0 auto;padding:80px 32px;">
            <p style="font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.3em;text-transform:uppercase;color:var(--stone);text-align:center;margin-bottom:16px;">Account</p>
            <h1 class="font-serif" style="font-size:48px;font-weight:300;text-align:center;margin-bottom:48px;color:var(--espresso);">Account</h1>

            <div style="background:var(--white);border:1px solid var(--cream);padding:32px;margin-bottom:48px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
                <div>
                    <p class="font-serif" style="font-size:28px;font-weight:300;color:var(--espresso);margin-bottom:6px;">Hello, <span id="dash-name">Friend</span>.</p>
                    <p style="font-family:'DM Mono',monospace;font-size:12px;color:var(--stone);letter-spacing:0.08em;" id="dash-email"></p>
                </div>
                <button id="dash-signout" type="button" class="btn-outline" style="font-family:'DM Mono',monospace;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;">Sign Out</button>
            </div>

            <p style="font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.3em;text-transform:uppercase;color:var(--stone);margin-bottom:16px;">Order History</p>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="border-bottom:2px solid var(--cream);">
                        <th style="text-align:left;padding:12px 0;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--stone);">Order</th>
                        <th style="text-align:left;padding:12px 0;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--stone);">Date</th>
                        <th style="text-align:left;padding:12px 0;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--stone);">Total</th>
                        <th style="text-align:left;padding:12px 0;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--stone);">Status</th>
                    </tr>
                </thead>
                <tbody id="dash-orders-tbody"></tbody>
            </table>
        </div>
    </section>

    <section id="view-about" class="view-section">
```

- [ ] **Step 5.2: Static verification**

Run: `grep -c 'id="view-dashboard"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'id="dash-name"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'id="dash-email"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'id="dash-signout"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'id="dash-orders-tbody"' themes/oaklyn/interface` — expected: `1`.

- [ ] **Step 5.3: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): insert view-dashboard section (greeting + order history)"
```

---

## Task 6: Add `renderAccount` + `setAuthMode` + `renderDashboard` + `wireAuthView` JS

**Files:**
- Modify: `themes/oaklyn/interface` — insert four functions after the `logout()` declaration (placed by Task 3).

- [ ] **Step 6.1: Insert render + wire functions after `logout`**

Find this exact block (placed by Task 3):

```js
        async function logout() {
            try { await postStore('customer_logout', {}); } catch {}
            customerAuth.token = null;
            customerAuth.user = null;
            localStorage.removeItem('vm_customer_token');
            localStorage.removeItem('vm_customer_cache');
            applyCustomerToDom();
            document.dispatchEvent(new CustomEvent('vm:customer-logout'));
            router.navigate('home');
        }

        function showToast(msg, type = "success") {
```

Replace with:

```js
        async function logout() {
            try { await postStore('customer_logout', {}); } catch {}
            customerAuth.token = null;
            customerAuth.user = null;
            localStorage.removeItem('vm_customer_token');
            localStorage.removeItem('vm_customer_cache');
            applyCustomerToDom();
            document.dispatchEvent(new CustomEvent('vm:customer-logout'));
            router.navigate('home');
        }

        function renderAccount() {
            if (isLoggedIn()) { router.navigate('dashboard'); return; }
            setAuthMode('login');
            document.querySelectorAll('#view-account [data-err]').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
        }

        function setAuthMode(mode) {
            document.querySelectorAll('#view-account .auth-tab').forEach(btn => {
                const active = btn.dataset.tab === mode;
                btn.style.color = active ? 'var(--espresso)' : 'var(--stone)';
                btn.style.borderBottomColor = active ? 'var(--gold)' : 'transparent';
            });
            document.querySelector('#view-account .auth-mode-login').style.display = mode === 'login' ? 'block' : 'none';
            document.querySelector('#view-account .auth-mode-register').style.display = mode === 'register' ? 'block' : 'none';
        }

        async function renderDashboard() {
            if (!isLoggedIn()) { router.navigate('account'); return; }

            document.getElementById('dash-name').textContent = customerAuth.user.name || 'Friend';
            document.getElementById('dash-email').textContent = customerAuth.user.email || '';

            const tbody = document.getElementById('dash-orders-tbody');
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:48px 0;color:var(--stone);font-family:'Cormorant Garamond',serif;font-size:18px;">Loading…</td></tr>`;

            let orders = [];
            try { orders = await fetchStore('customer_my_orders'); } catch { orders = []; }
            if (!Array.isArray(orders)) orders = [];

            if (orders.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:48px 0;color:var(--stone);font-family:'Cormorant Garamond',serif;font-size:18px;">No orders yet.</td></tr>`;
                return;
            }
            tbody.innerHTML = orders.map(o => {
                const id = o.id;
                const total = formatPrice(o.total_amount || o.total || 0);
                const status = (o.status || 'Processing').toString();
                const date = (o.created_at || '').toString().substring(0, 10);
                return `
                <tr style="border-bottom:1px solid var(--cream);">
                    <td style="padding:16px 0;font-family:'DM Mono',monospace;">#${id}</td>
                    <td style="padding:16px 0;color:var(--stone);">${date}</td>
                    <td style="padding:16px 0;">${total}</td>
                    <td style="padding:16px 0;"><span style="background:var(--cream);padding:3px 10px;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:0.1em;text-transform:uppercase;">${status}</span></td>
                </tr>`;
            }).join('');
        }

        function wireAuthView() {
            document.querySelectorAll('#view-account .auth-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('#view-account [data-err]').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
                    setAuthMode(btn.dataset.tab);
                });
            });

            const loginForm = document.querySelector('#view-account [data-form="login"]');
            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = loginForm.querySelector('button[type="submit"]');
                    if (btn.disabled) return;
                    const errEl = loginForm.querySelector('[data-err="login"]');
                    errEl.style.display = 'none';
                    btn.disabled = true;
                    btn.dataset.originalText = btn.textContent;
                    btn.textContent = 'Signing in…';
                    try {
                        const fd = new FormData(loginForm);
                        const result = await login(fd.get('email'), fd.get('password'));
                        if (result.ok) {
                            router.navigate('dashboard');
                        } else {
                            errEl.textContent = result.error;
                            errEl.style.display = 'block';
                            btn.disabled = false;
                            btn.textContent = btn.dataset.originalText;
                        }
                    } catch (err) {
                        errEl.textContent = 'Sign-in failed.';
                        errEl.style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText;
                    }
                });
            }

            const registerForm = document.querySelector('#view-account [data-form="register"]');
            if (registerForm) {
                registerForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = registerForm.querySelector('button[type="submit"]');
                    if (btn.disabled) return;
                    const errEl = registerForm.querySelector('[data-err="register"]');
                    errEl.style.display = 'none';
                    btn.disabled = true;
                    btn.dataset.originalText = btn.textContent;
                    btn.textContent = 'Creating…';
                    try {
                        const fd = new FormData(registerForm);
                        const result = await register(fd.get('email'), fd.get('password'), fd.get('name'));
                        if (result.ok) {
                            router.navigate('dashboard');
                        } else {
                            errEl.textContent = result.error;
                            errEl.style.display = 'block';
                            btn.disabled = false;
                            btn.textContent = btn.dataset.originalText;
                        }
                    } catch (err) {
                        errEl.textContent = 'Could not create account.';
                        errEl.style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText;
                    }
                });
            }

            const signOutBtn = document.getElementById('dash-signout');
            if (signOutBtn) {
                signOutBtn.addEventListener('click', async () => {
                    await logout();
                });
            }
        }

        function showToast(msg, type = "success") {
```

- [ ] **Step 6.2: Static verification**

Run: `grep -c "function renderAccount" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "function setAuthMode" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "async function renderDashboard" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "function wireAuthView" themes/oaklyn/interface` — expected: `1`.

- [ ] **Step 6.3: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): add renderAccount/setAuthMode/renderDashboard/wireAuthView"
```

---

## Task 7: Add Account nav link + remove track/orders links from nav/footer/noscript

**Files:**
- Modify: `themes/oaklyn/interface` — edits to desktop nav, mobile nav, footer, noscript fallback.

- [ ] **Step 7.1: Add `Account` link to desktop nav**

Find this exact block (desktop nav `.nav-links`):

```html
            <div class="nav-links">
                <a href="#shop">Shop</a>
                <a href="#collections">Collections</a>
                <a href="#sale">Sale</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </div>
```

Replace with:

```html
            <div class="nav-links">
                <a href="#shop">Shop</a>
                <a href="#collections">Collections</a>
                <a href="#sale">Sale</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
                <a href="#account" id="nav-account-link">Account</a>
            </div>
```

- [ ] **Step 7.2: Replace mobile nav — remove `Track Order`, add `Account`**

Find this exact block:

```html
    <div id="mobile-nav">
        <button class="mob-close" id="mob-close">✕</button>
        <a href="#home">Home</a>
        <a href="#shop">Shop</a>
        <a href="#collections">Collections</a>
        <a href="#sale">Sale</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
        <a href="#faq">FAQ</a>
        <a href="#track">Track Order</a>
    </div>
```

Replace with:

```html
    <div id="mobile-nav">
        <button class="mob-close" id="mob-close">✕</button>
        <a href="#home">Home</a>
        <a href="#shop">Shop</a>
        <a href="#collections">Collections</a>
        <a href="#sale">Sale</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
        <a href="#faq">FAQ</a>
        <a href="#account">Account</a>
    </div>
```

- [ ] **Step 7.3: Update footer Support column — remove `Track Order` and `Order History`, add `Account`**

Find this exact block:

```html
            <div class="footer-col">
                <h4>Support</h4>
                <a href="#contact">Contact Us</a>
                <a href="#faq">FAQ</a>
                <a href="#track">Track Order</a>
                <a href="#orders">Order History</a>
            </div>
```

Replace with:

```html
            <div class="footer-col">
                <h4>Support</h4>
                <a href="#contact">Contact Us</a>
                <a href="#faq">FAQ</a>
                <a href="#account">Account</a>
            </div>
```

- [ ] **Step 7.4: Update noscript fallback — replace `Track` with `Account`**

Find this exact line:

```html
            <a href="#faq">FAQ</a> | <a href="#track">Track</a> | <a href="#terms">Terms</a>
```

Replace with:

```html
            <a href="#faq">FAQ</a> | <a href="#account">Account</a> | <a href="#terms">Terms</a>
```

- [ ] **Step 7.5: Other `#track` link in checkout drawer (line ~1204 originally; check before this task)**

A `<a href="#track">Track Order</a>` may exist elsewhere. Run: `grep -nE 'href="#track"|href="#orders"' themes/oaklyn/interface`. For each remaining occurrence:
- Footer / nav: should already be handled by Steps 7.1-7.4 — sanity check.
- Any standalone "track order" link in a confirmation panel or cart drawer: keep behavior but update to `#account` (so logged-in users land on their dashboard, which serves the same purpose).

Expected after Steps 7.1-7.4: no remaining `href="#track"` or `href="#orders"` in nav/footer/noscript. If grep finds others in non-nav contexts (e.g. inside checkout step 4 confirmation), update them: replace `href="#track"` with `href="#account"`. Replace `>Track Order<` text with `>View Account<`.

- [ ] **Step 7.6: Static verification**

Run: `grep -c 'id="nav-account-link"' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'href="#account"' themes/oaklyn/interface` — expected: `4` (desktop nav, mobile nav, footer, noscript).
Run: `grep -c 'href="#track"' themes/oaklyn/interface` — expected: `0`.
Run: `grep -c 'href="#orders"' themes/oaklyn/interface` — expected: `0`.

- [ ] **Step 7.7: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): swap track/orders nav links for Account; add nav-account-link id"
```

---

## Task 8: Router switch — add account/dashboard, remove track/orders

**Files:**
- Modify: `themes/oaklyn/interface` — the `onRoute(view, params)` switch inside the router object.

- [ ] **Step 8.1: Update router switch**

Find this exact block (search for `case "checkout": renderCheckout();`):

```js
            onRoute(view, params) {
                switch (view) {
                    case "home": renderHome(); break;
                    case "shop": renderShop(params.category); break;
                    case "product": renderProduct(params.id); break;
                    case "collections": renderCollections(); break;
                    case "sale": renderSale(); break;
                    case "cart": renderCart(); break;
                    case "checkout": renderCheckout(); break;
                    case "search": renderSearchPage(params.q); break;
                    case "faq": renderFaq(); break;
                    case "track": break;
                    case "orders": if (params.email) { document.getElementById("orders-email").value = params.email; lookupOrders(); } break;
                }
            }
```

Replace with:

```js
            onRoute(view, params) {
                switch (view) {
                    case "home": renderHome(); break;
                    case "shop": renderShop(params.category); break;
                    case "product": renderProduct(params.id); break;
                    case "collections": renderCollections(); break;
                    case "sale": renderSale(); break;
                    case "cart": renderCart(); break;
                    case "checkout": renderCheckout(); break;
                    case "search": renderSearchPage(params.q); break;
                    case "faq": renderFaq(); break;
                    case "account": renderAccount(); break;
                    case "dashboard": renderDashboard(); break;
                }
            }
```

- [ ] **Step 8.2: Static verification**

Run: `grep -c 'case "track":' themes/oaklyn/interface` — expected: `0`.
Run: `grep -c 'case "orders":' themes/oaklyn/interface` — expected: `0`.
Run: `grep -c 'case "account":' themes/oaklyn/interface` — expected: `1`.
Run: `grep -c 'case "dashboard":' themes/oaklyn/interface` — expected: `1`.

- [ ] **Step 8.3: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): router routes account+dashboard; drops track+orders"
```

---

## Task 9: Upgrade `placeOrder` + `showCheckoutStep` prefill

**Files:**
- Modify: `themes/oaklyn/interface` — two existing functions.

- [ ] **Step 9.1: Replace `placeOrder`**

Find this exact block (search for `async function placeOrder()`):

```js
        async function placeOrder() {
            const email = document.getElementById("co-email").value;
            const fname = document.getElementById("co-fname").value;
            const lname = document.getElementById("co-lname").value;
            const orderId = "KK-" + Math.floor(10000 + Math.random() * 90000);

            const body = {
                name: `${fname} ${lname}`,
                email,
                total: cart.subtotal(),
                items: JSON.stringify(cart.items.map(i => ({ id: i.id, name: i.name, qty: i.qty, price: i.price })))
            };

            try {
                const url = new URL("api.php?state=order", window.location.href);
                await fetch(url, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body), signal: AbortSignal.timeout(5000) });
            } catch { }

            document.getElementById("order-confirm-num").textContent = `Order Reference: ${orderId}`;
            currentStep = 4;
            showCheckoutStep(4);
            cart.clear();
        }
```

Replace with:

```js
        async function placeOrder() {
            const email = document.getElementById("co-email").value;
            const fname = document.getElementById("co-fname").value;
            const lname = document.getElementById("co-lname").value;
            const orderId = "KK-" + Math.floor(10000 + Math.random() * 90000);

            const body = {
                name: `${fname} ${lname}`.trim(),
                email,
                total: cart.subtotal(),
                items: JSON.stringify(cart.items.map(i => ({ id: i.id, name: i.name, qty: i.qty, price: i.price })))
            };
            if (isLoggedIn() && customerAuth.user && customerAuth.user.id) {
                body.customer_id = customerAuth.user.id;
            }

            try {
                const url = new URL("api.php?state=order", window.location.href);
                await fetch(url, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body), signal: AbortSignal.timeout(5000) });
            } catch { }

            document.getElementById("order-confirm-num").textContent = `Order Reference: ${orderId}`;
            currentStep = 4;
            showCheckoutStep(4);
            cart.clear();
        }
```

- [ ] **Step 9.2: Replace `showCheckoutStep` to prefill identity inputs**

Find this exact block (search for `function showCheckoutStep(n)`):

```js
        function showCheckoutStep(n) {
            for (let i = 1; i <= 4; i++) {
                const el = document.getElementById(`checkout-step-${i}`);
                if (el) el.style.display = i === n ? 'block' : 'none';
            }
        }
```

(The exact existing body may be slightly different in formatting. If it does not match exactly, find the function by name and capture its current body.) Replace with:

```js
        function showCheckoutStep(n) {
            for (let i = 1; i <= 4; i++) {
                const el = document.getElementById(`checkout-step-${i}`);
                if (el) el.style.display = i === n ? 'block' : 'none';
            }
            if (n === 1 && isLoggedIn()) {
                const email = document.getElementById('co-email');
                const fname = document.getElementById('co-fname');
                const lname = document.getElementById('co-lname');
                const user = customerAuth.user || {};
                if (email && !email.value) email.value = user.email || '';
                if ((fname && !fname.value) || (lname && !lname.value)) {
                    const parts = (user.name || '').trim().split(/\s+/);
                    if (fname && !fname.value) fname.value = parts[0] || '';
                    if (lname && !lname.value) lname.value = parts.slice(1).join(' ') || '';
                }
            }
        }
```

- [ ] **Step 9.3: Static verification**

Run: `grep -c "body.customer_id = customerAuth.user.id" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "if (n === 1 && isLoggedIn())" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "async function placeOrder" themes/oaklyn/interface` — expected: `1`.
Run: `grep -c "function showCheckoutStep" themes/oaklyn/interface` — expected: `1`.

- [ ] **Step 9.4: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): placeOrder sends customer_id; showCheckoutStep prefills identity"
```

---

## Task 10: Delete `view-track` + `view-orders` sections + `trackOrder` + `lookupOrders`

**Files:**
- Modify: `themes/oaklyn/interface` — two HTML deletions + two JS deletions.

- [ ] **Step 10.1: Delete `view-track` section**

Find this block (search for `<section id="view-track"`) and delete from the opening `<section>` through its closing `</section>` inclusive. The view-track section contains the email entry form + the trackOrder button. Delete:

```html
    <section id="view-track" class="view-section">
        ... (all content)
    </section>
```

Anchor for safety: the section starts at the line containing `<section id="view-track" class="view-section">` and ends at the next `</section>` BEFORE the next `<section id="view-` opener. Verify with `grep -n` before deleting.

- [ ] **Step 10.2: Delete `view-orders` section**

Same approach for `<section id="view-orders" class="view-section">` through its closing `</section>`.

- [ ] **Step 10.3: Delete `async function trackOrder`**

Find this exact block (search for `async function trackOrder`) and delete the entire function from `async function trackOrder() {` through its closing `}`:

```js
        async function trackOrder() {
            const email = document.getElementById("track-email").value.trim();
            ... (rest of body)
        }
```

- [ ] **Step 10.4: Delete `async function lookupOrders`**

Same for `async function lookupOrders() { ... }`.

- [ ] **Step 10.5: Static verification**

Run: `grep -c 'id="view-track"' themes/oaklyn/interface` — expected: `0`.
Run: `grep -c 'id="view-orders"' themes/oaklyn/interface` — expected: `0`.
Run: `grep -c "function trackOrder" themes/oaklyn/interface` — expected: `0`.
Run: `grep -c "function lookupOrders" themes/oaklyn/interface` — expected: `0`.
Run: `grep -c 'document.getElementById("orders-email")' themes/oaklyn/interface` — expected: `0` (no remaining references).
Run: `grep -c 'document.getElementById("track-email")' themes/oaklyn/interface` — expected: `0` (no remaining references).

- [ ] **Step 10.6: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): delete view-track + view-orders + trackOrder + lookupOrders"
```

---

## Task 11: Wire `wireAuthView` + `bootCustomer` at boot

**Files:**
- Modify: `themes/oaklyn/interface` — add two function calls at the end of the existing boot routine.

- [ ] **Step 11.1: Find the existing boot block**

Search for the existing `DOMContentLoaded` handler or IIFE at the bottom of the script. Run:
`grep -nE "DOMContentLoaded|await fetchStore\(.site.\)|router\.init" themes/oaklyn/interface`

The bottom-of-script boot region likely contains an async `(async () => { ... })();` IIFE that calls `await fetchStore("site")` and binds the router.

- [ ] **Step 11.2: Insert `wireAuthView()` and `bootCustomer()` calls**

Find this exact block (search for `const site = await fetchStore("site");`):

```js
                const site = await fetchStore("site");
```

This is inside the boot IIFE. We need to add the wire + boot calls AFTER the IIFE body completes. The simplest, lowest-risk anchor is the closing `})();` of that IIFE.

Read the file around `const site = await fetchStore("site");` to identify the IIFE's closing. Then, AFTER the closing `})();`, add:

```js

        wireAuthView();
        bootCustomer();
```

If the file ends with `</script>` immediately after the IIFE close (no other init lines), the inserts go between `})();` and `</script>`. Concretely:

Find this exact block:

```js
        })();
    </script>
```

Replace with:

```js
        })();

        wireAuthView();
        bootCustomer();
    </script>
```

(If the boot block doesn't end with `})();` immediately before `</script>`, search for the LAST `})();` before `</script>` and use that as the anchor.)

- [ ] **Step 11.3: Static verification**

Run: `grep -c "wireAuthView()" themes/oaklyn/interface` — expected: `2` (declaration + module call).
Run: `grep -c "bootCustomer()" themes/oaklyn/interface` — expected: `2` (declaration + module call).

- [ ] **Step 11.4: Commit**

```bash
git add -f themes/oaklyn/interface
git commit -m "feat(oaklyn): wire wireAuthView + bootCustomer at init"
```

---

## Task 12: End-to-end smoke test

**Files:**
- No code changes unless smoke reveals bugs.

- [ ] **Step 12.1: Reset state**

Navigate to `http://localhost:8016/themes/oaklyn/interface`. Run:

```js
localStorage.removeItem('vm_customer_token');
localStorage.removeItem('vm_customer_cache');
localStorage.removeItem('oaklyn_cart');
localStorage.removeItem('vm_user_session');
localStorage.removeItem('vm_user_history');
location.reload();
```

Verify post-reload: home page renders, header nav has `Account` link, no console errors.

- [ ] **Step 12.2: Run smoke sequence**

| # | Action | Expected |
|---|---|---|
| 1 | Load home | Editorial hero; nav `Account` label; `document.body.dataset.customer === 'out'` |
| 2 | Click `Account` (logged-out) | `view-account` activates; Sign In tab active; both forms render |
| 3 | Switch to Create Account tab | Register form visible; sign-in hidden; tab style flips |
| 4 | Submit Create with fresh email/password/name | Routes to `#dashboard`; greeting "Hello, <name>." visible; email shown; nav label flipped |
| 5 | Reload page; navigate to `#account` | Auto-redirects to `#dashboard` (logged in) |
| 6 | Add a product to cart (`router.navigate('shop')` → click product → Add to Cart) → cart drawer → Proceed to Checkout | view-checkout opens at step 1; co-email + co-fname + co-lname prefilled from auth |
| 7 | Click Continue through all 4 steps; Step 4 confirmation visible | Order placed; cart cleared; payload included `customer_id` (verify in Network tab) |
| 8 | Click `Account` link from confirmation page → dashboard | New order in Order History (or empty state if backend N/A) |
| 9 | Click `Sign Out` | Routes to `#home`; nav label back to `Account`; `dataset.customer === 'out'` |
| 10 | Manual navigate to `#track` | Routes to home (router fallback for unknown view) |
| 11 | Manual navigate to `#orders` | Routes to home |

Steps 4-8 may skip with "Backend N/A" if `/themes/oaklyn/interface` direct-serve doesn't route to the customer API (same documented engine limitation as anti). Mark accordingly.

- [ ] **Step 12.3: Triage**

- PASS — note and continue.
- FAIL in oaklyn code (missing element id, broken querySelector, JS error) → stop, fix immediately, retest, commit as `fix(oaklyn): smoke-test issue from D.4 verification`.
- FAIL due to direct-serve backend limitation → mark "Skipped — backend N/A", continue.

- [ ] **Step 12.4: Final commit (if step 12.3 produced fixes)**

If no fixes needed: skip. Otherwise:

```bash
git add -f themes/oaklyn/interface
git commit -m "fix(oaklyn): smoke-test issues from D.4 verification"
```

---

## Verification summary

After all tasks, net change to `themes/oaklyn/interface`: ~400-500 lines added (view-account + view-dashboard sections, auth methods, render fns, wireAuthView, boot wiring) — minus ~150 lines deleted (view-track + view-orders + trackOrder + lookupOrders + dead nav links). Net additive ~250-350 lines.

No new files. No backend changes.

Known follow-ups (logged, not addressed by this plan):

1. **`state=order` doesn't persist `customer_id`** — sending it is forward-compat.
2. **No order-detail view in dashboard** — could add later.
3. **Address book / change password / profile edit** — deferred (consistent across all D ports).
4. **Lossy `name` split** — `user.name` → fname/lname is one-way only.
5. **`#track` / `#orders` graceful fallback** — currently routes to `#home`. A "Page moved" notice could be friendlier but adds scope.
