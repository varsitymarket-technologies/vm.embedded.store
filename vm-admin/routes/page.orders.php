<?php
$db = initiate_web_database(); 

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_order') {
        $customer_name = $_POST['customer_name'] ?? '';
        $customer_email = $_POST['customer_email'] ?? '';
        $total_amount = $_POST['total_amount'] ?? 0;
        $status = $_POST['status'] ?? 'pending';

        $sql = "INSERT INTO orders (customer_name, customer_email, total_amount, status) VALUES (?, ?, ?, ?)";
        $db->query($sql, [$customer_name, $customer_email, $total_amount, $status]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_order') {
        $id = $_POST['id'] ?? 0;
        $customer_name = $_POST['customer_name'] ?? '';
        $customer_email = $_POST['customer_email'] ?? '';
        $total_amount = $_POST['total_amount'] ?? 0;
        $status = $_POST['status'] ?? 'pending';

        $sql = "UPDATE orders SET customer_name = ?, customer_email = ?, total_amount = ?, status = ? WHERE id = ?";
        $db->query($sql, [$customer_name, $customer_email, $total_amount, $status, $id]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_order') {
        $id = $_POST['id'] ?? 0;
        $sql = "DELETE FROM orders WHERE id = ?";
        $db->query($sql, [$id]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Fetch Orders
$orders = $db->query("SELECT * FROM orders ORDER BY id DESC");
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10">
                <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
                    <i class="bi bi-list text-2xl"></i>
                </button>
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
                        <!-- Dropdown -->
                        <div class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block border border-white/10">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profile</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Settings</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Sign out</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Order Management</h2>
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="bi bi-plus-lg"></i> Create Order
                    </button>
                </div>

                <!-- Orders Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-700/50 text-xs uppercase text-gray-300">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Order ID</th>
                                    <th scope="col" class="px-6 py-3">Customer</th>
                                    <th scope="col" class="px-6 py-3">Total</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Date</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="bi bi-cart-x text-4xl mb-2"></i>
                                                <p>No orders found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4 font-medium text-white">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-white"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-white">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $statusClass = match($order['status'] ?? 'pending') {
                                                'completed' => 'bg-green-500/10 text-green-500',
                                                'cancelled' => 'bg-red-500/10 text-red-500',
                                                'processing' => 'bg-blue-500/10 text-blue-500',
                                                default => 'bg-yellow-500/10 text-yellow-500'
                                            };
                                            ?>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">
                                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-3">
                                                <button onclick='openModal("edit", <?php echo json_encode($order); ?>)' class="text-blue-400 hover:text-blue-300 transition-colors" title="Edit">
                                                    <i class="bi bi-pencil-square text-lg"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this order?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_order">
                                                    <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="text-red-400 hover:text-red-300 transition-colors" title="Delete">
                                                        <i class="bi bi-trash text-lg"></i>
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
                </div>
            </main>
        </div>

        <!-- Modal -->
        <div id="orderModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>

                <div class="relative inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-white/10">
                    <form method="POST" id="orderForm">
                        <input type="hidden" name="action" id="formAction" value="add_order">
                        <input type="hidden" name="id" id="orderId">
                        
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-5">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modalTitle">Create Order</h3>
                                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Customer Name</label>
                                    <input type="text" name="customer_name" id="customerName" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Customer Email</label>
                                    <input type="email" name="customer_email" id="customerEmail" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Total Amount ($)</label>
                                    <input type="number" step="0.01" name="total_amount" id="totalAmount" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                                    <select name="status" id="orderStatus" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-700/30 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-white/5">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Save Order
                            </button>
                            <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-600 shadow-sm px-4 py-2 bg-transparent text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openModal(mode, order = null) {
            const modal = document.getElementById('orderModal');
            const form = document.getElementById('orderForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            
            modal.classList.remove('hidden');
            
            if (mode === 'edit' && order) {
                title.textContent = 'Edit Order';
                action.value = 'update_order';
                document.getElementById('orderId').value = order.id;
                document.getElementById('customerName').value = order.customer_name;
                document.getElementById('customerEmail').value = order.customer_email;
                document.getElementById('totalAmount').value = order.total_amount;
                document.getElementById('orderStatus').value = order.status || 'pending';
            } else {
                title.textContent = 'Create Order';
                action.value = 'add_order';
                form.reset();
                document.getElementById('orderId').value = '';
            }
        }

        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }
        </script>