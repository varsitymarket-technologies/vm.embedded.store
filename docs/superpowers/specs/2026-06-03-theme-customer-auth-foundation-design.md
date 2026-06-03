# Theme Customer Auth Foundation — Design (Sub-project D.1)

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co
**Parent decomposition:** First of six sub-sub-projects under sub-project D
(Wire theme dashboards to the real auth + API). The other five:

- D.2 — Port `austin` as canonical reference theme
- D.3 — Port `aura` + template-apply to 18 lookalike themes
- D.4 — Port `anti`, `oaklyn`, `ourchieve` (commerce-only themes without auth UI)
- D.5 — Port `hastings.ego`
- D.6 — Decide fate of `lafromage` + `default`

Audit doc that defines the punch list and ordering:
[2026-06-03-theme-storefront-baseline-design.md](2026-06-03-theme-storefront-baseline-design.md).

## Summary

Themes today call `api.php` (the Micro API in `skel/api.php`), not
`/store-access/<id>/?state=...` (the public store API where
sub-projects A and B added the customer auth endpoints). As a result,
**none of the new `customer_*` endpoints are reachable from any
theme.** D.1 closes that gap by adding the customer auth surface to
`skel/api.php` (reusing the existing modules) and shipping a small
drop-in JS helper (`skel/vm-customer.js`) that themes adopt with a
five-line snippet. D.2 and later sub-sub-projects then wire individual
themes' UI to call the helper.

## Goals

- Make every `customer_*` endpoint reachable from existing theme
  fetch calls (`api.php?state=...`) without changing themes' fetch
  patterns.
- Provide a single, opinionated client (`vm-customer.js`) so each
  theme's adoption work is a few CSS hooks + per-feature form
  handlers, not token plumbing or HTTP wiring.
- Keep the foundation layer testable in isolation (PHP module test
  + browser harness) so D.2–D.6 inherit a stable surface.

## Non-goals

- No theme is modified by D.1. Per-theme work begins in D.2.
- No address book or `customer_set_default` endpoints. Address book
  CRUD UX is non-trivial (default-flag modal patterns) and deferred
  to a later D-phase or built directly into D.2+ themes that want it.
- No changes to `module/customer_auth.php`, `module/customer_account.php`,
  or `api/index.php`. Sub-projects A and B are stable.
- No refactor of inline `fetch('api.php')` calls in themes into
  `skel/vm.api.js`. That refactor is deferred indefinitely; D.1 ships
  `vm-customer.js` as a parallel file.
- No reconciliation of the existing "engine-hosted vs deployed mode
  api.php reachability" pre-existing concern. `vm-customer.js` will
  fail the same way `vm.api.js` already fails in modes where `api.php`
  is unreachable. The fix for that lives outside D.1.
- No CSRF tokens. Customer endpoints use a custom header that browsers
  cannot set from cross-origin without preflight; same reasoning as A.
- No email verification UX. The backend `email_verified` column
  remains unenforced.

## User flow

A storefront customer (in a theme that has adopted D.1 plus D.2+'s
wiring) does:

1. Loads a storefront page. `vm-customer.js` runs in the head.
2. If `localStorage.vm_customer_token` exists, the helper fires
   `GET ?state=customer_me` with the `X-Customer-Token` header. On
   200, it caches the customer and dispatches `vm:customer-loaded`
   on `window`. On 401, it clears the token and dispatches
   `vm:customer-logout`.
3. Theme code listens for `vm:customer-loaded` / `vm:customer-logout`
   and toggles `document.body.dataset.customer` between `logged-in`
   and `logged-out`. CSS handles which sections show.
4. User submits a login form. Theme handler calls
   `vmCustomer.login(email, password)`. On success, token + cached
   customer are stored and `vm:customer-login` fires.
5. Logged-in user views their dashboard. Theme handler calls
   `vmCustomer.myOrders()` to populate a list.
6. User submits the profile form. Theme handler calls
   `vmCustomer.updateProfile(name, phone)`.
7. User submits change-password form. Theme handler calls
   `vmCustomer.changePassword(current, next)`. The helper stores the
   new token returned by the backend (sub-project A semantics: all
   prior sessions are killed; the response contains a fresh token
   for the current request).
8. User clicks logout. Theme handler calls `vmCustomer.logout()`,
   which hits `state=customer_logout`, clears localStorage, and
   dispatches `vm:customer-logout`.

Themes never touch `localStorage.vm_customer_token` directly or send
the `X-Customer-Token` header themselves. The helper owns both.

## Component split

**Create:**

- `skel/vm-customer.js` — Vanilla JS, no dependencies, IIFE wrapping
  a single `window.vmCustomer` object. ~150 lines. Reads its API
  base URL from either a `<script data-api-base="...">` attribute or
  a default of `api.php` resolved against the current page (mirroring
  what existing themes do for product fetches).

- `tests/vm-customer-harness.html` — Static HTML page that loads
  `vm-customer.js`, runs a series of `await` calls against a
  configured backend, and renders pass/fail rows. Manual verification
  in the browser. No JS test framework added.

- `docs/theme-integration.md` — One-page guide for sub-projects
  D.2+ showing the five-line `<head>` snippet and the
  `data-customer` CSS pattern.

**Modify:**

- `skel/api.php` — Append seven new endpoint branches (the file
  already has a long `if ($request == "...") { ... }` chain; new
  branches follow the same pattern). Total addition ~150 lines.
  Includes `module/customer_auth.php` and `module/customer_account.php`
  at the top. Adds a helper `extract_customer_token()` mirroring the
  one in `api/index.php`.

**Untouched:**

- `module/customer_auth.php`, `module/customer_account.php`,
  `module/database.php` — stable foundation from sub-projects A + B.
- `api/index.php` — the public store API surface stays exactly as it
  is. D.1 deliberately does not consolidate the two API entry points.
- `themes/` — no theme is touched.
- `skel/vm.api.js`, `skel/vm.theme.js` — the existing (unused)
  runtime classes are untouched. `vm-customer.js` is a parallel file.

## Backend: `skel/api.php` endpoints

Append seven branches inside the existing `if ($method === 'GET')`
and `elseif ($method === 'POST')` blocks. Each branch follows the
identical pattern from `api/index.php`'s customer endpoints:

```php
// At top of skel/api.php (after the existing includes):
@include_once dirname(dirname(__FILE__)) . "/module/customer_auth.php";
@include_once dirname(dirname(__FILE__)) . "/module/customer_account.php";

function extract_customer_token(): ?string {
    $tok = $_SERVER['HTTP_X_CUSTOMER_TOKEN'] ?? '';
    $tok = is_string($tok) ? trim($tok) : '';
    return $tok === '' ? null : $tok;
}
```

GET branches (inside the existing GET block):

- `customer_me` — resolves token, returns `{ok, customer}` or 401.
- `customer_my_orders` — resolves token, returns
  `{ok, orders:[...up to 100]}`. 401 if no/invalid token.

POST branches (inside the existing POST block):

- `customer_register` — body `{email, password, name?, phone?}`,
  delegates to `customer_register()`. 400 on validation failure.
- `customer_login` — body `{email, password}`, delegates to
  `customer_login()`. 401 on wrong creds, 429 on `code:"locked"`
  (using the structured field, not error-string matching).
- `customer_logout` — sends `X-Customer-Token`, delegates to
  `customer_logout()`. Always returns 200 (idempotent).
- `customer_update_profile` — body `{name?, phone?}`, requires
  token, delegates to `customer_update_profile()`. 400 on empty body.
- `customer_change_password` — body `{current_password, new_password}`,
  requires token, delegates to `customer_change_password()`. 401 on
  wrong current password, 400 on validation failure. Response includes
  the fresh token (sub-project A semantics: all prior sessions killed).

### Status code conventions

| Code | When |
|---|---|
| 200 | Success. |
| 400 | Validation failure (missing field, wrong shape). |
| 401 | Missing/invalid token, wrong login creds, or wrong current password. |
| 404 | Reserved for address endpoints (deferred). |
| 429 | Lockout (matches `code:"locked"` from the auth module). |

### Per-site database routing

`skel/api.php` already has `$db = __DB_MODULE__;` and
`$db->override_connection(dirname(__FILE__) . '/storage.data')`. The
customer auth and account modules expect a `database_manager $db`
connected to the per-site DB with `customers`, `customer_sessions`,
and (optionally) `customer_addresses` tables.

For D.1, the same `$db` connection is used for the customer endpoints
as for the existing product/order endpoints. This means:

- In **deployed mode** (each store has its own copy of skel/), each
  storefront has its own customer database — exactly as intended.
- In **engine-hosted mode**, if `skel/api.php` is reached without
  templating to the per-site DB, customers leak across stores.
  This is a pre-existing concern (the same issue would affect orders
  today) and is **out of scope for D.1**. The fix lives in the
  deployment templating logic.

### Schema bootstrap

Existing `skel/api.php` uses `$db->createTable(...)` at the top of
the file to ensure `page_views` and `orders` exist. D.1 follows the
same pattern: add three new `createTable` calls for `customers`,
`customer_sessions`, and an idempotent ALTER on `orders` for
`customer_id`. This avoids depending on `services/database.install.php`
being re-run when a fresh deploy lands.

The three statements mirror what `services/database.install.php`
does for the engine-managed per-site DBs. Address book table is
omitted (address endpoints deferred).

## JS helper: `skel/vm-customer.js`

Single file, vanilla JS, no dependencies. IIFE pattern matching the
existing `skel/vm.api.js`. Exposes one global: `window.vmCustomer`.

### Surface

```js
// State queries (synchronous, read from localStorage)
vmCustomer.isLoggedIn()       // → boolean
vmCustomer.token()            // → string | null
vmCustomer.cached()           // → cached customer object | null

// Auth flow (async, hits skel/api.php)
await vmCustomer.register(email, password, name?, phone?)
await vmCustomer.login(email, password)
await vmCustomer.logout()
await vmCustomer.me()         // refreshes the cached customer

// Account ops (async, requires logged-in token)
await vmCustomer.myOrders()
await vmCustomer.updateProfile(name?, phone?)
await vmCustomer.changePassword(currentPassword, newPassword)
```

### Storage keys

- `localStorage.vm_customer_token` — bearer token (64-char hex).
- `localStorage.vm_customer_cache` — JSON-serialized customer object
  from the last successful `me()` / `login()` / `register()`. Lets
  themes render the dashboard immediately on page load before the
  network round-trip completes.

### DOM events

Dispatched on `window` with no detail data unless noted:

- `vm:customer-loaded` — fired once on script init if the initial
  `me()` call succeeds. `event.detail = { customer }`.
- `vm:customer-login` — fired after successful `login()` or
  `register()`. `event.detail = { customer }`.
- `vm:customer-logout` — fired after `logout()`, OR after a 401 from
  any other endpoint, OR after the init `me()` call returns 401.

### 401 auto-logout

Every method's response handler checks for 401. If found:

1. Clear `localStorage.vm_customer_token` and `vm_customer_cache`.
2. Dispatch `vm:customer-logout`.
3. Reject the calling promise with an error whose `.code` is
   `'unauthenticated'`.

Themes don't need to handle "token expired" — the helper does, and
themes catch the rejection in their submit handlers to show the
appropriate UI.

### Initialization

On script load (after `DOMContentLoaded`):

1. Read `localStorage.vm_customer_token`. If missing, do nothing.
2. If present, call `me()`. On success, dispatch `vm:customer-loaded`.
   On 401, the existing auto-logout path fires (`vm:customer-logout`).

### API base URL resolution

The helper resolves its backend URL in this order:

1. `<script data-api-base="...">` attribute on the loading script tag.
2. `window.VM_CUSTOMER_API_BASE` global, if set before script load.
3. Default: `api.php` relative to the current page (same default
   as existing theme inline fetches).

This lets a theme override the URL for testing (point at a different
backend) without modifying the helper.

### Implementation sketch

```js
(function () {
  const TOKEN_KEY = 'vm_customer_token';
  const CACHE_KEY = 'vm_customer_cache';

  function resolveBase() {
    const tag = document.currentScript;
    if (tag && tag.dataset.apiBase) return tag.dataset.apiBase;
    if (window.VM_CUSTOMER_API_BASE) return window.VM_CUSTOMER_API_BASE;
    return new URL('api.php', window.location.href).href;
  }
  const BASE = resolveBase();

  function emit(type, detail) {
    window.dispatchEvent(new CustomEvent(type, { detail }));
  }

  function getToken() {
    try { return localStorage.getItem(TOKEN_KEY); } catch { return null; }
  }
  function setToken(t) {
    try { localStorage.setItem(TOKEN_KEY, t); } catch {}
  }
  function getCached() {
    try { return JSON.parse(localStorage.getItem(CACHE_KEY) || 'null'); } catch { return null; }
  }
  function setCached(c) {
    try { localStorage.setItem(CACHE_KEY, JSON.stringify(c)); } catch {}
  }
  function clearAll() {
    try {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(CACHE_KEY);
    } catch {}
  }

  async function call(state, { method = 'GET', body = null, withToken = false } = {}) {
    const url = `${BASE}?state=${encodeURIComponent(state)}`;
    const headers = { 'Content-Type': 'application/json' };
    if (withToken) {
      const t = getToken();
      if (t) headers['X-Customer-Token'] = t;
    }
    const res = await fetch(url, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });
    if (res.status === 401) {
      clearAll();
      emit('vm:customer-logout');
      const err = new Error('Unauthenticated');
      err.code = 'unauthenticated';
      throw err;
    }
    const data = await res.json().catch(() => ({}));
    if (!data.ok) {
      const err = new Error(data.error || `HTTP ${res.status}`);
      err.status = res.status;
      err.code = data.code || null;
      throw err;
    }
    return data;
  }

  const vmCustomer = {
    isLoggedIn() { return !!getToken(); },
    token() { return getToken(); },
    cached() { return getCached(); },

    async register(email, password, name = null, phone = null) {
      const r = await call('customer_register', {
        method: 'POST',
        body: { email, password, name, phone },
      });
      setToken(r.token);
      setCached(r.customer);
      emit('vm:customer-login', { customer: r.customer });
      return r;
    },

    async login(email, password) {
      const r = await call('customer_login', {
        method: 'POST',
        body: { email, password },
      });
      setToken(r.token);
      setCached(r.customer);
      emit('vm:customer-login', { customer: r.customer });
      return r;
    },

    async logout() {
      try { await call('customer_logout', { method: 'POST', withToken: true }); }
      catch (e) { /* network error during logout — we still clear locally */ }
      clearAll();
      emit('vm:customer-logout');
    },

    async me() {
      const r = await call('customer_me', { withToken: true });
      setCached(r.customer);
      return r;
    },

    async myOrders() {
      return call('customer_my_orders', { withToken: true });
    },

    async updateProfile(name = null, phone = null) {
      const r = await call('customer_update_profile', {
        method: 'POST',
        body: { name, phone },
        withToken: true,
      });
      setCached(r.customer);
      return r;
    },

    async changePassword(currentPassword, newPassword) {
      const r = await call('customer_change_password', {
        method: 'POST',
        body: { current_password: currentPassword, new_password: newPassword },
        withToken: true,
      });
      // change_password kills all sessions and returns a fresh token.
      setToken(r.token);
      setCached(r.customer);
      return r;
    },
  };

  // Initial bootstrap: if a token is present, resolve it.
  function init() {
    if (!getToken()) return;
    vmCustomer.me()
      .then(r => emit('vm:customer-loaded', { customer: r.customer }))
      .catch(() => { /* auto-logout already fired via 401 path */ });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.vmCustomer = vmCustomer;
})();
```

This sketch is the intended implementation. The plan will copy
this verbatim into `skel/vm-customer.js` modulo final variable name
tweaks.

## Theme integration: the five-line snippet

Documented in `docs/theme-integration.md`. The theme adopts D.1 by
adding to `<head>`:

```html
<script src="vm-customer.js" defer></script>
<script>
  window.addEventListener('vm:customer-loaded', e => {
    document.body.dataset.customer = 'logged-in';
  });
  window.addEventListener('vm:customer-login', e => {
    document.body.dataset.customer = 'logged-in';
  });
  window.addEventListener('vm:customer-logout', e => {
    document.body.dataset.customer = 'logged-out';
  });
</script>
```

And styles in the theme's existing CSS block:

```css
[data-customer="logged-out"] .show-when-logged-in { display: none; }
[data-customer="logged-in"]  .show-when-logged-out { display: none; }
```

Pages start with `data-customer` unset; the theme can either default
to logged-out via CSS (`body:not([data-customer="logged-in"]) .X`) or
gate visibility entirely on the event firing.

Themes then write their per-feature handlers — e.g., the login form
submit handler calls `await vmCustomer.login(email, password)` inside
a try/catch and shows the error or closes the modal.

## Failure modes & guards

| Scenario | Behavior |
|---|---|
| `vm-customer.js` loads but `api.php` is unreachable | `me()` fails on init with a network error. No `vm:customer-loaded` fires. Token stays in localStorage; next page may try again. |
| 401 from any endpoint | Auto-logout flow: clear localStorage, emit `vm:customer-logout`, reject the calling promise with `.code = 'unauthenticated'`. |
| 429 lockout on `login` | Promise rejects with `error.code = 'locked'` (from the structured field). Theme shows "Try again in a few minutes." |
| 400 validation on `register` / `update_profile` / `change_password` | Promise rejects with `error.message` from the backend's `error` field. Theme shows the message inline. |
| Token in localStorage but customer was deleted | `me()` returns 401 → auto-logout. |
| Customer changes password from another device | All sessions killed; current token returns 401 → auto-logout fires on next call. |
| Theme calls `myOrders()` without being logged in | Helper still sends the request without the token header. Backend returns 401, auto-logout fires. No special "you're not logged in" check on the client. |
| Network failure during `logout()` | Local state is cleared regardless. The backend session row may linger until expiry. Trade-off: prioritize the user's intent over server cleanliness. |

## Security notes

- **No store-level API key on the Micro API.** The existing
  `skel/api.php` has zero auth (matches today's posture for
  products/orders). Customer endpoints are gated by the bearer
  token directly. This is intentional and matches the per-site
  isolation model.
- **Token theft via XSS:** same surface as the public store API.
  Mitigated by 30-day TTL + 401-auto-logout + ease of revocation
  via `customer_change_password` (kills all sessions).
- **CORS:** `skel/api.php` already sends
  `Access-Control-Allow-Origin: *`. Customer endpoints inherit this.
  Browsers cannot send `X-Customer-Token` cross-origin without a
  preflight OPTIONS request, which the existing handler accepts.
- **Token in localStorage** vs HttpOnly cookie: localStorage is XSS
  exposed but works with the iframe/PWA delivery model. Cookie-based
  auth would hit cross-origin issues in iframe mode. Accepted.
- **No CSRF token.** Custom header (`X-Customer-Token`) cannot be
  set by cross-origin form posts without preflight; same reasoning
  as sub-project A.

## Testing

### Backend: HTTP smoke test via curl

The module-level coverage (62 + 36 PHP assertions) from sub-projects
A and B already validates the business logic. D.1's backend addition
is a thin wrapper. So no new module-isolation test is added; instead,
verification is HTTP-level smoke against a real site DB.

Procedure for the implementer:

1. Pick a known-working site (e.g., `debug.com` used in B's smoke).
2. Apply the schema if not already present — sub-project B's plan
   covers the one-liner.
3. Run the seven smoke calls in sequence against the storefront URL
   path that resolves to `skel/api.php` (e.g.,
   `http://localhost:8016/api/<store-id>/api.php` or whatever the
   deploy routing exposes — the implementer will discover the exact
   path during step 1).
4. Assert: `customer_register` 200 + token; `customer_me` with token
   200; `customer_me` without 401; `customer_login` wrong-pw 401;
   `customer_login` correct 200; `customer_my_orders` 200 + `[]`;
   `customer_update_profile` empty 400; `customer_update_profile`
   `{name:"X"}` 200; `customer_change_password` wrong-current 401;
   `customer_change_password` happy 200 + new token; old token dead
   on next `customer_me`; `customer_logout` 200; subsequent
   `customer_me` 401.

Each step is a single curl invocation. The implementer logs the
status code + first ~80 chars of response body per step. No
test-runner file is committed for D.1 — the smoke is run during
implementation and the results are recorded in the commit message.

The path-discovery step is important: D.1 may discover that
`skel/api.php` is not directly routable in engine-hosted mode, in
which case the smoke must be performed in deployed mode (or the
finding is reported as a blocker requiring a separate fix).

### JS helper: `tests/vm-customer-harness.html`

A static HTML page loaded in the browser. Has a small script that
runs the helper through:

1. `register` a fresh-email test customer
2. Confirm `isLoggedIn()` → true and `cached()` returns the customer
3. `me()` → returns customer
4. `myOrders()` → returns `[]`
5. `updateProfile('NewName', '555')` → returns updated customer
6. `changePassword('p1', 'p2')` → returns + new token; verify
   `localStorage.vm_customer_token` changed
7. `logout()` → `isLoggedIn()` → false, localStorage cleared

Each step renders a green/red row with the result. Manual verification.

This harness becomes useful in D.2+ when individual themes need a
sanity check that the helper still works after each integration.

## Out of scope (recap)

- Address book endpoints
- `customer_set_default`
- Touching any theme
- Refactoring `vm.api.js` / `vm.theme.js`
- Engine-hosted vs deployed mode reconciliation
- Email verification UI
- Logout-all-other-sessions UI

## Open questions

None. Approved in brainstorming on 2026-06-03.
