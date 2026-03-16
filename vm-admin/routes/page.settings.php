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
    <?php @include_once "header.php"; ?>

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

                        <div>
                            <h3 class="text-lg font-medium text-white mb-4">Email Template</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="discord_webhook" class="block text-sm font-medium text-gray-400 mb-1">HTML Template</label>
                                    <textarea class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"><?php echo htmlspecialchars($current_settings['discord_webhook']); ?></textarea>
                                </div>
                                <br>
                                <div>
                                    <label for="discord_webhook" class="block text-sm font-medium text-gray-400 mb-1">Email Password</label>
                                    <input type="url" name="settings[discord_webhook]" id="discord_webhook" value="<?php echo htmlspecialchars($current_settings['discord_webhook']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                            </div>

                        <hr class="border-white/10">

                        <div>
                            <h3 class="text-lg font-medium text-white mb-4">Store Notifications</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="discord_webhook" class="block text-sm font-medium text-gray-400 mb-1">Discord Webhook</label>
                                    <input type="url" name="settings[discord_webhook]" id="discord_webhook" value="<?php echo htmlspecialchars($current_settings['discord_webhook']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                <br>
                                <div>
                                    <label for="discord_webhook" class="block text-sm font-medium text-gray-400 mb-1">Email Address</label>
                                    <input type="url" name="settings[discord_webhook]" id="discord_webhook" value="<?php echo htmlspecialchars($current_settings['discord_webhook']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                <div>
                                    <label for="discord_webhook" class="block text-sm font-medium text-gray-400 mb-1">SMTP Server</label>
                                    <input type="url" name="settings[discord_webhook]" id="discord_webhook" value="<?php echo htmlspecialchars($current_settings['discord_webhook']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                <div>
                                    <label for="discord_webhook" class="block text-sm font-medium text-gray-400 mb-1">SMTP Port</label>
                                    <input type="url" name="settings[discord_webhook]" id="discord_webhook" value="<?php echo htmlspecialchars($current_settings['discord_webhook']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                <div>
                                    <label for="discord_webhook" class="block text-sm font-medium text-gray-400 mb-1">Email Password</label>
                                    <input type="url" name="settings[discord_webhook]" id="discord_webhook" value="<?php echo htmlspecialchars($current_settings['discord_webhook']); ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                            </div>

                            <div class=" px-6 py-4 text-right">

                            <button type="submit" class="mt-4 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg flex items-center gap-2 transition-colors text-sm font-medium inline-flex">
                                Save
                            </button>
                            
                            </div>
                        </div>
                        <hr class="border-white/10">
                        <div>
                            <h3 class="text-lg font-medium text-white mb-4">Deployment Systems</h3>
                            <div class="space-y-4">
                                <button class="text-left bg-[#ffffff0a] hover:bg-[#ffffff15] text-white p-4 rounded-xl border border-[#ffffff24] flex items-center gap-3 transition-all">
                                    <span class="text-[#948db3] text-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github-icon lucide-github"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
                                    </span>
                                    <div><p class="font-bold">Github</p><p class="text-xs text-[#948db3]">github-username</p></div>
                                </button>
                            </div>

                            <div class=" py-6 text-left">

                            <?php
                            $client_id = __GITHUB_APK_CLIENT__;
                            $redirect_uri = __GITHUB_APK_REDIRECT__;
                            $scopes = 'repo'; // This gives access to create and push to repos
                            $auth_url = "https://github.com/login/oauth/authorize?client_id=$client_id&redirect_uri=$redirect_uri&scope=$scopes&state=" . bin2hex(random_bytes(16));
                            ?>

                            <a href="<?php echo $auth_url; ?>" style="padding: 10px; margin:2rem 0px;  background: #24292e; color: white; text-decoration: none; border-radius: 5px;">
                                Authenticate with GitHub
                            </a>
                            
                            </div>
                        </div>

                    </div>

                </div>
            </form>
        </div>
    </main>
</div>