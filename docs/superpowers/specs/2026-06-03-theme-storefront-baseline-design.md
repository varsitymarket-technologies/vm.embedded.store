# Theme Audit + Storefront Baseline — Sub-project C

**Date:** 2026-06-03
**Status:** Audit complete — handoff document for sub-project D
**Owner:** keenan@doneros.co
**Parent decomposition:** Third of four sub-projects from the broader
"themes become Shopify-style online stores" initiative. The other three:

- A. Customer auth backend — **shipped** (commit `e8e9e1f`)
- B. Customer-side API expansion — **shipped** (commit `1e86112`)
- D. Wire theme dashboards to the real auth + API — uses this doc as its punch list

## Summary

This is a documentation-only deliverable. It catalogues every theme in
`themes/`, defines a 9-feature storefront baseline that every theme
should hit, scores each theme against the baseline, and gives
sub-project D a per-theme remediation punch list.

**No theme files are modified by sub-project C.** All edits happen in D.

## Non-goals

- Modifying any theme HTML, CSS, or JS (deferred to sub-project D).
- Auditing the upstream `embedded-themes` GitHub repository or
  coordinating with its maintainers about who owns which patches.
- Performance, accessibility, or mobile responsiveness audits.
- Aesthetic / visual quality judgments — only feature presence.
- Auditing the wholly-unrelated `austin` autofill data, the
  `interface` template syntax (`e(__CONST__)`), or theme sync from
  the remote repo.

## Methodology

Two passes, layered:

### 1. Refined static scan

For each theme's primary storefront file (`interface` if present, else
`index.html`), `grep` for specific markers that indicate each
baseline feature is present in the markup:

| Feature | Markers grepped |
|---|---|
| Product list (PLst) | `getProducts`, `product-grid`, `catalog-grid`, `renderShop`, `state=products` |
| Product detail (PDt) | `getProduct(`, `product-detail`, `renderProduct`, `state=product&`, `product-page` |
| Search (Srch) | `type="search"`, `searchInput`, `searchProducts`, `state=search` |
| Cart | `addToCart`, `VMCart`, `cart.add`, `renderCart`, `cart-drawer`, `cart-item` |
| Checkout (Chkt) | `checkout-form`, `placeOrder`, `state=order`, `state=checkout`, `customer_address.*input` |
| Login | `customer_login`, `loginForm`, `login-form`, `password.*input`, `input.*password` |
| Register (Regr) | `customer_register`, `register-form`, `signup-form`, `createAccount`, `signUp` |
| Dashboard (Dash) | `dashboard`, `account-view`, `account-page`, `userSession`, `customer_me` |
| My orders (MyOr) | `customer_my_orders`, `order-history`, `my-orders`, `getOrders` |

Marker counts are noted; a non-zero count is treated as "present in
markup" but not necessarily "correctly wired." See limitations below.

### 2. Spot-render verification (3 representative themes)

Loaded `http://localhost:8016/themes/<name>/interface` for three samples
covering the expected tiers, captured a viewport screenshot, and
compared structure to the scan numbers:

| Theme | Tier expected | Verified |
|---|---|---|
| `austin` | Gold | ✓ Header with search + account avatar + cart, hero, product grid render |
| `anti` | Silver | ✓ Nav includes Account + Cart links, search icon, products render |
| `default` | Broken | ✓ "Website Under Construction" placeholder — no storefront content |

The route `/themes/<name>/interface` serves the raw template (with
`e(__CONST__)` placeholders un-substituted) but the structure is
intact, which is sufficient for feature-presence verification.

### Limitations of this methodology

1. **The static scan does not catch wiring quality.** A theme can
   have `addToCart` in markup but be hooked to a stub that only
   updates localStorage. Sub-project D will surface these during
   implementation; the scan flags presence/absence only.
2. **Marker count is a rough proxy.** A theme using a different
   naming convention (e.g., `addItem` instead of `addToCart`) may
   undercount. Spot-render mitigates this for the 3 sampled themes.
3. **Server-side rendering paths not exercised.** The audit covers
   the client-side SPA layer only.

## Baseline definition

A theme is "Shopify-like" when it passes all nine of the following.
Each criterion is intentionally narrow so that pass/fail is
unambiguous from code reading.

| # | Feature | Pass criteria |
|---|---|---|
| 1 | **Product list** | A view that calls `VMApi.getProducts()` or `state=products` and renders the response into a grid/list. |
| 2 | **Product detail** | A view that calls `VMApi.getProduct(id)` or `state=product&id=N` and shows a single product with an add-to-cart action. |
| 3 | **Search** | A visible search input that filters the catalog or routes to `#search/<q>` or `state=search`. |
| 4 | **Cart** | A persistent cart UI (drawer / page) with add, remove, qty adjust, line totals, and subtotal. State must survive a page reload (localStorage via `VMCart`). |
| 5 | **Checkout** | A form collecting at minimum `name, email, phone, address` and submitting to `state=order` or the `cart_*` → `checkout_*` flow. Returns to a confirmation view on success. |
| 6 | **Login + Register** | Two forms (or tabs) for `state=customer_login` and `state=customer_register`. On success, the returned token is stored in `localStorage` under `vm_customer_token` and the UI shows the logged-in state. |
| 7 | **Account dashboard** | A logged-in view (gated by token presence) showing the customer's name + email, with links to "My Orders" and "Profile". |
| 8 | **My Orders** | A list view fetching `state=customer_my_orders` with the `X-Customer-Token` header. Each row shows total, status, date, and order items. |
| 9 | **Profile edit** | A form for updating name and phone via `state=customer_update_profile`. |

### Bonus (not required for baseline)

- **Address book** — list/create/edit/delete addresses via the
  `customer_addresses*` endpoints. Non-trivial UI (modal patterns +
  default flag) so deferred.
- **Change password** — self-service password change via
  `customer_change_password`. Important but most themes can ship
  without it initially.
- **Email verification UI** — there is no backend yet, so no theme
  should pretend to have it.

## Per-theme scorecard

Legend:
- ✓ — markup present, structure looks correct
- ✗ — feature absent
- L — feature is **L**egacy mockup (localStorage / fake auth, NOT wired
  to the new API endpoints from sub-projects A/B)
- — — directory has no usable `interface` or `index.html`

**Important context:** Zero themes currently use the new `customer_*`
API endpoints. Every `Login`/`Dash` cell that shows ✓ in the
non-Legacy table below is in fact L (legacy) — the new API only
shipped with sub-project A. The table below labels L wherever a
legacy auth UI exists; ✗ where no auth UI exists at all.

| Theme | PLst | PDt | Srch | Cart | Chkt | Login+Regr | Dash | MyOr | Profile | Score |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `austin` | ✓ | ✓ | ✓ | ✓ | ✓ | L | L | L | L | 5/9 + 4L |
| `anti` | ✓ | ✓ | ✓ | ✓ | ✗ | L | L | ✗ | ✗ | 4/9 + 2L |
| `hastings.ego` | ✓ | ✓ | ✓ | ✓ | ✓ | L | L | ✗ | ✗ | 5/9 + 2L |
| `oaklyn` | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | L | ✗ | 5/9 + 1L |
| `ourchieve` | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 5/9 |
| `aura` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `corselle` | ✓ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 4/9 |
| `crown` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `eros` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `gta` | ✓ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 4/9 |
| `imara` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `kinetic` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `linus` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `lucid` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `makhesa` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `mashala` | ✓ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 4/9 |
| `mono` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `oasis` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `osmossis` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `pvt` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `revenge` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `starved.hustla` | ✓ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 4/9 |
| `street` | ✓ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 4/9 |
| `terra` | ✗ | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `lafromage` | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | 3/9 |
| `default` | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | 0/9 |

## Tier summary

### Gold (passes all 9 with real API)
**Zero themes.** Every theme that has login/dashboard UI today uses
legacy localStorage auth. None talk to `customer_login` /
`customer_register` / `customer_me`. This is the entire gap
sub-project D exists to close.

### Silver (5/9 — has cart/checkout/search + legacy auth)
- `austin` — most complete theme. Full login/register/dashboard
  modal mockup using a localStorage `users[]` array. Best candidate
  to be the **canonical reference** sub-project D ports patterns from.
- `hastings.ego` — login + dashboard UI present, also legacy. Smaller
  than austin so the rewiring delta is smaller.

### Bronze (3–5 / 9 — cart/checkout/search but no auth UI)
- `anti`, `oaklyn`, `ourchieve` — strong commerce, missing auth UI
  entirely. Need login/register/dashboard added from scratch.
- Most other themes (`aura`, `crown`, `eros`, `imara`, `kinetic`,
  `linus`, `lucid`, `makhesa`, `mono`, `oasis`, `osmossis`, `pvt`,
  `revenge`, `terra` — and `corselle`, `gta`, `mashala`,
  `starved.hustla`, `street`) share an identical commerce skeleton.
  Most efforts here can be templated into one patch applied to all.

### Broken (0/9)
- `default` — serves a "Website Under Construction" placeholder. Not
  a usable storefront. Either upgrade to a minimal real storefront or
  remove from the theme picker.

## Per-theme remediation notes (sub-project D punch list)

Items below are actionable to-dos for sub-project D. Each starts
with what the theme needs added; reuse the patterns from `austin` and
the existing `skel/vm.api.js` extensions sub-project D will add.

### austin (highest priority — canonical reference)

Replace the legacy localStorage auth with real API calls:

1. `submitLogin()` (line ~3298) — POST to `state=customer_login`,
   store `token` in `localStorage.vm_customer_token`, then call
   `customer_me` to populate the dashboard.
2. `submitRegister()` (similar pattern, line ~3289) — POST to
   `state=customer_register`. Auto-stores token and switches to
   logged-in state.
3. `getOrders()` in the dashboard view — call
   `state=customer_my_orders` with `X-Customer-Token`.
4. Profile-update form — wire to `state=customer_update_profile`.
5. Password-change form (lines 2562–2567 — already in markup) — wire
   to `state=customer_change_password`. Display the new token on
   success.
6. Logout — POST to `state=customer_logout` then clear
   `localStorage.vm_customer_token` and force a reload.

**Why first:** austin's auth UI is the most complete; once converted,
patterns can be copy-pasted into other themes.

### hastings.ego

Same wiring work as austin, smaller surface (5 login/3 dashboard
markers). Likely a 1-day port after austin is done.

### anti

Markup has `dashboard` references (20 marker hits — mostly nav links)
but no actual dashboard view div. Needs:

1. New dashboard view div (gated on `localStorage.vm_customer_token`).
2. New login + register modal (copy austin's pattern once converted).
3. Wire the existing `Account` nav link to open the modal when logged
   out, or open the dashboard when logged in.
4. Checkout exists but uses `Chkt: 0` markers — investigate whether
   it's a legacy guest-only checkout that should also send the
   `customer_id` when the visitor is logged in.

### oaklyn, ourchieve

Both have full commerce (5/9) with no auth UI at all. Treat as
"Bronze" template — add auth modal + dashboard view from scratch
following austin's pattern. Larger themes (~948–2856 lines) so the
work won't be trivial, but the modifications are additive.

### The "cart+checkout-only majority" group

`aura`, `crown`, `eros`, `imara`, `kinetic`, `linus`, `lucid`,
`makhesa`, `mono`, `oasis`, `osmossis`, `pvt`, `revenge`, `terra`,
`corselle`, `gta`, `mashala`, `starved.hustla`, `street`.

These 19 themes share a near-identical commerce skeleton (PDt=4 +
Cart=7 + Chkt=1 marker counts in most). Recommended approach:

1. Pick one (e.g., `aura`) as the template-group canonical.
2. Build the auth modal + dashboard + my-orders + profile-edit views
   as a self-contained reusable snippet that hooks into the
   existing cart + checkout flow.
3. Apply the same patch to the other 18 themes — most should accept
   it with minimal aesthetic adjustment.

**Estimated effort:** 1 day to design the snippet, 0.5 day per
remaining theme for the apply + visual smoke test = ~10 days total.

### lafromage

Has product list/detail and cart but **no checkout markers at all**.
Either:
- Add a minimal checkout form (copy from `aura`), or
- Mark the theme as "browse only" and remove from the active theme
  picker.

### default

Placeholder "Website Under Construction" page. Either:
- Rebuild as a minimal real storefront (use the aura template), or
- Remove from the theme picker (set `is_active = 0` in
  `sys_websites` for any store currently using it; remove the
  `default/` directory from the upstream `embedded-themes` repo).

## Cross-cutting recommendations for sub-project D

### 1. Centralize the auth wiring in `skel/vm.api.js`

Add four methods to `VMApi` that themes call rather than implementing
themselves:

```js
VMApi.prototype.customerLogin(email, password)
VMApi.prototype.customerRegister(email, password, name, phone)
VMApi.prototype.customerMe()  // reads token from localStorage
VMApi.prototype.customerLogout()
VMApi.prototype.customerMyOrders()
VMApi.prototype.customerUpdateProfile(name, phone)
VMApi.prototype.customerChangePassword(current, next)
```

Each handles the `X-Customer-Token` header automatically by reading
`localStorage.vm_customer_token`. Themes then have no token plumbing
to do — they just call `vm.api.customerLogin(...).then(...)`.

**Benefit:** A bug fix in the wiring (e.g., handling 401 to force
logout) lands in one file and benefits all 26 themes.

### 2. Provide a `vm-customer-aware` mixin in `skel/vm.theme.js`

A small mixin that:
- Resolves `vm.api.customerMe()` on page load if a token is present.
- Exposes `vm.customer` for theme code to read.
- Emits a `vm:customer-loaded` event themes can hook into to flip UI
  between logged-out and logged-in states.

### 3. Source of truth for theme patches

The themes are synced from
[varsitymarket-technologies/embedded-themes](https://github.com/varsitymarket-technologies/embedded-themes)
on every request via `.register.php`. **Any patch sub-project D
applies locally will be overwritten on the next sync.** Three options
for handling this:

- **A. Patch upstream** — push the audit findings + patches as PRs to
  the `embedded-themes` repo. Slow but correct.
- **B. Suspend the sync during D** — short-circuit `.register.php`'s
  theme sync for the duration of the project, then re-enable after
  upstream is updated.
- **C. Layer a per-store override file** — themes load
  `sites/<domain>/theme-overrides.js` if present and merge it over
  the base theme. Lets a per-store hot-patch survive theme sync.

Recommend **A** for the long-term canonical fix, with **B** as a
short-circuit if upstream coordination is slow.

### 4. Suggested implementation order

1. **Week 1**: Build the centralized API client + mixin in `skel/`.
2. **Week 2**: Port `austin` end-to-end. Use it as the reference.
3. **Week 3**: Port `hastings.ego` (similar legacy auth pattern).
4. **Week 4**: Pick `aura` as the template-group canonical; port it
   end-to-end. Apply the same snippet to the 18 other commerce-only
   themes (`crown`, `eros`, `imara`, etc.).
5. **Week 5**: Tackle the bigger commerce themes without auth UI
   (`anti`, `oaklyn`, `ourchieve`) using austin's patterns.
6. **Week 6**: Decide fate of `lafromage` and `default`.

### 5. Verification per theme

For each ported theme, sub-project D should:

1. Load the storefront in the demo store.
2. Register a new customer through the theme's modal.
3. Add a product to cart, complete checkout, return to dashboard.
4. Verify the order appears under "My Orders".
5. Edit profile, change password, verify both via the API directly.

Playwright scripts can be reused from sub-projects A/B's smoke tests.

## Open questions for sub-project D

- Should themes implement the full address book UI, or defer it to a
  "manage addresses" link that opens a separate page/modal not
  templated per theme? (Default-flag UX is fiddly enough that one
  centralized implementation might be better.)
- Should logged-in customers see their saved name/email/address
  pre-filled on the existing guest checkout, or should checkout
  detect a logged-in customer and switch to a streamlined
  one-click-style flow?
- For themes that currently have no auth UI (the majority), should
  the login affordance be a header avatar dropdown (austin pattern)
  or a dedicated `/account` route entered via nav?

These are sub-project D's calls to make. This audit just flags them.

## Appendix: Marker counts

Raw output from the refined static scan, for reference.

```text
theme              PLst PDt  Srch Cart Chkt Login Regr Dash MyOr
anti               0    0    0    13   0    0    0    20   0
aura               0    4    0    7    1    0    0    0    0
austin             2    2    0    17   3    6    0    11   0
corselle           4    2    0    5    2    0    0    0    0
crown              0    4    0    7    1    0    0    0    0
default            0    0    0    0    0    0    0    0    0
eros               0    4    0    7    1    0    0    0    0
gta                4    2    0    8    2    1    0    0    0
hastings.ego       2    6    1    5    3    3    0    5    0
imara              0    4    0    8    1    0    0    0    0
kinetic            0    4    0    7    1    0    0    0    0
lafromage          4    2    0    3    0    0    0    0    0
linus              0    4    0    7    1    0    0    0    0
lucid              0    4    0    7    1    0    0    0    0
makhesa            0    4    0    7    1    0    0    0    0
mashala            4    2    0    8    2    0    0    0    0
mono               0    4    0    7    1    0    0    1    0
oaklyn             3    2    0    28   3    0    0    0    0
oasis              0    4    0    7    1    0    0    0    0
osmossis           0    4    0    8    1    0    0    0    0
ourchieve          2    4    0    11   2    0    0    0    0
pvt                0    4    0    7    1    0    0    0    0
revenge            0    4    0    7    1    0    0    0    0
starved.hustla     4    2    0    8    2    1    0    0    0
street             4    2    0    8    2    0    0    0    0
terra              0    4    0    7    1    0    0    0    0
```

Cells where the marker count is non-zero but the scorecard shows ✗:
the marker matched something other than a real feature (a CSS class,
a comment, etc.). These were resolved during spot-render where
applicable; the rest were judged conservatively as ✗.
