<?php
// --- CONFIG & DATA FETCHING ---
$site_dir = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__;

// 1. Get Active Theme
$current_theme_path = $site_dir . "/theme";
$active_theme = file_exists($current_theme_path) ? trim(file_get_contents($current_theme_path)) : '';

// Check if a custom (local) HTML file is active
$builder_cache = $site_dir . "/builder.cache.html";
$has_custom_file = file_exists($builder_cache);
$custom_is_active = ($active_theme === '__custom__');

// 2. Handle Activation (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle custom HTML upload
    if (isset($_POST['action']) && $_POST['action'] === 'upload_custom') {
        if (isset($_POST['html_content']) && !empty($_POST['html_content'])) {
            file_put_contents($builder_cache, $_POST['html_content']);
            file_put_contents($current_theme_path, '__custom__');
            // Clear old generated files
            @unlink($site_dir . "/config.php");
            @unlink($site_dir . "/encode.php");
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    // Handle remove custom
    if (isset($_POST['action']) && $_POST['action'] === 'remove_custom') {
        @unlink($builder_cache);
        // Reset to first available theme or empty
        $path = dirname(dirname(dirname(__FILE__))) . '/themes/*';
        $dirs = glob($path, GLOB_ONLYDIR);
        if (!empty($dirs)) {
            $fallback = basename($dirs[0]);
            file_put_contents($current_theme_path, $fallback);
        } else {
            file_put_contents($current_theme_path, '');
        }
        @unlink($site_dir . "/config.php");
        @unlink($site_dir . "/encode.php");
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Handle theme activation
    if (isset($_POST['edthemes'])) {
        $name = $_POST['edthemes'];
        file_put_contents($current_theme_path, $name);
        @unlink($site_dir . "/config.php");
        @unlink($site_dir . "/encode.php");
        @unlink($builder_cache);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 3. Build Theme Library
$path = dirname(dirname(dirname(__FILE__))) . '/themes/*';
$directories = glob($path, GLOB_ONLYDIR);
natcasesort($directories);

$all_themes = [];
$categories = ['All'];

foreach ($directories as $key => $value) {
    $name = basename($value);
    $type = (strpos($name, 'pro_') !== false) ? 'Premium' : 'Standard';
    if (!in_array($type, $categories)) $categories[] = $type;

    $all_themes[] = [
        'id' => $key,
        'title' => $name,
        'image' => '/themes/' . $name . '/poster.png',
        'author' => 'vmTECH',
        'type' => $type,
        'version' => '1.0',
        'is_active' => ($name === $active_theme)
    ];
}

$total_themes = count($all_themes);
$active_count = count(array_filter($all_themes, fn($t) => $t['is_active']));
$premium_count = count(array_filter($all_themes, fn($t) => $t['type'] === 'Premium'));
$standard_count = count(array_filter($all_themes, fn($t) => $t['type'] === 'Standard'));
?>

<style>
    .theme-card { transition: all 0.3s ease; }
    .theme-card:hover { transform: translateY(-4px); }
    .theme-card:hover .theme-overlay { opacity: 1; }
    .theme-overlay { transition: opacity 0.3s ease; }
    .active-glow { box-shadow: 0 0 24px rgba(139, 92, 246, 0.2); }
    .stat-card { transition: all 0.2s ease; }
    .stat-card:hover { background: rgba(255,255,255,0.06); }

    /* Drop zone */
    .drop-zone { transition: all 0.3s ease; }
    .drop-zone.drag-over {
        border-color: #8b5cf6 !important;
        background: rgba(139, 92, 246, 0.08) !important;
    }
    .drop-zone.drag-over .drop-icon { transform: scale(1.15); color: #8b5cf6; }
    .drop-icon { transition: all 0.3s ease; }

    /* File info card */
    .file-card { animation: slideUp 0.3s ease; }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Filter pills */
    .filter-pill { transition: all 0.2s ease; }
    .filter-pill.active { background: #8b5cf6; color: #fff; }
    .filter-pill:not(.active):hover { background: rgba(255,255,255,0.08); }

    /* Search */
    .search-input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
</style>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#09090b] text-zinc-100 font-sans">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto">
        <!-- Page Header -->
        <div class="px-8 pt-8 pb-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Themes</h2>
                    <p class="text-sm text-zinc-500 mt-1">Choose a design template for your store</p>
                </div>
                <?php if ($active_theme && $active_theme !== '__custom__'): ?>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-zinc-500">Active:</span>
                        <span class="inline-flex items-center gap-1.5 bg-violet-500/10 text-violet-400 border border-violet-500/20 px-3 py-1 rounded-full text-xs font-semibold">
                            <i class="bi bi-check-circle-fill text-[10px]"></i>
                            <?php echo ucwords(str_replace(['_', '-', '.'], ' ', $active_theme)); ?>
                        </span>
                    </div>
                <?php elseif ($custom_is_active): ?>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-zinc-500">Active:</span>
                        <span class="inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 px-3 py-1 rounded-full text-xs font-semibold">
                            <i class="bi bi-file-earmark-code text-[10px]"></i>
                            Custom HTML File
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="px-8 pb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="stat-card bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Total Themes</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <i class="bi bi-palette text-violet-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?php echo $total_themes; ?></p>
                </div>
                <div class="stat-card bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Active</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <i class="bi bi-check-circle text-emerald-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?php echo $custom_is_active ? 1 : $active_count; ?></p>
                </div>
                <div class="stat-card bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Standard</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                            <i class="bi bi-grid text-blue-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?php echo $standard_count; ?></p>
                </div>
                <div class="stat-card bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500 font-medium">Premium</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <i class="bi bi-star text-amber-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold"><?php echo $premium_count; ?></p>
                </div>
            </div>
        </div>

        <!-- Custom Theme Upload Section -->
        <div class="px-8 pb-6">
            <div class="bg-zinc-900/40 border border-zinc-800/60 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-800/60 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <i class="bi bi-cloud-arrow-up text-amber-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold">Custom Theme</h3>
                            <p class="text-xs text-zinc-500">Import your own HTML store design</p>
                        </div>
                    </div>
                    <?php if ($has_custom_file): ?>
                        <div class="flex items-center gap-2">
                            <?php if (!$custom_is_active): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="upload_custom">
                                    <input type="hidden" name="html_content" value="__reactivate__">
                                    <button type="button" onclick="activateCustom()" class="text-xs bg-violet-600 hover:bg-violet-500 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                                        Activate
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-xs text-emerald-400 font-medium flex items-center gap-1">
                                    <i class="bi bi-check-circle-fill"></i> Active
                                </span>
                            <?php endif; ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Remove custom theme and switch to default?')">
                                <input type="hidden" name="action" value="remove_custom">
                                <button type="submit" class="text-xs text-zinc-500 hover:text-red-400 px-2 py-1.5 rounded-lg transition-colors">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-6">
                    <?php if ($has_custom_file && $custom_is_active): ?>
                        <!-- Active custom file indicator -->
                        <div class="file-card flex items-center gap-4 bg-zinc-800/40 border border-zinc-700/40 rounded-xl p-4 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-file-earmark-code text-amber-400 text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">Local HTML File</p>
                                <p class="text-xs text-zinc-500 mt-0.5">Custom imported design &middot; Currently active</p>
                            </div>
                            <a href="/vm-admin/<?php echo __DOMAIN__; ?>/builder" class="text-xs bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-1.5 rounded-lg transition-colors font-medium flex items-center gap-1.5">
                                <i class="bi bi-pencil-square"></i> Edit in Builder
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Drop Zone -->
                    <input type="file" id="fileInput" accept=".html,.htm" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none">
                    <div id="dropZone" class="drop-zone border-2 border-dashed border-zinc-700/60 rounded-xl p-8 text-center cursor-pointer hover:border-zinc-600">
                        <div class="drop-icon w-14 h-14 rounded-2xl bg-zinc-800/80 flex items-center justify-center mx-auto mb-4">
                            <i class="bi bi-file-earmark-arrow-up text-zinc-400 text-2xl"></i>
                        </div>
                        <p class="text-sm font-medium text-zinc-300 mb-1">Drop your HTML file here</p>
                        <p class="text-xs text-zinc-500">or click to browse &middot; Single .html file</p>
                    </div>

                    <!-- File preview (hidden by default) -->
                    <div id="filePreview" class="hidden mt-4">
                        <div class="file-card flex items-center gap-4 bg-zinc-800/40 border border-zinc-700/40 rounded-xl p-4">
                            <div class="w-12 h-12 rounded-xl bg-violet-500/10 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-file-earmark-code text-violet-400 text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p id="fileName" class="text-sm font-medium truncate"></p>
                                <p id="fileSize" class="text-xs text-zinc-500 mt-0.5"></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="clearFile()" class="text-xs text-zinc-500 hover:text-red-400 p-1.5 rounded-lg transition-colors">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <form id="uploadForm" method="POST" class="mt-3 flex justify-end">
                            <input type="hidden" name="action" value="upload_custom">
                            <input type="hidden" name="html_content" id="htmlContentInput" value="">
                            <button type="submit" class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                                <i class="bi bi-cloud-arrow-up"></i>
                                Import &amp; Activate
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="px-8 pb-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button class="filter-pill active px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer" onclick="filterThemes('All')" data-filter="All">
                        All <span class="ml-1 opacity-60"><?php echo $total_themes; ?></span>
                    </button>
                    <button class="filter-pill px-3 py-1.5 rounded-lg text-xs font-medium text-zinc-400 cursor-pointer" onclick="filterThemes('Standard')" data-filter="Standard">
                        Standard <span class="ml-1 opacity-60"><?php echo $standard_count; ?></span>
                    </button>
                    <?php if ($premium_count > 0): ?>
                    <button class="filter-pill px-3 py-1.5 rounded-lg text-xs font-medium text-zinc-400 cursor-pointer" onclick="filterThemes('Premium')" data-filter="Premium">
                        Premium <span class="ml-1 opacity-60"><?php echo $premium_count; ?></span>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Search themes..."
                        class="search-input bg-zinc-900/60 border border-zinc-800 rounded-lg pl-9 pr-4 py-2 text-sm text-white focus:outline-none w-64 placeholder-zinc-600">
                </div>
            </div>
        </div>

        <!-- Theme Grid -->
        <div class="px-8 pb-8">
            <div id="themeGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <?php foreach ($all_themes as $theme): ?>
                    <div class="theme-card bg-zinc-900/50 rounded-2xl overflow-hidden border <?php echo $theme['is_active'] ? 'border-violet-500/60 active-glow' : 'border-zinc-800/60 hover:border-zinc-700'; ?> flex flex-col"
                         data-name="<?php echo strtolower($theme['title']); ?>"
                         data-type="<?php echo $theme['type']; ?>">

                        <div class="relative aspect-[4/3] bg-zinc-950 overflow-hidden">
                            <img src="<?php echo $theme['image']; ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                class="w-full h-full object-cover"
                                alt="<?php echo $theme['title']; ?> preview"
                                loading="lazy">
                            <div class="absolute inset-0 items-center justify-center bg-zinc-900" style="display:none">
                                <div class="text-center">
                                    <i class="bi bi-palette text-zinc-700 text-4xl"></i>
                                    <p class="text-zinc-600 text-xs mt-2">No preview</p>
                                </div>
                            </div>

                            <!-- Hover overlay -->
                            <div class="theme-overlay absolute inset-0 bg-black/70 opacity-0 flex items-center justify-center gap-3 backdrop-blur-sm">
                                <form method="POST">
                                    <input type="hidden" name="edthemes" value="<?php echo $theme['title']; ?>">
                                    <button type="submit" class="bg-white text-black font-semibold px-5 py-2 rounded-lg hover:bg-zinc-100 transition-colors text-sm">
                                        <?php echo $theme['is_active'] ? 'Reapply' : 'Activate'; ?>
                                    </button>
                                </form>
                                <a href="/themes/<?php echo $theme['title']; ?>/interface" target="_blank"
                                   class="bg-zinc-800/80 text-white p-2.5 rounded-lg hover:bg-zinc-700 transition-colors" title="Preview">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>

                            <?php if ($theme['is_active']): ?>
                                <span class="absolute top-3 right-3 px-2.5 py-1 bg-violet-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-md shadow-lg">
                                    Active
                                </span>
                            <?php endif; ?>

                            <?php if ($theme['type'] === 'Premium'): ?>
                                <span class="absolute top-3 left-3 px-2 py-1 bg-amber-500/20 text-amber-300 text-[10px] font-bold uppercase tracking-wider rounded-md backdrop-blur-sm border border-amber-500/20">
                                    <i class="bi bi-star-fill text-[8px]"></i> Pro
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-sm truncate">
                                    <?php echo ucwords(str_replace(['_', '-', '.'], ' ', $theme['title'])); ?>
                                </h3>
                                <span class="text-[10px] text-zinc-600 font-mono">v<?php echo $theme['version']; ?></span>
                            </div>
                            <div class="flex items-center justify-between mt-1.5">
                                <span class="text-[11px] text-zinc-500"><?php echo $theme['author']; ?></span>
                                <span class="text-[10px] text-zinc-600 uppercase tracking-wider"><?php echo $theme['type']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty state -->
            <div id="emptyState" class="hidden text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-zinc-800/50 flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-search text-zinc-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-zinc-400">No themes found</h3>
                <p class="text-sm text-zinc-600 mt-1">Try adjusting your search or filter</p>
            </div>
        </div>
    </main>
</div>

<script>
// --- File Upload / Drop Zone ---
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const filePreview = document.getElementById('filePreview');
const htmlContentInput = document.getElementById('htmlContentInput');

// Click to open file dialog — only on real user clicks
dropZone.addEventListener('click', function(e) {
    if (!e.isTrusted) return;
    fileInput.click();
});

// Drag events
['dragenter', 'dragover'].forEach(evt => {
    dropZone.addEventListener(evt, e => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('drag-over');
    });
});

['dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, e => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');
    });
});

dropZone.addEventListener('drop', e => {
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
});

fileInput.addEventListener('change', e => {
    if (e.target.files[0]) handleFile(e.target.files[0]);
});

function handleFile(file) {
    if (!file.name.match(/\.(html|htm)$/i)) {
        alert('Please select an HTML file.');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const content = e.target.result;
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatSize(file.size) + ' \u00b7 HTML Document';
        htmlContentInput.value = content;
        filePreview.classList.remove('hidden');
        dropZone.style.display = 'none';
    };
    reader.readAsText(file);
}

function clearFile() {
    filePreview.classList.add('hidden');
    dropZone.style.display = '';
    htmlContentInput.value = '';
    fileInput.value = '';
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function activateCustom() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="edthemes" value="__custom__">';
    document.body.appendChild(form);
    form.submit();
}

// --- Search & Filter ---
const searchInput = document.getElementById('searchInput');
const themeGrid = document.getElementById('themeGrid');
const emptyState = document.getElementById('emptyState');
let activeFilter = 'All';

searchInput.addEventListener('input', applyFilters);

function filterThemes(type) {
    activeFilter = type;
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.classList.toggle('active', pill.dataset.filter === type);
        if (pill.dataset.filter !== type) {
            pill.classList.add('text-zinc-400');
        } else {
            pill.classList.remove('text-zinc-400');
        }
    });
    applyFilters();
}

function applyFilters() {
    const query = searchInput.value.toLowerCase().trim();
    const cards = themeGrid.querySelectorAll('.theme-card');
    let visible = 0;

    cards.forEach(card => {
        const name = card.dataset.name;
        const type = card.dataset.type;
        const matchesSearch = !query || name.includes(query);
        const matchesFilter = activeFilter === 'All' || type === activeFilter;
        const show = matchesSearch && matchesFilter;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    emptyState.classList.toggle('hidden', visible > 0);
}
</script>
