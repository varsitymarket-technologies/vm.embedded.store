<?php
#   TITLE   : Analytics Dashboard
#   DESC    : Store analytics with views tracking (day/week/month)
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 2.0.0

$db = initiate_web_database();
$domain = __DOMAIN__;
$currency_symbol = __CURRENCY_SIGN__;

// --- Period selection ---
$period = $_GET['period'] ?? '7d';
$period_map = [
    'today' => 0,
    '7d'    => 7,
    '30d'   => 30,
    '90d'   => 90,
];
$days_back = $period_map[$period] ?? 7;

// For "today" we still want 1 day of data
$date_from = ($days_back === 0)
    ? date('Y-m-d')
    : date('Y-m-d', strtotime("-{$days_back} days"));
$date_to = date('Y-m-d');

// Previous period for comparison
$prev_from = ($days_back === 0)
    ? date('Y-m-d', strtotime('-1 day'))
    : date('Y-m-d', strtotime("-" . ($days_back * 2) . " days"));
$prev_to = ($days_back === 0)
    ? date('Y-m-d', strtotime('-1 day'))
    : date('Y-m-d', strtotime("-" . ($days_back + 1) . " days"));

// --- Load analytics database (raw PDO to avoid uncatchable trigger_error) ---
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

// --- Helper: safe query on analytics db ---
function aq($pdo, $sql, $params = []) {
    if ($pdo === null) return [];
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) { return []; }
}

// --- Helper: safe query on store db ---
function sq($db, $sql, $params = []) {
    if ($db === null) return [];
    try { return $db->query($sql, $params) ?: []; }
    catch (\Throwable $e) { return []; }
}

// =====================
// Analytics Data
// =====================

// Current period views & unique visitors
$views_current = aq($analytics_pdo,
    "SELECT COALESCE(SUM(views),0) as total, COALESCE(SUM(unique_views),0) as uniques FROM pageviews_daily WHERE date >= ? AND date <= ?",
    [$date_from, $date_to]
);
$total_views = (int) ($views_current[0]['total'] ?? 0);
$total_uniques = (int) ($views_current[0]['uniques'] ?? 0);

// Previous period for comparison
$views_prev = aq($analytics_pdo,
    "SELECT COALESCE(SUM(views),0) as total, COALESCE(SUM(unique_views),0) as uniques FROM pageviews_daily WHERE date >= ? AND date <= ?",
    [$prev_from, $prev_to]
);
$prev_views = (int) ($views_prev[0]['total'] ?? 0);
$prev_uniques = (int) ($views_prev[0]['uniques'] ?? 0);

// Percentage change
function pct_change($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}
$views_change = pct_change($total_views, $prev_views);
$uniques_change = pct_change($total_uniques, $prev_uniques);

// Views over time (for chart)
$views_over_time = aq($analytics_pdo,
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

// Top pages
$top_pages = aq($analytics_pdo,
    "SELECT page, title, SUM(views) as views, SUM(unique_views) as uniques FROM pageviews_daily WHERE date >= ? AND date <= ? GROUP BY page ORDER BY views DESC LIMIT 10",
    [$date_from, $date_to]
);

// Top referrers
$top_referrers = aq($analytics_pdo,
    "SELECT referrer_domain, SUM(count) as total FROM referrers_daily WHERE date >= ? AND date <= ? GROUP BY referrer_domain ORDER BY total DESC LIMIT 10",
    [$date_from, $date_to]
);

// Device breakdown
$device_data = aq($analytics_pdo,
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

// =====================
// Store Data (from main DB)
// =====================
$total_revenue_result = sq($db, "SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE status = 'completed'");
$total_revenue = (float) ($total_revenue_result[0]['total'] ?? 0);

$total_orders_result = sq($db, "SELECT COUNT(id) as total FROM orders");
$total_orders = (int) ($total_orders_result[0]['total'] ?? 0);

$total_products_result = sq($db, "SELECT COUNT(id) as total FROM products");
$total_products = (int) ($total_products_result[0]['total'] ?? 0);

// Recent orders (for live feed)
$recent_orders = sq($db, "SELECT customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");

// Sales over time
$sales_over_time = sq($db,
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

// Order status breakdown
$order_status_data = sq($db, "SELECT status, COUNT(id) as count FROM orders GROUP BY status");
$status_labels = [];
$status_counts = [];
$status_colors_arr = [];
$color_map = [
    'pending'    => '#f59e0b',
    'processing' => '#3b82f6',
    'completed'  => '#22c55e',
    'cancelled'  => '#ef4444',
];
foreach ($order_status_data as $row) {
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = (int) $row['count'];
    $status_colors_arr[] = $color_map[$row['status']] ?? '#6b7280';
}

// --- Store ID for tracking tag ---
$store_record = __DB_MODULE__->query("SELECT id FROM sys_websites WHERE domain = ? LIMIT 1", [$domain]);
$store_id = $store_record[0]['id'] ?? '';
$tracking_host = ($_SERVER['HTTP_HOST'] ?? 'localhost:8016');
$tracking_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$tag_url = $tracking_protocol . '://' . $tracking_host . '/track/vm.analytics.js';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-4 md:p-6">

        <!-- Header Row -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-white">Analytics</h1>
                <p class="text-sm text-gray-400 mt-1">Track your store performance and visitor activity.</p>
            </div>
            <!-- Period Selector -->
            <div class="flex items-center gap-2 bg-gray-800 rounded-xl p-1 border border-white/10">
                <?php foreach (['today' => 'Today', '7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days'] as $k => $v): ?>
                    <a href="?period=<?= $k ?>"
                       class="px-4 py-2 rounded-lg text-sm font-bold transition-all <?= $period === $k ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-400 hover:text-white hover:bg-white/5' ?>">
                        <?= $v ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <!-- Page Views -->
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-eye-fill text-blue-400"></i>
                    </div>
                    <?php if ($views_change != 0): ?>
                    <span class="text-xs font-bold px-2 py-1 rounded-full <?= $views_change > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                        <?= $views_change > 0 ? '+' : '' ?><?= $views_change ?>%
                    </span>
                    <?php endif; ?>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_views) ?></p>
                <p class="text-xs text-gray-500 mt-1">Page Views</p>
            </div>

            <!-- Unique Visitors -->
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-people-fill text-purple-400"></i>
                    </div>
                    <?php if ($uniques_change != 0): ?>
                    <span class="text-xs font-bold px-2 py-1 rounded-full <?= $uniques_change > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                        <?= $uniques_change > 0 ? '+' : '' ?><?= $uniques_change ?>%
                    </span>
                    <?php endif; ?>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_uniques) ?></p>
                <p class="text-xs text-gray-500 mt-1">Unique Visitors</p>
            </div>

            <!-- Revenue -->
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-currency-dollar text-emerald-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= htmlspecialchars($currency_symbol) ?><?= number_format($total_revenue, 2) ?></p>
                <p class="text-xs text-gray-500 mt-1">Total Revenue</p>
            </div>

            <!-- Orders -->
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-cart-fill text-amber-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_orders) ?></p>
                <p class="text-xs text-gray-500 mt-1">Total Orders</p>
            </div>

            <!-- Products -->
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-cyan-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-box-seam-fill text-cyan-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= number_format($total_products) ?></p>
                <p class="text-xs text-gray-500 mt-1">Products</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Traffic Chart (spans 2 cols) -->
            <div class="lg:col-span-2 rounded-xl bg-gray-800 border border-white/5 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wide">Visitor Traffic</h3>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span> Views</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span> Unique</span>
                    </div>
                </div>
                <div style="height: 280px;">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>

            <!-- Device Breakdown -->
            <div class="rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Devices</h3>
                <?php if (empty($device_data)): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-600">
                        <i class="bi bi-phone text-4xl mb-2"></i>
                        <p class="text-sm">No device data yet</p>
                    </div>
                <?php else: ?>
                    <div style="height: 200px;">
                        <canvas id="deviceChart"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        <?php
                        $dev_total = array_sum($device_counts) ?: 1;
                        foreach ($device_data as $i => $d):
                            $pct = round(((int)$d['total'] / $dev_total) * 100);
                        ?>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" style="background: <?= $device_bg[$i] ?>"></span>
                                <span class="text-gray-300"><?= ucfirst($d['device_type']) ?></span>
                            </div>
                            <span class="text-gray-400 font-mono"><?= $pct ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Second Row: Sales + Order Status -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Sales Chart -->
            <div class="lg:col-span-2 rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Sales Revenue</h3>
                <?php if (empty($sales_data)): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-600">
                        <i class="bi bi-graph-up text-4xl mb-2"></i>
                        <p class="text-sm">No sales data yet</p>
                    </div>
                <?php else: ?>
                    <div style="height: 240px;">
                        <canvas id="salesChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Status -->
            <div class="rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Order Status</h3>
                <?php if (empty($status_counts)): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-600">
                        <i class="bi bi-pie-chart text-4xl mb-2"></i>
                        <p class="text-sm">No orders yet</p>
                    </div>
                <?php else: ?>
                    <div class="flex justify-center" style="height: 200px;">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        <?php foreach ($order_status_data as $i => $row): ?>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" style="background: <?= $status_colors_arr[$i] ?? '#6b7280' ?>"></span>
                                <span class="text-gray-300"><?= ucfirst($row['status']) ?></span>
                            </div>
                            <span class="text-gray-400 font-mono"><?= (int) $row['count'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Third Row: Top Pages + Referrers + Recent Orders -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Top Pages -->
            <div class="rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Top Pages</h3>
                <?php if (empty($top_pages)): ?>
                    <div class="flex flex-col items-center justify-center h-32 text-gray-600">
                        <i class="bi bi-file-earmark text-3xl mb-2"></i>
                        <p class="text-sm">No page data yet</p>
                        <p class="text-xs text-gray-700 mt-1">Install the tracking tag below</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php
                        $max_views = max(array_column($top_pages, 'views')) ?: 1;
                        foreach ($top_pages as $pg):
                            $bar_width = round(((int)$pg['views'] / $max_views) * 100);
                        ?>
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-300 truncate max-w-[65%]" title="<?= htmlspecialchars($pg['page']) ?>">
                                    <?= htmlspecialchars($pg['title'] ?: $pg['page']) ?>
                                </span>
                                <span class="text-gray-500 font-mono text-xs"><?= number_format((int)$pg['views']) ?></span>
                            </div>
                            <div class="w-full bg-white/5 rounded-full h-1.5">
                                <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?= $bar_width ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Referrers -->
            <div class="rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Top Referrers</h3>
                <?php if (empty($top_referrers)): ?>
                    <div class="flex flex-col items-center justify-center h-32 text-gray-600">
                        <i class="bi bi-link-45deg text-3xl mb-2"></i>
                        <p class="text-sm">No referrer data yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php
                        $max_ref = max(array_column($top_referrers, 'total')) ?: 1;
                        foreach ($top_referrers as $ref):
                            $bar_w = round(((int)$ref['total'] / $max_ref) * 100);
                        ?>
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-300 truncate max-w-[65%]"><?= htmlspecialchars($ref['referrer_domain']) ?></span>
                                <span class="text-gray-500 font-mono text-xs"><?= number_format((int)$ref['total']) ?></span>
                            </div>
                            <div class="w-full bg-white/5 rounded-full h-1.5">
                                <div class="bg-purple-500 h-1.5 rounded-full" style="width: <?= $bar_w ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Orders -->
            <div class="rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Recent Orders</h3>
                <?php if (empty($recent_orders)): ?>
                    <div class="flex flex-col items-center justify-center h-32 text-gray-600">
                        <i class="bi bi-receipt text-3xl mb-2"></i>
                        <p class="text-sm">No orders yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_orders as $order):
                            $status_color = $color_map[$order['status']] ?? '#6b7280';
                        ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-white font-medium"><?= htmlspecialchars($order['customer_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= date('M d, H:i', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-white font-mono"><?= htmlspecialchars($currency_symbol) ?><?= number_format((float)$order['total_amount'], 2) ?></p>
                                <span class="text-xs px-2 py-0.5 rounded-full" style="background: <?= $status_color ?>20; color: <?= $status_color ?>"><?= ucfirst($order['status']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tracking Tag Installation -->
        <div class="rounded-xl bg-gray-800 border border-white/5 p-5 mb-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-code-slash text-amber-400"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wide">Install Tracking Tag</h3>
                    <p class="text-xs text-gray-400 mt-1 mb-3">Add this snippet to your storefront's HTML to start collecting visitor analytics. Place it before the closing <code class="text-amber-400">&lt;/body&gt;</code> tag.</p>
                    <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 relative group">
                        <code id="trackingTag">&lt;script src="<?= htmlspecialchars($tag_url) ?>" data-store-id="<?= htmlspecialchars($store_id) ?>" defer&gt;&lt;/script&gt;</code>
                        <button onclick="copyTag()" class="absolute top-2 right-2 bg-white/10 hover:bg-white/20 text-gray-400 hover:text-white px-3 py-1.5 rounded-lg text-xs transition-colors opacity-0 group-hover:opacity-100">
                            <i class="bi bi-copy"></i> Copy
                        </button>
                    </div>
                    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                        <span><i class="bi bi-lightning-charge"></i> Lightweight (~1KB)</span>
                        <span><i class="bi bi-shield-check"></i> Privacy-friendly (hashed IPs)</span>
                        <span><i class="bi bi-speedometer2"></i> Non-blocking (async)</span>
                    </div>
                    <?php if (!empty($store_id)): ?>
                    <details class="mt-4">
                        <summary class="cursor-pointer text-xs font-bold text-gray-400 hover:text-gray-300 transition-colors">
                            Custom Event Tracking
                        </summary>
                        <div class="mt-2 bg-black/50 rounded-lg p-4 font-mono text-xs text-gray-400">
                            <p class="text-gray-500 mb-2">// Track custom events from your storefront JavaScript:</p>
                            <code class="text-gray-300">vmAnalytics.track('add_to_cart');</code><br>
                            <code class="text-gray-300">vmAnalytics.track('purchase');</code><br>
                            <code class="text-gray-300">vmAnalytics.track('product_view');</code>
                        </div>
                    </details>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.color = '#6b7280';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';
    Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';

    // --- Traffic Chart ---
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
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    },
                    {
                        label: 'Unique Visitors',
                        data: <?= json_encode($chart_uniques) ?>,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,0.05)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // --- Device Chart ---
    const deviceCtx = document.getElementById('deviceChart')?.getContext('2d');
    if (deviceCtx) {
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($device_labels) ?>,
                datasets: [{
                    data: <?= json_encode($device_counts) ?>,
                    backgroundColor: <?= json_encode($device_bg) ?>,
                    borderColor: '#1a1a1a',
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- Sales Chart ---
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
                    backgroundColor: 'rgba(34,197,94,0.3)',
                    borderColor: '#22c55e',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (v) => '<?= htmlspecialchars($currency_symbol) ?>' + v }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // --- Order Status Chart ---
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
                    borderColor: '#1a1a1a',
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }
    <?php endif; ?>
});

function copyTag() {
    const el = document.getElementById('trackingTag');
    if (el) {
        // Decode HTML entities for clipboard
        const txt = el.textContent;
        navigator.clipboard.writeText(txt);
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    }
}
</script>
