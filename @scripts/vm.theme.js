/**
 * Varsity Market Universal Theme Engine
 * Provides "Shopify-like" functionality to any theme.
 * Version: 1.0.0
 */

class VMTheme {
    constructor() {
        this.api = new VMApi();
        this.cart = window.VMCart;
        this.root = document.querySelector('#app-root') || document.body;
        this.currentView = '';
        
        this.init();
    }

    init() {
        // Handle Hash Routing
        window.addEventListener('hashchange', () => this.handleRouting());
        
        // Handle Data-View Clicks
        document.addEventListener('click', (e) => {
            const viewTrigger = e.target.closest('[data-view]');
            if (viewTrigger) {
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

    async handleRouting() {
        const hash = window.location.hash.slice(1) || 'shop';
        const [view, id] = hash.split('/');
        this.currentView = view;

        // Show generic loading or clear root
        this.root.innerHTML = '<div class="vm-loading">Loading...</div>';

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
            case 'billing':
            case 'checkout':
                this.renderCheckout();
                break;
            default:
                this.renderStaticView(view);
                break;
        }
    }

    renderStaticView(viewName) {
        const templateId = `tpl-${viewName}`;
        this.renderTemplate(templateId);
    }

    renderTemplate(templateId, data = null) {
        const tpl = document.getElementById(templateId);
        if (!tpl) {
            console.error(`Template ${templateId} not found.`);
            this.root.innerHTML = `<div class="vm-error">404: View ${this.currentView} not defined.</div>`;
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
        
        const grid = this.root.querySelector('.js-grid') || this.root.querySelector('#product-grid');
        if (grid) {
            grid.innerHTML = '';
            products.forEach(p => {
                const card = this.createProductCard(p);
                grid.appendChild(card);
            });
        }
    }

    createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.setAttribute('data-view', 'product');
        card.setAttribute('data-id', product.id);
        
        // Try to use a template for the card if it exists, otherwise use a default structure
        const cardTpl = document.getElementById('tpl-product-card');
        if (cardTpl) {
            const clone = cardTpl.content.cloneNode(true);
            this.bindData(clone, product);
            return clone.firstElementChild;
        }

        // Default minimalist fallback card
        card.innerHTML = `
            <img src="${product.image || ''}" alt="${product.name}">
            <div class="product-card-info">
                <h3>${product.name}</h3>
                <p>${window.StoreConfig?.currency || '$'}${product.price}</p>
            </div>
        `;
        return card;
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
                alert(`${product.name} added to bag!`);
            };
        }

        // Bind Back button
        const backBtn = this.root.querySelector('.js-back');
        if (backBtn) {
            backBtn.onclick = () => window.history.back();
        }
    }

    renderCart() {
        this.renderTemplate('tpl-cart');
        const list = this.root.querySelector('.js-cart-list');
        const totalEl = this.root.querySelector('.js-total');
        
        if (list) {
            list.innerHTML = '';
            this.cart.items.forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = 'cart-item';
                itemEl.innerHTML = `
                    <img src="${item.image || ''}" width="50">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p>${window.StoreConfig?.currency || '$'}${item.price} x ${item.qty}</p>
                    </div>
                    <button onclick="window.VMCart.remove(${item.id}); window.location.reload();">Remove</button>
                `;
                list.appendChild(itemEl);
            });
        }

        if (totalEl) {
            totalEl.textContent = `${window.StoreConfig?.currency || '$'}${this.cart.getTotal().toFixed(2)}`;
        }

        const checkoutBtn = this.root.querySelector('.js-checkout');
        if (checkoutBtn) {
            checkoutBtn.onclick = () => window.location.hash = 'checkout';
        }
    }

    renderCheckout() {
        this.renderTemplate('tpl-billing') || this.renderTemplate('tpl-checkout');
        const form = this.root.querySelector('.js-billing-form') || this.root.querySelector('#checkout-form');
        const totalEl = this.root.querySelector('#total-price') || this.root.querySelector('.js-total');

        if (totalEl) {
            totalEl.textContent = `${window.StoreConfig?.currency || '$'}${this.cart.getTotal().toFixed(2)}`;
        }

        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const orderData = {
                    name: formData.get('name'),
                    email: formData.get('email'),
                    total: this.cart.getTotal(),
                    items: this.cart.items
                };

                try {
                    await this.api.placeOrder(orderData);
                    this.cart.clear();
                    alert('Order placed successfully!');
                    window.location.hash = 'shop';
                } catch (error) {
                    alert('Failed to place order. Please try again.');
                }
            };
        }
    }

    bindData(container, data) {
        if (!data) return;
        
        // Automatically find elements with js-[key] classes and populate them
        for (const [key, value] of Object.entries(data)) {
            // Text values
            const els = container.querySelectorAll(`.js-${key}`);
            els.forEach(el => {
                if (el.tagName === 'IMG') {
                    el.src = value;
                } else if (key === 'price') {
                    el.textContent = `${window.StoreConfig?.currency || '$'}${value}`;
                } else {
                    el.textContent = value;
                }
            });
        }
        
        // Special case for descriptions
        const descEls = container.querySelectorAll('.js-desc, .js-description');
        descEls.forEach(el => el.textContent = data.description || data.desc || '');
    }

    updateCartCounters() {
        const count = this.cart.getCount();
        const els = document.querySelectorAll('.js-cart-count, #cart-count');
        els.forEach(el => el.textContent = count);
    }
}

// Initialize on DOM Load
document.addEventListener('DOMContentLoaded', () => {
    window.VMThemeEngine = new VMTheme();
});
