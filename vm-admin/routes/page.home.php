<?php
$db = initiate_web_database();
$currency_symbol = defined('__CURRENCY_SIGN__') ? __CURRENCY_SIGN__ : '$';

// Fetch dashboard stats safely
$revenue_result = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'");
$total_revenue = $revenue_result[0]['total'] ?? 0;

$orders_result = $db->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $orders_result[0]['total'] ?? 0;

$products_result = $db->query("SELECT COUNT(*) as total FROM products");
$total_products = $products_result[0]['total'] ?? 0;

$users_result = $db->query("SELECT COUNT(*) as total FROM page_views");
$total_users = $users_result[0]['total'] ?? 0;

// Store success checklist data
$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$has_products = $total_products > 0;
$has_domain = !empty(__DOMAIN__);
$has_theme = !empty(website_data('theme'));
$has_payment = file_exists(dirname(dirname(dirname(__FILE__))) . "/sites/" . (__DOMAIN__ ?? '') . "/payment.config.enc");
$has_analytics = file_exists(dirname(dirname(dirname(__FILE__))) . "/sites/" . (__DOMAIN__ ?? '') . "/analytics.data");
$has_orders = $total_orders > 0;

$checklist = [
    ['done' => $has_domain, 'label' => 'Set up your store domain', 'link' => $admin_base . 'settings?tab=domain', 'icon' => 'bi-globe'],
    ['done' => $has_products, 'label' => 'Add your first product', 'link' => $admin_base . 'products', 'icon' => 'bi-box-seam'],
    ['done' => $has_theme, 'label' => 'Choose a theme', 'link' => $admin_base . 'theme', 'icon' => 'bi-palette'],
    ['done' => $has_payment, 'label' => 'Configure payment methods', 'link' => $admin_base . 'settings?tab=payment', 'icon' => 'bi-credit-card'],
    ['done' => $has_analytics, 'label' => 'Install analytics tracking', 'link' => $admin_base . 'analytics', 'icon' => 'bi-graph-up'],
    ['done' => $has_orders, 'label' => 'Get your first order', 'link' => $admin_base . 'orders', 'icon' => 'bi-bag-check'],
];
$completed_steps = count(array_filter($checklist, fn($s) => $s['done']));
$total_steps = count($checklist);
$progress_pct = round(($completed_steps / $total_steps) * 100);
?>
<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-4 md:p-8">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white">Dashboard Overview</h2>
                <p class="text-gray-400 text-sm">Real-time insights for your business performance.</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="flex items-center gap-2 px-4 py-2 bg-gray-800 border border-white/10 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 transition">
                    <i class="bi bi-download"></i> Export Report
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4 mb-8">
            
            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Total Revenue</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo $currency_symbol . number_format($total_revenue, 2); ?>
                        </p>
                        <span class="text-green-500 text-xs font-medium flex items-center gap-1 mt-2">
                            <i class="bi bi-arrow-up-short"></i> +12.5% <span class="text-gray-500">vs last month</span>
                        </span>
                    </div>
                    <div class="p-3 bg-green-500/10 rounded-xl text-green-500">
                        <i class="bi bi-currency-dollar text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Total Orders</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo number_format($total_orders); ?>
                        </p>
                        <span class="text-blue-500 text-xs font-medium flex items-center gap-1 mt-2">
                            <i class="bi bi-bag-check"></i> 48 <span class="text-gray-500">new today</span>
                        </span>
                    </div>
                    <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
                        <i class="bi bi-cart3 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Products</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo number_format($total_products); ?>
                        </p>
                        <span class="text-purple-500 text-xs font-medium flex items-center gap-1 mt-2">
                             Active Inventory
                        </span>
                    </div>
                    <div class="p-3 bg-purple-500/10 rounded-xl text-purple-500">
                        <i class="bi bi-box-seam text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Customers</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo number_format($total_users); ?>
                        </p>
                        <span class="text-orange-500 text-xs font-medium flex items-center gap-1 mt-2">
                            <i class="bi bi-person-plus"></i> +3% <span class="text-gray-500">growth</span>
                        </span>
                    </div>
                    <div class="p-3 bg-orange-500/10 rounded-xl text-orange-500">
                        <i class="bi bi-people text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- What's New & Tips Carousel -->
        <div class="mb-8 relative overflow-hidden rounded-2xl border border-white/5" id="carousel-wrapper">
            <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track">
                <!-- Slide 1: Welcome / Getting Started -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/20">
                            <i class="bi bi-rocket-takeoff text-4xl text-purple-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-purple-400 text-xs font-bold uppercase tracking-widest">Getting Started</span>
                            <h3 class="text-xl font-bold text-white mt-1">Launch Your Store in 5 Minutes</h3>
                            <p class="text-gray-400 text-sm mt-2 max-w-lg">Add products, pick a theme, connect payments, and publish. Your customers are waiting — let's get you live.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>products" class="shrink-0 bg-purple-600 hover:bg-purple-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors">
                            Add Products <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 2: New Feature - Analytics -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #1a1a1a 0%, #1c2a1c 50%, #0d2818 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                            <i class="bi bi-graph-up-arrow text-4xl text-emerald-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-emerald-400 text-xs font-bold uppercase tracking-widest">New Feature</span>
                            <h3 class="text-xl font-bold text-white mt-1">Real-Time Analytics Dashboard</h3>
                            <p class="text-gray-400 text-sm mt-2 max-w-lg">Track page views, unique visitors, referrers, and device breakdowns — all without slowing your store. Just add the lightweight tracking tag.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>analytics" class="shrink-0 bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors">
                            View Analytics <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 3: Tip - SEO & Marketing -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #1a1a1a 0%, #2a1c2a 50%, #1a0d28 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-pink-500/10 border border-pink-500/20">
                            <i class="bi bi-lightbulb text-4xl text-pink-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-pink-400 text-xs font-bold uppercase tracking-widest">Pro Tip</span>
                            <h3 class="text-xl font-bold text-white mt-1">Write Product Descriptions That Sell</h3>
                            <p class="text-gray-400 text-sm mt-2 max-w-lg">Focus on benefits over features. "Keeps drinks cold for 24 hours" sells better than "double-wall insulation". Use short paragraphs and bullet points.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>products" class="shrink-0 bg-pink-600 hover:bg-pink-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors">
                            Edit Products <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 4: New Feature - API & SDK -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #1a1a1a 0%, #1c1c2a 50%, #0d0d28 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/20">
                            <i class="bi bi-code-slash text-4xl text-blue-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-blue-400 text-xs font-bold uppercase tracking-widest">New Feature</span>
                            <h3 class="text-xl font-bold text-white mt-1">Public Store API & JavaScript SDK</h3>
                            <p class="text-gray-400 text-sm mt-2 max-w-lg">Embed your products anywhere with our drop-in SDK, or build custom integrations with the REST API. Full cart and checkout support included.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>settings?tab=dev" class="shrink-0 bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors">
                            Developer Settings <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 5: Tip - Boost Conversions -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #1a1a1a 0%, #2a2a1c 50%, #28210d 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20">
                            <i class="bi bi-trophy text-4xl text-amber-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-amber-400 text-xs font-bold uppercase tracking-widest">Growth Tip</span>
                            <h3 class="text-xl font-bold text-white mt-1">3 Ways to Boost Your Conversion Rate</h3>
                            <p class="text-gray-400 text-sm mt-2 max-w-lg">1) Add high-quality product photos. 2) Offer free delivery over a threshold. 3) Create urgency with flash sales and limited discounts.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>discounts" class="shrink-0 bg-amber-600 hover:bg-amber-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors">
                            Create Discount <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Carousel Controls -->
            <button onclick="carouselPrev()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors backdrop-blur-sm">
                <i class="bi bi-chevron-left text-sm"></i>
            </button>
            <button onclick="carouselNext()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors backdrop-blur-sm">
                <i class="bi bi-chevron-right text-sm"></i>
            </button>

            <!-- Dots -->
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2" id="carousel-dots">
                <button onclick="carouselGo(0)" class="w-2 h-2 rounded-full bg-white transition-all"></button>
                <button onclick="carouselGo(1)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(2)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(3)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(4)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
            </div>
        </div>

        <script>
        (function() {
            var current = 0;
            var total = 5;
            var track = document.getElementById('carousel-track');
            var dots = document.getElementById('carousel-dots').children;
            var autoplay;

            function update() {
                track.style.transform = 'translateX(-' + (current * 100) + '%)';
                for (var i = 0; i < dots.length; i++) {
                    dots[i].className = i === current
                        ? 'w-2 h-2 rounded-full bg-white transition-all scale-125'
                        : 'w-2 h-2 rounded-full bg-white/30 transition-all';
                }
            }

            window.carouselNext = function() {
                current = (current + 1) % total;
                update();
                resetAutoplay();
            };
            window.carouselPrev = function() {
                current = (current - 1 + total) % total;
                update();
                resetAutoplay();
            };
            window.carouselGo = function(i) {
                current = i;
                update();
                resetAutoplay();
            };

            function resetAutoplay() {
                clearInterval(autoplay);
                autoplay = setInterval(function() { window.carouselNext(); }, 8000);
            }

            resetAutoplay();
        })();
        </script>

        <!-- Store Success Guide & Recent Transactions -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

            <!-- Store Success Roadmap -->
            <div class="xl:col-span-1 rounded-2xl bg-gray-800 border border-white/5 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Store Roadmap</h3>
                    <span class="text-xs font-bold text-purple-400"><?php echo $completed_steps; ?>/<?php echo $total_steps; ?></span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-2 bg-gray-700 rounded-full mb-5 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 <?php echo $progress_pct === 100 ? 'bg-emerald-500' : 'bg-purple-500'; ?>"
                         style="width: <?php echo $progress_pct; ?>%"></div>
                </div>

                <?php if ($progress_pct === 100): ?>
                <div class="flex items-center gap-2 mb-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3">
                    <i class="bi bi-check-circle-fill text-emerald-400"></i>
                    <span class="text-emerald-400 text-sm font-medium">All set! Your store is fully configured.</span>
                </div>
                <?php endif; ?>

                <div class="space-y-2">
                    <?php foreach ($checklist as $step): ?>
                    <a href="<?php echo $step['link']; ?>"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors <?php echo $step['done'] ? 'opacity-60' : 'hover:bg-gray-700'; ?>">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 <?php echo $step['done'] ? 'bg-emerald-500/20' : 'bg-gray-700'; ?>">
                            <?php if ($step['done']): ?>
                                <i class="bi bi-check-lg text-emerald-400 text-sm"></i>
                            <?php else: ?>
                                <i class="bi <?php echo $step['icon']; ?> text-gray-400 text-xs"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm <?php echo $step['done'] ? 'text-gray-500 line-through' : 'text-gray-300'; ?>"><?php echo $step['label']; ?></span>
                        <?php if (!$step['done']): ?>
                        <i class="bi bi-chevron-right text-gray-600 text-xs ml-auto"></i>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($progress_pct < 100): ?>
                <div class="mt-5 rounded-xl bg-purple-500/5 border border-purple-500/10 p-4">
                    <p class="text-xs text-gray-400"><i class="bi bi-lightbulb text-purple-400 mr-1"></i> <span class="text-purple-400 font-medium">Tip:</span> Stores that complete all steps within the first week see 3x more traffic.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Transactions (2/3 width) -->
            <div class="xl:col-span-2 rounded-2xl bg-gray-800 border border-white/5 shadow-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">Recent Transactions</h3>
                    <a href="<?php echo $admin_base; ?>orders" class="text-sm text-purple-400 hover:text-purple-300 font-medium transition">View All Orders</a>
                </div>
                <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-900/50 text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Order ID</th>
                            <th class="px-6 py-4 font-semibold">Customer</th>
                            <th class="px-6 py-4 font-semibold">Amount</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
                        // Fetching specific recent orders for the table
                        $recent_orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                        foreach ($recent_orders as $order):
                            $status_class = match($order['status']) {
                                'completed' => 'bg-green-500/10 text-green-500',
                                'pending'   => 'bg-yellow-500/10 text-yellow-500',
                                'cancelled' => 'bg-red-500/10 text-red-500',
                                default     => 'bg-gray-500/10 text-gray-400',
                            };
                        ?>
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="px-6 py-4 font-mono text-gray-400 group-hover:text-purple-400 transition">
                                #<?php echo $order['id']; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold text-white">
                                        <?php echo strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <span class="text-gray-200 font-medium"><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown'); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-white font-semibold">
                                <?php echo $currency_symbol . number_format($order['total_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide <?php echo $status_class; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="p-2 hover:bg-gray-700 rounded-lg text-gray-400 hover:text-white transition">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </main>
</div>
