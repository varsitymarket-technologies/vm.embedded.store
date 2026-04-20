<?php
// --- CONFIG & DATA FETCHING ---
$items_per_page = 8;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$search_query = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$selected_group = isset($_GET['group']) ? $_GET['group'] : 'All';

// 1. Get Active Theme
$current_theme_path = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/theme";
$active_theme = file_exists($current_theme_path) ? file_get_contents($current_theme_path) : '';

// 2. Handle Activation (POST)
if (isset($_POST['edthemes'])) {
    $name = $_POST['edthemes'] ?? null;
    $saved_path = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/theme";
    file_put_contents($saved_path, $name);

    unlink(dirname($saved_path) . "/config.php");
    unlink(dirname($saved_path) . "/encode.php");
    unlink(dirname($saved_path) . "/builder.cache.html");

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 3. Build Raw Library
$path = dirname(dirname(dirname(__FILE__))) . '/themes/*';
$directories = glob($path, GLOB_ONLYDIR);
natcasesort($directories); // Order is by Folder First (Alphabetical)

$all_themes = [];
$categories = ['All'];

foreach ($directories as $key => $value) {
    $name = str_ireplace(dirname(dirname(dirname(__FILE__))) . '/themes/', '', $value);

    // Logic: If theme folder starts with "shop_", category is "E-commerce", etc.
    // For now, we'll use your static assignment or folder prefixing
    $type = (strpos($name, 'pro_') !== false) ? 'Premium' : 'Standard';
    if (!in_array($type, $categories))
        $categories[] = $type;

    $theme = [
        'id' => $key,
        'title' => $name,
        'image' => '/themes/' . $name . '/poster.png',
        'color' => 'from-zinc-500 to-zinc-800',
        'author' => 'vmTECH',
        'type' => $type,
        'version' => '1.0',
        'is_active' => ($name === $active_theme)
    ];

    // Filter: Search & Group
    $matches_search = empty($search_query) || strpos(strtolower($name), $search_query) !== false;
    $matches_group = ($selected_group === 'All') || ($theme['type'] === $selected_group);

    if ($matches_search && $matches_group) {
        $all_themes[] = $theme;
    }
}

// 4. Pagination Calculation
$total_themes = count($all_themes);
$total_pages = ceil($total_themes / $items_per_page);
$offset = ($current_page - 1) * $items_per_page;
$themes = array_slice($all_themes, $offset, $items_per_page);
?>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#09090b] text-zinc-100 font-sans">
    <?php @include_once "header.php"; ?>

    <div class="px-8 py-6 border-b border-zinc-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold tracking-tight">Template Library</h2>
            <p class="text-xs text-zinc-500 uppercase tracking-widest mt-1">Showing <?php echo count($all_themes); ?>
                Styles</p>
        </div>

        <div class="flex items-center gap-3">
            <form action="" method="GET" class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>"
                    placeholder="Search themes..."
                    class="bg-zinc-900 border border-zinc-800 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-purple-500 w-64">
            </form>

            <select onchange="location.href='?group=' + this.value"
                class="bg-zinc-900 border border-zinc-800 rounded-lg px-4 py-2 text-sm focus:outline-none">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo $selected_group == $cat ? 'selected' : ''; ?>>
                        <?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-8 overflow-y-auto flex-1">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($themes as $theme): ?>
                <div
                    class="group bg-zinc-900/50 rounded-2xl overflow-hidden border <?php echo $theme['is_active'] ? 'border-purple-500 shadow-[0_0_20px_rgba(168,85,247,0.15)]' : 'border-zinc-800'; ?> hover:border-zinc-700 transition-all duration-300 flex flex-col">

                    <div class="relative aspect-[4/3] bg-zinc-950 overflow-hidden">
                        <img src="<?php echo $theme['image']; ?>"
                            onerror="this.src='https://placehold.co/600x400/18181b/52525b?text=No+Preview'"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            alt="Preview">

                        <div
                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center gap-2 backdrop-blur-sm">
                            <form method="POST">
                                <input type="hidden" name="edthemes" value="<?php echo $theme['title']; ?>">
                                <button type="submit"
                                    class="bg-white text-black font-bold px-5 py-2 rounded-lg hover:scale-105 transition-transform">
                                    Activate
                                </button>
                            </form>
                            <button class="bg-zinc-800 text-white p-2 rounded-lg hover:bg-zinc-700">
                                <i class="bi bi-fullscreen"></i>
                            </button>
                        </div>

                        <?php if ($theme['is_active']): ?>
                            <span
                                class="absolute top-3 right-3 px-2 py-1 bg-purple-500 text-white text-[9px] font-black uppercase tracking-[0.2em] rounded-md">Active</span>
                        <?php endif; ?>
                    </div>

                    <div class="p-5">
                        <div class="flex justify-between items-center mb-1">
                            <h3 class="font-bold text-sm truncate">
                                <?php echo ucwords(str_replace(['_', '-'], ' ', $theme['title'])); ?></h3>
                            <span class="text-[10px] text-zinc-500 font-mono">v<?php echo $theme['version']; ?></span>
                        </div>
                        <p class="text-[10px] text-zinc-500 uppercase tracking-widest"><?php echo $theme['type']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center items-center gap-2 pb-10">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&q=<?php echo $search_query; ?>&group=<?php echo $selected_group; ?>"
                        class="w-10 h-10 flex items-center justify-center rounded-lg border <?php echo $i == $current_page ? 'bg-white text-black border-white' : 'border-zinc-800 text-zinc-500 hover:border-zinc-600'; ?> transition-all font-bold text-sm">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>