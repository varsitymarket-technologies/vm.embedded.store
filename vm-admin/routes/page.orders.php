<?php
$db = initiate_web_database();

// Check if optional columns exist
$hasPhoneCol = false;
$hasAddressCol = false;
$hasItemsCol = false;
try {
    $colCheck = $db->query("PRAGMA table_info(orders)");
    foreach ($colCheck as $col) {
        if ($col['name'] === 'customer_phone') $hasPhoneCol = true;
        if ($col['name'] === 'customer_address') $hasAddressCol = true;
        if ($col['name'] === 'items') $hasItemsCol = true;
    }
} catch (Exception $e) {}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_order') {
        $customer_name = $_POST['customer_name'] ?? '';
        $customer_email = $_POST['customer_email'] ?? '';
        $customer_phone = $_POST['customer_phone'] ?? '';
        $customer_address = $_POST['customer_address'] ?? '';
        $total_amount = $_POST['total_amount'] ?? 0;
        $status = $_POST['status'] ?? 'pending';

        $fields = 'customer_name, customer_email, total_amount, status';
        $placeholders = '?, ?, ?, ?';
        $params = [$customer_name, $customer_email, $total_amount, $status];

        if ($hasPhoneCol) {
            $fields .= ', customer_phone';
            $placeholders .= ', ?';
            $params[] = $customer_phone;
        }
        if ($hasAddressCol) {
            $fields .= ', customer_address';
            $placeholders .= ', ?';
            $params[] = $customer_address;
        }

        $sql = "INSERT INTO orders ($fields) VALUES ($placeholders)";
        $db->query($sql, $params);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_order') {
        $id = $_POST['id'] ?? 0;
        $customer_name = $_POST['customer_name'] ?? '';
        $customer_email = $_POST['customer_email'] ?? '';
        $customer_phone = $_POST['customer_phone'] ?? '';
        $customer_address = $_POST['customer_address'] ?? '';
        $total_amount = $_POST['total_amount'] ?? 0;
        $status = $_POST['status'] ?? 'pending';

        $set = 'customer_name = ?, customer_email = ?, total_amount = ?, status = ?';
        $params = [$customer_name, $customer_email, $total_amount, $status];

        if ($hasPhoneCol) {
            $set .= ', customer_phone = ?';
            $params[] = $customer_phone;
        }
        if ($hasAddressCol) {
            $set .= ', customer_address = ?';
            $params[] = $customer_address;
        }
        $params[] = $id;

        $sql = "UPDATE orders SET $set WHERE id = ?";
        $db->query($sql, $params);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 'pending';
        $db->query("UPDATE orders SET status = ? WHERE id = ?", [$status, $id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_order') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM orders WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Fetch all orders
$orders = $db->query("SELECT * FROM orders ORDER BY id DESC") ?: [];

// Compute stats
$totalOrders = count($orders);
$pendingOrders = 0;
$processingOrders = 0;
$completedOrders = 0;
$cancelledOrders = 0;
$revenue = 0;

foreach ($orders as $o) {
    $s = $o['status'] ?? 'pending';
    if ($s === 'pending') $pendingOrders++;
    elseif ($s === 'processing') $processingOrders++;
    elseif ($s === 'completed') { $completedOrders++; $revenue += floatval($o['total_amount']); }
    elseif ($s === 'cancelled') $cancelledOrders++;
}

// Date helper
function formatOrderDate($dateStr) {
    if (empty($dateStr)) return '-';
    $ts = strtotime($dateStr);
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');
    if ($ts >= $today) return 'Today, ' . date('h:i A', $ts);
    if ($ts >= $yesterday && $ts < $today) return 'Yesterday, ' . date('h:i A', $ts);
    return date('M d, Y', $ts);
}

// Parse items JSON safely
function parseOrderItems($order) {
    if (!isset($order['items']) || empty($order['items'])) return null;
    $items = json_decode($order['items'], true);
    return (json_last_error() === JSON_ERROR_NONE && is_array($items)) ? $items : null;
}
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-4 md:p-6">

                <!-- Page Title Row -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Orders</h2>
                        <p class="text-sm text-gray-400 mt-1">Manage and track customer orders</p>
                    </div>
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 transition-colors font-medium text-sm shadow-lg shadow-purple-600/20">
                        <i class="bi bi-plus-lg"></i> New Order
                    </button>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                                <i class="bi bi-bag text-purple-400 text-lg"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalOrders; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Total Orders</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center">
                                <i class="bi bi-cash-stack text-green-400 text-lg"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo __CURRENCY_SIGN__ . number_format($revenue, 2); ?></p>
                        <p class="text-xs text-gray-400 mt-1">Revenue (Completed)</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                                <i class="bi bi-clock text-yellow-400 text-lg"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $pendingOrders; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Pending Orders</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center">
                                <i class="bi bi-check-circle text-green-400 text-lg"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $completedOrders; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Completed</p>
                    </div>
                </div>

                <!-- Filter Tabs + Search -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                    <!-- Status Tabs -->
                    <div class="flex flex-wrap gap-1 bg-gray-800 rounded-lg p-1 border border-white/5">
                        <button onclick="filterOrders('all')" data-filter="all" class="order-filter-tab active-tab px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                            All <span class="ml-1 opacity-70"><?php echo $totalOrders; ?></span>
                        </button>
                        <button onclick="filterOrders('pending')" data-filter="pending" class="order-filter-tab px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-gray-400 hover:text-white">
                            Pending <span class="ml-1 opacity-70"><?php echo $pendingOrders; ?></span>
                        </button>
                        <button onclick="filterOrders('processing')" data-filter="processing" class="order-filter-tab px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-gray-400 hover:text-white">
                            Processing <span class="ml-1 opacity-70"><?php echo $processingOrders; ?></span>
                        </button>
                        <button onclick="filterOrders('completed')" data-filter="completed" class="order-filter-tab px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-gray-400 hover:text-white">
                            Completed <span class="ml-1 opacity-70"><?php echo $completedOrders; ?></span>
                        </button>
                        <button onclick="filterOrders('cancelled')" data-filter="cancelled" class="order-filter-tab px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-gray-400 hover:text-white">
                            Cancelled <span class="ml-1 opacity-70"><?php echo $cancelledOrders; ?></span>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full md:w-72">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                        <input type="text" id="orderSearch" placeholder="Search by name or email..." oninput="searchOrders(this.value)"
                               class="w-full bg-gray-800 border border-white/5 rounded-lg pl-9 pr-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm" id="ordersTable">
                            <thead class="bg-gray-700/40 text-xs uppercase text-gray-400 tracking-wider">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 font-semibold">Order</th>
                                    <th scope="col" class="px-5 py-3.5 font-semibold">Customer</th>
                                    <th scope="col" class="px-5 py-3.5 font-semibold">Total</th>
                                    <th scope="col" class="px-5 py-3.5 font-semibold">Status</th>
                                    <th scope="col" class="px-5 py-3.5 font-semibold">Date</th>
                                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5" id="ordersBody">
                                <?php if (empty($orders)): ?>
                                <tr id="emptyRow">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 rounded-full bg-gray-700/50 flex items-center justify-center mb-4">
                                                <i class="bi bi-bag-x text-3xl text-gray-500"></i>
                                            </div>
                                            <p class="text-gray-400 font-medium mb-1">No orders yet</p>
                                            <p class="text-gray-500 text-xs mb-4">Orders will appear here when customers make purchases</p>
                                            <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-xs font-medium transition-colors inline-flex items-center gap-1.5">
                                                <i class="bi bi-plus-lg"></i> Create First Order
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order):
                                        $status = $order['status'] ?? 'pending';
                                        $items = $hasItemsCol ? parseOrderItems($order) : null;
                                        $statusColors = [
                                            'pending' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                                            'processing' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                            'completed' => 'bg-green-500/10 text-green-400 border-green-500/20',
                                            'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        ];
                                        $statusDots = [
                                            'pending' => 'bg-yellow-400',
                                            'processing' => 'bg-blue-400',
                                            'completed' => 'bg-green-400',
                                            'cancelled' => 'bg-red-400',
                                        ];
                                        $sc = $statusColors[$status] ?? $statusColors['pending'];
                                        $sd = $statusDots[$status] ?? $statusDots['pending'];
                                    ?>
                                    <tr class="order-row hover:bg-white/[0.02] transition-colors cursor-pointer group"
                                        data-status="<?php echo $status; ?>"
                                        data-name="<?php echo strtolower(htmlspecialchars($order['customer_name'])); ?>"
                                        data-email="<?php echo strtolower(htmlspecialchars($order['customer_email'] ?? '')); ?>"
                                        onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>, <?php echo $hasItemsCol ? 'true' : 'false'; ?>)">
                                        <td class="px-5 py-4">
                                            <span class="font-mono text-sm font-semibold text-white">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                            <?php if ($items): ?>
                                                <span class="ml-1.5 text-[10px] text-gray-500"><?php echo count($items); ?> item<?php echo count($items) !== 1 ? 's' : ''; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="font-medium text-white text-sm"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            <?php if (!empty($order['customer_email'])): ?>
                                                <div class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="font-semibold text-white"><?php echo __CURRENCY_SIGN__ . number_format($order['total_amount'], 2); ?></span>
                                        </td>
                                        <td class="px-5 py-4" onclick="event.stopPropagation()">
                                            <div class="relative inline-block">
                                                <button onclick="toggleStatusDropdown(this, event)" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border <?php echo $sc; ?> hover:opacity-80 transition-opacity">
                                                    <span class="w-1.5 h-1.5 rounded-full <?php echo $sd; ?>"></span>
                                                    <?php echo ucfirst($status); ?>
                                                    <i class="bi bi-chevron-down text-[10px] opacity-60"></i>
                                                </button>
                                                <div class="status-dropdown hidden absolute top-full left-0 mt-1 w-40 bg-gray-700 rounded-lg shadow-xl border border-white/10 z-30 py-1 overflow-hidden">
                                                    <?php foreach (['pending', 'processing', 'completed', 'cancelled'] as $opt):
                                                        $optDot = $statusDots[$opt];
                                                        $isActive = $opt === $status ? 'bg-white/5' : '';
                                                    ?>
                                                    <form method="POST" class="contents">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="status" value="<?php echo $opt; ?>">
                                                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-200 hover:bg-white/10 transition-colors <?php echo $isActive; ?>">
                                                            <span class="w-2 h-2 rounded-full <?php echo $optDot; ?>"></span>
                                                            <?php echo ucfirst($opt); ?>
                                                            <?php if ($opt === $status): ?>
                                                                <i class="bi bi-check ml-auto text-sm"></i>
                                                            <?php endif; ?>
                                                        </button>
                                                    </form>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-gray-400 text-sm">
                                            <?php echo formatOrderDate($order['created_at'] ?? ''); ?>
                                        </td>
                                        <td class="px-5 py-4 text-right" onclick="event.stopPropagation()">
                                            <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button onclick='openModal("edit", <?php echo htmlspecialchars(json_encode($order)); ?>)' class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-blue-400 hover:bg-blue-400/10 transition-colors" title="Edit">
                                                    <i class="bi bi-pencil text-sm"></i>
                                                </button>
                                                <button onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>, <?php echo $hasItemsCol ? 'true' : 'false'; ?>)" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-purple-400 hover:bg-purple-400/10 transition-colors" title="View Details">
                                                    <i class="bi bi-eye text-sm"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Delete this order permanently?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_order">
                                                    <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-400 hover:bg-red-400/10 transition-colors" title="Delete">
                                                        <i class="bi bi-trash text-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- No results from search/filter -->
                    <div id="noResults" class="hidden px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class="bi bi-search text-2xl text-gray-600 mb-2"></i>
                            <p class="text-gray-500 text-sm">No orders match your criteria</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Create/Edit Order Modal -->
        <div id="orderModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
                <div class="relative w-full max-w-lg bg-gray-800 rounded-xl shadow-2xl border border-white/10 transform transition-all">
                    <form method="POST" id="orderForm">
                        <input type="hidden" name="action" id="formAction" value="add_order">
                        <input type="hidden" name="id" id="orderId">

                        <!-- Modal Header -->
                        <div class="flex justify-between items-center px-6 py-4 border-b border-white/5">
                            <h3 class="text-lg font-semibold text-white" id="modalTitle">New Order</h3>
                            <button type="button" onclick="closeModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Customer Name *</label>
                                    <input type="text" name="customer_name" id="customerName" required
                                           class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"
                                           placeholder="Full name">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Email</label>
                                    <input type="email" name="customer_email" id="customerEmail"
                                           class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"
                                           placeholder="email@example.com">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Phone</label>
                                    <input type="text" name="customer_phone" id="customerPhone"
                                           class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"
                                           placeholder="+1 234 567 8901">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Address</label>
                                    <textarea name="customer_address" id="customerAddress" rows="2"
                                              class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors resize-none"
                                              placeholder="Shipping address"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Total Amount (<?php echo __CURRENCY_SIGN__; ?>) *</label>
                                    <input type="number" step="0.01" min="0" name="total_amount" id="totalAmount" required
                                           class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"
                                           placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Status</label>
                                    <select name="status" id="orderStatus"
                                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end gap-3 px-6 py-4 border-t border-white/5 bg-gray-800/50">
                            <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-lg border border-gray-600 text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-lg bg-purple-600 text-sm font-medium text-white hover:bg-purple-700 transition-colors shadow-lg shadow-purple-600/20">
                                Save Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Detail Modal -->
        <div id="detailModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeDetailModal()"></div>
                <div class="relative w-full max-w-xl bg-gray-800 rounded-xl shadow-2xl border border-white/10 transform transition-all">

                    <!-- Detail Header -->
                    <div class="flex justify-between items-center px-6 py-4 border-b border-white/5">
                        <div>
                            <h3 class="text-lg font-semibold text-white" id="detailTitle">Order #0001</h3>
                            <p class="text-xs text-gray-400 mt-0.5" id="detailDate"></p>
                        </div>
                        <button type="button" onclick="closeDetailModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <!-- Detail Body -->
                    <div class="px-6 py-5 max-h-[65vh] overflow-y-auto space-y-5">
                        <!-- Status Timeline -->
                        <div id="detailTimeline" class="flex items-center gap-0 text-xs"></div>

                        <!-- Customer Info -->
                        <div class="bg-gray-700/30 rounded-lg p-4 border border-white/5">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Customer Information</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs text-gray-500">Name</p>
                                    <p class="text-sm text-white font-medium" id="detailName">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm text-white" id="detailEmail">-</p>
                                </div>
                                <div id="detailPhoneWrap" class="hidden">
                                    <p class="text-xs text-gray-500">Phone</p>
                                    <p class="text-sm text-white" id="detailPhone">-</p>
                                </div>
                                <div id="detailAddressWrap" class="hidden sm:col-span-2">
                                    <p class="text-xs text-gray-500">Address</p>
                                    <p class="text-sm text-white" id="detailAddress">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Items -->
                        <div id="detailItemsSection" class="hidden">
                            <div class="bg-gray-700/30 rounded-lg border border-white/5 overflow-hidden">
                                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 pt-4 pb-2">Order Items</h4>
                                <div id="detailItemsList" class="divide-y divide-white/5"></div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="flex justify-between items-center bg-gray-700/30 rounded-lg p-4 border border-white/5">
                            <span class="text-sm font-medium text-gray-400">Total Amount</span>
                            <span class="text-lg font-bold text-white" id="detailTotal">-</span>
                        </div>
                    </div>

                    <!-- Detail Footer -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-white/5 bg-gray-800/50">
                        <button onclick="closeDetailModal()" class="px-4 py-2.5 rounded-lg border border-gray-600 text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                            Close
                        </button>
                        <button id="detailEditBtn" class="px-5 py-2.5 rounded-lg bg-purple-600 text-sm font-medium text-white hover:bg-purple-700 transition-colors">
                            <i class="bi bi-pencil text-xs mr-1"></i> Edit Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .active-tab {
                background-color: rgba(147, 51, 234, 0.15);
                color: #c084fc;
            }
            .order-row td { user-select: none; }
            .status-dropdown { animation: dropIn 0.12s ease-out; }
            @keyframes dropIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
        </style>

        <script>
        const currencySign = '<?php echo __CURRENCY_SIGN__; ?>';

        // --- Create/Edit Modal ---
        function openModal(mode, order = null) {
            const modal = document.getElementById('orderModal');
            const form = document.getElementById('orderForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');

            closeDetailModal();
            modal.classList.remove('hidden');

            if (mode === 'edit' && order) {
                title.textContent = 'Edit Order #' + String(order.id).padStart(4, '0');
                action.value = 'update_order';
                document.getElementById('orderId').value = order.id;
                document.getElementById('customerName').value = order.customer_name || '';
                document.getElementById('customerEmail').value = order.customer_email || '';
                document.getElementById('customerPhone').value = order.customer_phone || '';
                document.getElementById('customerAddress').value = order.customer_address || '';
                document.getElementById('totalAmount').value = order.total_amount || '';
                document.getElementById('orderStatus').value = order.status || 'pending';
            } else {
                title.textContent = 'New Order';
                action.value = 'add_order';
                form.reset();
                document.getElementById('orderId').value = '';
            }
        }

        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }

        // --- Detail Modal ---
        function viewOrder(order, hasItems) {
            const modal = document.getElementById('detailModal');
            modal.classList.remove('hidden');

            document.getElementById('detailTitle').textContent = 'Order #' + String(order.id).padStart(4, '0');
            document.getElementById('detailDate').textContent = order.created_at ? formatDate(order.created_at) : '';
            document.getElementById('detailName').textContent = order.customer_name || '-';
            document.getElementById('detailEmail').textContent = order.customer_email || '-';
            document.getElementById('detailTotal').textContent = currencySign + parseFloat(order.total_amount || 0).toFixed(2);

            // Phone
            const phoneWrap = document.getElementById('detailPhoneWrap');
            if (order.customer_phone) {
                phoneWrap.classList.remove('hidden');
                document.getElementById('detailPhone').textContent = order.customer_phone;
            } else {
                phoneWrap.classList.add('hidden');
            }

            // Address
            const addrWrap = document.getElementById('detailAddressWrap');
            if (order.customer_address) {
                addrWrap.classList.remove('hidden');
                document.getElementById('detailAddress').textContent = order.customer_address;
            } else {
                addrWrap.classList.add('hidden');
            }

            // Status timeline
            renderTimeline(order.status || 'pending');

            // Items
            const itemsSection = document.getElementById('detailItemsSection');
            const itemsList = document.getElementById('detailItemsList');
            itemsList.innerHTML = '';

            let items = null;
            if (hasItems && order.items) {
                try { items = typeof order.items === 'string' ? JSON.parse(order.items) : order.items; } catch(e) {}
            }

            if (items && Array.isArray(items) && items.length > 0) {
                itemsSection.classList.remove('hidden');
                items.forEach(function(item) {
                    const name = item.name || item.product_name || item.title || 'Item';
                    const qty = item.quantity || item.qty || 1;
                    const price = parseFloat(item.price || item.unit_price || 0).toFixed(2);
                    const row = document.createElement('div');
                    row.className = 'flex items-center justify-between px-4 py-3 text-sm';
                    row.innerHTML = '<div class="flex items-center gap-3">' +
                        '<div class="w-8 h-8 rounded-md bg-gray-600/50 flex items-center justify-center text-gray-400"><i class="bi bi-box-seam text-xs"></i></div>' +
                        '<div><p class="text-white font-medium">' + escapeHtml(name) + '</p>' +
                        '<p class="text-xs text-gray-500">Qty: ' + qty + '</p></div></div>' +
                        '<span class="text-white font-medium">' + currencySign + price + '</span>';
                    itemsList.appendChild(row);
                });
            } else {
                itemsSection.classList.add('hidden');
            }

            // Edit button
            document.getElementById('detailEditBtn').onclick = function() {
                closeDetailModal();
                openModal('edit', order);
            };
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function renderTimeline(currentStatus) {
            const steps = ['pending', 'processing', 'completed'];
            const container = document.getElementById('detailTimeline');
            container.innerHTML = '';

            if (currentStatus === 'cancelled') {
                container.innerHTML = '<div class="flex items-center gap-2 bg-red-500/10 text-red-400 rounded-lg px-3 py-2 w-full border border-red-500/20">' +
                    '<i class="bi bi-x-circle"></i><span class="font-medium">Order Cancelled</span></div>';
                return;
            }

            const activeIdx = steps.indexOf(currentStatus);
            const colors = { done: 'bg-purple-500 text-white', active: 'bg-purple-500 text-white', future: 'bg-gray-700 text-gray-500' };
            const lineColors = { done: 'bg-purple-500', future: 'bg-gray-700' };

            steps.forEach(function(step, i) {
                const state = i < activeIdx ? 'done' : (i === activeIdx ? 'active' : 'future');
                const circleClass = colors[state];
                const icon = state === 'done' ? '<i class="bi bi-check text-[10px]"></i>' : '<span class="text-[10px]">' + (i + 1) + '</span>';

                const el = document.createElement('div');
                el.className = 'flex items-center gap-2';
                el.innerHTML = '<div class="w-6 h-6 rounded-full flex items-center justify-center ' + circleClass + ' flex-shrink-0">' + icon + '</div>' +
                    '<span class="text-xs font-medium ' + (state === 'future' ? 'text-gray-500' : 'text-white') + '">' + step.charAt(0).toUpperCase() + step.slice(1) + '</span>';
                container.appendChild(el);

                if (i < steps.length - 1) {
                    const line = document.createElement('div');
                    line.className = 'flex-1 h-0.5 mx-1 rounded ' + (i < activeIdx ? lineColors.done : lineColors.future);
                    container.appendChild(line);
                }
            });
        }

        // --- Filtering ---
        let currentFilter = 'all';

        function filterOrders(status) {
            currentFilter = status;
            applyFilters();

            document.querySelectorAll('.order-filter-tab').forEach(function(tab) {
                if (tab.dataset.filter === status) {
                    tab.className = 'order-filter-tab active-tab px-3 py-1.5 rounded-md text-xs font-medium transition-colors';
                } else {
                    tab.className = 'order-filter-tab px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-gray-400 hover:text-white';
                }
            });
        }

        function searchOrders(query) {
            applyFilters(query.toLowerCase());
        }

        function applyFilters(search) {
            if (typeof search === 'undefined') {
                search = (document.getElementById('orderSearch').value || '').toLowerCase();
            }
            const rows = document.querySelectorAll('.order-row');
            let visibleCount = 0;

            rows.forEach(function(row) {
                const matchStatus = currentFilter === 'all' || row.dataset.status === currentFilter;
                const matchSearch = !search || row.dataset.name.includes(search) || row.dataset.email.includes(search);
                if (matchStatus && matchSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResults = document.getElementById('noResults');
            if (rows.length > 0 && visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }

        // --- Status Dropdown ---
        function toggleStatusDropdown(btn, e) {
            e.stopPropagation();
            const dropdown = btn.nextElementSibling;
            const wasHidden = dropdown.classList.contains('hidden');

            // Close all open dropdowns first
            document.querySelectorAll('.status-dropdown').forEach(function(d) { d.classList.add('hidden'); });

            if (wasHidden) {
                dropdown.classList.remove('hidden');
            }
        }

        // Close dropdowns on outside click
        document.addEventListener('click', function() {
            document.querySelectorAll('.status-dropdown').forEach(function(d) { d.classList.add('hidden'); });
        });

        // Close modals on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeDetailModal();
            }
        });

        // --- Helpers ---
        function formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);

            if (d >= today) {
                return 'Today, ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (d >= yesterday) {
                return 'Yesterday, ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        </script>
