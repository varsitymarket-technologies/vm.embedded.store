<?php
$db = initiate_web_database();

$db->query("CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    name TEXT,
    phone TEXT,
    email_verified INTEGER NOT NULL DEFAULT 0,
    failed_login_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->query("CREATE TABLE IF NOT EXISTS customer_sessions (
    token TEXT PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    user_agent TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
)");
$db->query("CREATE INDEX IF NOT EXISTS idx_sessions_customer ON customer_sessions(customer_id)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_customer') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $email_verified = !empty($_POST['email_verified']) ? 1 : 0;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ./users?error=invalid_email');
            exit;
        }
        if (strlen($password) < 8) {
            header('Location: ./users?error=weak_password');
            exit;
        }

        $existing = $db->query("SELECT id FROM customers WHERE email = ? LIMIT 1", [$email]);
        if (!empty($existing)) {
            header('Location: ./users?error=duplicate_email');
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO customers (email, password_hash, name, phone, email_verified) VALUES (?, ?, ?, ?, ?)",
            [$email, $passwordHash, $name !== '' ? $name : null, $phone !== '' ? $phone : null, $email_verified]
        );
        header('Location: ./users?created=1');
        exit;
    }

    if ($action === 'save_customer') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email_verified = !empty($_POST['email_verified']) ? 1 : 0;

        $db->query("UPDATE customers SET name = ?, phone = ?, email_verified = ? WHERE id = ?", [
            $name !== '' ? $name : null,
            $phone !== '' ? $phone : null,
            $email_verified,
            $id
        ]);
        header('Location: ./users?saved=1');
        exit;
    }

    if ($action === 'delete_customer') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->query("DELETE FROM customers WHERE id = ?", [$id]);
        header('Location: ./users?deleted=1');
        exit;
    }
}

$customers = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM customer_sessions cs WHERE cs.customer_id = c.id AND cs.expires_at > datetime('now')) AS active_sessions
    FROM customers c
    ORDER BY c.created_at DESC, c.id DESC
") ?: [];

$totalCustomers = count($customers);
$verifiedCustomers = 0;
$lockedCustomers = 0;
$activeSessions = 0;
foreach ($customers as $customer) {
    if (!empty($customer['email_verified'])) {
        $verifiedCustomers++;
    }
    if (!empty($customer['locked_until'])) {
        $lockedUntil = strtotime($customer['locked_until']);
        if ($lockedUntil !== false && $lockedUntil > time()) {
            $lockedCustomers++;
        }
    }
    $activeSessions += (int) ($customer['active_sessions'] ?? 0);
}

$recentCustomers = array_slice($customers, 0, 10);
$lockedCount = $lockedCustomers;
?>
<div class="flex flex-1 flex-col overflow-hidden bg-[#1b1b1c] min-h-screen text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden p-6 lg:p-8 space-y-6">
        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[linear-gradient(135deg,#f5f7fa_0%,#edf2f7_48%,#ffffff_100%)] text-slate-900 shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
            <div class="absolute inset-0 opacity-70">
                <div class="absolute -right-20 top-[-5rem] h-64 w-64 rounded-full bg-emerald-200/70 blur-3xl"></div>
                <div class="absolute left-1/3 top-10 h-40 w-40 rounded-full bg-sky-200/70 blur-3xl"></div>
            </div>
            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)] lg:p-8">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1 text-xs font-semibold text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-[#008060]"></span>
                        Customer accounts
                    </div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Manage store customers, sessions, and verification.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        Keep track of verified accounts, lockouts, and active sessions from one tabular admin screen.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button onclick="openCreateModal()" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                            <i class="bi bi-person-plus-fill"></i>
                            <span>Add client</span>
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total clients</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-950"><?php echo $totalCustomers; ?></p>
                        <p class="mt-1 text-sm text-slate-500">All customer accounts</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Active sessions</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-950"><?php echo $activeSessions; ?></p>
                        <p class="mt-1 text-sm text-slate-500">Live login tokens</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Total clients</span>
                    <i class="bi bi-people text-violet-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?php echo $totalCustomers; ?></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Verified</span>
                    <i class="bi bi-shield-check text-emerald-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?php echo $verifiedCustomers; ?></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Locked</span>
                    <i class="bi bi-lock-fill text-amber-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?php echo $lockedCount; ?></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Sessions</span>
                    <i class="bi bi-window-stack text-sky-300"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?php echo $activeSessions; ?></p>
            </div>
        </section>

        <section class="rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="relative w-full md:max-w-md">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm"></i>
                    <input type="text" id="searchInput" placeholder="Search by name or email..." oninput="filterCustomers()" class="w-full rounded-full border border-white/10 bg-[#1b1b1c] pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-[#008060]">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="setStatusFilter('all')" data-filter="all" class="status-tab active-tab rounded-full border border-[#008060] bg-[#008060] px-4 py-2 text-xs font-semibold text-white transition-colors">All</button>
                    <button onclick="setStatusFilter('verified')" data-filter="verified" class="status-tab rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-zinc-300 transition-colors">Verified</button>
                    <button onclick="setStatusFilter('locked')" data-filter="locked" class="status-tab rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-zinc-300 transition-colors">Locked</button>
                    <button onclick="setStatusFilter('unverified')" data-filter="unverified" class="status-tab rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-zinc-300 transition-colors">Unverified</button>
                </div>
            </div>
        </section>

        <?php if (empty($customers)): ?>
            <div class="rounded-3xl border border-white/10 bg-[#202123] p-16 text-center shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="inline-flex items-center justify-center h-20 w-20 rounded-full bg-white/5 mb-5">
                    <i class="bi bi-person-lines-fill text-4xl text-zinc-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">No store clients yet</h3>
                <p class="text-sm text-zinc-400 mb-6 max-w-sm mx-auto">Customer accounts will appear here after shoppers
                    register or place orders with saved accounts.</p>
            </div>
        <?php else: ?>
            <div id="noResults" class="rounded-3xl border border-white/10 bg-[#202123] p-12 text-center hidden mb-6 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-white/5 mb-4">
                    <i class="bi bi-search text-2xl text-zinc-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-white mb-1">No matches found</h3>
                <p class="text-sm text-zinc-400">Try another search term or status filter.</p>
            </div>

            <div class="rounded-3xl border border-white/10 bg-[#202123] overflow-hidden shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-300">
                        <thead class="bg-white/5 text-xs uppercase text-zinc-400 tracking-[0.18em]">
                            <tr>
                                <th scope="col" class="px-6 py-4">Client</th>
                                <th scope="col" class="px-6 py-4">Status</th>
                                <th scope="col" class="px-6 py-4">Sessions</th>
                                <th scope="col" class="px-6 py-4">Joined</th>
                                <th scope="col" class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php foreach ($recentCustomers as $customer):
                                $lockedUntil = !empty($customer['locked_until']) ? strtotime($customer['locked_until']) : false;
                                $isLocked = $lockedUntil !== false && $lockedUntil > time();
                                $status = !empty($customer['email_verified']) ? 'verified' : 'unverified';
                                if ($isLocked) {
                                    $status = 'locked';
                                }
                                $initial = strtoupper(substr($customer['name'] ?? $customer['email'] ?? '?', 0, 1));
                                $avatarClass = $status === 'verified' ? 'bg-emerald-500/15 text-emerald-300' : ($status === 'locked' ? 'bg-rose-500/15 text-rose-300' : 'bg-white/5 text-zinc-200');
                                $badgeClass = $status === 'verified' ? 'bg-emerald-500/10 text-emerald-300 ring-1 ring-emerald-500/20' : ($status === 'locked' ? 'bg-rose-500/10 text-rose-300 ring-1 ring-rose-500/20' : 'bg-white/5 text-zinc-300 ring-1 ring-white/10');
                                $createdAt = !empty($customer['created_at']) ? date('M j, Y', strtotime($customer['created_at'])) : 'N/A';
                                ?>
                                <tr class="customer-row hover:bg-white/[0.03] transition-colors"
                                    data-name="<?php echo htmlspecialchars(strtolower($customer['name'] ?? '')); ?>"
                                    data-email="<?php echo htmlspecialchars(strtolower($customer['email'] ?? '')); ?>"
                                    data-status="<?php echo htmlspecialchars($status); ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-10 w-10 rounded-full <?php echo $avatarClass; ?> flex items-center justify-center font-semibold text-sm flex-shrink-0">
                                                <?php echo $initial; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-medium text-white truncate">
                                                    <?php echo htmlspecialchars($customer['name'] ?: $customer['email']); ?>
                                                </div>
                                                <div class="text-xs text-zinc-500 truncate">
                                                    <?php echo htmlspecialchars($customer['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?php echo $badgeClass; ?>">
                                            <?php echo $status === 'verified' ? 'Verified' : ($status === 'locked' ? 'Locked' : 'Unverified'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300 text-sm">
                                        <?php echo (int) ($customer['active_sessions'] ?? 0); ?> active
                                    </td>
                                    <td class="px-6 py-4 text-zinc-400 text-sm"><?php echo $createdAt; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2 justify-end">
                                            <button onclick='openModal(<?php echo json_encode($customer); ?>)'
                                                class="h-8 w-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-sky-300 transition-colors"
                                                title="Edit client">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button
                                                onclick='confirmDelete(<?php echo (int) $customer["id"]; ?>, "<?php echo htmlspecialchars(addslashes($customer["email"])); ?>")'
                                                class="h-8 w-8 rounded-full bg-white/5 hover:bg-red-600/20 flex items-center justify-center text-red-300 transition-colors"
                                                title="Delete client">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<div id="createCustomerModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeCreateModal()"></div>
        <div class="relative w-full max-w-md bg-[#202123] rounded-3xl shadow-2xl border border-white/10 transform transition-all">
            <form method="POST" id="createCustomerForm">
                <input type="hidden" name="action" value="create_customer">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Add Client</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Create a customer account from the admin side</p>
                    </div>
                    <button type="button" onclick="closeCreateModal()"
                        class="h-8 w-8 rounded-lg hover:bg-white/5 flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1.5">Email</label>
                        <input type="email" name="email" required
                            class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#008060] transition-colors"
                            placeholder="customer@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1.5">Name</label>
                        <input type="text" name="name"
                            class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#008060] transition-colors"
                            placeholder="Client name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1.5">Phone</label>
                        <input type="text" name="phone"
                            class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#008060] transition-colors"
                            placeholder="Phone number">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1.5">Temporary Password</label>
                        <input type="password" name="password" required
                            class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#008060] transition-colors"
                            placeholder="At least 8 characters">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="email_verified" class="w-4 h-4 accent-[#008060] rounded">
                        <span class="text-zinc-300 text-xs">Mark email as verified immediately</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-white/10 bg-white/5">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2.5 rounded-full border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-full bg-[#008060] hover:bg-[#006e52] text-sm font-medium text-white transition-colors shadow-lg shadow-emerald-700/20">Create Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="customerModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="relative w-full max-w-md bg-[#202123] rounded-3xl shadow-2xl border border-white/10 transform transition-all">
            <form method="POST" id="customerForm">
                <input type="hidden" name="action" value="save_customer">
                <input type="hidden" name="id" id="customerId">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Edit Client</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Update client profile data and verification state</p>
                    </div>
                    <button type="button" onclick="closeModal()"
                        class="h-8 w-8 rounded-lg hover:bg-white/5 flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1.5">Email</label>
                        <input type="text" id="customerEmail" disabled
                            class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-3.5 py-2.5 text-sm text-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1.5">Name</label>
                        <input type="text" name="name" id="customerName"
                            class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#008060] transition-colors"
                            placeholder="Customer name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1.5">Phone</label>
                        <input type="text" name="phone" id="customerPhone"
                            class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#008060] transition-colors"
                            placeholder="Phone number">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="email_verified" id="customerVerified" class="w-4 h-4 accent-[#008060] rounded">
                        <span class="text-zinc-300 text-xs">Mark email as verified</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-white/10 bg-white/5">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2.5 rounded-full border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-full bg-[#008060] hover:bg-[#006e52] text-sm font-medium text-white transition-colors shadow-lg shadow-emerald-700/20">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
        <div class="relative w-full max-w-sm bg-[#202123] rounded-3xl shadow-2xl border border-white/10">
            <div class="p-6 text-center">
                <div class="inline-flex items-center justify-center h-14 w-14 rounded-full bg-red-500/15 mb-4">
                    <i class="bi bi-exclamation-triangle-fill text-2xl text-red-300"></i>
                </div>
                <h3 class="text-lg font-semibold text-white mb-1">Delete Client</h3>
                <p class="text-sm text-zinc-400 mb-6">Are you sure you want to delete <strong id="deleteCustomerName"
                        class="text-white"></strong>? This will remove their account and session records.</p>
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-2.5 rounded-full border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10 transition-colors">Cancel</button>
                    <form method="POST" id="deleteForm" class="flex-1">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="id" id="deleteCustomerId">
                        <button type="submit"
                            class="w-full px-4 py-2.5 rounded-full bg-red-600 hover:bg-red-700 text-sm font-medium text-white transition-colors">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .status-tab {
        color: #d1d5db;
    }

    .status-tab.active-tab {
        background-color: #008060;
        color: #fff;
        border-color: #008060;
    }
</style>

<script>
    let currentStatusFilter = 'all';

    function openCreateModal() {
        document.getElementById('createCustomerModal').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('createCustomerModal').classList.add('hidden');
    }

    function openModal(customer) {
        document.getElementById('customerId').value = customer.id || '';
        document.getElementById('customerEmail').value = customer.email || '';
        document.getElementById('customerName').value = customer.name || '';
        document.getElementById('customerPhone').value = customer.phone || '';
        document.getElementById('customerVerified').checked = !!customer.email_verified;
        document.getElementById('customerModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('customerModal').classList.add('hidden');
    }

    function confirmDelete(id, email) {
        document.getElementById('deleteCustomerId').value = id;
        document.getElementById('deleteCustomerName').textContent = email;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    function setStatusFilter(status) {
        currentStatusFilter = status;
        document.querySelectorAll('.status-tab').forEach(function (tab) {
            tab.classList.remove('active-tab');
            if (tab.getAttribute('data-filter') === status) {
                tab.classList.add('active-tab');
            }
        });
        filterCustomers();
    }

    function filterCustomers() {
        const query = (document.getElementById('searchInput').value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.customer-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';
            const status = row.getAttribute('data-status') || '';
            const matchesSearch = !query || name.indexOf(query) !== -1 || email.indexOf(query) !== -1;
            const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;
            const visible = matchesSearch && matchesStatus;
            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        const noResults = document.getElementById('noResults');
        if (noResults) {
            noResults.classList.toggle('hidden', visibleCount !== 0);
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeModal();
            closeDeleteModal();
        }
    });
</script>
