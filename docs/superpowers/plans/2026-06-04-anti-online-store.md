# anti Online Store Port Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Surgically replace anti's mock auth + mock order history with the real `customer_*` API, using anti's existing modal infrastructure for a Sign In / Create flow.

**Architecture:** Single-file vanilla-JS SPA (`themes/anti/interface`, ~1104 lines). All views, cart drawer, fetchStore, and showView router already exist. The port replaces `userSession`/`userHistory` with `customerAuth = {user, token}`, adds an auth modal with login/register tabs (using anti's pre-built `.modal-overlay` CSS), and rewires `login`/`logout`/`renderDashboardUI`/`renderOrdersUI`/`openOrder`/`handleCheckout` to talk to the API.

**Tech Stack:** Vanilla JS, Tailwind CDN, Inter font. PHP backend (`skel/api.php`) ships every endpoint we need (`customer_login`, `customer_register`, `customer_logout`, `customer_me`, `customer_my_orders`, `state=order`) — verified by sub-projects A/B/D.1.

---

## API contract recap

| Endpoint | Method | Request | Success response |
|---|---|---|---|
| `api.php?state=customer_login` | POST | JSON `{email, password}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_register` | POST | JSON `{email, password, name?, phone?}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_logout` | POST | Header `X-Customer-Token` | `{ok:true}` |
| `api.php?state=customer_me` | GET | Header `X-Customer-Token` | `{ok:true, customer}` |
| `api.php?state=customer_my_orders` | GET | Header `X-Customer-Token` | `[ {id, customer_name, total_amount, items, status, created_at}, ... ]` (raw array, no ok wrapper) |
| `api.php?state=order` | POST | JSON `{name, email, total, items}` | `{success:true, message}` (no `ok` wrapper) |

Failure for auth/me/my_orders: `{ok:false, error}` + 4xx. Failure for `state=order`: `{error}` + 4xx.

---

## File scope

**Modify (one file):**
- `themes/anti/interface` (current: 1104 lines as of HEAD `1bae203`)

**Do not touch:**
- `themes/anti/index.html`
- Backend / admin / other themes

---

## Pre-flight

- [ ] **Step 0a: Confirm dev container is up**

Run: `docker ps --format "{{.Names}} {{.Status}}"`
Expected: line containing `vm-emb-sites Up ...`. If absent, run `docker-compose up -d`.

- [ ] **Step 0b: Confirm anti template is reachable**

Browser: `http://localhost:8016/themes/anti/interface`
Expected: dark page renders with "ANTI.CLOTHING" logo, nav (search icon, Home/Catalog/Journal/Account), hero. No JS errors in console.

- [ ] **Step 0c: Confirm git state is on `master` at HEAD `1bae203` or later**

Run: `git status && git log -1 --oneline`
Expected: branch `master`, HEAD at lafromage D.6 work or the spec commit `34e2310`. Themes are gitignored; theme edits MUST use `git add -f`.

---

## Task 1: Augment `fetchStore` + add `postStore` + legacy storage cleanup

**Files:**
- Modify: `themes/anti/interface` — the `<script>` block (around line 561 onwards), specifically the `fetchStore` function and the top of the state declarations.

This task adds data-layer plumbing and one-shot localStorage migration. No UI changes.

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

- [ ] **Step 1.2: Insert legacy storage cleanup at the very top of the script body**

Find the line `let PRODUCTS = [` (early in the `<script>` block — should be a few lines after `<script type="text/javascript">`). Immediately BEFORE the `let PRODUCTS = [` line, insert:

```js
        // One-time migration from legacy mock auth (pre-D.4 anti port).
        try { localStorage.removeItem('vm_user_session'); } catch {}
        try { localStorage.removeItem('vm_user_history'); } catch {}

```

- [ ] **Step 1.3: Static verification**

Run: `grep -c "postStore" themes/anti/interface` — expected: `1` (just the declaration; no callsites yet).
Run: `grep -c "X-Customer-Token" themes/anti/interface` — expected: `2` (fetchStore + postStore).
Run: `grep -c "vm_user_session" themes/anti/interface` — expected: `1` (the migration removeItem; the let declaration of userSession is still present but doesn't reference `vm_user_session` as a string — verify this is the only string literal).
Run: `grep -c "vm_user_history" themes/anti/interface` — expected: `2` (the migration removeItem AND the existing `let userHistory = JSON.parse(localStorage.getItem('vm_user_history'))` declaration which we'll remove in Task 2).

- [ ] **Step 1.4: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): token-aware fetchStore + postStore + legacy storage cleanup"
```

---

## Task 2: Replace state variables (`userSession` + `userHistory` → `customerAuth`)

**Files:**
- Modify: `themes/anti/interface` — the state declaration block (after Task 1's edits, around the `// STATE Logic` comment).

This task swaps the legacy state vars. No behavior changes yet — Tasks 3-9 use the new state.

- [ ] **Step 2.1: Replace declarations**

Find this block (after Task 1, look for `// STATE Logic`):

```js
        // STATE Logic
        let cart = JSON.parse(localStorage.getItem('vm_cart')) || [];
        let userHistory = JSON.parse(localStorage.getItem('vm_user_history')) || [];
        let userSession = JSON.parse(localStorage.getItem('vm_user_session')) || null;
        let currentOrderView = null;
        let currentDiscount = null; // {code: 'VM10', percent: 10}
        
        let activePdpProduct = null;
        let activePdpQty = 1;
```

Replace with:

```js
        // STATE Logic
        let cart = JSON.parse(localStorage.getItem('vm_cart')) || [];
        let customerAuth = {
            user: (() => { try { return JSON.parse(localStorage.getItem('vm_customer_cache') || 'null'); } catch { return null; } })(),
            token: localStorage.getItem('vm_customer_token')
        };
        let currentOrderView = null;
        let currentDiscount = null; // {code: 'VM10', percent: 10}

        let activePdpProduct = null;
        let activePdpQty = 1;
```

- [ ] **Step 2.2: Static verification**

Run: `grep -c "let userHistory" themes/anti/interface` — expected: `0`.
Run: `grep -c "let userSession" themes/anti/interface` — expected: `0`.
Run: `grep -c "let customerAuth" themes/anti/interface` — expected: `1`.

Note: `userHistory` and `userSession` references still exist in:
- `login()` (Task 4 replaces)
- `logout()` (Task 4 replaces)
- `renderDashboardUI()` / `renderOrdersUI()` / `openOrder()` (Task 7 replaces)
- `handleCheckout()` (Task 8 replaces)
- An `if (userSession)` block at module level near the bottom (Task 9 deletes)

After Task 2 the file has runtime ReferenceError potential when those code paths execute — expected; do NOT smoke-test between Task 2 and Task 9. Static plan checks continue to work because they read the file, not execute it.

Run: `grep -c "userHistory" themes/anti/interface` — record the count (will be >0).
Run: `grep -c "userSession" themes/anti/interface` — record the count (will be >0).

- [ ] **Step 2.3: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): replace userSession/userHistory with customerAuth"
```

---

## Task 3: Add `isLoggedIn`, `applyCustomerToDom`, `bootCustomer`

**Files:**
- Modify: `themes/anti/interface` — insert three new functions after the existing state declarations (before any function definition).

- [ ] **Step 3.1: Insert helpers + boot routine**

Find the closing `}` of `saveCart()` (small function — search for `function saveCart()`, body has `localStorage.setItem('vm_cart', JSON.stringify(cart));` etc.). Immediately AFTER `saveCart`'s closing `}`, insert:

```js

        function isLoggedIn() {
            return !!(customerAuth && customerAuth.user);
        }

        function applyCustomerToDom() {
            document.body.dataset.customer = isLoggedIn() ? 'in' : 'out';
            const nav = document.getElementById('nav-account');
            if (!nav) return;
            if (isLoggedIn()) {
                const name = (customerAuth.user.name || customerAuth.user.email || 'Account').toString();
                nav.textContent = name.length > 12 ? name.substring(0, 12) + '…' : name;
            } else {
                nav.textContent = 'Account';
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
                    const cur = document.querySelector('.view:not(.hidden)')?.id || '';
                    if (cur === 'view-dashboard' || cur === 'view-orders' || cur === 'view-order-detail') {
                        showView('shop');
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
```

- [ ] **Step 3.2: Static verification**

Run: `grep -c "function isLoggedIn" themes/anti/interface` — expected: `1`.
Run: `grep -c "function applyCustomerToDom" themes/anti/interface` — expected: `1`.
Run: `grep -c "async function bootCustomer" themes/anti/interface` — expected: `1`.

- [ ] **Step 3.3: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): add isLoggedIn, applyCustomerToDom, bootCustomer"
```

---

## Task 4: Replace `login`/`logout`, add `register`

**Files:**
- Modify: `themes/anti/interface` — the existing `login()` and `logout()` functions (currently around lines 870-887 pre-port).

- [ ] **Step 4.1: Replace `login` and `logout`, insert `register`**

Find the existing `login` + `logout` block (search for `function login()`):

```js
        function login() {
            const email = document.getElementById('login-email').value;
            if (email && email.includes('@')) {
                userSession = { email };
                localStorage.setItem('vm_user_session', JSON.stringify(userSession));
                document.getElementById('nav-account').textContent = 'Profile';
                showView('dashboard');
            } else {
                alert('Please enter a valid email.');
            }
        }

        function logout() {
            userSession = null;
            localStorage.removeItem('vm_user_session');
            document.getElementById('nav-account').textContent = 'Account';
            showView('shop');
        }
```

Replace with:

```js
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
            showView('shop');
        }
```

- [ ] **Step 4.2: Static verification**

Run: `grep -c "function login" themes/anti/interface` — expected: `1`.
Run: `grep -c "function logout" themes/anti/interface` — expected: `1`.
Run: `grep -c "function register" themes/anti/interface` — expected: `1`.
Run: `grep -c "vm_user_session" themes/anti/interface` — expected: `1` (only the cleanup removeItem from Task 1).
Run: `grep -c "userSession" themes/anti/interface` — record count; will drop further as Tasks 7-8 rewrite their references.

- [ ] **Step 4.3: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): real customer_login/register/logout via postStore"
```

---

## Task 5: Insert auth modal markup

**Files:**
- Modify: `themes/anti/interface` — insert new `<div id="auth-modal">` block in the `<body>`, near (but not inside) the existing `#checkout-modal`.

- [ ] **Step 5.1: Find the existing `#checkout-modal` opening tag for anchoring**

Run: `grep -n 'id="checkout-modal"' themes/anti/interface` — should yield one line. Note the line number.

- [ ] **Step 5.2: Insert auth modal immediately BEFORE the `#checkout-modal` opening div**

Find the line that opens `<div id="checkout-modal"` (an outer container; depending on anti's markup it may be on its own line or wrapped with other classes). Immediately BEFORE that line, insert this block (preserving anti's 4-space indentation in the `<body>` section):

```html
    <!-- AUTH MODAL -->
    <div id="auth-modal" class="modal-overlay fixed inset-0 z-[80] bg-black/60 flex items-center justify-center px-6">
        <div class="bg-brand-card border border-brand-border rounded-3xl shadow-2xl w-full max-w-md p-10 relative">
            <button onclick="closeAuthModal()" type="button" class="absolute top-6 right-6 text-brand-mute hover:text-white text-2xl leading-none">×</button>

            <div class="flex gap-2 mb-8 border-b border-brand-border">
                <button data-tab="login" type="button" class="auth-tab text-white font-bold pb-4 px-4 border-b-2 border-white">Sign In</button>
                <button data-tab="register" type="button" class="auth-tab text-brand-mute pb-4 px-4 border-b-2 border-transparent hover:text-white transition-colors">Create</button>
            </div>

            <div class="auth-mode-login">
                <form class="space-y-4 js-login-form" data-form="login">
                    <input type="email" name="email" placeholder="Email Address" required class="w-full bg-brand-bg border border-brand-border p-4 rounded-xl text-white outline-none focus:border-white transition-colors">
                    <input type="password" name="password" placeholder="Password" required class="w-full bg-brand-bg border border-brand-border p-4 rounded-xl text-white outline-none focus:border-white transition-colors">
                    <button type="submit" class="w-full bg-white text-black font-bold py-4 rounded-xl hover:bg-gray-200 transition-colors transform active:scale-[0.98]">Sign In →</button>
                    <p class="text-xs text-red-400 hidden js-login-error" data-err="login"></p>
                </form>
            </div>

            <div class="auth-mode-register hidden">
                <form class="space-y-4 js-register-form" data-form="register">
                    <input type="text" name="name" placeholder="Display Name" class="w-full bg-brand-bg border border-brand-border p-4 rounded-xl text-white outline-none focus:border-white transition-colors">
                    <input type="email" name="email" placeholder="Email Address" required class="w-full bg-brand-bg border border-brand-border p-4 rounded-xl text-white outline-none focus:border-white transition-colors">
                    <input type="password" name="password" placeholder="Password (8+ characters)" required minlength="8" class="w-full bg-brand-bg border border-brand-border p-4 rounded-xl text-white outline-none focus:border-white transition-colors">
                    <button type="submit" class="w-full bg-white text-black font-bold py-4 rounded-xl hover:bg-gray-200 transition-colors transform active:scale-[0.98]">Create Account →</button>
                    <p class="text-xs text-red-400 hidden js-register-error" data-err="register"></p>
                </form>
            </div>
        </div>
    </div>

```

- [ ] **Step 5.3: Static verification**

Run: `grep -c 'id="auth-modal"' themes/anti/interface` — expected: `1`.
Run: `grep -c 'data-tab=' themes/anti/interface` — expected: `2`.
Run: `grep -c 'data-form="login"' themes/anti/interface` — expected: `1`.
Run: `grep -c 'data-form="register"' themes/anti/interface` — expected: `1`.
Run: `grep -c 'auth-mode-login' themes/anti/interface` — expected: `1` (so far — wireAuthModal will reference it in Task 6).
Run: `grep -c 'auth-mode-register' themes/anti/interface` — expected: `1`.

- [ ] **Step 5.4: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): insert auth modal markup (sign-in / create tabs)"
```

---

## Task 6: Modal open/close + `wireAuthModal` + `handleAccountClick` rewire

**Files:**
- Modify: `themes/anti/interface` — insert new `openAuthModal`, `closeAuthModal`, `setAuthMode`, `wireAuthModal` functions; rewire existing `handleAccountClick`.

- [ ] **Step 6.1: Insert modal helpers + wireAuthModal**

Find the existing `closeCheckout()` function:

```js
        function closeCheckout() {
            document.getElementById('checkout-modal').classList.remove('open');
        }
```

Immediately AFTER its closing `}`, insert:

```js

        function openAuthModal(initialMode = 'login') {
            const modal = document.getElementById('auth-modal');
            if (!modal) return;
            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
            setAuthMode(initialMode);
            modal.querySelectorAll('[data-err]').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
            const activeMode = modal.querySelector(initialMode === 'register' ? '.auth-mode-register' : '.auth-mode-login');
            activeMode?.querySelector('input')?.focus();
        }

        function closeAuthModal() {
            const modal = document.getElementById('auth-modal');
            if (!modal) return;
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
        }

        function setAuthMode(mode) {
            const modal = document.getElementById('auth-modal');
            if (!modal) return;
            modal.querySelectorAll('.auth-tab').forEach(btn => {
                const active = btn.dataset.tab === mode;
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('text-brand-mute', !active);
                btn.classList.toggle('border-white', active);
                btn.classList.toggle('border-transparent', !active);
            });
            modal.querySelector('.auth-mode-login').classList.toggle('hidden', mode !== 'login');
            modal.querySelector('.auth-mode-register').classList.toggle('hidden', mode !== 'register');
        }

        function wireAuthModal() {
            const modal = document.getElementById('auth-modal');
            if (!modal) return;

            modal.querySelectorAll('.auth-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.querySelectorAll('[data-err]').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
                    setAuthMode(btn.dataset.tab);
                });
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeAuthModal();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('open')) closeAuthModal();
            });

            const loginForm = modal.querySelector('[data-form="login"]');
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = loginForm.querySelector('button[type="submit"]');
                if (btn.disabled) return;
                const errEl = loginForm.querySelector('[data-err="login"]');
                errEl.classList.add('hidden');
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent;
                btn.textContent = 'Signing in...';
                try {
                    const fd = new FormData(loginForm);
                    const result = await login(fd.get('email'), fd.get('password'));
                    if (result.ok) {
                        closeAuthModal();
                        showView('dashboard');
                    } else {
                        errEl.textContent = result.error;
                        errEl.classList.remove('hidden');
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText;
                    }
                } catch (err) {
                    errEl.textContent = 'Sign-in failed.';
                    errEl.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = btn.dataset.originalText;
                }
            });

            const registerForm = modal.querySelector('[data-form="register"]');
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = registerForm.querySelector('button[type="submit"]');
                if (btn.disabled) return;
                const errEl = registerForm.querySelector('[data-err="register"]');
                errEl.classList.add('hidden');
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent;
                btn.textContent = 'Creating...';
                try {
                    const fd = new FormData(registerForm);
                    const result = await register(fd.get('email'), fd.get('password'), fd.get('name'));
                    if (result.ok) {
                        closeAuthModal();
                        showView('dashboard');
                    } else {
                        errEl.textContent = result.error;
                        errEl.classList.remove('hidden');
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText;
                    }
                } catch (err) {
                    errEl.textContent = 'Could not create account.';
                    errEl.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = btn.dataset.originalText;
                }
            });
        }
```

- [ ] **Step 6.2: Rewire `handleAccountClick`**

Find:

```js
        function handleAccountClick() {
            if (userSession) {
                showView('dashboard');
            } else {
                showView('account');
            }
        }
```

Replace with:

```js
        function handleAccountClick() {
            if (isLoggedIn()) {
                showView('dashboard');
            } else {
                openAuthModal('login');
            }
        }
```

- [ ] **Step 6.3: Static verification**

Run: `grep -c "function openAuthModal" themes/anti/interface` — expected: `1`.
Run: `grep -c "function closeAuthModal" themes/anti/interface` — expected: `1`.
Run: `grep -c "function setAuthMode" themes/anti/interface` — expected: `1`.
Run: `grep -c "function wireAuthModal" themes/anti/interface` — expected: `1`.
Run: `grep -c "openAuthModal(" themes/anti/interface` — expected: `2` (declaration + handleAccountClick call). Will rise as later tasks call it from guards.

- [ ] **Step 6.4: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): auth modal open/close + wireAuthModal + handleAccountClick rewire"
```

---

## Task 7: Rewrite `renderDashboardUI`, `renderOrdersUI`, `openOrder`, `reorderCurrent`

**Files:**
- Modify: `themes/anti/interface` — four existing functions.

- [ ] **Step 7.1: Replace `renderDashboardUI`**

Find:

```js
        function renderDashboardUI() {
            if (!userSession) return showView('account');
            
            document.getElementById('dash-user-email').textContent = userSession.email;
            const userOrders = userHistory.filter(o => o.email === userSession.email);
            const totalSpent = userOrders.reduce((sum, o) => sum + o.total, 0);
            
            document.getElementById('dash-total-orders').textContent = userOrders.length;
            document.getElementById('dash-total-spent').textContent = `$${totalSpent.toFixed(2)}`;
            
            const recentContainer = document.getElementById('dash-recent-orders');
            if (userOrders.length === 0) {
                recentContainer.innerHTML = '<p class="text-brand-mute italic mt-4">No recent orders.</p>';
                return;
            }
            
            recentContainer.innerHTML = userOrders.slice(-3).reverse().map(o => `
                <div onclick="openOrder(${o.id})" class="bg-brand-card border border-brand-border p-6 rounded-2xl hover:border-white/50 transition-colors cursor-pointer flex justify-between items-center group shadow-md">
                    <div>
                        <p class="text-white font-bold text-lg mb-1 group-hover:text-white transition-colors">Order #${o.id}</p>
                        <p class="text-xs text-brand-mute uppercase tracking-widest">${o.status}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-white font-bold text-lg mb-1">$${o.total.toFixed(2)}</p>
                        <p class="text-xs text-brand-mute">${o.date}</p>
                    </div>
                </div>
            `).join('');
        }
```

Replace with:

```js
        async function renderDashboardUI() {
            if (!isLoggedIn()) { openAuthModal('login'); return; }

            document.getElementById('dash-user-email').textContent = customerAuth.user.email || '';

            let orders = [];
            try { orders = await fetchStore('customer_my_orders'); } catch { orders = []; }
            if (!Array.isArray(orders)) orders = [];

            const totalSpent = orders.reduce((sum, o) => sum + Number(o.total_amount || o.total || 0), 0);
            document.getElementById('dash-total-orders').textContent = orders.length;
            document.getElementById('dash-total-spent').textContent = '$' + totalSpent.toFixed(2);

            const recentContainer = document.getElementById('dash-recent-orders');
            if (orders.length === 0) {
                recentContainer.innerHTML = '<p class="text-brand-mute italic mt-4">No recent orders.</p>';
                return;
            }
            recentContainer.innerHTML = orders.slice(0, 3).map(o => {
                const id = o.id;
                const total = Number(o.total_amount || o.total || 0).toFixed(2);
                const status = (o.status || 'Processing').toString();
                const date = (o.created_at || '').toString().substring(0, 10);
                return `
                <div onclick="openOrder(${id})" class="bg-brand-card border border-brand-border p-6 rounded-2xl hover:border-white/50 transition-colors cursor-pointer flex justify-between items-center group shadow-md">
                    <div>
                        <p class="text-white font-bold text-lg mb-1 group-hover:text-white transition-colors">Order #${id}</p>
                        <p class="text-xs text-brand-mute uppercase tracking-widest">${status}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-white font-bold text-lg mb-1">$${total}</p>
                        <p class="text-xs text-brand-mute">${date}</p>
                    </div>
                </div>
                `;
            }).join('');
        }
```

- [ ] **Step 7.2: Replace `renderOrdersUI`**

Find:

```js
        function renderOrdersUI() {
            if (!userSession) return showView('account');
            const userOrders = userHistory.filter(o => o.email === userSession.email).reverse();
            const container = document.getElementById('orders-list');
            
            if (userOrders.length === 0) {
                container.innerHTML = '<div class="text-center py-32 border border-brand-border border-dashed rounded-3xl"><p class="text-brand-mute font-medium text-lg">No history found.</p></div>';
                return;
            }
            
            container.innerHTML = userOrders.map(o => `
                <div onclick="openOrder(${o.id})" class="bg-brand-card border border-brand-border p-6 md:p-8 rounded-2xl hover:border-white transition-colors cursor-pointer flex flex-col md:flex-row justify-between md:items-center gap-6 shadow-xl">
                    <div>
                        <p class="text-2xl text-white font-bold mb-2">Order #${o.id}</p>
                        <p class="text-sm text-brand-mute">${o.date}</p>
                    </div>
                    <div class="flex items-center gap-8 justify-between md:justify-end">
                        <div class="text-right flex flex-col items-end">
                            <p class="text-2xl text-white font-bold mb-2">$${o.total.toFixed(2)}</p>
                            <span class="inline-block bg-brand-bg border border-brand-border px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest text-brand-mute">${o.status}</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-brand-mute"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
            `).join('');
        }
```

Replace with:

```js
        async function renderOrdersUI() {
            if (!isLoggedIn()) { openAuthModal('login'); return; }

            let orders = [];
            try { orders = await fetchStore('customer_my_orders'); } catch { orders = []; }
            if (!Array.isArray(orders)) orders = [];

            const container = document.getElementById('orders-list');
            if (orders.length === 0) {
                container.innerHTML = '<div class="text-center py-32 border border-brand-border border-dashed rounded-3xl"><p class="text-brand-mute font-medium text-lg">No history found.</p></div>';
                return;
            }

            container.innerHTML = orders.map(o => {
                const id = o.id;
                const total = Number(o.total_amount || o.total || 0).toFixed(2);
                const status = (o.status || 'Processing').toString();
                const date = (o.created_at || '').toString().substring(0, 10);
                return `
                <div onclick="openOrder(${id})" class="bg-brand-card border border-brand-border p-6 md:p-8 rounded-2xl hover:border-white transition-colors cursor-pointer flex flex-col md:flex-row justify-between md:items-center gap-6 shadow-xl">
                    <div>
                        <p class="text-2xl text-white font-bold mb-2">Order #${id}</p>
                        <p class="text-sm text-brand-mute">${date}</p>
                    </div>
                    <div class="flex items-center gap-8 justify-between md:justify-end">
                        <div class="text-right flex flex-col items-end">
                            <p class="text-2xl text-white font-bold mb-2">$${total}</p>
                            <span class="inline-block bg-brand-bg border border-brand-border px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest text-brand-mute">${status}</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-brand-mute"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
                `;
            }).join('');
        }
```

- [ ] **Step 7.3: Replace `openOrder`**

Find:

```js
        function openOrder(orderId) {
            currentOrderView = userHistory.find(o => o.id === orderId);
            if (!currentOrderView) return;
            
            document.getElementById('detail-id').textContent = currentOrderView.id;
            document.getElementById('detail-date').textContent = currentOrderView.date;
            document.getElementById('detail-status').textContent = currentOrderView.status;
            document.getElementById('detail-subtotal').textContent = `$${currentOrderView.total.toFixed(2)}`;
            document.getElementById('detail-total').textContent = `$${currentOrderView.total.toFixed(2)}`;
            
            document.getElementById('detail-items').innerHTML = currentOrderView.items.map(item => `
                <div class="flex gap-6 items-center bg-brand-card p-6 rounded-2xl border border-brand-border shadow-md">
                    <div class="w-20 h-20 bg-brand-bg rounded-xl overflow-hidden flex-shrink-0 border border-brand-border">
                        <img src="${item.img}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-grow">
                        <h4 class="text-lg font-bold text-white mb-1">${item.name}</h4>
                        <p class="text-sm text-brand-mute uppercase tracking-widest font-semibold">Qty: ${item.quantity}</p>
                    </div>
                    <div class="text-lg font-bold text-white">
                        $${(item.price * item.quantity).toFixed(2)}
                    </div>
                </div>
            `).join('');
            
            showView('order-detail');
        }
```

Replace with:

```js
        async function openOrder(orderId) {
            if (!isLoggedIn()) { openAuthModal('login'); return; }

            let orders = [];
            try { orders = await fetchStore('customer_my_orders'); } catch { orders = []; }
            if (!Array.isArray(orders)) orders = [];

            currentOrderView = orders.find(o => Number(o.id) === Number(orderId));
            if (!currentOrderView) {
                showView('orders');
                return;
            }

            const total = Number(currentOrderView.total_amount || currentOrderView.total || 0).toFixed(2);
            const status = (currentOrderView.status || 'Processing').toString();
            const date = (currentOrderView.created_at || '').toString().substring(0, 10);

            document.getElementById('detail-id').textContent = currentOrderView.id;
            document.getElementById('detail-date').textContent = date;
            document.getElementById('detail-status').textContent = status;
            document.getElementById('detail-subtotal').textContent = '$' + total;
            document.getElementById('detail-total').textContent = '$' + total;

            let items = [];
            try {
                items = typeof currentOrderView.items === 'string'
                    ? JSON.parse(currentOrderView.items)
                    : (Array.isArray(currentOrderView.items) ? currentOrderView.items : []);
            } catch { items = []; }
            currentOrderView._items = items;

            document.getElementById('detail-items').innerHTML = items.map(item => `
                <div class="flex gap-6 items-center bg-brand-card p-6 rounded-2xl border border-brand-border shadow-md">
                    <div class="w-20 h-20 bg-brand-bg rounded-xl overflow-hidden flex-shrink-0 border border-brand-border">
                        <img src="${item.img || item.image || ''}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-grow">
                        <h4 class="text-lg font-bold text-white mb-1">${item.name || item.title || 'Item'}</h4>
                        <p class="text-sm text-brand-mute uppercase tracking-widest font-semibold">Qty: ${item.quantity || item.qty || 1}</p>
                    </div>
                    <div class="text-lg font-bold text-white">
                        $${(Number(item.price || 0) * Number(item.quantity || item.qty || 1)).toFixed(2)}
                    </div>
                </div>
            `).join('');

            showView('order-detail');
        }
```

- [ ] **Step 7.4: Replace `reorderCurrent`**

Find:

```js
        function reorderCurrent() {
            if(!currentOrderView) return;
            currentOrderView.items.forEach(orderItem => {
                const existing = cart.find(c => c.id === orderItem.id);
                if (existing) {
                    existing.quantity += orderItem.quantity;
                } else {
                    cart.push({ ...orderItem });
                }
            });
            saveCart();
            showView('shop');
            toggleCart();
        }
```

Replace with:

```js
        function reorderCurrent() {
            if (!currentOrderView || !Array.isArray(currentOrderView._items)) return;
            currentOrderView._items.forEach(orderItem => {
                const existing = cart.find(c => c.id === orderItem.id);
                const addQty = orderItem.quantity || orderItem.qty || 1;
                if (existing) {
                    existing.quantity = (existing.quantity || 1) + addQty;
                } else {
                    cart.push({
                        id: orderItem.id,
                        name: orderItem.name || orderItem.title || 'Item',
                        price: Number(orderItem.price || 0),
                        quantity: addQty,
                        img: orderItem.img || orderItem.image || ''
                    });
                }
            });
            saveCart();
            showView('shop');
            toggleCart();
        }
```

- [ ] **Step 7.5: Static verification**

Run: `grep -c "userHistory" themes/anti/interface` — expected: `1` (only the migration `removeItem('vm_user_history')` from Task 1).
Run: `grep -c "userSession" themes/anti/interface` — expected: `1` (only the module-level `if (userSession)` block near the bottom, deleted in Task 9).
Run: `grep -c "async function renderDashboardUI" themes/anti/interface` — expected: `1`.
Run: `grep -c "async function renderOrdersUI" themes/anti/interface` — expected: `1`.
Run: `grep -c "async function openOrder" themes/anti/interface` — expected: `1`.
Run: `grep -c "fetchStore('customer_my_orders')" themes/anti/interface` — expected: `3` (renderDashboardUI, renderOrdersUI, openOrder).
Run: `grep -c "currentOrderView._items" themes/anti/interface` — expected: `2` (openOrder set, reorderCurrent read).

- [ ] **Step 7.6: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): dashboard/orders/openOrder/reorderCurrent fed by customer_my_orders"
```

---

## Task 8: Rewrite `handleCheckout` to POST `state=order` with double-submit guard

**Files:**
- Modify: `themes/anti/interface` — replace `handleCheckout`.

- [ ] **Step 8.1: Replace `handleCheckout`**

Find:

```js
        function handleCheckout(e) {
            e.preventDefault();
            
            // Get email if provided in form
            const emailInput = document.querySelector('input[type="email"]').value;
            const userEmail = userSession ? userSession.email : (emailInput || 'guest@example.com');
            
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const totalDiscount = currentDiscount ? (subtotal * currentDiscount.percent / 100) : 0;
            const finalP = subtotal - totalDiscount;
            
            const newOrder = {
                id: Math.floor(Math.random() * 90000) + 10000,
                email: userEmail,
                date: new Date().toLocaleDateString(),
                status: 'Processing',
                items: [...cart],
                total: finalP
            };
            
            userHistory.push(newOrder);
            localStorage.setItem('vm_user_history', JSON.stringify(userHistory));

            alert("Order confirmed! Thank you for choosing VMTECH.");
            cart = [];
            saveCart();
            closeCheckout();
            currentDiscount = null; // reset discount
            
            // If logged in, go to dashboard, else shop
            if(userSession && userSession.email === userEmail) {
                showView('dashboard');
            } else {
                showView('shop');
            }
        }
```

Replace with:

```js
        async function handleCheckout(e) {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn && submitBtn.disabled) return;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent;
                submitBtn.textContent = 'Placing order...';
            }

            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const totalDiscount = currentDiscount ? (subtotal * currentDiscount.percent / 100) : 0;
            const finalP = subtotal - totalDiscount;

            const emailInput = document.querySelector('#checkout-modal input[type="email"]')?.value || '';
            const user = customerAuth.user || {};
            const payload = {
                name: user.name || 'CUSTOMER',
                email: user.email || emailInput || 'guest@example.com',
                total: finalP,
                items: cart.map(c => ({
                    id: c.id,
                    name: c.name,
                    price: c.price,
                    quantity: c.quantity,
                    img: c.img
                }))
            };

            try {
                const result = await postStore('order', payload);
                const ok = result.ok || (result.data && result.data.success === true);
                if (ok) {
                    cart = [];
                    saveCart();
                    closeCheckout();
                    currentDiscount = null;
                    if (isLoggedIn()) {
                        showView('dashboard');
                    } else {
                        showView('shop');
                    }
                    alert('Order confirmed. Thank you.');
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.originalText || 'Place Order';
                    }
                    alert(result.error || (result.data && result.data.error) || 'Order could not be placed. Please try again.');
                }
            } catch (err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.dataset.originalText || 'Place Order';
                }
                alert('Order could not be placed. Please try again.');
            }
        }
```

- [ ] **Step 8.2: Static verification**

Run: `grep -c "async function handleCheckout" themes/anti/interface` — expected: `1`.
Run: `grep -c "postStore('order'" themes/anti/interface` — expected: `1`.
Run: `grep -c "userHistory" themes/anti/interface` — expected: `1` (only the cleanup removeItem; ALL business logic references gone).
Run: `grep -c "userSession" themes/anti/interface` — expected: `1` (still the bottom `if (userSession)` block — Task 9 deletes it).
Run: `grep -c "vm_user_history" themes/anti/interface` — expected: `1` (only the cleanup removeItem).

- [ ] **Step 8.3: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): handleCheckout posts state=order with double-submit guard"
```

---

## Task 9: Delete `view-account`, fix `showView` nav-opacity, integrate `bootCustomer`/`wireAuthModal` in IIFE

**Files:**
- Modify: `themes/anti/interface` — remove dead view, two small JS edits.

- [ ] **Step 9.1: Delete `view-account` HTML block**

Find this block (currently around line 130-138 in the markup section):

```html
    <div id="view-account" class="view hidden pt-40 pb-40 px-6 max-w-lg mx-auto text-center">
        <h1 class="text-4xl font-bold text-white mb-4">Welcome</h1>
        <p class="text-brand-mute mb-10">Sign in to manage your orders and preferences.</p>
        <div class="bg-brand-card border border-brand-border p-8 rounded-2xl shadow-2xl">
            <input type="email" id="login-email" placeholder="Email Address" class="w-full bg-brand-bg border border-brand-border p-4 rounded-xl text-white outline-none focus:border-white transition-colors mb-4">
            <button onclick="login()" class="w-full bg-white text-black font-bold py-4 rounded-xl hover:bg-gray-200 transition-colors mb-4 transform active:scale-[0.98]">Login to Account</button>
            <p class="text-[10px] text-brand-mute uppercase tracking-widest">Secure 1-click access</p>
        </div>
    </div>
```

Delete it entirely (also remove the preceding blank line if present).

- [ ] **Step 9.2: Update `showView` nav-opacity calc**

Find inside `showView`:

```js
            // Update Nav account status
            const accountNav = document.getElementById('nav-account');
            accountNav.style.opacity = (viewId !== 'shop' && viewId !== 'account') ? '1' : '';
```

Replace with:

```js
            // Update Nav account status
            const accountNav = document.getElementById('nav-account');
            accountNav.style.opacity = (viewId !== 'shop') ? '1' : '';
```

- [ ] **Step 9.3: Delete stale `if (userSession)` Profile block + add bootCustomer / wireAuthModal at module level**

Anti's script tail (post-Task 2, with `userSession` undefined since Task 2's rename) has this structure at module level (currently around lines 1083-1100):

```js
        // Hydrate via canonical data contract (api.php → mock fallback), then re-render
        (async () => {
            try {
                const __d = await fetchStore('products');
                PRODUCTS = __d.map(p => ({ ...p, name: p.name || p.title || '', img: p.img || p.image || '', desc: p.desc || p.description || '', category: p.category || ((MOCK.categories.find(c => c.id == p.category_id) || {}).name) || '' }));
                filterCollection('all');
            } catch {}
        })();

        // Initialize App
        setupCatalog();
        filterCollection('all'); // Initialize collection view
        renderCart();
        updateCartIndicator();
        
        if (userSession) {
            document.getElementById('nav-account').textContent = 'Profile';
        }
```

Step 9.3a: DELETE the `if (userSession) { ... }` block (3 lines + the blank line above it):

```js
        
        if (userSession) {
            document.getElementById('nav-account').textContent = 'Profile';
        }
```

Step 9.3b: Immediately AFTER the existing `updateCartIndicator();` line (after the "Initialize App" block, in the position where the deleted `if (userSession)` used to be), insert:

```js

        wireAuthModal();
        bootCustomer();
```

`bootCustomer` is async but we don't `await` it here — it runs in the background; the cached user (if any) is rendered synchronously by its first `applyCustomerToDom` call before the `customer_me` fetch resolves. This matches lafromage's pattern.

- [ ] **Step 9.4: Static verification**

Run: `grep -c 'id="view-account"' themes/anti/interface` — expected: `0`.
Run: `grep -c "'Profile'" themes/anti/interface` — expected: `0` (no other Profile literals were anywhere).
Run: `grep -c "wireAuthModal()" themes/anti/interface` — expected: `2` (declaration + the module-level call).
Run: `grep -c "bootCustomer()" themes/anti/interface` — expected: `2` (declaration + the module-level call).
Run: `grep -c "viewId !== 'account'" themes/anti/interface` — expected: `0`.
Run: `grep -c "viewId !== 'shop'" themes/anti/interface` — expected: `1` (the simplified opacity calc).
Run: `grep -c "userSession" themes/anti/interface` — expected: `0` (last reference gone).
Run: `grep -c "userHistory" themes/anti/interface` — expected: `1` (only the migration removeItem from Task 1 remains).

- [ ] **Step 9.5: Commit**

```bash
git add -f themes/anti/interface
git commit -m "feat(anti): delete view-account, fix showView opacity, wire boot at init"
```

---

## Task 10: End-to-end smoke test

**Files:**
- No code changes unless smoke reveals bugs. If a fix is needed, commit it separately.

This task uses Playwright MCP to validate the spec's smoke sequence end-to-end.

- [ ] **Step 10.1: Reset state**

Navigate browser to `http://localhost:8016/themes/anti/interface`. Run via browser_evaluate:

```js
localStorage.removeItem('vm_customer_token');
localStorage.removeItem('vm_customer_cache');
localStorage.removeItem('vm_cart');
localStorage.removeItem('vm_user_session');
localStorage.removeItem('vm_user_history');
location.reload();
```

After reload, take a snapshot. Verify: hero "ANTI.CLOTHING" branding visible, nav shows `Account` label (NOT `Profile`).

- [ ] **Step 10.2: Run 11-step smoke**

| # | Action | Expected |
|---|---|---|
| 1 | Load home | ANTI.CLOTHING hero; nav `Account` label; `document.body.dataset.customer === 'out'` |
| 2 | Click `Account` (logged-out) | auth modal opens; Sign In tab active; email/password inputs visible |
| 3 | Switch to Create tab | register form shown (name/email/password); login form hidden |
| 4 | Submit Create with fresh email (`antitest+<ts>@example.com`), password (`testpass123`), name (`Test User`) | modal closes; dashboard renders; nav label updated to "Test User" (or first 12 chars); `dataset.customer === 'in'` |
| 5 | Reload page | dashboard still accessible via `Account` click; nav label still "Test User"; no flicker |
| 6 | Click `Account` (logged-in) | routes to dashboard |
| 7 | Open PDP for any product → add to bag; do same for a second product. Open cart drawer → click Checkout → fill email if shown → Place Order | success alert; dashboard reappears; new order in Recent Orders |
| 8 | Click `View All` link in dashboard | orders list renders, includes the new order |
| 9 | Click an order row | order-detail view with items + total |
| 10 | Click Sign Out in dashboard | returns to shop; nav label back to `Account`; `dataset.customer === 'out'` |
| 11 | Click backdrop on auth modal (open it first, then click outside the card) | modal closes |

Steps 4-9 may fail with "Customer auth module not loaded" or 401 in direct-serve mode at `/themes/anti/interface` — this is the documented engine routing limitation. Mark "Skipped — backend N/A" and continue with the auth-independent steps.

- [ ] **Step 10.3: Triage**

- PASS — note and continue.
- FAIL in anti code (missing element id, broken querySelector, JS console error) → stop, fix immediately, retest, commit fix as `fix(anti): smoke-test issue from D.4 verification`.
- FAIL because backend unreachable from direct serve → mark "Skipped — backend N/A," continue.

- [ ] **Step 10.4: Final commit (only if step 10.3 produced fixes)**

If no fixes were needed, skip. Otherwise:

```bash
git add -f themes/anti/interface
git commit -m "fix(anti): smoke-test issues from D.4 verification"
```

---

## Verification summary

After all tasks: ~250-300 net lines added/replaced in `themes/anti/interface`. New: auth modal markup, postStore, customerAuth state, isLoggedIn/applyCustomerToDom/bootCustomer, register, openAuthModal/closeAuthModal/setAuthMode/wireAuthModal. Rewritten: fetchStore (header attach), login, logout, renderDashboardUI, renderOrdersUI, openOrder, reorderCurrent, handleCheckout, handleAccountClick, showView (opacity calc). Deleted: `view-account` HTML, `userSession`/`userHistory` state, `vm_user_session`/`vm_user_history` business-logic localStorage writes, stale `'Profile'` literal.

No new files. No backend changes.

Known follow-ups (logged, NOT addressed by this plan):

1. **Order endpoint upgrade** — `state=order` does not persist address fields (consistent gap across all D ports).
2. **Single-order endpoint** — `openOrder` re-fetches the full list; a `customer_order_by_id` endpoint would be cleaner.
3. **Address book / change password / profile edit** — deferred (consistent with all D ports).
4. **Membership status** — `VIP` is a static literal in the dashboard markup; no real tier system.
5. **Discount codes** — `applyDiscount` is unchanged (hardcoded `VIP20` / `WELCOME`); server has `state=discounts` but anti doesn't consult it.
