# anti — Online Store Port (Sub-project D.4 / anti)

**Date:** 2026-06-04
**Status:** Approved
**Owner:** keenan@doneros.co
**Parent decomposition:** First of three D.4 themes (`anti`, then `oaklyn`, then `ourchieve`). Same parent initiative as D.5 (`hastings.ego`) and D.6 (`lafromage`): convert pre-built theme designs into real Shopify-style storefronts wired to the customer auth + orders API shipped in sub-projects A, B, and D.1.

## Summary

Replace `themes/anti/interface`'s mock auth and mock order history with the real `customer_*` API. Anti's design surface — header, cart drawer, product grids, dashboard, orders, order-detail, checkout modal — is already complete. The work is **surgical replacement** of the data layer, not new view construction.

Key changes:

1. Augment `fetchStore` with bearer token + add `postStore` for mutations (same plumbing as lafromage).
2. Replace `let userSession = ...` / `let userHistory = ...` with a single `customerAuth = {user, token}` backed by `vm_customer_token` + `vm_customer_cache`.
3. Add a new auth modal (`#auth-modal`) using anti's existing `.modal-overlay` CSS infrastructure. Two tabs: Sign In and Create. Replaces the current 1-input "magic-link style" `view-account`.
4. Rewrite `login()` / `logout()` and add `register()` to hit `customer_login` / `customer_register` / `customer_logout` via `postStore`.
5. Add `bootCustomer()` running once at init — hydrate from cache, verify via `customer_me`, dispatch DOM events.
6. Rewrite `renderDashboardUI()`, `renderOrdersUI()`, `openOrder()` to source from `customer_my_orders`. Drop `userHistory` entirely.
7. Rewrite `handleCheckout()` to POST `state=order` with double-submit guard. Delete the `userHistory` localStorage write.
8. Delete `view-account` (replaced by the modal). Remove dead references in `showView`.

## Non-goals

- New views or visual redesigns. Existing anti aesthetic preserved verbatim.
- Address book, change-password, profile-edit, password-reset — deferred to a later pass (matches lafromage and hastings.ego deferrals).
- Address fields at checkout — anti's checkout never had them, and the `state=order` endpoint doesn't persist addresses anyway. Skipped per user decision during brainstorming.
- Backend changes — sub-projects A/B/D.1 already shipped every endpoint used here.
- `themes/anti/index.html` — separate standalone file unused by the engine, not touched.

## Architecture decision

**Modal overlay for auth, inline extension for engine code** — user-selected from three options during brainstorming.

Why modal: anti already has `.modal-overlay` CSS with `.open` state and one in-use modal (`#checkout-modal`). The pattern is established; adding a sibling modal is mechanical. Tabs handle login/register in one surface rather than two views.

Why drop `view-account`: with the modal handling login + register, the existing single-input `view-account` "magic-link" page is dead weight. Cleaner to delete than leave as a dead route.

Trade-off: anti will not share `skel/vm-customer.js` updates automatically (same trade-off accepted for lafromage and hastings.ego — small auth surface, established per-theme inlining convention).

## Components

The port modifies these concerns in `themes/anti/interface`:

1. **Data layer** — `fetchStore` (augment), `postStore` (new). Module-scope, sibling functions.
2. **State** — `customerAuth = {user, token}` replaces `userSession`. `userHistory` deleted entirely. `cart` and `currentDiscount` untouched.
3. **Auth UI** — new `#auth-modal` markup + `openAuthModal()` / `closeAuthModal()` + `wireAuthModal()` runs once. `view-account` deleted.
4. **Auth actions** — `login(email, password)`, `register(email, password, name)`, `logout()` — all async, all `postStore`-backed.
5. **Boot** — `bootCustomer()` runs once after products hydration; hydrates from cache, verifies via `customer_me`, dispatches events.
6. **Dashboard/orders** — `renderDashboardUI`, `renderOrdersUI`, `openOrder` rewritten to consume `customer_my_orders`.
7. **Checkout** — `handleCheckout` POSTs `state=order` with double-submit guard.

## API contract (recap)

Same endpoints as lafromage's port — already verified in sub-project B's tests:

| Endpoint | Method | Request | Response (success) |
|---|---|---|---|
| `api.php?state=customer_login` | POST | JSON `{email, password}` | `{ok: true, customer, token, expires_at}` |
| `api.php?state=customer_register` | POST | JSON `{email, password, name?, phone?}` | `{ok: true, customer, token, expires_at}` |
| `api.php?state=customer_logout` | POST | Header `X-Customer-Token` | `{ok: true}` |
| `api.php?state=customer_me` | GET | Header `X-Customer-Token` | `{ok: true, customer}` |
| `api.php?state=customer_my_orders` | GET | Header `X-Customer-Token` | `[ { id, customer_name, total_amount, items, status, created_at }, ... ]` (raw array, no `ok` wrapper) |
| `api.php?state=order` | POST | JSON `{name, email, total, items}` | `{success: true, message: "..."}` |

Failure shapes:
- Auth/me/my_orders: `{ok: false, error: "..."}` + 4xx.
- `state=order`: `{error: "..."}` + 4xx (note: no `success: false` wrapper).

## Data flow

### Read path

`fetchStore(state, params)` keeps its existing mock fallback behavior. New: attach `X-Customer-Token: <localStorage.vm_customer_token>` when present.

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

`postStore(state, body)` for mutations. Returns `{ok, status, data, error}` shape.

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

Three `localStorage` keys:

- `vm_customer_token` — bearer token from `customer_login` / `customer_register` (canonical, shared with lafromage + hastings.ego)
- `vm_customer_cache` — last-known `customer_me.customer` JSON
- `vm_cart` — anti's existing cart key (unchanged)

Dropped:
- `vm_user_session` — legacy mock auth; one-time cleanup on first load
- `vm_user_history` — legacy mock orders; one-time cleanup on first load

## Auth modal markup

Inserted into `<body>` (anywhere outside other views, conventionally near the existing `checkout-modal`):

```html
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

Open/close mechanics mirror the existing `checkout-modal`: `.modal-overlay.open` triggers fade-in via CSS. Backdrop click + `×` button + Esc key all close. Same pattern as `closeCheckout`.

## State variables

Replace existing declarations (current lines 630-631 approximate):

```js
// Old (delete):
// let userHistory = JSON.parse(localStorage.getItem('vm_user_history')) || [];
// let userSession = JSON.parse(localStorage.getItem('vm_user_session')) || null;

// New:
let customerAuth = {
    user: (() => { try { return JSON.parse(localStorage.getItem('vm_customer_cache') || 'null'); } catch { return null; } })(),
    token: localStorage.getItem('vm_customer_token')
};
```

Keep unchanged: `cart`, `currentOrderView`, `currentDiscount`, `activePdpProduct`, `activePdpQty`, `PRODUCTS`, `MOCK`.

## Helper functions

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
```

Anti's nav account label can hold ~12 chars cleanly (more relaxed than lafromage's 8-char mono limit). Truncation with ellipsis if longer.

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
            // If currently on a logged-in-only view, redirect:
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

Called once at the end of the existing IIFE, after `fetchStore('products')` completes.

## Legacy storage cleanup

At the very top of the `<script>` block (before state declarations):

```js
// One-time migration from legacy mock auth (pre-D.4 anti port).
try { localStorage.removeItem('vm_user_session'); } catch {}
try { localStorage.removeItem('vm_user_history'); } catch {}
```

Idempotent; safe to leave permanently.

## Auth action methods

Replace the existing `login()` and `logout()`. Add new `register()`.

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

Note `logout()` now ends with `showView('shop')` — matches anti's existing behavior (current `logout()` does the same), preserved.

## Modal open/close + wiring

```js
function openAuthModal(initialMode = 'login') {
    const modal = document.getElementById('auth-modal');
    if (!modal) return;
    modal.classList.add('open');
    document.body.classList.add('overflow-hidden');
    // Reset to requested mode + clear errors:
    setAuthMode(initialMode);
    modal.querySelectorAll('[data-err]').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
    // Focus the first input in the active mode:
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
```

`wireAuthModal()` runs once at init (alongside `bootCustomer()`):

```js
function wireAuthModal() {
    const modal = document.getElementById('auth-modal');
    if (!modal) return;

    // Tab switching:
    modal.querySelectorAll('.auth-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            modal.querySelectorAll('[data-err]').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });
            setAuthMode(btn.dataset.tab);
        });
    });

    // Backdrop click closes:
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeAuthModal();
    });

    // Esc key closes (only when modal open):
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) closeAuthModal();
    });

    // Login form submit:
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

    // Register form submit: same shape.
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

## handleAccountClick rewire

```js
function handleAccountClick() {
    if (isLoggedIn()) {
        showView('dashboard');
    } else {
        openAuthModal('login');
    }
}
```

## Dashboard / orders / order-detail rewrite

### renderDashboardUI

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
    // Member status stays 'VIP' (decorative; left in markup).

    const recentContainer = document.getElementById('dash-recent-orders');
    if (orders.length === 0) {
        recentContainer.innerHTML = '<p class="text-brand-mute italic mt-4">No recent orders.</p>';
        return;
    }
    // Server returns most recent first per customer_my_orders. Slice top 3:
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

### renderOrdersUI

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

### openOrder

`state=order` endpoint doesn't have a single-order GET. Reuse the list. Cheap, correct, matches the server's data model.

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

    // Items are JSON-stringified server-side in state=order's items column. Parse defensively.
    let items = [];
    try {
        items = typeof currentOrderView.items === 'string'
            ? JSON.parse(currentOrderView.items)
            : (Array.isArray(currentOrderView.items) ? currentOrderView.items : []);
    } catch { items = []; }

    // Normalize field aliases. The server stored what handleCheckout sent
    // (we control the shape — see handleCheckout below). Defensive read for safety.
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

### reorderCurrent

Unchanged — `currentOrderView.items` is now populated by `openOrder` from the server (or the items list `openOrder` already parsed). One small adjustment: the parsed items from `openOrder` may use `quantity` field; existing `reorderCurrent` does `orderItem.quantity` lookups — compatible.

But because `openOrder` sets `currentOrderView` directly (the row from the API, not the parsed-items object), `reorderCurrent` accessing `currentOrderView.items` will get the JSON-string. Fix: stash the parsed items list under `currentOrderView._items`:

```js
// At the end of openOrder, after parsing items:
currentOrderView._items = items;
```

And update `reorderCurrent`:

```js
function reorderCurrent() {
    if (!currentOrderView || !Array.isArray(currentOrderView._items)) return;
    currentOrderView._items.forEach(orderItem => {
        const existing = cart.find(c => c.id === orderItem.id);
        if (existing) {
            existing.quantity = (existing.quantity || 1) + (orderItem.quantity || orderItem.qty || 1);
        } else {
            cart.push({
                id: orderItem.id,
                name: orderItem.name || orderItem.title || 'Item',
                price: Number(orderItem.price || 0),
                quantity: orderItem.quantity || orderItem.qty || 1,
                img: orderItem.img || orderItem.image || ''
            });
        }
    });
    saveCart();
    showView('shop');
    toggleCart();
}
```

## Checkout rewrite

Replace existing `handleCheckout`:

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

`handleCheckout` is now `async`. The existing form binding (`onsubmit="handleCheckout(event)"` in the checkout-modal markup, or wherever it's set) does not need changes — async function returns a promise and inline `onsubmit` ignores the return value.

## view-account deletion

Delete the entire block (current lines 130-138):

```html
<!-- DELETE: -->
<div id="view-account" class="view hidden pt-40 pb-40 px-6 max-w-lg mx-auto text-center">
    <h1 class="text-4xl font-bold text-white mb-4">Welcome</h1>
    <p class="text-brand-mute mb-10">Sign in to manage your orders and preferences.</p>
    <div class="bg-brand-card border border-brand-border p-8 rounded-2xl shadow-2xl">
        <input type="email" id="login-email" placeholder="Email Address" ...>
        <button onclick="login()" ...>Login to Account</button>
        <p class="text-[10px] text-brand-mute uppercase tracking-widest">Secure 1-click access</p>
    </div>
</div>
```

Update `showView`'s nav-opacity calc (current line 852):

```js
// Old:
// accountNav.style.opacity = (viewId !== 'shop' && viewId !== 'account') ? '1' : '';
// New:
accountNav.style.opacity = (viewId !== 'shop') ? '1' : '';
```

Remove the stale `document.getElementById('nav-account').textContent = 'Profile';` call near the bottom of the IIFE (current line ~1099). `applyCustomerToDom` (called by `bootCustomer`) is the canonical source of truth now.

## Boot integration

In the existing `DOMContentLoaded`-style IIFE at the bottom (the part that hydrates products), add `wireAuthModal()` and `bootCustomer()` calls:

```js
// Existing block (approximately):
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const __d = await fetchStore('products');
        PRODUCTS = __d.map(p => ({
            ...p,
            name: p.name || p.title || '',
            img: p.img || p.image || '',
            desc: p.desc || p.description || '',
            category: p.category || ''
        }));
    } catch {}

    // NEW (replaces the unconditional `nav-account.textContent = 'Profile'`):
    wireAuthModal();
    bootCustomer();

    // ...existing render calls (renderCart, renderShop, etc.)
});
```

## Events

Two custom events on `document`, consistent with lafromage and the playbook:

- `vm:customer-login` (`detail: customer`) — fired by `login()`, `register()`, `bootCustomer()` success branch
- `vm:customer-logout` — fired by `logout()`, `bootCustomer()` 401 branch, `bootCustomer()` no-token branch

Anti doesn't currently subscribe to these — they exist for future analytics/widget integration consistency.

## Error handling summary

- `fetchStore` network failures fall through to mock — unchanged.
- `postStore` failures return `{ok: false}` — handlers display user-facing text.
- 401 from `customer_me` clears state + redirects off logged-in views.
- Modal submit handlers re-enable the button on failure (so user can retry).
- `handleCheckout` keeps `alert()` for confirmation/error — matches anti's existing UX style.

## Testing approach

Browser-based smoke (Playwright MCP if available). Direct serve via `http://localhost:8016/themes/anti/interface`. Auth-gated steps may be skipped if the direct-serve path doesn't reach the customer API (documented limitation in playbook).

Smoke sequence:

1. Load home, verify hero renders + nav has account label `Account`.
2. Click `Account` (logged-out) → auth modal opens with Sign In tab active.
3. Switch to Create tab → register form visible, login hidden.
4. Submit Create with fresh email/password/name → modal closes, dashboard visible, nav label updated.
5. Reload page → still logged in (cache hydrates), nav label still updated.
6. Click `Account` (logged-in) → goes straight to dashboard.
7. Add 2 products to cart via PDP, open cart drawer, click Checkout → checkout modal opens. Place Order → success, redirected to dashboard, new order in Recent.
8. Click `View All` → orders page lists the new order.
9. Click an order row → order-detail view with items.
10. Click `Sign Out` → returned to shop, nav label back to `Account`.
11. Backdrop click on auth modal → closes.

## File scope

**Modified:**
- `themes/anti/interface` (full file; additive + 1 view deletion)

**Untouched:**
- `themes/anti/index.html`
- Backend code
- `vm-admin/routes/page.theme.php`
- Other themes

**Created:**
- None.

## Known follow-ups (logged, not addressed)

1. **Order endpoint upgrade** — `state=order` does not persist address fields. Same gap as lafromage's port.
2. **Single-order endpoint** — `openOrder` re-fetches the full list. A `customer_order_by_id` endpoint would be cleaner.
3. **Address book / change password / profile edit** — deferred (consistent with all D ports).
4. **Membership status** — currently a static `VIP` literal in the dashboard markup. No real tier system on the backend.
5. **Discount codes** — `applyDiscount` is unchanged (still client-side hardcoded `VIP20` / `WELCOME` codes). The server has `state=discounts` but anti doesn't consult it. Could be a future pass.

## Open questions resolved during brainstorming

- **Auth UI shape** → Modal overlay (austin-style), tabs for login/register
- **Address form at checkout** → No; just rewire to real API
- **Mock fallback for dashboard/orders** → Drop `userHistory`; show empty state on missing data
- **Deletion of `view-account`** → Yes; modal replaces it
