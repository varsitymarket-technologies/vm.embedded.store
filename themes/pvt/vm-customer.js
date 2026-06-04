/**
 * Varsity Market Customer Auth Helper
 * Drop-in client for the customer_* endpoints in skel/api.php.
 * Owns token storage, the X-Customer-Token header, and DOM events.
 * Version: 1.0.0 (sub-project D.1)
 *
 * Usage:
 *   <script src="vm-customer.js" defer></script>
 *
 * Themes then call vmCustomer.login(...), .register(...), .me(), etc.
 * and listen for window events: vm:customer-loaded, vm:customer-login,
 * vm:customer-logout. See docs/theme-integration.md.
 */
(function () {
    const TOKEN_KEY = 'vm_customer_token';
    const CACHE_KEY = 'vm_customer_cache';

    function resolveBase() {
        const tag = document.currentScript;
        if (tag && tag.dataset && tag.dataset.apiBase) return tag.dataset.apiBase;
        if (window.VM_CUSTOMER_API_BASE) return window.VM_CUSTOMER_API_BASE;
        return new URL('api.php', window.location.href).href;
    }
    const BASE = resolveBase();

    function emit(type, detail) {
        window.dispatchEvent(new CustomEvent(type, { detail: detail || null }));
    }

    function getToken() {
        try { return localStorage.getItem(TOKEN_KEY); } catch (e) { return null; }
    }
    function setToken(t) {
        try { localStorage.setItem(TOKEN_KEY, t); } catch (e) { /* ignore */ }
    }
    function getCached() {
        try { return JSON.parse(localStorage.getItem(CACHE_KEY) || 'null'); } catch (e) { return null; }
    }
    function setCached(c) {
        try { localStorage.setItem(CACHE_KEY, JSON.stringify(c)); } catch (e) { /* ignore */ }
    }
    function clearAll() {
        try {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(CACHE_KEY);
        } catch (e) { /* ignore */ }
    }

    async function call(state, opts) {
        opts = opts || {};
        const method = opts.method || 'GET';
        const body = opts.body || null;
        const withToken = !!opts.withToken;

        const url = BASE + (BASE.indexOf('?') === -1 ? '?' : '&')
            + 'state=' + encodeURIComponent(state);
        const headers = { 'Content-Type': 'application/json' };
        if (withToken) {
            const t = getToken();
            if (t) headers['X-Customer-Token'] = t;
        }
        const fetchOpts = { method: method, headers: headers };
        if (body) fetchOpts.body = JSON.stringify(body);

        const res = await fetch(url, fetchOpts);
        if (res.status === 401) {
            clearAll();
            emit('vm:customer-logout');
            const err = new Error('Unauthenticated');
            err.code = 'unauthenticated';
            err.status = 401;
            throw err;
        }
        const data = await res.json().catch(function () { return {}; });
        if (!data.ok) {
            const err = new Error(data.error || ('HTTP ' + res.status));
            err.status = res.status;
            err.code = data.code || null;
            throw err;
        }
        return data;
    }

    const vmCustomer = {
        isLoggedIn: function () { return !!getToken(); },
        token: function () { return getToken(); },
        cached: function () { return getCached(); },

        register: async function (email, password, name, phone) {
            const r = await call('customer_register', {
                method: 'POST',
                body: { email: email, password: password, name: name || null, phone: phone || null }
            });
            setToken(r.token);
            setCached(r.customer);
            emit('vm:customer-login', { customer: r.customer });
            return r;
        },

        login: async function (email, password) {
            const r = await call('customer_login', {
                method: 'POST',
                body: { email: email, password: password }
            });
            setToken(r.token);
            setCached(r.customer);
            emit('vm:customer-login', { customer: r.customer });
            return r;
        },

        logout: async function () {
            try {
                await call('customer_logout', { method: 'POST', withToken: true });
            } catch (e) {
                // Network or 401 during logout — still clear locally.
            }
            clearAll();
            emit('vm:customer-logout');
        },

        me: async function () {
            const r = await call('customer_me', { withToken: true });
            setCached(r.customer);
            return r;
        },

        myOrders: async function () {
            return call('customer_my_orders', { withToken: true });
        },

        updateProfile: async function (name, phone) {
            const r = await call('customer_update_profile', {
                method: 'POST',
                body: { name: name || null, phone: phone || null },
                withToken: true
            });
            setCached(r.customer);
            return r;
        },

        changePassword: async function (currentPassword, newPassword) {
            const r = await call('customer_change_password', {
                method: 'POST',
                body: { current_password: currentPassword, new_password: newPassword },
                withToken: true
            });
            // change_password kills all sessions and returns a fresh token.
            setToken(r.token);
            setCached(r.customer);
            return r;
        }
    };

    // Initial bootstrap: if a token is present, resolve it.
    function init() {
        if (!getToken()) return;
        vmCustomer.me()
            .then(function (r) { emit('vm:customer-loaded', { customer: r.customer }); })
            .catch(function () { /* auto-logout already fired via 401 path */ });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.vmCustomer = vmCustomer;
})();
