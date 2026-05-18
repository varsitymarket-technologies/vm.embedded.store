<?php
#   TITLE   : Dashboard Home Page
#   VERSION : 2.0.0

$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$store_name = website_data('name') ?: 'My Store';
$store_domain = __DOMAIN__ ?? '';
$store_theme = __WEBSITE_THEME__ ?? 'default';
$store_url = __WEBSITE_URL__ ?? '#';
?>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body { background: #09090b !important; }
    .dash-main { font-family: 'Inter', -apple-system, sans-serif; }
</style>

<?php @include_once "header.php"; ?>

<div class="grid-layout">

    <main class="dash-main overflow-x-hidden bg-[#09090b] p-6 md:p-8 max-w-6xl mx-auto">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">Welcome back <?php echo __USERNAME__; ?></h1>
                <p class="text-zinc-400 text-sm mt-1">Here's what's happening with <span class="text-violet-400 font-medium"><?php echo htmlspecialchars($store_name); ?></span></p>
            </div>
            <a href="<?php echo $admin_base; ?>" class="bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-lg shadow-violet-500/20">
                Open Admin Panel
            </a>
        </div>

        <!-- Quick Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <!-- Store Status -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-zinc-400 text-xs font-medium uppercase tracking-wider">Store Status</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>Live
                    </span>
                </div>
                <p class="text-white font-semibold text-lg"><?php echo htmlspecialchars($store_domain); ?></p>
                <a href="<?php echo $store_url; ?>" target="_blank" class="text-violet-400 hover:text-violet-300 text-xs mt-2 inline-flex items-center gap-1 transition-colors">
                    Visit store <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                </a>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-emerald-500/5"></div>
            </div>

            <!-- Theme -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-zinc-400 text-xs font-medium uppercase tracking-wider">Active Theme</span>
                    <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                        <i class="bi bi-palette text-violet-400"></i>
                    </span>
                </div>
                <p class="text-white font-semibold text-lg capitalize"><?php echo htmlspecialchars($store_theme); ?></p>
                <a href="<?php echo $admin_base; ?>theme" class="text-violet-400 hover:text-violet-300 text-xs mt-2 inline-flex items-center gap-1 transition-colors">
                    Change theme <i class="bi bi-arrow-right text-[10px]"></i>
                </a>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-violet-500/5"></div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-zinc-400 text-xs font-medium uppercase tracking-wider">Quick Actions</span>
                    <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                        <i class="bi bi-grid text-sky-400"></i>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="<?php echo $admin_base; ?>products" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium transition-colors">
                        <i class="bi bi-box-seam text-violet-400"></i> Products
                    </a>
                    <a href="<?php echo $admin_base; ?>orders" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium transition-colors">
                        <i class="bi bi-bag text-emerald-400"></i> Orders
                    </a>
                    <a href="<?php echo $admin_base; ?>analytics" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium transition-colors">
                        <i class="bi bi-graph-up text-sky-400"></i> Analytics
                    </a>
                    <a href="<?php echo $admin_base; ?>settings" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium transition-colors">
                        <i class="bi bi-gear text-amber-400"></i> Settings
                    </a>
                </div>
            </div>
        </div>

        <!-- Feature Carousel -->
        <div class="mb-8 relative overflow-hidden rounded-xl border border-zinc-800" id="carousel-wrapper">
            <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track">
                <!-- Slide 1: Welcome -->
                <div class="w-full shrink-0 p-8 md:p-10 relative" style="background: linear-gradient(135deg, #09090b 0%, #1a103d 50%, #0d0628 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-xl bg-violet-500/10 border border-violet-500/20">
                            <i class="bi bi-rocket-takeoff text-4xl text-violet-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-violet-400 text-xs font-bold uppercase tracking-widest">Welcome</span>
                            <h3 class="text-xl font-bold text-white mt-1">Welcome to <?php echo htmlspecialchars($store_name); ?></h3>
                            <p class="text-zinc-400 text-sm mt-2 max-w-lg">Add products, pick a theme, connect payments, and publish. Your customers are waiting.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>products" class="shrink-0 bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                            Get Started <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 2: Analytics -->
                <div class="w-full shrink-0 p-8 md:p-10 relative" style="background: linear-gradient(135deg, #09090b 0%, #0d2818 50%, #061a0e 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                            <i class="bi bi-graph-up-arrow text-4xl text-emerald-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-emerald-400 text-xs font-bold uppercase tracking-widest">New Feature</span>
                            <h3 class="text-xl font-bold text-white mt-1">Real-Time Analytics Dashboard</h3>
                            <p class="text-zinc-400 text-sm mt-2 max-w-lg">Track page views, visitors, referrers, and device breakdowns — all without slowing your store.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>analytics" class="shrink-0 bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                            View Analytics <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 3: API -->
                <div class="w-full shrink-0 p-8 md:p-10 relative" style="background: linear-gradient(135deg, #09090b 0%, #0d1528 50%, #060d1a 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/20">
                            <i class="bi bi-code-slash text-4xl text-sky-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-sky-400 text-xs font-bold uppercase tracking-widest">Developer</span>
                            <h3 class="text-xl font-bold text-white mt-1">Public Store API & JavaScript SDK</h3>
                            <p class="text-zinc-400 text-sm mt-2 max-w-lg">Embed your products anywhere with our drop-in SDK, or build custom integrations with the REST API.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>settings?tab=dev" class="shrink-0 bg-sky-600 hover:bg-sky-500 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                            Developer Settings <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 4: Growth -->
                <div class="w-full shrink-0 p-8 md:p-10 relative" style="background: linear-gradient(135deg, #09090b 0%, #28210d 50%, #1a1506 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
                            <i class="bi bi-trophy text-4xl text-amber-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-amber-400 text-xs font-bold uppercase tracking-widest">Growth Tip</span>
                            <h3 class="text-xl font-bold text-white mt-1">Boost Conversions With Urgency</h3>
                            <p class="text-zinc-400 text-sm mt-2 max-w-lg">Flash sales and limited-time discounts create urgency. Pair with free delivery thresholds for maximum impact.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>discounts" class="shrink-0 bg-amber-600 hover:bg-amber-500 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                            Create Discount <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <button onclick="carouselPrev()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-9 h-9 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button onclick="carouselNext()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-9 h-9 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-right"></i>
            </button>
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2" id="carousel-dots">
                <button onclick="carouselGo(0)" class="w-2 h-2 rounded-full bg-white transition-all"></button>
                <button onclick="carouselGo(1)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(2)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(3)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
            </div>
        </div>

        <script>
        (function() {
            var current = 0, total = 4;
            var track = document.getElementById('carousel-track');
            var dots = document.getElementById('carousel-dots').children;
            var autoplay;
            function update() {
                track.style.transform = 'translateX(-' + (current * 100) + '%)';
                for (var i = 0; i < dots.length; i++) {
                    dots[i].className = i === current ? 'w-2 h-2 rounded-full bg-white transition-all scale-125' : 'w-2 h-2 rounded-full bg-white/30 transition-all';
                }
            }
            window.carouselNext = function() { current = (current + 1) % total; update(); resetAuto(); };
            window.carouselPrev = function() { current = (current - 1 + total) % total; update(); resetAuto(); };
            window.carouselGo = function(i) { current = i; update(); resetAuto(); };
            function resetAuto() { clearInterval(autoplay); autoplay = setInterval(window.carouselNext, 8000); }
            resetAuto();
            var startX = 0;
            var wrapper = document.getElementById('carousel-wrapper');
            wrapper.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; });
            wrapper.addEventListener('touchend', function(e) {
                var diff = startX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) { diff > 0 ? window.carouselNext() : window.carouselPrev(); }
            });
        })();
        </script>

        <!-- Live Preview -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-red-500/60"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/60"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/60"></span>
                    </div>
                    <span class="text-zinc-500 text-xs font-mono"><?php echo htmlspecialchars($store_domain); ?></span>
                </div>
                
            </div>
            <iframe src="<?php echo $store_url; ?>" class="w-full border-none" style="height: 65vh;" frameborder="0"></iframe>
        </div>

    </main>

</div>
