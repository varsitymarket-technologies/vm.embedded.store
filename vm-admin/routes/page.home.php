<?php
$db = initiate_web_database();
$currency_symbol = defined('__CURRENCY_SIGN__') ? __CURRENCY_SIGN__ : 'R';
$site_root = dirname(dirname(dirname(__FILE__)));
$site_dir = $site_root . '/sites/' . (__DOMAIN__ ?? '');
$pages_dir = $site_dir . '/data/pages';
$builder_cache = $site_dir . '/builder.cache.html';
$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';

// Core store stats
$revenue_result = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'");
$total_revenue = $revenue_result[0]['total'] ?? 0;

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

$recent_orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_pages = [];

if (is_dir($pages_dir)) {
    foreach (glob($pages_dir . '/*.page') ?: [] as $file) {
        $slug = basename($file, '.page');
        $recent_pages[] = [
            'slug' => $slug,
            'label' => ucfirst(str_replace(['-', '_'], ' ', $slug)),
            'modified' => @filemtime($file) ?: time(),
            'is_home' => in_array($slug, ['home', 'index'], true),
        ];
    }

    usort($recent_pages, fn($a, $b) => $b['modified'] <=> $a['modified']);
}

$home_page = null;
foreach ($recent_pages as $page) {
    if ($page['is_home']) {
        $home_page = $page;
        break;
    }
}

$page_count = count($recent_pages);
$has_domain = !empty(__DOMAIN__);
$has_theme = !empty(website_data('theme'));
$has_payment = file_exists($site_dir . "/payment.config.enc");
$has_analytics = file_exists($site_dir . "/analytics.data");
$has_products = $total_products > 0;
$has_orders = $total_orders > 0;

$checklist = [
    ['done' => $has_domain, 'label' => 'Set up your store domain', 'link' => $admin_base . 'settings?tab=domain', 'icon' => 'bi-globe'],
    ['done' => $has_theme, 'label' => 'Choose a theme', 'link' => $admin_base . 'theme', 'icon' => 'bi-palette'],
    ['done' => $has_products, 'label' => 'Add your first product', 'link' => $admin_base . 'products', 'icon' => 'bi-box-seam'],
    ['done' => $has_payment, 'label' => 'Configure payment methods', 'link' => $admin_base . 'settings?tab=payment', 'icon' => 'bi-credit-card'],
    ['done' => $has_analytics, 'label' => 'Install analytics tracking', 'link' => $admin_base . 'analytics', 'icon' => 'bi-graph-up'],
    ['done' => $has_orders, 'label' => 'Get your first order', 'link' => $admin_base . 'orders', 'icon' => 'bi-bag-check'],
];

$completed_steps = count(array_filter($checklist, fn($s) => $s['done']));
$total_steps = max(1, count($checklist));
$progress_pct = round(($completed_steps / $total_steps) * 100);
$hero_metric = $page_count > 0 ? $page_count : 0;
?>
<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#1b1b1c] p-4 sm:p-6 lg:p-8">
        <section class="relative overflow-hidden rounded-[28px] border border-white/10 bg-[linear-gradient(135deg,rgba(37,37,38,0.98),rgba(23,23,24,0.96))] shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
            <div class="absolute inset-y-0 right-0 w-1/3 bg-[radial-gradient(circle_at_top_right,_rgba(122,26,171,0.18),_transparent_55%)]"></div>
            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)] lg:p-8">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-300">
                        <span class="h-2 w-2 rounded-full bg-[#008060]"></span>
                        Store Pages
                    </div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Manage your storefront pages from one calm workspace.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400 sm:text-base">
                        Create, edit, and organize homepage and content pages without leaving the admin. The layout is cleaner and more focused, while the interface stays dark and production-ready.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="<?php echo $admin_base; ?>builder<?php echo $home_page ? '?page=' . urlencode($home_page['slug']) : ''; ?>"
                           class="admin-btn inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                            <i class="bi bi-brush"></i>
                            <span>Open page builder</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>page"
                           class="admin-btn inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            <i class="bi bi-file-earmark-text"></i>
                            <span>View all pages</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>settings?tab=branding"
                           class="admin-btn inline-flex items-center gap-2 rounded-full border border-white/10 bg-[#111827] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1f2937]">
                            <i class="bi bi-palette"></i>
                            <span>Branding settings</span>
                        </a>
                    </div>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Pages</p>
                            <p class="mt-2 text-3xl font-semibold text-white"><?php echo $hero_metric; ?></p>
                            <p class="mt-1 text-sm text-zinc-400">Active store pages</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Completion</p>
                            <p class="mt-2 text-3xl font-semibold text-white"><?php echo $progress_pct; ?>%</p>
                            <p class="mt-1 text-sm text-zinc-400"><?php echo $completed_steps; ?>/<?php echo $total_steps; ?> setup tasks</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Orders</p>
                            <p class="mt-2 text-3xl font-semibold text-white"><?php echo $total_orders; ?></p>
                            <p class="mt-1 text-sm text-zinc-400"><?php echo $pending_orders; ?> pending</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="rounded-3xl border border-white/10 bg-[#202123] p-5 text-white shadow-[0_18px_45px_rgba(0,0,0,0.22)]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Store health</p>
                                <h2 class="mt-1 text-xl font-semibold">Ready-to-publish checklist</h2>
                            </div>
                            <div class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">
                                <?php echo $completed_steps; ?>/<?php echo $total_steps; ?>
                            </div>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-[#008060]" style="width: <?php echo $progress_pct; ?>%"></div>
                        </div>
                        <div class="mt-4 space-y-3">
                            <?php foreach (array_slice($checklist, 0, 4) as $step): ?>
                                <a href="<?php echo $step['link']; ?>" class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:bg-white/10">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl <?php echo $step['done'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-white/70'; ?>">
                                        <i class="bi <?php echo $step['done'] ? 'bi-check-lg' : $step['icon']; ?>"></i>
                                    </span>
                                    <span class="text-sm font-medium <?php echo $step['done'] ? 'text-white/50 line-through' : 'text-white'; ?>">
                                        <?php echo htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <?php if (!$step['done']): ?>
                                        <i class="bi bi-chevron-right ml-auto text-white/35"></i>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-[#202123] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Revenue</p>
                        <div class="mt-2 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-3xl font-semibold text-white"><?php echo $currency_symbol . number_format($total_revenue, 2); ?></p>
                                <p class="mt-1 text-sm text-zinc-400">Completed order revenue</p>
                            </div>
                            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400">
                                Live store
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-white/10 bg-[#252526] p-5 shadow-[0_10px_28px_rgba(0,0,0,0.18)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-zinc-400">Products</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/5 text-[#008060]"><i class="bi bi-box-seam"></i></span>
                </div>
                <p class="mt-4 text-3xl font-semibold text-white"><?php echo $total_products; ?></p>
                <p class="mt-1 text-sm text-zinc-400"><?php echo $total_categories; ?> collections and categories</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-[#252526] p-5 shadow-[0_10px_28px_rgba(0,0,0,0.18)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-zinc-400">Orders</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/5 text-white"><i class="bi bi-bag"></i></span>
                </div>
                <p class="mt-4 text-3xl font-semibold text-white"><?php echo $total_orders; ?></p>
                <p class="mt-1 text-sm text-zinc-400"><?php echo $pending_orders; ?> waiting for action</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-[#252526] p-5 shadow-[0_10px_28px_rgba(0,0,0,0.18)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-zinc-400">Inventory</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400"><i class="bi bi-exclamation-triangle"></i></span>
                </div>
                <p class="mt-4 text-3xl font-semibold text-white"><?php echo $low_stock; ?></p>
                <p class="mt-1 text-sm text-zinc-400"><?php echo $out_of_stock; ?> out of stock</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-[#252526] p-5 shadow-[0_10px_28px_rgba(0,0,0,0.18)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-zinc-400">Pages</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-500/10 text-[#008060]"><i class="bi bi-file-earmark-text"></i></span>
                </div>
                <p class="mt-4 text-3xl font-semibold text-white"><?php echo $page_count; ?></p>
                <p class="mt-1 text-sm text-zinc-400">Homepage, landing pages, and content pages</p>
            </div>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(330px,0.8fr)]">
            <div class="rounded-[28px] border border-white/10 bg-[#202123] shadow-[0_18px_45px_rgba(0,0,0,0.22)]">
                <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Pages</p>
                        <h2 class="mt-1 text-2xl font-semibold text-white">All store pages</h2>
                    </div>
                    <a href="<?php echo $admin_base; ?>page"
                       class="admin-btn inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                        <i class="bi bi-arrow-right"></i>
                        <span>Open page manager</span>
                    </a>
                </div>

                <div class="overflow-hidden">
                    <?php if (empty($recent_pages)): ?>
                        <div class="px-5 py-14 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 text-[#008060]">
                                <i class="bi bi-file-earmark-plus text-2xl"></i>
                            </div>
                            <p class="mt-4 text-lg font-semibold text-white">No store pages yet</p>
                            <p class="mt-1 text-sm text-zinc-400">Create your homepage or launch a content page to get started.</p>
                            <div class="mt-5 flex justify-center gap-3">
                                <a href="<?php echo $admin_base; ?>page"
                                   class="admin-btn inline-flex items-center gap-2 rounded-full bg-[#008060] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#006e52]">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Create page</span>
                                </a>
                                <a href="<?php echo $admin_base; ?>builder"
                                   class="admin-btn inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                    <i class="bi bi-brush"></i>
                                    <span>Open builder</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-white/10">
                            <?php foreach (array_slice($recent_pages, 0, 6) as $page): ?>
                                <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl <?php echo $page['is_home'] ? 'bg-emerald-500/10 text-[#008060]' : 'bg-white/5 text-white'; ?>">
                                            <i class="bi <?php echo $page['is_home'] ? 'bi-house-heart' : 'bi-file-earmark-text'; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="text-sm font-semibold text-white"><?php echo htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <?php if ($page['is_home']): ?>
                                                    <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-300">Home</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mt-1 text-sm text-zinc-400">/<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <p class="mt-1 text-xs text-zinc-500">Updated <?php echo date('M j, Y', (int) $page['modified']); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="<?php echo $admin_base; ?>builder?page=<?php echo urlencode($page['slug']); ?>"
                                           class="admin-btn inline-flex items-center gap-2 rounded-full bg-[#008060] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#006e52]">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit</span>
                                        </a>
                                        <a href="<?php echo $admin_base; ?>page?page_slug=<?php echo urlencode($page['slug']); ?>"
                                           class="admin-btn inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/10">
                                            <i class="bi bi-folder2-open"></i>
                                            <span>Manage</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-[28px] border border-white/10 bg-[#202123] p-5 text-white shadow-[0_18px_45px_rgba(0,0,0,0.22)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Next steps</p>
                            <h3 class="mt-1 text-xl font-semibold">Finish store setup</h3>
                        </div>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/75"><?php echo $progress_pct; ?>%</span>
                    </div>
                    <div class="mt-4 h-2 rounded-full bg-white/10">
                        <div class="h-2 rounded-full bg-[#008060]" style="width: <?php echo $progress_pct; ?>%"></div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <?php foreach ($checklist as $step): ?>
                            <a href="<?php echo $step['link']; ?>" class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:bg-white/10">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl <?php echo $step['done'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-white/70'; ?>">
                                    <i class="bi <?php echo $step['done'] ? 'bi-check-lg' : $step['icon']; ?>"></i>
                                </span>
                                <span class="text-sm <?php echo $step['done'] ? 'text-white/50 line-through' : 'text-white'; ?>">
                                    <?php echo htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if (!$step['done']): ?>
                                    <i class="bi bi-chevron-right ml-auto text-white/35"></i>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.22)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Recent orders</p>
                            <h3 class="mt-1 text-xl font-semibold text-white">Latest sales</h3>
                        </div>
                        <a href="<?php echo $admin_base; ?>orders" class="text-sm font-semibold text-[#008060] hover:text-[#006e52]">View all</a>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                        <?php if (empty($recent_orders)): ?>
                            <div class="px-4 py-10 text-center text-sm text-zinc-400">
                                No orders yet. Your first sale will appear here.
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-white/10">
                                <?php foreach ($recent_orders as $order):
                                    $status = strtolower((string) ($order['status'] ?? 'unknown'));
                                    $badge = match ($status) {
                                        'completed' => 'bg-emerald-50 text-emerald-700',
                                        'pending' => 'bg-amber-50 text-amber-700',
                                        'processing' => 'bg-sky-50 text-sky-700',
                                        'cancelled' => 'bg-red-50 text-red-700',
                                        default => 'bg-zinc-100 text-zinc-700',
                                    };
                                    ?>
                                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-white">Order #<?php echo $order['id']; ?></p>
                                            <p class="text-xs text-zinc-400"><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown customer'); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-white"><?php echo $currency_symbol . number_format((float) ($order['total_amount'] ?? 0), 2); ?></p>
                                            <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold <?php echo $badge; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
