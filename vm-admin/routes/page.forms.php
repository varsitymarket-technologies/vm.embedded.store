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
    }
}

$leads_data = $db->query("SELECT * FROM forms ORDER BY id DESC");
$subs_data = $db->query("SELECT * FROM subscribers ORDER BY id DESC");

$leads = [];
$unread = 0;
if ($leads_data) {
    foreach($leads_data as $l) {
        if ($l['unread']) $unread++;
        $leads[] = [
            'id' => $l['id'],
            'name' => htmlspecialchars($l['name'] ?? ''),
            'email' => htmlspecialchars($l['email'] ?? ''),
            'subject' => htmlspecialchars($l['subject'] ?? ''),
            'message' => htmlspecialchars($l['message'] ?? ''),
            'date' => date('M j, Y g:i A', strtotime($l['created_at'] ?? 'now')),
            'unread' => (bool)$l['unread']
        ];
    }
}

$subscribers = [];
if ($subs_data) {
    foreach($subs_data as $s) {
        $subscribers[] = [
            'email' => htmlspecialchars($s['email'] ?? ''),
            'date' => date('M j, Y', strtotime($s['created_at'] ?? 'now'))
        ];
    }
}
$subs_count = count($subscribers);
$totalInquiries = count($leads);
$convRate = ($totalInquiries > 0 || $subs_count > 0) ? round(($subs_count / ($totalInquiries + $subs_count)) * 100, 1) : 0;
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <div class="flex justify-between items-center mb-6">
                </div>
                
<div class="flex flex-1 flex-col bg-gray-900 text-white min-h-screen" x-data="formManager()">
    <div class="max-w-7xl mx-auto w-full space-y-6">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Inbound Management</h1>
                <p class="text-gray-400 text-sm">Reviewing form submissions and audience growth.</p>
            </div>
            <div class="flex gap-2">
                <button @click="exportData()" class="bg-gray-800 hover:bg-gray-700 border border-white/10 px-4 py-2 rounded-lg text-sm font-medium transition-all">
                    Export CSV
                </button>
                <button class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition-all">
                    Form Settings
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gray-800 border border-white/5 p-5 rounded-2xl">
                <p class="text-gray-500 text-[10px] uppercase font-bold tracking-widest">Total Inquiries</p>
                <h3 class="text-2xl font-bold mt-1" x-text="stats.totalInquiries">0</h3>
                <p class="text-green-400 text-[10px] mt-1">+12% this week</p>
            </div>
            <div class="bg-gray-800 border border-white/5 p-5 rounded-2xl">
                <p class="text-gray-500 text-[10px] uppercase font-bold tracking-widest">Unread Leads</p>
                <h3 class="text-2xl font-bold mt-1 text-orange-400" x-text="stats.unread">0</h3>
                <p class="text-gray-500 text-[10px] mt-1">Requires attention</p>
            </div>
            <div class="bg-gray-800 border border-white/5 p-5 rounded-2xl">
                <p class="text-gray-500 text-[10px] uppercase font-bold tracking-widest">Subscribers</p>
                <h3 class="text-2xl font-bold mt-1 text-purple-400" x-text="stats.subscribers">0</h3>
                <p class="text-purple-400/50 text-[10px] mt-1">Active mailing list</p>
            </div>
            <div class="bg-gray-800 border border-white/5 p-5 rounded-2xl">
                <p class="text-gray-500 text-[10px] uppercase font-bold tracking-widest">Conv. Rate</p>
                <h3 class="text-2xl font-bold mt-1" x-text="stats.convRate + '%'">0%</h3>
                <div class="w-full bg-gray-700 h-1 mt-3 rounded-full overflow-hidden">
                    <div class="bg-purple-500 h-full" :style="`width: ${stats.convRate}%` text-purple-400"></div>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 border border-white/5 rounded-3xl overflow-hidden">
            <div class="flex border-b border-white/5 bg-black/20">
                <button @click="activeTab = 'contacts'" 
                        :class="activeTab === 'contacts' ? 'text-purple-400 border-b-2 border-purple-400 bg-white/5' : 'text-gray-500 hover:text-gray-300'"
                        class="px-8 py-4 text-sm font-bold transition-all">
                    Contact Submissions
                </button>
                <button @click="activeTab = 'newsletter'" 
                        :class="activeTab === 'newsletter' ? 'text-purple-400 border-b-2 border-purple-400 bg-white/5' : 'text-gray-500 hover:text-gray-300'"
                        class="px-8 py-4 text-sm font-bold transition-all">
                    Newsletter List
                </button>
            </div>

            <div x-show="activeTab === 'contacts'" class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-white/5 text-[10px] uppercase text-gray-400">
                        <tr>
                            <th class="px-6 py-4">Sender</th>
                            <th class="px-6 py-4">Subject/Message</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <template x-for="lead in leads" :key="lead.id">
                            <tr class="hover:bg-white/5 transition-colors group" :class="lead.unread ? 'bg-purple-500/5' : ''">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-sm" x-text="lead.name"></div>
                                    <div class="text-xs text-gray-500" x-text="lead.email"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium line-clamp-1" x-text="lead.subject"></div>
                                    <div class="text-xs text-gray-400 line-clamp-1" x-text="lead.message"></div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500" x-text="lead.date"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <form method="POST" class="inline" x-html="`<input type='hidden' name='action' value='mark_read'><input type='hidden' name='id' value='${lead.id}'>`">
                                            <button type="submit" class="p-2 hover:bg-gray-700 rounded-lg text-purple-400" title="Mark as Read">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this lead?');" x-html="`<input type='hidden' name='action' value='delete_lead'><input type='hidden' name='id' value='${lead.id}'>`">
                                            <button type="submit" class="p-2 hover:bg-red-900/30 rounded-lg text-red-500" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="activeTab === 'newsletter'" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-4">
                        <template x-for="sub in subscribers" :key="sub.email">
                            <div class="flex items-center justify-between p-4 bg-black/20 rounded-xl border border-white/5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-purple-600/20 text-purple-400 flex items-center justify-center text-xs font-bold" x-text="sub.email[0].toUpperCase()"></div>
                                    <div>
                                        <div class="text-sm font-medium" x-text="sub.email"></div>
                                        <div class="text-[10px] text-gray-500" x-text="'Joined ' + sub.date"></div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-green-500/10 text-green-500 text-[10px] font-bold rounded">ACTIVE</span>
                            </div>
                        </template>
                    </div>
                    <div class="bg-black/40 rounded-2xl p-6 border border-white/5 h-fit">
                        <h4 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest">Growth Trend</h4>
                        <div class="h-32 bg-gray-900/50 rounded-xl flex items-end justify-between p-2 gap-1">
                            <template x-for="h in [40, 70, 45, 90, 65, 80, 95]">
                                <div class="bg-purple-500/40 hover:bg-purple-500 w-full rounded-t-sm transition-all" :style="`height: ${h}%` text-purple-400"></div>
                            </template>
                        </div>
                        <p class="text-center text-[10px] text-gray-500 mt-4 italic">Subscribers increased by 42% this month.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formManager() {
    return {
        activeTab: 'contacts',
        stats: {
            totalInquiries: <?php echo json_encode($totalInquiries); ?>,
            unread: <?php echo json_encode($unread); ?>,
            subscribers: <?php echo json_encode($subs_count); ?>,
            convRate: <?php echo json_encode($convRate); ?>
        },
        leads: <?php echo json_encode($leads); ?>,
        subscribers: <?php echo json_encode($subscribers); ?>,
        exportData() {
            alert("Generating CSV export for " + this.activeTab + "...");
        }
    }
}
</script>
            </main>
        </div>