<?php
$db = __DB_MODULE__;

// Helper to get setting
function get_setting($db, $key, $default = '') {
    $result = $db->query("SELECT value FROM settings WHERE key = ?", [$key]);
    return $result ? $result[0]['value'] : $default;
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $value) {
        $exists = $db->query("SELECT id FROM settings WHERE key = ?", [$key]);
        if ($exists) {
            $db->query("UPDATE settings SET value = ? WHERE key = ?", [$value, $key]);
        } else {
            $db->query("INSERT INTO settings (key, value) VALUES (?, ?)", [$key, $value]);
        }
    }
    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}

// Load settings with defaults
$defaults = [
    'site_title' => 'Varsity Market',
    'primary_color' => '#7a1aab',
    'secondary_color' => '#1a1a1a',
    'font_family' => 'Inter',
    'show_hero' => '1'
];

$current_settings = [];
foreach ($defaults as $key => $def) {
    $current_settings[$key] = get_setting($db, $key, $def);
}
?>

<div class="flex flex-1 flex-col overflow-hidden h-full">
    <!-- Header -->
    <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10 shrink-0">
        <div class="flex items-center gap-4">
            <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
                <i class="bi bi-list text-2xl"></i>
            </button>
            <h2 class="text-lg font-semibold text-white">Visual Builder</h2>
        </div>
        <div class="flex items-center gap-3">
             <button onclick="document.getElementById('builderForm').submit()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors text-sm font-medium">
                <i class="bi bi-save"></i> Save Changes
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Controls Sidebar -->
        <aside class="w-80 bg-gray-900 border-r border-white/10 overflow-y-auto custom-scrollbar z-10">
            <form id="builderForm" method="POST" class="p-4 space-y-6">
                
                <!-- Branding Section -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Branding</h3>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Site Title</label>
                        <input type="text" name="settings[site_title]" value="<?php echo htmlspecialchars($current_settings['site_title']); ?>" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Logo URL</label>
                        <input type="text" name="settings[logo_url]" value="<?php echo htmlspecialchars(get_setting($db, 'logo_url')); ?>" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none" placeholder="https://...">
                    </div>
                </div>

                <hr class="border-white/10">

                <!-- Colors Section -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Colors</h3>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Primary Color</label>
                        <div class="flex gap-2">
                            <input type="color" name="settings[primary_color]" value="<?php echo htmlspecialchars($current_settings['primary_color']); ?>" class="h-9 w-9 rounded cursor-pointer bg-transparent border-0 p-0">
                            <input type="text" value="<?php echo htmlspecialchars($current_settings['primary_color']); ?>" class="flex-1 bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none uppercase" onchange="this.previousElementSibling.value = this.value">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Secondary Color</label>
                        <div class="flex gap-2">
                            <input type="color" name="settings[secondary_color]" value="<?php echo htmlspecialchars($current_settings['secondary_color']); ?>" class="h-9 w-9 rounded cursor-pointer bg-transparent border-0 p-0">
                            <input type="text" value="<?php echo htmlspecialchars($current_settings['secondary_color']); ?>" class="flex-1 bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none uppercase" onchange="this.previousElementSibling.value = this.value">
                        </div>
                    </div>
                </div>

                <hr class="border-white/10">

                <!-- Typography -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Typography</h3>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Font Family</label>
                        <select name="settings[font_family]" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                            <option value="Inter" <?php echo $current_settings['font_family'] === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                            <option value="Roboto" <?php echo $current_settings['font_family'] === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                            <option value="Open Sans" <?php echo $current_settings['font_family'] === 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                        </select>
                    </div>
                </div>

                <hr class="border-white/10">

                <!-- Layout -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Layout</h3>
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-400">Show Hero Section</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[show_hero]" value="0">
                            <input type="checkbox" name="settings[show_hero]" value="1" class="sr-only peer" <?php echo $current_settings['show_hero'] ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                </div>

            </form>
        </aside>

        <!-- Preview Area -->
        <main class="flex-1 bg-gray-800 relative">
            <div class="absolute inset-0 p-4 md:p-8 bg-gray-900/50">
                <div class="bg-white h-full w-full rounded-lg shadow-2xl overflow-hidden border border-gray-700">
                    <iframe src="<?php echo __WEBSITE_URL__; ?>" class="w-full h-full border-0 bg-white"></iframe>
                </div>
            </div>
        </main>
    </div>
</div>