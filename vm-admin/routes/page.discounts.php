<?php
$db = initiate_web_database(); 

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_discount') {
        $code = $_POST['code'] ?? '';
        $value = $_POST['value'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $sql = "INSERT INTO discounts (code, value, status) VALUES (?, ?, ?)";
        $db->query($sql, [$code, $value, $status]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_discount') {
        $id = $_POST['id'] ?? 0;
        $code = $_POST['code'] ?? '';
        $value = $_POST['value'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $sql = "UPDATE discounts SET code = ?, value = ?, status = ? WHERE id = ?";
        $db->query($sql, [$code, $value, $status, $id]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_discount') {
        $id = $_POST['id'] ?? 0;
        $sql = "DELETE FROM discounts WHERE id = ?";
        $db->query($sql, [$id]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Fetch Discounts
$discounts = $db->query("SELECT * FROM discounts ORDER BY id DESC");
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Discount Codes</h2>
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="bi bi-plus-lg"></i> Add Discount
                    </button>
                </div>

                <!-- Discounts Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-700/50 text-xs uppercase text-gray-300">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Code</th>
                                    <th scope="col" class="px-6 py-3">Value (%)</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($discounts)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="bi bi-percent text-4xl mb-2"></i>
                                                <p>No discounts found. Click "Add Discount" to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($discounts as $discount): ?>
                                    <tr class="hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4 font-medium text-white">#<?php echo $discount['id']; ?></td>
                                        <td class="px-6 py-4 font-mono text-purple-400 font-bold"><?php echo htmlspecialchars($discount['code']); ?></td>
                                        <td class="px-6 py-4 text-white"><?php echo $discount['value']; ?>%</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo ($discount['status'] ?? 'active') === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                <?php echo ucfirst($discount['status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-3">
                                                <button onclick='openModal("edit", <?php echo json_encode($discount); ?>)' class="text-blue-400 hover:text-blue-300 transition-colors" title="Edit">
                                                    <i class="bi bi-pencil-square text-lg"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this discount?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_discount">
                                                    <input type="hidden" name="id" value="<?php echo $discount['id']; ?>">
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
        <div id="discountModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>

                <div class="relative inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-white/10">
                    <form method="POST" id="discountForm">
                        <input type="hidden" name="action" id="formAction" value="add_discount">
                        <input type="hidden" name="id" id="discountId">
                        
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-5">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modalTitle">Add New Discount</h3>
                                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Discount Code</label>
                                    <input type="text" name="code" id="discountCode" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors uppercase" placeholder="SUMMER2024">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Discount Value (%)</label>
                                    <input type="number" name="value" id="discountValue" required min="0" max="100" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                                    <select name="status" id="discountStatus" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-700/30 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-white/5">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Save Discount
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
        function openModal(mode, discount = null) {
            const modal = document.getElementById('discountModal');
            const form = document.getElementById('discountForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            
            modal.classList.remove('hidden');
            
            if (mode === 'edit' && discount) {
                title.textContent = 'Edit Discount';
                action.value = 'update_discount';
                document.getElementById('discountId').value = discount.id;
                document.getElementById('discountCode').value = discount.code;
                document.getElementById('discountValue').value = discount.value;
                document.getElementById('discountStatus').value = discount.status || 'active';
            } else {
                title.textContent = 'Add New Discount';
                action.value = 'add_discount';
                form.reset();
                document.getElementById('discountId').value = '';
            }
        }

        function closeModal() {
            document.getElementById('discountModal').classList.add('hidden');
        }
        </script>