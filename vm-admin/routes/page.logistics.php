<?php
$db = initiate_web_database(); 

// Create table if not exists
$db->query("CREATE TABLE IF NOT EXISTS logistics (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    task_name VARCHAR(255), 
    order_reference VARCHAR(255), 
    courier VARCHAR(100), 
    tracking_code VARCHAR(100), 
    status VARCHAR(50) DEFAULT 'pending'
)");

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_logistics') {
        $task_name = $_POST['task_name'] ?? '';
        $order_reference = $_POST['order_reference'] ?? '';
        $courier = $_POST['courier'] ?? '';
        $tracking_code = $_POST['tracking_code'] ?? '';
        $status = $_POST['status'] ?? 'pending';

        $sql = "INSERT INTO logistics (task_name, order_reference, courier, tracking_code, status) VALUES (?, ?, ?, ?, ?)";
        $db->query($sql, [$task_name, $order_reference, $courier, $tracking_code, $status]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_logistics') {
        $id = $_POST['id'] ?? 0;
        $task_name = $_POST['task_name'] ?? '';
        $order_reference = $_POST['order_reference'] ?? '';
        $courier = $_POST['courier'] ?? '';
        $tracking_code = $_POST['tracking_code'] ?? '';
        $status = $_POST['status'] ?? 'pending';

        $sql = "UPDATE logistics SET task_name = ?, order_reference = ?, courier = ?, tracking_code = ?, status = ? WHERE id = ?";
        $db->query($sql, [$task_name, $order_reference, $courier, $tracking_code, $status, $id]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_logistics') {
        $id = $_POST['id'] ?? 0;
        $sql = "DELETE FROM logistics WHERE id = ?";
        $db->query($sql, [$id]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Fetch Logistics Tasks
$logistics = $db->query("SELECT * FROM logistics ORDER BY id DESC");
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Logistics Tasks</h2>
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="bi bi-plus-lg"></i> Add Task
                    </button>
                </div>

                <!-- Logistics Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-700/50 text-xs uppercase text-gray-300">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Task Name</th>
                                    <th scope="col" class="px-6 py-3">Order Ref</th>
                                    <th scope="col" class="px-6 py-3">Courier</th>
                                    <th scope="col" class="px-6 py-3">Tracking</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($logistics)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="bi bi-box-seam text-4xl mb-2"></i>
                                                <p>No logistics tasks found. Click "Add Task" to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logistics as $log): ?>
                                    <tr class="hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4 font-medium text-white">#<?php echo $log['id']; ?></td>
                                        <td class="px-6 py-4 font-medium text-white"><?php echo htmlspecialchars($log['task_name'] ?? ''); ?></td>
                                        <td class="px-6 py-4 text-white"><?php echo htmlspecialchars($log['order_reference'] ?? ''); ?></td>
                                        <td class="px-6 py-4 text-white"><?php echo htmlspecialchars($log['courier'] ?? ''); ?></td>
                                        <td class="px-6 py-4 text-white"><?php echo htmlspecialchars($log['tracking_code'] ?? ''); ?></td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $statusClass = 'bg-gray-500/10 text-gray-500';
                                                $statusText = ucfirst($log['status'] ?? 'pending');
                                                if ($log['status'] === 'dispatched') $statusClass = 'bg-blue-500/10 text-blue-500';
                                                elseif ($log['status'] === 'delivered') $statusClass = 'bg-green-500/10 text-green-500';
                                                elseif ($log['status'] === 'cancelled') $statusClass = 'bg-red-500/10 text-red-500';
                                                else $statusClass = 'bg-yellow-500/10 text-yellow-500'; // pending
                                            ?>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-3">
                                                <button onclick='openModal("edit", <?php echo json_encode($log); ?>)' class="text-blue-400 hover:text-blue-300 transition-colors" title="Edit">
                                                    <i class="bi bi-pencil-square text-lg"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this logistics task?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_logistics">
                                                    <input type="hidden" name="id" value="<?php echo $log['id']; ?>">
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
        <div id="logisticsModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>

                <div style="min-width: 24rem;" class="relative inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-white/10">
                    <form method="POST" id="logisticsForm">
                        <input type="hidden" name="action" id="formAction" value="add_logistics">
                        <input type="hidden" name="id" id="logisticsId">
                        
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-5">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modalTitle">Add Logistics Task</h3>
                                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Task Name</label>
                                    <input type="text" name="task_name" id="logisticsTaskName" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors" placeholder="e.g. Delivery to NY">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Order Reference</label>
                                    <input type="text" name="order_reference" id="logisticsOrderRef" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors" placeholder="#ORD-12345">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Courier</label>
                                    <input type="text" name="courier" id="logisticsCourier" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors" placeholder="FedEx / UPS">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Tracking Code</label>
                                    <input type="text" name="tracking_code" id="logisticsTracking" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors" placeholder="TRK123456789">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                                    <select name="status" id="logisticsStatus" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                        <option value="pending">Pending</option>
                                        <option value="dispatched">Dispatched</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-700/30 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-white/5">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Save Task
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
        function openModal(mode, log = null) {
            const modal = document.getElementById('logisticsModal');
            const form = document.getElementById('logisticsForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            
            modal.classList.remove('hidden');
            
            if (mode === 'edit' && log) {
                title.textContent = 'Edit Logistics Task';
                action.value = 'update_logistics';
                document.getElementById('logisticsId').value = log.id;
                document.getElementById('logisticsTaskName').value = log.task_name;
                document.getElementById('logisticsOrderRef').value = log.order_reference;
                document.getElementById('logisticsCourier').value = log.courier;
                document.getElementById('logisticsTracking').value = log.tracking_code;
                document.getElementById('logisticsStatus').value = log.status || 'pending';
            } else {
                title.textContent = 'Add Logistics Task';
                action.value = 'add_logistics';
                form.reset();
                document.getElementById('logisticsId').value = '';
            }
        }

        function closeModal() {
            document.getElementById('logisticsModal').classList.add('hidden');
        }
        </script>
