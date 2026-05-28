/**
 * Varsity Market Universal Theme Engine
 * Provides "Shopify-like" functionality to any theme.
 * Version: 1.2.0
 */

class VMTheme {
    constructor() {
        this.api = new VMApi();
        this.cart = window.VMCart;
        this.root = document.querySelector('#app-root') || document.body;
        this.currentView = '';
        this.configLoaded = false;

        this.init();
    }

    async init() {
        // Load store config first
        await this.loadConfig();

        // Handle Hash Routing
        window.addEventListener('hashchange', () => this.handleRouting());

        // Handle Data-View Clicks
        document.addEventListener('click', (e) => {
            const viewTrigger = e.target.closest('[data-view]');
            if (viewTrigger) {
                e.preventDefault();
                const view = viewTrigger.getAttribute('data-view');
                const id = viewTrigger.getAttribute('data-id');
                window.location.hash = id ? `${view}/${id}` : view;
            }
        });

        // Initial Route
        this.handleRouting();

        // Global Cart Counter listener
        window.addEventListener('vm-cart-updated', () => this.updateCartCounters());
        this.updateCartCounters();
    }

    async loadConfig() {
        try {
            const siteInfo = await this.api.getSiteInfo();
            window.StoreConfig = {
                name: siteInfo.name || 'My Store',
                currency: siteInfo.currency || '$',
                domain: siteInfo.domain || window.location.hostname,
                apiEndpoint: this.api.apiEndpoint
            };
            this.configLoaded = true;

            // Update any existing title elements
            const titleEls = document.querySelectorAll('.js-store-name, #store-name');
            titleEls.forEach(el => el.textContent = window.StoreConfig.name);

            document.title = window.StoreConfig.name;
        } catch (error) {
            console.warn('VM Theme: Could not load store config, using defaults.', error);
            window.StoreConfig = window.StoreConfig || {
                name: 'My Store',
                currency: '$',
                domain: window.location.hostname
            };
        }
    }

    async handleRouting() {
        const hash = window.location.hash.slice(1) || 'shop';
        const [view, id] = hash.split('/');
        this.currentView = view;

        // Show generic loading or clear root
        this.root.innerHTML = '<div class="vm-loading">Loading...</div>';

        try {
            switch(view) {
                case 'shop':
                    await this.renderShop();
                    break;
                case 'product':
                    await this.renderProduct(id);
                    break;
                case 'cart':
                    this.renderCart();
                    break;
                case 'search':
                    await this.renderSearch(id || '');
                    break;
                case 'billing':
                case 'checkout':
                    this.renderCheckout();
                    break;
                default:
                    this.renderStaticView(view);
                    break;
            }
        } catch (error) {
            console.error('VM Theme: Route error', error);
            this.root.innerHTML = '<div class="vm-error">Something went wrong. Please try again.</div>';
        }
    }

    renderStaticView(viewName) {
        const templateId = `tpl-${viewName}`;
        this.renderTemplate(templateId);
    }

    renderTemplate(templateId, data = null) {
        const tpl = document.getElementById(templateId);
        if (!tpl) {
            console.warn(`Template ${templateId} not found.`);
            this.root.innerHTML = `<div class="vm-error">404: View ${this.currentView} not defined in theme.</div>`;
            return null;
        }

        const clone = tpl.content.cloneNode(true);
        this.root.innerHTML = '';
        this.root.appendChild(clone);
        return this.root;
    }

    async renderShop() {
        const products = await this.api.getProducts();
        this.renderTemplate('tpl-shop');

        const grid = this.root.querySelector('.js-grid') || this.root.querySelector('#product-grid') || this.root.querySelector('.js-product-grid');
        if (grid) {
            grid.innerHTML = '';
            products.forEach(p => {
                const card = this.createProductCard(p);
                if (card) grid.appendChild(card);
            });
        }

        // Bind search if template has search input
        const searchInput = this.root.querySelector('.js-search, #search-input');
        if (searchInput) {
            let debounce;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounce);
                debounce = setTimeout(() => {
                    const q = e.target.value.trim();
                    if (q.length >= 2) {
                        window.location.hash = `search/${encodeURIComponent(q)}`;
                    } else if (q.length === 0) {
                        window.location.hash = 'shop';
                    }
                }, 300);
            });
        }
    }

    createProductCard(product) {
        // Try to use a template for the card if it exists
        const cardTpl = document.getElementById('tpl-product-card');
        if (cardTpl) {
            const clone = cardTpl.content.cloneNode(true);
            this.bindData(clone, product);

            // Add click listener to the card link/element
            const link = clone.querySelector('a') || clone.firstElementChild;
            if (link) {
                link.setAttribute('data-view', 'product');
                link.setAttribute('data-id', product.id);
                link.href = `#product/${product.id}`;
            }
            return clone.firstElementChild;
        }

        // Default minimalist fallback card
        const currency = window.StoreConfig?.currency || '$';
        const card = document.createElement('div');
        card.className = 'product-card';
        card.setAttribute('data-view', 'product');
        card.setAttribute('data-id', product.id);
        card.innerHTML = `
            <img src="${product.image || ''}" alt="${product.name}" style="width:100%; height:200px; object-fit:cover;">
            <div class="product-card-info" style="padding:1rem;">
                <h3>${product.name}</h3>
                <p>${currency}${product.price}</p>
            </div>
        `;
        return card;
    }

    async renderSearch(query) {
        query = decodeURIComponent(query);
        const products = await this.api.searchProducts(query);

        // Try search template, fall back to shop template
        if (!this.renderTemplate('tpl-search')) {
            this.renderTemplate('tpl-shop');
        }

        const grid = this.root.querySelector('.js-grid') || this.root.querySelector('#product-grid') || this.root.querySelector('.js-product-grid');
        if (grid) {
            grid.innerHTML = '';
            if (products.length === 0) {
                grid.innerHTML = `<p class="text-center py-10 opacity-50 uppercase tracking-widest text-xs">No results for "${query}"</p>`;
            } else {
                products.forEach(p => {
                    const card = this.createProductCard(p);
                    if (card) grid.appendChild(card);
                });
            }
        }

        // Update search heading if present
        const heading = this.root.querySelector('.js-search-heading, #search-heading');
        if (heading) heading.textContent = `Results for "${query}"`;
    }

    async renderProduct(id) {
        const product = await this.api.getProduct(id);
        this.renderTemplate('tpl-product');
        this.bindData(this.root, product);

        // Bind Add to Cart
        const addBtn = this.root.querySelector('.js-add') || this.root.querySelector('.js-add-btn');
        if (addBtn) {
            addBtn.onclick = () => {
                this.cart.add(product);
                this.showToast(`${product.name} added to bag!`);
            };
        }

        // Bind Back button
        const backBtn = this.root.querySelector('.js-back');
        if (backBtn) {
            backBtn.onclick = (e) => {
                e.preventDefault();
                window.location.hash = 'shop';
            };
        }
    }

    renderCart() {
        this.renderTemplate('tpl-cart');
        const list = this.root.querySelector('.js-cart-list') || this.root.querySelector('.js-cart-container') || this.root.querySelector('.js-cart-items');
        const totalEl = this.root.querySelector('.js-total') || this.root.querySelector('.js-cart-total');
        const currency = window.StoreConfig?.currency || '$';

        if (list) {
            list.innerHTML = '';
            if (this.cart.items.length === 0) {
                list.innerHTML = '<p class="text-center py-10 opacity-50 uppercase tracking-widest text-xs">Your bag is empty</p>';
            } else {
                this.cart.items.forEach((item, index) => {
                    const itemTpl = document.getElementById('tpl-cart-item');
                    if (itemTpl) {
                        const clone = itemTpl.content.cloneNode(true);
                        this.bindData(clone, item);
                        // Qty controls
                        const qtyAdd = clone.querySelector('.js-qty-add');
                        const qtySub = clone.querySelector('.js-qty-sub');
                        const removeBtn = clone.querySelector('.js-item-remove');

                        if (qtyAdd) qtyAdd.onclick = () => { item.qty++; this.cart.save(); this.renderCart(); };
                        if (qtySub) qtySub.onclick = () => { if (item.qty > 1) { item.qty--; this.cart.save(); this.renderCart(); } };
                        if (removeBtn) removeBtn.onclick = () => { this.cart.remove(item.id); this.renderCart(); };

                        list.appendChild(clone);
                    } else {
                        // Fallback list item
                        const itemEl = document.createElement('div');
                        itemEl.style.display = 'flex';
                        itemEl.style.justifyContent = 'space-between';
                        itemEl.style.padding = '1rem 0';
                        itemEl.innerHTML = `
                            <span>${item.name} (x${item.qty})</span>
                            <span>${currency}${(item.price * item.qty).toFixed(2)}</span>
                        `;
                        const removeBtn = document.createElement('button');
                        removeBtn.textContent = '\u00d7';
                        removeBtn.onclick = () => { this.cart.remove(item.id); this.renderCart(); };
                        itemEl.appendChild(removeBtn);
                        list.appendChild(itemEl);
                    }
                });
            }
        }

        if (totalEl) {
            totalEl.textContent = `${currency}${this.cart.getTotal().toFixed(2)}`;
        }

        const checkoutBtn = this.root.querySelector('.js-checkout');
        if (checkoutBtn) {
            checkoutBtn.onclick = () => window.location.hash = 'checkout';
        }
    }

    renderCheckout() {
        this.renderTemplate('tpl-billing') || this.renderTemplate('tpl-checkout');
        const form = this.root.querySelector('.js-billing-form') || this.root.querySelector('.js-checkout-form') || this.root.querySelector('#checkout-form');
        const totalEl = this.root.querySelector('#total-price') || this.root.querySelector('.js-total');
        const currency = window.StoreConfig?.currency || '$';

        if (totalEl) {
            totalEl.textContent = `${currency}${this.cart.getTotal().toFixed(2)}`;
        }

        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const orderData = {
                    name: formData.get('name') || document.getElementById('name')?.value,
                    email: formData.get('email') || document.getElementById('email')?.value,
                    total: this.cart.getTotal(),
                    items: this.cart.items
                };

                try {
                    await this.api.placeOrder(orderData);
                    this.cart.clear();
                    if (document.getElementById('tpl-success')) {
                        window.location.hash = 'success';
                    } else {
                        this.showToast('Order placed successfully!');
                        window.location.hash = 'shop';
                    }
                } catch (error) {
                    this.showToast('Failed to place order. Please try again.', 'error');
                }
            };
        }
    }

    bindData(container, data) {
        if (!data) return;
        const currency = window.StoreConfig?.currency || '$';

        for (const [key, value] of Object.entries(data)) {
            const els = container.querySelectorAll(`.js-${key}`);
            els.forEach(el => {
                if (el.tagName === 'IMG' || el.classList.contains('js-img') || el.classList.contains('js-detail-img') || el.classList.contains('js-item-img')) {
                    if (el.tagName === 'IMG') {
                        el.src = value || '';
                    } else {
                        el.style.backgroundImage = `url(${value})`;
                        el.style.backgroundSize = 'cover';
                        el.style.backgroundPosition = 'center';
                    }
                } else if (key === 'price' || el.classList.contains('js-price')) {
                    el.textContent = `${currency}${value}`;
                } else {
                    el.textContent = value;
                }
            });
        }

        const descEls = container.querySelectorAll('.js-desc, .js-description, .js-detail-desc');
        descEls.forEach(el => el.textContent = data.description || data.desc || '');
    }

    updateCartCounters() {
        const count = this.cart.getCount();
        const els = document.querySelectorAll('.js-cart-count, #cart-count');
        els.forEach(el => el.textContent = count);
    }

    showToast(message, type = 'success') {
        // Remove any existing toast
        const existing = document.querySelector('.vm-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'vm-toast';
        const bgColor = type === 'error' ? '#e53e3e' : '#38a169';
        toast.setAttribute('style', `
            position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
            background: ${bgColor}; color: #fff; padding: 0.75rem 1.5rem;
            border-radius: 0.5rem; font-size: 0.875rem; z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3); animation: vmToastIn 0.3s ease;
        `);
        toast.textContent = message;

        // Add animation keyframes if not present
        if (!document.getElementById('vm-toast-styles')) {
            const style = document.createElement('style');
            style.id = 'vm-toast-styles';
            style.textContent = `
                @keyframes vmToastIn { from { opacity: 0; transform: translateX(-50%) translateY(1rem); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }
                @keyframes vmToastOut { from { opacity: 1; } to { opacity: 0; transform: translateX(-50%) translateY(1rem); } }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'vmToastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize on DOM Load
document.addEventListener('DOMContentLoaded', () => {
    window.VMThemeEngine = new VMTheme();
});
