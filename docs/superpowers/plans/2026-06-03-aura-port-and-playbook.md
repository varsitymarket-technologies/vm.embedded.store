# Aura Port + Playbook Implementation Plan (Sub-project D.3.1)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port `themes/aura/interface` to use the customer auth + account API via `vmCustomer.*` calls (account icon, auth modal, view-account section, app.auth handlers), and author a reusable playbook at `docs/superpowers/theme-port-playbook.md` that documents the pattern so D.3.2/D.4/D.5 implementers can apply it to other commerce-skeleton themes.

**Architecture:** Five surgical additions to aura's existing markup + JS. No backend changes (D.1 foundation is stable). Account handlers are namespaced under `app.auth` (avoiding global collisions with aura's existing symbols). The same `auth` adapter shape from D.2 austin (single-name backend → fname/lname client split) carries over. The playbook is a separate documentation file.

**Tech Stack:** Vanilla browser JS, Tailwind utility classes (existing in aura), Lucide icons (existing in aura). No new dependencies. No build step.

**Spec:** [docs/superpowers/specs/2026-06-03-aura-port-and-playbook-design.md](../specs/2026-06-03-aura-port-and-playbook-design.md)

---

## File Map

**Modify:**
- `themes/aura/interface` — five surgical edits totaling ~700 added lines.

**Create:**
- `docs/superpowers/theme-port-playbook.md` — the reusable pattern for D.3.2/D.4/D.5. ~450 lines.
- `themes/aura/vm-customer.js` — copy of `skel/vm-customer.js` so the theme can be smoke-tested directly via `/themes/aura/interface` (the engine's iframe routing is broken at this commit; this is a local-only test workaround).

**Untouched:**
- All other themes
- `module/`, `api/`, `skel/`, sub-project A/B/D.1/D.2 files

---

## Notes for the implementer

- Container: `vm-emb-sites`; project mount: `/var/www/html/public/`.
- aura's interface file is `themes/aura/interface` (no extension — full HTML+CSS+JS bundle).
- Current line markers (verified during plan authoring):
  - Line 56 — `</head>` (insertion point for Edit 1).
  - Lines 69-81 — header right-side button cluster (Search + Tote). Account button (Edit 2) inserts between them.
  - Line 185 — last existing `<section>` is `view-checkout`. Edit 4's `view-account` inserts before it.
  - Line 248 — existing `toast-notif` overlay. Auth modal (Edit 3) inserts right before this.
  - Line 339 — `const app = {` declaration. Edit 5 appends `app.auth = { ... }` after the last existing app-sub-object.
- `themes/` is gitignored at the project level — use `git add -f` for theme edits (same pattern as D.2).

---

## Task 1: Copy `vm-customer.js` into `themes/aura/` for local dev

**Files:**
- Create: `themes/aura/vm-customer.js`

### Step 1.1: Copy from skel/

- [ ] **Step 1.1: Copy the helper**

```bash
cp -f skel/vm-customer.js themes/aura/vm-customer.js
ls -la themes/aura/vm-customer.js
```

Expected: file exists (~5.9 KB).

### Step 1.2: Commit

- [ ] **Step 1.2: Commit**

```bash
git add -f themes/aura/vm-customer.js
git commit -m "chore(theme/aura): seed vm-customer.js copy for local smoke testing

The engine's iframe routing has a session-dependent quirk that
breaks loading the storefront at /sites/<domain>/. To smoke-test
aura's port we serve it directly via /themes/aura/interface; the
helper must be at the same directory for the relative
<script src=\"vm-customer.js\"> resolution to work.

Long-term, vm-customer.js gets copied into sites/<domain>/ on
deploy (same pattern as api.php). This file is the local-dev
shortcut."
```

---

## Task 2: Edit 1 — head snippet

**Files:**
- Modify: `themes/aura/interface` (around line 56, immediately before `</head>`)

### Step 2.1: Insert the snippet

- [ ] **Step 2.1: Edit `themes/aura/interface`**

Find this near line 56:

```html
    </style>
</head>
```

Insert this block immediately before `</head>`:

```html

    <!-- D.3.1: customer auth helper (vm-customer.js from same dir as api.php) -->
    <script src="vm-customer.js" defer></script>
    <script>
        // Toggle data-customer + sync aura's nav icon on auth events.
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
</head>
```

Note: aura had no `aus_user` legacy state, so the cleanup stanza from D.2's austin port is omitted here. The playbook keeps it as an optional step for themes that did.

### Step 2.2: Verify

- [ ] **Step 2.2: Sanity check**

```bash
grep -c "vm-customer.js" themes/aura/interface
grep -c "</head>" themes/aura/interface
```

Expected: first returns 2 (one in the comment, one in the `<script src>`), second returns 1.

### Step 2.3: Commit

- [ ] **Step 2.3: Commit**

```bash
git add -f themes/aura/interface
git commit -m "feat(theme/aura): add vm-customer.js head snippet (D.3.1 edit 1/5)"
```

---

## Task 3: Edit 2 — account button in header

**Files:**
- Modify: `themes/aura/interface` (line 69-81, between Search and Tote buttons)

### Step 3.1: Insert the button

- [ ] **Step 3.1: Edit `themes/aura/interface`**

Find this exact block (currently around lines 69-81):

```html
            <div class="flex items-center space-x-6 text-neutral-500">
                <button class="hover:text-black transition-colors duration-300" onclick="app.ui.toggleSearchOverlay()" aria-label="Search">
                    <span class="text-[11px] uppercase tracking-[0.15em] hidden md:block">Search</span>
                    <i data-lucide="search" class="w-4 h-4 stroke-[1] md:hidden"></i>
                </button>
                <button class="hover:text-black transition-colors duration-300 relative flex items-center" onclick="app.ui.toggleCartDrawer()" aria-label="Cart">
                    <span class="text-[11px] uppercase tracking-[0.15em] hidden md:block">Tote (<span id="global-cart-count-text">0</span>)</span>
                    <div class="md:hidden relative">
                        <i data-lucide="shopping-bag" class="w-4 h-4 stroke-[1]"></i>
                        <span id="global-cart-count" class="absolute -top-1.5 -right-1.5 text-[9px] text-white bg-black w-3.5 h-3.5 rounded-full flex items-center justify-center transition-all transform scale-0">0</span>
                    </div>
                </button>
            </div>
```

Insert a third button between the Search and Cart buttons:

```html
            <div class="flex items-center space-x-6 text-neutral-500">
                <button class="hover:text-black transition-colors duration-300" onclick="app.ui.toggleSearchOverlay()" aria-label="Search">
                    <span class="text-[11px] uppercase tracking-[0.15em] hidden md:block">Search</span>
                    <i data-lucide="search" class="w-4 h-4 stroke-[1] md:hidden"></i>
                </button>
                <button id="auth-icon-btn"
                        class="hover:text-black transition-colors duration-300 flex items-center"
                        onclick="app.auth.iconClick()"
                        aria-label="Account">
                    <span class="text-[11px] uppercase tracking-[0.15em] hidden md:block" id="auth-icon-label">Account</span>
                    <i data-lucide="user" class="w-4 h-4 stroke-[1] md:hidden"></i>
                </button>
                <button class="hover:text-black transition-colors duration-300 relative flex items-center" onclick="app.ui.toggleCartDrawer()" aria-label="Cart">
                    <span class="text-[11px] uppercase tracking-[0.15em] hidden md:block">Tote (<span id="global-cart-count-text">0</span>)</span>
                    <div class="md:hidden relative">
                        <i data-lucide="shopping-bag" class="w-4 h-4 stroke-[1]"></i>
                        <span id="global-cart-count" class="absolute -top-1.5 -right-1.5 text-[9px] text-white bg-black w-3.5 h-3.5 rounded-full flex items-center justify-center transition-all transform scale-0">0</span>
                    </div>
                </button>
            </div>
```

### Step 3.2: Commit

- [ ] **Step 3.2: Commit**

```bash
git add -f themes/aura/interface
git commit -m "feat(theme/aura): add Account button to header (D.3.1 edit 2/5)

Inserts between Search and Tote buttons. Uses Lucide 'user' icon
(matching aura's existing icon scheme) for the mobile compact view
and a text 'Account' label for desktop. onclick handler is
app.auth.iconClick() which opens the modal when logged out and
navigates to #account when logged in (defined in edit 5)."
```

---

## Task 4: Edit 3 — auth modal overlay

**Files:**
- Modify: `themes/aura/interface` (around line 248, immediately before the existing `toast-notif` overlay)

### Step 4.1: Insert the modal

- [ ] **Step 4.1: Edit `themes/aura/interface`**

Find the existing toast overlay at line 248:

```html
    <div id="toast-notif" class="fixed bottom-8 left-1/2 transform -translate-x-1/2 translate-y-10 bg-black text-white text-[10px] uppercase tracking-[0.15em] py-3 px-6 shadow-sm z-50 opacity-0 transition-all duration-500 pointer-events-none whitespace-nowrap">
        <span id="toast-notif-message">Item added</span>
    </div>
```

Insert this auth modal block **immediately before** the toast overlay:

```html
    <!-- D.3.1: Auth modal overlay -->
    <div id="auth-modal" class="fixed inset-0 bg-white z-50 hidden flex-col items-center justify-start overflow-y-auto p-6">
        <div class="w-full max-w-md mx-auto pt-12 pb-24">
            <div class="flex items-center justify-between mb-12">
                <a href="#home" onclick="app.auth.close(); app.router.navigate('home')" class="font-serif text-2xl font-light tracking-[0.1em] text-black">AURA</a>
                <button onclick="app.auth.close()" aria-label="Close" class="text-neutral-500 hover:text-black transition-colors">
                    <i data-lucide="x" class="w-5 h-5 stroke-[1]"></i>
                </button>
            </div>

            <!-- Tab nav -->
            <div class="flex items-center justify-center gap-8 mb-10 text-[11px] uppercase tracking-[0.15em]">
                <button id="auth-tab-login"    onclick="app.auth.switchTab('login')"    class="auth-tab pb-2">Sign In</button>
                <button id="auth-tab-register" onclick="app.auth.switchTab('register')" class="auth-tab pb-2">Create</button>
                <button id="auth-tab-forgot"   onclick="app.auth.switchTab('forgot')"   class="auth-tab pb-2">Forgot</button>
            </div>

            <!-- Login panel -->
            <div id="auth-panel-login" class="auth-panel">
                <form onsubmit="event.preventDefault(); app.auth.submitLogin()" class="space-y-6">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Email</label>
                        <input type="email" id="auth-login-email" required class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Password</label>
                        <input type="password" id="auth-login-pass" required class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <div id="auth-login-error" class="hidden text-[11px] text-red-600 mt-2"></div>
                    <button type="submit" class="w-full bg-black text-white text-[11px] uppercase tracking-[0.15em] py-3 mt-4 hover:bg-neutral-800 transition-colors">Sign In</button>
                </form>
            </div>

            <!-- Register panel -->
            <div id="auth-panel-register" class="auth-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.submitRegister()" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">First Name</label>
                            <input type="text" id="auth-reg-fname" required class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Last Name</label>
                            <input type="text" id="auth-reg-lname" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Email</label>
                        <input type="email" id="auth-reg-email" required class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Password</label>
                        <input type="password" id="auth-reg-pass" required minlength="8" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Confirm Password</label>
                        <input type="password" id="auth-reg-pass2" required minlength="8" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <div id="auth-reg-error" class="hidden text-[11px] text-red-600 mt-2"></div>
                    <button type="submit" class="w-full bg-black text-white text-[11px] uppercase tracking-[0.15em] py-3 mt-4 hover:bg-neutral-800 transition-colors">Create Account</button>
                </form>
            </div>

            <!-- Forgot panel -->
            <div id="auth-panel-forgot" class="auth-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.submitForgot()" class="space-y-6">
                    <p class="text-[11px] text-neutral-500 mb-4 leading-relaxed">Enter the email associated with your account and we'll send a reset link when this feature becomes available.</p>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Email</label>
                        <input type="email" id="auth-forgot-email" required class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <button type="submit" class="w-full bg-black text-white text-[11px] uppercase tracking-[0.15em] py-3 mt-4 hover:bg-neutral-800 transition-colors">Send Reset</button>
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

### Step 4.2: Commit

- [ ] **Step 4.2: Commit**

```bash
git add -f themes/aura/interface
git commit -m "feat(theme/aura): add auth modal overlay (D.3.1 edit 3/5)

Three tabs (Sign In / Create / Forgot) using aura's existing
visual language: bg-transparent border-b inputs, black-on-white
buttons, 11px uppercase tracking-[0.15em] labels."
```

---

## Task 5: Edit 4 — `view-account` section

**Files:**
- Modify: `themes/aura/interface` (before `<section id="view-checkout">` around line 185)

### Step 5.1: Insert the section

- [ ] **Step 5.1: Edit `themes/aura/interface`**

Find this line (around line 185):

```html
        <section id="view-checkout" class="view-section max-w-5xl mx-auto px-4 py-16">
```

Insert this block immediately before that line:

```html
        <!-- D.3.1: account view -->
        <section id="view-account" class="view-section max-w-5xl mx-auto px-4 sm:px-8 py-16">
            <div class="flex items-end justify-between border-b border-[#E5E5E5] pb-6 mb-12">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-1">Account</p>
                    <h1 id="acc-greeting" class="font-serif text-3xl font-light text-black">Welcome</h1>
                </div>
                <button onclick="app.auth.doLogout()" class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 hover:text-black transition-colors">Sign Out</button>
            </div>

            <div class="flex items-center gap-10 mb-12 text-[11px] uppercase tracking-[0.15em] border-b border-[#E5E5E5]">
                <button id="acc-tab-overview" onclick="app.auth.activateTab('overview')" class="acc-tab pb-3">Overview</button>
                <button id="acc-tab-profile"  onclick="app.auth.activateTab('profile')"  class="acc-tab pb-3">Profile</button>
                <button id="acc-tab-orders"   onclick="app.auth.activateTab('orders')"   class="acc-tab pb-3">Orders</button>
                <button id="acc-tab-password" onclick="app.auth.activateTab('password')" class="acc-tab pb-3">Password</button>
            </div>

            <div id="acc-panel-overview" class="acc-panel">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Name</p>
                        <p id="acc-ov-name" class="font-serif text-xl text-black">—</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Email</p>
                        <p id="acc-ov-email" class="text-sm text-black break-all">—</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Member Since</p>
                        <p id="acc-ov-joined" class="text-sm text-black">—</p>
                    </div>
                </div>
                <button onclick="app.auth.activateTab('profile')" class="text-[11px] uppercase tracking-[0.15em] text-black border-b border-black pb-1 hover:text-neutral-600 hover:border-neutral-600 transition-colors">Edit profile</button>
            </div>

            <div id="acc-panel-profile" class="acc-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.saveProfile()" class="space-y-8 max-w-xl">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">First Name</label>
                            <input type="text" id="acc-fname" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Last Name</label>
                            <input type="text" id="acc-lname" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Email</label>
                        <input type="email" id="acc-email" readonly title="Email is the account identifier and cannot be changed here" class="w-full bg-neutral-100 border-b border-[#E5E5E5] py-2 text-neutral-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Phone (optional)</label>
                        <input type="tel" id="acc-phone" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <button type="submit" class="bg-black text-white text-[11px] uppercase tracking-[0.15em] py-3 px-10 hover:bg-neutral-800 transition-colors">Save</button>
                </form>
            </div>

            <div id="acc-panel-orders" class="acc-panel hidden">
                <div id="acc-orders-list">
                    <p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 py-12 text-center">Loading…</p>
                </div>
            </div>

            <div id="acc-panel-password" class="acc-panel hidden">
                <form onsubmit="event.preventDefault(); app.auth.changePassword()" class="space-y-6 max-w-md">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Current Password</label>
                        <input type="password" id="acc-pw-cur" required class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">New Password</label>
                        <input type="password" id="acc-pw-new" required minlength="8" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.15em] text-neutral-500 mb-2">Confirm New Password</label>
                        <input type="password" id="acc-pw-conf" required minlength="8" class="w-full bg-transparent border-b border-[#E5E5E5] py-2 text-black focus:outline-none focus:border-black transition-colors">
                    </div>
                    <button type="submit" class="bg-black text-white text-[11px] uppercase tracking-[0.15em] py-3 px-10 hover:bg-neutral-800 transition-colors">Change Password</button>
                </form>
            </div>
        </section>

        <style>
            .acc-tab { color: rgb(115 115 115); border-bottom: 1px solid transparent; transition: all 0.3s; }
            .acc-tab.active { color: #000; border-bottom-color: #000; }
        </style>
```

### Step 5.2: Commit

- [ ] **Step 5.2: Commit**

```bash
git add -f themes/aura/interface
git commit -m "feat(theme/aura): add view-account section (D.3.1 edit 4/5)"
```

---

## Task 6: Edit 5 — `app.auth` handlers + router wiring

**Files:**
- Modify: `themes/aura/interface` (insert `app.auth = {...}` before `window.addEventListener('DOMContentLoaded'...)`, modify `app.router.evalRoute`)

### Step 6.1: Insert `app.auth` object

- [ ] **Step 6.1: Edit `themes/aura/interface`**

Find:

```js
        window.addEventListener('DOMContentLoaded', () => app.init());
```

Insert this block immediately before that line:

```js

        // D.3.1: customer auth + account handlers (all delegate to vmCustomer)
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
                    phone: c.phone || '',
                    joinDate: c.created_at || ''
                };
            },
            isLoggedIn: function () { return !!window.vmCustomer && vmCustomer.isLoggedIn(); },

            iconClick: function () {
                if (this.isLoggedIn()) app.router.navigate('account');
                else this.open('login');
            },
            updateIcon: function () {
                const label = document.getElementById('auth-icon-label');
                if (!label) return;
                const u = this.getUser();
                label.textContent = u && u.fname ? u.fname : 'Account';
            },

            open: function (tab) {
                this.switchTab(tab || 'login');
                const m = document.getElementById('auth-modal');
                m.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                if (window.lucide && typeof lucide.createIcons === 'function') lucide.createIcons();
            },
            close: function () {
                const m = document.getElementById('auth-modal');
                m.classList.remove('is-open');
                document.body.style.overflow = '';
                ['auth-login-error', 'auth-reg-error'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) { el.textContent = ''; el.classList.add('hidden'); }
                });
            },
            switchTab: function (tab) {
                ['login', 'register', 'forgot'].forEach(function (t) {
                    const btn = document.getElementById('auth-tab-' + t);
                    const pan = document.getElementById('auth-panel-' + t);
                    if (btn) btn.classList.toggle('active', t === tab);
                    if (pan) pan.classList.toggle('hidden', t !== tab);
                });
            },

            submitLogin: async function () {
                const email = document.getElementById('auth-login-email').value.trim();
                const pass = document.getElementById('auth-login-pass').value;
                const errEl = document.getElementById('auth-login-error');
                errEl.classList.add('hidden');
                if (!email || !pass) { errEl.textContent = 'Email and password required.'; errEl.classList.remove('hidden'); return; }
                try {
                    await vmCustomer.login(email, pass);
                    this.close();
                    const u = this.getUser();
                    app.ui.triggerToast('Welcome, ' + ((u && u.fname) || 'friend'));
                    app.router.navigate('account');
                } catch (e) {
                    errEl.textContent = (e && e.code === 'locked')
                        ? 'Account temporarily locked. Try again in a few minutes.'
                        : ((e && e.message) || 'Sign-in failed');
                    errEl.classList.remove('hidden');
                }
            },
            submitRegister: async function () {
                const fname = document.getElementById('auth-reg-fname').value.trim();
                const lname = document.getElementById('auth-reg-lname').value.trim();
                const email = document.getElementById('auth-reg-email').value.trim();
                const pass  = document.getElementById('auth-reg-pass').value;
                const pass2 = document.getElementById('auth-reg-pass2').value;
                const errEl = document.getElementById('auth-reg-error');
                errEl.classList.add('hidden');
                if (!fname || !email || !pass) { errEl.textContent = 'Please fill in all required fields.'; errEl.classList.remove('hidden'); return; }
                if (pass !== pass2) { errEl.textContent = 'Passwords do not match.'; errEl.classList.remove('hidden'); return; }
                if (pass.length < 8) { errEl.textContent = 'Password must be at least 8 characters.'; errEl.classList.remove('hidden'); return; }
                const name = (fname + ' ' + lname).trim();
                try {
                    await vmCustomer.register(email, pass, name, null);
                    this.close();
                    app.ui.triggerToast('Welcome, ' + fname);
                    app.router.navigate('account');
                } catch (e) {
                    errEl.textContent = (e && e.message) || 'Sign-up failed';
                    errEl.classList.remove('hidden');
                }
            },
            submitForgot: function () {
                app.ui.triggerToast('Reset link sent (placeholder)');
                this.switchTab('login');
            },
            doLogout: async function () {
                try { await vmCustomer.logout(); } catch (e) { /* cleared locally regardless */ }
                app.ui.triggerToast('Signed out');
                app.router.navigate('home');
            },

            activateTab: function (name) {
                ['overview', 'profile', 'orders', 'password'].forEach(function (t) {
                    const btn = document.getElementById('acc-tab-' + t);
                    const pan = document.getElementById('acc-panel-' + t);
                    if (btn) btn.classList.toggle('active', t === name);
                    if (pan) pan.classList.toggle('hidden', t !== name);
                });
                if (name === 'orders') app.auth.hydrateOrders();
            },
            renderDashboard: async function () {
                if (!this.isLoggedIn()) { this.open('login'); return; }
                const u = this.getUser();
                if (!u) { this.open('login'); return; }
                document.getElementById('acc-greeting').textContent = 'Welcome, ' + ((u.fname) || 'friend');
                document.getElementById('acc-ov-name').textContent  = (u.fname + ' ' + u.lname).trim() || '—';
                document.getElementById('acc-ov-email').textContent = u.email || '—';
                document.getElementById('acc-ov-joined').textContent = u.joinDate ? u.joinDate.split(' ')[0] : '—';
                document.getElementById('acc-fname').value = u.fname || '';
                document.getElementById('acc-lname').value = u.lname || '';
                document.getElementById('acc-email').value = u.email || '';
                document.getElementById('acc-phone').value = u.phone || '';
                this.activateTab('overview');
            },
            hydrateOrders: async function () {
                const wrap = document.getElementById('acc-orders-list');
                wrap.innerHTML = '<p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 py-12 text-center">Loading…</p>';
                try {
                    const r = await vmCustomer.myOrders();
                    const orders = (r.orders || []);
                    if (!orders.length) {
                        wrap.innerHTML = '<p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500 py-12 text-center">No orders yet. <a href="#shop" onclick="app.router.navigate(\'shop\')" class="text-black border-b border-black pb-0.5">Start shopping →</a></p>';
                        return;
                    }
                    wrap.innerHTML = '<div class="space-y-4">' + orders.map(function (o) {
                        const total = (o.total_amount != null ? o.total_amount : 0).toFixed(2);
                        const dateStr = o.created_at ? String(o.created_at).split(' ')[0] : '—';
                        const status = o.status || 'pending';
                        return '<div class="flex items-center justify-between border-b border-[#E5E5E5] pb-4">' +
                                  '<div><p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500">Order</p>' +
                                  '<p class="font-serif text-lg text-black">#' + o.id + '</p></div>' +
                                  '<div class="text-right"><p class="text-[11px] uppercase tracking-[0.15em] text-neutral-500">' + dateStr + '</p>' +
                                  '<p class="text-sm text-black">$' + total + ' &middot; ' + status + '</p></div>' +
                               '</div>';
                    }).join('') + '</div>';
                } catch (e) {
                    wrap.innerHTML = '<p class="text-[11px] uppercase tracking-[0.15em] text-red-600 py-12 text-center">Failed to load orders.</p>';
                }
            },
            saveProfile: async function () {
                const fname = document.getElementById('acc-fname').value.trim();
                const lname = document.getElementById('acc-lname').value.trim();
                const phone = document.getElementById('acc-phone').value.trim();
                const name = (fname + ' ' + lname).trim();
                try {
                    await vmCustomer.updateProfile(name || null, phone || null);
                    const u = this.getUser();
                    if (u) {
                        document.getElementById('acc-greeting').textContent = 'Welcome, ' + ((u.fname) || 'friend');
                        document.getElementById('acc-ov-name').textContent = (u.fname + ' ' + u.lname).trim() || '—';
                    }
                    this.updateIcon();
                    app.ui.triggerToast('Profile updated');
                } catch (e) {
                    app.ui.triggerToast((e && e.message) || 'Update failed');
                }
            },
            changePassword: async function () {
                const cur = document.getElementById('acc-pw-cur').value;
                const nw  = document.getElementById('acc-pw-new').value;
                const conf = document.getElementById('acc-pw-conf').value;
                if (!cur || !nw || !conf) { app.ui.triggerToast('All password fields required'); return; }
                if (nw.length < 8) { app.ui.triggerToast('New password must be 8+ chars'); return; }
                if (nw !== conf) { app.ui.triggerToast('Passwords do not match'); return; }
                try {
                    await vmCustomer.changePassword(cur, nw);
                    ['acc-pw-cur','acc-pw-new','acc-pw-conf'].forEach(function (id) {
                        const el = document.getElementById(id); if (el) el.value = '';
                    });
                    app.ui.triggerToast('Password updated');
                } catch (e) {
                    app.ui.triggerToast((e && e.message) || 'Failed to change password');
                }
            }
        };

```

### Step 6.2: Wire `#account` into the router

Find the existing `app.router.evalRoute` function. After the
`else if (view === '#checkout')` branch, add:

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

### Step 6.3: JS syntax check via node

- [ ] **Step 6.3: Verify the JS parses**

```bash
awk '/<script>/{flag=1; next} /<\/script>/{flag=0} flag' themes/aura/interface > /tmp/aura.js
node --check /tmp/aura.js
```

Expected: no output (success).

### Step 6.4: Sanity grep

```bash
echo "vmCustomer call sites:"
grep -c "vmCustomer\." themes/aura/interface
echo "app.auth references:"
grep -c "app\.auth\." themes/aura/interface
echo "#account route branch:"
grep -c "view === '#account'" themes/aura/interface
```

Expected: vmCustomer 8+, app.auth many, #account exactly 1.

### Step 6.5: Commit

- [ ] **Step 6.5: Commit**

```bash
git add -f themes/aura/interface
git commit -m "feat(theme/aura): wire app.auth handlers + router (D.3.1 edit 5/5)

app.auth = { ... } namespace with login/register/logout modal
handlers, account-view tab switching, dashboard render, order
hydration, profile save, password change. Router learns about
#account: shows modal if logged out, renders dashboard if
logged in.

Sub-project D.3.1 aura wiring complete; smoke test next."
```

---

## Task 7: Browser smoke test

**Files:** none (verification only)

Open `http://localhost:8016/themes/aura/interface` and run through these
10 checks. In DevTools console, override the API base:

```js
window.VM_CUSTOMER_API_BASE = 'http://localhost:8016/sites/debug.com/api.php';
location.reload();
```

- [ ] **Step 7.1:** Account icon visible in header between Search and Tote.
- [ ] **Step 7.2:** Click Account → modal opens at Sign In tab.
- [ ] **Step 7.3:** Switch to Create tab → register fresh email `aura-smoke-$(date +%s)@x.com` / "Smoke" / "Tester" / "goodpass1". Modal closes; toast appears; hash becomes `#account`; header label changes to "Smoke".
- [ ] **Step 7.4:** Profile tab → edit phone → Save → toast "Profile updated".
- [ ] **Step 7.5:** Orders tab → empty state visible ("No orders yet").
- [ ] **Step 7.6:** Password tab → wrong current → toast "Current password is incorrect" (status 400, no auto-logout).
- [ ] **Step 7.7:** Password tab → correct current → toast "Password updated".
- [ ] **Step 7.8:** DevTools → `localStorage.vm_customer_token` value changed.
- [ ] **Step 7.9:** Sign Out button → toast "Signed out", header label reverts to "Account".
- [ ] **Step 7.10:** Login again with new password → account view restores.

If any step fails, fix and re-run before Task 8.

---

## Task 8: Author the theme-port playbook

**Files:**
- Create: `docs/superpowers/theme-port-playbook.md`

### Step 8.1: Write the playbook

- [ ] **Step 8.1: Create `docs/superpowers/theme-port-playbook.md`** with the following structure (full content described inline; copy from this section):

The playbook captures the same 5-edit pattern used in this plan,
reframed as a reusable guide for D.3.2/D.4/D.5. Structure:

1. **Overview** — which themes this applies to (the audit's Bronze tier
   + the 4/7/1 commerce-only lookalikes).
2. **The 5-question integration checklist** — file path, `</head>` line,
   cart-button parent, view-section location, theme's toast function.
3. **The 5 edits** — for each, the paste-in code from this plan's
   Tasks 2-6, with `<<DOUBLE-ANGLE-BRACKETS>>` placeholders for
   theme-specific tokens (icon library, input/button/label classes,
   brand-logo markup).
4. **Common variations** — Bootstrap icons vs Lucide, missing toast
   function (drop in a 12-line micro-toast helper), themes without
   `app = {}` skeleton (defer to D.4/D.5 patterns).
5. **Smoke test sequence** — identical to Task 7 above.
6. **Common pitfalls** — CORS, `vmCustomer` undefined, missing
   `lucide.createIcons()` call inside modal open, `state=order` not
   stamping `customer_id`.
7. **Time estimate** — 25-40 minutes per theme for D.3.2's
   fill-in-the-blank work.

The full text of each section is captured verbatim by:

- For the integration checklist: copy from this plan's "Notes for the
  implementer" + the per-task header.
- For each Edit's paste-in code: copy from this plan's Task N
  corresponding step.
- For the smoke test: copy from this plan's Task 7.
- For common pitfalls: synthesize from `docs/theme-integration.md`
  (D.1's guide) plus the new learnings from D.2 + D.3.1 (the
  `state=order` customer_id gap is the most important new entry).

The playbook should be ~400-500 lines. It is a self-contained
reference — the implementer of D.3.2/D.4/D.5 should not need to read
this plan or D.2's plan; the playbook tells them everything.

### Step 8.2: Verify length and commit

- [ ] **Step 8.2: Commit**

```bash
wc -l docs/superpowers/theme-port-playbook.md
git add docs/superpowers/theme-port-playbook.md
git commit -m "docs: theme port playbook for D.3.2+/D.4/D.5 (D.3.1 step 8/8)

Captures the 5-edit pattern from porting aura into a reusable
guide. Integration checklist, paste-in templates with token
placeholders, common variations, full smoke test sequence,
common pitfalls. Estimated 25-40 min per theme for D.3.2."
```

---

## Verification checklist

Before declaring sub-project D.3.1 done:

- [ ] `themes/aura/interface` has exactly one `vm-customer.js` script tag.
- [ ] Exactly one `<div id="auth-modal">` and one `<section id="view-account">`.
- [ ] Exactly one `app.auth = {` declaration.
- [ ] `node --check` on extracted JS passes.
- [ ] All 10 smoke steps in Task 7 pass (via API base override).
- [ ] `docs/superpowers/theme-port-playbook.md` exists.
- [ ] No other theme was modified.
- [ ] `module/`, `api/`, `skel/` are untouched.

