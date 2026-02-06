<?php
$db = initiate_web_database(); 

// Helper to get setting
function get_setting($db, $key, $default = '') {
    $result = $db->query("SELECT value FROM settings WHERE key = ?", [$key]);
    return $result && isset($result[0]['value']) ? $result[0]['value'] : $default;
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $value) {
        $value = trim($value);
        
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
$setting_keys = [
    'store_email' => 'contact@varsitymarket.com',
    'currency_symbol' => '$',
    'items_per_page' => '12',
    'facebook_url' => '',
    'twitter_url' => '',
    'instagram_url' => '',
];

$current_settings = [];
foreach ($setting_keys as $key => $def) {
    $current_settings[$key] = get_setting($db, $key, $def);
}
?>

<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden">
    <!-- Header -->
    <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10">
        <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
            <i class="bi bi-list text-2xl"></i>
        </button>
        <h2 class="text-lg font-semibold text-white">General Settings</h2>
        <div class="flex items-center gap-3">
             <button onclick="document.getElementById('settingsForm').submit()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors text-sm font-medium">
                <i class="bi bi-save"></i> Save Settings
            </button>
        </div>
    </header>

    <!-- Main Scrollable Area -->
    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
        <div class="max-w-4xl mx-auto">
            <form id="settingsForm" method="POST">
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="p-6 space-y-6">
                        
                        <!-- Store Information -->
                        <div>
                            <h3 class="text-lg font-medium text-white mb-4">Store Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="store_email" class="block text-sm font-medium text-gray-400 mb-1">Contact Email</label>
                                    <input type="email" name="settings[store_email]" id="store_email" value="<?php echo htmlspecialchars($current_settings['store_email']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                            </div>
                        </div>

                        <hr class="border-white/10">

                        <!-- Localization -->
                        <div>
                            <h3 class="text-lg font-medium text-white mb-4">Localization & Display</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="currency_symbol" class="block text-sm font-medium text-gray-400 mb-1">Currency Symbol</label>
                                    <input type="text" name="settings[currency_symbol]" id="currency_symbol" value="<?php echo htmlspecialchars($current_settings['currency_symbol']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                <div>
                                    <label for="items_per_page" class="block text-sm font-medium text-gray-400 mb-1">Products Per Page</label>
                                    <input type="number" name="settings[items_per_page]" id="items_per_page" value="<?php echo htmlspecialchars($current_settings['items_per_page']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                            </div>
                        </div>

                        <hr class="border-white/10">

                        <!-- Social Media Links -->
                        <div>
                            <h3 class="text-lg font-medium text-white mb-4">Social Media</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="facebook_url" class="block text-sm font-medium text-gray-400 mb-1">Facebook URL</label>
                                    <input type="url" name="settings[facebook_url]" id="facebook_url" value="<?php echo htmlspecialchars($current_settings['facebook_url']); ?>" placeholder="https://facebook.com/yourpage" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                <div>
                                    <label for="twitter_url" class="block text-sm font-medium text-gray-400 mb-1">Twitter (X) URL</label>
                                    <input type="url" name="settings[twitter_url]" id="twitter_url" value="<?php echo htmlspecialchars($current_settings['twitter_url']); ?>" placeholder="https://twitter.com/yourhandle" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                <div>
                                    <label for="instagram_url" class="block text-sm font-medium text-gray-400 mb-1">Instagram URL</label>
                                    <input type="url" name="settings[instagram_url]" id="instagram_url" value="<?php echo htmlspecialchars($current_settings['instagram_url']); ?>" placeholder="https://instagram.com/yourprofile" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="bg-gray-700/30 px-6 py-4 text-right border-t border-white/5">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg flex items-center gap-2 transition-colors text-sm font-medium inline-flex">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>