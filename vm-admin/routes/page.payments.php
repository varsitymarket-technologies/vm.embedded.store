<?php
#   TITLE   : Payments Dashboard
#   DESC    : Revenue tracking and payment gateway status
#   VERSION : 3.0.0

$db = initiate_web_database();
$currency = __CURRENCY_SIGN__;
$domain = __DOMAIN__;

// --- Helper: safe query ---
function pq($db, $sql, $params = []) {
    if ($db === null) return [];
    try { return $db->query($sql, $params) ?: []; }
    catch (\Throwable $e) { return []; }
}

// --- Load payment config from settings ---
$config_key = create_enc_key();
$payment_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/payment.config.enc";
$payment_config = [];
if (file_exists($payment_path)) {
    $encrypted = file_get_contents($payment_path);
    $json = __decryption__($encrypted, $config_key);
    $payment_config = json_decode($json, true) ?: [];
}

$cod_enabled = ($payment_config['cod_enabled'] ?? '0') === '1';
$yoco_enabled = ($payment_config['yoco_enabled'] ?? '0') === '1';
$yoco_mode = $payment_config['yoco_mode'] ?? 'test';
$paypal_enabled = ($payment_config['paypal_enabled'] ?? '0') === '1';
$paypal_env = $payment_config['paypal_env'] ?? 'sandbox';
$active_methods = ($cod_enabled ? 1 : 0) + ($yoco_enabled ? 1 : 0) + ($paypal_enabled ? 1 : 0);

// --- Stats from orders ---
$orders = pq($db, "SELECT * FROM orders");
$grossRevenue = 0;
$completedRevenue = 0;
$totalOrders = 0;
$completedOrders = 0;
$pendingOrders = 0;
foreach ($orders as $o) {
    $amt = (float)($o['total_amount'] ?? 0);
    if ($o['status'] !== 'cancelled') {
        $grossRevenue += $amt;
        $totalOrders++;
    }
    if ($o['status'] === 'completed') {
        $completedRevenue += $amt;
        $completedOrders++;
    }
    if ($o['status'] === 'pending') {
        $pendingOrders++;
    }
}
$avgOrder = $totalOrders > 0 ? $grossRevenue / $totalOrders : 0;

// --- Revenue chart data (last 30 days) ---
$revenue_by_day = pq($db,
    "SELECT strftime('%Y-%m-%d', created_at) as date, SUM(total_amount) as total
     FROM orders WHERE status != 'cancelled' AND created_at >= date('now', '-30 days')
     GROUP BY date ORDER BY date ASC"
);
$chart_labels = [];
$chart_data = [];
foreach ($revenue_by_day as $row) {
    $chart_labels[] = date('M d', strtotime($row['date']));
    $chart_data[] = (float) $row['total'];
}

// --- Recent orders ---
$recent_orders = pq($db, "SELECT customer_name, customer_email, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 10");

// --- Banking details ---
$bank_name = defined('__BANKING_SERVICE__') ? __BANKING_SERVICE__ : '';
$bank_account = defined('__BANKING_ACCOUNT_NUMBER__') ? __BANKING_ACCOUNT_NUMBER__ : '';
$bank_type = defined('__BANKING_ACCOUNT_TYPE__') ? __BANKING_ACCOUNT_TYPE__ : '';

$status_colors = [
    'pending'    => '#f59e0b',
    'processing' => '#3b82f6',
    'completed'  => '#22c55e',
    'cancelled'  => '#ef4444',
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#09090b] text-zinc-100 font-sans">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto">
        <!-- Header -->
        <div class="px-8 pt-8 pb-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Payments</h2>
                    <p class="text-sm text-zinc-500 mt-1">Revenue tracking and payment gateway status</p>
                </div>
                <a href="settings?tab=payment" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="bi bi-gear"></i> Payment Settings
                </a>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="px-8 pb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Gross Revenue</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <i class="bi bi-cash-stack text-emerald-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= $currency ?><?= number_format($grossRevenue, 2) ?></p>
                </div>
                <div class="bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Completed Revenue</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <i class="bi bi-check2-circle text-violet-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= $currency ?><?= number_format($completedRevenue, 2) ?></p>
                </div>
                <div class="bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Avg Order Value</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                            <i class="bi bi-receipt text-blue-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= $currency ?><?= number_format($avgOrder, 2) ?></p>
                </div>
                <div class="bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Total Orders</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <i class="bi bi-bag text-amber-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= $totalOrders ?></p>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-[10px] text-emerald-400"><?= $completedOrders ?> completed</span>
                        <span class="text-[10px] text-amber-400"><?= $pendingOrders ?> pending</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="px-8 pb-6">
            <div class="bg-zinc-900/40 border border-zinc-800/60 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-semibold">Revenue (Last 30 Days)</h3>
                    <span class="text-xs text-zinc-500"><?= count($chart_labels) ?> days with orders</span>
                </div>
                <?php if (empty($chart_data)): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-zinc-600">
                        <i class="bi bi-graph-up text-4xl mb-2"></i>
                        <p class="text-sm">No revenue data yet</p>
                        <p class="text-xs text-zinc-700 mt-1">Revenue will appear here once orders come in</p>
                    </div>
                <?php else: ?>
                    <div style="height: 280px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Methods + Banking -->
        <div class="px-8 pb-6">
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-1">
                <!-- Payment Methods -->
                <div class="lg:col-span-2 bg-zinc-900/40 border border-zinc-800/60 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-800/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <i class="bi bi-credit-card text-violet-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold">Payment Methods</h3>
                                <p class="text-xs text-zinc-500"><?= $active_methods ?> of 3 active</p>
                            </div>
                        </div>
                        <a href="settings?tab=payment" class="text-xs text-violet-400 hover:text-violet-300 transition-colors">Configure</a>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <!-- COD -->
                            <div class="rounded-xl p-4 border <?= $cod_enabled ? 'bg-emerald-500/[0.03] border-emerald-500/20' : 'bg-zinc-800/30 border-zinc-800/40 opacity-60' ?> transition-all">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg <?= $cod_enabled ? 'bg-emerald-500/15' : 'bg-zinc-700/40' ?> flex items-center justify-center">
                                        <i class="bi bi-cash-stack <?= $cod_enabled ? 'text-emerald-400' : 'text-zinc-500' ?> text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Cash on Delivery</p>
                                        <p class="text-[10px] text-zinc-500">Pay at doorstep</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $cod_enabled ? 'bg-emerald-400' : 'bg-zinc-600' ?>"></span>
                                    <span class="text-xs font-medium <?= $cod_enabled ? 'text-emerald-400' : 'text-zinc-500' ?>">
                                        <?= $cod_enabled ? 'Active' : 'Disabled' ?>
                                    </span>
                                </div>
                            </div>
                            <!-- YOCO -->
                            <div class="rounded-xl p-4 border <?= $yoco_enabled ? 'bg-violet-500/[0.03] border-violet-500/20' : 'bg-zinc-800/30 border-zinc-800/40 opacity-60' ?> transition-all">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg <?= $yoco_enabled ? 'bg-violet-500/15' : 'bg-zinc-700/40' ?> flex items-center justify-center">
                                        <i class="bi bi-credit-card-2-front <?= $yoco_enabled ? 'text-violet-400' : 'text-zinc-500' ?> text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">YOCO</p>
                                        <p class="text-[10px] text-zinc-500">Card payments</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $yoco_enabled ? 'bg-violet-400' : 'bg-zinc-600' ?>"></span>
                                    <span class="text-xs font-medium <?= $yoco_enabled ? 'text-violet-400' : 'text-zinc-500' ?>">
                                        <?= $yoco_enabled ? ($yoco_mode === 'live' ? 'Live' : 'Test Mode') : 'Disabled' ?>
                                    </span>
                                </div>
                            </div>
                            <!-- PayPal -->
                            <div class="rounded-xl p-4 border <?= $paypal_enabled ? 'bg-blue-500/[0.03] border-blue-500/20' : 'bg-zinc-800/30 border-zinc-800/40 opacity-60' ?> transition-all">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg <?= $paypal_enabled ? 'bg-blue-500/15' : 'bg-zinc-700/40' ?> flex items-center justify-center">
                                        <i class="bi bi-paypal <?= $paypal_enabled ? 'text-blue-400' : 'text-zinc-500' ?> text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">PayPal</p>
                                        <p class="text-[10px] text-zinc-500">Online payments</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $paypal_enabled ? 'bg-blue-400' : 'bg-zinc-600' ?>"></span>
                                    <span class="text-xs font-medium <?= $paypal_enabled ? 'text-blue-400' : 'text-zinc-500' ?>">
                                        <?= $paypal_enabled ? ($paypal_env === 'production' ? 'Live' : 'Sandbox') : 'Disabled' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Recent Orders -->
        <div class="px-8 pb-8">
            <div class="bg-zinc-900/40 border border-zinc-800/60 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-800/60 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center">
                            <i class="bi bi-receipt-cutoff text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold">Recent Orders</h3>
                            <p class="text-xs text-zinc-500">Last 10 transactions</p>
                        </div>
                    </div>
                    <a href="orders" class="text-xs text-violet-400 hover:text-violet-300 transition-colors">View all</a>
                </div>
                <?php if (empty($recent_orders)): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-zinc-600">
                        <div class="w-16 h-16 rounded-2xl bg-zinc-800/50 flex items-center justify-center mb-4">
                            <i class="bi bi-cart text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-400">No orders yet</h3>
                        <p class="text-sm text-zinc-600 mt-1">Orders will appear here once customers start purchasing</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-zinc-800/60">
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/40">
                                <?php foreach ($recent_orders as $order):
                                    $oc = $status_colors[$order['status']] ?? '#6b7280';
                                    $initials = strtoupper(substr($order['customer_name'], 0, 1));
                                ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-400"><?= $initials ?></div>
                                            <span class="text-sm font-medium"><?= htmlspecialchars($order['customer_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-zinc-500"><?= htmlspecialchars($order['customer_email'] ?? '-') ?></td>
                                    <td class="px-6 py-3 text-sm font-semibold"><?= $currency ?><?= number_format((float)$order['total_amount'], 2) ?></td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full" style="background: <?= $oc ?>15; color: <?= $oc ?>">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $oc ?>"></span>
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-zinc-500 text-right"><?= date('M d, H:i', strtotime($order['created_at'])) ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.color = '#52525b';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.04)';

    <?php if (!empty($chart_data)): ?>
    const ctx = document.getElementById('revenueChart')?.getContext('2d');
    if (ctx) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.15)');
        gradient.addColorStop(1, 'rgba(139, 92, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($chart_data) ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#8b5cf6',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#18181b',
                        borderColor: '#27272a',
                        borderWidth: 1,
                        titleColor: '#a1a1aa',
                        bodyColor: '#fff',
                        bodyFont: { weight: 'bold' },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: (ctx) => '<?= $currency ?>' + ctx.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.03)' },
                        ticks: {
                            font: { size: 10 },
                            callback: (v) => '<?= $currency ?>' + v.toLocaleString()
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
