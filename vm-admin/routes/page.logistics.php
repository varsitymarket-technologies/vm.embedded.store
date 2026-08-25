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

$db->query("CREATE TABLE IF NOT EXISTS courier_integrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider_name VARCHAR(255),
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    account_number VARCHAR(100),
    is_active INTEGER DEFAULT 1
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

        $db->query("INSERT INTO logistics (task_name, order_reference, courier, tracking_code, status) VALUES (?, ?, ?, ?, ?)", [$task_name, $order_reference, $courier, $tracking_code, $status]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_logistics') {
        $id = $_POST['id'] ?? 0;
        $task_name = $_POST['task_name'] ?? '';
        $order_reference = $_POST['order_reference'] ?? '';
        $courier = $_POST['courier'] ?? '';
        $tracking_code = $_POST['tracking_code'] ?? '';
        $status = $_POST['status'] ?? 'pending';

        $db->query("UPDATE logistics SET task_name = ?, order_reference = ?, courier = ?, tracking_code = ?, status = ? WHERE id = ?", [$task_name, $order_reference, $courier, $tracking_code, $status, $id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_logistics') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM logistics WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    } elseif ($action === 'save_courier_integration') {
        $provider_name = $_POST['provider_name'] ?? '';
        $api_key = $_POST['api_key'] ?? '';
        $api_secret = $_POST['api_secret'] ?? '';
        $account_number = $_POST['account_number'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $id = $_POST['id'] ?? 0;

        if ($id > 0) {
            $db->query("UPDATE courier_integrations SET provider_name = ?, api_key = ?, api_secret = ?, account_number = ?, is_active = ? WHERE id = ?", [$provider_name, $api_key, $api_secret, $account_number, $is_active, $id]);
        } else {
            $db->query("INSERT INTO courier_integrations (provider_name, api_key, api_secret, account_number, is_active) VALUES (?, ?, ?, ?, ?)", [$provider_name, $api_key, $api_secret, $account_number, $is_active]);
        }
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    } elseif ($action === 'delete_courier_integration') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM courier_integrations WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

$logistics = $db->query("SELECT * FROM logistics ORDER BY id DESC");
$integrations = $db->query("SELECT * FROM courier_integrations ORDER BY id DESC") ?? [];
$totalTasks = is_array($logistics) ? count($logistics) : 0;
$pending = 0;
$dispatched = 0;
$delivered = 0;
if ($logistics) {
    foreach ($logistics as $l) {
        $s = $l['status'] ?? 'pending';
        if ($s === 'pending')
            $pending++;
        elseif ($s === 'dispatched')
            $dispatched++;
        elseif ($s === 'delivered')
            $delivered++;
    }
}
?>
<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#1b1b1c] 0]  p-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white">Logistics</h2>
                <p class="text-zinc-400 text-sm mt-1">Track shipments, couriers and delivery tasks</p>
            </div>
            <button onclick="openModal('add')"
                class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> New Task
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-[#252526] border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Total Tasks</span>
                    <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                        <i class="bi bi-clipboard-data text-violet-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $totalTasks; ?></p>
            </div>
            <div class="bg-[#252526] border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Pending</span>
                    <span class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                        <i class="bi bi-hourglass-split text-amber-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $pending; ?></p>
            </div>
            <div class="bg-[#252526] border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Dispatched</span>
                    <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                        <i class="bi bi-send text-sky-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $dispatched; ?></p>
            </div>
            <div class="bg-[#252526] border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Delivered</span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i class="bi bi-check-circle text-emerald-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $delivered; ?></p>
            </div>
        </div>

        <!-- Courier Integrations -->
        <div class="bg-[#252526] border border-zinc-800 rounded-xl overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                <h3 class="text-white font-semibold text-sm"><i class="bi bi-truck mr-2 text-zinc-500"></i>Courier Integrations</h3>
                <button onclick="openIntegrationModal('add')"
                    class="bg-violet-600 hover:bg-violet-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-2">
                    <i class="bi bi-plug"></i> Setup Courier
                </button>
            </div>
            <?php if (empty($integrations)): ?>
                <div class="flex flex-col items-center justify-center py-8 text-zinc-500">
                    <i class="bi bi-plug text-3xl mb-2"></i>
                    <p class="text-sm">No couriers configured</p>
                    <p class="text-xs text-zinc-600 mt-1">Connect with delivery providers like The Courier Guy</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
                    <?php foreach ($integrations as $int): ?>
                        <div class="bg-zinc-800/50 border border-zinc-700 rounded-lg p-4 flex flex-col relative group">
                            <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                                <button onclick='openIntegrationModal("edit", <?php echo json_encode($int); ?>)' class="text-zinc-400 hover:text-white transition-colors" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Remove this integration?');">
                                    <input type="hidden" name="action" value="delete_courier_integration">
                                    <input type="hidden" name="id" value="<?php echo $int['id']; ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 transition-colors" title="Remove">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-lg bg-zinc-900 flex items-center justify-center border border-zinc-700">
                                    <i class="bi bi-truck text-violet-400 text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-white font-medium text-sm"><?php echo htmlspecialchars($int['provider_name']); ?></h4>
                                    <span class="text-xs <?php echo !empty($int['is_active']) ? 'text-emerald-400' : 'text-zinc-500'; ?> flex items-center gap-1">
                                        <i class="bi bi-circle-fill" style="font-size: 6px;"></i> 
                                        <?php echo !empty($int['is_active']) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-auto pt-3 border-t border-zinc-700/50 text-xs text-zinc-400">
                                <p class="truncate">Account: <?php echo htmlspecialchars($int['account_number'] ?: 'Not set'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Logistics Table -->
        <div class="bg-[#252526] border border-zinc-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                <h3 class="text-white font-semibold text-sm"><i class="bi bi-box-seam mr-2 text-zinc-500"></i>Shipment
                    Tasks</h3>
            </div>
            <?php if (empty($logistics)): ?>
                <div class="flex flex-col items-center justify-center py-16 text-zinc-500">
                    <i class="bi bi-box-seam text-4xl mb-3"></i>
                    <p class="text-sm">No logistics tasks found</p>
                    <p class="text-xs text-zinc-600 mt-1">Create a task to start tracking shipments</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                                <th class="px-5 py-3 font-medium">Task</th>
                                <th class="px-5 py-3 font-medium">Order Ref</th>
                                <th class="px-5 py-3 font-medium">Courier</th>
                                <th class="px-5 py-3 font-medium">Tracking</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/50">
                            <?php foreach ($logistics as $log): ?>
                                <?php
                                $st = $log['status'] ?? 'pending';
                                $statusClasses = [
                                    'pending' => 'bg-amber-500/10 text-amber-400',
                                    'dispatched' => 'bg-sky-500/10 text-sky-400',
                                    'delivered' => 'bg-emerald-500/10 text-emerald-400',
                                    'cancelled' => 'bg-red-500/10 text-red-400',
                                ];
                                $dotClasses = [
                                    'pending' => 'bg-amber-400',
                                    'dispatched' => 'bg-sky-400',
                                    'delivered' => 'bg-emerald-400',
                                    'cancelled' => 'bg-red-400',
                                ];
                                $sc = $statusClasses[$st] ?? 'bg-zinc-700 text-zinc-400';
                                $dc = $dotClasses[$st] ?? 'bg-zinc-500';
                                ?>
                                <tr class="hover:bg-zinc-800/30 transition-colors group">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-zinc-800 flex items-center justify-center">
                                                <i class="bi bi-box-seam text-zinc-400"></i>
                                            </div>
                                            <div>
                                                <p class="text-white font-medium">
                                                    <?php echo htmlspecialchars($log['task_name'] ?? ''); ?>
                                                </p>
                                                <p class="text-zinc-500 text-xs">#<?php echo $log['id']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-zinc-300 font-mono text-xs">
                                        <?php echo htmlspecialchars($log['order_reference'] ?? '-'); ?>
                                    </td>
                                    <td class="px-5 py-4 text-zinc-300"><?php echo htmlspecialchars($log['courier'] ?? '-'); ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <?php if (!empty($log['tracking_code'])): ?>
                                            <span
                                                class="text-xs font-mono bg-zinc-800 text-zinc-300 px-2 py-1 rounded"><?php echo htmlspecialchars($log['tracking_code']); ?></span>
                                        <?php else: ?>
                                            <span class="text-zinc-600 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full <?php echo $sc; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo $dc; ?>"></span>
                                            <?php echo ucfirst($st); ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div
                                            class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick='openModal("edit", <?php echo json_encode($log); ?>)'
                                                class="p-1.5 rounded-md hover:bg-zinc-700 text-zinc-400 transition-colors"
                                                title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this task?');">
                                                <input type="hidden" name="action" value="delete_logistics">
                                                <input type="hidden" name="id" value="<?php echo $log['id']; ?>">
                                                <button type="submit"
                                                    class="p-1.5 rounded-md hover:bg-red-900/30 text-red-400 transition-colors"
                                                    title="Delete">
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
<div id="logisticsModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-[#252526] /60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="relative bg-[#252526] border border-zinc-800 rounded-xl w-full max-w-md shadow-2xl">
            <form method="POST" id="logisticsForm">
                <input type="hidden" name="action" id="formAction" value="add_logistics">
                <input type="hidden" name="id" id="logisticsId">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold" id="modalTitle">New Task</h3>
                    <button type="button" onclick="closeModal()"
                        class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Task Name</label>
                        <input type="text" name="task_name" id="logisticsTaskName" required
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="e.g. Ship to Johannesburg">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Order Reference</label>
                        <input type="text" name="order_reference" id="logisticsOrderRef"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="#ORD-12345">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Courier</label>
                        <input type="text" name="courier" id="logisticsCourier"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="e.g. The Courier Guy">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Tracking Code</label>
                        <input type="text" name="tracking_code" id="logisticsTracking"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="TRK123456789">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Status</label>
                        <select name="status" id="logisticsStatus"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <option value="pending">Pending</option>
                            <option value="dispatched">Dispatched</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-zinc-800 flex justify-end gap-2">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white transition-colors">Cancel</button>
                    <button type="submit"
                        class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Save
                        Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Integration Modal -->
<div id="integrationModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-[#252526] /60 backdrop-blur-sm" onclick="closeIntegrationModal()"></div>
        <div class="relative bg-[#252526] border border-zinc-800 rounded-xl w-full max-w-md shadow-2xl">
            <form method="POST" id="integrationForm">
                <input type="hidden" name="action" id="integrationFormAction" value="save_courier_integration">
                <input type="hidden" name="id" id="integrationId">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold" id="integrationModalTitle">Setup Courier</h3>
                    <button type="button" onclick="closeIntegrationModal()"
                        class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Provider</label>
                        <select name="provider_name" id="integrationProvider" required
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <option value="The Courier Guy">The Courier Guy</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Account Number (Optional)</label>
                        <input type="text" name="account_number" id="integrationAccount"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="TCG Account Number">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">API Key</label>
                        <input type="text" name="api_key" id="integrationApiKey" required
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="Enter API Key">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">API Secret (Optional)</label>
                        <input type="password" name="api_secret" id="integrationApiSecret"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="Enter API Secret">
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="is_active" id="integrationActive" value="1" class="rounded border-zinc-700 bg-zinc-800 text-violet-500 focus:ring-violet-500" checked>
                        <label class="text-zinc-300 text-sm" for="integrationActive">Integration Active</label>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-zinc-800 flex justify-end gap-2">
                    <button type="button" onclick="closeIntegrationModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white transition-colors">Cancel</button>
                    <button type="submit"
                        class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Save Setup</button>
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
            title.textContent = 'Edit Task';
            action.value = 'update_logistics';
            document.getElementById('logisticsId').value = log.id;
            document.getElementById('logisticsTaskName').value = log.task_name;
            document.getElementById('logisticsOrderRef').value = log.order_reference;
            document.getElementById('logisticsCourier').value = log.courier;
            document.getElementById('logisticsTracking').value = log.tracking_code;
            document.getElementById('logisticsStatus').value = log.status || 'pending';
        } else {
            title.textContent = 'New Task';
            action.value = 'add_logistics';
            form.reset();
            document.getElementById('logisticsId').value = '';
        }
    }

    function closeModal() {
        document.getElementById('logisticsModal').classList.add('hidden');
    }

    function openIntegrationModal(mode, int = null) {
        const modal = document.getElementById('integrationModal');
        const form = document.getElementById('integrationForm');
        const title = document.getElementById('integrationModalTitle');
        modal.classList.remove('hidden');
        if (mode === 'edit' && int) {
            title.textContent = 'Edit Courier Setup';
            document.getElementById('integrationId').value = int.id;
            document.getElementById('integrationProvider').value = int.provider_name;
            document.getElementById('integrationApiKey').value = int.api_key;
            document.getElementById('integrationApiSecret').value = int.api_secret;
            document.getElementById('integrationAccount').value = int.account_number;
            document.getElementById('integrationActive').checked = int.is_active == 1;
        } else {
            title.textContent = 'Setup Courier';
            form.reset();
            document.getElementById('integrationId').value = '';
            document.getElementById('integrationActive').checked = true;
        }
    }

    function closeIntegrationModal() {
        document.getElementById('integrationModal').classList.add('hidden');
    }
</script>