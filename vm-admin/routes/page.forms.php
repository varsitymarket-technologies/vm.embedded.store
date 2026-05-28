<?php
$db = initiate_web_database();

// Create tables if they don't exist
$db->query("CREATE TABLE IF NOT EXISTS forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    email TEXT,
    subject TEXT,
    message TEXT,
    unread INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->query("CREATE TABLE IF NOT EXISTS subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $id = $_POST['id'] ?? 0;
        $db->query("UPDATE forms SET unread = 0 WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    } elseif ($action === 'delete_lead') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM forms WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    } elseif ($action === 'delete_subscriber') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM subscribers WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

$leads_data = $db->query("SELECT * FROM forms ORDER BY id DESC");
$subs_data = $db->query("SELECT * FROM subscribers ORDER BY id DESC");

$leads = [];
$unread = 0;
if ($leads_data) {
    foreach($leads_data as $l) {
        if ($l['unread']) $unread++;
        $leads[] = $l;
    }
}

$subscribers = [];
if ($subs_data) {
    foreach($subs_data as $s) {
        $subscribers[] = $s;
    }
}
$subs_count = count($subscribers);
$totalInquiries = count($leads);
$replied = $totalInquiries - $unread;
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php @include_once "header.php"; ?>

            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#09090b] p-6">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Forms & Subscribers</h2>
                        <p class="text-zinc-400 text-sm mt-1">Manage contact submissions and newsletter subscribers</p>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Total Inquiries</span>
                            <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <i class="bi bi-envelope text-violet-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalInquiries; ?></p>
                    </div>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Unread</span>
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <i class="bi bi-envelope-exclamation text-amber-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $unread; ?></p>
                        <?php if ($unread > 0): ?>
                        <p class="text-amber-400 text-xs mt-1">Requires attention</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Subscribers</span>
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <i class="bi bi-people text-emerald-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $subs_count; ?></p>
                    </div>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Read / Replied</span>
                            <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                                <i class="bi bi-check2-all text-sky-400"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $replied; ?></p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                    <div class="flex border-b border-zinc-800">
                        <button onclick="switchTab('contacts')" id="tab-contacts" class="px-6 py-3 text-sm font-medium transition-colors border-b-2 border-violet-500 text-violet-400">
                            <i class="bi bi-envelope-open mr-1.5"></i>Contact Submissions
                        </button>
                        <button onclick="switchTab('newsletter')" id="tab-newsletter" class="px-6 py-3 text-sm font-medium transition-colors border-b-2 border-transparent text-zinc-500 hover:text-zinc-300">
                            <i class="bi bi-megaphone mr-1.5"></i>Newsletter <span class="ml-1 bg-zinc-800 text-zinc-400 text-xs px-1.5 py-0.5 rounded-full"><?php echo $subs_count; ?></span>
                        </button>
                    </div>

                    <!-- Contacts Tab -->
                    <div id="panel-contacts">
                        <?php if (empty($leads)): ?>
                        <div class="flex flex-col items-center justify-center py-16 text-zinc-500">
                            <i class="bi bi-inbox text-4xl mb-3"></i>
                            <p class="text-sm">No contact submissions yet</p>
                            <p class="text-xs text-zinc-600 mt-1">Submissions from your store's contact form will appear here</p>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                                        <th class="px-5 py-3 font-medium">Sender</th>
                                        <th class="px-5 py-3 font-medium">Subject / Message</th>
                                        <th class="px-5 py-3 font-medium">Date</th>
                                        <th class="px-5 py-3 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-800/50">
                                    <?php foreach ($leads as $lead): ?>
                                    <tr class="hover:bg-zinc-800/30 transition-colors group <?php echo $lead['unread'] ? 'bg-violet-500/[0.03]' : ''; ?>">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-violet-500/10 text-violet-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                    <?php echo strtoupper(substr($lead['name'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-white text-sm font-medium truncate">
                                                        <?php echo htmlspecialchars($lead['name'] ?? 'Unknown'); ?>
                                                        <?php if ($lead['unread']): ?>
                                                        <span class="inline-block w-2 h-2 bg-violet-500 rounded-full ml-1.5"></span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="text-zinc-500 text-xs truncate"><?php echo htmlspecialchars($lead['email'] ?? ''); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 max-w-xs">
                                            <p class="text-zinc-200 text-sm truncate"><?php echo htmlspecialchars($lead['subject'] ?? ''); ?></p>
                                            <p class="text-zinc-500 text-xs truncate"><?php echo htmlspecialchars($lead['message'] ?? ''); ?></p>
                                        </td>
                                        <td class="px-5 py-4 text-zinc-500 text-xs whitespace-nowrap">
                                            <?php echo date('M j, g:i A', strtotime($lead['created_at'] ?? 'now')); ?>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <?php if ($lead['unread']): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <input type="hidden" name="id" value="<?php echo $lead['id']; ?>">
                                                    <button type="submit" class="p-1.5 rounded-md hover:bg-zinc-700 text-violet-400 transition-colors" title="Mark as read">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <button onclick="viewMessage(<?php echo htmlspecialchars(json_encode($lead)); ?>)" class="p-1.5 rounded-md hover:bg-zinc-700 text-zinc-400 transition-colors" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Delete this submission?');">
                                                    <input type="hidden" name="action" value="delete_lead">
                                                    <input type="hidden" name="id" value="<?php echo $lead['id']; ?>">
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

                    <!-- Newsletter Tab -->
                    <div id="panel-newsletter" class="hidden">
                        <?php if (empty($subscribers)): ?>
                        <div class="flex flex-col items-center justify-center py-16 text-zinc-500">
                            <i class="bi bi-megaphone text-4xl mb-3"></i>
                            <p class="text-sm">No subscribers yet</p>
                            <p class="text-xs text-zinc-600 mt-1">Newsletter signups from your store will appear here</p>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                                        <th class="px-5 py-3 font-medium">Email</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3 font-medium">Subscribed</th>
                                        <th class="px-5 py-3 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-800/50">
                                    <?php foreach ($subscribers as $sub): ?>
                                    <tr class="hover:bg-zinc-800/30 transition-colors group">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xs font-bold">
                                                    <?php echo strtoupper(substr($sub['email'] ?? 'U', 0, 1)); ?>
                                                </div>
                                                <span class="text-white text-sm"><?php echo htmlspecialchars($sub['email'] ?? ''); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full <?php echo ($sub['status'] ?? 'active') === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-zinc-700 text-zinc-400'; ?>">
                                                <span class="w-1.5 h-1.5 rounded-full <?php echo ($sub['status'] ?? 'active') === 'active' ? 'bg-emerald-400' : 'bg-zinc-500'; ?>"></span>
                                                <?php echo ucfirst($sub['status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-zinc-500 text-xs"><?php echo date('M j, Y', strtotime($sub['created_at'] ?? 'now')); ?></td>
                                        <td class="px-5 py-4 text-right">
                                            <form method="POST" class="inline opacity-0 group-hover:opacity-100 transition-opacity" onsubmit="return confirm('Remove this subscriber?');">
                                                <input type="hidden" name="action" value="delete_subscriber">
                                                <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                                <button type="submit" class="p-1.5 rounded-md hover:bg-red-900/30 text-red-400 transition-colors" title="Remove">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
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

<!-- View Message Modal -->
<div id="messageModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeMessageModal()"></div>
        <div class="relative bg-zinc-900 border border-zinc-800 rounded-xl w-full max-w-lg shadow-2xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                <h3 class="text-white font-semibold" id="msgModalName">Message</h3>
                <button onclick="closeMessageModal()" class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-5 space-y-3">
                <div>
                    <label class="text-zinc-500 text-xs">From</label>
                    <p class="text-white text-sm" id="msgModalEmail"></p>
                </div>
                <div>
                    <label class="text-zinc-500 text-xs">Subject</label>
                    <p class="text-white text-sm" id="msgModalSubject"></p>
                </div>
                <div>
                    <label class="text-zinc-500 text-xs">Message</label>
                    <p class="text-zinc-300 text-sm leading-relaxed bg-zinc-800/50 rounded-lg p-3 mt-1" id="msgModalBody"></p>
                </div>
                <div>
                    <label class="text-zinc-500 text-xs">Received</label>
                    <p class="text-zinc-400 text-sm" id="msgModalDate"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('panel-contacts').classList.toggle('hidden', tab !== 'contacts');
    document.getElementById('panel-newsletter').classList.toggle('hidden', tab !== 'newsletter');

    const tabC = document.getElementById('tab-contacts');
    const tabN = document.getElementById('tab-newsletter');
    if (tab === 'contacts') {
        tabC.className = tabC.className.replace('border-transparent text-zinc-500', 'border-violet-500 text-violet-400');
        tabN.className = tabN.className.replace('border-violet-500 text-violet-400', 'border-transparent text-zinc-500');
    } else {
        tabN.className = tabN.className.replace('border-transparent text-zinc-500', 'border-violet-500 text-violet-400');
        tabC.className = tabC.className.replace('border-violet-500 text-violet-400', 'border-transparent text-zinc-500');
    }
}

function viewMessage(lead) {
    document.getElementById('msgModalName').textContent = lead.name || 'Unknown';
    document.getElementById('msgModalEmail').textContent = lead.email || '';
    document.getElementById('msgModalSubject').textContent = lead.subject || '(No subject)';
    document.getElementById('msgModalBody').textContent = lead.message || '';
    document.getElementById('msgModalDate').textContent = lead.created_at || '';
    document.getElementById('messageModal').classList.remove('hidden');
}

function closeMessageModal() {
    document.getElementById('messageModal').classList.add('hidden');
}
</script>
