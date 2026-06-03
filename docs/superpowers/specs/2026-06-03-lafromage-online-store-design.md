# lafromage — Online Store Port (Sub-project D.6)

**Date:** 2026-06-03
**Status:** Approved
**Owner:** keenan@doneros.co
**Parent decomposition:** Final sub-project of the "themes become Shopify-style online stores" initiative. D.6 closes out the sub-project C audit's last two open items: `lafromage` (no checkout) and `default` (placeholder).

## Summary

Convert `themes/lafromage` from a cart-only design demo into a complete storefront that hits the 9-feature baseline from sub-project C: product list, product detail, **search**, cart, **checkout**, **login**, **register**, **dashboard**, **my orders**.

`themes/default` is **explicitly out of scope** for changes. The audit's "remove or rebuild" framing turned out to be wrong — `default` is the bootstrap theme assigned to every new store at creation (`pages/page.setup.php:92` hardcodes `$theme = "default"`). All 21 sites in the DB currently sit on it as their initial state. The "Website Under Construction" copy is a reasonable onboarding placeholder. We leave it as-is and document its role.

## Non-goals

- Modifying `themes/default`, the bootstrap copy, or the new-site initialization flow.
- Backend API changes — sub-projects A and B already shipped every endpoint this port needs (`customer_login`, `customer_register`, `customer_me`, `customer_my_orders`, `customer_logout`, `state=order`, `state=search`).
- Adding `vm-customer.js` as an external script — lafromage's existing architecture is inline-everything via a single `SpecimenEngine` class. We match that pattern (see Architecture decision below).
- Address book, change-password, profile-edit, password-reset — all deferred to a later pass, matching the waiver list in the sub-project C spec.
- Order-confirmation route. The dashboard's recent-orders list IS the confirmation surface.
- Guest checkout. `state=order` requires a `customer_id`; guests are redirected to the account view.

## Architecture decision

**Inline extension of `SpecimenEngine`** — not the `vm-customer.js` + `window.app.auth` pattern used in aura / hastings.ego.

Why: lafromage today is a single-file SPA. The class already owns routing, rendering, and cart state. Auth, checkout, search, and the dashboard fit cleanly as additional methods + templates inside that class. Introducing an external auth script alongside an inline shop class would mix paradigms.

Trade-off: lafromage will not share `skel/vm-customer.js` updates automatically. Acceptable — the auth surface is small (login/register/logout/me) and the existing per-theme port playbook already accepts that some themes inline their auth.

## Components

The port adds three concerns to the existing engine:

1. **Auth namespace** on the engine — `engine.auth = { user, token }` with `login`, `register`, `logout`, `bootCustomer`, `isLoggedIn`.
2. **New routes + templates** — `#search`, `#account`, `#checkout` with `tpl-search`, `tpl-account`, `tpl-checkout`.
3. **Data layer mutation surface** — `postStore(state, body)` sibling to existing `fetchStore`, both bearer-token-aware.

Plus one small refactor (`renderProductCard` extracted from `renderShop` so `renderSearch` can reuse it).

## Data flow

### Read path (unchanged behavior, augmented with auth header)

`fetchStore(state, params)` already exists and falls back to `getMockData` on network failure. We augment it to attach `X-Customer-Token: <localStorage.vm_customer_token>` when present. No other behavior changes.

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

### Write path (new)

`postStore(state, body)` for mutations: login, register, logout, change_password, place-order.

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
        return { ok: res.ok, status: res.status, data, error: data.error || null };
    } catch (e) {
        return { ok: false, status: 0, data: {}, error: e.message };
    }
}
```

## Persisted client state

Two `localStorage` keys, consistent with other ported themes:

- `vm_customer_token` — bearer token returned by `customer_login` / `customer_register`
- `vm_customer_cache` — last-known `customer_me` JSON (lets the dashboard render instantly on reload)

Plus the existing `specimen_cart` key for the cart (no change).

## Navigation

The fixed nav grows from 2 links to 4:

```
S//P    SEARCH    CATALOG    ARCHIVE [account]    INDEX [n]
```

`S//P` (logo) → `#home`. `SEARCH` → `#search`. `CATALOG` → `#shop`. `ARCHIVE` → `#account`. `INDEX` → `#cart`. The `[n]` cart count behavior is unchanged.

When logged in, `ARCHIVE` is replaced (in copy only) with the first 8 chars of the customer's name — a small affordance that the user is signed in. Same selector (`a[href="#account"]`), text swapped by the auth boot routine.

## Templates

### `tpl-search`

```
QUERY_THE_ARCHIVE
[ <input> — specimen-border, JetBrains Mono, no chrome ]
RESULTS [n]
<div class="js-search-grid">...</div>
```

Empty input → grid shows a single `AWAITING_QUERY` placeholder row. On `input` event (debounced 250ms) → `fetchStore('search', { q })` → grid re-rendered using `renderProductCard()`.

### `tpl-account`

Single template, three `<section>`s toggled by `.hidden`:

**`section.acc-login`** (default visible for guests)
```
RECORD_LOOKUP
[ email input ]
[ password input ]
[ TRANSMIT button ]
[ error line — hidden until set ]
NO_RECORD? -> INITIATE_NEW [data-action="show-register"]
```

**`section.acc-register`** (visible after toggle)
```
INITIATE_RECORD
[ email input ]
[ password input ]
[ name input ]
[ INITIATE_RECORD button ]
[ error line ]
EXISTING_RECORD? -> SIGN_IN [data-action="show-login"]
```

**`section.acc-dashboard`** (visible when logged in)
```
RECORD_HOLDER: <name>
<email>
---
RECENT_TRANSMISSIONS
[ ordered list — last 5 orders: id / date / total / status, each as a row of mono cells ]
---
[ TERMINATE_SESSION button ]
```

### `tpl-checkout`

Two states by login status:

**Guest state:**
```
TRANSMISSION_REQUIRES_IDENTIFICATION
You must log a record before committing this archive.
[ RETURN_TO_ARCHIVE → pushes #account ]
```

**Logged-in state:**
```
COMMIT_TRANSMISSION
DESTINATION
[ street | city | postal | country — 4 inputs ]
INVENTORY
[ summary: n items, total ]
[ COMMIT_ORDER button ]
[ error line ]
```

No phone / notes fields — keeps the address form to the four required by `state=order`.

### `tpl-cart` (modifications to existing)

Each row gains an inline quantity control between the title and price:

```
[ - ] [ qty ] [ + ]    <price>    PURGE
```

`-` decrements (min 1), `+` increments. Existing `PURGE` button stays. New totals strip at the bottom of the container:

```
SUBTOTAL: <total>
[ PROCEED_TO_TRANSMISSION → pushes #checkout ]
```

Both hidden when cart is empty (existing `Index_Empty` state is unchanged).

## Engine surface

```js
class SpecimenEngine {
    constructor() {
        this.root = document.getElementById('app-root');
        this.cart = JSON.parse(localStorage.getItem('specimen_cart')) || [];
        this.auth = {
            user: null,
            token: localStorage.getItem('vm_customer_token')
        };
        this.initRouter();
        this.updateCartCount();
        this.bootCustomer();
    }

    // EXISTING (untouched):
    // initRouter, route, renderTemplate, renderShop, renderProduct,
    // renderCart, cartAdd, cartSave, updateCartCount

    // NEW:
    async bootCustomer() { /* hydrate cache → render → fetch customer_me → reconcile */ }
    async login(email, password) { /* postStore('customer_login') */ }
    async register(email, password, name) { /* postStore('customer_register') */ }
    async logout() { /* postStore('customer_logout'); clear; dispatch */ }
    isLoggedIn() { return !!this.auth.user; }

    renderSearch() { /* tpl-search + debounced fetchStore('search') */ }
    renderAccount() { /* tpl-account; toggle sub-section by isLoggedIn() */ }
    renderCheckout() { /* tpl-checkout; toggle by isLoggedIn(); place-order handler */ }
    renderDashboard() { /* fill acc-dashboard from this.auth.user + fetchStore('orders') */ }
    renderProductCard(product, grid) { /* extracted from renderShop */ }
}
```

Router gains three cases:

```js
case 'search':   this.renderSearch();   break;
case 'account':  this.renderAccount();  break;
case 'checkout': this.renderCheckout(); break;
```

## Auth bootstrapping

`bootCustomer()` runs once at engine startup:

1. If `localStorage.vm_customer_cache` exists, parse it and stash as `this.auth.user` immediately — this lets the dashboard render synchronously on `#account` reload.
2. If `this.auth.token` is set, fire `fetchStore('customer_me')` in the background. On success, overwrite `this.auth.user` and update the cache. On 401 (token expired/revoked), clear both keys, set `this.auth.user = null`, dispatch `vm:customer-logout`, and (if currently on `#account`) re-render.
3. On any successful boot, dispatch `vm:customer-login` with `detail: this.auth.user`. On signed-out boot, dispatch nothing.
4. Set `body.dataset.customer = 'in' | 'out'` so CSS rules can target each state if needed.

## Login / Register handlers

Both forms submit via the same pattern:

1. Read input values.
2. Clear error line.
3. Call `postStore('customer_login', {...})` or `postStore('customer_register', {...})`.
4. On `ok === true`:
   - Store `data.token` in `localStorage.vm_customer_token`
   - Store `data.customer` in `localStorage.vm_customer_cache`
   - Set `this.auth.token`, `this.auth.user`
   - Dispatch `vm:customer-login`
   - Re-render account → `acc-dashboard` visible
   - Update nav copy (ARCHIVE → name)
5. On `ok === false`: display `data.error || 'TRANSMISSION_FAILED'` in the error line.

## Logout handler

Click on `TERMINATE_SESSION`:

1. Call `postStore('customer_logout')` (fire-and-forget; we clear local state regardless of outcome).
2. Clear `vm_customer_token` and `vm_customer_cache`.
3. Set `this.auth.user = null`, `this.auth.token = null`.
4. Dispatch `vm:customer-logout`.
5. Re-render account → `acc-login` visible.
6. Update nav copy (name → `ARCHIVE`).

## Checkout handler

`COMMIT_ORDER` click:

1. Verify `isLoggedIn()`. (Should be — the guest state would have intercepted earlier — but defensive check.)
2. Read address inputs. If any required field empty → error line, abort.
3. Build payload:
   ```js
   {
       customer_id: this.auth.user.id,
       addr_street, addr_city, addr_postal, addr_country,
       items: this.cart.map(i => ({ id: i.id, qty: i.qty || 1 }))
   }
   ```
4. Call `postStore('order', payload)`.
5. On `ok === true`:
   - Clear `specimen_cart` from localStorage, set `this.cart = []`, update count.
   - Push hash to `#account`. The dashboard re-renders with `customer_my_orders` and the new order appears at the top — confirmation surface.
6. On `ok === false`: error line with `data.error || 'TRANSMISSION_REJECTED'`.

## Events

Two custom events on `document`, consistent with the playbook:

- `vm:customer-login` (detail: customer object) — dispatched after successful login, register, or boot-with-valid-token
- `vm:customer-logout` — dispatched after logout, or boot-with-expired-token

Engine doesn't subscribe to these itself (it's the dispatcher). They exist so future code (analytics, embedded widgets) can react.

## Search debounce

Single shared `searchDebounceTimer` on the engine instance. Cleared on every keystroke, set to fire after 250ms. Pattern:

```js
let timer = null;
input.addEventListener('input', (e) => {
    clearTimeout(timer);
    timer = setTimeout(() => this.runSearch(e.target.value), 250);
});
```

`runSearch(q)` → if `q` empty: render `AWAITING_QUERY`. Else: `fetchStore('search', { q })` → re-render grid with `renderProductCard`.

## Error handling

- Network failures in `fetchStore` already fall through to `getMockData` (existing behavior, unchanged).
- Mutations in `postStore` return `{ok: false}` on network failure; handler shows generic `TRANSMISSION_FAILED` error.
- 401 from `fetchStore('customer_me')` → clear token, log out, do not retry.
- Form validation is minimal: required-field check on submit, server-side error displayed verbatim in the error line.

## Testing approach

Browser-based smoke test (Playwright MCP if available, or manual in the dev container):

1. Load `http://localhost:8016/themes/lafromage/interface` — verify home renders, nav has SEARCH / CATALOG / ARCHIVE / INDEX.
2. Click ARCHIVE → `acc-login` visible.
3. Toggle to register, submit a new account → dashboard visible, name in dashboard greeting, ARCHIVE nav label flipped to name.
4. Reload page → still logged in (cache hydrates), dashboard renders immediately.
5. Catalog → add 2 items → cart shows 2 rows + totals + PROCEED button.
6. Cart `+`/`-` controls update qty; PURGE removes row.
7. PROCEED → checkout shows address form (logged in).
8. Fill address + COMMIT_ORDER → cart clears, redirected to `#account`, new order in RECENT_TRANSMISSIONS.
9. Logout → nav flips back to ARCHIVE, `acc-login` visible.
10. SEARCH → type a known title → results grid populates, cards clickable.
11. Logged-out checkout direct nav (`#checkout`) → guest interception panel renders.

No unit tests. Sub-project A and B's PHP tests already cover the backend endpoints this port consumes.

## File scope

**Modified:**
- `themes/lafromage/interface` — the entire port (single file, additive ~250 lines)

**Untouched:**
- `themes/default/*` — placeholder stays as-is
- `themes/lafromage/index.html` — separate file, unused by the engine (the `interface` template is what the engine serves)
- All backend code (sub-projects A and B already shipped what's needed)
- `vm-admin/routes/page.theme.php` — no picker blocklist needed
- `pages/page.setup.php` — `default` stays as bootstrap

**Created:**
- None.

## Out of scope (deferred)

These follow the sub-project C waiver list and stay consistent with the other ported themes:

- Address book (multiple saved addresses)
- Change password from the dashboard
- Profile edit (name, email change)
- Password reset / forgot password
- Order detail view (`#order/<id>`)
- Email notifications
- Cart-line subtotals vs. unit price
- Tax / shipping calculation
- Coupon code application at checkout
- Multi-currency

## Open questions resolved during brainstorming

- **Architecture pattern** → extend `SpecimenEngine` inline, not external `vm-customer.js`
- **Search included?** → Yes (user explicitly opted in)
- **Address book?** → No (deferred)
- **Order confirmation route?** → No; dashboard recent-orders is the confirmation surface
- **Guest checkout?** → No; redirect to `#account`
- **Fate of `default`?** → Leave as-is; document role as bootstrap placeholder
