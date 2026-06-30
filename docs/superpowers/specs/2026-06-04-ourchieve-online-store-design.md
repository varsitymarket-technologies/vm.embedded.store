# ourchieve — Online Store Port (Sub-project D.4 / ourchieve)

**Date:** 2026-06-04
**Status:** Approved
**Owner:** keenan@doneros.co
**Parent decomposition:** Third and final D.4 theme. anti shipped at `847d0e9`, oaklyn at `c0a5d33`. Completes the "themes become Shopify-style online stores" initiative across all auth-less commerce themes.

## Summary

Add customer accounts + wire the existing-but-mock checkout to the real API in `themes/ourchieve/interface` (~948 lines — smallest of the three D.4 themes). Ourchieve has a polished commerce surface (product modal, cart drawer, search, 2-state SPA: `main-view` ↔ `checkout-view`) but **zero auth** and a **placeholder `placeOrder()` that doesn't POST** — it just shows a toast and clears the cart.

Key changes:

1. Augment `fetchStore` with bearer token + add `postStore` (canonical pattern from anti/lafromage/oaklyn).
2. Add `customerAuth = {user, token}` state + `vm_customer_token` / `vm_customer_cache` localStorage.
3. Add a centered auth modal using ourchieve's existing `.modal-overlay.active` CSS — two tabs (Sign In / Create).
4. Add `view-state` for dashboard: new `<div id="dashboard-view" class="dashboard-view">` as a third sibling to `main-view` and `checkout-view`. `showDashboardView()` toggles like `goToCheckout()` does today.
5. Add `bootCustomer()` + `isLoggedIn()` + `applyCustomerToDom()`. Header Account icon flips between `fa-user` (logged out) and `fa-circle-user text-accent` (logged in).
6. Add `login(email, password)` / `register(email, password, name)` / `logout()`.
7. Add header Account icon button between Cart and Mobile Menu icons + Account link in mobile menu drawer.
8. Wire `placeOrder()` to POST `state=order` with `customer_id` when logged in. Validate name + email. Double-submit guard. Show server error feedback.
9. Add `id` attributes to existing checkout form inputs so JS can read them. Prefill identity inputs from `customerAuth.user` when logged in via `prefillCheckoutFromAuth()` called from `goToCheckout()`.

## Non-goals

- New views beyond `dashboard-view`. Existing main view / product modal / cart drawer / search untouched.
- Address book, change-password, profile-edit, password-reset — deferred (consistent with all D ports).
- Address persistence — `state=order` doesn't accept addresses today. Address fields in the checkout form remain visual-only (their values are not sent to the backend). Documented as a follow-up.
- Backend changes — sub-projects A/B/D.1 already shipped every endpoint used here.
- `themes/ourchieve/index.html` — separate file, not the engine target. Untouched.

## Architecture decision

**Modal overlay for auth + new view for dashboard** — user-selected from two-option choices during brainstorming.

Why modal for auth: ourchieve already has `.modal-overlay.active` CSS infrastructure with a scale-and-fade animation built for the product detail modal. Reusing it for auth is a natural fit; the modal pattern is canonical in this codebase.

Why a new view for dashboard: ourchieve's existing pattern is binary (`main-view` ↔ `checkout-view`) via `.hidden` and `.active` toggles. Adding a third sibling matches that pattern exactly. A modal-based dashboard would feel cramped for an order history table.

Trade-off: ourchieve doesn't share `skel/vm-customer.js` updates automatically. Acceptable — same trade-off taken for anti/lafromage/oaklyn.

## Components

The port modifies these concerns in `themes/ourchieve/interface`:

1. **Data layer** — `fetchStore` (augment), `postStore` (new).
2. **State** — `customerAuth = {user, token}` (new), inserted alongside `let cart = []`.
3. **Auth UI** — new `#auth-modal` markup + `openAuthModal()` / `closeAuthModal()` / `setAuthMode()` / `wireAuthModal()`.
4. **Auth actions** — `login`, `register`, `logout` — `postStore`-backed.
5. **Boot** — `bootCustomer()` runs once at end of init; hydrates from cache, verifies via `customer_me`, dispatches events.
6. **Header + mobile menu** — new Account icon button (header) + Account link (mobile menu drawer).
7. **Dashboard** — new `<div id="dashboard-view">` markup + `showDashboardView()` + `renderDashboard()`.
8. **View-state plumbing** — `showMainView()` and `goToCheckout()` updated to also toggle `dashboard-view`.
9. **Checkout wiring** — form input IDs added + `prefillCheckoutFromAuth()` called from `goToCheckout()` + `placeOrder()` rewritten to POST `state=order` with double-submit guard.

## API contract recap

Same endpoints used by all D ports — already verified in sub-project B's tests:

| Endpoint | Method | Request | Success response |
|---|---|---|---|
| `api.php?state=customer_login` | POST | JSON `{email, password}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_register` | POST | JSON `{email, password, name?, phone?}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_logout` | POST | Header `X-Customer-Token` | `{ok:true}` |
| `api.php?state=customer_me` | GET | Header `X-Customer-Token` | `{ok:true, customer}` |
| `api.php?state=customer_my_orders` | GET | Header `X-Customer-Token` | `[ {id, customer_name, total_amount, items, status, created_at}, ... ]` (raw array) |
| `api.php?state=order` | POST | JSON `{name, email, total, items, customer_id?}` | `{success:true, message}` (no `ok` wrapper) |

Failure: `{ok:false, error}` + 4xx for auth/me/my_orders; `{error}` + 4xx for `state=order`.

## Data flow

### Read path

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
```

### Write path

```js
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

## Persisted client state

- `vm_customer_token` — bearer token (canonical shared key)
- `vm_customer_cache` — last-known customer JSON
- (Ourchieve has no existing cart localStorage key — cart is in-memory only. Not changed.)

Defensive legacy cleanup at top of script (consistent with anti/oaklyn):

```js
try { localStorage.removeItem('vm_user_session'); } catch {}
try { localStorage.removeItem('vm_user_history'); } catch {}
```

## State variable

Inserted before the existing `let cart = []`:

```js
let customerAuth = {
    user: (() => { try { return JSON.parse(localStorage.getItem('vm_customer_cache') || 'null'); } catch { return null; } })(),
    token: localStorage.getItem('vm_customer_token')
};
```

## Helper functions

```js
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
```

Ourchieve's header icons are icon-only (no text labels), so the affordance is the icon swap + accent-green color when logged in.

## Boot routine

```js
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
            // If currently on dashboard-view, send to main:
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
```

## Auth methods

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
    showMainView();
}
```

## Auth modal markup

Inserted in `<body>` alongside the existing product modal:

```html
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
```

## Modal open/close + wireAuthModal

```js
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
}
```

## Header Account button + handleAccountClick

Inserted between Cart button and Mobile Menu button in the existing `<div class="flex items-center gap-4">` icon row:

```html
<!-- Account -->
<button onclick="handleAccountClick()" id="header-account-btn" class="p-2 text-gray-400 hover:text-white transition-colors" title="Account">
    <i class="fa-solid fa-user"></i>
</button>
```

Plus an Account link in the mobile menu drawer (alongside Shop / About / Contact):

```html
<a href="#" onclick="handleAccountClick(); toggleMobileMenu();" class="text-lg font-medium text-gray-300 hover:text-accent transition-colors">Account</a>
```

```js
function handleAccountClick() {
    if (isLoggedIn()) {
        showDashboardView();
    } else {
        openAuthModal('login');
    }
}
```

## Dashboard view

CSS additions inside the existing `<style>` block, alongside `.checkout-view`:

```css
.dashboard-view { display: none; }
.dashboard-view.active { display: block; }
```

HTML — new `<div id="dashboard-view" class="dashboard-view">` inserted alongside `checkout-view`:

```html
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
```

## View-state plumbing

`showMainView()` updated to also hide dashboard-view:

```js
function showMainView() {
    document.getElementById('main-view').classList.remove('hidden');
    document.getElementById('checkout-view').classList.remove('active');
    document.getElementById('dashboard-view').classList.remove('active');
    window.scrollTo(0, 0);
}
```

`showDashboardView()` (new):

```js
function showDashboardView() {
    if (!isLoggedIn()) { openAuthModal('login'); return; }
    document.getElementById('main-view').classList.add('hidden');
    document.getElementById('checkout-view').classList.remove('active');
    document.getElementById('dashboard-view').classList.add('active');
    window.scrollTo(0, 0);
    renderDashboard();
}
```

`goToCheckout()` updated to also hide dashboard:

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
```

## renderDashboard

```js
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

Sign-out button wiring (inside `wireAuthModal` — runs once at boot):

```js
const signOutBtn = document.getElementById('dash-signout');
if (signOutBtn) {
    signOutBtn.addEventListener('click', async () => { await logout(); });
}
```

## Checkout form ID additions

Replace the 8 existing checkout form inputs with the same markup but each gets an `id` attribute: `co-fname`, `co-lname`, `co-email`, `co-street`, `co-city`, `co-province`, `co-postal`, `co-phone`. All other classes/attributes preserved.

## prefillCheckoutFromAuth

```js
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

## placeOrder rewrite

Replace the current mock placeOrder:

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

Notable choices:
- Validates only `fname`/`lname`/`email` (the rest of the form is visual-only address fields the backend doesn't persist).
- Double-submit guard via button disable + label swap to `PLACING ORDER...`.
- On success: clear cart, jump to dashboard (if logged in) or back to shop (guest).
- Error path re-enables button so user can retry.

## Boot integration

In the existing `DOMContentLoaded` IIFE at the bottom (which already calls `renderProducts()` etc. after the products fetch), add the wire + boot calls:

```js
wireAuthModal();
bootCustomer();
```

These run after the existing init so the auth modal element exists when wireAuthModal queries it.

## Events

Two custom events on `document`, consistent with all D ports:

- `vm:customer-login` (`detail: customer`) — dispatched by login, register, bootCustomer success
- `vm:customer-logout` — dispatched by logout, bootCustomer 401 branch, bootCustomer no-token branch

## Error handling

- `fetchStore` network failures fall through to mock — unchanged.
- `postStore` failures return `{ok: false}` with user-facing error.
- 401 from `customer_me` clears state + redirects off dashboard.
- Form submit handlers re-enable buttons on failure.
- `placeOrder` shows error via `showToast` and re-enables the PLACE ORDER button so the user can retry.

## Testing approach

Browser-based smoke (Playwright MCP). Direct serve at `http://localhost:8016/themes/ourchieve/interface`. Auth-gated steps may skip per documented direct-serve limitation.

Smoke sequence:

1. Load home, verify hero renders, header has Account icon (`fa-user`), dataset.customer=out.
2. Click Account icon → auth modal opens, Sign In tab active.
3. Switch to Create tab → register form visible, login hidden.
4. Submit Create with fresh email/password/name → modal closes, dashboard-view active, greeting shows "Hello, <name>.", header icon flipped to `fa-circle-user text-accent`.
5. Reload page → still logged in (cache); click Account → goes directly to dashboard.
6. Add a product to cart, open cart drawer, click checkout → checkout view active, fname/lname/email prefilled.
7. Click PLACE ORDER (with prefilled values) → toast shows success, cart clears, jumps to dashboard, new order in table.
8. Click Sign Out → returns to main view, header icon back to outline `fa-user`, dataset.customer=out.
9. Open auth modal, click backdrop → closes. Open again, press Esc → closes.
10. Try POST guest order without name/email → toast "Please fill in name and email."

## File scope

**Modified (one file):**
- `themes/ourchieve/interface` (~948 lines pre-port)

**Untouched:**
- `themes/ourchieve/index.html`
- Backend / admin / other themes

**Created:**
- None.

## Known follow-ups

1. **Order endpoint** doesn't persist address fields — same gap across all D ports. Address inputs on the checkout form are visual-only.
2. **No order-detail click-through** — dashboard shows orders as table rows only. Could add later.
3. **Address book / change password / profile edit** — deferred (consistent).
4. **Lossy name split** — `user.name` → fname/lname is one-way.
5. **Address-fields-ignored UX** — the checkout form has 5 address inputs (street/city/province/postal/phone) that look like they're collected but aren't sent to the backend. Could either hide them until the backend persists addresses, OR keep the current "looks normal" UX. Spec keeps them as-is per ourchieve's existing visual design.

## Open questions resolved during brainstorming

- **Auth UI shape** → Modal overlay (uses ourchieve's existing `.modal-overlay.active` CSS)
- **Dashboard location** → New `dashboard-view` sibling to `main-view` and `checkout-view`
- **Checkout wiring** → Wire to real API + prefill + double-submit guard
