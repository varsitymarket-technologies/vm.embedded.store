# Austin Canonical Port Implementation Plan (Sub-project D.2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire `themes/austin/interface` to the real customer auth + account API via `vmCustomer.*` calls, replacing the localStorage `aus_user`/`aus_users` mockup, while keeping austin's visual structure unchanged. Establishes the canonical port pattern D.3–D.5 will copy.

**Architecture:** One small engine change (suspend the upstream theme sync so local edits stick) plus surgical edits in `themes/austin/interface`. A thin `auth` adapter delegates `getUser()`/`isLoggedIn()` to `vmCustomer`; six submit handlers (`doLogin`, `doRegister`, `doLogout`, `renderDashboard`, `saveAccountInfo`, `changePassword`) are rewritten to call the helper directly. `setUser`/`clearUser` from the old mockup go away. fname/lname split happens client-side; backend stays with single `name`.

**Tech Stack:** PHP 7.4+ (engine), vanilla browser JS, no build step, no new dependencies.

**Spec:** [docs/superpowers/specs/2026-06-03-austin-canonical-port-design.md](../specs/2026-06-03-austin-canonical-port-design.md)

---

## File Map

**Modify:**
- `.register.php` — Add a one-statement guard around the existing `sync_themes()` call so local theme edits survive across requests.
- `.gitignore` — Add `.theme-sync-suspended` so the local flag file doesn't get committed.
- `themes/austin/interface` — Add `vm-customer.js` script + 5-line head snippet; replace the `auth` object internals (lines 2767-2772); rewire 6 handlers; mark addresses panel; hide delete-account block; make `acc-email` readonly.

**Create:**
- `.theme-sync-suspended` — Empty marker file (gitignored). Touched into existence to flip the suspend flag.

**Sync to existing per-site copies** (one-shot, gitignored):
- `sites/<domain>/vm-customer.js` — copy of `skel/vm-customer.js` so loaded themes can resolve it.

**Untouched:**
- `module/customer_auth.php`, `module/customer_account.php`, `module/database.php`
- `api/index.php`, `skel/api.php`
- `skel/vm-customer.js` (D.1 foundation)
- Every other theme

---

## Notes for the implementer

- The container is `vm-emb-sites`; project mounted at `/var/www/html/public/`.
- austin's source file is `themes/austin/interface` (no extension — it's the storefront HTML+CSS+JS bundle).
- austin already uses these line ranges for the items we'll touch:
  - **Line 1587:** `</head>` (insertion point for the 5-line snippet — insert immediately BEFORE this line).
  - **Lines 2462-2500:** Addresses dash-panel (badge insertion target).
  - **Lines 2517-2526:** Profile form including the `acc-email` input we're making readonly.
  - **Lines 2544-2553:** Danger Zone delete-account block to hide.
  - **Lines 2767-2772:** The `auth` object to replace.
  - **Lines 3297-3344:** `doLogin`, `doRegister`, `doForgot`, `doLogout`, `updateAccountBtn`, `confirmDeleteAccount`.
  - **Line 3364:** `renderDashboard` function start.
  - **Line 3476:** `saveAccountInfo` function start.
  - **Line 3494:** `changePassword` function start.
- Always edit `themes/austin/interface` directly. Once the sync is suspended (Task 1), the edits persist.
- The demo store at `claude.test` is the cleanest target for end-to-end testing — it was seeded with sample data earlier in the session. To test austin: log in via Demo Account, set austin as the active theme via the admin's Themes page, then load the storefront iframe URL the dashboard displays.

---

## Task 1: Suspend upstream theme sync

**Files:**
- Modify: `.register.php`
- Modify: `.gitignore`
- Create: `.theme-sync-suspended` (empty marker file)

### Step 1.1: Add the suspend guard in `.register.php`

- [ ] **Step 1.1: Edit `.register.php`**

Find the existing call near the bottom of the file (around line 71):

```php
$e = sync_themes();
```

Replace with:

```php
// Suspend theme sync during sub-project D. Local edits to themes/<name>/
// must survive across requests. Re-enable by removing or flipping
// VM_SUSPEND_THEME_SYNC after D ships (or deleting .theme-sync-suspended).
$suspend = getenv('VM_SUSPEND_THEME_SYNC')
    || file_exists(__DIR__ . '/.theme-sync-suspended');
if (!$suspend) {
    $e = sync_themes();
}
```

### Step 1.2: Create the marker file

- [ ] **Step 1.2: Create `.theme-sync-suspended`**

```bash
touch .theme-sync-suspended
```

Confirm:

```bash
ls -la .theme-sync-suspended
```

Expected: file exists with zero bytes.

### Step 1.3: Add to `.gitignore`

- [ ] **Step 1.3: Edit `.gitignore`**

Open `.gitignore`. Add this line if not already present:

```
.theme-sync-suspended
```

Verify it's now ignored:

```bash
git check-ignore .theme-sync-suspended
```

Expected output: `.theme-sync-suspended` (the path itself, indicating it matched a gitignore rule).

### Step 1.4: Verify the sync is actually suspended

- [ ] **Step 1.4: Test by editing a tiny harmless thing and confirming it sticks**

Pick any theme file and append a comment, then load a page that triggers `.register.php`, then re-read the file:

```bash
# Note the current hash of the theme's records.json (only sync target that touches local state)
md5sum themes/records.json > /tmp/records-before
# Trigger a request that hits .register.php (the engine front controller does)
curl -s -o /dev/null http://localhost:8016/
# Verify themes/records.json didn't change
md5sum themes/records.json > /tmp/records-after
diff /tmp/records-before /tmp/records-after
```

Expected: `diff` returns no output (files identical → sync didn't run).

If the diff is non-empty, the suspend guard isn't catching. Re-check `.register.php` for syntax errors:

```bash
php -l .register.php
```

### Step 1.5: Syntax check

- [ ] **Step 1.5: Syntax check**

```bash
docker exec vm-emb-sites bash -c "php -l /var/www/html/public/.register.php"
```

Expected: `No syntax errors detected`.

### Step 1.6: Commit

- [ ] **Step 1.6: Commit**

```bash
git add .register.php .gitignore
git commit -m "feat: suspend upstream theme sync via guard + flag file (D.2 prep)

.register.php now skips sync_themes() when either:
- VM_SUSPEND_THEME_SYNC env var is set
- .theme-sync-suspended marker file exists (gitignored, local)

This unblocks sub-project D (theme wiring): local edits to
themes/<name>/ now survive across requests instead of being
overwritten by the GitHub pull. Re-enabled by removing the
marker / env var after D.6 wraps."
```

---

## Task 2: Sync `vm-customer.js` to existing per-site directories

**Files:**
- Copy: `skel/vm-customer.js` → `sites/<each>/vm-customer.js`

### Step 2.1: Run the sync loop

- [ ] **Step 2.1: Sync the helper to each existing site dir**

```bash
for site in sites/*/; do
  cp -f skel/vm-customer.js "$site/vm-customer.js"
  echo "synced: $site/vm-customer.js"
done
```

Expected output: one line per existing site (the demo dirs created during this session).

### Step 2.2: Confirm a couple of copies exist

- [ ] **Step 2.2: Sanity check**

```bash
ls -la sites/claude.test/vm-customer.js sites/debug.com/vm-customer.js 2>&1 | head
```

Expected: two file entries, each ~5-6 KB (the size of `skel/vm-customer.js`).

### Step 2.3: No commit needed

- [ ] **Step 2.3: Skip the commit**

`sites/` is gitignored. The synced files live on this host only. Future deploys to new sites will copy `skel/vm-customer.js` automatically as part of provisioning. No git commit needed; just proceed.

---

## Task 3: Add the `<head>` snippet to austin

**Files:**
- Modify: `themes/austin/interface` (line 1587 — `</head>`)

### Step 3.1: Edit `themes/austin/interface`

- [ ] **Step 3.1: Insert the snippet before `</head>`**

Find this exact line (line 1587):

```html
</head>
```

Insert this block **immediately before** that line:

```html

    <!-- D.2: customer auth helper (vm-customer.js from skel/, served from same dir as api.php) -->
    <script src="vm-customer.js" defer></script>
    <script>
        // One-time cleanup of stale aus_user from the pre-D.2 mockup.
        // Safe to remove this stanza after a few weeks once existing
        // customers' caches have rotated.
        (function () {
            try {
                if (!localStorage.getItem('vm_customer_token') && localStorage.getItem('aus_user')) {
                    localStorage.removeItem('aus_user');
                }
            } catch (e) { /* ignore */ }
        })();

        // Toggle data-customer + sync austin's nav avatar on auth events.
        window.addEventListener('vm:customer-loaded', function () {
            document.body.dataset.customer = 'logged-in';
            if (typeof updateAccountBtn === 'function') updateAccountBtn();
        });
        window.addEventListener('vm:customer-login', function () {
            document.body.dataset.customer = 'logged-in';
            if (typeof updateAccountBtn === 'function') updateAccountBtn();
        });
        window.addEventListener('vm:customer-logout', function () {
            document.body.dataset.customer = 'logged-out';
            if (typeof updateAccountBtn === 'function') updateAccountBtn();
        });
    </script>
</head>
```

### Step 3.2: Confirm no other `<head>` interleaves got harmed

- [ ] **Step 3.2: Sanity check the edit**

```bash
grep -n "vm-customer.js" themes/austin/interface
grep -c "</head>" themes/austin/interface
```

Expected: one match for `vm-customer.js` (the new script tag); exactly one `</head>` (we didn't accidentally double-close).

### Step 3.3: Commit

- [ ] **Step 3.3: Commit**

```bash
git add themes/austin/interface
git commit -m "feat(theme/austin): wire vm-customer.js into <head> (D.2 step 1/4)

Adds the script tag + 3 DOM event listeners + a one-time
aus_user cleanup stanza. Sets document.body.dataset.customer
on login/logout/loaded; pings the existing updateAccountBtn()
when it exists so the header avatar reflects state.

Adapter + handler rewrites land in the next commits."
```

---

## Task 4: Replace the `auth` adapter + rewire all six handlers

**Files:**
- Modify: `themes/austin/interface` (lines 2767-2772 auth object, 3297-3344 submit handlers, 3364 renderDashboard, 3476 saveAccountInfo, 3494 changePassword)
- Modify: `themes/austin/interface` (lines 2517-2526 acc-email readonly, 2462-2500 addresses badge, 2544-2553 delete-account hide)

### Step 4.1: Replace the `auth` object

- [ ] **Step 4.1: Edit `themes/austin/interface` — auth object**

Find the existing block at lines 2767-2772:

```js
        const auth = {
            getUser() { try { return JSON.parse(localStorage.getItem('aus_user')); } catch { return null; } },
            setUser(u) { localStorage.setItem('aus_user', JSON.stringify(u)); },
            clearUser() { localStorage.removeItem('aus_user'); },
            isLoggedIn() { return !!this.getUser(); }
        };
```

Replace with:

```js
        // D.2: auth adapter delegating to vm-customer.js. The returned shape
        // matches what austin's existing render code expects: fname/lname
        // (split from backend's single name field), email, phone, joinDate,
        // and orders (hydrated separately by hydrateOrdersForDashboard()).
        const auth = {
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
                    joinDate: c.created_at || '',
                    orders: window.__aus_orders__ || []
                };
            },
            isLoggedIn() { return !!window.vmCustomer && vmCustomer.isLoggedIn(); }
            // setUser + clearUser deleted — call sites now go through
            // vmCustomer.updateProfile / vmCustomer.logout directly.
        };
```

### Step 4.2: Rewire `doLogin`

- [ ] **Step 4.2: Edit `themes/austin/interface` — doLogin (around line 3297)**

Find this existing function:

```js
        function doLogin() {
            const email = document.getElementById('login-email').value.trim();
            const pass = document.getElementById('login-pass').value;
            if (!email || !pass) { showToast('Please enter your email and password', 'error'); return; }
            let users = JSON.parse(localStorage.getItem('aus_users') || '[]');
            const user = users.find(u => u.email === email && u.password === pass);
            if (!user) {
                const newUser = { email, password: pass, fname: 'Member', lname: '', phone: '', orders: [], joinDate: new Date().toLocaleDateString() };
                users.push(newUser); localStorage.setItem('aus_users', JSON.stringify(users));
                auth.setUser(newUser);
            } else { auth.setUser(user); }
            closeAuthModal();
            showToast(`Welcome back, ${auth.getUser().fname}!`);
            updateAccountBtn();
            router.nav('dashboard');
        }
```

Replace with:

```js
        async function doLogin() {
            const email = document.getElementById('login-email').value.trim();
            const pass = document.getElementById('login-pass').value;
            if (!email || !pass) { showToast('Please enter your email and password', 'error'); return; }
            try {
                await vmCustomer.login(email, pass);
                // vm:customer-login event fires; updateAccountBtn runs from the head listener.
                closeAuthModal();
                const u = auth.getUser();
                showToast('Welcome back, ' + (u && u.fname ? u.fname : 'friend') + '!');
                router.nav('dashboard');
            } catch (e) {
                if (e && e.code === 'locked') {
                    showToast('Account temporarily locked. Try again in a few minutes.', 'error');
                } else {
                    showToast((e && e.message) || 'Sign-in failed', 'error');
                }
            }
        }
```

### Step 4.3: Rewire `doRegister`

- [ ] **Step 4.3: Edit `themes/austin/interface` — doRegister (around line 3314)**

Find this existing function:

```js
        function doRegister() {
            const fname = document.getElementById('reg-fname').value.trim();
            const lname = document.getElementById('reg-lname').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const pass = document.getElementById('reg-pass').value;
            const pass2 = document.getElementById('reg-pass2').value;
            if (!fname || !email || !pass) { showToast('Please fill in all fields', 'error'); return; }
            if (pass !== pass2) { showToast('Passwords do not match', 'error'); return; }
            if (pass.length < 8) { showToast('Password must be at least 8 characters', 'error'); return; }
            const newUser = { email, password: pass, fname, lname, phone: '', orders: [], joinDate: new Date().toLocaleDateString() };
            let users = JSON.parse(localStorage.getItem('aus_users') || '[]');
            if (users.find(u => u.email === email)) { showToast('An account with this email already exists', 'error'); return; }
            users.push(newUser); localStorage.setItem('aus_users', JSON.stringify(users));
            auth.setUser(newUser); closeAuthModal();
            showToast(`Welcome to Austin, ${fname}!`);
            updateAccountBtn();
            router.nav('dashboard');
        }
```

Replace with:

```js
        async function doRegister() {
            const fname = document.getElementById('reg-fname').value.trim();
            const lname = document.getElementById('reg-lname').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const pass  = document.getElementById('reg-pass').value;
            const pass2 = document.getElementById('reg-pass2').value;
            if (!fname || !email || !pass) { showToast('Please fill in all fields', 'error'); return; }
            if (pass !== pass2)  { showToast('Passwords do not match', 'error'); return; }
            if (pass.length < 8) { showToast('Password must be at least 8 characters', 'error'); return; }
            const name = (fname + ' ' + lname).trim();
            try {
                await vmCustomer.register(email, pass, name, null);
                // vm:customer-login event fires; updateAccountBtn runs from the head listener.
                closeAuthModal();
                showToast('Welcome to Austin, ' + fname + '!');
                router.nav('dashboard');
            } catch (e) {
                showToast((e && e.message) || 'Sign-up failed', 'error');
            }
        }
```

### Step 4.4: Leave `doForgot` unchanged

- [ ] **Step 4.4: Verify `doForgot` is untouched**

`doForgot` (around line 3333) keeps its fake-toast behavior — there's no
backend reset endpoint yet. Confirm by grepping:

```bash
grep -n -A 4 "function doForgot" themes/austin/interface
```

Expected: the four-line function showing `showToast('Reset link sent to your email', 'info'); switchTab('login');` unchanged.

### Step 4.5: Rewire `doLogout`

- [ ] **Step 4.5: Edit `themes/austin/interface` — doLogout (around line 3340)**

Find:

```js
        function doLogout() {
            auth.clearUser(); updateAccountBtn();
            showToast('You have been signed out', 'info');
            router.nav('home');
        }
```

Replace with:

```js
        async function doLogout() {
            try { await vmCustomer.logout(); }
            catch (e) { /* helper clears local state regardless */ }
            // vm:customer-logout event runs updateAccountBtn() from the head listener.
            showToast('You have been signed out', 'info');
            router.nav('home');
        }
```

### Step 4.6: Remove `confirmDeleteAccount` JS function

- [ ] **Step 4.6: Edit `themes/austin/interface` — delete the function (around line 3353)**

Find:

```js
        function confirmDeleteAccount() {
            if (confirm('Are you sure you want to permanently delete your account? This cannot be undone.')) {
                auth.clearUser(); updateAccountBtn();
                showToast('Account deleted', 'info');
                router.nav('home');
            }
        }
```

Replace with:

```js
        // D.2: confirmDeleteAccount removed — no customer_delete_account
        // endpoint exists. The button it bound to is hidden in markup. When
        // a delete endpoint ships in a future phase, re-add the function
        // and un-comment the button (lines 2544-2553).
```

### Step 4.7: Rewire `renderDashboard` + add `hydrateOrdersForDashboard`

- [ ] **Step 4.7: Edit `themes/austin/interface` — renderDashboard (around line 3364)**

Find this existing function:

```js
        function renderDashboard() {
            const u = auth.getUser();
            if (!u) { openAuthModal('login'); return; }

            document.getElementById('sb-name').textContent = `${u.fname} ${u.lname}`.trim();
            document.getElementById('sb-email').textContent = u.email;
            document.getElementById('sb-avatar').textContent = `${u.fname.charAt(0)}${u.lname ? u.lname.charAt(0) : ''}`.toUpperCase();
            document.getElementById('dash-first').textContent = u.fname;

            document.getElementById('acc-fname').value = u.fname || '';
            document.getElementById('acc-lname').value = u.lname || '';
            document.getElementById('acc-email').value = u.email || '';
            document.getElementById('acc-phone').value = u.phone || '';

            const userOrders = u.orders || [];
            document.getElementById('st-orders').textContent = userOrders.length;
            document.getElementById('st-spent').textContent = fmt(userOrders.reduce((s, o) => s + (o.total || 0), 0));
            document.getElementById('st-wish').textContent = wishlist.items.length;
            document.getElementById('st-addr').textContent = addresses.list.length;

            renderOverviewOrders(userOrders);
            switchPanel('overview');
```

Replace with this exact block (preserving the `renderOverviewOrders` + `switchPanel` calls at the end):

```js
        async function renderDashboard() {
            if (!auth.isLoggedIn()) { openAuthModal('login'); return; }
            // Hydrate orders from the API BEFORE reading auth.getUser(), so
            // u.orders (which adapter reads from window.__aus_orders__) is populated.
            await hydrateOrdersForDashboard();
            const u = auth.getUser();
            if (!u) { openAuthModal('login'); return; }

            document.getElementById('sb-name').textContent = (u.fname + ' ' + u.lname).trim();
            document.getElementById('sb-email').textContent = u.email;
            document.getElementById('sb-avatar').textContent =
                (u.fname.charAt(0) + (u.lname ? u.lname.charAt(0) : '')).toUpperCase();
            document.getElementById('dash-first').textContent = u.fname;

            document.getElementById('acc-fname').value = u.fname || '';
            document.getElementById('acc-lname').value = u.lname || '';
            document.getElementById('acc-email').value = u.email || '';
            document.getElementById('acc-phone').value = u.phone || '';

            const userOrders = u.orders || [];
            document.getElementById('st-orders').textContent = userOrders.length;
            document.getElementById('st-spent').textContent = fmt(userOrders.reduce(function (s, o) { return s + (o.total || 0); }, 0));
            document.getElementById('st-wish').textContent = wishlist.items.length;
            document.getElementById('st-addr').textContent = addresses.list.length;

            renderOverviewOrders(userOrders);
            switchPanel('overview');
```

Immediately after the closing brace of `renderDashboard` (which exists later in the file), insert this helper function:

```js

        // D.2: pull orders from the API and stash on window.__aus_orders__
        // in the shape austin's render code already expects
        // ({ id, total, status, date, items }).
        async function hydrateOrdersForDashboard() {
            try {
                const r = await vmCustomer.myOrders();
                window.__aus_orders__ = (r.orders || []).map(function (o) {
                    return {
                        id: o.id,
                        total: o.total_amount,
                        status: o.status,
                        date: o.created_at,
                        items: o.items || []
                    };
                });
            } catch (e) {
                window.__aus_orders__ = [];
            }
        }
```

### Step 4.8: Rewire `saveAccountInfo`

- [ ] **Step 4.8: Edit `themes/austin/interface` — saveAccountInfo (around line 3476)**

Find this existing function:

```js
        function saveAccountInfo() {
            const u = auth.getUser(); if (!u) return;
            u.fname = document.getElementById('acc-fname').value.trim();
            u.lname = document.getElementById('acc-lname').value.trim();
            u.email = document.getElementById('acc-email').value.trim();
            u.phone = document.getElementById('acc-phone').value.trim();
            auth.setUser(u);
            document.getElementById('sb-name').textContent = `${u.fname} ${u.lname}`.trim();
            document.getElementById('sb-email').textContent = u.email;
            document.getElementById('sb-avatar').textContent = `${u.fname.charAt(0)}${u.lname ? u.lname.charAt(0) : ''}`.toUpperCase();
            let users = JSON.parse(localStorage.getItem('aus_users') || '[]');
            const idx = users.findIndex(x => x.email === u.email);
            if (idx > -1) { users[idx] = u; localStorage.setItem('aus_users', JSON.stringify(users)); }
            showToast('Profile updated');
        }
```

Replace with:

```js
        async function saveAccountInfo() {
            const fname = document.getElementById('acc-fname').value.trim();
            const lname = document.getElementById('acc-lname').value.trim();
            const phone = document.getElementById('acc-phone').value.trim();
            const name = (fname + ' ' + lname).trim();
            try {
                await vmCustomer.updateProfile(name || null, phone || null);
                // helper updates vm_customer_cache; pull from there for re-render
                const u = auth.getUser();
                if (u) {
                    document.getElementById('sb-name').textContent = (u.fname + ' ' + u.lname).trim();
                    document.getElementById('sb-avatar').textContent =
                        (u.fname.charAt(0) + (u.lname ? u.lname.charAt(0) : '')).toUpperCase();
                }
                showToast('Profile updated');
            } catch (e) {
                showToast((e && e.message) || 'Update failed', 'error');
            }
        }
```

### Step 4.9: Rewire `changePassword`

- [ ] **Step 4.9: Edit `themes/austin/interface` — changePassword (around line 3494)**

Find this existing function:

```js
        function changePassword() {
            const cur = document.getElementById('pw-cur').value;
            const nw = document.getElementById('pw-new').value;
            const conf = document.getElementById('pw-conf').value;
            if (!cur || !nw || !conf) { showToast('Please fill all password fields', 'error'); return; }
            if (nw.length < 8) { showToast('New password must be at least 8 characters', 'error'); return; }
            if (nw !== conf) { showToast('Passwords do not match', 'error'); return; }
            const u = auth.getUser(); if (!u) return;
            if (u.password !== cur) { showToast('Current password is incorrect', 'error'); return; }
            u.password = nw; auth.setUser(u);
            let users = JSON.parse(localStorage.getItem('aus_users') || '[]');
            const idx = users.findIndex(x => x.email === u.email);
            if (idx > -1) { users[idx] = u; localStorage.setItem('aus_users', JSON.stringify(users)); }
            ['pw-cur', 'pw-new', 'pw-conf'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
            showToast('Password updated');
        }
```

Replace with:

```js
        async function changePassword() {
            const cur = document.getElementById('pw-cur').value;
            const nw  = document.getElementById('pw-new').value;
            const conf = document.getElementById('pw-conf').value;
            if (!cur || !nw || !conf) { showToast('Please fill all password fields', 'error'); return; }
            if (nw.length < 8)  { showToast('New password must be at least 8 characters', 'error'); return; }
            if (nw !== conf)    { showToast('Passwords do not match', 'error'); return; }
            try {
                await vmCustomer.changePassword(cur, nw);
                ['pw-cur', 'pw-new', 'pw-conf'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                showToast('Password updated');
            } catch (e) {
                showToast((e && e.message) || 'Failed to change password', 'error');
            }
        }
```

### Step 4.10: Make `acc-email` readonly

- [ ] **Step 4.10: Edit `themes/austin/interface` — acc-email input (around line 2521)**

Find this exact line:

```html
                        <div class="form-group"><label class="form-label">Email</label><input type="email"
                                class="form-input" id="acc-email"></div>
```

Replace with:

```html
                        <div class="form-group"><label class="form-label">Email</label><input type="email"
                                class="form-input" id="acc-email" readonly
                                title="Email is the account identifier and cannot be changed here"></div>
```

### Step 4.11: Add the "sync coming soon" badge to the addresses panel

- [ ] **Step 4.11: Edit `themes/austin/interface` — addresses panel header (around line 2464)**

Find this exact block:

```html
                <!-- Addresses -->
                <div class="dash-panel" id="panel-addresses">
                    <div class="dash-title">Addresses</div>
                    <div class="dash-sub">Saved Shipping &amp; Billing Addresses</div>
```

Replace with:

```html
                <!-- Addresses -->
                <div class="dash-panel" id="panel-addresses">
                    <div class="dash-title">
                        Addresses
                        <span style="font-size:10px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--ash);background:rgba(120,120,120,0.08);padding:3px 8px;border-radius:99px;margin-left:8px;vertical-align:middle;">Sync coming soon</span>
                    </div>
                    <div class="dash-sub">Saved Shipping &amp; Billing Addresses &middot; <em style="color:var(--ash);">stored locally for now</em></div>
```

### Step 4.12: Hide the delete-account block

- [ ] **Step 4.12: Edit `themes/austin/interface` — danger zone (around lines 2544-2553)**

Find this exact block:

```html
                    <div class="acc-section" style="border:1.5px solid rgba(192,57,43,0.18);">
                        <div class="acc-section-title" style="color:var(--warn);">Danger Zone</div>
                        <p style="font-size:13px;color:var(--ash);margin-bottom:14px;line-height:1.65;">Permanently
                            delete your account and all associated data. This cannot be undone.</p>
                        <button onclick="confirmDeleteAccount()"
                            style="background:none;border:1.5px solid var(--warn);color:var(--warn);padding:9px 22px;border-radius:99px;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;cursor:pointer;transition:all 0.2s;"
                            onmouseover="this.style.background='var(--warn)';this.style.color='#fff'"
                            onmouseout="this.style.background='none';this.style.color='var(--warn)'">Delete
                            Account</button>
                    </div>
```

Replace with:

```html
                    <!-- D.2: Danger Zone hidden. No customer_delete_account
                         endpoint exists; the old button only logged out
                         locally, which was misleading UX. Re-enable when a
                         delete endpoint ships and confirmDeleteAccount()
                         is re-added in the JS section.
                    <div class="acc-section" style="border:1.5px solid rgba(192,57,43,0.18);">
                        <div class="acc-section-title" style="color:var(--warn);">Danger Zone</div>
                        <p style="font-size:13px;color:var(--ash);margin-bottom:14px;line-height:1.65;">Permanently
                            delete your account and all associated data. This cannot be undone.</p>
                        <button onclick="confirmDeleteAccount()"
                            style="background:none;border:1.5px solid var(--warn);color:var(--warn);padding:9px 22px;border-radius:99px;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;cursor:pointer;transition:all 0.2s;"
                            onmouseover="this.style.background='var(--warn)';this.style.color='#fff'"
                            onmouseout="this.style.background='none';this.style.color='var(--warn)'">Delete
                            Account</button>
                    </div>
                    -->
```

### Step 4.13: Quick sanity grep + commit

- [ ] **Step 4.13: Sanity check**

```bash
echo "--- references to legacy mockup state ---"
grep -n "auth.setUser\|auth.clearUser\|aus_users\|aus_user" themes/austin/interface
echo "--- vmCustomer call sites ---"
grep -n "vmCustomer\." themes/austin/interface | head -20
echo "--- doForgot still present and unchanged ---"
grep -n -A 1 "function doForgot" themes/austin/interface
```

Expected:
- The first grep should match the one-time cleanup line that reads `localStorage.getItem('aus_user')` and removes it (from the head snippet). Otherwise no matches (no `auth.setUser`/`auth.clearUser`, no `aus_users` writes anywhere in the JS handlers).
- The vmCustomer grep should show ~10-12 call sites: `vmCustomer.isLoggedIn`, `.cached`, `.login`, `.register`, `.logout`, `.me` (implicit via init), `.myOrders`, `.updateProfile`, `.changePassword`.
- `doForgot` shows up still calling `showToast('Reset link sent ...', 'info')`.

- [ ] **Step 4.14: Commit**

```bash
git add themes/austin/interface
git commit -m "feat(theme/austin): replace auth mockup with vmCustomer calls (D.2 step 2/4)

- auth object becomes a thin adapter delegating getUser/isLoggedIn
  to vmCustomer. setUser/clearUser deleted.
- fname/lname split client-side from backend's single name field.
- doLogin: vmCustomer.login + 401/429 toast on failure.
- doRegister: joins fname+lname into name, vmCustomer.register.
- doLogout: vmCustomer.logout; UI cleanup via vm:customer-logout
  event listener in <head>.
- doForgot stays as placeholder (no backend reset endpoint).
- renderDashboard now async; hydrates orders via vmCustomer.myOrders
  into window.__aus_orders__ before populating the dashboard.
- saveAccountInfo posts updateProfile and re-renders from the
  cached customer object.
- changePassword posts to backend; helper swaps tokens transparently
  on success.
- confirmDeleteAccount JS function removed; Danger Zone button
  block commented out in markup (no delete endpoint exists).
- acc-email input now readonly (email is the identity, not editable).
- Addresses panel gets a 'sync coming soon' badge + 'stored locally
  for now' subtitle hint."
```

---

## Task 5: Manual end-to-end smoke test

**Files:** none (verification only)

### Step 5.1: Make austin the active theme on a test store

- [ ] **Step 5.1: Set the theme**

Open `http://localhost:8016/` in a browser. Click **Demo Account**. Navigate to the admin's Themes page for the demo store (`/vm-admin/<your-domain>/theme`). Click **Activate** on the austin card.

If the demo store's domain is `claude.test`, the admin URL is `http://localhost:8016/vm-admin/claude.test/theme`.

### Step 5.2: Confirm the storefront loads with austin

- [ ] **Step 5.2: Open the storefront iframe**

The admin's Overview page shows a storefront iframe URL (something like `http://localhost:8016/websites/store_<hash>/<token>/`). Open that URL in a new tab. Confirm austin's branded header (Shop / Collections / Sale / Our Roots / Contact + search icon + account avatar + cart) is visible.

### Step 5.3: Register a fresh account

- [ ] **Step 5.3: Register**

Click the account avatar. The auth modal opens. Click "Create account" tab. Fill in:

- First name: `Smoke`
- Last name: `Tester`
- Email: `smoke-d2-<timestamp>@x.com` (use something unique)
- Password: `goodpass1`
- Confirm: `goodpass1`

Submit. Expected:

1. Modal closes.
2. Toast: "Welcome to Austin, Smoke!"
3. URL changes to `#dashboard`.
4. Dashboard sidebar shows "Smoke Tester" + the email.
5. Avatar in sidebar shows "ST".
6. Stat cards: Orders 0, Spent $0.00, Wish 0, Addr 0.

Open DevTools → Application → Local Storage. Confirm:

- `vm_customer_token` is a 64-char hex string.
- `vm_customer_cache` is JSON like `{"id":...,"email":"smoke-d2-...","name":"Smoke Tester","phone":null,"email_verified":false,"created_at":"..."}`.
- `aus_user` is NOT present (cleanup stanza removed it).

### Step 5.4: Update the profile

- [ ] **Step 5.4: Edit profile**

In the dashboard, change the phone field to `+27 555 0001`. Click Save. Toast: "Profile updated".

Reload the page. Open the dashboard again. Phone field still shows `+27 555 0001`. Sidebar avatar still shows "ST".

### Step 5.5: Change password

- [ ] **Step 5.5: Change password**

In the dashboard, scroll to the password section. Fill:

- Current: `goodpass1`
- New: `newpass456`
- Confirm: `newpass456`

Submit. Expected toast: "Password updated".

In DevTools, confirm `vm_customer_token` has a different value than before (changePassword issues a new token).

### Step 5.6: Logout

- [ ] **Step 5.6: Sign out**

Click the account avatar → Sign Out. Expected:

1. Toast: "You have been signed out".
2. `localStorage.vm_customer_token` is gone.
3. The account avatar reverts to logged-out style.

### Step 5.7: Login with new password

- [ ] **Step 5.7: Log back in**

Click account → sign in. Enter the same email + `newpass456`. Submit. Expected: dashboard reappears with `Smoke Tester` populated.

### Step 5.8: Wrong-password lockout sanity check

- [ ] **Step 5.8: Optional — confirm 401 toasts work**

Log out. Try to log in with the right email but `WRONG`. Expected toast: "Invalid email or password" (or similar — the backend's exact message). No auto-account-creation happens (the old mockup auto-created on missing match; the new flow should NOT).

### Step 5.9: Confirm wishlist + addresses panels still work (localStorage)

- [ ] **Step 5.9: Address panel sanity**

Log back in. Navigate to the dashboard's Addresses panel. The "Sync coming soon" badge is visible. Click Add New Address. Fill the form and save. Address appears in the grid. Reload the page. Address persists (it's in `aus_addresses` localStorage).

### Step 5.10: Decide on commit

- [ ] **Step 5.10: Commit if any tweaks were needed**

If the smoke test surfaced issues you fixed inline, commit them now with a `fix:` message. If everything worked first time, no commit needed.

---

## Verification checklist

Before declaring sub-project D.2 done, confirm:

- [ ] `php -l .register.php` reports no syntax errors.
- [ ] `.theme-sync-suspended` exists and is gitignored.
- [ ] Editing `themes/austin/interface` persists across requests (a curl to the engine root does not re-pull from GitHub).
- [ ] Every existing `sites/<domain>/` has a `vm-customer.js` file (synced from skel/).
- [ ] `themes/austin/interface` has exactly ONE script tag for `vm-customer.js`.
- [ ] `themes/austin/interface` has zero remaining `auth.setUser` or `auth.clearUser` references.
- [ ] `themes/austin/interface` has zero remaining `aus_users` (the fake users array) references — but DOES have the one-time `aus_user` cleanup line in the head snippet.
- [ ] All 10 steps in Task 5's manual smoke test pass.
- [ ] After the smoke flow, `localStorage.vm_customer_token` and `vm_customer_cache` are managed entirely by the helper (austin never touches them directly outside the head cleanup stanza).
- [ ] No other theme was modified.
- [ ] `module/`, `api/index.php`, `skel/api.php`, `skel/vm-customer.js` are untouched.
