<?php
$db = initiate_web_database();
$currency_symbol = defined('__CURRENCY_SIGN__') ? __CURRENCY_SIGN__ : 'R';

// Fetch dashboard stats
$revenue_result = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'");
$total_revenue = $revenue_result[0]['total'] ?? 0;

$all_revenue_result = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders");
$gross_revenue = $all_revenue_result[0]['total'] ?? 0;

$orders_result = $db->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $orders_result[0]['total'] ?? 0;

$pending_result = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $pending_result[0]['total'] ?? 0;

$products_result = $db->query("SELECT COUNT(*) as total FROM products");
$total_products = $products_result[0]['total'] ?? 0;

$categories_result = $db->query("SELECT COUNT(*) as total FROM categories");
$total_categories = $categories_result[0]['total'] ?? 0;

$low_stock_result = $db->query("SELECT COUNT(*) as total FROM products WHERE stock > 0 AND stock <= 5");
$low_stock = $low_stock_result[0]['total'] ?? 0;

$out_of_stock_result = $db->query("SELECT COUNT(*) as total FROM products WHERE stock = 0");
$out_of_stock = $out_of_stock_result[0]['total'] ?? 0;

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

// Recent orders
$recent_orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
?>
<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#09090b] p-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white">Overview</h2>
                <p class="text-zinc-400 text-sm mt-1">Your store at a glance</p>
            </div>
            <a href="<?php echo $admin_base; ?>orders" class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                <i class="bi bi-receipt"></i> View Orders
            </a>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Revenue</span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i class="bi bi-cash-stack text-emerald-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $currency_symbol . number_format($total_revenue, 2); ?></p>
                <p class="text-zinc-500 text-xs mt-1">Completed orders</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Orders</span>
                    <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                        <i class="bi bi-bag text-violet-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $total_orders; ?></p>
                <?php if ($pending_orders > 0): ?>
                <p class="text-amber-400 text-xs mt-1"><?php echo $pending_orders; ?> pending</p>
                <?php endif; ?>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Products</span>
                    <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                        <i class="bi bi-box-seam text-sky-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $total_products; ?></p>
                <p class="text-zinc-500 text-xs mt-1"><?php echo $total_categories; ?> categories</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Inventory</span>
                    <span class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                        <i class="bi bi-exclamation-triangle text-amber-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $low_stock; ?></p>
                <p class="text-zinc-500 text-xs mt-1">Low stock<?php if ($out_of_stock > 0): ?> <span class="text-red-400">&middot; <?php echo $out_of_stock; ?> out</span><?php endif; ?></p>
            </div>
        </div>

        <!-- Carousel -->
        <div class="mb-6 relative overflow-hidden rounded-xl border border-zinc-800" id="carousel-wrapper">
            <div class="flex transition-transform duration-500 ease-in-out" id="carousel-track">
                <!-- Slide 1: Getting Started -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #09090b 0%, #1a103d 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-3 rounded-xl bg-violet-500/10 border border-violet-500/20">
                            <i class="bi bi-rocket-takeoff text-3xl text-violet-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-violet-400 text-xs font-bold uppercase tracking-widest">Getting Started</span>
                            <h3 class="text-lg font-bold text-white mt-1">Launch Your Store in 5 Minutes</h3>
                            <p class="text-zinc-400 text-sm mt-1 max-w-lg">Add products, pick a theme, connect payments, and publish.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>products" class="shrink-0 bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                            Add Products <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 2: Analytics -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #09090b 0%, #0d2818 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                            <i class="bi bi-graph-up-arrow text-3xl text-emerald-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-emerald-400 text-xs font-bold uppercase tracking-widest">New Feature</span>
                            <h3 class="text-lg font-bold text-white mt-1">Real-Time Analytics Dashboard</h3>
                            <p class="text-zinc-400 text-sm mt-1 max-w-lg">Track page views, visitors, referrers and device breakdowns.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>analytics" class="shrink-0 bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                            View Analytics <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Slide 3: API & SDK -->
                <div class="w-full shrink-0 p-6 md:p-8" style="background: linear-gradient(135deg, #09090b 0%, #0d1528 100%);">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <div class="p-3 rounded-xl bg-sky-500/10 border border-sky-500/20">
                            <i class="bi bi-code-slash text-3xl text-sky-400"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-sky-400 text-xs font-bold uppercase tracking-widest">Developer</span>
                            <h3 class="text-lg font-bold text-white mt-1">Public Store API & JavaScript SDK</h3>
                            <p class="text-zinc-400 text-sm mt-1 max-w-lg">Embed your products anywhere with our drop-in SDK or REST API.</p>
                        </div>
                        <a href="<?php echo $admin_base; ?>settings?tab=dev" class="shrink-0 bg-sky-600 hover:bg-sky-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                            Developer Settings <i class="bi bi-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Carousel Controls -->
            <button onclick="carouselPrev()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-left text-sm"></i>
            </button>
            <button onclick="carouselNext()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/80 text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-right text-sm"></i>
            </button>
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2" id="carousel-dots">
                <button onclick="carouselGo(0)" class="w-2 h-2 rounded-full bg-white transition-all"></button>
                <button onclick="carouselGo(1)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
                <button onclick="carouselGo(2)" class="w-2 h-2 rounded-full bg-white/30 transition-all"></button>
            </div>
        </div>

        <script>
        (function() {
            var current = 0, total = 3;
            var track = document.getElementById('carousel-track');
            var dots = document.getElementById('carousel-dots').children;
            var autoplay;
            function update() {
                track.style.transform = 'translateX(-' + (current * 100) + '%)';
                for (var i = 0; i < dots.length; i++) {
                    dots[i].className = i === current ? 'w-2 h-2 rounded-full bg-white transition-all scale-125' : 'w-2 h-2 rounded-full bg-white/30 transition-all';
                }
            }
            window.carouselNext = function() { current = (current + 1) % total; update(); resetAutoplay(); };
            window.carouselPrev = function() { current = (current - 1 + total) % total; update(); resetAutoplay(); };
            window.carouselGo = function(i) { current = i; update(); resetAutoplay(); };
            function resetAutoplay() { clearInterval(autoplay); autoplay = setInterval(function() { window.carouselNext(); }, 8000); }
            resetAutoplay();
        })();
        </script>

        <!-- Roadmap + Recent Orders -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Store Roadmap -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-white font-semibold text-sm">Store Roadmap</h3>
                    <span class="text-xs font-bold text-violet-400"><?php echo $completed_steps; ?>/<?php echo $total_steps; ?></span>
                </div>
                <div class="w-full h-1.5 bg-zinc-800 rounded-full mb-5 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 <?php echo $progress_pct === 100 ? 'bg-emerald-500' : 'bg-violet-500'; ?>"
                         style="width: <?php echo $progress_pct; ?>%"></div>
                </div>

                <?php if ($progress_pct === 100): ?>
                <div class="flex items-center gap-2 mb-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-3 py-2">
                    <i class="bi bi-check-circle-fill text-emerald-400 text-sm"></i>
                    <span class="text-emerald-400 text-xs font-medium">All set! Your store is fully configured.</span>
                </div>
                <?php endif; ?>

                <div class="space-y-1">
                    <?php foreach ($checklist as $step): ?>
                    <a href="<?php echo $step['link']; ?>"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors <?php echo $step['done'] ? 'opacity-50' : 'hover:bg-zinc-800'; ?>">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 <?php echo $step['done'] ? 'bg-emerald-500/20' : 'bg-zinc-800'; ?>">
                            <?php if ($step['done']): ?>
                                <i class="bi bi-check-lg text-emerald-400 text-sm"></i>
                            <?php else: ?>
                                <i class="bi <?php echo $step['icon']; ?> text-zinc-500 text-xs"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm <?php echo $step['done'] ? 'text-zinc-600 line-through' : 'text-zinc-300'; ?>"><?php echo $step['label']; ?></span>
                        <?php if (!$step['done']): ?>
                        <i class="bi bi-chevron-right text-zinc-700 text-xs ml-auto"></i>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($progress_pct < 100): ?>
                <div class="mt-4 rounded-lg bg-violet-500/5 border border-violet-500/10 p-3">
                    <p class="text-xs text-zinc-500"><i class="bi bi-lightbulb text-violet-400 mr-1"></i> Stores that complete all steps within the first week see 3x more traffic.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Orders -->
            <div class="xl:col-span-2 bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                    <h3 class="text-white font-semibold text-sm"><i class="bi bi-receipt mr-2 text-zinc-500"></i>Recent Orders</h3>
                    <a href="<?php echo $admin_base; ?>orders" class="text-xs text-violet-400 hover:text-violet-300 font-medium transition-colors">View All</a>
                </div>
                <?php if (empty($recent_orders)): ?>
                <div class="flex flex-col items-center justify-center py-16 text-zinc-500">
                    <i class="bi bi-bag text-4xl mb-3"></i>
                    <p class="text-sm">No orders yet</p>
                    <p class="text-xs text-zinc-600 mt-1">Orders from your store will appear here</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                                <th class="px-5 py-3 font-medium">Order</th>
                                <th class="px-5 py-3 font-medium">Customer</th>
                                <th class="px-5 py-3 font-medium">Amount</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/50">
                            <?php foreach ($recent_orders as $order):
                                $sc = match($order['status'] ?? '') {
                                    'completed' => ['bg-emerald-500/10 text-emerald-400', 'bg-emerald-400'],
                                    'pending'   => ['bg-amber-500/10 text-amber-400', 'bg-amber-400'],
                                    'processing'=> ['bg-sky-500/10 text-sky-400', 'bg-sky-400'],
                                    'cancelled' => ['bg-red-500/10 text-red-400', 'bg-red-400'],
                                    default     => ['bg-zinc-700 text-zinc-400', 'bg-zinc-500'],
                                };
                            ?>
                            <tr class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-5 py-4 text-zinc-400 font-mono text-xs">#<?php echo $order['id']; ?></td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-violet-500/10 text-violet-400 flex items-center justify-center text-xs font-bold">
                                            <?php echo strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <span class="text-white text-sm"><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown'); ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-white font-medium"><?php echo $currency_symbol . number_format($order['total_amount'], 2); ?></td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full <?php echo $sc[0]; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $sc[1]; ?>"></span>
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>

    </main>
</div>
