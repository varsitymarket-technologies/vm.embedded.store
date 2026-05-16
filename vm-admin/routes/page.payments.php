<?php
#   TITLE   : Payments Dashboard
#   DESC    : Revenue tracking, payouts, and payment method status
#   VERSION : 2.0.0

$db = initiate_web_database();
$currency = __CURRENCY_SIGN__;
$domain = __DOMAIN__;

// --- Ensure payouts table exists ---
if ($db !== null) {
    try {
        $db->query("CREATE TABLE IF NOT EXISTS payouts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            amount REAL,
            status TEXT DEFAULT 'Pending',
            method TEXT DEFAULT 'Bank Transfer',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (\Throwable $e) {}
}

// --- Helper: safe query ---
function pq($db, $sql, $params = []) {
    if ($db === null) return [];
    try { return $db->query($sql, $params) ?: []; }
    catch (\Throwable $e) { return []; }
}

// --- Handle payout request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'withdraw' && $db !== null) {
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount > 0) {
            $db->query("INSERT INTO payouts (amount, status, method) VALUES (?, 'Pending', 'Bank Transfer')", [$amount]);
        }
        header("Location: ?payout_requested=1");
        exit;
    }
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

// --- Payout data ---
$payouts_data = pq($db, "SELECT * FROM payouts ORDER BY id DESC");
$totalPayouts = 0;
$pendingPayouts = 0;
$transactions = [];
foreach ($payouts_data as $p) {
    $pAmt = (float)$p['amount'];
    if ($p['status'] === 'Completed') $totalPayouts += $pAmt;
    if ($p['status'] === 'Pending') $pendingPayouts += $pAmt;
    $transactions[] = $p;
}
$balance = max(0, $grossRevenue - $totalPayouts);

// --- Revenue chart data (last 30 days from real orders) ---
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

// --- Recent orders for feed ---
$recent_orders = pq($db, "SELECT customer_name, customer_email, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 8");

// --- Banking details ---
$bank_name = defined('__BANKING_SERVICE__') ? __BANKING_SERVICE__ : '';
$bank_account = defined('__BANKING_ACCOUNT_NUMBER__') ? __BANKING_ACCOUNT_NUMBER__ : '';
$bank_type = defined('__BANKING_ACCOUNT_TYPE__') ? __BANKING_ACCOUNT_TYPE__ : '';

$status_colors = [
    'pending'    => '#f59e0b',
    'processing' => '#3b82f6',
    'completed'  => '#22c55e',
    'cancelled'  => '#ef4444',
    'Pending'    => '#f59e0b',
    'Completed'  => '#22c55e',
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-4 md:p-6">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-white">Payments</h1>
                <p class="text-sm text-gray-400 mt-1">Revenue, payouts, and payment gateway status.</p>
            </div>
            <a href="settings?tab=payment" class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-700 border border-white/10 text-white px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                <i class="bi bi-gear"></i> Payment Settings
            </a>
        </div>

        <?php if (isset($_GET['payout_requested'])): ?>
        <div id="payoutToast" class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-full text-sm font-bold mb-4 animate-bounce">
            <i class="bi bi-check2-circle text-lg"></i>
            <span>Payout request submitted successfully</span>
        </div>
        <script>setTimeout(() => document.getElementById('payoutToast')?.remove(), 5000);</script>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-cash-stack text-emerald-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= $currency ?><?= number_format($grossRevenue, 2) ?></p>
                <p class="text-xs text-gray-500 mt-1">Gross Revenue</p>
            </div>
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-receipt text-blue-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= $currency ?><?= number_format($avgOrder, 2) ?></p>
                <p class="text-xs text-gray-500 mt-1">Avg Order Value</p>
            </div>
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-arrow-up-right text-purple-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= $currency ?><?= number_format($totalPayouts, 2) ?></p>
                <p class="text-xs text-gray-500 mt-1">Total Payouts</p>
            </div>
            <div class="rounded-xl bg-gray-800 p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-wallet2 text-amber-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white"><?= $currency ?><?= number_format($balance, 2) ?></p>
                <p class="text-xs text-gray-500 mt-1">Available Balance</p>
            </div>
        </div>

        <!-- Revenue Chart + Payout Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <div class="lg:col-span-2 rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Revenue (Last 30 Days)</h3>
                <?php if (empty($chart_data)): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-600">
                        <i class="bi bi-graph-up text-4xl mb-2"></i>
                        <p class="text-sm">No revenue data yet</p>
                    </div>
                <?php else: ?>
                    <div style="height: 260px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payout Card -->
            <div class="rounded-xl bg-gradient-to-b from-purple-600/20 to-gray-800 border border-purple-500/20 p-6 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-purple-300 text-xs font-bold uppercase tracking-wide">Available to Payout</p>
                    <h2 class="text-4xl font-black text-white mt-2 mb-6"><?= $currency ?><?= number_format($balance, 2) ?></h2>

                    <?php if ($pendingPayouts > 0): ?>
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg px-3 py-2 mb-4">
                        <p class="text-amber-400 text-xs font-bold"><i class="bi bi-clock"></i> <?= $currency ?><?= number_format($pendingPayouts, 2) ?> pending payout</p>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="withdraw">
                        <div>
                            <label class="text-[10px] uppercase text-purple-300 font-bold">Withdrawal Amount</label>
                            <input type="number" step="0.01" name="amount" id="withdrawAmount"
                                class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-purple-400 transition-all mt-1"
                                placeholder="0.00" max="<?= $balance ?>">
                        </div>
                        <button type="submit" id="payoutBtn"
                                class="w-full bg-white text-black font-bold py-3 rounded-xl hover:scale-[1.02] active:scale-[0.98] disabled:opacity-30 transition-all text-sm">
                            Request Instant Payout
                        </button>
                    </form>
                </div>
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>
        </div>

        <!-- Payment Methods Status + Banking Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Active Payment Methods -->
            <div class="lg:col-span-2 rounded-xl bg-gray-800 border border-white/5 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wide">Payment Methods</h3>
                    <a href="settings?tab=payment" class="text-xs text-purple-400 hover:underline">Configure</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- COD -->
                    <div class="bg-white/5 rounded-xl p-4 border <?= $cod_enabled ? 'border-emerald-500/30' : 'border-white/5 opacity-50' ?>">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 bg-emerald-500/20 rounded-lg flex items-center justify-center">
                                <i class="bi bi-cash-stack text-emerald-400"></i>
                            </div>
                            <div>
                                <p class="text-white text-sm font-bold">Cash on Delivery</p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $cod_enabled ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-500/20 text-gray-400' ?>">
                            <?= $cod_enabled ? 'Active' : 'Disabled' ?>
                        </span>
                    </div>
                    <!-- YOCO -->
                    <div class="bg-white/5 rounded-xl p-4 border <?= $yoco_enabled ? 'border-purple-500/30' : 'border-white/5 opacity-50' ?>">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 bg-purple-500/20 rounded-lg flex items-center justify-center">
                                <i class="bi bi-credit-card-2-front text-purple-400"></i>
                            </div>
                            <div>
                                <p class="text-white text-sm font-bold">YOCO</p>
                            </div>
                        </div>
                        <?php if ($yoco_enabled): ?>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-400">
                                <?= $yoco_mode === 'live' ? 'Live' : 'Test Mode' ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-500/20 text-gray-400">Disabled</span>
                        <?php endif; ?>
                    </div>
                    <!-- PayPal -->
                    <div class="bg-white/5 rounded-xl p-4 border <?= $paypal_enabled ? 'border-blue-500/30' : 'border-white/5 opacity-50' ?>">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                <i class="bi bi-paypal text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-white text-sm font-bold">PayPal</p>
                            </div>
                        </div>
                        <?php if ($paypal_enabled): ?>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400">
                                <?= $paypal_env === 'production' ? 'Live' : 'Sandbox' ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-500/20 text-gray-400">Disabled</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Banking Details -->
            <div class="rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Payout Destination</h3>
                <div class="space-y-3">
                    <div class="p-3 bg-black/20 border border-white/5 rounded-xl">
                        <label class="text-[10px] text-gray-500 uppercase">Bank / Provider</label>
                        <p class="font-medium text-white text-sm"><?= !empty($bank_name) ? htmlspecialchars($bank_name) : 'Not Linked' ?></p>
                    </div>
                    <div class="p-3 bg-black/20 border border-white/5 rounded-xl">
                        <label class="text-[10px] text-gray-500 uppercase">Account Number</label>
                        <p class="font-mono text-sm text-white">
                            <?php if (!empty($bank_account)): ?>
                                <?= str_repeat('*', max(0, strlen($bank_account) - 4)) . substr($bank_account, -4) ?>
                            <?php else: ?>
                                Not configured
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if (!empty($bank_type)): ?>
                    <div class="p-3 bg-black/20 border border-white/5 rounded-xl">
                        <label class="text-[10px] text-gray-500 uppercase">Account Type</label>
                        <p class="text-sm text-white"><?= htmlspecialchars($bank_type) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Transactions + Recent Orders -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Payout Transactions -->
            <div class="lg:col-span-2 rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                <div class="p-5 border-b border-white/5">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wide">Payout History</h3>
                </div>
                <?php if (empty($transactions)): ?>
                    <div class="flex flex-col items-center justify-center py-12 text-gray-600">
                        <i class="bi bi-clock-history text-4xl mb-2"></i>
                        <p class="text-sm">No payouts yet</p>
                        <p class="text-xs text-gray-700 mt-1">Request a payout from your available balance</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase text-gray-400">
                                <tr>
                                    <th class="px-5 py-3">ID</th>
                                    <th class="px-5 py-3">Date</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Method</th>
                                    <th class="px-5 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($transactions as $tx):
                                    $sc = $status_colors[$tx['status']] ?? '#6b7280';
                                ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-5 py-3 font-mono text-xs text-gray-400">TXN-<?= str_pad($tx['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td class="px-5 py-3 text-xs text-gray-400"><?= date('M d, Y', strtotime($tx['created_at'])) ?></td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold" style="background: <?= $sc ?>20; color: <?= $sc ?>">
                                            <?= htmlspecialchars($tx['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-300"><?= htmlspecialchars($tx['method']) ?></td>
                                    <td class="px-5 py-3 text-right font-bold text-white"><?= $currency ?><?= number_format((float)$tx['amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Orders -->
            <div class="rounded-xl bg-gray-800 border border-white/5 p-5">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide mb-4">Recent Orders</h3>
                <?php if (empty($recent_orders)): ?>
                    <div class="flex flex-col items-center justify-center py-8 text-gray-600">
                        <i class="bi bi-cart text-3xl mb-2"></i>
                        <p class="text-sm">No orders yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_orders as $order):
                            $oc = $status_colors[$order['status']] ?? '#6b7280';
                        ?>
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-sm text-white font-medium truncate"><?= htmlspecialchars($order['customer_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= date('M d, H:i', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="text-sm text-white font-mono"><?= $currency ?><?= number_format((float)$order['total_amount'], 2) ?></p>
                                <span class="text-xs px-2 py-0.5 rounded-full" style="background: <?= $oc ?>20; color: <?= $oc ?>"><?= ucfirst($order['status']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.color = '#6b7280';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';

    <?php if (!empty($chart_data)): ?>
    const ctx = document.getElementById('revenueChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($chart_data) ?>,
                    borderColor: '#a855f7',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    borderWidth: 2
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
                        ticks: { callback: (v) => '<?= $currency ?>' + v }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Payout form validation
    const withdrawInput = document.getElementById('withdrawAmount');
    const payoutBtn = document.getElementById('payoutBtn');
    if (withdrawInput && payoutBtn) {
        const maxBalance = <?= (float)$balance ?>;
        withdrawInput.addEventListener('input', () => {
            const val = parseFloat(withdrawInput.value) || 0;
            payoutBtn.disabled = val <= 0 || val > maxBalance;
        });
        payoutBtn.disabled = true;

        payoutBtn.closest('form').addEventListener('submit', (e) => {
            const val = parseFloat(withdrawInput.value) || 0;
            if (val <= 0 || val > maxBalance) {
                e.preventDefault();
                return;
            }
            payoutBtn.disabled = true;
            payoutBtn.textContent = 'Processing...';
        });
    }
});
</script>
