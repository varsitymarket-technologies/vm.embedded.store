# ourchieve Online Store Port Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add real customer accounts (modal-based) + a sibling `dashboard-view` to `themes/ourchieve/interface`, and wire the existing-but-mock `placeOrder()` to the real `state=order` endpoint.

**Architecture:** Single-file vanilla-JS SPA (~948 lines). Existing 2-state SPA (`main-view` ↔ `checkout-view`) gains a third view (`dashboard-view`). Auth via centered modal using ourchieve's existing `.modal-overlay.active` CSS. Same canonical data-layer pattern as anti/oaklyn (`fetchStore` + `postStore`, `customerAuth = {user, token}`, `vm_customer_token` / `vm_customer_cache`).

**Tech Stack:** Vanilla JS, Tailwind via CDN, Inter font, Font Awesome 6 icons. Dark mode (#0A0A0C bg, #00FF88 accent green). No hash routing — function-based view toggles.

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
- `themes/ourchieve/interface` (~948 lines as of HEAD `b9a911e`)

**Do not touch:**
- `themes/ourchieve/index.html`
- Backend / admin / other themes

---

## Pre-flight

- [ ] **Step 0a: Container up**

Run: `docker ps --format "{{.Names}} {{.Status}}"` — expected: `vm-emb-sites Up ...`. If absent, `docker-compose up -d`.

- [ ] **Step 0b: Page reachable**

Browser: `http://localhost:8016/themes/ourchieve/interface` — expected: dark-mode OURCHIEVE home renders, accent-green hero, products grid loads. No JS errors.

- [ ] **Step 0c: Git state**

Run: `git status && git log -1 --oneline` — expected: branch `master`, HEAD at `b9a911e` (ourchieve spec) or later. Theme commits use `git add -f`.

---

## Task 1: Augment `fetchStore` + add `postStore` + legacy cleanup

**Files:**
- Modify: `themes/ourchieve/interface` — the `<script>` block (around line 595 onward).

- [ ] **Step 1.1: Replace `fetchStore` with token-aware + add `postStore`**

Find this block (search for `async function fetchStore`):

```js
    async function fetchStore(state, params = {}) {
        const url = new URL("api.php", window.location.href);
```

Read 12-15 lines to capture the existing body. The current `fetchStore` body matches the canonical pattern (try/catch/getMockData fallback). Replace the entire function block with:

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

Note: ourchieve's script body indents with 4 spaces (not 8 like anti/lafromage). Use 4-space indentation throughout. Verify by reading a few lines around the target.

- [ ] **Step 1.2: Insert legacy storage cleanup before `let cart = []`**

Find the line `let cart = [];` (search for that exact line). Replace with:

```js
    // One-time migration / proactive cleanup (defensive for shared-origin pollution).
    try { localStorage.removeItem('vm_user_session'); } catch {}
    try { localStorage.removeItem('vm_user_history'); } catch {}

    let cart = [];
```

- [ ] **Step 1.3: Static verification**

Run: `grep -c "postStore" themes/ourchieve/interface` — expected: `1` (declaration only).
Run: `grep -c "X-Customer-Token" themes/ourchieve/interface` — expected: `2`.
Run: `grep -c "vm_user_session" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "vm_user_history" themes/ourchieve/interface` — expected: `1`.

- [ ] **Step 1.4: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): token-aware fetchStore + postStore + legacy storage cleanup"
```

---

## Task 2: Add `customerAuth` state + helpers + `bootCustomer`

**Files:**
- Modify: `themes/ourchieve/interface` — insert state + helpers after the cleanup added in Task 1.

- [ ] **Step 2.1: Insert customerAuth + helpers + bootCustomer**

Find the exact block that Task 1 placed:

```js
    // One-time migration / proactive cleanup (defensive for shared-origin pollution).
    try { localStorage.removeItem('vm_user_session'); } catch {}
    try { localStorage.removeItem('vm_user_history'); } catch {}

    let cart = [];
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
        const icon = document.querySelector('#header-account-btn i');
        if (!icon) return;
        if (isLoggedIn()) {
            icon.className = 'fa-solid fa-circle-user text-accent';
        } else {
            icon.className = 'fa-solid fa-user';
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
                if (document.getElementById('dashboard-view')?.classList.contains('active')) {
                    showMainView();
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

    let cart = [];
```

- [ ] **Step 2.2: Static verification**

Run: `grep -c "let customerAuth" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function isLoggedIn" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function applyCustomerToDom" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "async function bootCustomer" themes/ourchieve/interface` — expected: `1`.

- [ ] **Step 2.3: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): add customerAuth state + isLoggedIn + applyCustomerToDom + bootCustomer"
```

---

## Task 3: Add `login` / `register` / `logout` methods

**Files:**
- Modify: `themes/ourchieve/interface` — insert auth methods after `bootCustomer`.

- [ ] **Step 3.1: Insert auth methods after `bootCustomer`**

Find this exact block (placed by Task 2):

```js
        } catch (e) {
            // Network failure: keep cached user, do not log out.
        }
    }

    let cart = [];
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
        showMainView();
    }

    let cart = [];
```

- [ ] **Step 3.2: Static verification**

Run: `grep -c "async function login" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "async function register" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "async function logout" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "postStore(" themes/ourchieve/interface` — expected: `4` (declaration + 3 callsites).

- [ ] **Step 3.3: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): add login/register/logout methods"
```

---

## Task 4: Insert auth modal markup

**Files:**
- Modify: `themes/ourchieve/interface` — insert auth modal in body, just before the product modal (which is around line 469 with `<div ... onclick="closeModal()"></div>` content).

- [ ] **Step 4.1: Find anchor for insertion**

Run: `grep -n 'onclick="closeModal()"' themes/ourchieve/interface | head -3` — the first occurrence is part of the product modal opener. Look one or two lines above for the modal's outer `<div>` opener.

The product modal opens with a `<div class="modal-overlay ...">`. Find its exact opening line:

Run: `grep -n 'class="modal-overlay' themes/ourchieve/interface`

This may yield 1-2 hits. The Product modal is the existing one. The auth modal will be inserted IMMEDIATELY BEFORE the existing modal-overlay div.

- [ ] **Step 4.2: Insert auth modal markup**

The simplest, safest anchor is the `<!-- Cart Drawer -->` HTML comment OR the `<!-- ... Modal -->` comment that precedes the product modal. Search for `<!-- Cart Drawer -->`:

Run: `grep -n "<!-- Cart Drawer -->" themes/ourchieve/interface`

If found (around line ~436), the auth modal goes BEFORE the Cart Drawer (so it sits in the modal/drawer cluster region). Otherwise, search for the comment right before the product modal opens.

Replace the line `<!-- Cart Drawer -->` with:

```html
    <!-- Auth Modal -->
    <div id="auth-modal" class="modal-overlay fixed inset-0 z-[80] bg-black/70 backdrop-blur-sm flex items-center justify-center px-4">
        <div class="modal-content bg-dark-800 border border-white/10 rounded-2xl shadow-2xl w-full max-w-md p-8 relative glow-border">
            <button onclick="closeAuthModal()" type="button" class="absolute top-4 right-4 p-2 bg-dark-700 rounded-full text-gray-400 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="flex gap-2 mb-8 border-b border-white/10">
                <button data-tab="login" type="button" class="auth-tab flex-1 pb-4 text-sm font-bold uppercase tracking-wider text-accent border-b-2 border-accent">Sign In</button>
                <button data-tab="register" type="button" class="auth-tab flex-1 pb-4 text-sm font-bold uppercase tracking-wider text-gray-400 border-b-2 border-transparent">Create</button>
            </div>

            <div class="auth-mode-login">
                <form class="js-login-form space-y-4" data-form="login">
                    <input type="email" name="email" placeholder="Email Address" required class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                    <input type="password" name="password" placeholder="Password" required class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                    <button type="submit" class="btn-primary w-full py-3 rounded-xl text-sm font-bold">Sign In →</button>
                    <p class="js-error text-xs text-red-400 hidden" data-err="login"></p>
                </form>
            </div>

            <div class="auth-mode-register hidden">
                <form class="js-register-form space-y-4" data-form="register">
                    <input type="text" name="name" placeholder="Display Name" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                    <input type="email" name="email" placeholder="Email Address" required class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                    <input type="password" name="password" placeholder="Password (8+ characters)" required minlength="8" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                    <button type="submit" class="btn-primary w-full py-3 rounded-xl text-sm font-bold">Create Account →</button>
                    <p class="js-error text-xs text-red-400 hidden" data-err="register"></p>
                </form>
            </div>
        </div>
    </div>

    <!-- Cart Drawer -->
```

If the `<!-- Cart Drawer -->` comment isn't present in the file, use a different anchor (the actual cart drawer's outer `<div id="cart-overlay"`). Search and adjust.

- [ ] **Step 4.3: Static verification**

Run: `grep -c 'id="auth-modal"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'data-tab="login"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'data-tab="register"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'data-form="login"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'data-form="register"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'auth-mode-login' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'auth-mode-register' themes/ourchieve/interface` — expected: `1`.

- [ ] **Step 4.4: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): insert auth modal markup (sign-in / create tabs)"
```

---

## Task 5: Add modal open/close + `wireAuthModal` + `handleAccountClick`

**Files:**
- Modify: `themes/ourchieve/interface` — insert JS functions after `logout()` (added by Task 3).

- [ ] **Step 5.1: Insert modal helpers + wireAuthModal + handleAccountClick**

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
        showMainView();
    }

    let cart = [];
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
        showMainView();
    }

    function openAuthModal(initialMode = 'login') {
        const modal = document.getElementById('auth-modal');
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        setAuthMode(initialMode);
        modal.querySelectorAll('[data-err]').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
        const activeMode = modal.querySelector(initialMode === 'register' ? '.auth-mode-register' : '.auth-mode-login');
        activeMode?.querySelector('input')?.focus();
    }

    function closeAuthModal() {
        const modal = document.getElementById('auth-modal');
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function setAuthMode(mode) {
        const modal = document.getElementById('auth-modal');
        if (!modal) return;
        modal.querySelectorAll('.auth-tab').forEach(btn => {
            const active = btn.dataset.tab === mode;
            btn.classList.toggle('text-accent', active);
            btn.classList.toggle('text-gray-400', !active);
            btn.classList.toggle('border-accent', active);
            btn.classList.toggle('border-transparent', !active);
        });
        modal.querySelector('.auth-mode-login').classList.toggle('hidden', mode !== 'login');
        modal.querySelector('.auth-mode-register').classList.toggle('hidden', mode !== 'register');
    }

    function handleAccountClick() {
        if (isLoggedIn()) {
            showDashboardView();
        } else {
            openAuthModal('login');
        }
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
            if (e.key === 'Escape' && modal.classList.contains('active')) closeAuthModal();
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
                    showDashboardView();
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
                    showDashboardView();
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

        const signOutBtn = document.getElementById('dash-signout');
        if (signOutBtn) {
            signOutBtn.addEventListener('click', async () => { await logout(); });
        }
    }

    let cart = [];
```

- [ ] **Step 5.2: Static verification**

Run: `grep -c "function openAuthModal" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function closeAuthModal" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function setAuthMode" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function handleAccountClick" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function wireAuthModal" themes/ourchieve/interface` — expected: `1`.

- [ ] **Step 5.3: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): auth modal open/close + wireAuthModal + handleAccountClick"
```

---

## Task 6: Add dashboard-view CSS + HTML

**Files:**
- Modify: `themes/ourchieve/interface` — add CSS rules + new view div.

- [ ] **Step 6.1: Add CSS for dashboard-view**

Find the exact block of CSS for checkout-view + main-view:

```css
        .checkout-view { display: none; }
        .checkout-view.active { display: block; }
        .main-view.hidden { display: none; }
```

Replace with:

```css
        .checkout-view { display: none; }
        .checkout-view.active { display: block; }
        .dashboard-view { display: none; }
        .dashboard-view.active { display: block; }
        .main-view.hidden { display: none; }
```

- [ ] **Step 6.2: Insert dashboard-view HTML alongside checkout-view**

Find this exact block (the closing of checkout-view; search for `</div>\n\n    <!-- Cart Drawer -->` or for the line right after the checkout-view div closes):

```html
    <!-- Checkout View -->
    <div id="checkout-view" class="checkout-view">
```

The checkout-view ends with several `</div>` tags before the `<!-- ` comment that follows. The simplest anchor: find the `<!-- Cart Drawer -->` comment (or whatever HTML comment follows the checkout-view closing). The auth-modal insertion from Task 4 went BEFORE `<!-- Cart Drawer -->`. The dashboard-view insertion should go between the closing of checkout-view and the auth modal.

Safer anchor: find the line BEFORE the auth modal opens. Replace:

```html
    <!-- Auth Modal -->
    <div id="auth-modal" class="modal-overlay fixed inset-0 z-[80] bg-black/70 backdrop-blur-sm flex items-center justify-center px-4">
```

with:

```html
    <!-- Dashboard View -->
    <div id="dashboard-view" class="dashboard-view">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <button onclick="showMainView()" class="flex items-center gap-2 text-gray-400 hover:text-accent transition-colors mb-8">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="text-sm font-medium">Back to Shop</span>
            </button>

            <h1 class="text-3xl font-black text-white mb-10">Account</h1>

            <div class="bg-dark-800 rounded-2xl p-6 border border-white/5 mb-10 flex justify-between items-start gap-4 flex-wrap">
                <div>
                    <p class="text-2xl font-black text-white mb-1">Hello, <span id="dash-name">Friend</span>.</p>
                    <p class="text-sm text-gray-500" id="dash-email"></p>
                </div>
                <button id="dash-signout" type="button" class="btn-outline px-5 py-2 rounded-xl text-sm font-bold">Sign Out</button>
            </div>

            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-4">Order History</h2>
            <div class="bg-dark-800 rounded-2xl p-6 border border-white/5">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10 text-left">
                            <th class="py-3 text-xs font-bold uppercase tracking-widest text-gray-500">Order</th>
                            <th class="py-3 text-xs font-bold uppercase tracking-widest text-gray-500">Date</th>
                            <th class="py-3 text-xs font-bold uppercase tracking-widest text-gray-500">Total</th>
                            <th class="py-3 text-xs font-bold uppercase tracking-widest text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody id="dash-orders-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Auth Modal -->
    <div id="auth-modal" class="modal-overlay fixed inset-0 z-[80] bg-black/70 backdrop-blur-sm flex items-center justify-center px-4">
```

- [ ] **Step 6.3: Static verification**

Run: `grep -c 'id="dashboard-view"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'id="dash-name"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'id="dash-email"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'id="dash-signout"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c 'id="dash-orders-tbody"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "\.dashboard-view" themes/ourchieve/interface` — expected: `4` (2 CSS rule selectors + 1 HTML class on the div + 1 inside JS — wait, no JS yet; just count: 2 CSS + 1 div class = 3. Acceptable: `>= 3`).

- [ ] **Step 6.4: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): add dashboard-view CSS + HTML"
```

---

## Task 7: Add `showDashboardView` + `renderDashboard` + update `showMainView` + update `goToCheckout`

**Files:**
- Modify: `themes/ourchieve/interface` — JS function additions and edits.

- [ ] **Step 7.1: Replace `showMainView` to include dashboard hide**

Find this exact block (search for `function showMainView()`):

```js
    function showMainView() {
        document.getElementById('main-view').classList.remove('hidden');
        document.getElementById('checkout-view').classList.remove('active');
        window.scrollTo(0, 0);
    }
```

Replace with:

```js
    function showMainView() {
        document.getElementById('main-view').classList.remove('hidden');
        document.getElementById('checkout-view').classList.remove('active');
        document.getElementById('dashboard-view').classList.remove('active');
        window.scrollTo(0, 0);
    }

    function showDashboardView() {
        if (!isLoggedIn()) { openAuthModal('login'); return; }
        document.getElementById('main-view').classList.add('hidden');
        document.getElementById('checkout-view').classList.remove('active');
        document.getElementById('dashboard-view').classList.add('active');
        window.scrollTo(0, 0);
        renderDashboard();
    }

    async function renderDashboard() {
        if (!isLoggedIn()) { openAuthModal('login'); return; }

        document.getElementById('dash-name').textContent = customerAuth.user.name || 'Friend';
        document.getElementById('dash-email').textContent = customerAuth.user.email || '';

        const tbody = document.getElementById('dash-orders-tbody');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-12 text-gray-500">Loading…</td></tr>`;

        let orders = [];
        try { orders = await fetchStore('customer_my_orders'); } catch { orders = []; }
        if (!Array.isArray(orders)) orders = [];

        if (orders.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-12 text-gray-500">No orders yet.</td></tr>`;
            return;
        }
        tbody.innerHTML = orders.map(o => {
            const id = o.id;
            const total = `R${Number(o.total_amount || o.total || 0).toLocaleString()}.00`;
            const status = (o.status || 'Processing').toString();
            const date = (o.created_at || '').toString().substring(0, 10);
            return `<tr class="border-b border-white/5">
                <td class="py-4 text-white font-medium">#${id}</td>
                <td class="py-4 text-gray-400">${date}</td>
                <td class="py-4 text-white">${total}</td>
                <td class="py-4"><span class="inline-block px-3 py-1 rounded-full bg-accent/10 text-accent text-xs font-bold">${status}</span></td>
            </tr>`;
        }).join('');
    }
```

- [ ] **Step 7.2: Replace `goToCheckout` to hide dashboard + prefill identity**

Find this exact block:

```js
    function goToCheckout() {
        if (cart.length === 0) return;
        toggleCart();
        setTimeout(() => {
            document.getElementById('main-view').classList.add('hidden');
            document.getElementById('checkout-view').classList.add('active');
            window.scrollTo(0, 0);
            updateCheckoutSummary();
        }, 300);
    }
```

Replace with:

```js
    function goToCheckout() {
        if (cart.length === 0) return;
        toggleCart();
        setTimeout(() => {
            document.getElementById('main-view').classList.add('hidden');
            document.getElementById('dashboard-view').classList.remove('active');
            document.getElementById('checkout-view').classList.add('active');
            window.scrollTo(0, 0);
            updateCheckoutSummary();
            if (isLoggedIn()) prefillCheckoutFromAuth();
        }, 300);
    }

    function prefillCheckoutFromAuth() {
        const user = customerAuth.user || {};
        const email = document.getElementById('co-email');
        const fname = document.getElementById('co-fname');
        const lname = document.getElementById('co-lname');
        if (email && !email.value) email.value = user.email || '';
        if ((fname && !fname.value) || (lname && !lname.value)) {
            const parts = (user.name || '').trim().split(/\s+/);
            if (fname && !fname.value) fname.value = parts[0] || '';
            if (lname && !lname.value) lname.value = parts.slice(1).join(' ') || '';
        }
    }
```

- [ ] **Step 7.3: Static verification**

Run: `grep -c "function showMainView" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function showDashboardView" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "async function renderDashboard" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function goToCheckout" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "function prefillCheckoutFromAuth" themes/ourchieve/interface` — expected: `1`.

- [ ] **Step 7.4: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): showDashboardView/renderDashboard + view-state plumbing + prefill"
```

---

## Task 8: Add header Account button + mobile menu Account link

**Files:**
- Modify: `themes/ourchieve/interface` — header icon row + mobile menu drawer.

- [ ] **Step 8.1: Add Account icon to header icon row**

Find this exact block (the Cart button + Mobile Menu button):

```html
                    <!-- Cart -->
                    <button onclick="toggleCart()" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-bag-shopping text-lg"></i>
                        <span id="cart-count" class="absolute -top-1 -right-1 bg-accent text-dark-900 text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center opacity-0 transition-opacity">0</span>
                    </button>

                    <!-- Mobile Menu -->
                    <button onclick="toggleMobileMenu()" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-bars"></i>
                    </button>
```

Replace with:

```html
                    <!-- Cart -->
                    <button onclick="toggleCart()" class="relative p-2 text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-bag-shopping text-lg"></i>
                        <span id="cart-count" class="absolute -top-1 -right-1 bg-accent text-dark-900 text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center opacity-0 transition-opacity">0</span>
                    </button>

                    <!-- Account -->
                    <button onclick="handleAccountClick()" id="header-account-btn" class="p-2 text-gray-400 hover:text-white transition-colors" title="Account">
                        <i class="fa-solid fa-user"></i>
                    </button>

                    <!-- Mobile Menu -->
                    <button onclick="toggleMobileMenu()" class="md:hidden p-2 text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-bars"></i>
                    </button>
```

- [ ] **Step 8.2: Add Account link to mobile menu**

Find this exact block in the mobile menu drawer:

```html
                <a href="#products" onclick="toggleMobileMenu()" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">Shop</a>
                <a href="#about" onclick="toggleMobileMenu()" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">About</a>
                <a href="#contact" onclick="toggleMobileMenu()" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">Contact</a>
```

Replace with:

```html
                <a href="#products" onclick="toggleMobileMenu()" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">Shop</a>
                <a href="#about" onclick="toggleMobileMenu()" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">About</a>
                <a href="#contact" onclick="toggleMobileMenu()" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">Contact</a>
                <a href="#" onclick="handleAccountClick(); toggleMobileMenu();" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">Account</a>
```

- [ ] **Step 8.3: Static verification**

Run: `grep -c 'id="header-account-btn"' themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "handleAccountClick()" themes/ourchieve/interface` — expected: `4` (declaration + 3 call sites: header button + mobile link + isLoggedIn dispatcher inline call... actually: 1 declaration + header button onclick + mobile link onclick = 3 callsites in HTML/JS + 1 from setup = 3 total. Acceptable expectation: `>= 3`. The dispatcher count varies; verify with the grep output rather than strict count).

- [ ] **Step 8.4: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): header Account icon + mobile menu Account link"
```

---

## Task 9: Add `id` attributes to checkout form inputs

**Files:**
- Modify: `themes/ourchieve/interface` — 8 input elements in the checkout view.

- [ ] **Step 9.1: Add `id="co-fname"` to First Name input**

Find:

```html
                                <input type="text" placeholder="First Name" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

Replace with:

```html
                                <input type="text" id="co-fname" placeholder="First Name" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

- [ ] **Step 9.2: Add `id="co-lname"` to Last Name input**

Find:

```html
                                <input type="text" placeholder="Last Name" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

Replace with:

```html
                                <input type="text" id="co-lname" placeholder="Last Name" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

- [ ] **Step 9.3: Add `id="co-email"` to Email Address input**

Find:

```html
                            <input type="email" placeholder="Email Address" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

Replace with:

```html
                            <input type="email" id="co-email" placeholder="Email Address" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

- [ ] **Step 9.4: Add `id="co-street"` to Street Address input**

Find:

```html
                            <input type="text" placeholder="Street Address" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

Replace with:

```html
                            <input type="text" id="co-street" placeholder="Street Address" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

- [ ] **Step 9.5: Add IDs to City / Province / Postal Code**

Find:

```html
                                <input type="text" placeholder="City" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                                <input type="text" placeholder="Province" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                                <input type="text" placeholder="Postal Code" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

Replace with:

```html
                                <input type="text" id="co-city" placeholder="City" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                                <input type="text" id="co-province" placeholder="Province" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
                                <input type="text" id="co-postal" placeholder="Postal Code" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

- [ ] **Step 9.6: Add `id="co-phone"` to Phone Number input**

Find:

```html
                            <input type="tel" placeholder="Phone Number" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

Replace with:

```html
                            <input type="tel" id="co-phone" placeholder="Phone Number" class="w-full bg-dark-700 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent/50">
```

- [ ] **Step 9.7: Static verification**

Run: `grep -c 'id="co-fname"\|id="co-lname"\|id="co-email"\|id="co-street"\|id="co-city"\|id="co-province"\|id="co-postal"\|id="co-phone"' themes/ourchieve/interface` — expected: `8`.

(Alternative: 8 separate `grep -c` runs, each `= 1`.)

- [ ] **Step 9.8: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): add id attributes to checkout form inputs"
```

---

## Task 10: Rewrite `placeOrder` to POST `state=order`

**Files:**
- Modify: `themes/ourchieve/interface` — replace placeOrder.

- [ ] **Step 10.1: Replace placeOrder**

Find:

```js
    function placeOrder() {
        showToast('Order placed successfully! Thank you for shopping with Ourchieve.');
        cart = [];
        updateCartUI();
        setTimeout(() => { showMainView(); }, 2000);
    }
```

Replace with:

```js
    async function placeOrder() {
        if (cart.length === 0) { showToast('Your cart is empty.'); return; }
        const btn = event?.target?.closest('button');
        if (btn?.disabled) return;

        const fname = document.getElementById('co-fname')?.value?.trim() || '';
        const lname = document.getElementById('co-lname')?.value?.trim() || '';
        const email = document.getElementById('co-email')?.value?.trim() || '';
        if (!fname || !lname || !email) {
            showToast('Please fill in name and email.');
            return;
        }

        const subtotal = getSubtotal();
        const shipping = subtotal >= 750 ? 0 : 99;
        const total = subtotal + shipping;

        const payload = {
            name: `${fname} ${lname}`.trim(),
            email,
            total,
            items: JSON.stringify(cart.map(i => ({ id: i.id, name: i.name, price: i.price, qty: i.qty, image: i.image })))
        };
        if (isLoggedIn() && customerAuth.user && customerAuth.user.id) {
            payload.customer_id = customerAuth.user.id;
        }

        if (btn) {
            btn.disabled = true;
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = 'PLACING ORDER...';
        }
        try {
            const result = await postStore('order', payload);
            const ok = result.ok || (result.data && result.data.success === true);
            if (ok) {
                showToast('Order placed successfully! Thank you for shopping with Ourchieve.');
                cart = [];
                updateCartUI();
                setTimeout(() => {
                    if (isLoggedIn()) { showDashboardView(); } else { showMainView(); }
                }, 1500);
            } else {
                if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.originalText; }
                showToast(result.error || (result.data && result.data.error) || 'Order could not be placed. Please try again.');
            }
        } catch (err) {
            if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.originalText; }
            showToast('Order could not be placed. Please try again.');
        }
    }
```

- [ ] **Step 10.2: Static verification**

Run: `grep -c "async function placeOrder" themes/ourchieve/interface` — expected: `1`.
Run: `grep -c "postStore('order'" themes/ourchieve/interface` — expected: `1`.

- [ ] **Step 10.3: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): placeOrder POSTs state=order with double-submit guard"
```

---

## Task 11: Wire boot calls at init

**Files:**
- Modify: `themes/ourchieve/interface` — the existing `DOMContentLoaded` handler.

- [ ] **Step 11.1: Insert wireAuthModal + bootCustomer calls**

Find the existing init handler:

```js
    document.addEventListener('DOMContentLoaded', async () => {
        try { const __d = await fetchStore('products'); products = __d.map(p => ({ ...p, name: p.name || p.title || '', image: p.image || p.img || '', description: p.description || p.desc || '', category: p.category || ((MOCK.categories.find(c => c.id == p.category_id) || {}).name) || '' })); } catch {}
        renderProducts();
        updateCartUI();
        initScrollAnimations();
    });
```

Replace with:

```js
    document.addEventListener('DOMContentLoaded', async () => {
        try { const __d = await fetchStore('products'); products = __d.map(p => ({ ...p, name: p.name || p.title || '', image: p.image || p.img || '', description: p.description || p.desc || '', category: p.category || ((MOCK.categories.find(c => c.id == p.category_id) || {}).name) || '' })); } catch {}
        renderProducts();
        updateCartUI();
        initScrollAnimations();
        wireAuthModal();
        bootCustomer();
    });
```

- [ ] **Step 11.2: Static verification**

Run: `grep -c "wireAuthModal()" themes/ourchieve/interface` — expected: `2` (declaration + init call).
Run: `grep -c "bootCustomer()" themes/ourchieve/interface` — expected: `2` (declaration + init call).

- [ ] **Step 11.3: Commit**

```bash
git add -f themes/ourchieve/interface
git commit -m "feat(ourchieve): wire wireAuthModal + bootCustomer at init"
```

---

## Task 12: End-to-end smoke test

**Files:**
- No code changes unless smoke reveals bugs.

- [ ] **Step 12.1: Reset state**

Navigate to `http://localhost:8016/themes/ourchieve/interface`. Run via browser_evaluate:

```js
localStorage.removeItem('vm_customer_token');
localStorage.removeItem('vm_customer_cache');
localStorage.removeItem('vm_user_session');
localStorage.removeItem('vm_user_history');
location.reload();
```

Verify after reload: home renders, header has Account icon (`fa-user`), dataset.customer=out, no console errors.

- [ ] **Step 12.2: Run smoke sequence**

| # | Action | Expected |
|---|---|---|
| 1 | Load home | OURCHIEVE hero, header Account icon visible, `document.body.dataset.customer === 'out'` |
| 2 | Click `#header-account-btn` | auth modal opens with Sign In tab active |
| 3 | Click `[data-tab="register"]` | register form visible, login hidden, tab style flips |
| 4 | Submit Create form with fresh email/password/name | modal closes, dashboard-view active, "Hello, <name>" visible, header icon flipped to `fa-circle-user text-accent` |
| 5 | Reload page | still logged in (cache); click Account icon → goes directly to dashboard |
| 6 | Add a product to cart, click cart, click PROCEED TO CHECKOUT | checkout-view active; #co-fname / #co-lname / #co-email prefilled from auth |
| 7 | Click PLACE ORDER | success toast, cart clears, jumps to dashboard, new order in table |
| 8 | Click Sign Out | returns to main view, header icon back to `fa-user`, dataset.customer=out |
| 9 | Open auth modal, click backdrop | modal closes |
| 10 | Open auth modal, press Esc | modal closes |
| 11 | Try POST guest order with empty name/email | toast "Please fill in name and email." |

Steps 4-7 may skip with "Backend N/A" if `/themes/ourchieve/interface` direct-serve doesn't route to the customer API (documented limitation). Mark accordingly. For steps that depend on simulated logged-in state, you can manually set `customerAuth.user` + `localStorage.vm_customer_token` to simulate auth without backend.

- [ ] **Step 12.3: Triage**

- PASS → note and continue.
- FAIL in ourchieve code (broken querySelector, JS error) → stop, fix, retest, commit as `fix(ourchieve): smoke-test issue from D.4 verification`.
- FAIL due to direct-serve backend limitation → mark "Skipped — backend N/A," continue.

- [ ] **Step 12.4: Final commit (only if step 12.3 produced fixes)**

If no fixes needed: skip. Otherwise:

```bash
git add -f themes/ourchieve/interface
git commit -m "fix(ourchieve): smoke-test issues from D.4 verification"
```

---

## Verification summary

After all tasks: ~350-400 net lines added/replaced. New: auth modal markup, postStore, customerAuth state, isLoggedIn/applyCustomerToDom/bootCustomer, login/register/logout, openAuthModal/closeAuthModal/setAuthMode/handleAccountClick/wireAuthModal, dashboard-view markup + CSS, showDashboardView, renderDashboard, prefillCheckoutFromAuth, header Account button, mobile menu Account link, 8 checkout form ID attributes. Rewritten: fetchStore (header attach), showMainView (dashboard hide), goToCheckout (dashboard hide + prefill), placeOrder (real POST + double-submit guard).

No new files. No backend changes.

Known follow-ups (logged, not addressed):

1. **`state=order` doesn't persist address fields** — same gap across all D ports. Address inputs in ourchieve's checkout form are visual-only.
2. **No order-detail click-through** — dashboard shows orders as table rows only.
3. **Address book / change password / profile edit** — deferred (consistent).
4. **Lossy name split** — `user.name` → fname/lname is one-way only.
5. **Address-fields-ignored UX** — checkout has 5 address inputs that look collected but aren't sent.
