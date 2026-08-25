<?php
#   TITLE   : Payments Dashboard
#   DESC    : Revenue tracking, payment gateway status, and payment request launcher
#   VERSION : 3.1.0

$db = initiate_web_database();
$currency = defined('__CURRENCY_SIGN__') ? __CURRENCY_SIGN__ : 'R';
$domain = defined('__DOMAIN__') ? __DOMAIN__ : '';
$admin_base = '/vm-admin/' . $domain . '/';
$site_store_id = defined('__STORE_INDEX__') ? __STORE_INDEX__ : '';
$api_base_url = defined('__SYSTEM_API__') ? __SYSTEM_API__ : '';
$api_key = defined('__SYSTEM_API_KEYS__') ? __SYSTEM_API_KEYS__ : '';

function pq($db, $sql, $params = [])
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

$orders = pq($db, "SELECT * FROM orders");
$grossRevenue = 0;
$completedRevenue = 0;
$totalOrders = 0;
$completedOrders = 0;
$pendingOrders = 0;
foreach ($orders as $o) {
    $amt = (float) ($o['total_amount'] ?? 0);
    if (($o['status'] ?? '') !== 'cancelled') {
        $grossRevenue += $amt;
        $totalOrders++;
    }
    if (($o['status'] ?? '') === 'completed') {
        $completedRevenue += $amt;
        $completedOrders++;
    }
    if (($o['status'] ?? '') === 'pending') {
        $pendingOrders++;
    }
}
$avgOrder = $totalOrders > 0 ? $grossRevenue / $totalOrders : 0;

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

$recent_orders = pq($db, "SELECT customer_name, customer_email, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 10");

$status_colors = [
    'pending' => '#f59e0b',
    'processing' => '#3b82f6',
    'completed' => '#22c55e',
    'cancelled' => '#ef4444',
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#1b1b1c] text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden">
        <section
            class="relative mx-8 mt-8 overflow-hidden rounded-[28px] border border-white/10 bg-[#252526] shadow-[0_24px_80px_rgba(0,0,0,0.28)]">

            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)] lg:p-8">
                <div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Revenue, gateways, and
                        checkout control in one place.</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400">
                        Monitor store revenue, confirm which payment methods are active, and launch a test checkout when
                        you need to verify the flow.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="settings?tab=payment"
                            class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                            <i class="bi bi-gear"></i>
                            <span>Payment settings</span>
                        </a>
                        <a href="orders"
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            <i class="bi bi-bag"></i>
                            <span>View orders</span>
                        </a>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Gross revenue</p>
                        <p class="mt-2 text-3xl font-semibold text-white">
                            <?= $currency ?>
                            <?= number_format($grossRevenue, 2) ?>
                        </p>
                        <p class="mt-1 text-sm text-zinc-400">All non-cancelled orders</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Completed</p>
                        <p class="mt-2 text-3xl font-semibold text-white">
                            <?= $currency ?>
                            <?= number_format($completedRevenue, 2) ?>
                        </p>
                        <p class="mt-1 text-sm text-zinc-400">
                            <?= $completedOrders ?> successful payments
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Average order</p>
                        <p class="mt-2 text-3xl font-semibold text-white">
                            <?= $currency ?>
                            <?= number_format($avgOrder, 2) ?>
                        </p>
                        <p class="mt-1 text-sm text-zinc-400">
                            <?= $totalOrders ?> orders tracked
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="px-8 pb-6 pt-6">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div
                    class="rounded-2xl border border-white/10 bg-[#252526] p-4 transition-colors hover:bg-white/[0.04]">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500">Gross Revenue</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10">
                            <i class="bi bi-cash-stack text-sm text-emerald-400"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white"><?= $currency ?><?= number_format($grossRevenue, 2) ?></p>
                </div>
                <div
                    class="rounded-2xl border border-white/10 bg-[#252526] p-4 transition-colors hover:bg-white/[0.04]">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500">Completed Revenue</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/10">
                            <i class="bi bi-check2-circle text-sm text-violet-400"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white"><?= $currency ?><?= number_format($completedRevenue, 2) ?>
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-white/10 bg-[#252526] p-4 transition-colors hover:bg-white/[0.04]">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500">Avg Order Value</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500/10">
                            <i class="bi bi-receipt text-sm text-blue-400"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white"><?= $currency ?><?= number_format($avgOrder, 2) ?></p>
                </div>
                <div
                    class="rounded-2xl border border-white/10 bg-[#252526] p-4 transition-colors hover:bg-white/[0.04]">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500">Total Orders</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10">
                            <i class="bi bi-bag text-sm text-amber-400"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-white"><?= $totalOrders ?></p>
                    <div class="mt-1 flex items-center gap-3">
                        <span class="text-[10px] text-emerald-400"><?= $completedOrders ?> completed</span>
                        <span class="text-[10px] text-amber-400"><?= $pendingOrders ?> pending</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-8 pb-6">
            <div class="rounded-2xl border border-white/10 bg-[#252526] p-6 shadow-[0_18px_45px_rgba(0,0,0,0.22)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Revenue trend</p>
                        <h3 class="mt-1 text-lg font-semibold text-white">Revenue (Last 30 Days)</h3>
                    </div>
                    <span class="text-xs text-zinc-500"><?= count($chart_labels) ?> days with orders</span>
                </div>
                <?php if (empty($chart_data)): ?>
                    <div class="flex h-48 flex-col items-center justify-center text-zinc-600">
                        <i class="bi bi-graph-up text-4xl mb-2"></i>
                        <p class="text-sm">No revenue data yet</p>
                        <p class="mt-1 text-xs text-zinc-500">Revenue will appear here once orders come in</p>
                    </div>
                <?php else: ?>
                    <div style="height: 280px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="px-8 pb-6">
            <div
                class="overflow-hidden rounded-2xl border border-white/10 bg-[#252526] shadow-[0_18px_45px_rgba(0,0,0,0.22)]">
                <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-500/10">
                            <i class="bi bi-credit-card text-violet-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Payment Methods</h3>
                            <p class="text-xs text-zinc-500"><?= $active_methods ?> of 3 active</p>
                        </div>
                    </div>
                    <a href="settings?tab=payment"
                        class="text-xs text-violet-400 transition-colors hover:text-violet-300">Configure</a>
                </div>
                <div class="p-6">
                    <?php if (empty($payment_methods)): ?>
                        <div
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-white/10 py-12 text-zinc-600">
                            <i class="bi bi-credit-card-2-back text-4xl mb-3"></i>
                            <h4 class="text-sm font-semibold text-zinc-300">No active payment methods</h4>
                            <p class="mt-1 text-xs text-zinc-500">Enable a gateway in Payment Settings to show it here.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-<?= min(3, max(1, count($payment_methods))); ?>">
                            <?php foreach ($payment_methods as $method): ?>
                                <div class="rounded-2xl border <?= $method['card_class'] ?> bg-[#252526] p-4 transition-all">
                                    <div class="mb-3 flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg <?= $method['icon_bg'] ?>">
                                            <i class="bi <?= $method['icon'] ?> <?= $method['icon_class'] ?> text-lg"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-white">
                                                <?= htmlspecialchars($method['title']); ?>
                                            </p>
                                            <p class="text-[10px] text-zinc-500"><?= htmlspecialchars($method['subtitle']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="px-8 pb-8">
            <div
                class="overflow-hidden rounded-2xl border border-white/10 bg-[#202123] shadow-[0_18px_45px_rgba(0,0,0,0.22)]">
                <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/10">
                            <i class="bi bi-receipt-cutoff text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Recent Orders</h3>
                            <p class="text-xs text-zinc-500">Last 10 transactions</p>
                        </div>
                    </div>
                    <a href="orders" class="text-xs text-violet-400 transition-colors hover:text-violet-300">View
                        all</a>
                </div>
                <?php if (empty($recent_orders)): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-zinc-600">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5">
                            <i class="bi bi-cart text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-zinc-300">No orders yet</h3>
                        <p class="mt-1 text-sm text-zinc-500">Orders will appear here once customers start purchasing</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-500">
                                        Customer</th>
                                    <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-500">
                                        Email</th>
                                    <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-500">
                                        Amount</th>
                                    <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-500">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-[10px] font-semibold uppercase tracking-wider text-right text-zinc-500">
                                        Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <?php foreach ($recent_orders as $order):
                                    $oc = $status_colors[$order['status']] ?? '#6b7280';
                                    $initials = strtoupper(substr($order['customer_name'], 0, 1));
                                    ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/5 text-xs font-bold text-zinc-300">
                                                    <?= $initials ?>
                                                </div>
                                                <span
                                                    class="text-sm font-medium text-white"><?= htmlspecialchars($order['customer_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-zinc-400">
                                            <?= htmlspecialchars($order['customer_email'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-semibold text-white"><?= $currency ?>
                                            <?= number_format((float) $order['total_amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                                style="background: <?= $oc ?>15; color: <?= $oc ?>">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background: <?= $oc ?>"></span>
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm text-zinc-400">
                                            <?= date('M d, H:i', strtotime($order['created_at'])) ?>
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
        Chart.defaults.color = '#a1a1aa';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';

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
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(0,0,0,0)',
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
                                backgroundColor: '#111827',
                                borderColor: 'rgba(255,255,255,0.08)',
                                borderWidth: 1,
                                titleColor: '#e4e4e7',
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
                                    color: 'rgba(255,255,255,0.05)'
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