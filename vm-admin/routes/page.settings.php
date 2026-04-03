<?php
session_start();
$db = initiate_web_database(); 

// Helper to get setting
function get_setting($db, $key, $default = '') {
    $result = $db->query("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $result && isset($result[0]['value']) ? $result[0]['value'] : $default;
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $value) {
        $value = is_array($value) ? json_encode($value) : trim($value);
        
        // Using "INSERT ... ON DUPLICATE KEY UPDATE" if your DB driver supports it, 
        // otherwise the manual check you had works fine:
        $exists = $db->query("SELECT id FROM settings WHERE `key` = ?", [$key]);
        if ($exists) {
            $db->query("UPDATE settings SET value = ? WHERE `key` = ?", [$value, $key]);
        } else {
            $db->query("INSERT INTO settings (`key`, `value`) VALUES (?, ?)", [$key, $value]);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit;
}

// Configuration Map for Keys and Defaults
$setting_map = [
    // Branding & SEO
    'site_title' => 'Varsity Market',
    'meta_description' => 'The premier marketplace for students.',
    'meta_keywords' => 'market, university, student trade',
    
    // Email Config
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_user' => '',
    'smtp_pass' => '',
    'email_template' => '<html><body><h1>Hello {{name}}</h1><p>{{message}}</p></body></html>',
    
    // App & Domain
    'connector_code' => bin2hex(random_bytes(8)),
    'custom_domain' => '',
    'dns_record_value' => '192.168.1.1', // Your static target
];

$current = [];
foreach ($setting_map as $key => $def) {
    $current[$key] = get_setting($db, $key, $def);
}
?>

<div class="flex flex-1 flex-col overflow-hidden bg-gray-900 text-white min-h-screen">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">
                
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">System Configuration</h2>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg font-medium transition-all">
                        Save All Changes
                    </button>
                </div>

                <div class="space-y-6">
                    
                    <section class="bg-gray-800 border border-white/5 rounded-xl p-6">
                        <h3 class="text-lg font-semibold mb-4 text-purple-400">Site Branding & SEO</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">Site Title</label>
                                <input type="text" name="settings[site_title]" value="<?= htmlspecialchars($current['site_title']) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 focus:border-purple-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">Meta Keywords</label>
                                <input type="text" name="settings[meta_keywords]" value="<?= htmlspecialchars($current['meta_keywords']) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 focus:border-purple-500 outline-none">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">Meta Description</label>
                                <textarea name="settings[meta_description]" rows="2" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 focus:border-purple-500 outline-none"><?= htmlspecialchars($current['meta_description']) ?></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="bg-gray-800 border border-white/5 rounded-xl p-6">
                        <h3 class="text-lg font-semibold mb-4 text-purple-400">SMTP & Email Templates</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">SMTP Host</label>
                                <input type="text" name="settings[smtp_host]" value="<?= htmlspecialchars($current['smtp_host']) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">Port</label>
                                <input type="text" name="settings[smtp_port]" value="<?= htmlspecialchars($current['smtp_port']) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">Username</label>
                                <input type="text" name="settings[smtp_user]" value="<?= htmlspecialchars($current['smtp_user']) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">Password</label>
                                <input type="password" name="settings[smtp_pass]" value="<?= htmlspecialchars($current['smtp_pass']) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">HTML Email Template</label>
                            <textarea name="settings[email_template]" rows="6" class="w-full font-mono text-sm bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 focus:border-purple-500 outline-none"><?= htmlspecialchars($current['email_template']) ?></textarea>
                            <p class="text-[10px] text-gray-500 mt-1">Use {{name}}, {{message}}, and {{link}} as placeholders.</p>
                        </div>
                    </section>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <section class="bg-gray-800 border border-white/5 rounded-xl p-6">
                            <h3 class="text-lg font-semibold mb-2 text-purple-400">PWA Connector</h3>
                            <p class="text-sm text-gray-400 mb-4">Use this code in your mobile app to sync admin settings.</p>
                            <div class="flex gap-2">
                                <input type="text" name="settings[connector_code]" value="<?= htmlspecialchars($current['connector_code']) ?>" class="flex-1 bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 font-mono text-purple-400" readonly>
                                <button type="button" onclick="navigator.clipboard.writeText('<?= $current['connector_code'] ?>')" class="bg-gray-700 px-3 rounded-lg hover:bg-gray-600 transition-colors text-sm">Copy</button>
                            </div>
                        </section>

                        <section class="bg-gray-800 border border-white/5 rounded-xl p-6">
                            <h3 class="text-lg font-semibold mb-2 text-purple-400">Domain Mapping</h3>
                            <label class="block text-xs font-medium text-gray-400 mb-1 uppercase">Your Custom Domain</label>
                            <input type="text" name="settings[custom_domain]" placeholder="e.g. shop.yourname.com" value="<?= htmlspecialchars($current['custom_domain']) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 mb-3 outline-none">
                            
                            <div class="p-3 bg-blue-900/20 border border-blue-500/30 rounded-lg">
                                <p class="text-[11px] text-blue-300 uppercase font-bold mb-1">Required DNS Record (A)</p>
                                <div class="flex justify-between text-sm font-mono">
                                    <span class="text-gray-400">Type: A</span>
                                    <span class="text-white">Value: <?= $current['dns_record_value'] ?></span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <section class="bg-gray-800 border border-white/5 rounded-xl p-6">
                        <h3 class="text-lg font-semibold mb-4 text-purple-400">GitHub Deployment</h3>
                        <?php if (isset($_SESSION['github_user'])): ?>
                            <div class="flex items-center justify-between p-4 bg-green-900/10 border border-green-500/20 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-bold"><?= $_SESSION['github_user'] ?></p>
                                        <p class="text-xs text-green-400">Connected for this session</p>
                                    </div>
                                </div>
                                <a href="logout_github.php" class="text-xs text-red-400 hover:underline">Disconnect</a>
                            </div>
                        <?php else: ?>
                            <a href="github_auth.php" class="inline-flex items-center gap-2 bg-[#24292e] hover:bg-black px-5 py-2.5 rounded-lg transition-all text-sm">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                                Connect GitHub Repository
                            </a>
                        <?php endif; ?>
                    </section>

                </div>
            </form>
        </div>
    </main>
</div>