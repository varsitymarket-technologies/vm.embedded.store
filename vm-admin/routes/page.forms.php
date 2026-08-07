<?php
// Initialize database connection
$db = initiate_web_database();

// Core structural tables
$db->query("CREATE TABLE IF NOT EXISTS forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_key TEXT UNIQUE,
    name TEXT,
    fields TEXT, -- JSON representation of fields
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->query("CREATE TABLE IF NOT EXISTS form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_key TEXT,
    data TEXT, -- JSON payload of submitted fields
    unread INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->query("CREATE TABLE IF NOT EXISTS subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Dynamic table structure checks / migrations can also live here if desired.

// Handle Dashboard & Form Builder Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_form') {
        $name = $_POST['form_name'] ?? 'Untitled Form';
        $form_key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name)) . '_' . time();
        $fields = $_POST['fields_json'] ?? '[]';

        $db->query("INSERT INTO forms (form_key, name, fields) VALUES (?, ?, ?)", [$form_key, $name, $fields]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=builder&success=1");
        exit;
    } elseif ($action === 'mark_read') {
        $id = $_POST['id'] ?? 0;
        $db->query("UPDATE form_submissions SET unread = 0 WHERE id = ?", [$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($action === 'delete_submission') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM form_submissions WHERE id = ?", [$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($action === 'delete_subscriber') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM subscribers WHERE id = ?", [$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch stats and lists
$all_forms = $db->query("SELECT * FROM forms ORDER BY id DESC") ?: [];
$submissions_data = $db->query("SELECT * FROM form_submissions ORDER BY id DESC") ?: [];
$subs_data = $db->query("SELECT * FROM subscribers ORDER BY id DESC") ?: [];

$submissions = [];
$unread = 0;
foreach ($submissions_data as $sub) {
    if ($sub['unread'])
        $unread++;
    $submissions[] = $sub;
}

$subscribers = [];
foreach ($subs_data as $s) {
    $subscribers[] = $s;
}

$total_submissions = count($submissions);
$subs_count = count($subscribers);
$replied = $total_submissions - $unread;
$active_tab = $_GET['tab'] ?? 'submissions';
$latest_form = $all_forms[0] ?? null;
?>
<!-- Main Content Wrapper -->
<div class="flex flex-1 flex-col overflow-hidden bg-[#1b1b1c]  min-h-screen text-zinc-100 font-sans">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">

        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#242424] text-white">
            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)] lg:p-8">
                <div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Manage store forms, responses, and newsletter.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white sm:text-base">
                        Build custom forms, inspect submissions, and copy integration snippets from a streamlined admin workspace.
                    </p>

                </div>

            </div>
        </section>

        <section class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Forms tracked</span>
                    <i class="bi bi-file-earmark-plus text-white"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?= count($all_forms); ?></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Submissions</span>
                    <i class="bi bi-inboxes text-white"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?= $total_submissions; ?></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Unread</span>
                    <i class="bi bi-envelope-exclamation text-white"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?= $unread; ?></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Subscribers</span>
                    <i class="bi bi-people text-white"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold text-white"><?= $subs_count; ?></p>
            </div>
        </section>

        <section class="flex flex-wrap gap-2">
            <button onclick="switchView('submissions')" id="btn-tab-submissions"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition-colors <?= $active_tab === 'submissions' ? 'border-[#008060] bg-[#008060] text-white' : 'border-white/10 bg-white/5 text-zinc-300 hover:bg-white/10' ?>">
                <i class="bi bi-chat-left-text"></i>
                <span>Form responses</span>
            </button>
            <button onclick="switchView('builder')" id="btn-tab-builder"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition-colors <?= $active_tab === 'builder' ? 'border-[#008060] bg-[#008060] text-white' : 'border-white/10 bg-white/5 text-zinc-300 hover:bg-white/10' ?>">
                <i class="bi bi-hammer"></i>
                <span>Visual builder</span>
            </button>
            <button onclick="switchView('newsletter')" id="btn-tab-newsletter"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition-colors <?= $active_tab === 'newsletter' ? 'border-[#008060] bg-[#008060] text-white' : 'border-white/10 bg-white/5 text-zinc-300 hover:bg-white/10' ?>">
                <i class="bi bi-envelope-check"></i>
                <span>Newsletter hub</span>
            </button>
        </section>

        <!-- VIEW 1: Submissions Manager -->
        <div id="section-submissions" class="<?= $active_tab !== 'submissions' ? 'hidden' : '' ?>">
            <div class="rounded-3xl border border-white/10 bg-[#202123] overflow-hidden shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <?php if (empty($submissions)): ?>
                    <div class="flex flex-col items-center justify-center py-20 text-zinc-500">
                        <i class="bi bi-mailbox2 text-5xl mb-4 text-zinc-700"></i>
                        <p class="text-sm font-medium text-zinc-300">No submissions found</p>
                        <p class="text-xs text-zinc-500 mt-1">Deploy form markup outputted from your builder to begin
                            gathering leads.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-white/10 text-xs text-zinc-400 uppercase tracking-[0.18em] bg-white/5">
                                    <th class="px-6 py-4 font-medium">Form Name / Key</th>
                                    <th class="px-6 py-4 font-medium">Parsed Submission Snapshot</th>
                                    <th class="px-6 py-4 font-medium">Captured Date</th>
                                    <th class="px-6 py-4 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <?php foreach ($submissions as $sub):
                                    $data = json_decode($sub['data'] ?? '{}', true);
                                    // Locate the parent form settings
                                    $form_meta = array_values(array_filter($all_forms, fn($f) => $f['form_key'] === $sub['form_key']))[0] ?? null;
                                    $form_title = $form_meta ? $form_meta['name'] : 'Unknown Form';

                                    // Extract simple previews dynamically (e.g. email, name keys if present, otherwise first 2 elements)
                                    $preview = [];
                                    foreach (array_slice($data, 0, 3) as $k => $v) {
                                        $preview[] = "<strong>" . htmlspecialchars($k) . "</strong>: " . htmlspecialchars(substr($v, 0, 45));
                                    }
                                    $preview_str = implode(' | ', $preview);
                                    ?>
                                    <tr class="hover:bg-white/[0.03] transition-colors group <?= $sub['unread'] ? 'bg-emerald-500/[0.03]' : ''; ?>">
                                        <td class="px-6 py-4">
                                            <p class="text-white text-sm font-semibold flex items-center gap-1.5">
                                                <?= htmlspecialchars($form_title); ?>
                                                <?php if ($sub['unread']): ?>
                                                    <span class="inline-block w-2 h-2 bg-[#008060] rounded-full animate-pulse"></span>
                                                <?php endif; ?>
                                            </p>
                                            <span
                                                class="text-zinc-500 text-xs font-mono select-all"><?= htmlspecialchars($sub['form_key']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 max-w-md">
                                            <p class="text-zinc-300 text-xs truncate leading-relaxed"><?= $preview_str; ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-zinc-400 text-xs whitespace-nowrap">
                                            <?= date('M j, Y • g:i A', strtotime($sub['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div
                                                class="flex justify-end gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                                <?php if ($sub['unread']): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="mark_read">
                                                        <input type="hidden" name="id" value="<?= $sub['id']; ?>">
                                                        <button type="submit"
                                                            class="p-2 rounded-lg hover:bg-zinc-800 text-violet-400 transition-colors"
                                                            title="Mark Read">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <button
                                                    onclick='viewCustomPayload(<?= json_encode($data); ?>, "<?= htmlspecialchars($form_title); ?>")'
                                                    class="p-2 rounded-lg hover:bg-white/5 text-zinc-300 transition-colors"
                                                    title="View Submission Payload">
                                                    <i class="bi bi-window-sidebar"></i>
                                                </button>
                                                <form method="POST" class="inline"
                                                    onsubmit="return confirm('Permanently remove this submission?');">
                                                    <input type="hidden" name="action" value="delete_submission">
                                                    <input type="hidden" name="id" value="<?= $sub['id']; ?>">
                                                    <button type="submit"
                                                        class="p-2 rounded-lg hover:bg-red-950/40 text-red-400 transition-colors"
                                                        title="Delete Entry">
                                                        <i class="bi bi-trash"></i>
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
        </div>

        <!-- VIEW 2: Form Builder Panel -->
        <div id="section-builder" class="<?= $active_tab !== 'builder' ? 'hidden' : '' ?> grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Configurator Workspace -->
            <div class="lg:col-span-5 rounded-3xl border border-white/10 bg-[#202123] p-5 flex flex-col gap-6 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <div>
                    <h3 class="text-lg font-bold text-white mb-1">Form creator</h3>
                    <p class="text-zinc-400 text-xs">Define dynamic fields, properties, and constraints.</p>
                </div>

                <!-- Core Parameters -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 mb-2">Form Label / Title</label>
                    <input id="new-form-name" type="text" placeholder="e.g. Quote Request Form"
                        class="w-full bg-[#1b1b1c] border border-white/10 rounded-2xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-[#008060] transition-colors">
                </div>

                <!-- Available Elements Drawer -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">Click to
                        Insert Field</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="addField('text')"
                            class="flex items-center gap-2 bg-[#1b1b1c] border border-white/10 hover:border-[#008060] hover:bg-white/5 text-left px-3.5 py-2.5 rounded-2xl text-xs font-medium text-zinc-300 transition-all">
                            <i class="bi bi-input-cursor text-emerald-300"></i> Text Field
                        </button>
                        <button onclick="addField('email')"
                            class="flex items-center gap-2 bg-[#1b1b1c] border border-white/10 hover:border-[#008060] hover:bg-white/5 text-left px-3.5 py-2.5 rounded-2xl text-xs font-medium text-zinc-300 transition-all">
                            <i class="bi bi-envelope text-emerald-300"></i> Email Input
                        </button>
                        <button onclick="addField('tel')"
                            class="flex items-center gap-2 bg-[#1b1b1c] border border-white/10 hover:border-[#008060] hover:bg-white/5 text-left px-3.5 py-2.5 rounded-2xl text-xs font-medium text-zinc-300 transition-all">
                            <i class="bi bi-telephone text-emerald-300"></i> Telephone Input
                        </button>
                        <button onclick="addField('textarea')"
                            class="flex items-center gap-2 bg-[#1b1b1c] border border-white/10 hover:border-[#008060] hover:bg-white/5 text-left px-3.5 py-2.5 rounded-2xl text-xs font-medium text-zinc-300 transition-all">
                            <i class="bi bi-justify-left text-emerald-300"></i> Text Area
                        </button>
                        <button onclick="addField('select')"
                            class="flex items-center gap-2 bg-[#1b1b1c] border border-white/10 hover:border-[#008060] hover:bg-white/5 text-left px-3.5 py-2.5 rounded-2xl text-xs font-medium text-zinc-300 transition-all">
                            <i class="bi bi-menu-button-wide text-emerald-300"></i> Dropdown Menu
                        </button>
                    </div>
                </div>

                <!-- Output Generator Trigger -->
                <form id="save-form-schema" method="POST" class="mt-auto pt-4 border-t border-white/10 flex gap-2">
                    <input type="hidden" name="action" value="create_form">
                    <input type="hidden" name="form_name" id="form-name-post">
                    <input type="hidden" name="fields_json" id="fields-json-post">
                    <button type="button" onclick="commitFormSchema()"
                        class="flex-1 bg-[#008060] hover:bg-[#006e52] text-white font-medium text-sm py-3 px-4 rounded-full transition-colors flex items-center justify-center gap-2 shadow-lg shadow-emerald-700/20">
                        <i class="bi bi-save"></i> Build & Generate Integration Snippet
                    </button>
                </form>
            </div>

            <!-- Right Canvas Preview & Live Re-ordering -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-zinc-400">Interactive design preview
                    </h3>
                    <button onclick="clearCanvas()"
                        class="text-xs text-zinc-500 hover:text-red-400 transition-colors">Clear All Fields</button>
                </div>

                <div class="bg-[#202123] border border-white/10 rounded-3xl p-6 min-h-[420px] flex flex-col gap-4 shadow-[0_18px_45px_rgba(0,0,0,0.24)] relative">
                    <div id="blank-slate-msg"
                        class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 pointer-events-none">
                        <i class="bi bi-palette text-4xl mb-2"></i>
                        <p class="text-xs">Drag/add elements on the left side menu to map out fields</p>
                    </div>

                    <!-- Builder Active Fields Wrapper -->
                    <div id="builder-canvas" class="space-y-4 z-10"></div>
                </div>

                <!-- Forms List & Integration Snippet Fetcher -->
                <div class="bg-[#202123] border border-white/10 rounded-3xl p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                    <h4 class="text-sm font-bold text-white mb-3">Your deployable forms</h4>
                    <?php if (empty($all_forms)): ?>
                        <p class="text-xs text-zinc-500 italic">No custom templates built yet.</p>
                    <?php else: ?>
                        <div class="space-y-2.5 max-h-48 overflow-y-auto">
                            <?php foreach ($all_forms as $item): ?>
                                <div
                                    class="flex items-center justify-between bg-[#1b1b1c] p-3 rounded-2xl border border-white/10">
                                    <div>
                                        <p class="text-xs font-semibold text-zinc-200"><?= htmlspecialchars($item['name']); ?>
                                        </p>
                                        <span
                                            class="text-[10px] text-zinc-500 font-mono"><?= htmlspecialchars($item['form_key']); ?></span>
                                    </div>
                                    <button onclick='launchIntegrationInstructions("<?= $item['form_key']; ?>")'
                                        class="bg-white/5 hover:bg-white/10 text-zinc-200 text-xs px-2.5 py-1.5 rounded-full transition-colors flex items-center gap-1.5">
                                        <i class="bi bi-code-slash"></i> Get Embed Code
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- VIEW 3: Newsletter Subscriptions -->
        <div id="section-newsletter" class="hidden">
            <div class="bg-[#202123] border border-white/10 rounded-3xl overflow-hidden shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                <?php if (empty($subscribers)): ?>
                    <div class="flex flex-col items-center justify-center py-20 text-zinc-500">
                        <i class="bi bi-mailbox text-5xl mb-4 text-zinc-700"></i>
                        <p class="text-sm font-medium text-zinc-400">No active subscribers</p>
                        <p class="text-xs text-zinc-600 mt-1">Users opting in via integration APIs will show up here.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b border-zinc-800 text-xs text-zinc-500 uppercase tracking-wider bg-[#252526]/50">
                                    <th class="px-6 py-4 font-medium">Email</th>
                                    <th class="px-6 py-4 font-medium">Status Flag</th>
                                    <th class="px-6 py-4 font-medium">Date Created</th>
                                    <th class="px-6 py-4 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/50">
                                <?php foreach ($subscribers as $sub): ?>
                                    <tr class="hover:bg-zinc-800/20 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xs font-bold font-mono">
                                                    <?= strtoupper(substr($sub['email'] ?? 'A', 0, 1)); ?>
                                                </div>
                                                <span
                                                    class="text-white text-sm font-medium"><?= htmlspecialchars($sub['email'] ?? ''); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                <?= ucfirst($sub['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-zinc-500 text-xs">
                                            <?= date('M j, Y • g:i A', strtotime($sub['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <form method="POST" class="inline" onsubmit="return confirm('Remove Subscriber?');">
                                                <input type="hidden" name="action" value="delete_subscriber">
                                                <input type="hidden" name="id" value="<?= $sub['id']; ?>">
                                                <button type="submit"
                                                    class="p-2 rounded-lg hover:bg-red-950/40 text-red-400 transition-colors"
                                                    title="Remove Subscriber">
                                                    <i class="bi bi-trash"></i>
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

<!-- Modal: Dynamic Custom Submission Payload Inspector -->
<div id="inspectorModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeInspector()"></div>
        <div class="relative bg-[#202123] border border-white/10 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden z-10">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/10 bg-white/5">
                <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                    <i class="bi bi-box-arrow-in-right text-emerald-300"></i> Inquiry Meta Payload - <span
                        id="inspectFormTitle" class="text-zinc-400 font-normal"></span>
                </h3>
                <button onclick="closeInspector()" class="text-zinc-500 hover:text-white transition-colors"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto" id="inspectDataGrid"></div>
        </div>
    </div>
</div>

<!-- Modal: Embed & Integration Snippets Generator -->
<div id="embedModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeEmbedModal()"></div>
        <div class="relative bg-[#202123] border border-white/10 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden z-10">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/10 bg-white/5">
                <h3 class="text-white font-semibold text-sm flex items-center gap-2">
                    <i class="bi bi-code-square text-emerald-300"></i> Dynamic Integration Snippets
                </h3>
                <button onclick="closeEmbedModal()" class="text-zinc-500 hover:text-white transition-colors"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <span class="text-[10px] text-zinc-500 font-mono block mb-1">Target Form Key ID</span>
                    <p id="embedFormKey"
                        class="text-white font-mono text-sm bg-zinc-950 p-2 border border-zinc-800/80 rounded select-all">
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="flex border-b border-zinc-800 gap-2">
                        <button onclick="switchEmbedTab('embed-html')" id="tab-embed-html"
                            class="px-4 py-2 text-xs font-semibold border-b-2 border-emerald-500 text-emerald-400">HTML
                            Embed Code (Any Framework)</button>
                        <button onclick="switchEmbedTab('embed-php')" id="tab-embed-php"
                            class="px-4 py-2 text-xs font-semibold border-b-2 border-transparent text-zinc-400 hover:text-zinc-200">Vanilla
                            PHP Form Action</button>
                    </div>

                    <!-- HTML Snippet -->
                    <div id="panel-embed-html" class="space-y-2">
                        <p class="text-zinc-400 text-xs leading-relaxed">Paste this markup into your client template.
                            Includes AJAX submission handing seamlessly.</p>
                        <pre class="bg-[#1b1b1c] border border-white/10 p-3 rounded-2xl overflow-x-auto text-[11px] text-emerald-300 font-mono"
                            id="snippet-html-code"></pre>
                    </div>

                    <!-- Dynamic Action Snippet -->
                    <div id="panel-embed-php" class="space-y-2 hidden">
                        <p class="text-zinc-400 text-xs leading-relaxed">Standard PHP structure executing traditional
                            POST requests routing straight through our dynamic engine.</p>
                        <pre class="bg-[#1b1b1c] border border-white/10 p-3 rounded-2xl overflow-x-auto text-[11px] text-emerald-300 font-mono"
                            id="snippet-php-code"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab View Switching
    function switchView(tab) {
        document.getElementById('section-submissions').classList.toggle('hidden', tab !== 'submissions');
        document.getElementById('section-builder').classList.toggle('hidden', tab !== 'builder');
        document.getElementById('section-newsletter').classList.toggle('hidden', tab !== 'newsletter');

        const btnSub = document.getElementById('btn-tab-submissions');
        const btnBld = document.getElementById('btn-tab-builder');
        const btnNwl = document.getElementById('btn-tab-newsletter');

        [btnSub, btnBld, btnNwl].forEach(b => {
            if (b) b.className = b.className.replace('border-violet-500 text-violet-400', 'border-transparent text-zinc-400 hover:text-zinc-200');
        });

        const activeBtn = document.getElementById(`btn-tab-${tab}`);
        if (activeBtn) {
            activeBtn.className = activeBtn.className.replace('border-transparent text-zinc-400 hover:text-zinc-200', 'border-violet-500 text-violet-400');
        }
    }

    // Form Builder Application State Matrix
    let formFields = [];

    function checkBlankSlate() {
        const slate = document.getElementById('blank-slate-msg');
        if (slate) slate.style.display = formFields.length === 0 ? 'flex' : 'none';
    }

    function addField(type) {
        const defaultLabel = type.charAt(0).toUpperCase() + type.slice(1) + " Label";
        const nameAttr = type + '_' + Math.random().toString(36).substring(2, 7);

        let field = {
            id: Date.now() + Math.random().toString(36).substring(2, 5),
            type: type,
            label: defaultLabel,
            name: nameAttr,
            placeholder: "Enter value here...",
            required: true,
            options: type === 'select' ? ['Choice A', 'Choice B', 'Choice C'] : []
        };

        formFields.push(field);
        renderCanvas();
    }

    function removeField(id) {
        formFields = formFields.filter(f => f.id !== id);
        renderCanvas();
    }

    function updateFieldLabel(id, val) {
        const idx = formFields.findIndex(f => f.id === id);
        if (idx !== -1) {
            formFields[idx].label = val;
            // Dynamically auto-generate clean name tag unless overridden
            formFields[idx].name = val.toLowerCase().replace(/[^a-z0-9]/g, '_');
        }
    }

    function updateFieldPlaceholder(id, val) {
        const idx = formFields.findIndex(f => f.id === id);
        if (idx !== -1) formFields[idx].placeholder = val;
    }

    function toggleRequired(id) {
        const idx = formFields.findIndex(f => f.id === id);
        if (idx !== -1) formFields[idx].required = !formFields[idx].required;
    }

    function updateOptions(id, rawOptions) {
        const idx = formFields.findIndex(f => f.id === id);
        if (idx !== -1) {
            formFields[idx].options = rawOptions.split(',').map(s => s.trim());
        }
    }

    function clearCanvas() {
        formFields = [];
        renderCanvas();
    }

    function renderCanvas() {
        const canvas = document.getElementById('builder-canvas');
        canvas.innerHTML = '';
        checkBlankSlate();

        formFields.forEach(f => {
            let optionsControl = '';
            if (f.type === 'select') {
                optionsControl = `
                <div>
                    <label class="text-[10px] text-zinc-500 font-semibold uppercase">Comma-separated Options</label>
                    <input type="text" value="${f.options.join(', ')}" oninput="updateOptions('${f.id}', this.value)" class="w-full bg-zinc-950 border border-zinc-800 text-xs rounded p-1.5 text-white focus:outline-none">
                </div>
            `;
            }

            let elementHtml = `
            <div class="bg-zinc-950 border border-zinc-800/80 rounded-lg p-4 relative group/field">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Element: <span class="text-violet-400 font-mono">${f.type}</span></span>
                    <button onclick="removeField('${f.id}')" class="text-zinc-500 hover:text-red-400 text-xs transition-colors"><i class="bi bi-trash"></i></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="text-[10px] text-zinc-500 font-semibold uppercase">Input Label</label>
                        <input type="text" value="${f.label}" oninput="updateFieldLabel('${f.id}', this.value)" class="w-full bg-zinc-950 border border-zinc-800 text-xs rounded p-1.5 text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="text-[10px] text-zinc-500 font-semibold uppercase">Placeholder Text</label>
                        <input type="text" value="${f.placeholder}" oninput="updateFieldPlaceholder('${f.id}', this.value)" class="w-full bg-zinc-950 border border-zinc-800 text-xs rounded p-1.5 text-white focus:outline-none">
                    </div>
                </div>
                ${optionsControl}
                <div class="flex items-center gap-2 mt-3 pt-2.5 border-t border-zinc-900">
                    <input type="checkbox" id="req-${f.id}" ${f.required ? 'checked' : ''} onclick="toggleRequired('${f.id}')" class="rounded bg-zinc-950 border-zinc-800 text-violet-500 focus:ring-0">
                    <label for="req-${f.id}" class="text-[10px] text-zinc-400 font-semibold cursor-pointer">Required input field</label>
                </div>
            </div>
        `;
            canvas.insertAdjacentHTML('beforeend', elementHtml);
        });
    }

    function commitFormSchema() {
        const nameInput = document.getElementById('new-form-name').value.trim();
        if (!nameInput) {
            alert('Please specify a Form Label/Title.');
            return;
        }
        if (formFields.length === 0) {
            alert('Form must contain at least one element.');
            return;
        }

        document.getElementById('form-name-post').value = nameInput;
        document.getElementById('fields-json-post').value = JSON.stringify(formFields);
        document.getElementById('save-form-schema').submit();
    }

    // Modal View Mechanics
    function viewCustomPayload(data, title) {
        document.getElementById('inspectFormTitle').textContent = title;
        const grid = document.getElementById('inspectDataGrid');
        grid.innerHTML = '';

        for (const [key, value] of Object.entries(data)) {
            let fieldHtml = `
            <div class="bg-zinc-950 p-3.5 rounded-lg border border-zinc-800/80">
                <label class="text-zinc-500 font-bold uppercase tracking-wider text-[10px] block mb-1">${key}</label>
                <p class="text-zinc-100 text-sm whitespace-pre-line leading-relaxed">${value ? value : '<span class="text-zinc-600 italic">None</span>'}</p>
            </div>
        `;
            grid.insertAdjacentHTML('beforeend', fieldHtml);
        }
        document.getElementById('inspectorModal').classList.remove('hidden');
    }

    function closeInspector() {
        document.getElementById('inspectorModal').classList.add('hidden');
    }

    function launchIntegrationInstructions(key) {
        document.getElementById('embedFormKey').textContent = key;

        // Resolve clean absolute address references for cross-origin targets
        const currentOrigin = window.location.origin;
        const backendUrl = currentOrigin + '/api/form_handler.php';

        // Output templates
        const htmlSnippet = `
<!-- Dynamic Form Integration Markup -->
<form id="dynamic-form-${key}" class="custom-form-container">
    <input type="hidden" name="form_key" value="${key}">
    <div id="form-fields-container-${key}"></div>
    <button type="submit" class="submit-btn">Submit Information</button>
    <div id="response-msg-${key}" class="status-message"></div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const key = "${key}";
    const handlerUrl = "${backendUrl}";
    const container = document.getElementById("form-fields-container-" + key);

    // Dynamic Render Elements
    fetch(handlerUrl + "?action=get_fields&form_key=" + key)
        .then(res => res.json())
        .then(fields => {
            fields.forEach(f => {
                let html = '<div class="form-group"><label>' + f.label + (f.required ? ' *' : '') + '</label>';
                if (f.type === 'textarea') {
                    html += '<textarea name="' + f.name + '" placeholder="' + f.placeholder + '"' + (f.required ? ' required' : '') + '></textarea>';
                } else if (f.type === 'select') {
                    html += '<select name="' + f.name + '"' + (f.required ? ' required' : '') + '>';
                    f.options.forEach(opt => {
                        html += '<option value="' + opt + '">' + opt + '</option>';
                    });
                    html += '</select>';
                } else {
                    html += '<input type="' + f.type + '" name="' + f.name + '" placeholder="' + f.placeholder + '"' + (f.required ? ' required' : '') + '>';
                }
                html += '</div>';
                container.insertAdjacentHTML('beforeend', html);
            });
        });

    // AJAX Form Handling Logic
    document.getElementById("dynamic-form-" + key).addEventListener("submit", function(e) {
        e.preventDefault();
        const msgDiv = document.getElementById("response-msg-" + key);
        msgDiv.textContent = "Submitting details...";
        
        fetch(handlerUrl, {
            method: "POST",
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                msgDiv.innerHTML = '<span class="success-alert">Success: Your entry has been logged.</span>';
                this.reset();
            } else {
                msgDiv.innerHTML = '<span class="error-alert">Error: ' + data.error + '</span>';
            }
        });
    });
});
<\/script>
    `.trim();

        const phpSnippet = `
<?php
// Place inside your template action destination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "${backendUrl}");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['success'])) {
        echo "Successfully tracked lead submission!";
    } else {
        echo "Tracking failure: " . $response['error'];
    }
}
?>
<!-- Simple POST Template -->
<form action="" method="POST">
    <input type="hidden" name="form_key" value="${key}">
    <!-- Dynamic custom text/email/select fields corresponding with configured form layout -->
    <input type="text" name="example_field_key">
    <button type="submit">Submit Dynamic POST</button>
</form>
    `.trim();

        document.getElementById('snippet-html-code').textContent = htmlSnippet;
        document.getElementById('snippet-php-code').textContent = phpSnippet;

        document.getElementById('embedModal').classList.remove('hidden');
    }

    function switchEmbedTab(target) {
        document.getElementById('panel-embed-html').classList.toggle('hidden', target !== 'embed-html');
        document.getElementById('panel-embed-php').classList.toggle('hidden', target !== 'embed-php');

        const tabHtml = document.getElementById('tab-embed-html');
        const tabPhp = document.getElementById('tab-embed-php');

        if (target === 'embed-html') {
            tabHtml.className = tabHtml.className.replace('border-transparent text-zinc-400 hover:text-zinc-200', 'border-emerald-500 text-emerald-400');
            tabPhp.className = tabPhp.className.replace('border-emerald-500 text-emerald-400', 'border-transparent text-zinc-400 hover:text-zinc-200');
        } else {
            tabPhp.className = tabPhp.className.replace('border-transparent text-zinc-400 hover:text-zinc-200', 'border-emerald-500 text-emerald-400');
            tabHtml.className = tabHtml.className.replace('border-emerald-500 text-emerald-400', 'border-transparent text-zinc-400 hover:text-zinc-200');
        }
    }

    function closeEmbedModal() {
        document.getElementById('embedModal').classList.add('hidden');
    }
</script>
