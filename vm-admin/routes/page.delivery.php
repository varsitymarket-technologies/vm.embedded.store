<?php
$db = initiate_web_database();

// Create table if not exists
$db->query("CREATE TABLE IF NOT EXISTS delivery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    province VARCHAR(255),
    type VARCHAR(50),
    price DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'active'
)");

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_delivery') {
        $province = $_POST['province'] ?? '';
        $type = $_POST['type'] ?? 'flat';
        $price = $_POST['price'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $db->query("INSERT INTO delivery (province, type, price, status) VALUES (?, ?, ?, ?)", [$province, $type, $price, $status]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_delivery') {
        $id = $_POST['id'] ?? 0;
        $province = $_POST['province'] ?? '';
        $type = $_POST['type'] ?? 'flat';
        $price = $_POST['price'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $db->query("UPDATE delivery SET province = ?, type = ?, price = ?, status = ? WHERE id = ?", [$province, $type, $price, $status, $id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_delivery') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM delivery WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

$deliveries = $db->query("SELECT * FROM delivery ORDER BY id DESC");
$totalZones = is_array($deliveries) ? count($deliveries) : 0;
$activeZones = 0;
$avgPrice = 0;
$priceSum = 0;
if ($deliveries) {
    foreach ($deliveries as $d) {
        if (($d['status'] ?? 'active') === 'active') $activeZones++;
        $priceSum += (float)($d['price'] ?? 0);
    }
    $avgPrice = $totalZones > 0 ? $priceSum / $totalZones : 0;
}
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php @include_once "header.php"; ?>

            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#09090b] p-6">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Delivery Zones</h2>
                        <p class="text-zinc-400 text-sm mt-1">Manage shipping rates and delivery regions</p>
                    </div>
                    <button onclick="openModal('add')" class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <i class="bi bi-plus-lg"></i> Add Zone
                    </button>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Total Zones</span>
                            <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <i class="bi bi-geo-alt text-violet-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalZones; ?></p>
                    </div>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Active</span>
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <i class="bi bi-check-circle text-emerald-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $activeZones; ?></p>
                    </div>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Avg Rate</span>
                            <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                                <i class="bi bi-currency-exchange text-sky-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white">R<?php echo number_format($avgPrice, 2); ?></p>
                    </div>
                </div>

                <!-- Delivery Table -->
                <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                        <h3 class="text-white font-semibold text-sm"><i class="bi bi-truck mr-2 text-zinc-500"></i>Delivery Rates</h3>
                    </div>
                    <?php if (empty($deliveries)): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-zinc-500">
                        <i class="bi bi-truck text-4xl mb-3"></i>
                        <p class="text-sm">No delivery zones configured</p>
                        <p class="text-xs text-zinc-600 mt-1">Add zones to enable shipping for your store</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                                    <th class="px-5 py-3 font-medium">Zone</th>
                                    <th class="px-5 py-3 font-medium">Type</th>
                                    <th class="px-5 py-3 font-medium">Price</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/50">
                                <?php foreach ($deliveries as $del): ?>
                                <tr class="hover:bg-zinc-800/30 transition-colors group">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-zinc-800 flex items-center justify-center">
                                                <i class="bi bi-geo-alt text-zinc-400"></i>
                                            </div>
                                            <span class="text-white font-medium"><?php echo htmlspecialchars($del['province'] ?? ''); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full <?php echo ($del['type'] ?? 'flat') === 'flat' ? 'bg-sky-500/10 text-sky-400' : 'bg-violet-500/10 text-violet-400'; ?>">
                                            <?php echo ucfirst($del['type'] ?? 'flat'); ?> Rate
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-white font-medium">R<?php echo number_format((float)($del['price'] ?? 0), 2); ?></td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full <?php echo ($del['status'] ?? 'active') === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo ($del['status'] ?? 'active') === 'active' ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                                            <?php echo ucfirst($del['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick='openModal("edit", <?php echo json_encode($del); ?>)' class="p-1.5 rounded-md hover:bg-zinc-700 text-zinc-400 transition-colors" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this delivery zone?');">
                                                <input type="hidden" name="action" value="delete_delivery">
                                                <input type="hidden" name="id" value="<?php echo $del['id']; ?>">
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
<div id="deliveryModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="relative bg-zinc-900 border border-zinc-800 rounded-xl w-full max-w-md shadow-2xl">
            <form method="POST" id="deliveryForm">
                <input type="hidden" name="action" id="formAction" value="add_delivery">
                <input type="hidden" name="id" id="deliveryId">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold" id="modalTitle">Add Delivery Zone</h3>
                    <button type="button" onclick="closeModal()" class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Zone Name</label>
                        <input type="text" name="province" id="deliveryProvince" required class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors" placeholder="e.g. Gauteng">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Rate Type</label>
                        <select name="type" id="deliveryType" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <option value="flat">Flat Rate</option>
                            <option value="regional">Regional Rate</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Price (R)</label>
                        <input type="number" step="0.01" name="price" id="deliveryPrice" required min="0" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors" placeholder="100.00">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Status</label>
                        <select name="status" id="deliveryStatus" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-zinc-800 flex justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white transition-colors">Cancel</button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Save Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(mode, del = null) {
    const modal = document.getElementById('deliveryModal');
    const form = document.getElementById('deliveryForm');
    const title = document.getElementById('modalTitle');
    const action = document.getElementById('formAction');
    modal.classList.remove('hidden');
    if (mode === 'edit' && del) {
        title.textContent = 'Edit Delivery Zone';
        action.value = 'update_delivery';
        document.getElementById('deliveryId').value = del.id;
        document.getElementById('deliveryProvince').value = del.province;
        document.getElementById('deliveryType').value = del.type || 'flat';
        document.getElementById('deliveryPrice').value = del.price;
        document.getElementById('deliveryStatus').value = del.status || 'active';
    } else {
        title.textContent = 'Add Delivery Zone';
        action.value = 'add_delivery';
        form.reset();
        document.getElementById('deliveryId').value = '';
    }
}

function closeModal() {
    document.getElementById('deliveryModal').classList.add('hidden');
}
</script>
