<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@400;600;800&display=swap');
        .font-heading { font-family: 'Archivo Black', sans-serif; }
        body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; }
        .product-card:hover img { transform: translateY(-10px) rotate(-5deg); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">

    <header class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-200 px-8 py-4 flex justify-between items-center">
        <div onclick="nav('hero')" class="text-2xl font-heading cursor-pointer tracking-tighter italic"><img src="<?php e(__SITE_LOGO__); ?>" class="h-12 w-12 object-contain"></div>
        <nav class="hidden md:flex space-x-8 font-bold text-[13px] uppercase tracking-widest">
            <a onclick="nav('shop')" class="cursor-pointer hover:text-orange-500 transition">Collections</a>
            <a onclick="nav('about')" class="cursor-pointer hover:text-orange-500 transition">Our Tech</a>
            <a onclick="nav('contact')" class="cursor-pointer hover:text-orange-500 transition">Support</a>
            <a onclick="nav('account')" class="bg-slate-900 text-white px-4 py-2 rounded-full cursor-pointer hover:bg-orange-600">Account</a>
        </nav>
    </header>

    <main>
        <section id="hero" class="page-section min-h-screen flex items-center pt-20 px-8">
            <div class="container mx-auto grid md:grid-cols-2 items-center">
                <div>
                    <span class="text-orange-600 font-extrabold uppercase tracking-widest">New Arrival</span>
                    <h1 class="text-6xl md:text-8xl font-heading uppercase leading-none mt-4 mb-8"><?php e(__SHOP_INTRO__); ?></h1>
                    <button onclick="nav('shop')" class="bg-slate-900 text-white text-lg px-12 py-5 font-bold rounded-xl hover:bg-orange-600 transition-all shadow-xl">SHOP NOW</button>
                </div>
                <div class="relative">
                    <img src="<?php e(__SHOP_HERO_IMAGE__); ?>" class="w-full drop-shadow-[0_35px_35px_rgba(0,0,0,0.3)] rotate-[-15deg]">
                </div>
            </div>
        </section>

        <section id="shop" class="page-section hidden py-32 px-8 bg-white">
            <div class="container mx-auto">
                <div class="flex justify-between items-end mb-12">
                    <h2 class="text-4xl font-heading uppercase">The Drop</h2>
                    <p class="text-slate-400 font-semibold italic">Showing 26 Styles</p>
                </div>
                <div id="product-list" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    </div>
            </div>
        </section>

        <section id="product-detail" class="page-section hidden py-32 px-8">
            <div id="detail-content" class="container mx-auto">
                </div>
        </section>

        <section id="about" class="page-section hidden py-32 px-8 bg-slate-900 text-white">
            <div class="container mx-auto grid md:grid-cols-2 gap-20 items-center">
                <img src="e('__SITE_LOGO__')" class="rounded-3xl">
                <div>
                    <h2 class="text-4xl font-heading mb-6">UNMATCHED COMFORT</h2>
                    <p class="text-lg text-slate-400 leading-relaxed mb-8">e('about_text')</p>
                    <ul class="space-y-4 font-bold italic">
                        <li class="flex items-center text-orange-500">✓ NITRO-CELL CUSHIONING</li>
                        <li class="flex items-center text-orange-500">✓ RECYCLED MESH UPPER</li>
                        <li class="flex items-center text-orange-500">✓ ERGONOMIC TRACTION</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="contact" class="page-section hidden py-32 px-8">
            <div class="max-w-2xl mx-auto bg-white p-12 rounded-3xl shadow-2xl">
                <h2 class="text-3xl font-heading mb-8">Need Help?</h2>
                <form id="contactForm" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <input type="text" name="name" placeholder="Name" class="w-full p-4 bg-slate-100 rounded-xl">
                        <input type="email" name="email" placeholder="Email" class="w-full p-4 bg-slate-100 rounded-xl">
                    </div>
                    <textarea name="message" placeholder="How can we help?" class="w-full p-4 bg-slate-100 rounded-xl" rows="4"></textarea>
                    <button class="w-full bg-orange-600 text-white font-bold py-4 rounded-xl hover:bg-slate-900 transition">SEND MESSAGE</button>
                </form>
            </div>
        </section>

        <section id="account" class="page-section hidden py-32 px-8">
            <div class="container mx-auto">
                <h2 class="text-4xl font-heading mb-12">Dashboard</h2>
                <div class="grid md:grid-cols-3 gap-12">
                    <div class="bg-white p-8 rounded-3xl border border-slate-200">
                        <p class="text-slate-400 uppercase font-bold text-xs mb-2">Member</p>
                        <p class="text-xl font-bold">e('user_email')</p>
                    </div>
                    <div class="md:col-span-2 overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-slate-400 uppercase text-xs">
                                <tr>
                                    <th class="pb-4">Order ID</th>
                                    <th class="pb-4">Product</th>
                                    <th class="pb-4">Status</th>
                                    <th class="pb-4">Total</th>
                                </tr>
                            </thead>
                            <tbody id="order-history" class="font-bold">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const API = "api.php";

        function nav(id) {
            document.querySelectorAll('.page-section').forEach(s => s.classList.add('hidden'));
            document.getElementById(id).classList.remove('hidden');
            window.scrollTo(0,0);
            if(id === 'shop') loadProducts();
            if(id === 'account') loadOrders();
        }

        async function loadProducts() {
            const res = await fetch(`${API}?state=products`);
            const data = await res.json();
            document.getElementById('product-list').innerHTML = data.map(p => `
                <div onclick="loadDetail(${p.id})" class="product-card group cursor-pointer">
                    <div class="bg-slate-200 rounded-3xl p-8 mb-6 transition-colors group-hover:bg-orange-100">
                        <img src="${p.image}" class="w-full transition duration-500">
                    </div>
                    <h3 class="font-heading text-xl uppercase">${p.name}</h3>
                    <p class="text-orange-600 font-extrabold">${p.price} <?php e(__SYSTEM_CURRENCY__); ?></p>
                </div>
            `).join('');
        }

        async function loadDetail(id) {
            nav('product-detail');
            const res = await fetch(`${API}?state=product_description&id=${id}`);
            const p = await res.json();
            document.getElementById('detail-content').innerHTML = `
                <div class="grid md:grid-cols-2 gap-16 items-center">
                    <div class="bg-slate-200 rounded-[40px] p-12">
                        <img src="${p.image}" class="w-full rotate-[-10deg] drop-shadow-2xl">
                    </div>
                    <div>
                        <h1 class="text-5xl font-heading mb-4 uppercase">${p.name}</h1>
                        <p class="text-3xl font-bold text-orange-600 mb-8">${p.price} <?php e(__SYSTEM_CURRENCY__); ?></p>
                        <p class="text-slate-600 text-lg mb-10 leading-relaxed">${p.description}</p>
                        <button class="w-full bg-slate-900 text-white py-6 rounded-2xl font-bold text-xl hover:bg-orange-600">ADD TO CART</button>
                    </div>
                </div>
            `;
        }
    </script>
</body>
</html>