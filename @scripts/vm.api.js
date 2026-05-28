/**
 * Varsity Market Website API Client
 * This client allows websites to interact with the Varsity Market Micro API.
 * Version: 1.0.0
 */

class VMApi {
    constructor(apiEndpoint = 'api.php') {
        this.apiEndpoint = apiEndpoint;
    }

    async _fetch(state, params = {}, method = 'GET', body = null) {
        let url = `${this.apiEndpoint}?state=${state}`;
        for (const [key, value] of Object.entries(params)) {
            url += `&${key}=${encodeURIComponent(value)}`;
        }

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (body) {
            options.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error(`VM API Error (${state}):`, error);
            throw error;
        }
    }

    // Product Endpoints
    async getProducts() {
        return this._fetch('products');
    }

    async getProduct(id) {
        return this._fetch('product', { id });
    }

    async getProductsByCategory(categoryId) {
        return this._fetch('products_by_category', { category_id: categoryId });
    }

    // Category Endpoints
    async getCategories() {
        return this._fetch('categories');
    }

    // Site Info
    async getSiteInfo() {
        return this._fetch('site');
    }

    // Order Placement
    async placeOrder(orderData) {
        /**
         * orderData example:
         * {
         *   name: "John Doe",
         *   email: "john@example.com",
         *   total: 100.00,
         *   items: [{id: 1, qty: 2}, {id: 2, qty: 1}]
         * }
         */
        return this._fetch('order', {}, 'POST', orderData);
    }
}

/**
 * Simple Shopping Cart Utility
 */
class VMCart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('vm_cart')) || [];
    }

    save() {
        localStorage.setItem('vm_cart', JSON.stringify(this.items));
        window.dispatchEvent(new CustomEvent('vm-cart-updated', { detail: this.items }));
    }

    add(product, qty = 1) {
        const existing = this.items.find(item => item.id === product.id);
        if (existing) {
            existing.qty += qty;
        } else {
            this.items.push({ ...product, qty });
        }
        this.save();
    }

    remove(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.save();
    }

    clear() {
        this.items = [];
        this.save();
    }

    getTotal() {
        return this.items.reduce((total, item) => total + (item.price * item.qty), 0);
    }

    getCount() {
        return this.items.reduce((count, item) => count + item.qty, 0);
    }
}

// Export mapping
if (typeof window !== 'undefined') {
    window.VMApi = VMApi;
    window.VMCart = new VMCart();
}
