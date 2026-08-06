<?php
#   TITLE   : Analytics Dashboard
#   DESC    : Store analytics with views tracking (day/week/month)
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 2.0.0

$db = initiate_web_database();
$domain = __DOMAIN__;
$currency_symbol = __CURRENCY_SIGN__;

$period = $_GET['period'] ?? '7d';
$period_map = [
    'today' => 0,
    '7d' => 7,
    '30d' => 30,
    '90d' => 90,
];
$days_back = $period_map[$period] ?? 7;

$date_from = ($days_back === 0)
    ? date('Y-m-d')
    : date('Y-m-d', strtotime("-{$days_back} days"));
$date_to = date('Y-m-d');

$prev_from = ($days_back === 0)
    ? date('Y-m-d', strtotime('-1 day'))
    : date('Y-m-d', strtotime("-" . ($days_back * 2) . " days"));
$prev_to = ($days_back === 0)
    ? date('Y-m-d', strtotime('-1 day'))
    : date('Y-m-d', strtotime("-" . ($days_back + 1) . " days"));

$analytics_pdo = null;
$analytics_dir = dirname(dirname(dirname(__FILE__))) . "/sites/" . $domain;
$analytics_db_path = $analytics_dir . "/analytics.data";

if (file_exists($analytics_db_path)) {
    try {
        $analytics_pdo = new PDO("sqlite:" . $analytics_db_path);
        $analytics_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (\Throwable $e) {
        $analytics_pdo = null;
    }
}

function aq($pdo, $sql, $params = [])
{
    if ($pdo === null) {
        return [];
    }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
        return [];
    }
}

function sq($db, $sql, $params = [])
{
    if ($db === null) {
        return [];
    }
    try {
        return $db->query($sql, $params) ?: [];
    } catch (\Throwable $e) {
        return [];
    }
}

function pct_change($current, $previous)
{
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

$views_current = aq(
    $analytics_pdo,
    "SELECT COALESCE(SUM(views),0) as total, COALESCE(SUM(unique_views),0) as uniques FROM pageviews_daily WHERE date >= ? AND date <= ?",
    [$date_from, $date_to]
);
$total_views = (int) ($views_current[0]['total'] ?? 0);
$total_uniques = (int) ($views_current[0]['uniques'] ?? 0);

$views_prev = aq(
    $analytics_pdo,
    "SELECT COALESCE(SUM(views),0) as total, COALESCE(SUM(unique_views),0) as uniques FROM pageviews_daily WHERE date >= ? AND date <= ?",
    [$prev_from, $prev_to]
);
$prev_views = (int) ($views_prev[0]['total'] ?? 0);
$prev_uniques = (int) ($views_prev[0]['uniques'] ?? 0);

$views_change = pct_change($total_views, $prev_views);
$uniques_change = pct_change($total_uniques, $prev_uniques);

$views_over_time = aq(
    $analytics_pdo,
    "SELECT date, SUM(views) as views, SUM(unique_views) as uniques FROM pageviews_daily WHERE date >= ? AND date <= ? GROUP BY date ORDER BY date ASC",
    [$date_from, $date_to]
);

$chart_labels = [];
$chart_views = [];
$chart_uniques = [];
foreach ($views_over_time as $row) {
    $chart_labels[] = date('M d', strtotime($row['date']));
    $chart_views[] = (int) $row['views'];
    $chart_uniques[] = (int) $row['uniques'];
}

$top_pages = aq(
    $analytics_pdo,
    "SELECT page, title, SUM(views) as views, SUM(unique_views) as uniques FROM pageviews_daily WHERE date >= ? AND date <= ? GROUP BY page ORDER BY views DESC LIMIT 10",
    [$date_from, $date_to]
);

$top_referrers = aq(
    $analytics_pdo,
    "SELECT referrer_domain, SUM(count) as total FROM referrers_daily WHERE date >= ? AND date <= ? GROUP BY referrer_domain ORDER BY total DESC LIMIT 10",
    [$date_from, $date_to]
);

$device_data = aq(
    $analytics_pdo,
    "SELECT device_type, SUM(count) as total FROM devices_daily WHERE date >= ? AND date <= ? GROUP BY device_type ORDER BY total DESC",
    [$date_from, $date_to]
);
$device_labels = [];
$device_counts = [];
$device_colors = ['desktop' => '#3b82f6', 'mobile' => '#8b5cf6', 'tablet' => '#f59e0b'];
$device_bg = [];
foreach ($device_data as $row) {
    $device_labels[] = ucfirst($row['device_type']);
    $device_counts[] = (int) $row['total'];
    $device_bg[] = $device_colors[$row['device_type']] ?? '#6b7280';
}

$total_revenue_result = sq($db, "SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE status = 'completed'");
$total_revenue = (float) ($total_revenue_result[0]['total'] ?? 0);

$total_orders_result = sq($db, "SELECT COUNT(id) as total FROM orders");
$total_orders = (int) ($total_orders_result[0]['total'] ?? 0);

$total_products_result = sq($db, "SELECT COUNT(id) as total FROM products");
$total_products = (int) ($total_products_result[0]['total'] ?? 0);

$recent_orders = sq($db, "SELECT customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");

$sales_over_time = sq(
    $db,
    "SELECT strftime('%Y-%m-%d', created_at) as date, SUM(total_amount) as sales, COUNT(id) as order_count
     FROM orders WHERE status = 'completed' AND created_at >= date('now', '-" . max($days_back, 7) . " days')
     GROUP BY date ORDER BY date ASC"
);
$sales_labels = [];
$sales_data = [];
foreach ($sales_over_time as $row) {
    $sales_labels[] = date('M d', strtotime($row['date']));
    $sales_data[] = (float) $row['sales'];
}

$order_status_data = sq($db, "SELECT status, COUNT(id) as count FROM orders GROUP BY status");
$status_labels = [];
$status_counts = [];
$status_colors_arr = [];
$color_map = [
    'pending' => '#f59e0b',
    'processing' => '#3b82f6',
    'completed' => '#22c55e',
    'cancelled' => '#ef4444',
];
foreach ($order_status_data as $row) {
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = (int) $row['count'];
    $status_colors_arr[] = $color_map[$row['status']] ?? '#6b7280';
}

$store_record = __DB_MODULE__->query("SELECT id FROM sys_websites WHERE domain = ? LIMIT 1", [$domain]);
$store_id = $store_record[0]['id'] ?? '';
$tracking_host = ($_SERVER['HTTP_HOST'] ?? 'localhost:8016');
$tracking_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$tag_url = $tracking_protocol . '://' . $tracking_host . '/track/vm.analytics.js';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .analytics-shell {
        color: #f3f4f6;
    }

    .analytics-card {
        transition: transform 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        background: rgba(37, 37, 38, 0.92);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 12px 34px rgba(0, 0, 0, 0.22);
        overflow: hidden;
    }

    .analytics-card:hover {
        transform: translateY(-1px);
        border-color: rgba(122, 26, 171, 0.22);
    }

    .analytics-chip {
        letter-spacing: 0.12em;
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: #d4d4d8;
    }

    .period-switcher {
        background: rgba(37, 37, 38, 0.95);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
    }

    .period-link {
        color: #a1a1aa;
    }

    .period-link:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #ffffff;
    }

    .period-link.active {
        background: #008060;
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(0, 128, 96, 0.16);
    }

    .metric-icon {
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
    }

    .surface {
        background: rgba(255, 255, 255, 0.04);
    }

    .section-head {
        background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.015) 100%);
    }
</style>

<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="analytics-shell flex-1 overflow-y-auto overflow-x-hidden bg-[#1b1b1c] p-4 md:p-6">
        <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] analytics-chip">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Analytics
                </div>
                <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl">Store performance</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">
                    A cleaner snapshot of traffic, conversions, and order flow for
                    <?php echo htmlspecialchars(website_data('name') ?: 'your store'); ?>.
                </p>
            </div>

            <div class="period-switcher flex items-center gap-2 rounded-2xl border p-1">
                <?php foreach (['today' => 'Today', '7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days'] as $k => $v): ?>
                    <a href="?period=<?= $k ?>"
                        class="period-link rounded-xl px-4 py-2 text-sm font-bold transition-all <?= $period === $k ? 'active' : '' ?>">
                        <?= $v ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="analytics-card rounded-2xl border p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="metric-icon flex h-11 w-11 items-center justify-center rounded-xl bg-sky-500/15 text-sky-300">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                    <?php if ($views_change != 0): ?>
                        <span class="rounded-full px-2 py-1 text-xs font-bold <?= $views_change > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                            <?= $views_change > 0 ? '+' : '' ?><?= $views_change ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_views) ?></p>
                <p class="mt-1 text-xs text-zinc-400">Page views</p>
            </div>

            <div class="analytics-card rounded-2xl border p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="metric-icon flex h-11 w-11 items-center justify-center rounded-xl bg-violet-500/15 text-violet-300">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <?php if ($uniques_change != 0): ?>
                        <span class="rounded-full px-2 py-1 text-xs font-bold <?= $uniques_change > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                            <?= $uniques_change > 0 ? '+' : '' ?><?= $uniques_change ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_uniques) ?></p>
                <p class="mt-1 text-xs text-zinc-400">Unique visitors</p>
            </div>

            <div class="analytics-card rounded-2xl border p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="metric-icon flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-300">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= htmlspecialchars($currency_symbol) ?><?= number_format($total_revenue, 2) ?></p>
                <p class="mt-1 text-xs text-zinc-400">Total revenue</p>
            </div>

            <div class="analytics-card rounded-2xl border p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="metric-icon flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/15 text-amber-300">
                        <i class="bi bi-cart-fill"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_orders) ?></p>
                <p class="mt-1 text-xs text-zinc-400">Orders</p>
            </div>

            <div class="analytics-card rounded-2xl border p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="metric-icon flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-500/15 text-cyan-300">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_products) ?></p>
                <p class="mt-1 text-xs text-zinc-400">Products</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 xl:grid-cols-12">
            <section class="analytics-card xl:col-span-8 overflow-hidden rounded-2xl border">
                <div class="section-head flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-white">Visitor traffic</h2>
                        <p class="mt-1 text-xs text-zinc-400">Views and unique visitors over the selected period.</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-zinc-400">
                        <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span> Views</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span> Unique</span>
                    </div>
                </div>
                <div class="p-5">
                    <div style="height: 320px;">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </section>

            <aside class="analytics-card xl:col-span-4 overflow-hidden rounded-2xl border">
                <div class="section-head border-b border-white/10 px-5 py-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-white">Devices</h2>
                    <p class="mt-1 text-xs text-zinc-400">Traffic split by device type.</p>
                </div>
                <div class="p-5">
                    <?php if (empty($device_data)): ?>
                        <div class="flex h-80 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 text-zinc-500">
                            <i class="bi bi-phone text-4xl mb-2"></i>
                            <p class="text-sm">No device data yet</p>
                        </div>
                    <?php else: ?>
                        <div style="height: 220px;">
                            <canvas id="deviceChart"></canvas>
                        </div>
                        <div class="mt-4 space-y-2">
                            <?php
                            $dev_total = array_sum($device_counts) ?: 1;
                            foreach ($device_data as $i => $d):
                                $pct = round(((int) $d['total'] / $dev_total) * 100);
                                ?>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: <?= $device_bg[$i] ?>"></span>
                                        <span class="text-zinc-300"><?= ucfirst($d['device_type']) ?></span>
                                    </div>
                                    <span class="font-mono text-zinc-500"><?= $pct ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 xl:grid-cols-12">
            <section class="analytics-card xl:col-span-8 overflow-hidden rounded-2xl border">
                <div class="section-head border-b border-white/10 px-5 py-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-white">Sales revenue</h2>
                    <p class="mt-1 text-xs text-zinc-400">Completed order revenue by day.</p>
                </div>
                <div class="p-5">
                    <?php if (empty($sales_data)): ?>
                        <div class="flex h-72 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 text-zinc-500">
                            <i class="bi bi-graph-up text-4xl mb-2"></i>
                            <p class="text-sm">No sales data yet</p>
                        </div>
                    <?php else: ?>
                        <div style="height: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="analytics-card xl:col-span-4 overflow-hidden rounded-2xl border">
                <div class="section-head border-b border-white/10 px-5 py-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-white">Order status</h2>
                    <p class="mt-1 text-xs text-zinc-400">How orders are moving through the funnel.</p>
                </div>
                <div class="p-5">
                    <?php if (empty($status_counts)): ?>
                        <div class="flex h-72 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 text-zinc-500">
                            <i class="bi bi-pie-chart text-4xl mb-2"></i>
                            <p class="text-sm">No orders yet</p>
                        </div>
                    <?php else: ?>
                        <div style="height: 210px;">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                        <div class="mt-4 space-y-2">
                            <?php foreach ($order_status_data as $i => $row): ?>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: <?= $status_colors_arr[$i] ?? '#6b7280' ?>"></span>
                                        <span class="text-gray-700"><?= ucfirst($row['status']) ?></span>
                                    </div>
                                    <span class="font-mono text-gray-500"><?= (int) $row['count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 xl:grid-cols-3">
            <section class="analytics-card overflow-hidden rounded-2xl border">
                <div class="section-head border-b border-white/10 px-5 py-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-white">Top pages</h2>
                    <p class="mt-1 text-xs text-zinc-400">Most viewed pages in the selected range.</p>
                </div>
                <div class="p-5">
                    <?php if (empty($top_pages)): ?>
                        <div class="flex h-44 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 text-zinc-500">
                            <i class="bi bi-file-earmark text-3xl mb-2"></i>
                            <p class="text-sm">No page data yet</p>
                            <p class="mt-1 text-xs text-zinc-500">Install the tracking tag below</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php
                            $max_views = max(array_column($top_pages, 'views')) ?: 1;
                            foreach ($top_pages as $pg):
                                $bar_width = round(((int) $pg['views'] / $max_views) * 100);
                                ?>
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                        <span class="max-w-[70%] truncate text-zinc-200" title="<?= htmlspecialchars($pg['page']) ?>">
                                            <?= htmlspecialchars($pg['title'] ?: $pg['page']) ?>
                                        </span>
                                        <span class="font-mono text-xs text-zinc-500"><?= number_format((int) $pg['views']) ?></span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-white/10">
                                        <div class="h-1.5 rounded-full bg-[#008060]" style="width: <?= $bar_width ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="analytics-card overflow-hidden rounded-2xl border">
                <div class="section-head border-b border-white/10 px-5 py-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-white">Top referrers</h2>
                    <p class="mt-1 text-xs text-zinc-400">Which sites are sending visitors your way.</p>
                </div>
                <div class="p-5">
                    <?php if (empty($top_referrers)): ?>
                        <div class="flex h-44 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 text-zinc-500">
                            <i class="bi bi-link-45deg text-3xl mb-2"></i>
                            <p class="text-sm">No referrer data yet</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php
                            $max_ref = max(array_column($top_referrers, 'total')) ?: 1;
                            foreach ($top_referrers as $ref):
                                $bar_w = round(((int) $ref['total'] / $max_ref) * 100);
                                ?>
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                        <span class="max-w-[70%] truncate text-zinc-200"><?= htmlspecialchars($ref['referrer_domain']) ?></span>
                                        <span class="font-mono text-xs text-zinc-500"><?= number_format((int) $ref['total']) ?></span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-white/10">
                                        <div class="h-1.5 rounded-full bg-[#008060]" style="width: <?= $bar_w ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="analytics-card overflow-hidden rounded-2xl border">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-white">Recent orders</h2>
                    <p class="mt-1 text-xs text-zinc-400">Latest order activity from the store.</p>
                </div>
                <div class="p-5">
                    <?php if (empty($recent_orders)): ?>
                        <div class="flex h-44 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 text-zinc-500">
                            <i class="bi bi-receipt text-3xl mb-2"></i>
                            <p class="text-sm">No orders yet</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_orders as $order):
                                $status_color = $color_map[$order['status']] ?? '#6b7280';
                                ?>
                                <div class="flex items-start justify-between gap-4 rounded-xl border border-white/10 bg-white/5 px-3 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-white"><?= htmlspecialchars($order['customer_name']) ?></p>
                                        <p class="mt-1 text-xs text-zinc-400"><?= date('M d, H:i', strtotime($order['created_at'])) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-mono text-white">
                                            <?= htmlspecialchars($currency_symbol) ?><?= number_format((float) $order['total_amount'], 2) ?>
                                        </p>
                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs"
                                            style="background: <?= $status_color ?>20; color: <?= $status_color ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="mt-8 rounded-2xl border border-white/10 bg-[#202123] p-5 md:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="metric-icon flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400">
                        <i class="bi bi-code-slash text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-white">Install tracking tag</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-400">
                            Add this snippet to your storefront HTML so the analytics tables and charts can start
                            collecting visitor data.
                        </p>
                    </div>
                </div>
                <button onclick="copyTag()"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-medium text-white hover:bg-white/10">
                    <i class="bi bi-copy"></i>
                    Copy tag
                </button>
            </div>

            <div class="surface mt-5 rounded-xl border border-white/10 p-4 font-mono text-sm text-zinc-200">
                <code id="trackingTag">&lt;script src="<?= htmlspecialchars($tag_url) ?>" data-store-id="<?= htmlspecialchars($store_id) ?>" defer&gt;&lt;/script&gt;</code>
            </div>

            <div class="mt-3 flex flex-wrap gap-4 text-xs text-zinc-500">
                <span><i class="bi bi-lightning-charge"></i> Lightweight (~1KB)</span>
                <span><i class="bi bi-shield-check"></i> Privacy-friendly (hashed IPs)</span>
                <span><i class="bi bi-speedometer2"></i> Non-blocking (async)</span>
            </div>

            <?php if (!empty($store_id)): ?>
                <details class="mt-4">
                    <summary class="cursor-pointer text-xs font-bold text-zinc-400 transition-colors hover:text-white">
                        Custom event tracking
                    </summary>
                    <div class="surface mt-2 rounded-xl border border-white/10 p-4 font-mono text-xs text-zinc-400">
                        <p class="mb-2 text-zinc-500">// Track custom events from your storefront JavaScript:</p>
                        <code class="text-zinc-200">vmAnalytics.track('add_to_cart');</code><br>
                        <code class="text-zinc-200">vmAnalytics.track('purchase');</code><br>
                        <code class="text-zinc-200">vmAnalytics.track('product_view');</code>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        Chart.defaults.color = '#a1a1aa';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
        Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';

        const makeGradient = (ctx, topColor, bottomColor) => {
            const { chartArea, ctx: canvasCtx } = ctx;
            if (!chartArea) return bottomColor;
            const gradient = canvasCtx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, topColor);
            gradient.addColorStop(1, bottomColor);
            return gradient;
        };

        const trafficCtx = document.getElementById('trafficChart')?.getContext('2d');
        if (trafficCtx) {
            new Chart(trafficCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [
                        {
                            label: 'Page Views',
                            data: <?= json_encode($chart_views) ?>,
                            borderColor: '#38bdf8',
                            backgroundColor: (ctx) => makeGradient(ctx.chart, 'rgba(56,189,248,0.35)', 'rgba(56,189,248,0.02)'),
                            fill: true,
                            tension: 0.46,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            borderWidth: 2.5,
                            borderCapStyle: 'round',
                            borderJoinStyle: 'round'
                        },
                        {
                            label: 'Unique Visitors',
                            data: <?= json_encode($chart_uniques) ?>,
                            borderColor: '#4ade80',
                            backgroundColor: (ctx) => makeGradient(ctx.chart, 'rgba(74,222,128,0.24)', 'rgba(74,222,128,0.01)'),
                            fill: true,
                            tension: 0.46,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            borderWidth: 2.5,
                            borderCapStyle: 'round',
                            borderJoinStyle: 'round'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'line',
                                boxWidth: 18,
                                boxHeight: 3,
                                padding: 16,
                                color: '#9ca3af'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255,255,255,0.96)',
                            titleColor: '#111827',
                            bodyColor: '#374151',
                            borderColor: 'rgba(15,23,42,0.08)',
                            borderWidth: 1,
                            displayColors: true,
                            padding: 12,
                            cornerRadius: 10
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: true,
                                drawBorder: false,
                                color: 'rgba(229,231,235,0.9)'
                            },
                            ticks: { color: '#6b7280' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#6b7280' },
                            grid: {
                                color: 'rgba(229,231,235,0.9)',
                                drawBorder: false
                            }
                        }
                    }
                }
            });
        }

        const deviceCtx = document.getElementById('deviceChart')?.getContext('2d');
        if (deviceCtx) {
            new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($device_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($device_counts) ?>,
                        backgroundColor: <?= json_encode($device_bg) ?>,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 8,
                        spacing: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '74%',
                    rotation: -90,
                    circumference: 360,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                padding: 14,
                                color: '#9ca3af'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255,255,255,0.96)',
                            titleColor: '#111827',
                            bodyColor: '#374151',
                            borderColor: 'rgba(15,23,42,0.08)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 10
                        }
                    }
                }
            });
        }

        <?php if (!empty($sales_data)): ?>
            const salesCtx = document.getElementById('salesChart')?.getContext('2d');
            if (salesCtx) {
                new Chart(salesCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($sales_labels) ?>,
                        datasets: [{
                            label: 'Sales',
                            data: <?= json_encode($sales_data) ?>,
                            backgroundColor: (ctx) => makeGradient(ctx.chart, 'rgba(34,197,94,0.55)', 'rgba(34,197,94,0.12)'),
                            borderColor: '#22c55e',
                            borderWidth: 1,
                            borderRadius: 10,
                            borderSkipped: false,
                            maxBarThickness: 26
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                            backgroundColor: 'rgba(255,255,255,0.96)',
                            titleColor: '#111827',
                            bodyColor: '#374151',
                            borderColor: 'rgba(15,23,42,0.08)',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 10
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { color: '#6b7280' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#6b7280',
                                    callback: (v) => '<?= htmlspecialchars($currency_symbol) ?>' + v
                                },
                                grid: {
                                color: 'rgba(229,231,235,0.9)',
                                    drawBorder: false
                                }
                            }
                        }
                    }
                });
            }
        <?php endif; ?>

        <?php if (!empty($status_counts)): ?>
            const statusCtx = document.getElementById('orderStatusChart')?.getContext('2d');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($status_labels) ?>,
                        datasets: [{
                            data: <?= json_encode($status_counts) ?>,
                            backgroundColor: <?= json_encode($status_colors_arr) ?>,
                        borderColor: '#ffffff',
                            borderWidth: 2,
                            hoverOffset: 8,
                            spacing: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '74%',
                        rotation: -90,
                        circumference: 360,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    boxWidth: 8,
                                    padding: 14,
                                    color: '#9ca3af'
                                }
                            },
                            tooltip: {
                            backgroundColor: 'rgba(255,255,255,0.96)',
                            titleColor: '#111827',
                            bodyColor: '#374151',
                            borderColor: 'rgba(15,23,42,0.08)',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 10
                            }
                        }
                    }
                });
            }
        <?php endif; ?>
    });

    function copyTag() {
        const el = document.getElementById('trackingTag');
        if (!el) return;
        navigator.clipboard.writeText(el.textContent);
        const btn = event.target.closest('button');
        if (!btn) return;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        setTimeout(() => {
            btn.innerHTML = original;
        }, 2000);
    }
</script>
