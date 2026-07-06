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
                } catch (Exception $e) {
                }

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
                <h2 class="text-2xl font-bold text-white">Export </h2>
                <p class="text-zinc-400 text-sm mt-1">Download your webstore code</p>
            </div>
            <div class="flex items-center gap-2">
            </div>
        </div>

        <!-- Editor + Preview -->
        <div style="display:none;" class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden" style="height: calc(100vh - 340px); min-height: 400px;">
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
                <div style="display:none;" id="editor_panel" class="w-1/2 flex flex-col border-r border-zinc-800">
                    <textarea id="editor"
                        class="flex-1 bg-zinc-950 text-emerald-400 p-5 font-mono text-sm outline-none resize-none leading-relaxed"
                        spellcheck="false"><?php echo htmlspecialchars($current_code); ?></textarea>
                </div>

                <!-- Preview Panel -->
                <div id="preview_panel" class="w-full flex flex-col bg-white">
                    <iframe id="preview" class="w-full h-full border-none"></iframe>
                </div>
            </div>
        </div>

        <!-- Embed Code -->
        <div style="max-height: 60vh; height:100%; " class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-300">Embed Code</h3>
                <button onclick="copyEmbedCode()" class="text-xs bg-accent hover:bg-accent-hover bg-[#333] hover:bg-zinc-700 text-black font-semibold px-3 py-1.5 rounded-lg transition-all duration-150 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="9" y="9" width="13" height="13" rx="2" stroke-width="2"></rect>
                        <path stroke-linecap="round" stroke-width="2" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path>
                    </svg> Copy Code
                </button>
            </div>
            <pre style="height: calc(100% - 2rem);" class="code-block p-4 text-xs overflow-y-auto" id="embedCodeBlock">&lt;!-- Embedded Webstore --&gt; <?php @include_once dirname(dirname(__DIR__))."/services/export.store.frame.php"; echo (embedd_application(__DOMAIN__,"https://".get_domain())); ?></pre>
        </div>


        <!-- DOWNLOAD STORE WEBSITE (THE ACTUAL STORE CODE) -->
        <div class="bg-zinc-900 border border-zinc-800 my-4 rounded-xl p-5 space-y-4 relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-accent text-black text-xs font-bold px-3 py-1 rounded-bl-lg">MAIN FEATURE</div>
            <h3 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v8m0 0l-3-3m3 3l3-3M4 4h16v4H4z"></path>
                </svg>
                Download Store Website (HTML)
            </h3>
            <p class="text-xs text-gray-400">Get a complete, standalone HTML file of your store with all active products. Dark theme, ready to host or share.</p>
        

            <button onclick="downloadCode()" class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                <i class="bi bi-download"></i> Download
            </button>
        </div>


        <!-- Export Product Data
        <div class="bg-surface-card border border-surface-border rounded-xl p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-300">Export Product Data</h3>
            <div class="flex flex-wrap gap-3">
                <button onclick="exportCSV()" class="flex items-center gap-2 bg-surface-elevated hover:bg-surface-border text-gray-200 px-4 py-2.5 rounded-lg text-sm border border-surface-border transition-all duration-150">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v8m0 0l-3-3m3 3l3-3M4 4h16v4H4z"></path>
                    </svg> Export as CSV
                </button>
                <button onclick="exportJSON()" class="flex items-center gap-2 bg-surface-elevated hover:bg-surface-border text-gray-200 px-4 py-2.5 rounded-lg text-sm border border-surface-border transition-all duration-150">
                    <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v8m0 0l-3-3m3 3l3-3M4 4h16v4H4z"></path>
                    </svg> Export as JSON
                </button>
                <button onclick="downloadDashboardHTML()" class="flex items-center gap-2 bg-surface-elevated hover:bg-surface-border text-gray-200 px-4 py-2.5 rounded-lg text-sm border border-surface-border transition-all duration-150" title="Download this admin dashboard for backup">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v8m0 0l-3-3m3 3l3-3M4 4h16v4H4z"></path>
                    </svg> Download Dashboard HTML
                </button>
            </div>
        </div>
         -->


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
        const blob = new Blob([text], {
            type: 'text/html'
        });
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

        [btnS, btnC, btnP].forEach(b => {
            b.className = b.className.replace('text-violet-400 border-violet-500', 'text-zinc-500 border-transparent');
        });

        if (view === 'split') {
            ep.style.display = 'flex';
            ep.style.width = '50%';
            pp.style.display = 'flex';
            pp.style.width = '50%';
            btnS.className = btnS.className.replace('text-zinc-500 border-transparent', 'text-violet-400 border-violet-500');
        } else if (view === 'code') {
            ep.style.display = 'flex';
            ep.style.width = '100%';
            pp.style.display = 'none';
            btnC.className = btnC.className.replace('text-zinc-500 border-transparent', 'text-violet-400 border-violet-500');
        } else {
            ep.style.display = 'none';
            pp.style.display = 'flex';
            pp.style.width = '100%';
            btnP.className = btnP.className.replace('text-zinc-500 border-transparent', 'text-violet-400 border-violet-500');
        }
    }

    editor.addEventListener('input', updatePreview);
    window.onload = updatePreview;
</script>