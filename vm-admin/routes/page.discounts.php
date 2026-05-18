<?php
$db = initiate_web_database();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_discount') {
        $code = $_POST['code'] ?? '';
        $value = $_POST['value'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $db->query("INSERT INTO discounts (code, value, status) VALUES (?, ?, ?)", [$code, $value, $status]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_discount') {
        $id = $_POST['id'] ?? 0;
        $code = $_POST['code'] ?? '';
        $value = $_POST['value'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $db->query("UPDATE discounts SET code = ?, value = ?, status = ? WHERE id = ?", [$code, $value, $status, $id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_discount') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM discounts WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

$discounts = $db->query("SELECT * FROM discounts ORDER BY id DESC");
$totalDiscounts = is_array($discounts) ? count($discounts) : 0;
$activeDiscounts = 0;
$maxDiscount = 0;
if ($discounts) {
    foreach ($discounts as $d) {
        if (($d['status'] ?? 'active') === 'active') $activeDiscounts++;
        if ((float)($d['value'] ?? 0) > $maxDiscount) $maxDiscount = (float)$d['value'];
    }
}
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php @include_once "header.php"; ?>

            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#09090b] p-6">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Discount Codes</h2>
                        <p class="text-zinc-400 text-sm mt-1">Create and manage promotional discount codes</p>
                    </div>
                    <button onclick="openModal('add')" class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <i class="bi bi-plus-lg"></i> Add Discount
                    </button>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Total Codes</span>
                            <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <i class="bi bi-ticket-perforated text-violet-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalDiscounts; ?></p>
                    </div>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Active</span>
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <i class="bi bi-check-circle text-emerald-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $activeDiscounts; ?></p>
                    </div>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Highest Discount</span>
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <i class="bi bi-percent text-amber-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $maxDiscount; ?>%</p>
                    </div>
                </div>

                <!-- Discounts Table -->
                <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                        <h3 class="text-white font-semibold text-sm"><i class="bi bi-ticket-perforated mr-2 text-zinc-500"></i>All Discount Codes</h3>
                    </div>
                    <?php if (empty($discounts)): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-zinc-500">
                        <i class="bi bi-ticket-perforated text-4xl mb-3"></i>
                        <p class="text-sm">No discount codes yet</p>
                        <p class="text-xs text-zinc-600 mt-1">Create a code to offer promotions to your customers</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                                    <th class="px-5 py-3 font-medium">Code</th>
                                    <th class="px-5 py-3 font-medium">Discount</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/50">
                                <?php foreach ($discounts as $discount): ?>
                                <tr class="hover:bg-zinc-800/30 transition-colors group">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                                <i class="bi bi-percent text-violet-400 text-sm"></i>
                                            </div>
                                            <span class="text-white font-mono font-bold tracking-wider"><?php echo htmlspecialchars($discount['code']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-white font-semibold"><?php echo $discount['value']; ?>%</span>
                                        <span class="text-zinc-500 text-xs ml-1">off</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full <?php echo ($discount['status'] ?? 'active') === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo ($discount['status'] ?? 'active') === 'active' ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                                            <?php echo ucfirst($discount['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick='openModal("edit", <?php echo json_encode($discount); ?>)' class="p-1.5 rounded-md hover:bg-zinc-700 text-zinc-400 transition-colors" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this discount code?');">
                                                <input type="hidden" name="action" value="delete_discount">
                                                <input type="hidden" name="id" value="<?php echo $discount['id']; ?>">
                                                <button type="submit" class="p-1.5 rounded-md hover:bg-red-900/30 text-red-400 transition-colors" title="Delete">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>

<!-- Modal -->
<div id="discountModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="relative bg-zinc-900 border border-zinc-800 rounded-xl w-full max-w-md shadow-2xl">
            <form method="POST" id="discountForm">
                <input type="hidden" name="action" id="formAction" value="add_discount">
                <input type="hidden" name="id" id="discountId">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold" id="modalTitle">Add Discount</h3>
                    <button type="button" onclick="closeModal()" class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Discount Code</label>
                        <input type="text" name="code" id="discountCode" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm font-mono uppercase focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors" placeholder="SUMMER2026">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Discount Value (%)</label>
                        <input type="number" name="value" id="discountValue" required min="0" max="100" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors" placeholder="10">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Status</label>
                        <select name="status" id="discountStatus" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-zinc-800 flex justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white transition-colors">Cancel</button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Save Discount</button>
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
        title.textContent = 'Add Discount';
        action.value = 'add_discount';
        form.reset();
        document.getElementById('discountId').value = '';
    }
}

function closeModal() {
    document.getElementById('discountModal').classList.add('hidden');
}
</script>
