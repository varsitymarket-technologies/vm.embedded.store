# hastings.ego account-view port Implementation Plan (Sub-project D.5)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Activate customer auth in `themes/hastings.ego/interface` by wiring the existing `tpl-account` login form (plus a new register-form toggle) to `vmCustomer.*`, with a compact logged-in dashboard (name + email + recent orders + Sign Out). A minimal inline router intercepts only the Account nav link.

**Architecture:** Three surgical additions to a single theme file: head snippet, expanded `tpl-account` template, inline `<script>` block declaring `window.app.auth` and a tiny router. The existing `<main id="app-root">` (already in the markup at line 574) is the template mount target. The external `store.js` tag stays in place — D.5 doesn't restore or replace it.

**Tech Stack:** Vanilla browser JS, no build step. Reuses `skel/vm-customer.js` (D.1 foundation) and the playbook patterns from D.3.1.

**Spec:** [docs/superpowers/specs/2026-06-03-hastings-ego-account-view-design.md](../specs/2026-06-03-hastings-ego-account-view-design.md)

---

## File Map

**Modify:**
- `themes/hastings.ego/interface` — ~250 lines added across three regions: head snippet (~25), expanded `tpl-account` (~80), inline router + `app.auth` block (~150). Existing `<script src="...store.js">` and inline helpers left untouched.

**Create:**
- `themes/hastings.ego/vm-customer.js` — copy of `skel/vm-customer.js` so the relative `<script src="vm-customer.js">` resolves when serving the theme at `/themes/hastings.ego/interface` for smoke testing.

**Untouched:**
- All other themes
- `module/`, `api/`, `skel/` (D.1 foundation stays stable)

---

## Notes for the implementer

- Container: `vm-emb-sites`; project mount: `/var/www/html/public/`.
- The theme has no extension — `themes/hastings.ego/interface` is the full HTML+CSS+JS file.
- Line markers verified during plan authoring:
  - **Line 535** — `</header>`.
  - **Line 574** — `<main id="app-root" class="container"></main>` (template mount target).
  - **Line 648** — `<template id="tpl-account">` opens.
  - **Line 666** — `</template>` closes `tpl-account`.
  - **Line 730** — first `<script>` block (`window.StoreConfig`).
  - **Line 738** — `<script src="https://themes.varsitymarket.tech/plugin/store.js" defer></script>`.
  - **Line 774** — closing `</script>` of the existing inline JS (open_menu / heroShinker).
- `themes/` is gitignored — use `git add -f themes/hastings.ego/interface` to commit edits.

---

## Task 1: Copy vm-customer.js into themes/hastings.ego/

**Files:**
- Create: `themes/hastings.ego/vm-customer.js`

### Step 1.1: Copy + commit

- [ ] **Step 1.1: Copy from skel/ and commit**

```bash
cp -f skel/vm-customer.js themes/hastings.ego/vm-customer.js
git add -f themes/hastings.ego/vm-customer.js
git commit -m "chore(theme/hastings.ego): seed vm-customer.js copy for local smoke testing (D.5)

Same pattern as D.3.1 aura: serve the theme directly via
/themes/hastings.ego/interface; the helper must be at the same
directory for the relative <script src='vm-customer.js'> resolution."
```

---

## Task 2: Edit 1 — head snippet

**Files:**
- Modify: `themes/hastings.ego/interface` (before the existing `</head>`)

### Step 2.1: Find the `</head>` line

- [ ] **Step 2.1: Locate the head close**

```bash
grep -n "</head>" themes/hastings.ego/interface
```

Expected: exactly one match. Note the line number.

### Step 2.2: Insert the snippet

- [ ] **Step 2.2: Edit `themes/hastings.ego/interface`**

Find the line containing `</head>`. Insert this block **immediately before** that line:

```html

    <!-- D.5: customer auth helper (vm-customer.js from same dir as api.php) -->
    <script src="vm-customer.js" defer></script>
    <script>
        window.addEventListener('vm:customer-loaded', function () {
            document.body.dataset.customer = 'logged-in';
            if (window.app && app.auth && typeof app.auth.renderState === 'function') app.auth.renderState();
        });
        window.addEventListener('vm:customer-login', function () {
            document.body.dataset.customer = 'logged-in';
            if (window.app && app.auth && typeof app.auth.renderState === 'function') app.auth.renderState();
        });
        window.addEventListener('vm:customer-logout', function () {
            document.body.dataset.customer = 'logged-out';
            if (window.app && app.auth && typeof app.auth.renderState === 'function') app.auth.renderState();
        });
    </script>
```

### Step 2.3: Verify + commit

- [ ] **Step 2.3: Sanity check + commit**

```bash
grep -c "vm-customer.js" themes/hastings.ego/interface
grep -c "</head>" themes/hastings.ego/interface
```

Expected: first = 2 (one in comment, one in `<script src>`), second = 1.

```bash
git add -f themes/hastings.ego/interface
git commit -m "feat(theme/hastings.ego): add vm-customer.js head snippet (D.5 edit 1/3)"
```

---

## Task 3: Edit 2 — expand `tpl-account` template

**Files:**
- Modify: `themes/hastings.ego/interface` (lines 648-666 — replace the existing `<template id="tpl-account">` block contents)

### Step 3.1: Replace the template

- [ ] **Step 3.1: Edit `themes/hastings.ego/interface`**

Find the exact current template (lines 648-666):

```html
        <template id="tpl-account">
            <section id="account-view">
                <div class="container">
                    <h1>My Account</h1>
                    <form class="js-login-form" id="login-form">
                        <div class="form-group">
                            <label for="login-email">Email</label>
                            <input  class="js-email" type="email" id="login-email" required>
                        </div>
                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <input  class="js-pass" type="password" id="login-password" required>
                        </div>
                        <button type="submit" class="btn">Login</button>
                        <button type="button" class="btn btn-dark">Create Account</button>
                    </form>
                </div>
            </section>
        </template>
```

Replace the entire block with:

```html
        <template id="tpl-account">
            <section id="account-view">
                <div class="container">

                    <!-- Section A: login form (default when logged out) -->
                    <div id="acc-login" class="acc-state">
                        <h1>Sign In</h1>
                        <form class="js-login-form" data-form="login">
                            <div class="form-group">
                                <label for="acc-login-email">Email</label>
                                <input class="js-email" type="email" id="acc-login-email" required>
                            </div>
                            <div class="form-group">
                                <label for="acc-login-pass">Password</label>
                                <input class="js-pass" type="password" id="acc-login-pass" required>
                            </div>
                            <p class="acc-err" data-err="login" style="color:#a00;font-size:0.9em;display:none;"></p>
                            <button type="submit" class="btn">Sign In</button>
                            <button type="button" class="btn btn-dark" data-action="show-register">Create Account</button>
                        </form>
                    </div>

                    <!-- Section B: register form (hidden by default) -->
                    <div id="acc-register" class="acc-state" style="display:none;">
                        <h1>Create Account</h1>
                        <form class="js-register-form" data-form="register">
                            <div class="form-group">
                                <label for="acc-reg-fname">First Name</label>
                                <input type="text" id="acc-reg-fname" required>
                            </div>
                            <div class="form-group">
                                <label for="acc-reg-lname">Last Name</label>
                                <input type="text" id="acc-reg-lname">
                            </div>
                            <div class="form-group">
                                <label for="acc-reg-email">Email</label>
                                <input type="email" id="acc-reg-email" required>
                            </div>
                            <div class="form-group">
                                <label for="acc-reg-pass">Password</label>
                                <input type="password" id="acc-reg-pass" required minlength="8">
                            </div>
                            <p class="acc-err" data-err="register" style="color:#a00;font-size:0.9em;display:none;"></p>
                            <button type="submit" class="btn">Create Account</button>
                            <button type="button" class="btn btn-dark" data-action="show-login">Back to Sign In</button>
                        </form>
                    </div>

                    <!-- Section C: logged-in dashboard (hidden by default) -->
                    <div id="acc-dashboard" class="acc-state" style="display:none;">
                        <h1>Welcome, <span class="js-greet-fname">friend</span></h1>
                        <p class="js-greet-email" style="color:#666;"></p>
                        <h2 style="margin-top:2em;">Recent Orders</h2>
                        <div class="js-orders-list" style="margin-bottom:2em;">No orders yet.</div>
                        <button type="button" class="btn btn-dark js-logout">Sign Out</button>
                    </div>

                </div>
            </section>
        </template>
```

### Step 3.2: Commit

- [ ] **Step 3.2: Commit**

```bash
git add -f themes/hastings.ego/interface
git commit -m "feat(theme/hastings.ego): expand tpl-account with 3 states (D.5 edit 2/3)

login form (default), register form (toggled via Create Account button),
and logged-in dashboard (name, email, recent orders, Sign Out).
All three states share the .acc-state class so JS can toggle their
display via section ID. Handler wiring lands in the next edit."
```

---

## Task 4: Edit 3 — inline router + `app.auth` handlers

**Files:**
- Modify: `themes/hastings.ego/interface` (after the existing closing `</script>` around line 774)

### Step 4.1: Locate the insertion point

- [ ] **Step 4.1: Find the line**

```bash
grep -n "heroShinker();" themes/hastings.ego/interface
```

The match is on a line like `heroShinker();` which is the last statement in the existing inline JS block. Immediately after it on the next line, the file has `</script>`. Insert a NEW `<script>` block right after that closing `</script>`.

### Step 4.2: Insert the new script block

- [ ] **Step 4.2: Edit `themes/hastings.ego/interface`**

After the existing closing `</script>` (the one that follows `heroShinker();`), append this new block:

```html
    <script>
        // D.5: inline customer auth + minimal account-view router.
        // Other data-view links (shop, cart, etc.) remain non-functional
        // until store.js is restored or those views are ported separately.
        (function () {
            const VIEW_CONTAINER_SELECTOR = '#app-root';
            const app = window.app || (window.app = {});

            function renderTemplate(tplId) {
                const tpl = document.getElementById(tplId);
                const container = document.querySelector(VIEW_CONTAINER_SELECTOR);
                if (!tpl || !container) return;
                container.innerHTML = '';
                container.appendChild(tpl.content.cloneNode(true));
            }

            // Minimal router: intercept only data-view="account" clicks.
            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-view]');
                if (!trigger) return;
                const view = trigger.getAttribute('data-view');
                if (view !== 'account') return;
                e.preventDefault();
                renderTemplate('tpl-account');
                app.auth.renderState();
            });

            // Delegated handlers inside the rendered tpl-account fragment.
            document.addEventListener('click', function (e) {
                const action = e.target.closest('[data-action]');
                if (action) {
                    const which = action.getAttribute('data-action');
                    if (which === 'show-register') return app.auth.showSection('register');
                    if (which === 'show-login')    return app.auth.showSection('login');
                }
                const logoutBtn = e.target.closest('.js-logout');
                if (logoutBtn) { e.preventDefault(); app.auth.doLogout(); }
            });

            document.addEventListener('submit', function (e) {
                const form = e.target.closest('[data-form]');
                if (!form) return;
                e.preventDefault();
                const which = form.getAttribute('data-form');
                if (which === 'login') app.auth.submitLogin(form);
                else if (which === 'register') app.auth.submitRegister(form);
            });

            function showError(name, msg) {
                const el = document.querySelector('[data-err="' + name + '"]');
                if (!el) return;
                el.textContent = msg || '';
                el.style.display = msg ? '' : 'none';
            }

            app.auth = {
                getUser: function () {
                    if (!window.vmCustomer || !vmCustomer.isLoggedIn()) return null;
                    const c = vmCustomer.cached();
                    if (!c) return null;
                    const parts = (c.name || '').trim().split(/\s+/).filter(Boolean);
                    return {
                        email: c.email,
                        fname: parts[0] || '',
                        lname: parts.slice(1).join(' '),
                        joinDate: c.created_at || ''
                    };
                },
                isLoggedIn: function () {
                    return !!window.vmCustomer && vmCustomer.isLoggedIn();
                },
                showSection: function (which) {
                    const login = document.getElementById('acc-login');
                    const register = document.getElementById('acc-register');
                    const dash = document.getElementById('acc-dashboard');
                    if (!login || !register || !dash) return;
                    login.style.display    = (which === 'login')    ? '' : 'none';
                    register.style.display = (which === 'register') ? '' : 'none';
                    dash.style.display     = (which === 'dashboard')? '' : 'none';
                    showError('login', '');
                    showError('register', '');
                },
                renderState: function () {
                    const login = document.getElementById('acc-login');
                    if (!login) return;  // account template not currently mounted; no-op
                    if (this.isLoggedIn()) {
                        this.showSection('dashboard');
                        this.renderDashboard();
                    } else {
                        this.showSection('login');
                    }
                },
                submitLogin: async function (form) {
                    showError('login', '');
                    const email = form.querySelector('.js-email').value.trim();
                    const pass  = form.querySelector('.js-pass').value;
                    if (!email || !pass) { showError('login', 'Email and password required.'); return; }
                    try {
                        await vmCustomer.login(email, pass);
                        // vm:customer-login event runs renderState() via head listener.
                    } catch (e) {
                        showError('login', (e && e.code === 'locked')
                            ? 'Account temporarily locked. Try again in a few minutes.'
                            : ((e && e.message) || 'Sign-in failed'));
                    }
                },
                submitRegister: async function (form) {
                    showError('register', '');
                    const fname = document.getElementById('acc-reg-fname').value.trim();
                    const lname = document.getElementById('acc-reg-lname').value.trim();
                    const email = document.getElementById('acc-reg-email').value.trim();
                    const pass  = document.getElementById('acc-reg-pass').value;
                    if (!fname || !email || !pass) { showError('register', 'Please fill all required fields.'); return; }
                    if (pass.length < 8) { showError('register', 'Password must be at least 8 characters.'); return; }
                    const name = (fname + ' ' + lname).trim();
                    try {
                        await vmCustomer.register(email, pass, name, null);
                        // vm:customer-login event runs renderState() via head listener.
                    } catch (e) {
                        showError('register', (e && e.message) || 'Sign-up failed');
                    }
                },
                doLogout: async function () {
                    try { await vmCustomer.logout(); } catch (e) { /* cleared locally regardless */ }
                    // vm:customer-logout event runs renderState() via head listener.
                },
                renderDashboard: async function () {
                    const u = this.getUser();
                    if (!u) { this.renderState(); return; }
                    const fnameEl = document.querySelector('.js-greet-fname');
                    const emailEl = document.querySelector('.js-greet-email');
                    if (fnameEl) fnameEl.textContent = u.fname || 'friend';
                    if (emailEl) emailEl.textContent = u.email || '';
                    const list = document.querySelector('.js-orders-list');
                    if (!list) return;
                    list.textContent = 'Loading…';
                    try {
                        const r = await vmCustomer.myOrders();
                        const orders = (r.orders || []).slice(0, 5);
                        if (!orders.length) { list.textContent = 'No orders yet.'; return; }
                        list.innerHTML = '';
                        orders.forEach(function (o) {
                            const row = document.createElement('div');
                            row.className = 'order-row';
                            row.style.cssText = 'padding:0.5em 0;border-bottom:1px solid rgba(0,0,0,0.08);';
                            const total = (o.total_amount != null ? Number(o.total_amount) : 0).toFixed(2);
                            const dateStr = o.created_at ? String(o.created_at).split(' ')[0] : '—';
                            row.textContent = '#' + o.id + '  ·  ' + dateStr + '  ·  $' + total + '  ·  ' + (o.status || 'pending');
                            list.appendChild(row);
                        });
                    } catch (e) {
                        list.textContent = 'Failed to load orders.';
                    }
                }
            };
        })();
    </script>
```

### Step 4.3: Verify + commit

- [ ] **Step 4.3: Sanity grep + commit**

```bash
echo "vmCustomer call sites: $(grep -c 'vmCustomer\.' themes/hastings.ego/interface)"
echo "app.auth references: $(grep -c 'app\.auth\.' themes/hastings.ego/interface)"
echo "Three acc-state IDs: $(grep -cE 'id=\"acc-(login|register|dashboard)\"' themes/hastings.ego/interface)"
echo "VIEW_CONTAINER_SELECTOR present: $(grep -c 'VIEW_CONTAINER_SELECTOR' themes/hastings.ego/interface)"
```

Expected: vmCustomer 5+, app.auth 6+, three acc-state IDs = 3, VIEW_CONTAINER_SELECTOR = 1.

```bash
git add -f themes/hastings.ego/interface
git commit -m "feat(theme/hastings.ego): wire inline router + app.auth handlers (D.5 edit 3/3)

Adds an IIFE-scoped script block that:
- Intercepts data-view='account' clicks only (other views still
  depend on the broken store.js; documented in spec).
- Clones tpl-account into <main id='app-root'> on entry.
- Declares window.app.auth with getUser/isLoggedIn/showSection/
  renderState/submitLogin/submitRegister/doLogout/renderDashboard.
- Wires delegated submit + click handlers so the cloned form
  fragments (which don't exist at script-load time) get their
  events caught at document level.

The head snippet's event listeners (added in edit 1/3) call
app.auth.renderState() on vm:customer-loaded/login/logout.

Sub-project D.5 wiring complete; smoke test next."
```

---

## Task 5: Browser smoke test

**Files:** none (verification only)

### Step 5.1: Open the storefront

- [ ] **Step 5.1: Navigate to the standalone theme URL**

```bash
echo "Open in browser: http://localhost:8016/themes/hastings.ego/interface"
```

Page should load with hastings.ego's branding (logo, nav with Shop / About Us / Contact / cart / Account).

### Step 5.2: Click the Account nav link

- [ ] **Step 5.2: Verify account template renders into #app-root**

Click the Account nav link in the header.

Expected: `<main id="app-root">` is now populated with the Sign In form. The form has email + password fields, a Sign In button, and a Create Account button.

In DevTools console:

```js
({
    accountLoginVisible: getComputedStyle(document.getElementById('acc-login')).display !== 'none',
    accountRegisterHidden: getComputedStyle(document.getElementById('acc-register')).display === 'none',
    accountDashboardHidden: getComputedStyle(document.getElementById('acc-dashboard')).display === 'none',
    appAuthDefined: typeof (window.app && window.app.auth) === 'object',
    vmCustomerDefined: typeof window.vmCustomer === 'object'
})
```

All five should be truthy / `'object'`.

### Step 5.3: Toggle to register

- [ ] **Step 5.3: Click "Create Account"**

Click the "Create Account" button below the password field.

Expected: register form (with First Name, Last Name, Email, Password) is visible; login form is hidden.

```js
({
    loginHidden: getComputedStyle(document.getElementById('acc-login')).display === 'none',
    registerVisible: getComputedStyle(document.getElementById('acc-register')).display !== 'none'
})
```

### Step 5.4: Toggle back

- [ ] **Step 5.4: Click "Back to Sign In"**

Expected: login visible, register hidden.

### Step 5.5: Inject a session manually (captured-BASE workaround)

- [ ] **Step 5.5: Register via direct fetch + render dashboard**

vm-customer.js captured its BASE at script-load time, so we can't easily redirect it at runtime. Instead, register via direct fetch, push token into localStorage, then call `renderState`:

```js
(async () => {
    const BASE = 'http://localhost:8016/sites/debug.com/api.php';
    const email = 'heg-' + Date.now() + '@x.com';
    const r = await fetch(BASE + '?state=customer_register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, password: 'goodpass1', name: 'Heg Smoke', phone: null })
    });
    const d = await r.json();
    if (!d.ok) { console.error(d); return d; }
    localStorage.setItem('vm_customer_token', d.token);
    localStorage.setItem('vm_customer_cache', JSON.stringify(d.customer));
    app.auth.renderState();
    return 'rendered';
})()
```

Expected: returns `'rendered'`. The account view now shows the dashboard ("Welcome, Heg" + email + "No orders yet." + Sign Out button).

### Step 5.6: Verify dashboard contents

- [ ] **Step 5.6: Check the dashboard rendered correctly**

```js
({
    fname: document.querySelector('.js-greet-fname').textContent,
    email: document.querySelector('.js-greet-email').textContent,
    ordersText: document.querySelector('.js-orders-list').textContent,
    loginHidden: getComputedStyle(document.getElementById('acc-login')).display === 'none',
    dashboardVisible: getComputedStyle(document.getElementById('acc-dashboard')).display !== 'none'
})
```

Expected: `fname: "Heg"`, email present, orders text is "No orders yet." (or "Loading…" momentarily), `loginHidden: true`, `dashboardVisible: true`.

### Step 5.7: Sign out

- [ ] **Step 5.7: Click "Sign Out"**

Expected: localStorage is cleared, login form is visible again, dashboard is hidden.

```js
({
    tokenCleared: !localStorage.getItem('vm_customer_token'),
    loginVisible: getComputedStyle(document.getElementById('acc-login')).display !== 'none',
    dashboardHidden: getComputedStyle(document.getElementById('acc-dashboard')).display === 'none'
})
```

### Step 5.8: Pass/fail summary

- [ ] **Step 5.8: Record outcome**

If steps 5.2–5.7 all pass, the port is verified. Any failure is a real bug — fix and re-commit.

---

## Verification checklist

Before declaring sub-project D.5 done:

- [ ] `themes/hastings.ego/interface` has exactly one `<script src="vm-customer.js">`.
- [ ] `tpl-account` template contains three sections with IDs `acc-login`, `acc-register`, `acc-dashboard`.
- [ ] `window.app.auth` is defined when the page loads.
- [ ] All seven smoke steps in Task 5 pass.
- [ ] The existing `<script src="https://themes.varsitymarket.tech/plugin/store.js">` is still present (we don't replace it).
- [ ] `window.StoreConfig`, `open_menu`, `close_menu`, `heroShinker` are still present in the original inline `<script>` block.
- [ ] No other theme was modified.
- [ ] `module/`, `api/`, `skel/` are untouched.
