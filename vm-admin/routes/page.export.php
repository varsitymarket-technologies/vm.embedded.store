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
                if (empty($target_repo))
                    throw new Exception("New repository name cannot be empty.");

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
$github_state = $github_connected ? 'connected' : 'disconnected';

@include dirname(dirname(dirname(__FILE__))) . "/services/export.store.source.php";
if (empty($current_code)) {
    $current_code = (export_application($target, $domain));
}
?>
<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden bg-[#1b1b1c] text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden p-6 lg:p-8 space-y-6">
        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#242424] text-white">
            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)] lg:p-8">
                <div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Export your storefront as code.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white sm:text-base">
                        Package the current store source for GitHub, embed it on another site, or download a ready-to-host HTML file.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button onclick="downloadCode()" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                            <i class="bi bi-download"></i>
                            <span>Download HTML</span>
                        </button>
                        <button onclick="copyEmbedCode()" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                            <i class="bi bi-clipboard"></i>
                            <span>Copy embed code</span>
                        </button>
                    </div>
                </div>

            </div>
        </section>

        <section style="display:block" class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,0.8fr)]">
            <div class="space-y-6">
                <div class="rounded-3xl border border-white/10 bg-[#202123] shadow-[0_18px_45px_rgba(0,0,0,0.24)] overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/10 bg-white/5 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-white">Source preview</h2>
                            <p class="text-xs text-zinc-500">Review the HTML that will be exported before you download it.</p>
                        </div>
                        <span id="save_status" class="text-xs text-zinc-400">Ready</span>
                    </div>
                    <div class="flex h-[66vh] min-h-[480px]" id="editorContainer" style="height: calc(100% - 40px);">
                        <div style="display:none;" id="editor_panel" class="w-1/2 flex flex-col border-r border-white/10 bg-[#1b1b1c]">
                            <textarea id="editor" class="flex-1 bg-[#1b1b1c] text-emerald-300 p-5 font-mono text-sm outline-none resize-none leading-relaxed" spellcheck="false"><?php echo htmlspecialchars($current_code); ?></textarea>
                        </div>
                        <div id="preview_panel" class="w-full flex flex-col bg-white">
                            <iframe id="preview" class="w-full h-full border-none"></iframe>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)] space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-white">Embed link code</h3>
                            <p class="text-xs text-zinc-500">Paste this to link back to the hosted store.</p>
                        </div>
                        <button onclick="copyEmbedCode()" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/10">
                            <i class="bi bi-clipboard"></i>
                            Copy
                        </button>
                    </div>
                    <pre class="max-h-32 overflow-y-auto rounded-2xl border border-white/10 bg-[#1b1b1c] p-4 text-xs text-zinc-200" id="embedCodeBlock"><?php @include_once dirname(dirname(__DIR__)) . "/services/export.store.link.php";
                                                                                                                                                        echo (embedd_link_application(__DOMAIN__, "https://" . get_domain())); ?></pre>
                </div>

                <div class="rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)] space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-white">Embed code</h3>
                            <p class="text-xs text-zinc-500">Use this when embedding the full storefront experience.</p>
                        </div>
                        <button onclick="copyEmbedCode()" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/10">
                            <i class="bi bi-clipboard"></i>
                            Copy
                        </button>
                    </div>
                    <pre class="max-h-[40vh] overflow-y-auto rounded-2xl border border-white/10 bg-[#1b1b1c] p-4 text-xs text-zinc-200" id="embedCodeBlock">&lt;!-- Embedded Webstore --&gt; <?php @include_once dirname(dirname(__DIR__)) . "/services/export.store.frame.php";
                                                                                                                                                                                                echo (embedd_application(__DOMAIN__, "https://" . get_domain())); ?></pre>
                </div>
            </div>
        </section>


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
        status.innerText = "Export Ready";
        status.className = "text-green-400 text-xs";
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