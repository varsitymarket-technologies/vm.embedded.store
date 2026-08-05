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
$repo_state = $github_connected ? 'connected' : 'disconnected';

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
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Publish your storefront source to GitHub.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white sm:text-base">
                        Review the current HTML, choose or create a repository, and push a clean deploy from one polished workspace.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button onclick="downloadCode()" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                            <i class="bi bi-download"></i>
                            <span>Download HTML</span>
                        </button>
                        <form method="POST" class="m-0" id="publish_form">
                            <textarea name="code_content" id="hidden_code" class="hidden"></textarea>
                            <button type="submit" name="save_code" onclick="syncCode()" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                                <i class="bi bi-rocket-takeoff"></i>
                                <span>Deploy to GitHub</span>
                            </button>
                        </form>
                    </div>

                </div>

            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,0.8fr)]">
            <div class="rounded-3xl border border-white/10 bg-[#202123] shadow-[0_18px_45px_rgba(0,0,0,0.24)] overflow-hidden">
                <div class="flex items-center justify-between border-b border-white/10 bg-white/5 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-white">Source preview</h2>
                        <p class="text-xs text-zinc-500">Review the code that will be deployed to the GitHub repository.</p>
                    </div>
                    <span id="save_status" class="text-xs text-zinc-400">Ready</span>
                </div>
                <div class="flex h-[72vh] min-h-[500px]" id="editorContainer">
                    <div id="editor_panel" style="display:none;" class="w-1/2 flex flex-col border-r border-white/10 bg-[#1b1b1c]">
                        <textarea id="editor" class="flex-1 bg-[#1b1b1c] text-emerald-300 p-5 font-mono text-sm outline-none resize-none leading-relaxed" spellcheck="false"><?php echo htmlspecialchars($current_code); ?></textarea>
                    </div>
                    <div id="preview_panel" class="w-full flex flex-col bg-white">
                        <iframe id="preview" class="w-full h-full border-none"></iframe>
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <section class="rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Repository</p>
                    <?php if ($github_connected): ?>
                        <div class="mt-4 space-y-3">
                            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                Connected
                            </div>
                            <label class="block">
                                <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Select repository</span>
                                <select id="repo_selector" name="github_repo" form="publish_form" onchange="handleRepoChange(this)" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none transition focus:border-[#008060]">
                                    <option value="" disabled <?php echo empty($selected_repo) ? 'selected' : ''; ?>>Select repository</option>
                                    <?php foreach ($repositories as $repo): ?>
                                        <option value="<?php echo htmlspecialchars($repo['name']); ?>" <?php echo $selected_repo == $repo['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($repo['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NEW__">+ New repository</option>
                                </select>
                            </label>
                            <div id="new_repo_container" class="hidden flex items-center gap-2 rounded-2xl border border-white/10 bg-[#1b1b1c] p-2">
                                <input type="text" id="new_repo_name" name="new_repo_name_text" form="publish_form" placeholder="Repository name..." class="flex-1 bg-transparent px-2 py-1.5 text-sm text-white outline-none">
                                <button type="button" onclick="cancelNewRepo()" class="rounded-full p-2 text-zinc-500 transition hover:bg-white/5 hover:text-white">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                            <input type="hidden" id="repo_action" name="repo_action" value="existing" form="publish_form">
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm text-amber-50">
                            <p class="font-semibold text-white">GitHub not connected</p>
                            <p class="mt-1 text-xs text-amber-100/80">Connect GitHub in deployment settings before publishing here.</p>
                            <a href="?tab=deployment" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-white underline decoration-white/30 underline-offset-4 hover:decoration-white">
                                Open deployment settings
                            </a>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Workspace notes</p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                            <i class="bi bi-1-circle text-lg text-sky-300"></i>
                            <div>
                                <p class="text-sm font-semibold text-white">Edit before deploy</p>
                                <p class="mt-1 text-xs text-zinc-400">The source preview reflects the HTML that will be committed to GitHub.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                            <i class="bi bi-2-circle text-lg text-emerald-300"></i>
                            <div>
                                <p class="text-sm font-semibold text-white">Save repository choice</p>
                                <p class="mt-1 text-xs text-zinc-400">The selected repository is stored automatically when you publish.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                            <i class="bi bi-3-circle text-lg text-amber-300"></i>
                            <div>
                                <p class="text-sm font-semibold text-white">Generate a new repo</p>
                                <p class="mt-1 text-xs text-zinc-400">Choose New repository to create a fresh GitHub target for this store.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </section>

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