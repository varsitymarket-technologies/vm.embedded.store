<?php
// 1. Setup Paths
$active_theme_file = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/theme";
$active_theme_name = file_exists($active_theme_file) ? trim(file_get_contents($active_theme_file)) : '';

$theme_base_path = dirname(dirname(dirname(__FILE__))) . '/themes/' . $active_theme_name;

if (file_exists(dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/builder.cache.html")) {
    $index_file = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/builder.cache.html";
} elseif (file_exists($theme_base_path . '/index.php')) {
    $index_file = $theme_base_path . '/index.php';
} else {
    $index_file = null;
}

// 2. Handle Save Request
if (isset($_POST['save_code'])) {
    $new_code = $_POST['code_content'];
    file_put_contents($index_file, $new_code);

    if ($github_connected && (isset($_POST['github_repo']) || isset($_POST['new_repo_name_text']))) {
        $target_repo = $_POST['github_repo'] ?? '';
        $repo_action = $_POST['repo_action'] ?? 'existing';

        try {
            if ($repo_action === 'new') {
                $target_repo = $_POST['new_repo_name_text'] ?? '';
                if (empty($target_repo)) throw new Exception("New repository name cannot be empty.");

                $new_repo_name = $github_session->slugify($target_repo);
                $env_data = [
                    'description' => 'Webstore deployed via Varsity Market',
                    'homepage' => $site_url,
                    'private' => false,
                ];
                $github_session->create_enviroment($new_repo_name, $env_data, "IGNORE");
                $target_repo = $new_repo_name;
            }

            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('github_repo', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$target_repo, $target_repo]);

            $owner = $github_session->get_user_login();

            if (!empty($owner) && !empty($target_repo)) {
                $github_session->github_configure_file(
                    $_SESSION['github_token'],
                    $owner,
                    $target_repo,
                    "index.html",
                    "Deploy from Varsity Market Admin - " . date('Y-m-d H:i:s'),
                    "varsitymarket-technologies",
                    "hastings@varsitymarket.tech",
                    $new_code
                );

                try {
                    $github_session->enable_domain($domain, $target_repo);
                    $parent_domain = $_SERVER['PARENT_DOMAIN'] ?? 'varsitymarket.co.za';
                    if (strpos($domain, $parent_domain) !== false && $domain !== $parent_domain) {
                        $github_session->configure_subdomain($domain);
                    }
                } catch (Exception $e) {}

                echo "<script>alert('Published to GitHub and domain configured!');</script>";
            } else {
                echo "<script>alert('Saved locally. GitHub owner could not be determined.');</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Code saved successfully!');</script>";
    }
}

// 3. GitHub Integration Setup
@include_once dirname(dirname(dirname(__FILE__))) . "/module/vm.github.php";
$github_connected = false;
$repositories = [];
$selected_repo = "";

if (isset($_SESSION['github_token'])) {
    try {
        $github_session = new varsitymarket_github_services($_SESSION['github_token']);
        $github_connected = true;
        $repositories = $github_session->list_enviroments() ?: [];

        $db_site = initiate_web_database();
        $repo_query = $db_site->query("SELECT value FROM settings WHERE key = 'github_repo'");
        if (!empty($repo_query)) {
            $selected_repo = $repo_query[0]['value'];
        }
    } catch (Exception $e) {
        $github_connected = false;
    }
}

// 4. Load existing code
$current_code = file_exists($index_file) ? file_get_contents($index_file) : "";

$site_url = "https://" . __DOMAIN__;
$preview_url = $site_url . "?preview=true&theme=" . $active_theme_name;
$domain = __WEBSITE_DOMAIN__;
$target = __DOMAIN__;

@include dirname(dirname(dirname(__FILE__))) . "/services/export.store.source.php";
if (empty($current_code)) {
    $current_code = (export_application($target, $domain));
}
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php @include_once "header.php"; ?>

            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#09090b] p-6">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Publish</h2>
                        <p class="text-zinc-400 text-sm mt-1">Review, edit and deploy your webstore</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="downloadCode()" class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                            <i class="bi bi-download"></i> Download
                        </button>
                        <form method="POST" class="m-0" id="publish_form">
                            <textarea name="code_content" id="hidden_code" class="hidden"></textarea>
                            <button type="submit" name="save_code" onclick="syncCode()" class="bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 shadow-lg shadow-violet-500/20">
                                <i class="bi bi-rocket-takeoff"></i> Publish
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                    <!-- Domain Card -->
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Live Domain</span>
                            <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <i class="bi bi-globe2 text-violet-400"></i>
                            </span>
                        </div>
                        <p class="text-white text-sm font-medium truncate"><?php echo $site_url; ?></p>
                        <a href="<?php echo $site_url; ?>" target="_blank" class="text-violet-400 hover:text-violet-300 text-xs mt-1 inline-flex items-center gap-1">
                            Visit <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                        </a>
                    </div>

                    <!-- GitHub Card -->
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">GitHub</span>
                            <span class="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center">
                                <i class="bi bi-github text-zinc-400"></i>
                            </span>
                        </div>
                        <?php if ($github_connected): ?>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Connected
                            </span>
                        </div>
                        <div class="mt-2">
                            <select id="repo_selector" name="github_repo" form="publish_form" onchange="handleRepoChange(this)"
                                class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-white text-xs focus:outline-none focus:border-violet-500 transition-colors">
                                <option value="" disabled <?php echo empty($selected_repo) ? 'selected' : ''; ?>>Select repository</option>
                                <?php foreach ($repositories as $repo): ?>
                                <option value="<?php echo htmlspecialchars($repo['name']); ?>" <?php echo $selected_repo == $repo['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($repo['name']); ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="__NEW__">+ New Repository</option>
                            </select>
                            <div id="new_repo_container" class="hidden mt-2 flex items-center gap-1">
                                <input type="text" id="new_repo_name" name="new_repo_name_text" form="publish_form" placeholder="Repository name..."
                                    class="flex-1 bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-white text-xs focus:outline-none focus:border-violet-500 transition-colors">
                                <button type="button" onclick="cancelNewRepo()" class="text-zinc-500 hover:text-white p-1"><i class="bi bi-x-circle"></i></button>
                            </div>
                            <input type="hidden" id="repo_action" name="repo_action" value="existing" form="publish_form">
                        </div>
                        <?php else: ?>
                        <p class="text-zinc-500 text-sm">Not connected</p>
                        <a href="?tab=deployment" class="text-violet-400 hover:text-violet-300 text-xs mt-1 inline-flex items-center gap-1">
                            <i class="bi bi-plug"></i> Connect GitHub
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Theme Card -->
                    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-zinc-400 text-xs font-medium">Active Theme</span>
                            <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                                <i class="bi bi-palette text-sky-400"></i>
                            </span>
                        </div>
                        <p class="text-white text-sm font-medium"><?php echo $active_theme_name ?: 'Default'; ?></p>
                        <a href="/vm-admin/<?php echo __DOMAIN__; ?>/theme" class="text-violet-400 hover:text-violet-300 text-xs mt-1 inline-flex items-center gap-1">
                            Change theme <i class="bi bi-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

                <!-- Editor + Preview -->
                <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden" style="height: calc(100vh - 340px); min-height: 400px;">
                    <!-- Editor Toolbar -->
                    <div class="flex items-center border-b border-zinc-800 bg-zinc-950/50">
                        <button onclick="toggleView('split')" id="btn-split" class="px-4 py-2.5 text-xs font-medium text-violet-400 border-b-2 border-violet-500 transition-colors">
                            <i class="bi bi-layout-split mr-1"></i>Split
                        </button>
                        <button onclick="toggleView('code')" id="btn-code" class="px-4 py-2.5 text-xs font-medium text-zinc-500 hover:text-zinc-300 border-b-2 border-transparent transition-colors">
                            <i class="bi bi-code-slash mr-1"></i>Code
                        </button>
                        <button onclick="toggleView('preview')" id="btn-preview" class="px-4 py-2.5 text-xs font-medium text-zinc-500 hover:text-zinc-300 border-b-2 border-transparent transition-colors">
                            <i class="bi bi-eye mr-1"></i>Preview
                        </button>
                        <div class="ml-auto pr-4">
                            <span id="save_status" class="text-zinc-600 text-xs">Ready</span>
                        </div>
                    </div>

                    <div class="flex h-full" id="editorContainer" style="height: calc(100% - 40px);">
                        <!-- Code Panel -->
                        <div id="editor_panel" class="w-1/2 flex flex-col border-r border-zinc-800">
                            <textarea id="editor"
                                class="flex-1 bg-zinc-950 text-emerald-400 p-5 font-mono text-sm outline-none resize-none leading-relaxed"
                                spellcheck="false"><?php echo htmlspecialchars($current_code); ?></textarea>
                        </div>

                        <!-- Preview Panel -->
                        <div id="preview_panel" class="w-1/2 flex flex-col bg-white">
                            <iframe id="preview" class="w-full h-full border-none"></iframe>
                        </div>
                    </div>
                </div>

            </main>
        </div>

<script>
const editor = document.getElementById('editor');
const preview = document.getElementById('preview');
const hiddenInput = document.getElementById('hidden_code');
const status = document.getElementById('save_status');

function updatePreview() {
    const content = editor.value;
    const doc = preview.contentDocument || preview.contentWindow.document;
    doc.open();
    doc.write(content);
    doc.close();
    status.innerText = "Unsaved changes";
    status.className = "text-amber-400 text-xs";
}

function syncCode() {
    hiddenInput.value = editor.value;
}

function downloadCode() {
    const text = editor.value;
    const blob = new Blob([text], { type: 'text/html' });
    const a = document.createElement('a');
    a.download = 'store.html';
    a.href = window.URL.createObjectURL(blob);
    a.click();
}

function toggleView(view) {
    const ep = document.getElementById('editor_panel');
    const pp = document.getElementById('preview_panel');
    const btnS = document.getElementById('btn-split');
    const btnC = document.getElementById('btn-code');
    const btnP = document.getElementById('btn-preview');

    [btnS, btnC, btnP].forEach(b => { b.className = b.className.replace('text-violet-400 border-violet-500', 'text-zinc-500 border-transparent'); });

    if (view === 'split') {
        ep.style.display = 'flex'; ep.style.width = '50%';
        pp.style.display = 'flex'; pp.style.width = '50%';
        btnS.className = btnS.className.replace('text-zinc-500 border-transparent', 'text-violet-400 border-violet-500');
    } else if (view === 'code') {
        ep.style.display = 'flex'; ep.style.width = '100%';
        pp.style.display = 'none';
        btnC.className = btnC.className.replace('text-zinc-500 border-transparent', 'text-violet-400 border-violet-500');
    } else {
        ep.style.display = 'none';
        pp.style.display = 'flex'; pp.style.width = '100%';
        btnP.className = btnP.className.replace('text-zinc-500 border-transparent', 'text-violet-400 border-violet-500');
    }
}

<?php if ($github_connected): ?>
function handleRepoChange(select) {
    const newRepoContainer = document.getElementById('new_repo_container');
    const repoAction = document.getElementById('repo_action');
    if (select.value === '__NEW__') {
        select.classList.add('hidden');
        newRepoContainer.classList.remove('hidden');
        document.getElementById('new_repo_name').focus();
        repoAction.value = 'new';
    } else {
        repoAction.value = 'existing';
    }
}
function cancelNewRepo() {
    const select = document.getElementById('repo_selector');
    const newRepoContainer = document.getElementById('new_repo_container');
    select.classList.remove('hidden');
    select.value = "";
    newRepoContainer.classList.add('hidden');
    document.getElementById('repo_action').value = 'existing';
}
<?php endif; ?>

editor.addEventListener('input', updatePreview);
window.onload = updatePreview;
</script>
