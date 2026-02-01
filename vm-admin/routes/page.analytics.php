<?php
$db = __DB_MODULE__;

// Helper to get setting
function get_setting($db, $key, $default = '') {
    $result = $db->query("SELECT value FROM settings WHERE key = ?", [$key]);
    return $result && isset($result[0]['value']) && !empty($result[0]['value']) ? $result[0]['value'] : $default;
}

// --- Data Fetching ---
$currency_symbol = get_setting($db, 'currency_symbol', '$');

// Stat Cards Data
$total_revenue_result = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
$total_revenue = $total_revenue_result[0]['total'] ?? 0;

$total_orders_result = $db->query("SELECT COUNT(id) as total FROM orders");
$total_orders = $total_orders_result[0]['total'] ?? 0;

$total_products_result = $db->query("SELECT COUNT(id) as total FROM products");
$total_products = $total_products_result[0]['total'] ?? 0;

$total_users_result = $db->query("SELECT COUNT(id) as total FROM users");
$total_users = $total_users_result[0]['total'] ?? 0;

// Sales Over Time Chart Data (last 30 days)
$sales_over_time = $db->query("
    SELECT 
        strftime('%Y-%m-%d', created_at) as date, 
        SUM(total_amount) as sales 
    FROM orders 
    WHERE status = 'completed' AND created_at >= date('now', '-30 days')
    GROUP BY date 
    ORDER BY date ASC
");

$sales_labels = [];
$sales_data = [];
foreach ($sales_over_time as $row) {
    $sales_labels[] = date('M d', strtotime($row['date']));
    $sales_data[] = $row['sales'];
}

// Order Status Chart Data
$order_status_data = $db->query("SELECT status, COUNT(id) as count FROM orders GROUP BY status");

$status_labels = [];
$status_counts = [];
$status_colors = [];
$color_map = [
    'pending'    => '#f59e0b', // text-yellow-500
    'processing' => '#3b82f6', // text-blue-500
    'completed'  => '#22c55e', // text-green-500
    'cancelled'  => '#ef4444', // text-red-500
];

foreach ($order_status_data as $row) {
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = $row['count'];
    $status_colors[] = $color_map[$row['status']] ?? '#6b7280'; // default to gray
}
?>

<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden">
    <!-- Header -->
    <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10">
        <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
            <i class="bi bi-list text-2xl"></i>
        </button>
        <h2 class="text-lg font-semibold text-white">Analytics</h2>
        <div class="flex items-center gap-4 ml-auto">
            <button class="relative text-gray-400 hover:text-white">
                <i class="bi bi-bell text-xl"></i>
                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-purple-500"></span>
            </button>
            <div class="relative group">
                <button class="flex items-center gap-2 text-sm font-medium text-white focus:outline-none">
                    <div class="h-8 w-8 rounded-full bg-purple-600 flex items-center justify-center">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span class="hidden md:block">Admin</span>
                    <i class="bi bi-chevron-down text-xs text-gray-400"></i>
                </button>
                <div class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block border border-white/10">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profile</a>
                    <a href="settings" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Settings</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Sign out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Scrollable Area -->
    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4 mb-8">
            <div class="rounded-xl bg-gray-800 p-6 border border-white/5">
                <p class="text-sm font-medium text-gray-400">Total Revenue</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo htmlspecialchars($currency_symbol); ?><?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="rounded-xl bg-gray-800 p-6 border border-white/5">
                <p class="text-sm font-medium text-gray-400">Total Orders</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo number_format($total_orders); ?></p>
            </div>
            <div class="rounded-xl bg-gray-800 p-6 border border-white/5">
                <p class="text-sm font-medium text-gray-400">Total Products</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo number_format($total_products); ?></p>
            </div>
            <div class="rounded-xl bg-gray-800 p-6 border border-white/5">
                <p class="text-sm font-medium text-gray-400">Total Users</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo number_format($total_users); ?></p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Sales Over Time Chart -->
            <div class="lg:col-span-3 rounded-xl bg-gray-800 border border-white/5 p-6">
                <h3 class="text-lg font-semibold mb-4 text-white">Sales (Last 30 Days)</h3>
                <canvas id="salesChart"></canvas>
            </div>

            <!-- Order Status Chart -->
            <div class="lg:col-span-2 rounded-xl bg-gray-800 border border-white/5 p-6">
                <h3 class="text-lg font-semibold mb-4 text-white">Order Status</h3>
                <div class="max-w-xs mx-auto mt-8">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Chart Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.color = '#9ca3af'; // text-gray-400
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

    // Sales Chart
    const salesCtx = document.getElementById('salesChart')?.getContext('2d');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode($sales_data); ?>,
                    borderColor: '#7a1aab',
                    backgroundColor: 'rgba(122, 26, 171, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#7a1aab',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '<?php echo htmlspecialchars($currency_symbol); ?>' + value
                        }
                    }
                }
            }
        });
    }

    // Order Status Chart
    const statusCtx = document.getElementById('orderStatusChart')?.getContext('2d');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: <?php echo json_encode($status_colors); ?>,
                    borderColor: '#1a1a1a', // bg-gray-800
                    borderWidth: 4,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 20 }
                    }
                }
            }
        });
    }
});
</script>