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
                grid.appendChild(card);
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
        const card = document.createElement('div');
        card.className = 'product-card';
        card.setAttribute('data-view', 'product');
        card.setAttribute('data-id', product.id);
        card.innerHTML = `
            <img src="${product.image || ''}" alt="${product.name}" style="width:100%; height:200px; object-fit:cover;">
            <div class="product-card-info" style="padding:1rem;">
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
                if (typeof this.showToast === 'function') {
                    this.showToast(`${product.name} added to bag!`);
                } else {
                    alert(`${product.name} added to bag!`);
                }
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
                            <span>${window.StoreConfig?.currency || '$'}${item.price}</span>
                            <button onclick="window.VMCart.remove(${item.id}); window.location.hash='cart'; location.reload();">×</button>
                        `;
                        list.appendChild(itemEl);
                    }
                });
            }
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
        const form = this.root.querySelector('.js-billing-form') || this.root.querySelector('.js-checkout-form') || this.root.querySelector('#checkout-form');
        const totalEl = this.root.querySelector('#total-price') || this.root.querySelector('.js-total');

        if (totalEl) {
            totalEl.textContent = `${window.StoreConfig?.currency || '$'}${this.cart.getTotal().toFixed(2)}`;
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
                        alert('Order placed successfully!');
                        window.location.hash = 'shop';
                    }
                } catch (error) {
                    alert('Failed to place order. Please try again.');
                }
            };
        }
    }

    bindData(container, data) {
        if (!data) return;
        
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
                    el.textContent = `${window.StoreConfig?.currency || '$'}${value}`;
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
}

// Initialize on DOM Load
document.addEventListener('DOMContentLoaded', () => {
    window.VMThemeEngine = new VMTheme();
});
