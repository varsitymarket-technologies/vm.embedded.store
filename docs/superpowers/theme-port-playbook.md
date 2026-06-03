# Theme Port Playbook

Use this guide when porting a commerce-skeleton theme to the customer
auth + account API. The pattern was validated against `austin` (D.2)
and `aura` (D.3.1) and is now the canonical reference for the
remaining D phases.

## Themes covered

The 19 commerce-skeleton themes (audit's Bronze tier + the 4/7/1
PDt/Cart/Chkt cluster):

- **`aura`** — canonical reference (D.3.1).
- **`crown`, `eros`, `imara`, `kinetic`, `linus`, `lucid`, `makhesa`,
  `mono`, `oasis`, `osmossis`, `pvt`, `revenge`, `terra`** — 4/7/1
  lookalikes; near-identical `app = {}` skeleton, Tailwind + Lucide
  styling.
- **`corselle`, `gta`, `lafromage`, `mashala`, `starved.hustla`,
  `street`** — 2/8/2 cousins. Same playbook applies; minor markup
  differences likely.

**Not covered by this playbook** (need their own per-theme spec):
- `austin` (custom-heavy auth, ported in D.2 — see its spec)
- `hastings.ego` (small surface; D.5)
- `anti`, `oaklyn`, `ourchieve` (full commerce, NO auth UI; D.4)
- `default`, `lafromage` (broken / cart-only — fix-or-remove decisions
  in D.6)

## Prerequisites

Before starting any port:

1. **Theme sync must be suspended.** D.2 already added the guard:
   `.theme-sync-suspended` marker exists at the project root. Verify
   with `ls .theme-sync-suspended`. Without this, your edits get
   overwritten on every page request.
2. **`skel/vm-customer.js` must exist.** It was shipped in D.1.
3. **The local-dev helper copy** — copy `skel/vm-customer.js` to
   `themes/<name>/vm-customer.js` so smoke testing via
   `/themes/<name>/interface` works (the engine's iframe routing is
   currently unreliable; this is the workaround).
4. **`themes/` is gitignored** at the project level. Use
   `git add -f themes/<name>/interface` to commit edits.

## The 5-question integration checklist

Answer these BEFORE editing the theme. Capture answers in the commit
message of the first commit so reviewers can follow.

1. **File path** — always `themes/<name>/interface`.
2. **`</head>` line** — `grep -n "</head>" themes/<name>/interface`.
   Record the number.
3. **Cart-button parent selector** — Look at the header's right-side
   button cluster. In aura: `<div class="flex items-center
   space-x-6 text-neutral-500">`. The account button (Edit 2) goes
   between the Search and Cart buttons. Record:
   - Search button's outer `class` value (the new account button
     reuses this).
   - The cart icon library (Lucide / Bootstrap Icons / inline SVG).
4. **`view-section` insertion point** — `grep -n "<section id=\"view-"`.
   The `view-account` section goes immediately before `view-checkout`
   (or whichever is the theme's last view-section).
5. **Toast function** — `grep -n "Toast\|triggerToast\|showToast" themes/<name>/interface`.
   Record signature: `triggerToast(msg)` vs `showToast(msg, type)` vs
   nothing. If nothing, drop in the micro-toast helper from Common
   Variations.

## The 5 edits

### Edit 1: `<head>` snippet

Insert immediately before `</head>`:

```html

    <!-- D.3.x: customer auth helper (vm-customer.js from same dir as api.php) -->
    <script src="vm-customer.js" defer></script>
    <script>
        window.addEventListener('vm:customer-loaded', function () {
            document.body.dataset.customer = 'logged-in';
            if (window.app && app.auth && typeof app.auth.updateIcon === 'function') app.auth.updateIcon();
        });
        window.addEventListener('vm:customer-login', function () {
            document.body.dataset.customer = 'logged-in';
            if (window.app && app.auth && typeof app.auth.updateIcon === 'function') app.auth.updateIcon();
        });
        window.addEventListener('vm:customer-logout', function () {
            document.body.dataset.customer = 'logged-out';
            if (window.app && app.auth && typeof app.auth.updateIcon === 'function') app.auth.updateIcon();
        });
    </script>
```

**If the theme has a localStorage user mockup** (austin had `aus_user`),
add a cleanup stanza just before the listeners:

```html
    <script>
        (function () {
            try {
                if (!localStorage.getItem('vm_customer_token') && localStorage.getItem('<<LEGACY_KEY>>')) {
                    localStorage.removeItem('<<LEGACY_KEY>>');
                }
            } catch (e) { /* ignore */ }
        })();
    </script>
```

### Edit 2: Account button in the header

Aura template — insert between Search and Cart buttons:

```html
                <button id="auth-icon-btn"
                        class="<<SEARCH_BUTTON_CLASSES>>"
                        onclick="app.auth.iconClick()"
                        aria-label="Account">
                    <span class="<<SEARCH_LABEL_CLASSES>>" id="auth-icon-label">Account</span>
                    <i data-lucide="user" class="<<SEARCH_ICON_CLASSES>>"></i>
                </button>
```

Replace `<<SEARCH_BUTTON_CLASSES>>`, `<<SEARCH_LABEL_CLASSES>>`,
`<<SEARCH_ICON_CLASSES>>` with the exact same class strings used on the
theme's existing Search button (or whichever utility icon button
already exists). This keeps visual rhythm consistent.

If the theme uses Bootstrap Icons instead of Lucide, swap
`<i data-lucide="user">` with `<i class="bi bi-person">`.

### Edit 3: Auth modal overlay

Insert before the theme's toast overlay (or anywhere late in `<body>`).
Template — fill in `<<TOKENS>>` from the theme's existing form styling:

```html
    <!-- D.3.x: Auth modal overlay -->
    <div id="auth-modal" class="<<MODAL_OVERLAY_CLASSES>>">
        <div class="<<MODAL_INNER_CLASSES>>">
            <div class="<<MODAL_HEADER_CLASSES>>">
                <<BRAND_LOGO_MARKUP>>
                <button onclick="app.auth.close()" aria-label="Close" class="<<CLOSE_BUTTON_CLASSES>>">
                    <i data-lucide="x" class="<<CLOSE_ICON_CLASSES>>"></i>
                </button>
            </div>

            <div class="<<TAB_NAV_CLASSES>>">
                <button id="auth-tab-login"    onclick="app.auth.switchTab('login')"    class="auth-tab <<TAB_BTN_CLASSES>>">Sign In</button>
                <button id="auth-tab-register" onclick="app.auth.switchTab('register')" class="auth-tab <<TAB_BTN_CLASSES>>">Create</button>
                <button id="auth-tab-forgot"   onclick="app.auth.switchTab('forgot')"   class="auth-tab <<TAB_BTN_CLASSES>>">Forgot</button>
            </div>

            <div id="auth-panel-login" class="auth-panel">
                <form onsubmit="event.preventDefault(); app.auth.submitLogin()" class="<<FORM_CLASSES>>">
                    <div>
                        <label class="<<LABEL_CLASSES>>">Email</label>
                        <input type="email" id="auth-login-email" required class="<<INPUT_CLASSES>>">
                    </div>
                    <div>
                        <label class="<<LABEL_CLASSES>>">Password</label>
                        <input type="password" id="auth-login-pass" required class="<<INPUT_CLASSES>>">
                    </div>
                    <div id="auth-login-error" class="<<ERROR_HIDDEN_CLASSES>>"></div>
                    <button type="submit" class="<<PRIMARY_BUTTON_CLASSES>>">Sign In</button>
                </form>
            </div>

            <div id="auth-panel-register" class="auth-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.submitRegister()" class="<<FORM_CLASSES>>">
                    <div class="<<TWO_COL_GRID_CLASSES>>">
                        <div><label class="<<LABEL_CLASSES>>">First Name</label>
                             <input type="text" id="auth-reg-fname" required class="<<INPUT_CLASSES>>"></div>
                        <div><label class="<<LABEL_CLASSES>>">Last Name</label>
                             <input type="text" id="auth-reg-lname" class="<<INPUT_CLASSES>>"></div>
                    </div>
                    <div><label class="<<LABEL_CLASSES>>">Email</label>
                         <input type="email" id="auth-reg-email" required class="<<INPUT_CLASSES>>"></div>
                    <div><label class="<<LABEL_CLASSES>>">Password</label>
                         <input type="password" id="auth-reg-pass" required minlength="8" class="<<INPUT_CLASSES>>"></div>
                    <div><label class="<<LABEL_CLASSES>>">Confirm Password</label>
                         <input type="password" id="auth-reg-pass2" required minlength="8" class="<<INPUT_CLASSES>>"></div>
                    <div id="auth-reg-error" class="<<ERROR_HIDDEN_CLASSES>>"></div>
                    <button type="submit" class="<<PRIMARY_BUTTON_CLASSES>>">Create Account</button>
                </form>
            </div>

            <div id="auth-panel-forgot" class="auth-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.submitForgot()" class="<<FORM_CLASSES>>">
                    <p class="<<HELPER_TEXT_CLASSES>>">Enter the email associated with your account and we'll send a reset link when this feature becomes available.</p>
                    <div><label class="<<LABEL_CLASSES>>">Email</label>
                         <input type="email" id="auth-forgot-email" required class="<<INPUT_CLASSES>>"></div>
                    <button type="submit" class="<<PRIMARY_BUTTON_CLASSES>>">Send Reset</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .auth-tab { color: rgb(115 115 115); border-bottom: 1px solid transparent; transition: all 0.3s; }
        .auth-tab.active { color: #000; border-bottom-color: #000; }
        #auth-modal.is-open { display: flex; }
    </style>
```

**Token substitutions for aura's port** (use as a baseline; theme-specific tweaks below):

| Token | aura's value |
|---|---|
| `<<MODAL_OVERLAY_CLASSES>>` | `fixed inset-0 bg-white z-50 hidden flex-col items-center justify-start overflow-y-auto p-6` |
| `<<MODAL_INNER_CLASSES>>` | `w-full max-w-md mx-auto pt-12 pb-24` |
| `<<INPUT_CLASSES>>` | `w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors` |
| `<<LABEL_CLASSES>>` | `block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2` |
| `<<PRIMARY_BUTTON_CLASSES>>` | `w-full bg-black text-white text-[11px] uppercase tracking-[0.15em] py-3 mt-4 hover:bg-neutral-800 transition-colors` |
| `<<ERROR_HIDDEN_CLASSES>>` | `hidden text-[11px] text-red-600 mt-2` |
| `<<FORM_CLASSES>>` | `space-y-6` |

### Edit 4: `view-account` section

Insert before the theme's `<section id="view-checkout">`. Template:

```html
        <!-- D.3.x: account view -->
        <section id="view-account" class="view-section <<SECTION_CONTAINER_CLASSES>>">
            <div class="<<SECTION_HEADER_CLASSES>>">
                <div>
                    <p class="<<HEADER_EYEBROW_CLASSES>>">Account</p>
                    <h1 id="acc-greeting" class="<<HEADER_TITLE_CLASSES>>">Welcome</h1>
                </div>
                <button onclick="app.auth.doLogout()" class="<<SIGNOUT_CLASSES>>">Sign Out</button>
            </div>

            <div class="<<TAB_NAV_CLASSES>>">
                <button id="acc-tab-overview" onclick="app.auth.activateTab('overview')" class="acc-tab <<TAB_BTN_CLASSES>>">Overview</button>
                <button id="acc-tab-profile"  onclick="app.auth.activateTab('profile')"  class="acc-tab <<TAB_BTN_CLASSES>>">Profile</button>
                <button id="acc-tab-orders"   onclick="app.auth.activateTab('orders')"   class="acc-tab <<TAB_BTN_CLASSES>>">Orders</button>
                <button id="acc-tab-password" onclick="app.auth.activateTab('password')" class="acc-tab <<TAB_BTN_CLASSES>>">Password</button>
            </div>

            <div id="acc-panel-overview" class="acc-panel">
                <div class="<<OVERVIEW_GRID_CLASSES>>">
                    <div><p class="<<EYEBROW_CLASSES>>">Name</p><p id="acc-ov-name" class="<<VALUE_CLASSES>>">—</p></div>
                    <div><p class="<<EYEBROW_CLASSES>>">Email</p><p id="acc-ov-email" class="<<VALUE_CLASSES>>">—</p></div>
                    <div><p class="<<EYEBROW_CLASSES>>">Member Since</p><p id="acc-ov-joined" class="<<VALUE_CLASSES>>">—</p></div>
                </div>
                <button onclick="app.auth.activateTab('profile')" class="<<LINK_BUTTON_CLASSES>>">Edit profile</button>
            </div>

            <div id="acc-panel-profile" class="acc-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.saveProfile()" class="<<FORM_CLASSES>>">
                    <div class="<<TWO_COL_GRID_CLASSES>>">
                        <div><label class="<<LABEL_CLASSES>>">First Name</label>
                             <input type="text" id="acc-fname" class="<<INPUT_CLASSES>>"></div>
                        <div><label class="<<LABEL_CLASSES>>">Last Name</label>
                             <input type="text" id="acc-lname" class="<<INPUT_CLASSES>>"></div>
                    </div>
                    <div><label class="<<LABEL_CLASSES>>">Email</label>
                         <input type="email" id="acc-email" readonly title="Email is the account identifier and cannot be changed here" class="<<INPUT_READONLY_CLASSES>>"></div>
                    <div><label class="<<LABEL_CLASSES>>">Phone (optional)</label>
                         <input type="tel" id="acc-phone" class="<<INPUT_CLASSES>>"></div>
                    <button type="submit" class="<<PRIMARY_BUTTON_CLASSES>>">Save</button>
                </form>
            </div>

            <div id="acc-panel-orders" class="acc-panel hidden">
                <div id="acc-orders-list">
                    <p class="<<EMPTY_STATE_CLASSES>>">Loading…</p>
                </div>
            </div>

            <div id="acc-panel-password" class="acc-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.changePassword()" class="<<FORM_CLASSES>>">
                    <div><label class="<<LABEL_CLASSES>>">Current Password</label>
                         <input type="password" id="acc-pw-cur" required class="<<INPUT_CLASSES>>"></div>
                    <div><label class="<<LABEL_CLASSES>>">New Password</label>
                         <input type="password" id="acc-pw-new" required minlength="8" class="<<INPUT_CLASSES>>"></div>
                    <div><label class="<<LABEL_CLASSES>>">Confirm New Password</label>
                         <input type="password" id="acc-pw-conf" required minlength="8" class="<<INPUT_CLASSES>>"></div>
                    <button type="submit" class="<<PRIMARY_BUTTON_CLASSES>>">Change Password</button>
                </form>
            </div>
        </section>

        <style>
            .acc-tab { color: rgb(115 115 115); border-bottom: 1px solid transparent; transition: all 0.3s; }
            .acc-tab.active { color: #000; border-bottom-color: #000; }
        </style>
```

For the full token-substituted reference, see `themes/aura/interface`.

### Edit 5: `app.auth` handlers + router wiring

Two parts.

**Part A — insert before `window.addEventListener('DOMContentLoaded', () => app.init());`**:

The `app.auth = {...}` block is ~200 lines of vanilla JS. **Copy it
verbatim from `themes/aura/interface`** — search for `app.auth = {`
in that file. No theme-specific tokens inside the JS itself.

**Part B — modify `app.router.evalRoute` to handle `#account`**:

Find the existing `evalRoute` function. After the
`else if (view === '#checkout')` branch (or whichever is the last
existing branch), add:

```js
                else if (view === '#account') {
                    if (!app.auth || !app.auth.isLoggedIn()) {
                        if (app.auth) app.auth.open('login');
                        const homeView = document.getElementById('view-home');
                        if (targetView) targetView.classList.remove('active');
                        if (homeView) homeView.classList.add('active');
                    } else {
                        app.auth.renderDashboard();
                    }
                }
```

**Part C — expose `app` on `window`** (CRITICAL — easy to miss):

aura's `app` is declared as `const`, which does NOT attach it to
`window`. The head snippet's listeners check `window.app` and silently
skip the update if it's undefined. Add immediately before
`window.addEventListener('DOMContentLoaded', ...)`:

```js
        // Expose app on window so the head snippet's event listeners
        // can reach into app.auth.updateIcon when vm:customer-loaded fires.
        window.app = app;
```

**Part D — token substitution in pasted JS**:

If the theme's toast function is NOT `app.ui.triggerToast`, search and
replace in the pasted `app.auth` block:

```bash
# Example: theme uses showToast instead of app.ui.triggerToast
sed -i 's/app\.ui\.triggerToast/showToast/g' themes/<name>/interface
```

## Common variations

### Theme uses Bootstrap Icons instead of Lucide

In Edit 2 (header button) and Edit 3 (modal close button), swap:

```html
<i data-lucide="user" class="..."></i>
<!-- becomes -->
<i class="bi bi-person ..."></i>
```

And inside `app.auth.open()`, remove the `lucide.createIcons()` call.

### Theme has no toast function

Drop this helper in just before the `app.auth = {...}` block:

```js
        if (!app.ui) app.ui = {};
        if (!app.ui.triggerToast) {
            app.ui.triggerToast = function (msg) {
                let t = document.getElementById('vm-mini-toast');
                if (!t) {
                    t = document.createElement('div');
                    t.id = 'vm-mini-toast';
                    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#000;color:#fff;padding:10px 16px;font-size:12px;z-index:1000;border-radius:4px;opacity:0;transition:opacity 0.3s;';
                    document.body.appendChild(t);
                }
                t.textContent = msg;
                t.style.opacity = '1';
                clearTimeout(t._vmTimer);
                t._vmTimer = setTimeout(function () { t.style.opacity = '0'; }, 2400);
            };
        }
```

### Theme has no `app = {}` skeleton

Then the theme isn't in this playbook's group. Refer to D.2 (austin)
for the "heavy auth mockup" pattern or wait for D.4/D.5 specs.

## Smoke test sequence

After all 5 edits:

1. Navigate to `http://localhost:8016/themes/<name>/interface`.
2. In DevTools console, confirm structure:

```js
({
  appOnWindow: typeof window.app,           // should be 'object'
  authOnApp: typeof (window.app && window.app.auth),  // 'object'
  vmCustomer: typeof window.vmCustomer,     // 'object'
  hasAccountBtn: !!document.getElementById('auth-icon-btn'),
  hasAuthModal: !!document.getElementById('auth-modal'),
  hasViewAccount: !!document.getElementById('view-account')
})
```

All six should return truthy / `'object'`.

3. Test modal mechanics:

```js
app.auth.iconClick();   // modal should open at Sign In tab
app.auth.switchTab('register');  // panel switches
app.auth.close();        // modal closes
```

4. Inject a session manually for the dashboard test (the
   `vm-customer.js` BASE was captured at script load; can't be
   re-routed at runtime):

```js
(async () => {
  const BASE = 'http://localhost:8016/sites/debug.com/api.php';
  const r = await fetch(BASE + '?state=customer_register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: 'smoke-' + Date.now() + '@x.com', password: 'goodpass1', name: 'Smoke Tester', phone: null })
  });
  const d = await r.json();
  localStorage.setItem('vm_customer_token', d.token);
  localStorage.setItem('vm_customer_cache', JSON.stringify(d.customer));
  app.auth.updateIcon();             // icon label flips to "Smoke"
  await app.auth.renderDashboard();  // populates account view
})();
```

5. Verify:

- Header icon label changed from "Account" to the customer's first name.
- `document.getElementById('acc-greeting').textContent` starts with "Welcome, ".
- `acc-fname` input value is "Smoke", `acc-lname` is "Tester".
- `acc-email` input is `readOnly === true`.
- Calling `app.auth.activateTab('profile')` shows profile panel.

6. Cleanup: `localStorage.clear(); location.reload();` — header
   should revert to "Account".

## Common pitfalls

- **`window.app` is undefined** — `const app = {...}` doesn't attach to
  window. Add `window.app = app;` explicitly. (CRITICAL — easy to miss
  during the port; the head snippet's listener silently no-ops without
  it.)
- **Modal opens but `<i data-lucide="...">` icons render as blank** —
  Lucide hasn't re-scanned the new DOM. `app.auth.open()` already calls
  `lucide.createIcons()`; the playbook's template includes this. If
  you're seeing blank icons elsewhere (e.g., after async tab switches
  that build markup), call `lucide.createIcons()` again.
- **CORS errors on `fetch`** — Keep the helper and the API on the same
  `localhost:8016` origin. If your local dev runs the theme on a
  different port, configure CORS on the deployment.
- **`vmCustomer` is undefined** — The `<script src="vm-customer.js">`
  failed to resolve. For `/themes/<name>/interface` testing, you MUST
  have copied `skel/vm-customer.js` into `themes/<name>/vm-customer.js`
  (Prerequisites step 3).
- **Theme sync overwrote your edits** — Verify `.theme-sync-suspended`
  exists at the project root: `ls .theme-sync-suspended`. Without it,
  `.register.php` re-pulls themes on every request.
- **`state=order` doesn't stamp `customer_id`** — A logged-in customer's
  new order lands as a guest order. This is a separate engine bug
  carried over from D.2; not part of any theme port. Order history will
  appear correct for orders backfilled at register time (sub-project A
  behavior), but new logged-in checkouts go un-linked. Tracked for a
  future phase.

## Per-theme work estimate

- 5-10 min: integration checklist + token discovery.
- 10-15 min: paste in Edits 1-4 with token substitutions.
- 5-10 min: paste in Edit 5 + the `window.app = app` + router branch.
- 5-10 min: structural smoke test.
- 5 min: commit.

**Total: ~30-50 minutes per theme.**

## Tracking the cohort

Maintain a checklist in `docs/superpowers/specs/2026-06-03-theme-storefront-baseline-design.md`
or a per-D-phase plan doc:

| Theme | Status | PR/commit | Smoke test pass |
|---|---|---|---|
| aura | ✅ D.3.1 | `78c1ed3`, `3a1e807` | ✅ structural + dashboard population |
| crown | pending | — | — |
| eros | pending | — | — |
| (etc.) | | | |
