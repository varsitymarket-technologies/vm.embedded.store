<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e(__SITE_TITLE__)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-background: e(__DESIGN_COLOR_BACKGROUND__);
            --color-text: e(__DESIGN_COLOR_TEXT__);
            --color-primary: e(__DESIGN_COLOR_PRIMARY__); /* A modern, vibrant green */
            --color-surface: e(__DESIGN_COLOR_SURFACE__);
            --color-border: #333333;
            --font-heading: 'Anton', sans-serif;
            --font-body: 'Poppins', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--color-background);
            color: var(--color-text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4 {
            font-family: var(--font-heading);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        a {
            color: var(--color-primary);
            text-decoration: none;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 0;
        }

        /*------------------------------------*\
          #SHARED COMPONENTS
        \*------------------------------------*/
        .btn {
            display: inline-block;
            background-color: var(--color-text);
            color: var(--color-background);
            padding: 14px 28px;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn:hover {
            background-color: var(--color-primary);
            color: var(--color-background);
        }

        .btn-dark {
            background-color: var(--color-background);
            color: var(--color-text);
            border: 1px solid var(--color-text);
        }
        
        .btn-dark:hover {
            background-color: var(--color-text);
            color: var(--color-background);
        }

        /*------------------------------------*\
          #HEADER & FOOTER
        \*------------------------------------*/
        .main-header {
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background-color: var(--color-background);
            z-index: 1000;
        }

        .logo {
            font-family: var(--font-heading);
            font-size: 2rem;
            letter-spacing: 2px;
            cursor: pointer;
        }

        .main-nav a {
            color: var(--color-text);
            margin: 0 20px;
            font-weight: 600;
            text-transform: uppercase;
            position: relative;
            padding-bottom: 5px;
            cursor: pointer;
        }

        .main-nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--color-primary);
            transition: width 0.3s ease;
        }

        .main-nav a:hover::after, .main-nav a.active::after {
            width: 100%;
        }
        
        #cart-indicator {
            position: relative;
        }

        #cart-count {
            position: absolute;
            top: -8px;
            right: -12px;
            background-color: var(--color-primary);
            color: var(--color-background);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .main-footer {
            text-align: center;
            padding: 40px 20px;
            margin-top: 60px;
        }

        /*------------------------------------*\
          #VIEW MANAGEMENT
        \*------------------------------------*/
        .view {
            display: none;
            animation: fadeIn 0.6s ease-in-out;
        }

        .view.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /*------------------------------------*\
          #SHOP VIEW
        \*------------------------------------*/
        .shop-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .shop-header h1 {
            font-size: 4rem;
            margin-bottom: 10px;
        }

        .shop-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            gap: 20px;
        }

        #search-bar {
            flex-grow: 1;
            padding: 12px 20px;
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 30px;
            color: var(--color-text);
            font-size: 1rem;
        }

        #product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background-color: var(--color-surface);
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .product-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }

        .product-card-info {
            padding: 20px;
        }

        .product-card-info h3 {
            font-size: 1.2rem;
            font-family: var(--font-body);
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0;
        }

        .product-card-info p {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-primary);
            margin-top: 10px;
        }

        /*------------------------------------*\
          #PRODUCT DETAIL VIEW
        \*------------------------------------*/
        #product-detail-view .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .product-image-gallery img {
            border-radius: 16px;
        }

        .product-details h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .product-details .price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 20px;
        }

        .product-details .description {
            line-height: 1.8;
            margin-bottom: 30px;
        }

        /*------------------------------------*\
          #CHECKOUT VIEW
        \*------------------------------------*/
        #checkout-view .container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 60px;
        }

        .checkout-forms h2, .checkout-summary h2 {
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            color: var(--color-text);
            font-size: 1rem;
        }

        .checkout-summary {
            background-color: var(--color-surface);
            padding: 30px;
            border-radius: 16px;
        }

        #cart-items-container .cart-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        #cart-items-container .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .cart-item-info {
            flex-grow: 1;
        }
        
        .cart-item-info h4 {
            font-family: var(--font-body);
            text-transform: none;
            letter-spacing: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .cart-total {
            border-top: 1px solid var(--color-border);
            padding-top: 20px;
            margin-top: 20px;
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
        }

        /*------------------------------------*\
          #ACCOUNT VIEW
        \*------------------------------------*/
        #account-view .container {
            max-width: 450px;
            text-align: center;
        }
        #account-view h1 {
            font-size: 3rem;
            margin-bottom: 30px;
        }
        #account-view form {
            text-align: left;
        }
        #account-view .btn {
            width: 100%;
            margin-top: 10px;
        }

    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo" data-view="shop-view"><img style="max-width:4rem;" src="e(__SITE_LOGO__)"></div>
        <nav class="main-nav">
            <a data-view="shop-view" class="nav-link active">Shop</a>
            <a data-view="checkout-view" class="nav-link" id="cart-indicator">
                Bag
                <span id="cart-count">0</span>
            </a>
            <a data-view="account-view" class="nav-link">Account</a>
        </nav>
    </header>

    <main>
        <!-- =============================================== -->
        <!-- SHOP VIEW -->
        <!-- =============================================== -->
        <section id="shop-view" class="view active">
            <div class="container">
                <div class="shop-header">
                    <h1>e(__SHOP_INTRO__)</h1>
                    <p>e(__SHOP_DESCRIPTION__)</p>
                </div>
                <div class="shop-controls">
                    <input type="search" id="search-bar" placeholder="Search for products...">
                    <!-- Filter controls can be added here -->
                </div>
                <div id="product-grid">
                    <!-- Products will be dynamically inserted here -->
                </div>
            </div>
        </section>

        <!-- =============================================== -->
        <!-- PRODUCT DETAIL VIEW -->
        <!-- =============================================== -->
        <section id="product-detail-view" class="view">
            <!-- Product details will be dynamically inserted here -->
        </section>

        <!-- =============================================== -->
        <!-- CHECKOUT VIEW -->
        <!-- =============================================== -->
        <section id="checkout-view" class="view">
            <div class="container">
                <div class="checkout-forms">
                    <h2>Checkout</h2>
                    <form id="checkout-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <h3>Shipping Address</h3>
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <h3>Payment Details</h3>
                        <div class="form-group">
                            <label for="card-number">Card Number</label>
                            <input type="text" id="card-number" name="card-number" placeholder="•••• •••• •••• ••••" required>
                        </div>
                        <!-- More payment fields can be added here -->
                    </form>
                </div>
                <div class="checkout-summary">
                    <h2>Summary</h2>
                    <div id="cart-items-container">
                        <p>Your bag is empty.</p>
                    </div>
                    <div id="cart-total">
                        <span>Total</span>
                        <span id="total-price">e(__SYSTEM_CURRENCY__) 0.00</span>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 30px;">Place Order</button>
                </div>
            </div>
        </section>

        <!-- =============================================== -->
        <!-- ACCOUNT VIEW -->
        <!-- =============================================== -->
        <section id="account-view" class="view">
            <div class="container">
                <h1>My Account</h1>
                <form id="login-form">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" required>
                    </div>
                    <button type="submit" class="btn">Login</button>
                    <button type="button" class="btn btn-dark">Create Account</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <p>Powered By VMTECH</p>
    </footer>

    <script>

        async function load(){
    
            document.addEventListener('DOMContentLoaded', () => {
                let products = [];
                const fetchProducts = async () => {
                    try {
                        const response = await fetch('e(__SYSTEM_API__)?state=products');
                        const apiProducts = await response.json();
                        if (Array.isArray(apiProducts)) {
                            products.splice(0, products.length, ...apiProducts);
                            renderProducts(products);
                        }
                    } catch (error) {
                        console.error('Failed to fetch products:', error);
                        // Keep mock data as fallback
                    }
                };
                fetchProducts();

                // --- STATE ---
                let cart = [];

                // --- DOM ELEMENTS ---
                const main = document.querySelector('main');
                const views = document.querySelectorAll('.view');
                const navLinks = document.querySelectorAll('.nav-link');
                const logo = document.querySelector('.logo');
                const productGrid = document.getElementById('product-grid');
                const productDetailView = document.getElementById('product-detail-view');
                const searchBar = document.getElementById('search-bar');
                const cartCount = document.getElementById('cart-count');
                const cartItemsContainer = document.getElementById('cart-items-container');
                const totalPriceEl = document.getElementById('total-price');

                // --- NAVIGATION ---
                const navigateTo = (viewId) => {
                    views.forEach(view => view.classList.remove('active'));
                    document.getElementById(viewId).classList.add('active');
                    
                    navLinks.forEach(link => {
                        link.classList.toggle('active', link.dataset.view === viewId);
                    });
                    window.scrollTo(0, 0);
                };

                // --- RENDER FUNCTIONS ---
                const renderProducts = (productsToRender) => {
                    productGrid.innerHTML = '';
                    productsToRender.forEach(product => {
                        const card = document.createElement('div');
                        card.className = 'product-card';
                        card.dataset.id = product.id;
                        card.innerHTML = `
                            <img src="${product.image}" alt="${product.name}">
                            <div class="product-card-info">
                                <h3>${product.name}</h3>
                                <p>e(__SYSTEM_CURRENCY__)${product.price.toFixed(2)}</p>
                            </div>
                        `;
                        card.addEventListener('click', () => showProductDetail(product.id));
                        productGrid.appendChild(card);
                    });
                };

                const showProductDetail = (productId) => {
                    const product = products.find(p => p.id === productId);
                    productDetailView.innerHTML = `
                        <div class="container">
                            <div class="product-image-gallery">
                                <img src="${product.image}" alt="${product.name}">
                            </div>
                            <div class="product-details">
                                <h1>${product.name}</h1>
                                <p class="price">e(__SYSTEM_CURRENCY__)${product.price.toFixed(2)}</p>
                                <p class="description">${product.description}</p>
                                <button class="btn add-to-cart-btn" data-id="${product.id}">Add to Bag</button>
                            </div>
                        </div>
                    `;
                    navigateTo('product-detail-view');
                };

                const renderCart = () => {
                    updateCartCount();
                    if (cart.length === 0) {
                        cartItemsContainer.innerHTML = '<p>Your bag is empty.</p>';
                        totalPriceEl.textContent = '$0.00';
                        return;
                    }

                    cartItemsContainer.innerHTML = '';
                    let total = 0;
                    cart.forEach(item => {
                        const product = products.find(p => p.id === item.id);
                        total += product.price * item.quantity;
                        const cartItemEl = document.createElement('div');
                        cartItemEl.className = 'cart-item';
                        cartItemEl.innerHTML = `
                            <img src="${product.image}" alt="${product.name}">
                            <div class="cart-item-info">
                                <h4>${product.name}</h4>
                                <p>e(__SYSTEM_CURRENCY__)${product.price.toFixed(2)} x ${item.quantity}</p>
                            </div>
                            <span>e(__SYSTEM_CURRENCY__)${(product.price * item.quantity).toFixed(2)}</span>
                        `;
                        cartItemsContainer.appendChild(cartItemEl);
                    });

                    totalPriceEl.textContent = `$${total.toFixed(2)}`;
                };

                // --- CART LOGIC ---
                const addToCart = (productId) => {
                    const existingItem = cart.find(item => item.id === productId);
                    if (existingItem) {
                        existingItem.quantity++;
                    } else {
                        cart.push({ id: productId, quantity: 1 });
                    }
                    renderCart();
                };
                
                const updateCartCount = () => {
                    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                    cartCount.textContent = totalItems;
                };

                // --- EVENT LISTENERS ---
                document.body.addEventListener('click', (e) => {
                    // Navigation clicks
                    if (e.target.matches('[data-view]')) {
                        e.preventDefault();
                        const viewId = e.target.dataset.view;
                        if (viewId === 'checkout-view') {
                            renderCart();
                        }
                        navigateTo(viewId);
                    }
                    // Add to cart button
                    if (e.target.matches('.add-to-cart-btn')) {
                        const productId = parseInt(e.target.dataset.id);
                        addToCart(productId);
                        // Optional: show a confirmation
                        e.target.textContent = 'Added!';
                        setTimeout(() => {
                            e.target.textContent = 'Add to Bag';
                        }, 1500);
                    }
                });

                searchBar.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase();
                    const filteredProducts = products.filter(p => p.name.toLowerCase().includes(searchTerm));
                    renderProducts(filteredProducts);
                });

                // --- INITIALIZATION ---
                const init = () => {
                    renderProducts(products);
                    navigateTo('shop-view');
                };

                init();
            });

        }
    
        load();
    
    </script>
</body>
</html>
