# hastings.ego account-view port — Design (Sub-project D.5)

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co
**Parent decomposition:** Fifth sub-project under D (theme wiring). D.1
(foundation), D.2 (austin), D.3.1 (aura + playbook) shipped. Remaining:

- D.3.2 — Apply playbook to 18 commerce-skeleton lookalikes
- D.4 — Port `anti`, `oaklyn`, `ourchieve`
- D.6 — Decide fate of `lafromage` + `default`

Audit doc: [2026-06-03-theme-storefront-baseline-design.md](2026-06-03-theme-storefront-baseline-design.md).
Playbook: [docs/superpowers/theme-port-playbook.md](../../theme-port-playbook.md).

## Summary

Activate customer auth in `themes/hastings.ego/interface` by wiring
the existing `tpl-account` login form (and a new register form
toggle) to `vmCustomer.*`. On successful login, the account view
shows a compact dashboard (name + email + recent orders + Sign
Out). Adds a minimal inline router so the `data-view="account"`
nav link actually does something; other broken views (shop, cart,
checkout) remain out of scope.

hastings.ego is architecturally distinct from austin (D.2) and
aura (D.3.1):

- **Smaller intentional scope.** It's a minimal template; no
  built-in dashboard like austin's.
- **External `store.js` was the API loader.** The host
  `themes.varsitymarket.tech` is currently unreachable (HTTP 000),
  which means *nothing* in the theme works today. D.5 doesn't
  restore `store.js` — that's a separate deployment fix.
- **No `app = {}` skeleton today.** The theme delegated everything
  to `store.js`. D.5 introduces a tiny inline `app.auth` namespace
  scoped to the account view only.

## Goals

- Make the Account nav link functional: clicking it shows either
  the login form, the register form, or the logged-in dashboard
  depending on `vmCustomer` state.
- Bind the login + register forms to real `vmCustomer.*` calls.
- Show a small dashboard with the customer's name, email, and up
  to 5 recent orders.
- Keep total scope under ~250 added lines so the port lands in
  this session.

## Non-goals

- **Do NOT restore or replace `store.js`.** It's the intended
  external API loader; its unavailability is a deployment problem,
  not a theme problem.
- **Do NOT route the other `data-view` links** (`shop`, `cart`,
  `checkout`, `contact`, `about`). Those were store.js's
  responsibility and remain broken until store.js is restored or
  a separate port wires them inline.
- **No address book / forgot-password / delete-account / profile
  editing / password change.** hastings.ego is a minimal template
  by design.
- **No backend changes.** D.1 foundation is stable.
- **No changes to other themes.**
- **No changes to skel/ or module/.**

## User flow (after the port)

1. Customer visits hastings.ego's storefront. `vm-customer.js`
   boots; if a `vm_customer_token` exists, the helper resolves it
   and fires `vm:customer-loaded`. Body `dataset.customer` flips to
   `logged-in`.
2. Customer clicks the **Account** nav link (line 530 / 562 of the
   current markup, `data-view="account"`). Inline router clones
   `tpl-account` into the main view container and calls
   `app.auth.renderState()`.
3. `renderState()` decides which of three sections to show:
   - **Logged out, default**: shows the login form.
   - **Logged out, after clicking "Create Account"**: shows the
     register form.
   - **Logged in**: shows the dashboard.
4. **Login flow**: customer submits email + password. Inline
   handler calls `await vmCustomer.login(email, password)`. On
   429 (locked), shows "Account temporarily locked." inline. On
   other failure, shows the error message. On success, the
   `vm:customer-login` event fires; `renderState()` re-runs and
   shows the dashboard.
5. **Register flow**: customer clicks "Create Account" → register
   form becomes visible. Fills first name + last name + email +
   password. Submits. Inline handler joins fname+lname → calls
   `vmCustomer.register(email, password, name, null)`. On success,
   dashboard appears.
6. **Dashboard**: shows `Welcome, <fname>`, the customer's email,
   and up to 5 recent orders (date, id, total) fetched from
   `vmCustomer.myOrders()`. Sign Out button calls
   `vmCustomer.logout()` and re-renders to login form.
7. **Logout**: `vm:customer-logout` event fires (helper auto-fires
   it after the logout call). The dashboard would be re-rendered as
   the login form on next account-view entry.

The other nav links (`shop`, `cart`, `contact`, `about`) remain
non-functional in this state. The spec notes this as an accepted
known-broken state, not a regression.

## Files touched

**Modify:**

- `themes/hastings.ego/interface` — ~250 lines added:
  - Head snippet (~25 lines)
  - Expanded `tpl-account` content (~80 lines)
  - Inline router + `app.auth = {...}` block (~150 lines)

**Create:**

- `themes/hastings.ego/vm-customer.js` — copy of
  `skel/vm-customer.js`. Same local-dev workaround as D.3.1
  (engine iframe routing is currently broken; serving the theme
  directly via `/themes/hastings.ego/interface` is the test path).

**Untouched:**

- `themes/hastings.ego/interface` blocks: the `<script
  src="https://themes.varsitymarket.tech/plugin/store.js">` tag,
  the `window.StoreConfig` declaration, `open_menu`/`close_menu`,
  `heroShinker`, and all other templates.
- `skel/`, `module/`, `api/`, other themes, sub-projects A/B/D.1/D.2/D.3.1.

## Edit details

### Edit 1: `<head>` snippet

Same pattern as the playbook. Inserted before `</head>`. Three
event listeners that flip `document.body.dataset.customer` and
re-call `app.auth.renderState()` when relevant. No `aus_user`
cleanup (hastings.ego had no localStorage mockup).

### Edit 2: Expand `tpl-account`

The existing template (lines 648-666) has a login form with a
"Create Account" button that did nothing. Replace the template's
inner content with three labeled `<div>` containers:

```html
<template id="tpl-account">
    <section id="account-view">
        <div class="container">
            <!-- Section A: login form (default visible when logged out) -->
            <div id="acc-login" class="acc-state">
                <h1>Sign In</h1>
                <form class="js-login-form">
                    <!-- email + password inputs + Sign In button -->
                    <!-- "Create Account" toggle button → flips to register -->
                </form>
            </div>

            <!-- Section B: register form (hidden by default) -->
            <div id="acc-register" class="acc-state" style="display:none">
                <h1>Create Account</h1>
                <form class="js-register-form">
                    <!-- fname + lname + email + password + confirm + Create button -->
                    <!-- "Back to sign in" link → flips back to login -->
                </form>
            </div>

            <!-- Section C: logged-in dashboard (shown when logged in) -->
            <div id="acc-dashboard" class="acc-state" style="display:none">
                <h1>Welcome, <span class="js-greet-fname">friend</span></h1>
                <p class="js-greet-email"></p>
                <h2>Recent Orders</h2>
                <div class="js-orders-list">No orders yet.</div>
                <button type="button" class="btn js-logout">Sign Out</button>
            </div>
        </div>
    </section>
</template>
```

Element IDs use `acc-` prefix (consistent with D.2 + D.3.1's
naming).

### Edit 3: Inline router + `app.auth` handlers

A new `<script>` block inserted after the existing `heroShinker`
script (around line 774). Defines:

```js
const app = window.app || (window.app = {});
const VIEW_CONTAINER_SELECTOR = '#main';  // see "Main container" below

// --- Minimal router (account only) ---
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-view]');
    if (!trigger) return;
    const view = trigger.getAttribute('data-view');
    if (view !== 'account') return;   // D.5 wires only account
    e.preventDefault();
    renderTemplate('tpl-account');
    app.auth.renderState();
});

function renderTemplate(tplId) {
    const tpl = document.getElementById(tplId);
    const container = document.querySelector(VIEW_CONTAINER_SELECTOR);
    if (!tpl || !container) return;
    container.innerHTML = '';
    container.appendChild(tpl.content.cloneNode(true));
}

// --- app.auth namespace ---
app.auth = {
    getUser() { /* same fname/lname split adapter as D.3.1 */ },
    isLoggedIn() { return !!window.vmCustomer && vmCustomer.isLoggedIn(); },
    renderState() {
        const login = document.getElementById('acc-login');
        const register = document.getElementById('acc-register');
        const dash = document.getElementById('acc-dashboard');
        if (!login || !register || !dash) return;
        if (this.isLoggedIn()) {
            login.style.display = 'none';
            register.style.display = 'none';
            dash.style.display = '';
            this.renderDashboard();
        } else {
            login.style.display = '';
            register.style.display = 'none';
            dash.style.display = 'none';
            this.bindForms();
        }
    },
    bindForms() {
        // Bind login and register form submits, and the toggle buttons
        // between them. Re-binding is idempotent because we set
        // form.dataset.bound after first bind.
    },
    submitLogin() { /* vmCustomer.login + renderState */ },
    submitRegister() { /* vmCustomer.register + renderState */ },
    async renderDashboard() {
        const u = this.getUser();
        if (!u) { this.renderState(); return; }
        // Populate greeting, email, orders list (via vmCustomer.myOrders).
    },
    async doLogout() { /* vmCustomer.logout + renderState */ }
};

// On page load — if logged in via stored token, render the dashboard
// when the customer-loaded event fires.
window.addEventListener('vm:customer-loaded', () => app.auth.renderState());
window.addEventListener('vm:customer-login',  () => app.auth.renderState());
window.addEventListener('vm:customer-logout', () => app.auth.renderState());
```

**Main container.** hastings.ego currently has no consistent main
content container — store.js was presumably writing into the body
directly. D.5 needs to identify or create one. Options:

1. **Existing element**: Inspect the markup for any `<main>`,
   `<div id="...">`, or similar that's clearly the "page content"
   area below the header. If one exists, use it.
2. **Create one**: If no container exists, wrap the existing
   non-template content in a `<main id="main">...</main>` block,
   or insert an empty `<main id="main">` after the header where
   templates get cloned.

The implementer decides during Task 1 of the plan based on what
exists. The selector in the JS (`VIEW_CONTAINER_SELECTOR`) is
configurable.

### Edit 4: Copy `vm-customer.js`

Same local-dev shortcut as D.3.1:

```bash
cp -f skel/vm-customer.js themes/hastings.ego/vm-customer.js
```

So `<script src="vm-customer.js" defer>` resolves at
`/themes/hastings.ego/vm-customer.js`. (Untracked sites/ deploys
would get the helper via the normal sync; this is for the local
test path.)

## Adapter shape

Same `auth.getUser()` adapter as D.2 + D.3.1:

```js
getUser() {
    if (!window.vmCustomer || !vmCustomer.isLoggedIn()) return null;
    const c = vmCustomer.cached();
    if (!c) return null;
    const parts = (c.name || '').trim().split(/\s+/).filter(Boolean);
    return {
        email: c.email,
        fname: parts[0] || '',
        lname: parts.slice(1).join(' '),
        phone: c.phone || '',
        joinDate: c.created_at || ''
    };
}
```

Multi-word names: first space-delimited token is fname, rest is
lname. Lossy by intent; documented across A/B/D specs.

## Dashboard rendering

`renderDashboard()` populates three elements:

- `.js-greet-fname` (span inside `<h1>`) — `textContent` set to
  `u.fname || 'friend'`.
- `.js-greet-email` — `textContent` set to `u.email`.
- `.js-orders-list` — rebuilt via `innerHTML` from the result of
  `await vmCustomer.myOrders()`. Each order as a single `<div
  class="order-row">` showing `#id · date · total · status`. Cap
  at 5 most recent (the backend already caps at 100; we slice 5
  client-side). On error or empty, show "No orders yet."

Toast/error display: hastings.ego has no existing toast function.
Show inline error text inside the form (e.g., a `<p class="js-error">`
inside each form, hidden by default, populated + revealed on
failure).

## Failure modes & guards

| Scenario | Behavior |
|---|---|
| `vm-customer.js` 404 | `window.vmCustomer` undefined; `auth.getUser()` returns null; `renderState()` falls through to login state; form submits throw `ReferenceError` caught in try/catch → inline error message. |
| `api.php` unreachable | `vmCustomer.login/register/myOrders` reject with network error; inline error shows the message. |
| 401 from any endpoint | vm-customer.js auto-logout cascade fires; `vm:customer-logout` runs `renderState()`; account view re-renders to login form. |
| 429 lockout on login | Inline error: "Account temporarily locked. Try again in a few minutes." |
| Wrong current password on change_password | N/A — change_password not implemented in D.5. |
| Customer with 0 orders | Dashboard shows "No orders yet." |
| Customer with > 100 orders | Backend caps at 100; client takes the first 5. |
| Customer clicks `data-view="shop"` (or any non-account view) | Router doesn't intercept; `<a>` tag's natural behavior (typically a `#` hash navigation that store.js would have caught) — does nothing visible. Documented as known-broken. |
| Page loaded fresh + logged in (token in localStorage) | `vm:customer-loaded` fires after `customer_me` resolves; `renderState()` runs, but the account template isn't yet mounted. The listener's `renderState()` call is a safe no-op when `#acc-login` etc. don't exist; the dashboard appears on the user's next click of "Account". |

## Security notes

- No new tokens introduced. `X-Customer-Token` flow inherited from
  D.1.
- `name` and `email` rendered via `textContent` (not `innerHTML`).
  XSS-safe.
- Order list uses `innerHTML` with backend-controlled values
  (`o.id`, `o.created_at`, `o.total_amount`, `o.status`). `id` and
  `total_amount` are numeric per the backend schema; status is a
  short enum-style string. Risk is low but the implementer should
  prefer string-template construction with explicit escapes if any
  field is user-controlled in the future.
- The dashboard hides the login/register forms but they remain in
  the DOM. A previously-typed password is cleared by re-rendering
  the template on logout (which discards the form values).

## Testing

Structural smoke test pattern (same as D.3.1):

1. Navigate to `http://localhost:8016/themes/hastings.ego/interface`.
2. Click the Account nav link. Expect the login form to render
   in the main container.
3. Click "Create Account". Expect the register form to be visible
   and the login form hidden.
4. Click "Back to sign in". Expect login form visible again.
5. Inject a session manually (the captured-BASE workaround from
   the playbook):
   ```js
   (async () => {
     const BASE = 'http://localhost:8016/sites/debug.com/api.php';
     const r = await fetch(BASE + '?state=customer_register', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({ email: 'heg-' + Date.now() + '@x.com', password: 'goodpass1', name: 'Heg Smoke', phone: null })
     });
     const d = await r.json();
     localStorage.setItem('vm_customer_token', d.token);
     localStorage.setItem('vm_customer_cache', JSON.stringify(d.customer));
     app.auth.renderState();
   })();
   ```
6. Verify the dashboard shows "Welcome, Heg" + email + "No orders
   yet." + Sign Out button.
7. Click Sign Out. Expect the login form to render again.
8. Cleanup: `localStorage.clear(); location.reload();`

If step 6 fails because the dashboard elements aren't found, the
template isn't being cloned correctly — check the
`VIEW_CONTAINER_SELECTOR` value.

## Out of scope (recap)

- store.js restoration or replacement — separate sub-project.
- Routing for shop/cart/checkout/contact/about — same.
- Profile edit / change password / address book — hastings.ego is
  a minimal template; these don't fit.
- Forgot password / email verification — backend missing.
- Wishlist — not in this theme.

## Open questions

None. Approved in brainstorming on 2026-06-03.
