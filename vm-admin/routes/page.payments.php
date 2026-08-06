<?php
#   TITLE   : Payments Dashboard
#   DESC    : Revenue tracking, payment gateway status, and payment request launcher
#   VERSION : 3.1.0

$db = initiate_web_database();
$currency = defined('__CURRENCY_SIGN__') ? __CURRENCY_SIGN__ : 'R';
$domain = __DOMAIN__;
$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$site_store_id = __STORE_INDEX__ ?? '';
$api_base_url = __SYSTEM_API__ ?? '';
$api_key = __SYSTEM_API_KEYS__ ?? '';

// --- Helper: safe query ---
function pq($db, $sql, $params = [])
{
    if ($db === null)
        return [];
    try {
        return $db->query($sql, $params) ?: [];
    } catch (\Throwable $e) {
        return [];
    }
}

// --- Load payment config from settings ---
$config_key = function_exists('create_enc_key') ? create_enc_key() : null;
$payment_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/payment.config.enc";
$payment_config = [];
if ($config_key && function_exists('__decryption__') && file_exists($payment_path)) {
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

$payment_methods = array_values(array_filter([
    [
        'enabled' => $cod_enabled,
        'title' => 'Cash on Delivery',
        'subtitle' => 'Pay at doorstep',
        'icon' => 'bi-cash-stack',
        'badge' => 'Active',
        'status_class' => 'text-emerald-400',
        'dot_class' => 'bg-emerald-400',
        'card_class' => 'bg-emerald-500/[0.03] border-emerald-500/20',
        'icon_bg' => 'bg-emerald-500/15',
        'icon_class' => 'text-emerald-400',
    ],
    [
        'enabled' => $yoco_enabled,
        'title' => 'YOCO',
        'subtitle' => 'Card payments',
        'icon' => 'bi-credit-card-2-front',
        'badge' => $yoco_mode === 'live' ? 'Live' : 'Test Mode',
        'status_class' => 'text-violet-400',
        'dot_class' => 'bg-violet-400',
        'card_class' => 'bg-violet-500/[0.03] border-violet-500/20',
        'icon_bg' => 'bg-violet-500/15',
        'icon_class' => 'text-violet-400',
    ],
    [
        'enabled' => $paypal_enabled,
        'title' => 'PayPal',
        'subtitle' => 'Online payments',
        'icon' => 'bi-paypal',
        'badge' => $paypal_env === 'production' ? 'Live' : 'Sandbox',
        'status_class' => 'text-blue-400',
        'dot_class' => 'bg-blue-400',
        'card_class' => 'bg-blue-500/[0.03] border-blue-500/20',
        'icon_bg' => 'bg-blue-500/15',
        'icon_class' => 'text-blue-400',
    ],
], fn($method) => !empty($method['enabled'])));

$sample_product = [];
try {
    $sample_product = $db->query("SELECT id, name, price FROM products ORDER BY id ASC LIMIT 1") ?: [];
} catch (\Throwable $e) {
    $sample_product = [];
}
$sample_product = $sample_product[0] ?? [];
$sample_product_id = (int) ($sample_product['id'] ?? 0);
$sample_product_name = $sample_product['name'] ?? 'Sample Product';
$sample_product_price = (float) ($sample_product['price'] ?? 0);

// --- Stats from orders ---
$orders = pq($db, "SELECT * FROM orders");
$grossRevenue = 0;
$completedRevenue = 0;
$totalOrders = 0;
$completedOrders = 0;
$pendingOrders = 0;
foreach ($orders as $o) {
    $amt = (float) ($o['total_amount'] ?? 0);
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
$revenue_by_day = pq(
    $db,
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
    'pending' => '#f59e0b',
    'processing' => '#3b82f6',
    'completed' => '#22c55e',
    'cancelled' => '#ef4444',
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#252526]  text-zinc-100 font-sans">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto">
        <!-- Header -->
        <div class="px-8 pt-8 pb-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Payments</h2>
                    <p class="text-sm text-zinc-500 mt-1">Revenue tracking and payment gateway status</p>
                </div>
                <a href="settings?tab=payment"
                    class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="bi bi-gear"></i> Payment Settings
                </a>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="px-8 pb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-[#252526]/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Gross Revenue</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <i class="bi bi-cash-stack text-emerald-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= $currency ?><?= number_format($grossRevenue, 2) ?></p>
                </div>
                <div
                    class="bg-[#252526]/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Completed Revenue</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <i class="bi bi-check2-circle text-violet-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= $currency ?><?= number_format($completedRevenue, 2) ?></p>
                </div>
                <div
                    class="bg-[#252526]/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Avg Order Value</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                            <i class="bi bi-receipt text-blue-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?= $currency ?><?= number_format($avgOrder, 2) ?></p>
                </div>
                <div
                    class="bg-[#252526]/60 border border-zinc-800/60 rounded-xl p-4 hover:bg-white/[0.04] transition-colors">
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

        <!-- Payment Request Launcher -->
        <div class="px-8 pb-6">
            <div class="bg-[#252526]/40 border border-zinc-800/60 rounded-2xl p-6">
                <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Launch Payment Request</h3>
                        <p class="text-xs text-zinc-500 mt-1">Creates a checkout session through the enabled payment methods and returns here when the payment completes.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-zinc-500">
                        <span class="rounded-full border border-zinc-700 px-3 py-1">Store #<?php echo htmlspecialchars((string) $site_store_id, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="rounded-full border border-zinc-700 px-3 py-1"><?php echo htmlspecialchars($sample_product_name, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    <?php foreach ($payment_methods as $method): ?>
                        <button
                            type="button"
                            class="payment-launch-btn rounded-xl border <?= $method['card_class'] ?> bg-[#1b1b1c] px-4 py-4 text-left transition-colors hover:border-zinc-600 hover:bg-white/[0.02]"
                            data-method="<?php echo htmlspecialchars($method['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg <?= $method['icon_bg'] ?> flex items-center justify-center shrink-0">
                                        <i class="bi <?= $method['icon'] ?> <?= $method['icon_class'] ?> text-lg"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-white truncate"><?php echo htmlspecialchars($method['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="text-[10px] text-zinc-500 truncate"><?php echo htmlspecialchars($method['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                                <span class="text-[10px] uppercase tracking-wider <?= $method['status_class'] ?> whitespace-nowrap"><?php echo htmlspecialchars($method['badge'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($payment_methods)): ?>
                    <div class="mt-5 rounded-xl border border-zinc-800 bg-[#1b1b1c] p-4 text-sm text-zinc-500">
                        Enable at least one payment method in Payment Settings to launch a checkout request here.
                    </div>
                <?php endif; ?>

                <div id="paymentLaunchState" class="mt-4 hidden rounded-xl border border-violet-500/20 bg-violet-500/10 px-4 py-3 text-sm text-violet-300"></div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="px-8 pb-6">
            <div class="bg-[#252526]/40 border border-zinc-800/60 rounded-2xl p-6">
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
                <div class="lg:col-span-2 bg-[#252526]/40 border border-zinc-800/60 rounded-2xl overflow-hidden">
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
                        <a href="settings?tab=payment"
                            class="text-xs text-violet-400 hover:text-violet-300 transition-colors">Configure</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($payment_methods)): ?>
                            <div class="flex flex-col items-center justify-center py-12 text-zinc-600">
                                <i class="bi bi-credit-card-2-back text-4xl mb-3"></i>
                                <h4 class="text-sm font-semibold text-zinc-400">No active payment methods</h4>
                                <p class="text-xs text-zinc-500 mt-1">Enable a gateway in Payment Settings to show it here.
                                </p>
                            </div>
                        <?php else: ?>
                            <div
                                class="grid grid-cols-1 md:grid-cols-<?= min(3, max(1, count($payment_methods))); ?> gap-3">
                                <?php foreach ($payment_methods as $method): ?>
                                    <div class="rounded-xl p-4 border <?= $method['card_class'] ?> transition-all">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div
                                                class="w-10 h-10 rounded-lg <?= $method['icon_bg'] ?> flex items-center justify-center">
                                                <i class="bi <?= $method['icon'] ?> <?= $method['icon_class'] ?> text-lg"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold"><?= htmlspecialchars($method['title']); ?></p>
                                                <p class="text-[10px] text-zinc-500">
                                                    <?= htmlspecialchars($method['subtitle']); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full <?= $method['dot_class'] ?>"></span>
                                            <span class="text-xs font-medium <?= $method['status_class'] ?>">
                                                <?= htmlspecialchars($method['badge']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Recent Orders -->
        <div class="px-8 pb-8">
            <div class="bg-[#252526]/40 border border-zinc-800/60 rounded-2xl overflow-hidden">
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
                    <a href="orders" class="text-xs text-violet-400 hover:text-violet-300 transition-colors">View
                        all</a>
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
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">
                                        Customer</th>
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">
                                        Email</th>
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">
                                        Amount</th>
                                    <th class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-[10px] uppercase text-zinc-500 font-semibold tracking-wider text-right">
                                        Date</th>
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
                                                <div
                                                    class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-400">
                                                    <?= $initials ?></div>
                                                <span
                                                    class="text-sm font-medium"><?= htmlspecialchars($order['customer_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-zinc-500">
                                            <?= htmlspecialchars($order['customer_email'] ?? '-') ?></td>
                                        <td class="px-6 py-3 text-sm font-semibold">
                                            <?= $currency ?>        <?= number_format((float) $order['total_amount'], 2) ?></td>
                                        <td class="px-6 py-3">
                                            <span
                                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                                                style="background: <?= $oc ?>15; color: <?= $oc ?>">
                                                <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $oc ?>"></span>
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-zinc-500 text-right">
                                            <?= date('M d, H:i', strtotime($order['created_at'])) ?></td>
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
    const paymentApiBase = <?php echo json_encode($api_base_url); ?>;
    const paymentApiKey = <?php echo json_encode($api_key); ?>;
    const paymentReturnUrl = <?php echo json_encode($admin_base . 'payments?status=complete'); ?>;
    const sampleProductId = <?php echo json_encode($sample_product_id); ?>;
    const paymentButtons = document.querySelectorAll('.payment-launch-btn');
    const paymentState = document.getElementById('paymentLaunchState');

    function setPaymentState(message, visible = true) {
        if (!paymentState) return;
        paymentState.textContent = message;
        paymentState.classList.toggle('hidden', !visible);
    }

    async function requestJson(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: {
                'X-API-Key': paymentApiKey,
                'Content-Type': 'application/json',
                ...(options.headers || {})
            }
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.error || 'Payment request failed');
        }
        return data;
    }

    async function launchPaymentRequest(methodName) {
        if (!paymentApiBase || !paymentApiKey) {
            setPaymentState('Payment API credentials are not available on this store.');
            return;
        }
        if (!sampleProductId) {
            setPaymentState('Add at least one product before launching a payment request.');
            return;
        }

        try {
            paymentButtons.forEach(btn => btn.disabled = true);
            setPaymentState('Creating checkout session for ' + methodName + '...');

            const cart = await requestJson(paymentApiBase + '?state=cart_create', {
                method: 'POST',
                body: JSON.stringify({})
            });
            const cartId = cart?.data?.cart_id;
            if (!cartId) throw new Error('Cart could not be created');

            await requestJson(paymentApiBase + '?state=cart_add', {
                method: 'POST',
                body: JSON.stringify({
                    cart_id: cartId,
                    product_id: sampleProductId,
                    quantity: 1
                })
            });

            setPaymentState('Redirecting to checkout...');
            const checkout = await requestJson(paymentApiBase + '?state=checkout_create', {
                method: 'POST',
                body: JSON.stringify({
                    cart_id: cartId,
                    return_url: paymentReturnUrl
                })
            });

            const checkoutUrl = checkout?.data?.checkout_url;
            if (!checkoutUrl) throw new Error('Checkout URL missing from response');

            window.location.href = checkoutUrl;
        } catch (error) {
            console.error(error);
            setPaymentState(error.message || 'Unable to launch payment request.');
        } finally {
            paymentButtons.forEach(btn => btn.disabled = false);
        }
    }

    paymentButtons.forEach(btn => {
        btn.addEventListener('click', () => launchPaymentRequest(btn.dataset.method || 'Payment'));
    });

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
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#18181b',
                                borderColor: '#27272a',
                                borderWidth: 1,
                                titleColor: '#a1a1aa',
                                bodyColor: '#fff',
                                bodyFont: {
                                    weight: 'bold'
                                },
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
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 10
                                    },
                                    maxRotation: 0
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255,255,255,0.03)'
                                },
                                ticks: {
                                    font: {
                                        size: 10
                                    },
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
