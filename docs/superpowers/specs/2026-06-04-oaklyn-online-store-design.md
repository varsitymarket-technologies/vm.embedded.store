# oaklyn — Online Store Port (Sub-project D.4 / oaklyn)

**Date:** 2026-06-04
**Status:** Approved
**Owner:** keenan@doneros.co
**Parent decomposition:** Second of three D.4 themes (`anti` done at commit `847d0e9`; `ourchieve` queued). Same parent initiative as D.5 (`hastings.ego`) and D.6 (`lafromage`).

## Summary

Add customer accounts (login/register/dashboard) to `themes/oaklyn/interface` (~2856 lines). Oaklyn already has a polished commerce surface — 4-step checkout, cart drawer, hash routing via a `router.navigate(view, params)` object, sophisticated FAQ/search/track/orders pages, and `placeOrder()` that already POSTs to `api.php?state=order`. The port adds two new routes (`#account`, `#dashboard`), a token-aware data layer, and deletes the two redundant email-based order-lookup pages (`view-track` and `view-orders`).

Key changes:

1. Augment `fetchStore` with bearer token + add `postStore` (canonical pattern from anti/lafromage).
2. Add `customerAuth = {user, token}` backed by `vm_customer_token` + `vm_customer_cache`.
3. Add `view-account` full-page section with Sign In / Create Account tabs + their forms.
4. Add `view-dashboard` full-page section with greeting + order history table.
5. Add `login(email, password)` / `register(email, password, name)` / `logout()` via `postStore`.
6. Add `bootCustomer()` + `isLoggedIn()` + `applyCustomerToDom()` (header label flip + `body.dataset.customer`).
7. Add `wireAuthView()` running once at boot — tab toggles, form submit handlers, sign-out button.
8. Upgrade `placeOrder()` to send `customer_id` when logged in + upgrade `showCheckoutStep(1)` to prefill identity from `customerAuth.user`.
9. Delete `view-track`, `view-orders`, `trackOrder()`, `lookupOrders()`, and their router cases + nav links.

## Non-goals

- New views or visual redesigns beyond the two added sections. Oaklyn's editorial aesthetic preserved.
- Address book, change-password, profile-edit, password-reset, order-detail click-through — deferred (matches all D ports).
- Backend changes — sub-projects A/B/D.1 already shipped every endpoint.
- Reorder / re-buy from dashboard — out of scope (anti has reorder; oaklyn dashboard is minimal display-only).
- Cart, search, FAQ, terms, contact, about pages — untouched.
- `themes/oaklyn/index.html` — separate standalone file, unused by the engine, not touched.

## Architecture decision

**Full-page `view-account` (NOT modal)** — user-selected from three options during brainstorming.

Why: oaklyn's editorial luxury aesthetic favors considered, full-page experiences (existing `view-track`, `view-orders`, `view-search` are all full pages). A modal overlay would feel jarring against the slow, considered design language. Full pages also match real Shopify themes' `/account/login` pattern.

**Email-lookup deletion (NOT keep as guest fallback)** — user choice.

Why: with the dashboard providing real order history for registered customers, the email-based lookup pages are visually redundant. Customers who want to track orders are expected to register. Trade-off: guest customers who placed orders before registering can't look them up without registering first. Mitigated by `customer_backfill_orders` (already in sub-project A) which links pre-existing orders by email when a customer registers with the same address.

## Components

The port modifies these concerns in `themes/oaklyn/interface`:

1. **Data layer** — `fetchStore` (augment), `postStore` (new). Module-scope, sibling functions.
2. **State** — `customerAuth = {user, token}` (new). `cart` object untouched.
3. **Auth UI** — new `view-account` section + `renderAccount()` + `setAuthMode()` + `wireAuthView()`. No modal.
4. **Auth actions** — `login(email, password)`, `register(email, password, name)`, `logout()` — all async, `postStore`-backed.
5. **Boot** — `bootCustomer()` runs once at end of init; hydrates from cache, verifies via `customer_me`, dispatches events.
6. **Dashboard** — new `view-dashboard` section + `renderDashboard()` reading `customer_my_orders`.
7. **Checkout integration** — `placeOrder()` sends `customer_id` when logged in. `showCheckoutStep(1)` prefills identity inputs from `customerAuth.user`.
8. **Deletions** — `view-track`, `view-orders`, `trackOrder()`, `lookupOrders()`, their router cases, their nav links.

## API contract recap

Same endpoints as the rest of the D series — already verified by sub-project B's tests:

| Endpoint | Method | Request | Success response |
|---|---|---|---|
| `api.php?state=customer_login` | POST | JSON `{email, password}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_register` | POST | JSON `{email, password, name?, phone?}` | `{ok:true, customer, token, expires_at}` |
| `api.php?state=customer_logout` | POST | Header `X-Customer-Token` | `{ok:true}` |
| `api.php?state=customer_me` | GET | Header `X-Customer-Token` | `{ok:true, customer}` |
| `api.php?state=customer_my_orders` | GET | Header `X-Customer-Token` | `[ {id, customer_name, total_amount, items, status, created_at}, ... ]` (raw array) |
| `api.php?state=order` | POST | JSON `{name, email, total, items, customer_id?}` | `{success:true, message}` (no `ok` wrapper) |

The existing `state=orders` (email-based lookup) endpoint is no longer used by oaklyn after this port. The endpoint itself stays in the backend (other themes may use it).

Failure shapes: `{ok:false, error}` + 4xx for auth/me/my_orders; `{error}` + 4xx for `state=order`.

## Data flow

### Read path

`fetchStore(state, params)` keeps mock-fallback behavior. Add `X-Customer-Token` from `localStorage.vm_customer_token` when present.

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

New `postStore(state, body)`:

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

Three `localStorage` keys after the port:

- `vm_customer_token` — bearer token (canonical, shared with anti/lafromage/hastings.ego)
- `vm_customer_cache` — last-known customer JSON
- `oaklyn_cart` — oaklyn's existing cart key (unchanged)

Proactive legacy cleanup at the top of the script (no-op for clean origins, defensive for shared-origin pollution from other ported themes):

```js
// One-time migration / proactive cleanup.
try { localStorage.removeItem('vm_user_session'); } catch {}
try { localStorage.removeItem('vm_user_history'); } catch {}
```

## State variable

Inserted alongside the existing `cart` declaration:

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
```

The header anchor that currently links to `#track` (or wherever the existing nav account-ish link points) needs `id="nav-account-link"` added to it — a small HTML attribute insert as part of Section 5's nav rewrite.

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
```

Note the active-view selector is `.view-section.active` (oaklyn's pattern), not `.view:not(.hidden)` (anti's). Same logical behavior, different DOM convention.

## view-account markup

Inserted into `<body>` as a sibling of the other `view-section`s (placement convention: alongside view-cart or view-checkout). Uses oaklyn's editorial luxury palette.

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
```

`.btn-primary` is oaklyn's existing button class (gold background, espresso text, DM Mono uppercase) — reused for consistency.

## view-dashboard markup

```html
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
```

## Router integration

Inside `router.onRoute(view, params)` switch, add two cases AND remove two cases:

```js
// Existing cases preserved (home, shop, product, collections, sale, cart, checkout, search, faq)
// NEW:
case "account":   renderAccount();   break;
case "dashboard": renderDashboard(); break;

// REMOVED:
// case "track":   break;
// case "orders":  if (params.email) { document.getElementById("orders-email").value = params.email; lookupOrders(); } break;
```

`router.navigate('account')` and `router.navigate('dashboard')` flow through the same `view-${view}` activation that all other routes use — no router changes needed beyond the switch.

## renderAccount

```js
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
```

## renderDashboard

```js
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
```

## Auth action methods

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
    router.navigate('home');
}
```

## wireAuthView

Runs once at boot. Wires both forms + sign-out button + tab toggles via delegation.

```js
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
```

## placeOrder upgrade

Replace existing `placeOrder()` (the `try { ... } catch { }` wrapper is preserved — oaklyn's fire-and-forget checkout UX is intentional):

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

## showCheckoutStep prefill

Replace existing `showCheckoutStep`:

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
        // user.name is a single field server-side; split lossy
        if ((fname && !fname.value) || (lname && !lname.value)) {
            const parts = (user.name || '').trim().split(/\s+/);
            if (fname && !fname.value) fname.value = parts[0] || '';
            if (lname && !lname.value) lname.value = parts.slice(1).join(' ') || '';
        }
    }
}
```

## Deletions

**HTML sections (two deletes):**

- `<section id="view-track" class="view-section">...</section>`
- `<section id="view-orders" class="view-section">...</section>`

**JavaScript functions (two deletes):**

- `async function trackOrder() { ... }` (full body)
- `async function lookupOrders() { ... }` (full body)

**Router switch cases (two deletes):**

- `case "track": break;`
- `case "orders": if (params.email) { document.getElementById("orders-email").value = params.email; lookupOrders(); } break;`

**Nav / footer links to `#track` and `#orders`:**

Discovered during implementation via `grep -nE 'href="#track"|href="#orders"|navigate.*track|navigate.*orders'`. Each occurrence either removed or repointed to `#account` depending on placement. Any standalone "Track Order" footer link is removed entirely (the dashboard is the new order surface).

## Boot integration

At the bottom of the script body, after all existing init calls and the DOMContentLoaded handler that calls `await fetchStore("site")`, add (in order):

```js
wireAuthView();
bootCustomer();
```

`bootCustomer` is async but not awaited — same fire-and-forget pattern as anti. Cached user (if any) renders synchronously via `applyCustomerToDom`'s first call; `customer_me` reconciliation happens in the background.

The header nav anchor that becomes the `Account` link gets `id="nav-account-link"` added as part of Section 1's nav rewrite — applyCustomerToDom queries by this id.

## Events

Two custom events on `document`, consistent with all D ports:

- `vm:customer-login` (`detail: customer`) — dispatched by `login()`, `register()`, `bootCustomer()` success branch
- `vm:customer-logout` — dispatched by `logout()`, `bootCustomer()` 401 branch, `bootCustomer()` no-token branch

Oaklyn doesn't subscribe — events exist for future analytics/widget integration consistency.

## Error handling

- `fetchStore` network failures fall through to mock — unchanged.
- `postStore` failures return `{ok: false}` with user-facing error text.
- 401 from `customer_me` clears state + redirects off view-dashboard.
- Form submit handlers re-enable buttons on failure (user can retry).
- `placeOrder` keeps its fire-and-forget swallowed catch — oaklyn's intentional UX.

## Testing approach

Browser-based smoke (Playwright MCP). Direct serve via `http://localhost:8016/themes/oaklyn/interface`. Auth-gated steps may skip per documented direct-serve limitation.

Smoke sequence:

1. Load home, verify oaklyn-styled hero renders, header has `Account` label.
2. Click `Account` → routes to `#account`, view-account active, Sign In tab visible.
3. Switch to Create Account tab → register form visible, sign-in hidden, tab styling flips.
4. Submit Create with fresh email/password/name → routes to `#dashboard`, greeting shows "Hello, <name>", email displayed.
5. Reload → still logged in (cache); navigate to `#account` → auto-redirects to `#dashboard` (since logged in).
6. Add items to cart, proceed to checkout. Step 1 inputs are prefilled with name/email from auth.
7. Complete checkout (4 steps) → Step 4 confirmation. Network payload includes `customer_id`.
8. Return to `#dashboard` → Order History table shows the order (or empty state if backend N/A).
9. Click `Sign Out` → routes to `#home`; header label back to `Account`.
10. Navigate to `#track` or `#orders` → routes to `#home` (deleted views, router fallback).

## File scope

**Modified (one file):**
- `themes/oaklyn/interface` (~2856 lines pre-port)

**Untouched:**
- `themes/oaklyn/index.html`, `themes/oaklyn/autofill.json`
- Backend code, admin routes, other themes

**Created:**
- None.

## Known follow-ups

1. **Order endpoint upgrade** — `state=order` doesn't persist `customer_id` today. Sending it is forward-compat.
2. **Single-order endpoint** — no order-detail view in oaklyn dashboard. Could add later.
3. **Address book / change password / profile edit** — deferred (consistent across D ports).
4. **Lossy name split** — `user.name` → fname/lname is one-way only. Same trade-off as austin (D.2).
5. **`#track` / `#orders` graceful fallback** — currently routes to `#home`. A "Page moved" notice could be friendlier but adds scope.

## Open questions resolved during brainstorming

- **Track/orders fate** → Delete both; dashboard is the only orders surface
- **placeOrder upgrade** → Yes; prefill identity + send `customer_id`
- **Auth UI shape** → Full-page `view-account` (NOT modal)
