/**
 * VM Store SDK v1.0.0
 * Varsity Market Embedded Store - JavaScript Storefront SDK
 *
 * Drop-in SDK for external websites (GitHub Pages, static sites, etc.)
 * to add full e-commerce functionality powered by your VM store.
 *
 * Usage:
 *   <script src="https://your-domain.com/store-access/{store-id}/sdk/vm-store.js"></script>
 *   <script>
 *     const store = new VMStore({ storeId: 'YOUR_STORE_ID', apiKey: 'vm_live_...' });
 *     store.products.list().then(data => console.log(data));
 *   </script>
 */

(function (global) {
    'use strict';

    // --- Configuration ---
    function VMStore(config) {
        if (!config || !config.storeId || !config.apiKey) {
            throw new Error('VMStore: storeId and apiKey are required.');
        }

        this.storeId = config.storeId;
        this.apiKey = config.apiKey;
        this.baseUrl = config.baseUrl || this._detectBaseUrl();
        this.currency = config.currency || 'R';
        this._cartId = null;
        this._listeners = {};

        // Sub-modules
        this.products = new ProductsAPI(this);
        this.categories = new CategoriesAPI(this);
        this.cart = new CartAPI(this);
        this.checkout = new CheckoutAPI(this);
        this.orders = new OrdersAPI(this);
        this.store = new StoreAPI(this);
        this.discounts = new DiscountsAPI(this);
        this.ui = new UIComponents(this);
    }

    // Detect base URL from the SDK script tag src
    VMStore.prototype._detectBaseUrl = function () {
        if (typeof document !== 'undefined') {
            var scripts = document.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
                var src = scripts[i].src || '';
                var match = src.match(/(https?:\/\/[^/]+)\/store-access\//);
                if (match) return match[1];
            }
        }
        return '';
    };

    // Core fetch wrapper
    VMStore.prototype._request = function (endpoint, options) {
        var url = this.baseUrl + '/store-access/' + this.storeId + '/?state=' + endpoint;
        var opts = options || {};
        var method = opts.method || 'GET';
        var body = opts.body || null;
        var params = opts.params || {};

        // Append query params for GET
        var queryParts = [];
        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                queryParts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
            }
        }
        if (queryParts.length > 0) {
            url += '&' + queryParts.join('&');
        }

        var fetchOpts = {
            method: method,
            headers: {
                'X-API-Key': this.apiKey,
                'Content-Type': 'application/json'
            }
        };

        if (body && method !== 'GET') {
            fetchOpts.body = JSON.stringify(body);
        }

        return fetch(url, fetchOpts).then(function (res) {
            if (!res.ok) {
                return res.json().then(function (err) {
                    var error = new Error(err.error || 'Request failed');
                    error.status = res.status;
                    error.response = err;
                    throw error;
                });
            }
            return res.json();
        });
    };

    // Event system
    VMStore.prototype.on = function (event, callback) {
        if (!this._listeners[event]) this._listeners[event] = [];
        this._listeners[event].push(callback);
        return this;
    };

    VMStore.prototype.off = function (event, callback) {
        if (!this._listeners[event]) return this;
        if (!callback) {
            delete this._listeners[event];
        } else {
            this._listeners[event] = this._listeners[event].filter(function (cb) { return cb !== callback; });
        }
        return this;
    };

    VMStore.prototype._emit = function (event, data) {
        var cbs = this._listeners[event] || [];
        for (var i = 0; i < cbs.length; i++) {
            try { cbs[i](data); } catch (e) { console.error('VMStore event error:', e); }
        }
    };

    // --- Products API ---
    function ProductsAPI(store) { this._store = store; }

    ProductsAPI.prototype.list = function (opts) {
        var params = {};
        if (opts) {
            if (opts.page) params.page = opts.page;
            if (opts.limit) params.limit = opts.limit;
        }
        return this._store._request('products', { params: params }).then(function (res) {
            return { products: res.data || [], pagination: res.pagination || {} };
        });
    };

    ProductsAPI.prototype.get = function (id) {
        return this._store._request('product', { params: { id: id } }).then(function (res) {
            return res.data || null;
        });
    };

    ProductsAPI.prototype.search = function (query, opts) {
        var params = { q: query };
        if (opts) {
            if (opts.page) params.page = opts.page;
            if (opts.limit) params.limit = opts.limit;
        }
        return this._store._request('search', { params: params }).then(function (res) {
            return { products: res.data || [], pagination: res.pagination || {} };
        });
    };

    ProductsAPI.prototype.byCategory = function (categoryId, opts) {
        var params = { category_id: categoryId };
        if (opts) {
            if (opts.page) params.page = opts.page;
            if (opts.limit) params.limit = opts.limit;
        }
        return this._store._request('products_by_category', { params: params }).then(function (res) {
            return { products: res.data || [], pagination: res.pagination || {} };
        });
    };

    // --- Categories API ---
    function CategoriesAPI(store) { this._store = store; }

    CategoriesAPI.prototype.list = function () {
        return this._store._request('categories').then(function (res) {
            return res.data || [];
        });
    };

    // --- Cart API ---
    function CartAPI(store) { this._store = store; }

    CartAPI.prototype._ensureCart = function () {
        var self = this;
        if (this._store._cartId) {
            return Promise.resolve(this._store._cartId);
        }
        // Check localStorage for existing cart
        var saved = null;
        try { saved = localStorage.getItem('vm_cart_' + this._store.storeId); } catch (e) {}
        if (saved) {
            this._store._cartId = saved;
            return Promise.resolve(saved);
        }
        return this.create();
    };

    CartAPI.prototype.create = function () {
        var self = this;
        return this._store._request('cart_create', { method: 'POST' }).then(function (res) {
            var cartId = res.data.cart_id;
            self._store._cartId = cartId;
            try { localStorage.setItem('vm_cart_' + self._store.storeId, cartId); } catch (e) {}
            self._store._emit('cart:created', { cart_id: cartId });
            return cartId;
        });
    };

    CartAPI.prototype.get = function () {
        var self = this;
        return this._ensureCart().then(function (cartId) {
            return self._store._request('cart', { params: { cart_id: cartId } }).then(function (res) {
                return res.data || { items: [], item_count: 0, subtotal: 0 };
            });
        });
    };

    CartAPI.prototype.add = function (productId, quantity) {
        var self = this;
        return this._ensureCart().then(function (cartId) {
            return self._store._request('cart_add', {
                method: 'POST',
                body: { cart_id: cartId, product_id: productId, quantity: quantity || 1 }
            }).then(function (res) {
                var cart = res.data;
                self._store._emit('cart:updated', cart);
                self._store._emit('cart:item-added', { product_id: productId, quantity: quantity || 1, cart: cart });
                return cart;
            });
        });
    };

    CartAPI.prototype.update = function (productId, quantity) {
        var self = this;
        return this._ensureCart().then(function (cartId) {
            return self._store._request('cart_update', {
                method: 'POST',
                body: { cart_id: cartId, product_id: productId, quantity: quantity }
            }).then(function (res) {
                var cart = res.data;
                self._store._emit('cart:updated', cart);
                return cart;
            });
        });
    };

    CartAPI.prototype.remove = function (productId) {
        var self = this;
        return this._ensureCart().then(function (cartId) {
            return self._store._request('cart_remove', {
                method: 'POST',
                body: { cart_id: cartId, product_id: productId }
            }).then(function (res) {
                var cart = res.data;
                self._store._emit('cart:updated', cart);
                self._store._emit('cart:item-removed', { product_id: productId, cart: cart });
                return cart;
            });
        });
    };

    CartAPI.prototype.clear = function () {
        var self = this;
        this._store._cartId = null;
        try { localStorage.removeItem('vm_cart_' + this._store.storeId); } catch (e) {}
        self._store._emit('cart:cleared', {});
        return this.create();
    };

    // --- Checkout API ---
    function CheckoutAPI(store) { this._store = store; }

    CheckoutAPI.prototype.create = function (opts) {
        var self = this;
        var returnUrl = (opts && opts.returnUrl) || window.location.href;
        return this._store.cart._ensureCart().then(function (cartId) {
            return self._store._request('checkout_create', {
                method: 'POST',
                body: { cart_id: cartId, return_url: returnUrl }
            }).then(function (res) {
                var data = res.data;
                self._store._emit('checkout:created', data);
                return data;
            });
        });
    };

    CheckoutAPI.prototype.redirect = function (opts) {
        return this.create(opts).then(function (data) {
            if (data.checkout_url) {
                window.location.href = data.checkout_url;
            }
            return data;
        });
    };

    CheckoutAPI.prototype.complete = function (sessionId, customer) {
        var self = this;
        return this._store._request('checkout_complete', {
            method: 'POST',
            body: {
                session_id: sessionId,
                customer_name: customer.name,
                customer_email: customer.email,
                customer_phone: customer.phone,
                customer_address: customer.address
            }
        }).then(function (res) {
            self._store._emit('checkout:completed', res.data);
            // Clear cart after successful checkout
            self._store.cart.clear();
            return res.data;
        });
    };

    // --- Orders API ---
    function OrdersAPI(store) { this._store = store; }

    OrdersAPI.prototype.list = function (email) {
        return this._store._request('orders', { params: { email: email } }).then(function (res) {
            return res.data || [];
        });
    };

    OrdersAPI.prototype.create = function (order) {
        var self = this;
        return this._store._request('order', {
            method: 'POST',
            body: {
                name: order.name,
                email: order.email,
                total: order.total,
                items: order.items
            }
        }).then(function (res) {
            self._store._emit('order:created', res);
            return res;
        });
    };

    // --- Store Info API ---
    function StoreAPI(store) { this._store = store; }

    StoreAPI.prototype.info = function () {
        return this._store._request('site').then(function (res) {
            return res.data || {};
        });
    };

    // --- Discounts API ---
    function DiscountsAPI(store) { this._store = store; }

    DiscountsAPI.prototype.list = function () {
        return this._store._request('discounts').then(function (res) {
            return res.data || [];
        });
    };

    // --- UI Components ---
    function UIComponents(store) {
        this._store = store;
    }

    /**
     * Render a product card into a target element.
     * store.ui.productCard('#container', product);
     */
    UIComponents.prototype.productCard = function (target, product) {
        var el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el || !product) return;

        var currency = this._store.currency;
        var card = document.createElement('div');
        card.className = 'vm-product-card';
        card.setAttribute('data-product-id', product.id);

        card.innerHTML =
            '<div class="vm-product-image">' +
                (product.image ? '<img src="' + _escHtml(product.image) + '" alt="' + _escHtml(product.name) + '">' : '<div class="vm-product-no-image">No Image</div>') +
            '</div>' +
            '<div class="vm-product-info">' +
                '<h3 class="vm-product-name">' + _escHtml(product.name) + '</h3>' +
                (product.description ? '<p class="vm-product-desc">' + _escHtml(product.description) + '</p>' : '') +
                '<div class="vm-product-footer">' +
                    '<span class="vm-product-price">' + currency + _formatPrice(product.price) + '</span>' +
                    '<button class="vm-add-to-cart" data-product-id="' + product.id + '">Add to Cart</button>' +
                '</div>' +
            '</div>';

        var self = this;
        card.querySelector('.vm-add-to-cart').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            btn.textContent = 'Adding...';
            self._store.cart.add(product.id, 1).then(function () {
                btn.textContent = 'Added!';
                setTimeout(function () { btn.disabled = false; btn.textContent = 'Add to Cart'; }, 1500);
            }).catch(function () {
                btn.disabled = false;
                btn.textContent = 'Add to Cart';
            });
        });

        el.appendChild(card);
        return card;
    };

    /**
     * Render a full product grid into a target element.
     * store.ui.productGrid('#shop', { limit: 12 });
     */
    UIComponents.prototype.productGrid = function (target, opts) {
        var el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) return Promise.resolve();

        var self = this;
        var grid = document.createElement('div');
        grid.className = 'vm-product-grid';
        el.appendChild(grid);

        return this._store.products.list(opts).then(function (result) {
            var products = result.products;
            for (var i = 0; i < products.length; i++) {
                self.productCard(grid, products[i]);
            }
            return result;
        });
    };

    /**
     * Render a mini cart badge showing item count.
     * store.ui.cartBadge('#cart-icon');
     */
    UIComponents.prototype.cartBadge = function (target) {
        var el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) return;

        var badge = document.createElement('span');
        badge.className = 'vm-cart-badge';
        badge.textContent = '0';
        badge.style.display = 'none';
        el.style.position = 'relative';
        el.appendChild(badge);

        function update(cart) {
            var count = cart ? (cart.item_count || 0) : 0;
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }

        this._store.on('cart:updated', update);
        this._store.on('cart:cleared', function () { update(null); });

        // Initial load
        this._store.cart.get().then(update).catch(function () {});

        return badge;
    };

    /**
     * Render a full cart view with items, quantities, and checkout button.
     * store.ui.cartDrawer('#cart-container');
     */
    UIComponents.prototype.cartDrawer = function (target, opts) {
        var el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) return;

        var self = this;
        var currency = this._store.currency;

        function render(cart) {
            var items = (cart && cart.items) || [];
            var subtotal = (cart && cart.subtotal) || 0;

            if (items.length === 0) {
                el.innerHTML = '<div class="vm-cart-empty"><p>Your cart is empty</p></div>';
                return;
            }

            var html = '<div class="vm-cart-items">';
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                html +=
                    '<div class="vm-cart-item" data-product-id="' + item.product_id + '">' +
                        (item.image ? '<img src="' + _escHtml(item.image) + '" alt="" class="vm-cart-item-image">' : '') +
                        '<div class="vm-cart-item-info">' +
                            '<span class="vm-cart-item-name">' + _escHtml(item.name) + '</span>' +
                            '<span class="vm-cart-item-price">' + currency + _formatPrice(item.line_total) + '</span>' +
                        '</div>' +
                        '<div class="vm-cart-item-qty">' +
                            '<button class="vm-qty-btn vm-qty-minus" data-id="' + item.product_id + '" data-qty="' + (item.quantity - 1) + '">-</button>' +
                            '<span>' + item.quantity + '</span>' +
                            '<button class="vm-qty-btn vm-qty-plus" data-id="' + item.product_id + '" data-qty="' + (item.quantity + 1) + '">+</button>' +
                        '</div>' +
                        '<button class="vm-cart-item-remove" data-id="' + item.product_id + '">&times;</button>' +
                    '</div>';
            }
            html += '</div>';
            html += '<div class="vm-cart-footer">' +
                '<div class="vm-cart-subtotal"><span>Subtotal</span><strong>' + currency + _formatPrice(subtotal) + '</strong></div>' +
                '<button class="vm-checkout-btn">Checkout</button>' +
            '</div>';

            el.innerHTML = html;

            // Bind quantity buttons
            var qtyBtns = el.querySelectorAll('.vm-qty-btn');
            for (var j = 0; j < qtyBtns.length; j++) {
                qtyBtns[j].addEventListener('click', function () {
                    var pid = parseInt(this.getAttribute('data-id'));
                    var qty = parseInt(this.getAttribute('data-qty'));
                    if (qty <= 0) {
                        self._store.cart.remove(pid).then(render);
                    } else {
                        self._store.cart.update(pid, qty).then(render);
                    }
                });
            }

            // Bind remove buttons
            var removeBtns = el.querySelectorAll('.vm-cart-item-remove');
            for (var k = 0; k < removeBtns.length; k++) {
                removeBtns[k].addEventListener('click', function () {
                    var pid = parseInt(this.getAttribute('data-id'));
                    self._store.cart.remove(pid).then(render);
                });
            }

            // Bind checkout
            var checkoutBtn = el.querySelector('.vm-checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function () {
                    checkoutBtn.disabled = true;
                    checkoutBtn.textContent = 'Redirecting...';
                    self._store.checkout.redirect(opts).catch(function () {
                        checkoutBtn.disabled = false;
                        checkoutBtn.textContent = 'Checkout';
                    });
                });
            }
        }

        // Initial render
        this._store.cart.get().then(render).catch(function () { render(null); });

        // Re-render on cart changes
        this._store.on('cart:updated', render);
        this._store.on('cart:cleared', function () { render(null); });
    };

    /**
     * Inject default CSS styles for UI components.
     * Call once: store.ui.injectStyles();
     */
    UIComponents.prototype.injectStyles = function () {
        if (document.getElementById('vm-store-styles')) return;

        var css = document.createElement('style');
        css.id = 'vm-store-styles';
        css.textContent =
            /* Product Grid */
            '.vm-product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;padding:10px 0}' +
            '.vm-product-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);transition:transform .2s,box-shadow .2s;cursor:pointer}' +
            '.vm-product-card:hover{transform:translateY(-3px);box-shadow:0 6px 24px rgba(0,0,0,.12)}' +
            '.vm-product-image img{width:100%;height:200px;object-fit:cover;display:block}' +
            '.vm-product-no-image{width:100%;height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;font-size:14px}' +
            '.vm-product-info{padding:16px}' +
            '.vm-product-name{margin:0 0 6px;font-size:16px;font-weight:600;color:#1a1a1a}' +
            '.vm-product-desc{margin:0 0 12px;font-size:13px;color:#666;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}' +
            '.vm-product-footer{display:flex;align-items:center;justify-content:space-between}' +
            '.vm-product-price{font-size:18px;font-weight:700;color:#7a1aab}' +
            '.vm-add-to-cart{background:#7a1aab;color:#fff;border:none;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s}' +
            '.vm-add-to-cart:hover{background:#5e1485}' +
            '.vm-add-to-cart:disabled{opacity:.6;cursor:not-allowed}' +
            /* Cart Badge */
            '.vm-cart-badge{position:absolute;top:-6px;right:-6px;background:#7a1aab;color:#fff;font-size:11px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px}' +
            /* Cart Drawer */
            '.vm-cart-items{display:flex;flex-direction:column;gap:12px;margin-bottom:20px}' +
            '.vm-cart-item{display:flex;align-items:center;gap:12px;padding:12px;background:#f9f9f9;border-radius:10px}' +
            '.vm-cart-item-image{width:50px;height:50px;object-fit:cover;border-radius:8px}' +
            '.vm-cart-item-info{flex:1;display:flex;flex-direction:column;gap:2px}' +
            '.vm-cart-item-name{font-size:14px;font-weight:600;color:#1a1a1a}' +
            '.vm-cart-item-price{font-size:13px;color:#7a1aab;font-weight:600}' +
            '.vm-cart-item-qty{display:flex;align-items:center;gap:8px}' +
            '.vm-qty-btn{width:28px;height:28px;border-radius:50%;border:1px solid #ddd;background:#fff;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:all .15s}' +
            '.vm-qty-btn:hover{background:#7a1aab;color:#fff;border-color:#7a1aab}' +
            '.vm-cart-item-remove{background:none;border:none;font-size:20px;color:#999;cursor:pointer;padding:4px 8px}' +
            '.vm-cart-item-remove:hover{color:#e53e3e}' +
            '.vm-cart-footer{border-top:1px solid #eee;padding-top:16px}' +
            '.vm-cart-subtotal{display:flex;justify-content:space-between;font-size:16px;margin-bottom:14px}' +
            '.vm-cart-subtotal strong{color:#7a1aab}' +
            '.vm-checkout-btn{width:100%;padding:14px;background:#7a1aab;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:background .2s}' +
            '.vm-checkout-btn:hover{background:#5e1485}' +
            '.vm-checkout-btn:disabled{opacity:.6;cursor:not-allowed}' +
            '.vm-cart-empty{text-align:center;padding:40px 20px;color:#999;font-size:15px}';

        document.head.appendChild(css);
    };

    // --- Utilities ---
    function _escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function _formatPrice(num) {
        return parseFloat(num || 0).toFixed(2);
    }

    // --- Export ---
    global.VMStore = VMStore;

})(typeof window !== 'undefined' ? window : this);
