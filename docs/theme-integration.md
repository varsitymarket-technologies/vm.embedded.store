# Theme integration guide (vm-customer.js)

This guide shows how a theme adopts the `skel/vm-customer.js` helper
to plug into the storefront's customer auth surface. Aimed at
sub-projects D.2+ (the per-theme rewiring work) and anyone porting a
new theme from scratch.

## The five-line snippet

Add to the theme's `<head>`:

```html
<script src="vm-customer.js" defer></script>
<script>
    window.addEventListener('vm:customer-loaded', function (e) {
        document.body.dataset.customer = 'logged-in';
    });
    window.addEventListener('vm:customer-login', function (e) {
        document.body.dataset.customer = 'logged-in';
    });
    window.addEventListener('vm:customer-logout', function () {
        document.body.dataset.customer = 'logged-out';
    });
</script>
```

The `vm-customer.js` file is served from the same directory the theme's
`api.php` lives in — `new URL('api.php', window.location.href)` and
`<script src="vm-customer.js">` both resolve relatively, so they
target the same per-site deployment.

## Toggle logged-in vs logged-out sections with CSS

The snippet flips `document.body.dataset.customer` between
`'logged-in'` and `'logged-out'`. Use CSS attribute selectors to
show/hide sections:

```css
[data-customer="logged-out"] .show-when-logged-in { display: none; }
[data-customer="logged-in"]  .show-when-logged-out { display: none; }
```

Default state (no token in localStorage, no event fired yet) leaves
`data-customer` unset. Decide per-theme whether unset = logged-out:

```css
/* Unset = logged-out fallback */
body:not([data-customer="logged-in"]) .show-when-logged-in { display: none; }
```

## Form handlers

### Login

```html
<form id="loginForm">
    <input name="email" type="email" required>
    <input name="password" type="password" required>
    <button type="submit">Sign in</button>
    <div class="error" hidden></div>
</form>

<script>
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const errEl = form.querySelector('.error');
    errEl.hidden = true;
    try {
        await vmCustomer.login(form.email.value, form.password.value);
        // vm:customer-login event fires; CSS flips visibility.
        form.closest('.login-modal')?.classList.remove('open');
    } catch (err) {
        errEl.textContent = err.code === 'locked'
            ? 'Account temporarily locked. Try again in a few minutes.'
            : err.message;
        errEl.hidden = false;
    }
});
</script>
```

### Register

```html
<form id="registerForm">
    <input name="email" type="email" required>
    <input name="password" type="password" required minlength="8">
    <input name="name" type="text">
    <input name="phone" type="tel">
    <button type="submit">Create account</button>
    <div class="error" hidden></div>
</form>

<script>
document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const errEl = form.querySelector('.error');
    errEl.hidden = true;
    try {
        await vmCustomer.register(
            form.email.value,
            form.password.value,
            form.name.value || null,
            form.phone.value || null
        );
    } catch (err) {
        errEl.textContent = err.message;
        errEl.hidden = false;
    }
});
</script>
```

### Logout

```html
<button id="logoutBtn">Sign out</button>

<script>
document.getElementById('logoutBtn').addEventListener('click', async function () {
    await vmCustomer.logout();
    // vm:customer-logout fires; CSS flips visibility.
});
</script>
```

### My orders view

```html
<div class="show-when-logged-in" id="myOrders">
    <h2>My Orders</h2>
    <ul id="orderList"></ul>
</div>

<script>
window.addEventListener('vm:customer-loaded', renderOrders);
window.addEventListener('vm:customer-login', renderOrders);

async function renderOrders() {
    const list = document.getElementById('orderList');
    list.innerHTML = '<li>Loading…</li>';
    try {
        const r = await vmCustomer.myOrders();
        list.innerHTML = '';
        if (r.orders.length === 0) {
            list.innerHTML = '<li>No orders yet.</li>';
            return;
        }
        r.orders.forEach(function (o) {
            const li = document.createElement('li');
            li.textContent = '#' + o.id + ' — ' + o.status + ' — ' + o.total_amount;
            list.appendChild(li);
        });
    } catch (err) {
        list.innerHTML = '<li>Error: ' + err.message + '</li>';
    }
}
</script>
```

### Profile edit

```html
<form id="profileForm" class="show-when-logged-in">
    <input name="name" type="text">
    <input name="phone" type="tel">
    <button type="submit">Save</button>
</form>

<script>
function fillProfileForm() {
    const c = vmCustomer.cached();
    if (!c) return;
    document.querySelector('#profileForm [name="name"]').value = c.name || '';
    document.querySelector('#profileForm [name="phone"]').value = c.phone || '';
}
window.addEventListener('vm:customer-loaded', fillProfileForm);
window.addEventListener('vm:customer-login', fillProfileForm);

document.getElementById('profileForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    await vmCustomer.updateProfile(form.name.value || null, form.phone.value || null);
});
</script>
```

### Change password

```html
<form id="changePwForm" class="show-when-logged-in">
    <input name="current" type="password" required>
    <input name="next" type="password" required minlength="8">
    <button type="submit">Change password</button>
    <div class="error" hidden></div>
</form>

<script>
document.getElementById('changePwForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const errEl = form.querySelector('.error');
    errEl.hidden = true;
    try {
        await vmCustomer.changePassword(form.current.value, form.next.value);
        form.reset();
    } catch (err) {
        // Wrong current password is HTTP 400 (not 401) — no auto-logout fires.
        errEl.textContent = err.message;
        errEl.hidden = false;
    }
});
</script>
```

## What the helper handles for you

- Token storage in `localStorage.vm_customer_token`.
- Cached customer object in `localStorage.vm_customer_cache` so the
  dashboard renders immediately on page load.
- `X-Customer-Token` header on every request that needs it.
- Bootstrap call: if a token is present on page load, the helper
  calls `customer_me` and fires `vm:customer-loaded` with the
  customer object.
- 401 auto-logout: any endpoint returning 401 clears the token,
  fires `vm:customer-logout`, and rejects the promise with
  `err.code === 'unauthenticated'`. Backend endpoints distinguish
  "token expired" (401) from "body field is wrong" (400) so the
  auto-logout only fires when the session actually went bad.
- Token swap on `changePassword`: the new token returned by the
  backend replaces the stored one transparently.

## What you still build per theme

- The login / register / logout / profile / password / my-orders
  markup and styles.
- CSS for `data-customer="logged-in"` vs `data-customer="logged-out"`
  states.
- Error display per form (the helper rejects promises; you decide
  where to render the message).
- Routing into the account dashboard from the rest of the storefront
  (header avatar, nav link, etc.).
- Pre-fill checkout fields from `vmCustomer.cached()` if you want a
  faster logged-in checkout.

## Bonus endpoints not in D.1

Address book endpoints (`customer_addresses_*`) ship in a later
D-phase. If you want to support them today, hit the public store
API at `/store-access/<store_id>/?state=customer_addresses_*` with
the same `X-Customer-Token` header.

## Common pitfalls

- **CORS** — if your theme is loaded from a different origin than
  `api.php`, browsers block `X-Customer-Token`. Keep them
  same-origin or configure CORS on the deployment.
- **localStorage in incognito** — some browsers limit or disable
  storage in private mode. The helper degrades gracefully (every
  `localStorage` call is wrapped in try/catch) but the user won't
  stay logged in across page loads.
- **Old token after password change** — the helper handles this for
  you automatically. Don't read `localStorage.vm_customer_token`
  directly; use `vmCustomer.token()` (which is always current).
