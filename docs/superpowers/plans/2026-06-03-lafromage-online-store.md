# lafromage Online Store Port Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend `themes/lafromage/interface` (a single-file SPA) from a cart-only design demo into a full storefront (login/register/dashboard/orders/checkout/search) by extending the existing `SpecimenEngine` class inline.

**Architecture:** Single-file vanilla JS SPA. One `SpecimenEngine` class owns routing, rendering, cart, and (after this port) auth + checkout + search. `fetchStore` / `postStore` are module-scope helpers that talk to `api.php` with an `X-Customer-Token` bearer header when one is in `localStorage`. New UI uses Tailwind utility classes already loaded via CDN.

**Tech Stack:** Vanilla JS (ES6 class + `<template>` cloning), Tailwind CDN, JetBrains Mono / Inter via Google Fonts, hash routing, localStorage. PHP backend (`skel/api.php`) already ships every endpoint we need (sub-projects A/B/D.1 — `customer_login`, `customer_register`, `customer_logout`, `customer_me`, `customer_my_orders`, `state=order`).

---

## API contract recap

| Endpoint | Method | Request | Response (success) |
|---|---|---|---|
| `api.php?state=customer_login` | POST | JSON `{email, password}` | `{ok: true, customer, token, expires_at}` |
| `api.php?state=customer_register` | POST | JSON `{email, password, name?, phone?}` | `{ok: true, customer, token, expires_at}` |
| `api.php?state=customer_logout` | POST | Header `X-Customer-Token: <token>` | `{ok: true}` |
| `api.php?state=customer_me` | GET | Header `X-Customer-Token: <token>` | `{ok: true, customer}` |
| `api.php?state=customer_my_orders` | GET | Header `X-Customer-Token: <token>` | `[ { id, customer_name, total_amount, items, status, created_at }, ... ]` (raw array, no `ok` wrapper) |
| `api.php?state=order` | POST | JSON `{name, email, total, items}` | `{success: true, message: "..."}` |
| `api.php?state=search` | GET | `?q=...` | `[ {id, name, image, price, ...}, ... ]` |

**Known gap (not addressed by this port):** `state=order` does NOT currently accept or persist address fields. The checkout form collects address fields for UX, sends them in the payload (extra fields are silently ignored by PHP), and we document a follow-up to upgrade the endpoint later. Orders ARE persisted (name + email + total + items), just without addresses.

**Failure response shape for `ok` endpoints:** `{ok: false, error: "<message>"}` + HTTP 4xx.

**Failure response shape for `state=order`:** `{error: "<message>"}` + HTTP 4xx — note no `success: false`. Handler must check `success !== true` OR HTTP status.

---

## File scope

**Modify (one file):**
- `themes/lafromage/interface` (current: 347 lines, single file)

**Do not touch:**
- `themes/default/*` (intentionally left as bootstrap placeholder per spec)
- `themes/lafromage/index.html` (standalone alternate, not what the engine serves)
- Backend PHP, admin routes, picker

---

## Pre-flight

- [ ] **Step 0a: Confirm the dev container is up**

Run: `docker ps --format "{{.Names}} {{.Status}}" | findstr vm-emb-sites`
Expected: a line like `vm-emb-sites Up X minutes` (Windows PowerShell `Select-String` form also fine). If empty, run `docker-compose up -d` and re-check.

- [ ] **Step 0b: Confirm the lafromage template is reachable**

In a browser, visit `http://localhost:8016/themes/lafromage/interface`.
Expected: the "THE COLLECTION" home page renders (large display text + grayscale image, nav with `S//P`, `Catalog`, `Index [0]`). If a 404 or a blank page renders, stop and investigate before proceeding.

- [ ] **Step 0c: Verify no committed changes block the work**

Run: `git status themes/lafromage/interface`
Expected: clean (no uncommitted changes) OR only changes you intend to keep.

---

## Task 1: Augment `fetchStore` with bearer header + add `postStore`

**Files:**
- Modify: `themes/lafromage/interface` — the `<script>` block, specifically the `fetchStore` function and the region directly below it (insert `postStore`).

This task adds the data-layer plumbing. No UI changes yet. The existing `fetchStore` keeps its mock-fallback behavior; we just attach the token header when one exists in localStorage. We add a sibling `postStore` for mutations.

- [ ] **Step 1.1: Replace `fetchStore` with token-aware version**

Open `themes/lafromage/interface`. Find this exact block (search for `async function fetchStore`):

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

Replace it with:

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

Note on `ok` computation: HTTP-OK alone isn't enough — `state=customer_login` returns `{ok: false, error: ...}` with a 401 status when credentials are wrong, but we still want a clean `result.ok === false` for callers. `res.ok && (data.ok !== false)` collapses both checks. For `state=order` (which uses `success` instead of `ok`), `data.ok !== false` is true (since `data.ok` is undefined → not strictly false), so the result tracks HTTP status correctly.

- [ ] **Step 1.2: Verify the page still renders + no JS errors**

Reload `http://localhost:8016/themes/lafromage/interface`. Open DevTools Console.
Expected: page renders identically to before, console has no red errors. The `postStore` function is defined but not called yet — that's correct.

In the DevTools console, run:
```js
typeof postStore === 'function'
```
Expected: `true`.

- [ ] **Step 1.3: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): add token-aware fetchStore + postStore helpers"
```

(`-f` because `themes/` is gitignored.)

---

## Task 2: Add auth state + `bootCustomer` to `SpecimenEngine`

**Files:**
- Modify: `themes/lafromage/interface` — the `SpecimenEngine` class constructor + insert `bootCustomer` method.

Constructor gains an `auth` namespace + a call to `bootCustomer`. The new `bootCustomer` method hydrates from cache, optionally verifies via `customer_me`, and dispatches DOM events.

- [ ] **Step 2.1: Augment the constructor**

Find this exact block:

```js
            constructor() {
                this.root = document.getElementById('app-root');
                this.cart = JSON.parse(localStorage.getItem('specimen_cart')) || [];
                this.initRouter();
                this.updateCartCount();
            }
```

Replace with:

```js
            constructor() {
                this.root = document.getElementById('app-root');
                this.cart = JSON.parse(localStorage.getItem('specimen_cart')) || [];
                this.auth = {
                    user: null,
                    token: localStorage.getItem('vm_customer_token')
                };
                this.searchDebounceTimer = null;
                this.initRouter();
                this.updateCartCount();
                this.bootCustomer();
            }
```

(`searchDebounceTimer` is reserved here so Task 8's search handler doesn't need to add another constructor edit.)

- [ ] **Step 2.2: Insert `bootCustomer` and helper methods immediately after `updateCartCount`**

Find this block (the last method in the class, before the closing `}`):

```js
            updateCartCount() {
                const count = this.cart.length;
                document.querySelectorAll('.js-cart-count').forEach(el => el.textContent = count);
            }
        }
```

Replace with:

```js
            updateCartCount() {
                const count = this.cart.length;
                document.querySelectorAll('.js-cart-count').forEach(el => el.textContent = count);
            }

            isLoggedIn() {
                return !!(this.auth && this.auth.user);
            }

            applyCustomerToDom() {
                document.body.dataset.customer = this.isLoggedIn() ? 'in' : 'out';
                const link = document.querySelector('nav a[href="#account"]');
                if (!link) return;
                if (this.isLoggedIn()) {
                    const name = (this.auth.user.name || this.auth.user.email || 'ACCOUNT').toString();
                    link.textContent = name.substring(0, 8).toUpperCase();
                } else {
                    link.textContent = 'ARCHIVE';
                }
            }

            async bootCustomer() {
                const cached = localStorage.getItem('vm_customer_cache');
                if (cached) {
                    try { this.auth.user = JSON.parse(cached); } catch { this.auth.user = null; }
                }
                this.applyCustomerToDom();

                if (!this.auth.token) {
                    document.dispatchEvent(new CustomEvent('vm:customer-logout'));
                    return;
                }
                try {
                    const res = await fetch(new URL("api.php?state=customer_me", window.location.href), {
                        headers: { 'X-Customer-Token': this.auth.token },
                        signal: AbortSignal.timeout(4000)
                    });
                    if (res.status === 401) {
                        localStorage.removeItem('vm_customer_token');
                        localStorage.removeItem('vm_customer_cache');
                        this.auth.user = null;
                        this.auth.token = null;
                        this.applyCustomerToDom();
                        document.dispatchEvent(new CustomEvent('vm:customer-logout'));
                        if ((window.location.hash || '').startsWith('#account')) this.route();
                        return;
                    }
                    if (!res.ok) throw new Error('boot failed: ' + res.status);
                    const data = await res.json();
                    if (data && data.ok && data.customer) {
                        this.auth.user = data.customer;
                        localStorage.setItem('vm_customer_cache', JSON.stringify(data.customer));
                        this.applyCustomerToDom();
                        document.dispatchEvent(new CustomEvent('vm:customer-login', { detail: data.customer }));
                    }
                } catch (e) {
                    // Network failure: keep cached user (if any), do not log out.
                }
            }
        }
```

- [ ] **Step 2.3: Verify page renders + console clean**

Reload the lafromage page. Expected: home view renders, no JS errors, `document.body.dataset.customer === 'out'` (run in console). Nav still shows `Catalog` / `Index [0]` (we haven't added the SEARCH/ARCHIVE links yet — that's Task 5).

In the console:
```js
window.App.auth
```
Expected: `{user: null, token: null}` (assuming no prior login on this origin).

- [ ] **Step 2.4: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): add auth state + bootCustomer to SpecimenEngine"
```

---

## Task 3: Add `login`, `register`, `logout` methods

**Files:**
- Modify: `themes/lafromage/interface` — insert three methods into `SpecimenEngine` after `bootCustomer`.

These are pure data methods — no rendering. Task 4 wires them to the UI.

- [ ] **Step 3.1: Insert auth action methods**

Find the closing `}` of `bootCustomer` (look for the comment `// Network failure: keep cached user (if any), do not log out.` then two closing braces). Immediately after the method's closing `}`, insert:

```js

            async login(email, password) {
                const result = await postStore('customer_login', { email, password });
                if (result.ok && result.data.token && result.data.customer) {
                    this.auth.token = result.data.token;
                    this.auth.user = result.data.customer;
                    localStorage.setItem('vm_customer_token', result.data.token);
                    localStorage.setItem('vm_customer_cache', JSON.stringify(result.data.customer));
                    this.applyCustomerToDom();
                    document.dispatchEvent(new CustomEvent('vm:customer-login', { detail: result.data.customer }));
                    return { ok: true };
                }
                return { ok: false, error: result.error || 'TRANSMISSION_FAILED' };
            }

            async register(email, password, name) {
                const result = await postStore('customer_register', { email, password, name });
                if (result.ok && result.data.token && result.data.customer) {
                    this.auth.token = result.data.token;
                    this.auth.user = result.data.customer;
                    localStorage.setItem('vm_customer_token', result.data.token);
                    localStorage.setItem('vm_customer_cache', JSON.stringify(result.data.customer));
                    this.applyCustomerToDom();
                    document.dispatchEvent(new CustomEvent('vm:customer-login', { detail: result.data.customer }));
                    return { ok: true };
                }
                return { ok: false, error: result.error || 'RECORD_REJECTED' };
            }

            async logout() {
                try { await postStore('customer_logout', {}); } catch {}
                this.auth.token = null;
                this.auth.user = null;
                localStorage.removeItem('vm_customer_token');
                localStorage.removeItem('vm_customer_cache');
                this.applyCustomerToDom();
                document.dispatchEvent(new CustomEvent('vm:customer-logout'));
            }
```

- [ ] **Step 3.2: Smoke-test the methods from the console**

Reload the page. In DevTools Console, run:
```js
await window.App.register('lafrtest+' + Date.now() + '@example.com', 'testpass123', 'Test User')
```

Expected: returns `{ok: true}`. Then:
```js
window.App.auth.user
```
Expected: an object with `email`, `name`, `id`. localStorage now has `vm_customer_token` and `vm_customer_cache`.

Then test logout:
```js
await window.App.logout()
window.App.auth.user
```
Expected: `null`. localStorage cleared.

If `register` returns `{ok: false}` and error mentions "Customer auth module not loaded", the test environment isn't picking up the customer module — this is the wider engine issue, NOT a bug in this task. Note it and continue (Task 4 will surface the same issue via the UI). Falls back gracefully because the methods short-circuit on `result.ok === false`.

- [ ] **Step 3.3: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): add login/register/logout methods to engine"
```

---

## Task 4: Add `#account` route + `tpl-account` template + render methods

**Files:**
- Modify: `themes/lafromage/interface` — insert `tpl-account` template after `tpl-cart`, add `account` case to router, add `renderAccount` + `renderDashboard` methods.

After this task, navigating to `#account` shows login (default), with toggle to register, and switches to dashboard after auth. No nav link yet — that's Task 5.

- [ ] **Step 4.1: Insert `tpl-account` template after `tpl-cart`**

Find this block (the `tpl-cart` closing `</template>` followed by the `<script>` opener):

```html
    <template id="tpl-cart">
        <section class="max-w-4xl mx-auto px-10 py-20 min-h-[60vh] reveal">
            <h1 class="font-display font-black text-6xl uppercase mb-20 italic">The_Index</h1>
            <div class="js-cart-container space-y-px bg-archive-line specimen-border"></div>
        </section>
    </template>

    <script>
```

Replace with:

```html
    <template id="tpl-cart">
        <section class="max-w-4xl mx-auto px-10 py-20 min-h-[60vh] reveal">
            <h1 class="font-display font-black text-6xl uppercase mb-20 italic">The_Index</h1>
            <div class="js-cart-container space-y-px bg-archive-line specimen-border"></div>
        </section>
    </template>

    <template id="tpl-account">
        <section class="max-w-2xl mx-auto px-10 py-20 min-h-[60vh] reveal">
            <h1 class="font-display font-black text-6xl uppercase mb-20 italic">The_Archive</h1>

            <div class="acc-login space-y-8">
                <p class="text-[10px] tracking-[0.5em] uppercase opacity-40">Record_Lookup</p>
                <form class="js-login-form space-y-6" data-form="login">
                    <input type="email" name="email" placeholder="EMAIL" required
                        class="w-full bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                    <input type="password" name="password" placeholder="PASSWORD" required
                        class="w-full bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                    <button type="submit" class="bg-archive-ink text-archive-paper px-12 py-4 font-display font-black uppercase text-sm hover:scale-105 transition-transform">
                        Transmit
                    </button>
                    <p class="js-error text-[10px] uppercase tracking-[0.3em] text-red-700 hidden" data-err="login"></p>
                </form>
                <p class="text-[10px] uppercase tracking-[0.4em] opacity-60">
                    No_Record? <a href="#" class="font-bold hover:line-through" data-action="show-register">Initiate_New</a>
                </p>
            </div>

            <div class="acc-register space-y-8 hidden">
                <p class="text-[10px] tracking-[0.5em] uppercase opacity-40">Initiate_Record</p>
                <form class="js-register-form space-y-6" data-form="register">
                    <input type="text" name="name" placeholder="DISPLAY NAME"
                        class="w-full bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                    <input type="email" name="email" placeholder="EMAIL" required
                        class="w-full bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                    <input type="password" name="password" placeholder="PASSWORD (8+ CHARS)" required minlength="8"
                        class="w-full bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                    <button type="submit" class="bg-archive-ink text-archive-paper px-12 py-4 font-display font-black uppercase text-sm hover:scale-105 transition-transform">
                        Initiate_Record
                    </button>
                    <p class="js-error text-[10px] uppercase tracking-[0.3em] text-red-700 hidden" data-err="register"></p>
                </form>
                <p class="text-[10px] uppercase tracking-[0.4em] opacity-60">
                    Existing_Record? <a href="#" class="font-bold hover:line-through" data-action="show-login">Sign_In</a>
                </p>
            </div>

            <div class="acc-dashboard space-y-12 hidden">
                <div class="space-y-2">
                    <p class="text-[10px] tracking-[0.5em] uppercase opacity-40">Record_Holder</p>
                    <h2 class="font-display font-black text-4xl uppercase js-acc-name"></h2>
                    <p class="text-[10px] uppercase tracking-[0.3em] opacity-60 js-acc-email"></p>
                </div>
                <div class="pt-10 border-t border-archive-line space-y-6">
                    <p class="text-[10px] tracking-[0.5em] uppercase opacity-40">Recent_Transmissions</p>
                    <div class="js-orders-list space-y-px bg-archive-line specimen-border"></div>
                </div>
                <button type="button" class="js-logout-btn text-[10px] uppercase tracking-[0.5em] font-bold hover:line-through pt-10">
                    Terminate_Session
                </button>
            </div>
        </section>
    </template>

    <script>
```

- [ ] **Step 4.2: Add `account` case to the router**

Find this exact block:

```js
                switch(view) {
                    case 'shop': this.renderShop(); break;
                    case 'product': this.renderProduct(id); break;
                    case 'cart': this.renderCart(); break;
                    default: this.renderTemplate('tpl-home'); break;
                }
```

Replace with:

```js
                switch(view) {
                    case 'shop': this.renderShop(); break;
                    case 'product': this.renderProduct(id); break;
                    case 'cart': this.renderCart(); break;
                    case 'account': this.renderAccount(); break;
                    default: this.renderTemplate('tpl-home'); break;
                }
```

- [ ] **Step 4.3: Insert `renderAccount` and `renderDashboard` methods**

Find this exact block (the `logout` method end + class closing `}`):

```js
            async logout() {
                try { await postStore('customer_logout', {}); } catch {}
                this.auth.token = null;
                this.auth.user = null;
                localStorage.removeItem('vm_customer_token');
                localStorage.removeItem('vm_customer_cache');
                this.applyCustomerToDom();
                document.dispatchEvent(new CustomEvent('vm:customer-logout'));
            }
        }
```

Replace with:

```js
            async logout() {
                try { await postStore('customer_logout', {}); } catch {}
                this.auth.token = null;
                this.auth.user = null;
                localStorage.removeItem('vm_customer_token');
                localStorage.removeItem('vm_customer_cache');
                this.applyCustomerToDom();
                document.dispatchEvent(new CustomEvent('vm:customer-logout'));
            }

            renderAccount() {
                this.renderTemplate('tpl-account', (view) => {
                    const showSection = (which) => {
                        view.querySelector('.acc-login').classList.toggle('hidden', which !== 'login');
                        view.querySelector('.acc-register').classList.toggle('hidden', which !== 'register');
                        view.querySelector('.acc-dashboard').classList.toggle('hidden', which !== 'dashboard');
                    };
                    const setError = (which, msg) => {
                        const el = view.querySelector('[data-err="' + which + '"]');
                        if (!el) return;
                        if (msg) { el.textContent = msg; el.classList.remove('hidden'); }
                        else { el.textContent = ''; el.classList.add('hidden'); }
                    };

                    if (this.isLoggedIn()) {
                        showSection('dashboard');
                        this.renderDashboard(view);
                    } else {
                        showSection('login');
                    }

                    view.querySelectorAll('[data-action="show-register"]').forEach(el => {
                        el.addEventListener('click', (e) => { e.preventDefault(); setError('login', ''); showSection('register'); });
                    });
                    view.querySelectorAll('[data-action="show-login"]').forEach(el => {
                        el.addEventListener('click', (e) => { e.preventDefault(); setError('register', ''); showSection('login'); });
                    });

                    const loginForm = view.querySelector('[data-form="login"]');
                    if (loginForm) {
                        loginForm.addEventListener('submit', async (e) => {
                            e.preventDefault();
                            setError('login', '');
                            const fd = new FormData(loginForm);
                            const result = await this.login(fd.get('email'), fd.get('password'));
                            if (result.ok) { showSection('dashboard'); this.renderDashboard(view); }
                            else setError('login', result.error);
                        });
                    }

                    const registerForm = view.querySelector('[data-form="register"]');
                    if (registerForm) {
                        registerForm.addEventListener('submit', async (e) => {
                            e.preventDefault();
                            setError('register', '');
                            const fd = new FormData(registerForm);
                            const result = await this.register(fd.get('email'), fd.get('password'), fd.get('name'));
                            if (result.ok) { showSection('dashboard'); this.renderDashboard(view); }
                            else setError('register', result.error);
                        });
                    }

                    const logoutBtn = view.querySelector('.js-logout-btn');
                    if (logoutBtn) {
                        logoutBtn.addEventListener('click', async () => {
                            await this.logout();
                            showSection('login');
                        });
                    }
                });
            }

            async renderDashboard(view) {
                const root = view || this.root;
                const user = this.auth.user || {};
                const nameEl = root.querySelector('.js-acc-name');
                const emailEl = root.querySelector('.js-acc-email');
                if (nameEl) nameEl.textContent = (user.name || 'UNNAMED_RECORD').toUpperCase();
                if (emailEl) emailEl.textContent = user.email || '';

                const ordersEl = root.querySelector('.js-orders-list');
                if (!ordersEl) return;
                ordersEl.innerHTML = '<div class="p-6 bg-archive-paper text-[10px] tracking-[0.4em] opacity-30">FETCHING_LOG...</div>';

                let orders = [];
                try { orders = await fetchStore('customer_my_orders'); } catch { orders = []; }
                if (!Array.isArray(orders)) orders = [];
                ordersEl.innerHTML = '';
                if (orders.length === 0) {
                    ordersEl.innerHTML = '<div class="p-8 bg-archive-paper text-[10px] tracking-[0.5em] opacity-30 text-center">NO_TRANSMISSIONS_ON_RECORD</div>';
                    return;
                }
                orders.slice(0, 5).forEach((order) => {
                    const row = document.createElement('div');
                    row.className = 'p-6 bg-archive-paper flex justify-between items-center text-[10px] uppercase tracking-[0.3em]';
                    const id = order.id || order.order_id || '?';
                    const total = order.total_amount != null ? order.total_amount : (order.total || '?');
                    const status = (order.status || 'pending').toString();
                    const created = (order.created_at || '').toString().substring(0, 10);
                    row.innerHTML =
                        '<span class="opacity-40">#' + String(id).padStart(4, '0') + '</span>' +
                        '<span>' + created + '</span>' +
                        '<span>' + total + '</span>' +
                        '<span class="font-bold">' + status + '</span>';
                    ordersEl.appendChild(row);
                });
            }
        }
```

- [ ] **Step 4.4: Browser verify the account flow**

Reload the page. Make sure localStorage has no `vm_customer_token` (DevTools → Application → Storage → clear it if present). Navigate manually to `http://localhost:8016/themes/lafromage/interface#account`.

Expected:
- `tpl-account` renders with `The_Archive` heading + `Record_Lookup` section + email/password inputs + `TRANSMIT` button. `acc-register` and `acc-dashboard` are hidden.
- Click `Initiate_New` link → `acc-login` hides, `acc-register` shows (with name/email/password). Click `Sign_In` → toggles back.
- In `acc-register`, enter a fresh email, 8+ char password, a name, click `Initiate_Record`. Expected: dashboard renders with the name + email + a `RECENT_TRANSMISSIONS` panel showing `NO_TRANSMISSIONS_ON_RECORD` (no orders yet for this account).
- Click `Terminate_Session` → returns to login section. `localStorage.vm_customer_token` is gone.

If the register call fails with "Customer auth module not loaded", note it — the engine's iframe routing surfaces customer endpoints differently than `themes/<x>/interface` does for direct loading. The wider playbook's workaround (mentioned in `docs/superpowers/theme-port-playbook.md`) is to seed a token manually for smoke. Don't fix this in lafromage; it's a wider routing issue.

- [ ] **Step 4.5: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): wire #account route with login/register/dashboard"
```

---

## Task 5: Update nav — add `SEARCH` and `ARCHIVE` links + reactive label

**Files:**
- Modify: `themes/lafromage/interface` — the `<nav>` element + a small listener so the ARCHIVE label flips after engine boot (Task 2's `applyCustomerToDom` already does this on init; we just need to ensure the new link selector exists before boot runs).

- [ ] **Step 5.1: Replace nav links**

Find this exact block:

```html
    <nav class="fixed top-0 left-0 w-full z-50 mix-blend-difference px-10 py-8 flex justify-between items-baseline">
        <a href="#home" class="font-display font-black text-2xl tracking-tighter text-archive-ink">S//P</a>
        <div class="flex space-x-12 text-[10px] uppercase tracking-[0.4em] font-bold">
            <a href="#shop" class="hover:line-through">Catalog</a>
            <a href="#cart" class="hover:line-through">Index [<span class="js-cart-count">0</span>]</a>
        </div>
    </nav>
```

Replace with:

```html
    <nav class="fixed top-0 left-0 w-full z-50 mix-blend-difference px-10 py-8 flex justify-between items-baseline">
        <a href="#home" class="font-display font-black text-2xl tracking-tighter text-archive-ink">S//P</a>
        <div class="flex space-x-12 text-[10px] uppercase tracking-[0.4em] font-bold">
            <a href="#search" class="hover:line-through">Search</a>
            <a href="#shop" class="hover:line-through">Catalog</a>
            <a href="#account" class="hover:line-through">Archive</a>
            <a href="#cart" class="hover:line-through">Index [<span class="js-cart-count">0</span>]</a>
        </div>
    </nav>
```

(The label is `Archive` in source — `applyCustomerToDom` will uppercase it to the user's name on boot if logged in.)

- [ ] **Step 5.2: Browser verify the nav**

Reload. Expected: 4 links visible — `SEARCH`, `CATALOG`, `ARCHIVE`, `INDEX [0]`. If logged in from Task 4's smoke, the ARCHIVE label should be the first 8 chars of the registered name (uppercased). Clicking each link routes:
- `Search` → `#search` (currently renders nothing — the route isn't wired yet; Task 8 adds it). For now, expect the home view to remain or a blank `#app-root` — this is acceptable until Task 8.
- `Catalog` → `#shop` (existing, works)
- `Archive` → `#account` (works per Task 4)
- `Index` → `#cart` (existing)

In console:
```js
document.body.dataset.customer
```
Expected: `'in'` if logged in, `'out'` otherwise.

- [ ] **Step 5.3: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): add SEARCH + ARCHIVE links to nav"
```

---

## Task 6: Cart upgrades — qty controls + totals strip + PROCEED button

**Files:**
- Modify: `themes/lafromage/interface` — `renderCart` method + `cartAdd` to initialize qty.

The existing cart treats each item as quantity 1 (the `cartAdd` short-circuits if the item already exists). We need real qty tracking. Existing row layout is preserved, with `+ / qty / -` injected between title and price.

- [ ] **Step 6.1: Replace `cartAdd` to track qty**

Find:

```js
            cartAdd(product) {
                const existing = this.cart.find(item => item.id === product.id);
                if (!existing) this.cart.push({ ...product, qty: 1 });
                this.cartSave();
            }
```

Replace with:

```js
            cartAdd(product) {
                const existing = this.cart.find(item => item.id === product.id);
                if (existing) {
                    existing.qty = (existing.qty || 1) + 1;
                } else {
                    this.cart.push({ ...product, qty: 1 });
                }
                this.cartSave();
            }
```

- [ ] **Step 6.2: Replace `renderCart` with qty controls + totals**

Find:

```js
            renderCart() {
                this.renderTemplate('tpl-cart', (mainView) => {
                    const container = mainView.querySelector('.js-cart-container');
                    if (this.cart.length === 0) {
                        container.innerHTML = `<div class="p-20 bg-archive-paper text-center opacity-30 text-[10px] tracking-[0.5em]">Index_Empty</div>`;
                        return;
                    }
                    this.cart.forEach((item, index) => {
                        const row = document.createElement('div');
                        row.className = "p-10 bg-archive-paper flex justify-between items-center group";
                        row.innerHTML = `
                            <div class="flex items-center space-x-8">
                                <span class="text-[9px] opacity-20 font-black">0${index+1}</span>
                                <h3 class="font-display font-black text-2xl uppercase">${item.title}</h3>
                            </div>
                            <div class="flex items-center space-x-12">
                                <span class="font-bold text-sm">${item.price} ${DB.currency}</span>
                                <button class="text-[9px] font-black hover:line-through js-remove">PURGE</button>
                            </div>
                        `;
                        row.querySelector('.js-remove').onclick = () => { this.cart.splice(index, 1); this.cartSave(); this.renderCart(); };
                        container.appendChild(row);
                    });
                });
            }
```

Replace with:

```js
            renderCart() {
                this.renderTemplate('tpl-cart', (mainView) => {
                    const container = mainView.querySelector('.js-cart-container');
                    if (this.cart.length === 0) {
                        container.innerHTML = `<div class="p-20 bg-archive-paper text-center opacity-30 text-[10px] tracking-[0.5em]">Index_Empty</div>`;
                        return;
                    }
                    this.cart.forEach((item, index) => {
                        const qty = item.qty || 1;
                        const lineTotal = (Number(item.price) || 0) * qty;
                        const row = document.createElement('div');
                        row.className = "p-10 bg-archive-paper flex justify-between items-center group";
                        row.innerHTML = `
                            <div class="flex items-center space-x-8">
                                <span class="text-[9px] opacity-20 font-black">0${index+1}</span>
                                <h3 class="font-display font-black text-2xl uppercase">${item.title}</h3>
                            </div>
                            <div class="flex items-center space-x-10">
                                <div class="flex items-center space-x-3 specimen-border px-3 py-1">
                                    <button class="text-xs font-black hover:line-through js-qty-down" type="button">-</button>
                                    <span class="text-xs font-mono w-6 text-center js-qty">${qty}</span>
                                    <button class="text-xs font-black hover:line-through js-qty-up" type="button">+</button>
                                </div>
                                <span class="font-bold text-sm js-line-total">${lineTotal} ${DB.currency}</span>
                                <button class="text-[9px] font-black hover:line-through js-remove" type="button">PURGE</button>
                            </div>
                        `;
                        row.querySelector('.js-qty-down').onclick = () => {
                            const current = item.qty || 1;
                            if (current <= 1) return;
                            item.qty = current - 1;
                            this.cartSave();
                            this.renderCart();
                        };
                        row.querySelector('.js-qty-up').onclick = () => {
                            item.qty = (item.qty || 1) + 1;
                            this.cartSave();
                            this.renderCart();
                        };
                        row.querySelector('.js-remove').onclick = () => {
                            this.cart.splice(index, 1);
                            this.cartSave();
                            this.renderCart();
                        };
                        container.appendChild(row);
                    });

                    const total = this.cart.reduce((sum, it) => sum + ((Number(it.price) || 0) * (it.qty || 1)), 0);
                    const footer = document.createElement('div');
                    footer.className = 'mt-10 specimen-border p-8 flex justify-between items-center';
                    footer.innerHTML = `
                        <span class="text-[10px] tracking-[0.5em] uppercase opacity-60">Subtotal: <span class="font-bold ml-4">${total} ${DB.currency}</span></span>
                        <a href="#checkout" class="bg-archive-ink text-archive-paper px-12 py-4 font-display font-black uppercase text-sm hover:scale-105 transition-transform">
                            Proceed_To_Transmission
                        </a>
                    `;
                    mainView.querySelector('section').appendChild(footer);
                });
            }
```

Also: the `updateCartCount` method shows the number of distinct items. Update it to sum quantities so the `INDEX [n]` reflects total items.

Find:

```js
            updateCartCount() {
                const count = this.cart.length;
                document.querySelectorAll('.js-cart-count').forEach(el => el.textContent = count);
            }
```

Replace with:

```js
            updateCartCount() {
                const count = this.cart.reduce((sum, it) => sum + (it.qty || 1), 0);
                document.querySelectorAll('.js-cart-count').forEach(el => el.textContent = count);
            }
```

- [ ] **Step 6.3: Browser verify the cart**

Reload, clear cart from localStorage if present (`localStorage.removeItem('specimen_cart')`). Navigate to `#shop`, click a product, click `Add_To_Archive`. The cart route opens automatically.

Expected:
- One row with `-` `1` `+` controls + line total + `PURGE`.
- Below container: `SUBTOTAL: <price>` + `PROCEED_TO_TRANSMISSION` button.
- Click `+` → qty becomes 2, line total doubles, INDEX count in nav becomes 2.
- Click `-` → qty becomes 1.
- Click `-` again at qty 1 → no change (min 1).
- Click `PURGE` → row removed, cart shows `Index_Empty`, footer disappears.
- Add another product → both rows show, totals reflect sum.

- [ ] **Step 6.4: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): cart qty controls + totals strip + proceed button"
```

---

## Task 7: Add `#checkout` route + `tpl-checkout` + place-order handler

**Files:**
- Modify: `themes/lafromage/interface` — insert `tpl-checkout` template after `tpl-account`, add `checkout` case to router, add `renderCheckout` method.

- [ ] **Step 7.1: Insert `tpl-checkout` template after `tpl-account`**

Find the closing `</template>` of `tpl-account` and the `<script>` opener (after Task 4's edits, this region looks like the `acc-dashboard` section closing then `</section></template>` then blank line then `<script>`).

Insert `tpl-checkout` immediately after `tpl-account`'s closing `</template>`:

```html
    <template id="tpl-checkout">
        <section class="max-w-3xl mx-auto px-10 py-20 min-h-[60vh] reveal">
            <h1 class="font-display font-black text-6xl uppercase mb-20 italic">Commit_Transmission</h1>

            <div class="chk-guest hidden space-y-8">
                <p class="text-[10px] tracking-[0.5em] uppercase opacity-60">Transmission_Requires_Identification</p>
                <p class="text-sm opacity-60">A record must be initiated before this archive can be committed.</p>
                <a href="#account" class="inline-block bg-archive-ink text-archive-paper px-12 py-4 font-display font-black uppercase text-sm hover:scale-105 transition-transform">
                    Return_To_Archive
                </a>
            </div>

            <form class="chk-form hidden space-y-12 js-checkout-form">
                <div class="space-y-6">
                    <p class="text-[10px] tracking-[0.5em] uppercase opacity-40">Destination</p>
                    <input type="text" name="addr_street" placeholder="STREET" required
                        class="w-full bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <input type="text" name="addr_city" placeholder="CITY" required
                            class="bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                        <input type="text" name="addr_postal" placeholder="POSTAL" required
                            class="bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                        <input type="text" name="addr_country" placeholder="COUNTRY" required
                            class="bg-transparent specimen-border px-6 py-4 font-mono text-sm uppercase tracking-wider placeholder:opacity-30 focus:outline-none focus:border-archive-ink">
                    </div>
                </div>

                <div class="space-y-4 pt-10 border-t border-archive-line">
                    <p class="text-[10px] tracking-[0.5em] uppercase opacity-40">Inventory</p>
                    <p class="text-sm js-chk-summary"></p>
                </div>

                <div class="flex justify-between items-center pt-10 border-t border-archive-line">
                    <span class="font-display font-black text-3xl js-chk-total"></span>
                    <button type="submit" class="bg-archive-ink text-archive-paper px-12 py-6 font-display font-black uppercase text-sm hover:scale-105 transition-transform">
                        Commit_Order
                    </button>
                </div>
                <p class="js-error text-[10px] uppercase tracking-[0.3em] text-red-700 hidden"></p>
            </form>
        </section>
    </template>
```

- [ ] **Step 7.2: Add `checkout` case to the router**

Find:

```js
                switch(view) {
                    case 'shop': this.renderShop(); break;
                    case 'product': this.renderProduct(id); break;
                    case 'cart': this.renderCart(); break;
                    case 'account': this.renderAccount(); break;
                    default: this.renderTemplate('tpl-home'); break;
                }
```

Replace with:

```js
                switch(view) {
                    case 'shop': this.renderShop(); break;
                    case 'product': this.renderProduct(id); break;
                    case 'cart': this.renderCart(); break;
                    case 'account': this.renderAccount(); break;
                    case 'checkout': this.renderCheckout(); break;
                    default: this.renderTemplate('tpl-home'); break;
                }
```

- [ ] **Step 7.3: Insert `renderCheckout` method**

Find the `renderDashboard` method's closing `}` (look for the final `ordersEl.appendChild(row);` line in the `forEach` block, then the method's two closing braces). Immediately after `renderDashboard`'s closing `}`, insert:

```js

            renderCheckout() {
                this.renderTemplate('tpl-checkout', (view) => {
                    const guestEl = view.querySelector('.chk-guest');
                    const formEl = view.querySelector('.chk-form');

                    if (!this.isLoggedIn()) {
                        guestEl.classList.remove('hidden');
                        return;
                    }
                    if (this.cart.length === 0) {
                        guestEl.classList.remove('hidden');
                        guestEl.querySelector('p:nth-of-type(2)').textContent = 'The archive is empty. Curate items before transmission.';
                        const link = guestEl.querySelector('a');
                        link.setAttribute('href', '#shop');
                        link.textContent = 'Return_To_Catalog';
                        return;
                    }

                    formEl.classList.remove('hidden');
                    const total = this.cart.reduce((sum, it) => sum + ((Number(it.price) || 0) * (it.qty || 1)), 0);
                    const itemCount = this.cart.reduce((sum, it) => sum + (it.qty || 1), 0);
                    view.querySelector('.js-chk-summary').textContent =
                        itemCount + ' specimen' + (itemCount === 1 ? '' : 's') + ' from your archive.';
                    view.querySelector('.js-chk-total').textContent = total + ' ' + DB.currency;

                    const errEl = view.querySelector('.js-error');
                    const setError = (msg) => {
                        if (msg) { errEl.textContent = msg; errEl.classList.remove('hidden'); }
                        else { errEl.textContent = ''; errEl.classList.add('hidden'); }
                    };

                    formEl.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        setError('');
                        const fd = new FormData(formEl);
                        const user = this.auth.user || {};
                        const payload = {
                            name: user.name || 'CUSTOMER',
                            email: user.email || '',
                            total: total,
                            items: this.cart.map(i => ({
                                id: i.id,
                                title: i.title || i.name,
                                price: i.price,
                                qty: i.qty || 1
                            })),
                            // Address fields included but not currently persisted by state=order.
                            addr_street: fd.get('addr_street'),
                            addr_city: fd.get('addr_city'),
                            addr_postal: fd.get('addr_postal'),
                            addr_country: fd.get('addr_country')
                        };
                        const result = await postStore('order', payload);
                        const ok = result.ok || (result.data && result.data.success === true);
                        if (ok) {
                            this.cart = [];
                            this.cartSave();
                            window.location.hash = 'account';
                        } else {
                            setError(result.error || (result.data && result.data.error) || 'TRANSMISSION_REJECTED');
                        }
                    });
                });
            }
```

- [ ] **Step 7.4: Browser verify checkout**

Reload. Two scenarios:

**Scenario A — guest checkout:**
- Make sure logged out (`localStorage.removeItem('vm_customer_token')`).
- Navigate to `#checkout` directly.
- Expected: guest panel renders with `TRANSMISSION_REQUIRES_IDENTIFICATION` heading + `RETURN_TO_ARCHIVE` link.
- Click the link → `#account` shows login form.

**Scenario B — logged-in checkout:**
- Register or log in (use steps from Task 4 smoke if needed).
- Add 2 different products to cart (one with qty 2).
- Navigate to `#cart` → click `PROCEED_TO_TRANSMISSION`.
- Expected: checkout form renders with 4 address inputs, summary line ("3 specimens from your archive."), total amount, `COMMIT_ORDER` button.
- Fill all 4 address fields. Click `COMMIT_ORDER`.
- Expected: cart clears, redirected to `#account`, dashboard shows the new order in `RECENT_TRANSMISSIONS`. Network tab shows POST to `api.php?state=order` with 200/201 status.

If the order POST fails with a 500 ("Failed to save order"), this is a backend issue unrelated to lafromage (likely the per-site DB missing the `orders` table) — note and move on.

- [ ] **Step 7.5: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): checkout view with guest interception + place-order"
```

---

## Task 8: Extract `renderProductCard` + add `#search` route + `tpl-search`

**Files:**
- Modify: `themes/lafromage/interface` — insert `tpl-search` after `tpl-checkout`, add `search` case to router, refactor `renderShop` to call new `renderProductCard`, add `renderSearch` method.

- [ ] **Step 8.1: Insert `tpl-search` template after `tpl-checkout`**

Insert after `tpl-checkout`'s closing `</template>`:

```html
    <template id="tpl-search">
        <section class="max-w-7xl mx-auto px-10 py-20 reveal">
            <div class="flex justify-between items-end mb-12">
                <h2 class="font-display font-black text-6xl uppercase italic">Query_The_Archive</h2>
            </div>
            <input type="search" class="js-search-input w-full bg-transparent specimen-border border-b-2 border-transparent border-b-archive-ink py-4 font-mono text-xl uppercase tracking-wider placeholder:opacity-30 focus:outline-none mb-12" placeholder="SEARCH...">
            <p class="text-[10px] tracking-[0.5em] uppercase opacity-40 mb-12 js-search-meta">Awaiting_Query</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 js-search-grid"></div>
        </section>
    </template>
```

- [ ] **Step 8.2: Add `search` case to router**

Find:

```js
                switch(view) {
                    case 'shop': this.renderShop(); break;
                    case 'product': this.renderProduct(id); break;
                    case 'cart': this.renderCart(); break;
                    case 'account': this.renderAccount(); break;
                    case 'checkout': this.renderCheckout(); break;
                    default: this.renderTemplate('tpl-home'); break;
                }
```

Replace with:

```js
                switch(view) {
                    case 'shop': this.renderShop(); break;
                    case 'product': this.renderProduct(id); break;
                    case 'cart': this.renderCart(); break;
                    case 'account': this.renderAccount(); break;
                    case 'checkout': this.renderCheckout(); break;
                    case 'search': this.renderSearch(); break;
                    default: this.renderTemplate('tpl-home'); break;
                }
```

- [ ] **Step 8.3: Extract `renderProductCard` from `renderShop`**

Find:

```js
            renderShop() {
                this.renderTemplate('tpl-shop', (view) => {
                    const grid = view.querySelector('.js-product-grid');
                    const prodTpl = document.getElementById('tpl-product-card');
                    DB.products.forEach(p => {
                        const card = prodTpl.content.cloneNode(true);
                        card.querySelector('.js-prod-link').onclick = () => window.location.hash = `product/${p.id}`;
                        card.querySelector('.js-title').textContent = p.title;
                        card.querySelector('.js-category').textContent = p.category;
                        card.querySelector('.js-price').textContent = `${p.price} ${DB.currency}`;
                        card.querySelector('img').src = p.img;
                        grid.appendChild(card);
                    });
                });
            }
```

Replace with:

```js
            renderProductCard(product, grid) {
                const prodTpl = document.getElementById('tpl-product-card');
                const card = prodTpl.content.cloneNode(true);
                const link = card.querySelector('.js-prod-link');
                if (link) link.onclick = () => window.location.hash = `product/${product.id}`;
                const title = card.querySelector('.js-title');
                if (title) title.textContent = product.title || product.name || '';
                const cat = card.querySelector('.js-category');
                if (cat) cat.textContent = product.category || '';
                const price = card.querySelector('.js-price');
                if (price) price.textContent = `${product.price} ${DB.currency}`;
                const img = card.querySelector('img');
                if (img) img.src = product.img || product.image || '';
                grid.appendChild(card);
            }

            renderShop() {
                this.renderTemplate('tpl-shop', (view) => {
                    const grid = view.querySelector('.js-product-grid');
                    DB.products.forEach(p => this.renderProductCard(p, grid));
                });
            }
```

- [ ] **Step 8.4: Insert `renderSearch` method**

Find the `renderCheckout` method's closing brace pattern (look for `setError(result.error || (result.data && result.data.error) || 'TRANSMISSION_REJECTED');` then 4 closing braces and ` });` then the method-closing `}` then class-closing `}`).

Insert `renderSearch` immediately before the class-closing `}`, after `renderCheckout`:

```js

            renderSearch() {
                this.renderTemplate('tpl-search', (view) => {
                    const input = view.querySelector('.js-search-input');
                    const grid = view.querySelector('.js-search-grid');
                    const meta = view.querySelector('.js-search-meta');
                    const run = async (q) => {
                        const query = (q || '').trim();
                        if (!query) {
                            grid.innerHTML = '';
                            meta.textContent = 'AWAITING_QUERY';
                            return;
                        }
                        meta.textContent = 'QUERYING...';
                        let results = [];
                        try { results = await fetchStore('search', { q: query }); } catch { results = []; }
                        if (!Array.isArray(results)) results = [];
                        grid.innerHTML = '';
                        meta.textContent = 'RESULTS [' + results.length + ']';
                        results.forEach(p => this.renderProductCard({
                            id: p.id,
                            title: p.title || p.name,
                            price: p.price,
                            category: p.category || '',
                            img: p.img || p.image
                        }, grid));
                    };
                    input.addEventListener('input', (e) => {
                        clearTimeout(this.searchDebounceTimer);
                        const v = e.target.value;
                        this.searchDebounceTimer = setTimeout(() => run(v), 250);
                    });
                    input.focus();
                });
            }
```

- [ ] **Step 8.5: Browser verify search**

Reload. Click `SEARCH` in the nav.
Expected:
- Search view renders with `QUERY_THE_ARCHIVE` heading, focused input, `AWAITING_QUERY` meta line, empty grid.
- Type a known product title (e.g. `void` for "Void Tee"). After 250ms, grid populates with matching card(s), meta shows `RESULTS [n]`.
- Clear the input. Meta returns to `AWAITING_QUERY`, grid clears.
- Click a result card → routes to `#product/<id>` (existing behavior).
- Verify `#shop` (CATALOG link) still renders all products — `renderProductCard` extraction didn't break it.

- [ ] **Step 8.6: Commit**

```bash
git add -f themes/lafromage/interface
git commit -m "feat(lafromage): #search route + extract renderProductCard"
```

---

## Task 9: End-to-end smoke test

**Files:**
- No code changes. This task validates the spec's 11-step smoke sequence end-to-end.

- [ ] **Step 9.1: Reset state**

In the browser at `http://localhost:8016/themes/lafromage/interface`, run in DevTools console:

```js
localStorage.removeItem('vm_customer_token');
localStorage.removeItem('vm_customer_cache');
localStorage.removeItem('specimen_cart');
location.hash = '';
location.reload();
```

- [ ] **Step 9.2: Run the 11-step sequence from the spec**

| # | Action | Expected |
|---|---|---|
| 1 | Load home | THE COLLECTION renders; nav has SEARCH / CATALOG / ARCHIVE / INDEX[0] |
| 2 | Click ARCHIVE | acc-login section visible; acc-register, acc-dashboard hidden |
| 3 | Toggle to register; submit fresh email + 8+ char password + name | dashboard renders; name in greeting; nav `ARCHIVE` label flipped to first 8 chars of name (uppercase) |
| 4 | Reload page; click ARCHIVE | dashboard renders immediately (cached) |
| 5 | CATALOG → add 2 different products | cart count `INDEX [2]` |
| 6 | Click INDEX | both rows visible with qty controls + totals + PROCEED button |
| 7 | Use `+` to set one item to qty 2 | line total doubles; subtotal updates; INDEX [3] |
| 8 | Click PROCEED_TO_TRANSMISSION; fill address; click COMMIT_ORDER | cart clears; redirected to #account; new order at top of RECENT_TRANSMISSIONS |
| 9 | Click TERMINATE_SESSION | back to login view; nav label back to ARCHIVE |
| 10 | Click SEARCH; type a known product title | results grid populates with matching card(s) |
| 11 | Navigate directly to `#checkout` (logged-out) | guest interception panel with RETURN_TO_ARCHIVE link |

- [ ] **Step 9.3: Record pass/fail per step**

For each row above, note PASS/FAIL with a short observation. Any FAIL must be triaged:
- If FAIL is in lafromage code → fix immediately, retry that step + commit.
- If FAIL is in the wider engine (customer auth module not loading, order endpoint 500, missing DB tables for `/themes/<x>/interface` direct serve) → document and continue. The lafromage code is done.

- [ ] **Step 9.4: Final commit (only if step 9.3 produced fixes)**

If no fixes were needed, skip. Otherwise:

```bash
git add -f themes/lafromage/interface
git commit -m "fix(lafromage): smoke-test issues from D.6 verification"
```

---

## Verification summary

After all tasks complete, the diff to `themes/lafromage/interface` should show ~250–300 added lines: 3 new templates (`tpl-account`, `tpl-checkout`, `tpl-search`), 3 new routes (`#account`, `#checkout`, `#search`), 8 new methods on `SpecimenEngine` (`isLoggedIn`, `applyCustomerToDom`, `bootCustomer`, `login`, `register`, `logout`, `renderAccount`, `renderDashboard`, `renderCheckout`, `renderSearch`, `renderProductCard` — and the modified `renderCart`, `cartAdd`, `updateCartCount`), augmented `fetchStore`, new `postStore`, plus nav updates.

No new files. No backend changes. Default theme untouched.

Known follow-ups (logged here, not addressed by this plan):

1. **Order endpoint upgrade** — `state=order` should accept and persist address fields. The checkout already sends them; the API just ignores them today.
2. **Order detail view** — currently no way to inspect a single past order. Dashboard shows id/date/total/status only.
3. **Address book / change password / profile edit** — explicitly deferred per spec.
