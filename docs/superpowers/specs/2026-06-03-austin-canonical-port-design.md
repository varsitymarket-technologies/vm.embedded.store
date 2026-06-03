# Port `austin` to vmCustomer — Design (Sub-project D.2)

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co
**Parent decomposition:** Second of six sub-sub-projects under sub-project
D (Wire theme dashboards to the real auth + API). D.1 (foundation) shipped.
Remaining:

- D.3 — Port `aura` + template-apply to 18 lookalike themes
- D.4 — Port `anti`, `oaklyn`, `ourchieve`
- D.5 — Port `hastings.ego`
- D.6 — Decide fate of `lafromage` + `default`

Audit doc: [2026-06-03-theme-storefront-baseline-design.md](2026-06-03-theme-storefront-baseline-design.md).
D.1 spec: [2026-06-03-theme-customer-auth-foundation-design.md](2026-06-03-theme-customer-auth-foundation-design.md).

## Summary

Replace `austin`'s localStorage-backed auth mockup with real `vmCustomer.*`
calls. Keep the rest of austin's UI exactly as it is. Suspend the upstream
theme sync during D so local edits stick. Treat the resulting austin as the
canonical reference D.3–D.5 will crib patterns from.

Sub-project A's customer endpoints (register / login / logout / me) and
sub-project B's account endpoints (my_orders / update_profile /
change_password) become reachable to austin through D.1's `vm-customer.js`
helper. Address book endpoints and email verification remain out of scope.

## Goals

- Make austin's existing login, register, dashboard, my-orders, profile-edit,
  and password-change UI hit the real backend instead of `localStorage`.
- Preserve austin's visual structure and copy verbatim — D.2 is a wiring
  rewrite, not a redesign.
- Establish patterns (the `auth` adapter, the fname/lname client-side
  join/split, the dashboard-orders hydration sequence) that D.3+ can
  template into other themes.
- Suspend the upstream theme sync project-wide for the duration of D so
  in-flight per-theme edits don't get overwritten on every request.

## Non-goals

- **No wishlist backend.** `aus_wishlist` localStorage stays. A future phase
  can add a wishlist API; D.2 doesn't touch it.
- **No address book wiring.** `aus_addresses` localStorage stays; the
  panel gets a "sync coming soon" badge. Real address endpoints will land
  with the rest of D's bonus phase.
- **No `delete_account` endpoint.** The danger-zone delete button is hidden
  (left in HTML comment for D.6 or later).
- **No `forgot_password` flow.** `doForgot` keeps its placeholder toast.
  Email sender doesn't exist yet (per sub-project A's deferral).
- **No fname/lname split in the backend.** The backend `customers.name`
  stays a single column; austin joins/splits client-side. Lossy for
  multi-word first names; acceptable per UX decision in brainstorming.
- **No theme aesthetic changes.** No color, layout, copy, font, or component
  swaps. Pure wiring.
- **No changes to other themes.** D.3+ port the other themes.
- **No changes to `vm-customer.js` or the backend modules.** D.1's
  foundation is treated as stable.

## User flow

A customer visits an austin-themed storefront and:

1. Page loads. `vm-customer.js` boots; if a `vm_customer_token` is in
   localStorage, the helper resolves it via `customer_me` and fires
   `vm:customer-loaded`. austin's listener sets
   `document.body.dataset.customer = 'logged-in'` and calls
   `updateAccountBtn()`.
2. User clicks the header account icon. If logged out, `openAuthModal()`
   shows the login tab. If logged in, the router navigates to
   `#dashboard`.
3. **Register flow**: user fills first name, last name, email, password.
   austin submits `vmCustomer.register(email, password, "First Last", null)`.
   Backend creates the account, returns token + customer. `vm:customer-login`
   fires; modal closes; toast greets the user; router navigates to
   `#dashboard`.
4. **Login flow**: user submits email + password. austin calls
   `vmCustomer.login(email, password)`. On 401, toast shows "Invalid email
   or password". On 429, toast shows "Account temporarily locked. Try again
   in a few minutes."
5. **Dashboard**: `renderDashboard()` calls `vmCustomer.myOrders()` to fetch
   the customer's orders, then populates the orders stat card, total spent,
   wishlist count (from localStorage), address count (from localStorage),
   and the overview list. Profile form is pre-filled from
   `vmCustomer.cached()` after splitting `name` into fname/lname.
6. **Save profile**: user edits fname/lname/phone, clicks Save. austin
   joins fname+lname into `name`, calls
   `vmCustomer.updateProfile(name, phone)`. The helper updates
   `vm_customer_cache`. Sidebar name/avatar re-render from the cached value.
7. **Change password**: user enters current + new + confirm. austin calls
   `vmCustomer.changePassword(current, new)`. On 400, error message shown
   in toast. On success, the helper transparently swaps tokens; toast
   confirms.
8. **Logout**: header avatar dropdown → Sign out. austin calls
   `vmCustomer.logout()`. The helper clears state and fires
   `vm:customer-logout`. austin's listener flips `data-customer` and
   navigates to home.

The address book and wishlist panels work as today — entirely client-side.
The addresses panel shows a small "sync coming soon" badge as a UX hint
that this section will hit the backend later.

## Suspending theme sync (engine-wide)

`.register.php` calls `sync_themes()` on every request, which re-pulls
every theme from
`varsitymarket-technologies/embedded-themes` GitHub. Local edits to
`themes/austin/interface` would be overwritten on the next request.

The fix is one guard at the call site (around line 71 of `.register.php`):

```php
// Suspend theme sync during sub-project D. Local edits to themes/<name>/
// must survive across requests. Re-enable by removing or flipping
// VM_SUSPEND_THEME_SYNC after D ships.
$suspend = getenv('VM_SUSPEND_THEME_SYNC')
    || file_exists(__DIR__ . '/.theme-sync-suspended');
if (!$suspend) {
    $e = sync_themes();
}
```

A `touch .theme-sync-suspended` creates the flag file. D.2 introduces this
guard and creates the flag. The file is added to `.gitignore`. When D
finishes (after D.6), the flag is removed.

## Files touched

**Modify:**

- `.register.php` — add the suspend guard (one if statement around the
  existing `sync_themes()` call).
- `themes/austin/interface` — add `vm-customer.js` script tag + 5-line
  event-listener snippet to `<head>`; replace the `auth` object internals
  (~5 lines); rewire `doLogin`, `doRegister`, `doForgot`, `doLogout`,
  `renderDashboard`, `saveAccountInfo`, `changePassword`, and a small
  helper (`hydrateOrdersForDashboard`) (~70 lines of edits across ~120
  lines of existing code); mark addresses panel with badge; hide
  delete-account button.

**Create:**

- `.theme-sync-suspended` — empty marker file (gitignored).

**No changes** to:

- `module/customer_auth.php`, `module/customer_account.php`,
  `module/database.php` — D.1 foundation
- `api/index.php`, `skel/api.php`, `skel/vm-customer.js` — D.1 foundation
- Any other theme

## Backend reachability

When austin is the active theme on a store at `sites/<domain>/`, the
storefront loads from `sites/<domain>/index.php` which serves the theme
files. `vm-customer.js` and `api.php` both resolve relatively to the page
URL, so they hit `sites/<domain>/vm-customer.js` and
`sites/<domain>/api.php` respectively — which were synced from `skel/`
during D.1. No further plumbing is needed for D.2.

`sites/<domain>/vm-customer.js` does NOT exist today; it will be
auto-synced by the next theme deploy, but D.2's testing requires it to
exist now. The implementation will sync `skel/vm-customer.js` to every
existing `sites/<domain>/` directory as a one-shot, same way D.1 synced
`skel/api.php`.

## The `auth` adapter

The crucial pattern. austin's `auth` object (lines 2767-2772) is currently:

```js
const auth = {
    getUser() { try { return JSON.parse(localStorage.getItem('aus_user')); } catch { return null; } },
    setUser(u) { localStorage.setItem('aus_user', JSON.stringify(u)); },
    clearUser() { localStorage.removeItem('aus_user'); },
    isLoggedIn() { return !!this.getUser(); }
};
```

Replaced with a thin adapter that delegates to `vmCustomer`:

```js
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
            // orders are hydrated by hydrateOrdersForDashboard() before
            // renderDashboard reads them; default to [] otherwise.
            orders: window.__aus_orders__ || []
        };
    },
    isLoggedIn() { return !!window.vmCustomer && vmCustomer.isLoggedIn(); }
    // setUser + clearUser deleted — handlers call vmCustomer directly.
};
```

Every `auth.getUser()` call site in austin works unchanged because the
returned object has the same shape (fname/lname/email/phone/orders).

Every `auth.setUser(u)` and `auth.clearUser()` call site needs to be
rewritten — those operations now happen through `vmCustomer.updateProfile`
or `vmCustomer.logout` directly. There are six such call sites: `doLogin`,
`doRegister`, `doLogout`, `confirmDeleteAccount` (which is being hidden),
`saveAccountInfo`, `changePassword`. All are rewired explicitly in the
relevant handler section below.

## Submit-handler rewrites

### `doLogin` (currently line 3297)

**Before** (28 lines, localStorage scan with auto-create fallback):

Reads `aus_users`, searches for matching email/password, if not found
auto-creates a new user. Real login should reject unknown.

**After:**

```js
async function doLogin() {
    const email = document.getElementById('login-email').value.trim();
    const pass = document.getElementById('login-pass').value;
    if (!email || !pass) { showToast('Please enter your email and password', 'error'); return; }
    try {
        await vmCustomer.login(email, pass);
        // vm:customer-login fires; CSS flips data-customer; updateAccountBtn runs.
        closeAuthModal();
        const u = auth.getUser();
        showToast(`Welcome back, ${u && u.fname ? u.fname : 'friend'}!`);
        router.nav('dashboard');
    } catch (e) {
        if (e.code === 'locked') {
            showToast('Account temporarily locked. Try again in a few minutes.', 'error');
        } else {
            showToast(e.message || 'Sign-in failed', 'error');
        }
    }
}
```

### `doRegister` (currently line 3314)

**Before:** local `aus_users` push, dupe check, set `aus_user`, navigate.

**After:**

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
        closeAuthModal();
        showToast(`Welcome to Austin, ${fname}!`);
        router.nav('dashboard');
    } catch (e) {
        showToast(e.message || 'Sign-up failed', 'error');
    }
}
```

### `doForgot` (currently line 3333) — unchanged

Still shows a fake toast. There's no backend reset endpoint. A comment
notes "TODO: wire to a future customer_request_password_reset endpoint"
is acceptable here (spec considers this an explicit deferral, not a TODO
placeholder in code).

### `doLogout` (currently line 3340)

**Before:** clears localStorage + toasts + navigates.

**After:**

```js
async function doLogout() {
    try { await vmCustomer.logout(); }
    catch { /* helper clears local state regardless */ }
    // vm:customer-logout event listener handles dataset + updateAccountBtn.
    showToast('You have been signed out', 'info');
    router.nav('home');
}
```

### `renderDashboard` (currently line 3364)

**Before:** synchronous read of `auth.getUser()`, populate from `u.orders`.

**After:**

```js
async function renderDashboard() {
    if (!auth.isLoggedIn()) { openAuthModal('login'); return; }
    await hydrateOrdersForDashboard();   // populates window.__aus_orders__
    const u = auth.getUser();
    if (!u) { openAuthModal('login'); return; }
    // ...rest is unchanged: same DOM IDs, same fields read from u
    // (sb-name, sb-email, sb-avatar, dash-first, acc-fname, acc-lname,
    //  acc-email, acc-phone, st-orders, st-spent, st-wish, st-addr, etc.)
}

async function hydrateOrdersForDashboard() {
    try {
        const r = await vmCustomer.myOrders();
        // Map the API order shape into the structure austin's old code
        // expected (`{ id, total, status, date, items }`).
        window.__aus_orders__ = (r.orders || []).map(o => ({
            id: o.id,
            total: o.total_amount,
            status: o.status,
            date: o.created_at,
            items: o.items || [],
        }));
    } catch {
        window.__aus_orders__ = [];
    }
}
```

The existing population code in `renderDashboard` (sidebar avatar/name,
overview-orders list, stat cards, profile form pre-fill) is unchanged
because it reads from `u.fname`, `u.lname`, `u.email`, `u.phone`,
`u.orders` — all of which the adapter returns correctly.

### `saveAccountInfo` (currently line 3476)

**Before:** writes to `aus_user` localStorage + updates `aus_users` array.

**After:**

```js
async function saveAccountInfo() {
    const fname = document.getElementById('acc-fname').value.trim();
    const lname = document.getElementById('acc-lname').value.trim();
    const phone = document.getElementById('acc-phone').value.trim();
    const name = (fname + ' ' + lname).trim();
    try {
        await vmCustomer.updateProfile(name || null, phone || null);
        const u = auth.getUser();
        if (u) {
            document.getElementById('sb-name').textContent = (u.fname + ' ' + u.lname).trim();
            document.getElementById('sb-avatar').textContent =
                (u.fname.charAt(0) + (u.lname ? u.lname.charAt(0) : '')).toUpperCase();
        }
        showToast('Profile updated');
    } catch (e) {
        showToast(e.message || 'Update failed', 'error');
    }
}
```

The `acc-email` input gets a `readonly` attribute in markup — email is
not editable client-side (sub-project A's non-goal).

### `changePassword` (currently line 3494)

**Before:** validates against `aus_user.password`, updates two localStorage
entries.

**After:**

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
        ['pw-cur', 'pw-new', 'pw-conf'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        showToast('Password updated');
    } catch (e) {
        showToast(e.message || 'Failed to change password', 'error');
    }
}
```

### `confirmDeleteAccount` (currently line 3353)

**Before:** confirm dialog + clears localStorage.

**After:** **the function is removed and its button is hidden** in markup
via a comment marker:

```html
<!-- Re-enable when a customer_delete_account endpoint exists (no such endpoint
     today; intentionally deferred). -->
<!-- <button class="btn-danger" onclick="confirmDeleteAccount()">Delete account</button> -->
```

## Markup edits

Three small markup edits:

1. **`<head>` snippet** — add `<script src="vm-customer.js" defer>` plus the
   3-event listener block.
2. **`acc-email` input** — add `readonly` attribute.
3. **Addresses panel** — add a `<span class="badge-soon">Sync coming soon</span>`
   inline note near the panel header. CSS for the badge piggybacks on
   austin's existing utility classes (small uppercase, ash color); single
   new CSS rule added in the existing `<style>` block.
4. **Delete-account button** — comment out, with a TODO marker referencing
   the deferred endpoint.

That's all four markup changes. The rest of austin's HTML is untouched.

## Failure modes & guards

| Scenario | Behavior |
|---|---|
| `vm-customer.js` not loaded (404 / network) | austin's calls to `vmCustomer` throw `ReferenceError`. `auth.getUser()` returns null. Dashboard prompts login. Account icon stays in logged-out state. The login form would fail with a TypeError on submit. To prevent this, the `auth` adapter checks `window.vmCustomer` before touching anything. Submit handlers wrap calls in try/catch and toast the error. |
| Storefront loaded at a URL where `api.php` resolves to a 404 | `vmCustomer.me()` rejects on init; `vm:customer-loaded` doesn't fire; user stays in logged-out UI. Subsequent login attempt rejects with a network error in the toast. This is the same failure surface every API call already has — austin's product/cart code degrades the same way. |
| Customer logs in, then changes password on another tab | The other tab's token is now invalid. Any subsequent API call from that tab returns 401 → vm-customer.js auto-logout fires → austin's `vm:customer-logout` listener flips `data-customer` and updates the account button. The user sees themself silently logged out, which is correct. |
| Customer registers with an existing email | Backend returns `{ ok: false, error: "Email already registered" }`, helper rejects with `error.message`. Toast shows the message. No client-side dupe check. |
| Network failure during register / login | Helper rejects; toast shows network error message. austin doesn't pre-validate. |
| Customer has no orders yet | `myOrders` returns `{ ok: true, orders: [] }`. `__aus_orders__` becomes `[]`. Dashboard renders "0 orders, $0 spent" and the overview list shows the existing empty state. |
| Customer has > 100 orders | Backend caps at 100. Dashboard shows only the most recent 100 (per `vmCustomer.myOrders` behavior). Acceptable for D.2. |
| Profile name with multiple words ("Mary Ann Smith") | Joined → `"Mary Ann Smith"` server side. On render: fname = "Mary", lname = "Ann Smith". Lossy but predictable. Documented in the brainstorming decision. |
| Customer typed wrong current password during `changePassword` | Backend returns HTTP 400 (NOT 401, per D.1's status code refinement) → helper rejects with `error.message = "Current password is incorrect"` → toast shows. No auto-logout fires. |
| Customer changes password successfully | Helper swaps tokens transparently. austin's three input fields are cleared. Toast confirms. No re-login required. |
| Address book or wishlist changes | Continue to work as today — pure localStorage, no API involved. |

## Security notes

- **No new tokens introduced.** austin uses only `vm-customer.js`'s
  helper, which already handles the `X-Customer-Token` header.
- **`aus_user` localStorage entry is no longer used.** Existing customers
  who had `aus_user` in localStorage from the old mockup carry stale data.
  On next page load with no `vm_customer_token`, austin's `auth.getUser()`
  returns null and the customer is treated as logged out — they need to
  log in again. This is acceptable since the old mockup auth had no
  cross-device state anyway. Add a one-time cleanup: when adopting the
  new auth, if `vm_customer_token` is absent but `aus_user` is present,
  delete `aus_user`. Adds two lines of init code.
- **XSS in `name`** — austin renders `u.fname` and `u.lname` via
  `textContent` everywhere I checked (sb-name, dash-first, sb-avatar).
  `textContent` is XSS-safe. The implementer should verify this during
  the port.

## Testing

End-to-end Playwright verification against the running demo store. The
test sequence (matching the user-flow section above):

1. Start docker; visit `http://localhost:8016/`; click Demo Account.
2. Navigate to the demo store's admin → Themes → set `austin` as the
   active theme.
3. Visit the storefront (the iframe URL the engine generates).
4. Click the account icon → modal opens.
5. Register a fresh email + password.
6. Verify dashboard loads with the new customer's name; orders list is
   empty; profile form shows the customer's data.
7. Edit profile (fname, lname, phone) → save → reload → fields persist.
8. Change password → reload → log in with new password.
9. Logout via menu → account icon shows logged-out state.
10. Log back in → dashboard shows the customer again.
11. Place an order (if cart/checkout works on the demo store) → return to
    dashboard → orders stat increments; the order shows in the overview
    list.
12. Open browser DevTools → verify `localStorage.vm_customer_token` is
    set when logged in, cleared when logged out.

If step 11 fails because the cart/checkout flow doesn't actually persist
an order linked to `customer_id` (a possible gap: at checkout time, the
order handler may not look at `X-Customer-Token` to stamp `customer_id`),
that's logged as a finding for a follow-up phase — but D.2's wiring work
is considered complete since the rest of the flow works end-to-end. The
backfill logic in sub-project A handles previously-placed guest orders
on register, but new orders placed by an already-logged-in customer need
their own treatment, which is outside D.2's scope.

No automated test suite is added. The browser smoke is manual.

## Open questions

None. Approved in brainstorming on 2026-06-03.
