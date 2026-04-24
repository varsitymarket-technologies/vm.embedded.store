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
    $index_file = null; // No file to edit
}

// 2. Handle Save Request
if (isset($_POST['save_code'])) {
    $new_code = $_POST['code_content'];
    file_put_contents($index_file, $new_code);

    // GitHub Push Logic
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

            // Save selected repo to database
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('github_repo', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$target_repo, $target_repo]);

            // Get owner
            $owner = $github_session->get_user_login();

            if (!empty($owner) && !empty($target_repo)) {
                // Push the file
                // We'll use the editor content
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

                // Domain Automation Part
                try {
                    // Enable GitHub Pages
                    $github_session->enable_domain($domain, $target_repo);
                    
                    // Configure Cloudflare if it's a platform subdomain
                    $parent_domain = $_SERVER['PARENT_DOMAIN'] ?? 'varsitymarket.co.za';
                    if (strpos($domain, $parent_domain) !== false && $domain !== $parent_domain) {
                        $github_session->configure_subdomain($domain);
                    }
                } catch (Exception $e) {
                    // Log or ignore domain errors to not break the main push alert
                }

                echo "<script>alert('Code deployed locally, pushed to GitHub, and domain automation initiated!');</script>";
            } else {
                echo "<script>alert('Code deployed locally, but GitHub owner could not be determined.');</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Error pushing to GitHub: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Code deployed successfully!');</script>";
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
        // Fetch repositories
        $repositories = $github_session->list_enviroments() ?: [];
        
        // Load selected repo for this site from database if exists
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

// 4. Generate Site Links
$site_url = "https://" . __DOMAIN__;
$preview_url = $site_url . "?preview=true&theme=" . $active_theme_name;

$domain = __WEBSITE_DOMAIN__;
$target = __DOMAIN__;

@include dirname(dirname(dirname(__FILE__))) . "/services/export.store.source.php";
if (!empty($current_code)) {
    # pass; 
} else {
    $current_code = (export_application($target, $domain));
}
?>
<!-- Main Content -->
<div class="flex-1 canvas-area flex flex-col relative p-2 pt-2">
    <?php @include_once "header.php"; ?>

    <div class="flex flex-col h-screen bg-[#060606] text-gray-200">
        <div class="h-16 border-b border-white/10 bg-[#111] px-6 flex items-center justify-between py-4">
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="text-sm font-bold uppercase">Webstore Deployment</h1>
                </div>
            </div>

            <div class="flex items-center gap-3 items-justify-center">
                <?php if ($github_connected): ?>
                    <div class="flex items-center bg-black/40 border border-white/5 rounded-lg px-3 py-1.5 text-xs gap-2">
                        <i class="bi bi-github text-gray-400"></i>
                        <select id="repo_selector" name="github_repo" form="publish_form"
                            class="bg-transparent text-gray-300 outline-none border-none focus:ring-0 cursor-pointer max-w-[150px]"
                            onchange="handleRepoChange(this)">
                            <option value="" disabled <?php echo empty($selected_repo) ? 'selected' : ''; ?>>Select Repo</option>
                            <?php foreach ($repositories as $repo): ?>
                                <option value="<?php echo htmlspecialchars($repo['name']); ?>" 
                                    <?php echo $selected_repo == $repo['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($repo['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__NEW__" class="text-purple-400">+ Create New Repo</option>
                        </select>
                        <div id="new_repo_container" class="hidden flex items-center gap-1">
                            <input type="text" id="new_repo_name" name="new_repo_name_text" form="publish_form" placeholder="Repo Name..."
                                class="bg-transparent text-gray-300 outline-none border-none focus:ring-0 text-xs w-24">
                            <button type="button" onclick="cancelNewRepo()" class="text-gray-500 hover:text-white">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                        <input type="hidden" id="repo_action" name="repo_action" value="existing" form="publish_form">
                    </div>
                    <script>
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
                            const repoAction = document.getElementById('repo_action');
                            select.classList.remove('hidden');
                            select.value = "";
                            newRepoContainer.classList.add('hidden');
                            repoAction.value = 'existing';
                        }
                    </script>
                <?php else: ?>
                    <a href="?tab=deployment" class="text-[10px] text-gray-500 hover:text-purple-400 transition-colors">
                        <i class="bi bi-plug-fill"></i> Connect GitHub
                    </a>
                <?php endif; ?>

                <div
                    class="hidden lg:flex items-center bg-black/40 border border-white/5 rounded-lg px-3 py-1.5 text-xs">
                    <i class="bi bi-globe2 text-gray-500 mr-2"></i>
                    <span class="text-gray-400">
                        <?php echo $site_url; ?>
                    </span>
                    <a href="<?php echo $site_url; ?>" target="_blank"
                        class="ml-3 text-purple-400 hover:text-purple-300">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>

                <button onclick="downloadCode()"
                    class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="bi bi-download mr-2"></i> Download Code
                </button>

                <form method="POST" class="m-0" id="publish_form">
                    <textarea name="code_content" id="hidden_code" class="hidden"></textarea>
                    <button type="submit" name="save_code" onclick="syncCode()"
                        class="bg-purple-600 hover:bg-purple-500 text-white px-6 py-2 rounded-md text-sm font-bold transition-all shadow-lg shadow-purple-500/20">
                        Publish Webstore
                    </button>
                </form>
            </div>
        </div>
        <button onclick="code_editor()"
            class="lg:hidden absolute bottom-4 right-4 bg-gray-600 hover:bg-blue-500 text-white px-4 py-2 rounded-full uppercase shadow-lg shadow-blue-900/20 z-10">
            Edit Code
        </button>
        <script>
            function code_editor() {
                const editorPanel = document.getElementById('editor_panel');
                const previewPanel = document.getElementById('preview_panel');
                if (editorPanel.style.display === 'none' || !editorPanel.style.display) {
                    editorPanel.style.display = 'flex';
                    previewPanel.style.display = 'none';
                } else {
                    editorPanel.style.display = 'none';
                    previewPanel.style.display = 'flex';
                }
            }
        </script>


        <div class="flex flex-1 overflow-hidden">

            <div id="editor_panel" class="w-full lg:w-1/2 hidden lg:flex flex-col border-r border-white/10">
                <div class="bg-[#1a1a1a] px-4 py-2 text-[10px] font-bold text-gray-500 uppercase flex justify-between">
                    <span>Source Editor</span>
                    <span id="save_status">Saved</span>
                </div>
                <textarea id="editor"
                    class="flex-1 bg-[#0d0d0d] text-emerald-400 p-6 font-mono text-sm outline-none resize-none leading-relaxed"
                    spellcheck="false"><?php echo htmlspecialchars($current_code); ?></textarea>
            </div>

            <div id="preview_panel" class="w-full lg:w-1/2 flex flex-col bg-white">
                <div class="bg-[#1a1a1a] px-4 py-2 text-[10px] font-bold text-gray-500 uppercase">
                    <span>Live Preview (Responsive)</span>

                </div>
                <iframe id="preview" class="w-full h-full border-none"></iframe>
            </div>

        </div>
    </div>

</div>

<script>
    const editor = document.getElementById('editor');
    const preview = document.getElementById('preview');
    const hiddenInput = document.getElementById('hidden_code');
    const status = document.getElementById('save_status');

    // Update preview in real-time
    function updatePreview() {
        const content = editor.value;
        const doc = preview.contentDocument || preview.contentWindow.document;
        doc.open();
        doc.write(content);
        doc.close();
        status.innerText = "Unsaved Changes...";
        status.classList.add('text-yellow-500');
    }

    // Sync for form submission
    function syncCode() {
        hiddenInput.value = editor.value;
    }

    // Download code as HTML file
    function downloadCode() {
        const text = editor.value;
        const blob = new Blob([text], { type: 'text/html' });
        const a = document.createElement('a');
        a.download = 'store.html';
        a.href = window.URL.createObjectURL(blob);
        a.click();
    }

    // Listen for typing
    editor.addEventListener('input', updatePreview);

    // Initial Load
    window.onload = updatePreview;
</script>