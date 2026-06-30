# Port `aura` + Author the Theme-Port Playbook — Design (Sub-project D.3.1)

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co
**Parent decomposition:** First of two sub-sub-projects under D.3 (port the
19-theme commerce-only template group). D.3.2 applies the playbook to 18
remaining themes.

D.1 (foundation) and D.2 (austin port) shipped earlier today. Remaining D
phases:

- D.3.2 — Apply the playbook to 18 commerce-only lookalike themes
- D.4 — Port `anti`, `oaklyn`, `ourchieve`
- D.5 — Port `hastings.ego`
- D.6 — Decide fate of `lafromage` + `default`

Audit doc: [2026-06-03-theme-storefront-baseline-design.md](2026-06-03-theme-storefront-baseline-design.md).
D.1 spec: [2026-06-03-theme-customer-auth-foundation-design.md](2026-06-03-theme-customer-auth-foundation-design.md).
D.2 spec: [2026-06-03-austin-canonical-port-design.md](2026-06-03-austin-canonical-port-design.md).

## Summary

Port `themes/aura/interface` to use `vmCustomer` and ship a reusable
"theme-port playbook" document that D.3.2/D.4/D.5 implementers use as
the integration guide for any commerce-skeleton theme. The playbook
captures the structural pattern derived during the aura port — header
button injection point, modal markup, account view section, JS handlers
— along with a per-theme integration checklist.

aura currently has no auth UI at all (audit score: 3/9). After this
sub-sub-project ships, aura passes the baseline: login + register +
dashboard + my-orders + profile + change-password all backed by the
real API.

## Goals

- Give aura a complete account UX visually consistent with its
  minimal-serif / soft-Tailwind aesthetic.
- Validate that the same paste-in template can be applied to other
  commerce-skeleton themes by extracting it into a documented playbook.
- Provide D.3.2 implementers with a "fill in the blanks" workflow:
  identify the cart icon's selector, paste the snippet, swap two
  theme-specific tokens, smoke-test.

## Non-goals

- **No batch-apply to other themes.** D.3.2 covers that.
- **No address book UI.** D.1 deferred address endpoints. Future D-phase.
- **No real forgot-password flow.** No backend endpoint exists.
- **No delete-account UI.** No backend endpoint exists.
- **No wishlist.** aura doesn't have a wishlist today; we don't add one.
- **No backend changes.** D.1/D.2 already shipped the API + helper.
- **No changes to other themes.** Only `themes/aura/interface` is edited.
- **No re-styling of aura's existing markup.** Only additions.

## User flow (aura-specific)

A customer visiting the aura storefront:

1. Page loads. `vm-customer.js` boots; if a `vm_customer_token` is in
   localStorage, the helper resolves it via `customer_me` and fires
   `vm:customer-loaded`. aura's listener sets
   `document.body.dataset.customer = 'logged-in'` and calls
   `app.auth.updateIcon()`.
2. Header shows a small "Account" text+icon button between the existing
   Search and Tote buttons. When logged out, clicking opens the auth
   modal. When logged in, clicking navigates to `#account`.
3. **Register flow**: user clicks "Create Account" tab in the modal,
   fills first name, last name, email, password. aura submits
   `vmCustomer.register(email, password, "First Last", null)`. On
   success the modal closes; `triggerToast('Welcome, First!')`.
4. **Login flow**: user submits email + password. aura calls
   `vmCustomer.login(...)`. On 429 (locked), toast shows
   "Account locked — try again in a few minutes." Other failures show
   the backend's error message verbatim.
5. **Account view**: `#account` route activates `view-account` section.
   Four sub-tabs: Overview / Profile / Orders / Password. Overview
   shows the customer's name + email + member-since-date. Tabs read
   from `vmCustomer.cached()` (instant) and `vmCustomer.myOrders()`
   (fetched on Orders tab activation).
6. **Save profile**: user edits name fields + phone, clicks Save.
   aura joins fname+lname → calls `vmCustomer.updateProfile(name, phone)`.
   The helper updates the cache; the form re-reads from cache after
   resolve.
7. **Change password**: standard 3-field form. Calls
   `vmCustomer.changePassword(current, new)`. On 400 with the
   "Current password is incorrect" body, the toast surfaces the
   message (no auto-logout because the change_password endpoint returns
   400 not 401 for that case — per D.1's status code refinement).
8. **Logout**: header button's logout option calls `vmCustomer.logout()`.
   `vm:customer-logout` fires; the header button flips style; router
   navigates to `#home`.

## Files touched

**Modify:**
- `themes/aura/interface` — five surgical additions detailed below.

**Create:**
- `docs/superpowers/theme-port-playbook.md` — the reusable playbook for
  D.3.2/D.4/D.5.

**Untouched:**
- All other themes
- `module/`, `api/`, `skel/`, sub-project A and B files

## Aura integration points (the 5 edits)

### Edit 1: `<head>` snippet

Same pattern as D.2's austin port — load `vm-customer.js` + 3 event
listeners + one-time `aus_user` cleanup (skipped for aura since aura
has no `aus_user` history). The listener calls
`app.auth.updateIcon()` (defined in Edit 5) rather than austin's
`updateAccountBtn()`. Inserted immediately before `</head>` (currently
line 56 of aura's interface).

### Edit 2: Account button in header

Inserted between aura's existing Search button (line 70-73 of the
current file) and the Tote button (lines 74-80), inside the
`<div class="flex items-center space-x-6 text-neutral-500">` wrapper
at line 69.

```html
<button id="auth-icon-btn"
        class="hover:text-black transition-colors duration-300 flex items-center"
        onclick="app.auth.iconClick()"
        aria-label="Account">
    <span class="text-[11px] uppercase tracking-[0.15em] hidden md:block" id="auth-icon-label">Account</span>
    <i data-lucide="user" class="w-4 h-4 stroke-[1] md:hidden"></i>
</button>
```

`app.auth.iconClick()` opens the modal when logged out, navigates to
`#account` when logged in.

`app.auth.updateIcon()` flips the label between "Account" (logged out)
and the customer's first name (logged in, e.g. "Jane"). Uses
`vmCustomer.cached()` for the name.

### Edit 3: Auth modal overlay

Inserted near the existing toast overlay (around line 248). Three tabs:
Sign In (default), Create Account, Forgot. Form inputs use aura's
`bg-transparent border-b` field style. Buttons use the existing
black-on-white pattern visible elsewhere in aura.

```html
<div id="auth-modal" class="fixed inset-0 bg-white z-50 hidden flex-col items-center justify-center p-4 overflow-y-auto">
    <!-- close button -->
    <!-- tabs: Sign In | Create Account | Forgot -->
    <!-- panel-login form: email + password + submit + "Forgot?" link -->
    <!-- panel-register form: fname + lname + email + password + confirm + submit -->
    <!-- panel-forgot form: email + "Send reset" (placeholder) -->
    <!-- inline error region per panel -->
</div>
```

Total ~120 lines including markup + minimal inline CSS for the tab
underline state.

### Edit 4: `view-account` section

Inserted as a new view-section alongside the existing ones, before
`view-checkout` (around line 185). Four tabs: Overview / Profile /
Orders / Password.

```html
<section id="view-account" class="view-section max-w-5xl mx-auto px-4 sm:px-8 py-16">
    <!-- header: greeting + logout button -->
    <!-- tab nav: Overview | Profile | Orders | Password -->
    <!-- tab-overview: name, email, member-since, "edit profile" CTA -->
    <!-- tab-profile: fname, lname, phone (email readonly), save -->
    <!-- tab-orders: list rendered from vmCustomer.myOrders() -->
    <!-- tab-password: current, new, confirm, change -->
</section>
```

Markup mirrors austin's dashboard at a structural level but uses aura's
visual tokens (`font-serif` for headings, `text-[11px] uppercase
tracking-[0.15em]` for labels, `bg-black text-white` for primary
buttons). Total ~200 lines.

### Edit 5: `app.auth` sub-object + handlers

Added inside aura's `app = { ... }` namespace, after the existing
`app.checkout` block. Mirrors D.2's austin handlers but namespaced
under `app.auth` to avoid colliding with aura's existing global symbols.

```js
app.auth = {
    open(tab = 'login') { /* show modal, switch tab */ },
    close() { /* hide modal */ },
    switchTab(tab) { /* swap panel visibility + tab underline */ },
    iconClick() { /* if logged in -> #account; else open() */ },
    updateIcon() { /* refresh header button label/state */ },
    async submitLogin() { /* vmCustomer.login + error handling */ },
    async submitRegister() { /* vmCustomer.register */ },
    submitForgot() { /* placeholder toast */ },
    async doLogout() { /* vmCustomer.logout + #home */ },
    async renderDashboard() { /* hydrate + populate Overview */ },
    async hydrateOrders() { /* vmCustomer.myOrders -> render table */ },
    async saveProfile() { /* vmCustomer.updateProfile */ },
    async changePassword() { /* vmCustomer.changePassword */ },
    activateTab(name) { /* swap account-view tab panels */ }
};
```

Plus router wiring: `app.router.evalRoute` learns about `#account`. On
match, runs `app.auth.renderDashboard()` and shows the section.

Total ~250 lines of JS.

## The playbook (`docs/superpowers/theme-port-playbook.md`)

A separate document that captures everything D.3.2/D.4/D.5 implementers
need to port a similar theme. Structure:

1. **Preconditions** — vm-customer.js + api.php at the same path; the
   theme uses an `app = {}` skeleton; the theme has a recognizable
   header with a cart icon next to which the account button will sit.
2. **The 5-question integration checklist** — answers an implementer
   fills in BEFORE editing:
   - Q1: What's the file path? (`themes/<name>/interface`)
   - Q2: Where's `</head>`? (line number)
   - Q3: Cart-button parent selector and exact insertion point for the
     new account button.
   - Q4: Where does the theme's "view-section" pattern live? Where to
     insert `view-account` (typically before `view-checkout`).
   - Q5: What's the theme's toast function name + signature? (`triggerToast(msg)` vs `showToast(msg, type)` vs none — add a micro-toast inline)
3. **The 5-edit template** — for each of the 5 edits in this spec, the
   playbook shows the exact paste-in code with `<<TOKENS>>` for
   theme-specific tweaks (toast function name, icon library, color
   tokens).
4. **Per-theme variation notes** — the 19 themes use either Lucide or
   inline SVG; some use `font-serif`, others use `font-sans` or custom
   fonts. The playbook lists the two or three common tweaks.
5. **Smoke test checklist** — same 10-step browser flow used in D.2 +
   D.3.1 testing.
6. **Common pitfalls** — based on the experience of porting aura:
   demo-store iframe routing, the `state=order` handler not stamping
   `customer_id` (carryover from D.2), CORS if the theme is loaded from
   a different origin.

The playbook is ~400 lines. It's purely a developer artifact; not
loaded by any runtime code.

## Out-of-scope items deferred to later D-phases

- **Address book endpoints** — D.1 deferred. The playbook notes that
  when the address endpoints ship, themes add a 5th account tab
  ("Addresses") and a new section in the modal-equivalent for inline
  address creation during checkout.
- **Pre-fill checkout from `vmCustomer.cached()`** — D.3.1 doesn't
  modify aura's existing checkout flow. A logged-in customer who
  hits checkout still types their name/email manually. Playbook flags
  this as a desirable follow-up.
- **`state=order` customer_id stamping** — bigger problem flagged in
  D.2; same status here. Logged-in customer orders land as guest
  orders. Tracked separately.

## Failure modes & guards

| Scenario | Behavior |
|---|---|
| `vm-customer.js` 404 | `window.vmCustomer` undefined; account button stays logged-out; clicking it tries to open modal which shows but submit handlers throw `ReferenceError` caught in try/catch → toast "Auth unavailable, refresh and try again". |
| Storefront loaded at a URL where `api.php` is unreachable | Same surface as aura's existing fetchStore failures — fallback to mock data already in place for products; auth simply doesn't work. Toast shows network error message. |
| 401 from any authenticated endpoint | vm-customer.js auto-logout fires; aura's listener flips the icon back to logged-out; if the user was on `#account`, the dashboard renders an empty state with "Please sign in" CTA. |
| Customer changes password on another tab | Same as D.2: any in-flight request returns 401 → auto-logout cascade. |
| Customer with 0 orders | Orders tab shows "No orders yet" with link back to `#shop`. |
| Customer with > 100 orders | Backend caps at 100. Orders tab notes "(showing 100 most recent)". |
| Customer's name has multiple words | Same fname/lname split as D.2: first space-delimited token is fname, rest is lname. Documented in the playbook as a known lossy mapping. |
| `acc-email` field | `readonly` attribute. Hover title: "Email is the account identifier and cannot be changed here." Same as D.2. |
| Wrong current password during change_password | Backend returns 400 (per D.1's status code refinement). Toast surfaces the error. No auto-logout. |

## Security notes

- No new tokens introduced. Same `X-Customer-Token` flow as D.1/D.2.
- `aus_user` cleanup is not needed for aura (aura never had the
  localStorage mockup austin had); the head snippet omits the cleanup
  stanza, but the playbook keeps it for themes that did have such
  state.
- XSS: aura renders all customer fields via `textContent` per the
  playbook template. Implementer must confirm this when adopting; the
  smoke test catches accidental `innerHTML` use.
- Modal overlay uses `z-50` (matching aura's existing overlays). The
  cart drawer is also `z-50`; the playbook flags that opening both
  simultaneously is a layering concern (auth modal closes the cart
  drawer when it opens).

## Testing

Manual Playwright smoke against aura, parallel to D.2 Task 5:

1. Set aura as the active theme on a test site (the demo-store iframe
   routing from D.2 still applies — implementer may need to test by
   directly editing `sites/<domain>/theme`).
2. Open the storefront URL the engine generates (or, if that's still
   blocked by the iframe-routing quirk, smoke against
   `http://localhost:8016/themes/aura/interface` after copying
   `skel/vm-customer.js` into `themes/aura/`).
3. Verify the account button appears in the header between Search and
   Tote.
4. Click → modal opens at the Sign In tab. Switch to Create Account.
5. Register a fresh email; toast greets the user; modal closes; icon
   button shows the first name.
6. Click the icon → navigates to `#account` → Overview tab populated
   with customer data.
7. Switch tabs in this order: Profile → edit fname/lname/phone → save
   → switch to Overview → re-rendered with new name.
8. Orders tab → empty state visible.
9. Password tab → change password (wrong current first → see 400 toast
   → then correct current → success toast).
10. Reload; verify still logged-in (cache restored), then click logout
    in the account header → returns to `#home`.

If iframe routing is still broken, the spec accepts smoke-via-console
testing: load `/themes/aura/interface` directly, then use DevTools
console to:

```js
window.VM_CUSTOMER_API_BASE = 'http://localhost:8016/sites/debug.com/api.php';
location.reload();
// After reload, vmCustomer is loaded against the debug.com backend.
```

This is documented in the playbook's smoke-test section.

## Open questions

None. Approved in brainstorming on 2026-06-03.
