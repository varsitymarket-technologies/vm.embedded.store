<?php
/**
 * page.settings.php - Redesigned Premium Settings Interface
 */

$db_engine = __DB_MODULE__;
$db_site = initiate_web_database();
$domain = __DOMAIN__;

// --- 1. Encryption & Encrypted Config Handling ---
$config_key = create_enc_key();
$config_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/email.config.enc";

function save_encrypted_config($path, $data, $key)
{
    if (!file_exists(dirname($path)))
        mkdir(dirname($path), 0777, true);
    $json = json_encode($data);
    $encrypted = __encryption__($json, $key);
    return file_put_contents($path, $encrypted);
}

function load_encrypted_config($path, $key)
{
    if (!file_exists($path))
        return [];
    $encrypted = file_get_contents($path);
    $json = __decryption__($encrypted, $key);
    return json_decode($json, true) ?: [];
}

// --- 2. Handle Save Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_email_config') {
        $email_settings = $_POST['email'] ?? [];
        save_encrypted_config($config_path, $email_settings, $config_key);
        header("Location: ?tab=messaging&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_branding') {
        $branding = $_POST['branding'] ?? [];
        foreach ($branding as $key => $val) {
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$key, $val, $val]);
        }
        header("Location: ?tab=branding&saved=1");
        exit;
    }

    if ($_POST['action'] === 'generate_api_key') {
        $key_name = htmlspecialchars(trim($_POST['key_name'] ?? 'Untitled Key'), ENT_QUOTES, 'UTF-8');
        $prefix = 'vm_live_';
        $api_key = $prefix . bin2hex(random_bytes(24));
        $private_db = initiate_private_database($domain);
        if ($private_db) {
            $private_db->query("INSERT INTO api_keys (key_name, api_key, active) VALUES (?, ?, 1)", [$key_name, $api_key]);
        }
        header("Location: ?tab=dev&saved=1&new_key=" . urlencode($api_key));
        exit;
    }

    if ($_POST['action'] === 'revoke_api_key') {
        $key_id = (int) ($_POST['key_id'] ?? 0);
        $private_db = initiate_private_database($domain);
        if ($private_db && $key_id > 0) {
            $private_db->query("UPDATE api_keys SET active = 0 WHERE id = ?", [$key_id]);
        }
        header("Location: ?tab=dev&saved=1");
        exit;
    }

    if ($_POST['action'] === 'delete_api_key') {
        $key_id = (int) ($_POST['key_id'] ?? 0);
        $private_db = initiate_private_database($domain);
        if ($private_db && $key_id > 0) {
            $private_db->query("DELETE FROM api_keys WHERE id = ?", [$key_id]);
        }
        header("Location: ?tab=dev&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_currency') {
        $currency = $_POST['currency'] ?? [];
        foreach ($currency as $key => $val) {
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$key, $val, $val]);
        }
        // Save accepted currencies as JSON array
        $accepted = $_POST['accepted_currencies'] ?? [];
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('accepted_currencies', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [json_encode($accepted), json_encode($accepted)]);
        header("Location: ?tab=currency&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_payment') {
        $payment_config = $_POST['payment'] ?? [];
        $payment_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/payment.config.enc";
        save_encrypted_config($payment_path, $payment_config, $config_key);
        header("Location: ?tab=payment&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_discord') {
        $discord = $_POST['discord'] ?? [];
        foreach ($discord as $key => $val) {
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", ['discord_' . $key, $val, $val]);
        }
        // Save notification events as JSON
        $events = $_POST['discord_events'] ?? [];
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('discord_events', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [json_encode($events), json_encode($events)]);
        // Save mention settings
        $mentions = $_POST['discord_mentions'] ?? [];
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('discord_mentions', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [json_encode($mentions), json_encode($mentions)]);
        header("Location: ?tab=app&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_console') {
        $console = $_POST['console'] ?? [];
        if (!empty($console['regenerate_secret'])) {
            $new_secret = 'vm_sec_' . bin2hex(random_bytes(12));
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('console_secret_key', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$new_secret, $new_secret]);
        }
        header("Location: ?tab=console&saved=1");
        exit;
    }
}

// --- Helper: load a setting from the site DB ---
function get_setting($db, $key, $default = '') {
    $result = $db->query("SELECT value FROM settings WHERE `key` = ? LIMIT 1", [$key]);
    return $result[0]['value'] ?? $default;
}

// --- 3. Load Current Configs ---
$email_current = load_encrypted_config($config_path, $config_key);
$site_name = website_data('name');
$site_domain = website_data('domain');
$site_theme = website_data('theme');

// Currency settings
$cur_default = get_setting($db_site, 'default_currency', 'ZAR');
$cur_symbol_pos = get_setting($db_site, 'symbol_position', 'left');
$cur_decimals = get_setting($db_site, 'decimal_places', '2');
$cur_thousands = get_setting($db_site, 'thousand_separator', ',');
$cur_decimal_sep = get_setting($db_site, 'decimal_separator', '.');
$cur_auto_convert = get_setting($db_site, 'auto_conversion', '1');
$cur_accepted = json_decode(get_setting($db_site, 'accepted_currencies', '["ZAR","USD"]'), true) ?: ['ZAR','USD'];

// Payment settings
$payment_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/payment.config.enc";
$payment_current = load_encrypted_config($payment_path, $config_key);

// Discord settings
$discord_webhook = get_setting($db_site, 'discord_webhook_url', '');
$discord_enabled = get_setting($db_site, 'discord_enabled', '0');
$discord_events = json_decode(get_setting($db_site, 'discord_events', '["new_order","payment_received","order_fulfilled","low_stock"]'), true) ?: [];
$discord_mentions = json_decode(get_setting($db_site, 'discord_mentions', '[]'), true) ?: [];
$discord_style = get_setting($db_site, 'discord_message_style', 'embed');
$discord_color = get_setting($db_site, 'discord_embed_color', '#5865F2');
$discord_bot_name = get_setting($db_site, 'discord_bot_name', 'Varsity Market Store');
$discord_avatar = get_setting($db_site, 'discord_avatar_url', '');

// Defaults for Email
$email_current['host'] = $email_current['host'] ?? '';
$email_current['port'] = $email_current['port'] ?? '587';
$email_current['user'] = $email_current['user'] ?? '';
$email_current['pass'] = $email_current['pass'] ?? '';
$email_current['template'] = $email_current['template'] ?? "<html>\n<body style=\"font-family: sans-serif; color: #333;\">\n  <div style=\"max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;\">\n    <h1 style=\"color: #7a1aab;\">Hello {{name}}!</h1>\n    <p>{{message}}</p>\n    <hr style=\"border: none; border-top: 1px solid #eee; margin: 20px 0;\">\n    <small style=\"color: #888;\">Sent from Your Online Store</small>\n  </div>\n</body>\n</html>";

$active_tab = $_GET['tab'] ?? 'general';
?>


<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden">
    <!-- Header -->
    <?php @include_once "header.php"; ?>


    <div class="animate-fade-in pb-20 flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
        <!-- Header Section -->
        <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <?php if (isset($_GET['saved'])): ?>
                <div id="saveToast"
                    class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-full text-sm font-bold animate-bounce">
                    <i class="bi bi-check2-circle text-lg"></i>
                    <span>Changes saved successfully</span>
                </div>
                <script>setTimeout(() => document.getElementById('saveToast').remove(), 5000);</script>
            <?php endif; ?>
        </div>


        <?php if ($active_tab == 'general'): ?>
            <div>
                <h1 class="text-4xl font-black tracking-tight text-white mb-2">Settings</h1>
                <p class="text-gray-400 text-lg">Configure your store environment, branding, and integrations.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div onclick="window.location.href='?tab=branding'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-brush-fill text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Store Branding</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your store branding.</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-brush-fill text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=email'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-envelope-fill text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Email Configuration</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your email settings for
                        transactional emails.</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-envelope-fill text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=payment'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-credit-card-fill text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Payment Methods</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your payment methods</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-credit-card-fill text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=currency'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-currency-exchange text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Currency</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your store currency</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-currency-exchange text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=dev'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-gear-fill text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Developer Settings</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your developer settings</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-gear-fill text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=app'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-plugin text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Application Extension</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your Application Plugins</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-plugin text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=console'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-phone text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Mobile App Console</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Connect the store to your mobile app.</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-phone text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=deployment'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-github text-xl"></i>
                        </div>
                        <span class="font-bold text-white">GitHub Deployment</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Connect your source code for automated
                        delivery cycles.</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-github text-9xl"></i>
                    </div>
                </div>

                <div onclick="window.location.href='?tab=domain'"
                    class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-globe text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Connect Domain</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Connect your domain to your store.</p>
                    <div
                        class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-globe text-9xl"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <div class="settings-layout">
            <!-- Sidebar Navigation -->

            <!-- Main Content Area -->
            <main class="settings-content">

                <?php if ($active_tab == 'branding'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_branding">
                        <div class="v-card animate-slide-up">
                            <div class="v-card-header">
                                <h2 class="text-xl font-bold text-white">Public Branding</h2>
                                <p class="text-sm text-gray-400 mt-2">Personalize how your store appears to customers.</p>
                            </div>
                            <div class="v-card-body space-y-8">
                                <div class="space-y-3">
                                    <label class="text-sm font-bold text-gray-200">Store Name</label>
                                    <input type="text" name="branding[wb_name]" value="<?= htmlspecialchars($site_name) ?>"
                                        class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all shadow-inner"
                                        placeholder="e.g. My Premium Store">
                                    <p class="text-[11px] text-gray-500">This name appears in browser tabs and customer
                                        emails.
                                    </p>
                                </div>

                                <div class="space-y-3 opacity-60">
                                    <label class="text-sm font-bold text-gray-200">Primary Public URL</label>
                                    <div class="relative">
                                        <input type="text" value="<?= htmlspecialchars($site_domain) ?>" readonly
                                            class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3.5 text-gray-500 cursor-not-allowed">
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black bg-white/5 px-2 py-0.5 rounded uppercase">Connected</span>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="v-card-footer">
                                <button type="submit"
                                    class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                                    Update Branding
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($active_tab == 'domain'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Domain Mapping</h2>
                            <p class="text-sm text-gray-400 mt-2">Connect your custom domain to your hosted repository.</p>
                        </div>
                        <div class="v-card-body space-y-12">
                            <div>
                                <div class="flex items-center gap-3 mb-6">
                                    <span
                                        class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-black text-xs">1</span>
                                    <h3 class="text-sm font-black text-white uppercase tracking-widest">Select Provider</h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div
                                        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                                        <div class="flex items-center gap-4 mb-3 relative z-10">
                                            <div
                                                class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                                                <i class="bi bi-github text-xl"></i>
                                            </div>
                                            <span class="font-bold text-white">GitHub Pages</span>
                                        </div>
                                        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Optimized for free
                                            static
                                            hosting. High reliability.</p>
                                        <div
                                            class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                                            <i class="bi bi-github text-9xl"></i>
                                        </div>
                                    </div>
                                    <div
                                        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                                        <div class="flex items-center gap-4 mb-3 relative z-10">
                                            <div
                                                class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                                                <i class="bi bi-triangle-fill text-xl"></i>
                                            </div>
                                            <span class="font-bold text-white">Vercel Edge</span>
                                        </div>
                                        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Premium global
                                            performance and automatic SSL.</p>
                                        <div
                                            class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                                            <i class="bi bi-triangle-fill text-9xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center gap-3 mb-6">
                                    <span
                                        class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-black text-xs">2</span>
                                    <h3 class="text-sm font-black text-white uppercase tracking-widest">DNS Configuration
                                    </h3>
                                </div>
                                <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#080808]">
                                    <table class="w-full text-left text-xs font-mono">
                                        <thead class="bg-white/5 text-gray-500 uppercase tracking-tighter">
                                            <tr>
                                                <th class="px-6 py-4 font-black">Type</th>
                                                <th class="px-6 py-4 font-black">Name</th>
                                                <th class="px-6 py-4 font-black">Value</th>
                                                <th class="px-6 py-4 font-black text-right">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-white/5">
                                            <tr class="hover:bg-white/[0.02] transition-colors">
                                                <td class="px-6 py-4">
                                                    <span
                                                        class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-purple-500/20">CNAME</span>
                                                </td>
                                                <td class="px-6 py-4 text-gray-300 font-bold">www</td>
                                                <td class="px-6 py-4 text-purple-400">cname.vercel-dns.com</td>
                                                <td class="px-6 py-4 text-right"><i
                                                        class="bi bi-check-circle-fill text-emerald-500"></i></td>
                                            </tr>
                                            <tr class="hover:bg-white/[0.02] transition-colors">
                                                <td class="px-6 py-4">
                                                    <span
                                                        class="bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-orange-500/20">A</span>
                                                </td>
                                                <td class="px-6 py-4 text-gray-300 font-bold">@</td>
                                                <td class="px-6 py-4 text-purple-400">
                                                    <?= $_SERVER['SERVER_ADDR'] ?>
                                                </td>
                                                <td class="px-6 py-4 text-right"><i
                                                        class="bi bi-cloud-check-fill text-emerald-500"></i></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <br><br>

                <?php endif; ?>

                <?php if ($active_tab == 'email'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <form method="POST" id="emailForm">
                        <input type="hidden" name="action" value="save_email_config">
                        <div class="v-card animate-slide-up">
                            <div class="v-card-header flex justify-between items-center">
                                <div>
                                    <h2 class="text-xl font-bold text-white">Email Configuration</h2>
                                    <p class="text-sm text-gray-400 mt-2">Securely store SMTP and notification templates.
                                    </p>
                                </div>
                            </div>
                            <div class="v-card-body space-y-12">
                                <!-- SMTP Section -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-500">SMTP
                                            Host</label>
                                        <input type="text" name="email[host]"
                                            value="<?= htmlspecialchars($email_current['host']) ?>"
                                            placeholder="smtp.gmail.com"
                                            class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-500">SMTP
                                            Port</label>
                                        <input type="text" name="email[port]"
                                            value="<?= htmlspecialchars($email_current['port']) ?>"
                                            class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                    </div>
                                    <div class="space-y-3">
                                        <label
                                            class="text-xs font-black uppercase tracking-widest text-gray-500">Username</label>
                                        <input type="text" name="email[user]"
                                            value="<?= htmlspecialchars($email_current['user']) ?>"
                                            class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                    </div>
                                    <div class="space-y-3">
                                        <label
                                            class="text-xs font-black uppercase tracking-widest text-gray-500">Password</label>
                                        <div class="relative group">
                                            <input type="password" name="email[pass]"
                                                value="<?= htmlspecialchars($email_current['pass']) ?>"
                                                class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                            <i
                                                class="bi bi-eye-slash absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 group-hover:text-gray-400 cursor-pointer"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Editor -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-end">
                                        <div class="space-y-1">
                                            <label class="text-xs font-black uppercase tracking-widest text-gray-500">System
                                                Notification Template</label>
                                            <p class="text-[10px] text-gray-600">Supports <code
                                                    class="text-purple-400">{{name}}</code> and <code
                                                    class="text-purple-400">{{message}}</code> tags.</p>
                                        </div>
                                        <button type="button" onclick="togglePreview()"
                                            class="px-4 py-1.5 bg-purple-600/10 text-purple-400 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-purple-600 hover:text-white transition-all">
                                            <i class="bi bi-eye-fill mr-1"></i> Preview Template
                                        </button>
                                    </div>
                                    <textarea name="email[template]" id="emailTemplate" rows="12"
                                        class="w-full bg-[#020202] border border-white/10 rounded-2xl px-6 py-6 font-mono text-xs text-purple-300 outline-none focus:border-purple-500 leading-relaxed shadow-lg"><?= htmlspecialchars($email_current['template']) ?></textarea>
                                </div>
                            </div>
                            <div class="v-card-footer">
                                <button type="submit"
                                    class="bg-purple-600 text-white px-8 py-2.5 rounded-full text-sm font-black hover:bg-purple-500 transition-all shadow-xl shadow-purple-900/40">
                                    Save Configuration
                                </button>
                            </div>
                        </div>
                    </form>


                    <!-- Preview Display -->
                    <div id="previewContainer" class="hidden animate-slide-up mt-8">
                        <div class="v-card border-purple-500/30 overflow-hidden">
                            <div class="v-card-header bg-purple-600/5 flex justify-between items-center py-4">
                                <h2 class="text-xs font-black uppercase tracking-[0.3em] text-purple-400">Live Render</h2>
                                <button onclick="togglePreview()"
                                    class="text-gray-500 hover:text-white transition-colors"><i
                                        class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="v-card-body bg-[#fcfcfc] p-0 shadow-inner">
                                <iframe id="previewFrame" class="w-full h-[600px] border-none shadow-2xl"></iframe>
                            </div>
                        </div>
                    </div>

                    <script>
                        function togglePreview() {
                            const container = document.getElementById('previewContainer');
                            const isHidden = container.classList.contains('hidden');

                            if (isHidden) {
                                container.classList.remove('hidden');
                                updatePreview();
                                setTimeout(() => container.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
                            } else {
                                container.classList.add('hidden');
                            }
                        }

                        function updatePreview() {
                            const template = document.getElementById('emailTemplate').value;
                            const frame = document.getElementById('previewFrame');

                            let rendered = template
                                .replace(/{{name}}/g, 'Valued Customer')
                                .replace(/{{message}}/g, 'This is a sample encrypted notification sent from your Store Admin. The styling here will match how your customers see automated emails.')
                                .replace(/{{link}}/g, '#');

                            const doc = frame.contentDocument || frame.contentWindow.document;
                            doc.open();
                            doc.write(rendered);
                            doc.close();
                        }
                    </script>

                    <br><br>

                <?php endif; ?>

                <?php if ($active_tab == 'deployment'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Advanced Deployment</h2>
                            <p class="text-sm text-gray-400 mt-2">Connect your source code for automated delivery cycles.
                            </p>
                        </div>
                        <div class="v-card-body text-center py-20 px-8">
                            <?php if (isset($_SESSION['github_token'])): ?>
                                <div class="v-card animate-slide-up">
                                    <div class="v-card-body text-center py-20 px-8">
                                        <div
                                            class="w-24 h-24 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-8 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                                            <i class="bi bi-github text-5xl text-gray-300"></i>
                                        </div>
                                        <h3 class="text-2xl font-black text-white mb-4">Store Deployment Ready</h3>
                                        <p class="text-gray-500 text-sm max-w-sm mx-auto mb-10 leading-relaxed font-medium">
                                            Your store is now connected to your GitHub account. You can now proceed to deploy
                                            your store to GitHub.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div
                                    class="w-24 h-24 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-8 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                                    <i class="bi bi-github text-5xl text-gray-300"></i>
                                </div>
                                <h3 class="text-2xl font-black text-white mb-4">Connect GitHub Repository</h3>
                                <p class="text-gray-500 text-sm max-w-sm mx-auto mb-10 leading-relaxed font-medium">Authorize
                                    Varsity Market to automate your builds and push production updates directly to your hosting
                                    provider.</p>

                                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                    <a onclick="window.location.href=`https://github.com/login/oauth/authorize?client_id=<?php echo $_SERVER['__GITHUB_APK_CLIENT__']; ?>`"
                                        class="inline-flex items-center gap-3 bg-[#24292e] hover:bg-black text-white px-8 py-3.5 rounded-full transition-all font-black text-sm shadow-xl shadow-black/40 group">
                                        <i class="bi bi-plug-fill text-lg group-hover:rotate-45 transition-transform"></i>
                                        Authorize GitHub
                                    </a>
                                    <a href="#"
                                        class="text-xs font-black uppercase tracking-widest text-gray-500 hover:text-white transition-colors">Setup
                                        Guide &rarr;</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endif; ?>

                <?php if ($active_tab == 'currency'):
                    $currencies = [
                        'ZAR' => ['flag' => '', 'name' => 'South African Rand', 'symbol' => 'R'],
                        'USD' => ['flag' => '', 'name' => 'US Dollar', 'symbol' => '$'],
                        'EUR' => ['flag' => '', 'name' => 'Euro', 'symbol' => '€'],
                        'GBP' => ['flag' => '', 'name' => 'British Pound', 'symbol' => '£'],
                        'CAD' => ['flag' => '', 'name' => 'Canadian Dollar', 'symbol' => 'C$'],
                        'AUD' => ['flag' => '', 'name' => 'Australian Dollar', 'symbol' => 'A$'],
                        'JPY' => ['flag' => '', 'name' => 'Japanese Yen', 'symbol' => '¥'],
                        'CNY' => ['flag' => '', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
                        'INR' => ['flag' => '', 'name' => 'Indian Rupee', 'symbol' => '₹'],
                        'NGN' => ['flag' => '', 'name' => 'Nigerian Naira', 'symbol' => '₦'],
                        'KES' => ['flag' => '', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh'],
                        'BWP' => ['flag' => '', 'name' => 'Botswana Pula', 'symbol' => 'P'],
                    ];
                ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_currency">
                        <div class="v-card animate-slide-up">
                            <div class="v-card-header">
                                <h2 class="text-xl font-bold text-white">Store Currencies</h2>
                                <p class="text-sm text-gray-400 mt-2">Configure your store's default currency and available payment options.</p>
                            </div>
                            <div class="v-card-body py-8 px-8">

                                <!-- Default Currency Selection -->
                                <div class="mb-8">
                                    <label class="block text-sm font-bold text-gray-300 mb-3 uppercase tracking-wide">Default Currency</label>
                                    <select name="currency[default_currency]"
                                        class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors">
                                        <?php foreach ($currencies as $code => $c): ?>
                                            <option value="<?= $code ?>" <?= $cur_default === $code ? 'selected' : '' ?>><?= $c['flag'] ?> <?= $code ?> - <?= htmlspecialchars($c['name']) ?> (<?= $c['symbol'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-2">This is the main currency displayed on your storefront.</p>
                                </div>

                                <!-- Accepted Currencies -->
                                <div class="mb-8">
                                    <label class="block text-sm font-bold text-gray-300 mb-3 uppercase tracking-wide">Accepted Currencies</label>
                                    <p class="text-xs text-gray-500 mb-3">Customers can pay using any of these currencies (auto-converted).</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                        <?php foreach ($currencies as $code => $c): ?>
                                        <label class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-white/10 transition-colors">
                                            <input type="checkbox" name="accepted_currencies[]" value="<?= $code ?>" class="w-4 h-4 accent-blue-500" <?= in_array($code, $cur_accepted) ? 'checked' : '' ?>>
                                            <span class="text-white text-sm"><?= $c['flag'] ?> <?= $code ?> (<?= $c['symbol'] ?>)</span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Currency Format Settings -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Symbol Position</label>
                                        <select name="currency[symbol_position]"
                                            class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                            <option value="left" <?= $cur_symbol_pos === 'left' ? 'selected' : '' ?>>Left ($100)</option>
                                            <option value="right" <?= $cur_symbol_pos === 'right' ? 'selected' : '' ?>>Right (100$)</option>
                                            <option value="left_space" <?= $cur_symbol_pos === 'left_space' ? 'selected' : '' ?>>Left with space ($ 100)</option>
                                            <option value="right_space" <?= $cur_symbol_pos === 'right_space' ? 'selected' : '' ?>>Right with space (100 $)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Decimal Places</label>
                                        <select name="currency[decimal_places]"
                                            class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                            <option value="0" <?= $cur_decimals === '0' ? 'selected' : '' ?>>0 (123)</option>
                                            <option value="2" <?= $cur_decimals === '2' ? 'selected' : '' ?>>2 (123.45)</option>
                                            <option value="3" <?= $cur_decimals === '3' ? 'selected' : '' ?>>3 (123.456)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Thousand & Decimal Separators -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Thousand Separator</label>
                                        <select name="currency[thousand_separator]"
                                            class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                            <option value="," <?= $cur_thousands === ',' ? 'selected' : '' ?>>Comma (1,234.56)</option>
                                            <option value="." <?= $cur_thousands === '.' ? 'selected' : '' ?>>Period (1.234,56)</option>
                                            <option value="space" <?= $cur_thousands === 'space' ? 'selected' : '' ?>>Space (1 234.56)</option>
                                            <option value="none" <?= $cur_thousands === 'none' ? 'selected' : '' ?>>None (1234.56)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Decimal Separator</label>
                                        <select name="currency[decimal_separator]"
                                            class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                            <option value="." <?= $cur_decimal_sep === '.' ? 'selected' : '' ?>>Period (123.45)</option>
                                            <option value="," <?= $cur_decimal_sep === ',' ? 'selected' : '' ?>>Comma (123,45)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Auto-Conversion Toggle -->
                                <div class="bg-white/5 rounded-xl p-5 mb-8">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div>
                                            <h4 class="text-white font-bold text-sm uppercase tracking-wide">Automatic Currency Conversion</h4>
                                            <p class="text-gray-500 text-xs mt-1">Enable real-time exchange rate updates via API</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="currency[auto_conversion]" value="1" class="sr-only peer" <?= $cur_auto_convert === '1' ? 'checked' : '' ?>>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            <span class="ml-3 text-sm font-medium text-gray-300"><?= $cur_auto_convert === '1' ? 'Enabled' : 'Disabled' ?></span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Save Button -->
                                <div class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-4 border-t border-gray-800">
                                    <button type="submit"
                                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-3 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-blue-900/30">
                                        Save Currency Settings
                                    </button>
                                </div>

                            </div>
                        </div>
                    </form>

                <?php endif; ?>

                <?php if ($active_tab == 'payment'):
                    $pay_cod_enabled = $payment_current['cod_enabled'] ?? '1';
                    $pay_cod_min = $payment_current['cod_min_amount'] ?? '';
                    $pay_cod_fee = $payment_current['cod_fee'] ?? '0.00';
                    $pay_yoco_enabled = $payment_current['yoco_enabled'] ?? '0';
                    $pay_yoco_secret = $payment_current['yoco_secret'] ?? '';
                    $pay_yoco_public = $payment_current['yoco_public'] ?? '';
                    $pay_yoco_mode = $payment_current['yoco_mode'] ?? 'test';
                    $pay_yoco_fee = $payment_current['yoco_fee'] ?? '2.9';
                    $pay_paypal_enabled = $payment_current['paypal_enabled'] ?? '0';
                    $pay_paypal_client = $payment_current['paypal_client_id'] ?? '';
                    $pay_paypal_secret = $payment_current['paypal_secret'] ?? '';
                    $pay_paypal_env = $payment_current['paypal_env'] ?? 'sandbox';
                    $pay_paypal_fee = $payment_current['paypal_fee'] ?? '3.4';
                    $pay_paypal_guest = $payment_current['paypal_guest_checkout'] ?? '0';
                ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_payment">
                        <div class="v-card animate-slide-up">
                            <div class="v-card-header">
                                <h2 class="text-xl font-bold text-white">Payment Methods</h2>
                                <p class="text-sm text-gray-400 mt-2">Configure how your customers can pay for their orders.</p>
                            </div>
                            <div class="v-card-body py-8 px-8">

                                <!-- Cash on Delivery -->
                                <div class="bg-white/5 rounded-xl p-5 mb-6">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                                                <i class="bi bi-cash-stack text-2xl text-emerald-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-white font-bold text-base">Cash on Delivery</h4>
                                                <p class="text-gray-500 text-xs mt-1">Customers pay in cash when they receive their order</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="payment[cod_enabled]" value="0">
                                            <input type="checkbox" name="payment[cod_enabled]" value="1" class="sr-only peer" id="cod_toggle" <?= $pay_cod_enabled === '1' ? 'checked' : '' ?>>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                            <span class="ml-3 text-sm font-medium text-gray-300"><?= $pay_cod_enabled === '1' ? 'Enabled' : 'Disabled' ?></span>
                                        </label>
                                    </div>
                                    <div id="cod_settings" class="mt-5 pt-4 border-t border-gray-700/50" style="opacity:<?= $pay_cod_enabled === '1' ? '1' : '0.5' ?>">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Minimum Order Amount (Optional)</label>
                                                <input type="number" step="0.01" name="payment[cod_min_amount]" value="<?= htmlspecialchars($pay_cod_min) ?>" placeholder="No minimum"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Additional Fee</label>
                                                <input type="number" step="0.01" name="payment[cod_fee]" value="<?= htmlspecialchars($pay_cod_fee) ?>"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Credit Card via YOCO -->
                                <div class="bg-white/5 rounded-xl p-5 mb-6">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                                                <i class="bi bi-credit-card-2-front text-2xl text-purple-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-white font-bold text-base">YOCO (Credit Card)</h4>
                                                <p class="text-gray-500 text-xs mt-1">Accept Visa, Mastercard, and American Express via YOCO</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="payment[yoco_enabled]" value="0">
                                            <input type="checkbox" name="payment[yoco_enabled]" value="1" class="sr-only peer" id="yoco_toggle" <?= $pay_yoco_enabled === '1' ? 'checked' : '' ?>>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                            <span class="ml-3 text-sm font-medium text-gray-300"><?= $pay_yoco_enabled === '1' ? 'Enabled' : 'Disabled' ?></span>
                                        </label>
                                    </div>
                                    <div id="yoco_settings" class="mt-5 pt-4 border-t border-gray-700/50 <?= $pay_yoco_enabled !== '1' ? 'hidden' : '' ?>">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">YOCO Secret Key</label>
                                                <input type="password" name="payment[yoco_secret]" value="<?= htmlspecialchars($pay_yoco_secret) ?>" placeholder="sk_test_xxxxxxxxxxxxx"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500 font-mono">
                                                <p class="text-xs text-gray-500 mt-1">Find this in your YOCO dashboard under Developers &rarr; API Keys</p>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">YOCO Public Key</label>
                                                <input type="text" name="payment[yoco_public]" value="<?= htmlspecialchars($pay_yoco_public) ?>" placeholder="pk_test_xxxxxxxxxxxxx"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500 font-mono">
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Payment Mode</label>
                                                    <select name="payment[yoco_mode]"
                                                        class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500">
                                                        <option value="test" <?= $pay_yoco_mode === 'test' ? 'selected' : '' ?>>Test Mode</option>
                                                        <option value="live" <?= $pay_yoco_mode === 'live' ? 'selected' : '' ?>>Live Mode</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Transaction Fee (%)</label>
                                                    <input type="number" step="0.1" name="payment[yoco_fee]" value="<?= htmlspecialchars($pay_yoco_fee) ?>"
                                                        class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PayPal -->
                                <div class="bg-white/5 rounded-xl p-5 mb-6">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                                                <i class="bi bi-paypal text-2xl text-blue-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-white font-bold text-base">PayPal</h4>
                                                <p class="text-gray-500 text-xs mt-1">Accept payments via PayPal wallet, credit cards, and more</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="payment[paypal_enabled]" value="0">
                                            <input type="checkbox" name="payment[paypal_enabled]" value="1" class="sr-only peer" id="paypal_toggle" <?= $pay_paypal_enabled === '1' ? 'checked' : '' ?>>
                                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            <span class="ml-3 text-sm font-medium text-gray-300"><?= $pay_paypal_enabled === '1' ? 'Enabled' : 'Disabled' ?></span>
                                        </label>
                                    </div>
                                    <div id="paypal_settings" class="mt-5 pt-4 border-t border-gray-700/50 <?= $pay_paypal_enabled !== '1' ? 'hidden' : '' ?>">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">PayPal Client ID</label>
                                                <input type="text" name="payment[paypal_client_id]" value="<?= htmlspecialchars($pay_paypal_client) ?>" placeholder="AYxXxxxxxx...xxxxx"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 font-mono">
                                                <p class="text-xs text-gray-500 mt-1">Get from PayPal Developer Dashboard &rarr; Apps & Credentials</p>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">PayPal Secret Key</label>
                                                <input type="password" name="payment[paypal_secret]" value="<?= htmlspecialchars($pay_paypal_secret) ?>" placeholder="EJxxxxxx...xxxxx"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 font-mono">
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Environment</label>
                                                    <select name="payment[paypal_env]"
                                                        class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                                                        <option value="sandbox" <?= $pay_paypal_env === 'sandbox' ? 'selected' : '' ?>>Sandbox (Test)</option>
                                                        <option value="production" <?= $pay_paypal_env === 'production' ? 'selected' : '' ?>>Production (Live)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Transaction Fee (%)</label>
                                                    <input type="number" step="0.1" name="payment[paypal_fee]" value="<?= htmlspecialchars($pay_paypal_fee) ?>"
                                                        class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <input type="hidden" name="payment[paypal_guest_checkout]" value="0">
                                                <input type="checkbox" name="payment[paypal_guest_checkout]" value="1" id="paypal_credit_card" class="accent-blue-500" <?= $pay_paypal_guest === '1' ? 'checked' : '' ?>>
                                                <label for="paypal_credit_card" class="text-xs text-gray-300">Allow guest checkout (credit/debit cards without PayPal account)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Save Button -->
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-800">
                                    <div class="text-xs text-gray-500">
                                        <i class="bi bi-shield-check"></i> All payment credentials are stored with AES-256-CBC encryption
                                    </div>
                                    <button type="submit"
                                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-3 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-blue-900/30">
                                        Save Payment Settings
                                    </button>
                                </div>

                            </div>
                        </div>
                    </form>

                    <script>
                        document.getElementById('cod_toggle')?.addEventListener('change', function (e) {
                            const settings = document.getElementById('cod_settings');
                            if (settings) settings.style.opacity = e.target.checked ? '1' : '0.5';
                        });
                        document.getElementById('yoco_toggle')?.addEventListener('change', function (e) {
                            const settings = document.getElementById('yoco_settings');
                            const label = e.target.nextElementSibling.nextElementSibling;
                            if (settings) settings.classList.toggle('hidden', !e.target.checked);
                            if (label) label.textContent = e.target.checked ? 'Enabled' : 'Disabled';
                        });
                        document.getElementById('paypal_toggle')?.addEventListener('change', function (e) {
                            const settings = document.getElementById('paypal_settings');
                            const label = e.target.nextElementSibling.nextElementSibling;
                            if (settings) settings.classList.toggle('hidden', !e.target.checked);
                            if (label) label.textContent = e.target.checked ? 'Enabled' : 'Disabled';
                        });
                    </script>

                <?php endif; ?>

                <?php if ($active_tab == 'dev'):
                    $store_id = '';
                    $api_base_url = '';
                    $api_keys = [];
                    $private_db = null;
                    $new_key_display = $_GET['new_key'] ?? '';
                    $dev_error = '';

                    try {
                        $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
                        $store_id = $store_record[0]['id'] ?? '';
                        $api_base_url = __WEBSITE_DOMAIN__ . "/store-access/" . $store_id . "/";
                        if (!empty($domain)) {
                            $private_db = initiate_private_database($domain);
                            $api_keys = $private_db ? $private_db->query("SELECT * FROM api_keys ORDER BY created_at DESC") : [];
                        }
                    } catch (\Throwable $th) {
                        $dev_error = $th->getMessage();
                    }

                    $sdk_url = __WEBSITE_DOMAIN__ . "/store-access/" . $store_id . "/sdk/vm-store.js";
                ?>
                    <?php if (!empty($dev_error)): ?>
                    <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-exclamation-triangle text-amber-400"></i>
                            <div>
                                <p class="text-amber-400 font-bold text-sm">Configuration Notice</p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($dev_error, ENT_QUOTES, 'UTF-8'); ?>. Please ensure your store is properly configured.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <?php if (!empty($new_key_display)): ?>
                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-5 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="bi bi-check-circle-fill text-emerald-400 text-xl"></i>
                                <div class="flex-1">
                                    <h4 class="text-emerald-400 font-bold text-sm">New API Key Generated</h4>
                                    <p class="text-gray-400 text-xs mt-1 mb-3">Copy this key now. You won't be able to see the full key again.</p>
                                    <div class="flex items-center gap-3 bg-black/40 rounded-lg px-4 py-3">
                                        <code id="newKeyValue" class="text-sm font-mono text-white break-all flex-1"><?php echo htmlspecialchars($new_key_display, ENT_QUOTES, 'UTF-8'); ?></code>
                                        <button onclick="navigator.clipboard.writeText(document.getElementById('newKeyValue').textContent); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'"
                                            class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Developer Settings</h2>
                            <p class="text-sm text-gray-400 mt-2">API access and security credentials for external integrations.</p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- API Access Section -->
                            <div class="mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">API Access</h3>
                                        <p class="text-xs text-gray-500 mt-1">RESTful API endpoints for store management and automation</p>
                                    </div>
                                </div>

                                <!-- Store ID -->
                                <div class="bg-purple-500/5 rounded-xl p-4 mb-4 border border-purple-500/20">
                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <div>
                                            <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">Store ID</span>
                                            <p class="text-white font-mono text-sm mt-1"><?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?>'); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i> Copy', 2000)"
                                            class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- API Base URL -->
                                <div class="bg-blue-500/5 rounded-xl p-4 mb-5 border border-blue-500/20">
                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <div>
                                            <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">API Endpoint</span>
                                            <p id="apiEndpoint" class="text-white font-mono text-sm mt-1 break-all"><?php echo htmlspecialchars($api_base_url, ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <button onclick="navigator.clipboard.writeText(document.getElementById('apiEndpoint').textContent); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i> Copy', 2000)"
                                            class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- Available Endpoints -->
                                <div class="bg-white/5 rounded-xl p-4 mb-6">
                                    <h4 class="text-white font-bold text-sm mb-3"><i class="bi bi-book"></i> Available Endpoints</h4>
                                    <div class="space-y-2">
                                        <p class="text-gray-500 text-[10px] uppercase tracking-wider font-bold mb-1">Read Data</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=products</code>
                                            <span class="text-gray-600 text-xs">— List products (paginated)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=product&id={id}</code>
                                            <span class="text-gray-600 text-xs">— Get single product</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=categories</code>
                                            <span class="text-gray-600 text-xs">— List categories</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=products_by_category&category_id={id}</code>
                                            <span class="text-gray-600 text-xs">— Products by category</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=search&q={query}</code>
                                            <span class="text-gray-600 text-xs">— Search products</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=discounts</code>
                                            <span class="text-gray-600 text-xs">— Active discounts</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=site</code>
                                            <span class="text-gray-600 text-xs">— Store info</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=orders&email={email}</code>
                                            <span class="text-gray-600 text-xs">— Order history by email</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart&cart_id={id}</code>
                                            <span class="text-gray-600 text-xs">— Get cart contents</span>
                                        </div>

                                        <p class="text-gray-500 text-[10px] uppercase tracking-wider font-bold mt-3 mb-1">Cart & Checkout</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_create</code>
                                            <span class="text-gray-600 text-xs">— Create new cart session</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_add</code>
                                            <span class="text-gray-600 text-xs">— Add item to cart</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_update</code>
                                            <span class="text-gray-600 text-xs">— Update item quantity</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_remove</code>
                                            <span class="text-gray-600 text-xs">— Remove item from cart</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=checkout_create</code>
                                            <span class="text-gray-600 text-xs">— Create checkout session</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=checkout_complete</code>
                                            <span class="text-gray-600 text-xs">— Complete checkout with customer info</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=order</code>
                                            <span class="text-gray-600 text-xs">— Place a direct order</span>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-xs mt-3">Pass API key via <code class="text-gray-500">X-API-Key</code> header, <code class="text-gray-500">Authorization: Bearer {key}</code>, or <code class="text-gray-500">?api_key={key}</code></p>
                                </div>
                            </div>

                            <!-- API Keys Section -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">API Keys</h3>
                                        <p class="text-xs text-gray-500 mt-1">Generate and manage API keys for external applications</p>
                                    </div>
                                    <button onclick="document.getElementById('generateKeyModal').classList.remove('hidden')"
                                        class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2">
                                        <i class="bi bi-plus-lg"></i> Generate New Key
                                    </button>
                                </div>

                                <?php if (!empty($api_keys)): ?>
                                <!-- Existing API Keys Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="border-b border-gray-700">
                                            <tr>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Key Name</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">API Key</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Created</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Last Used</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <?php foreach ($api_keys as $key): ?>
                                            <tr class="<?php echo $key['active'] ? '' : 'opacity-50'; ?>">
                                                <td class="py-3 text-white text-sm"><?php echo htmlspecialchars($key['key_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="py-3">
                                                    <code class="bg-black/50 px-2 py-1 rounded text-xs font-mono text-gray-300"><?php echo htmlspecialchars(substr($key['api_key'], 0, 12), ENT_QUOTES, 'UTF-8'); ?>••••••••</code>
                                                </td>
                                                <td class="py-3">
                                                    <?php if ($key['active']): ?>
                                                        <span class="bg-green-500/20 text-green-400 text-xs px-2 py-0.5 rounded">Active</span>
                                                    <?php else: ?>
                                                        <span class="bg-red-500/20 text-red-400 text-xs px-2 py-0.5 rounded">Revoked</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 text-gray-400 text-xs"><?php echo htmlspecialchars($key['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="py-3 text-gray-400 text-xs"><?php echo $key['last_used'] ? htmlspecialchars($key['last_used'], ENT_QUOTES, 'UTF-8') : 'Never'; ?></td>
                                                <td class="py-3">
                                                    <div class="flex items-center gap-2">
                                                        <?php if ($key['active']): ?>
                                                        <form method="POST" style="display:inline" onsubmit="return confirm('Revoke this API key? It will no longer be able to access the API.')">
                                                            <input type="hidden" name="action" value="revoke_api_key">
                                                            <input type="hidden" name="key_id" value="<?php echo (int) $key['id']; ?>">
                                                            <button type="submit" class="text-amber-400 hover:text-amber-300 text-xs transition-colors" title="Revoke">
                                                                <i class="bi bi-slash-circle"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                        <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete this API key?')">
                                                            <input type="hidden" name="action" value="delete_api_key">
                                                            <input type="hidden" name="key_id" value="<?php echo (int) $key['id']; ?>">
                                                            <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition-colors" title="Delete">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <!-- No keys message -->
                                <div class="text-center py-8">
                                    <i class="bi bi-key text-4xl text-gray-600"></i>
                                    <p class="text-gray-500 text-sm mt-2">No API keys generated yet</p>
                                    <p class="text-gray-600 text-xs">Click "Generate New Key" to create your first API key</p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- JavaScript SDK Section -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">JavaScript SDK</h3>
                                        <p class="text-xs text-gray-500 mt-1">Drop-in storefront SDK for external websites — GitHub Pages, static sites, and more</p>
                                    </div>
                                </div>

                                <!-- SDK Script Tag -->
                                <div class="bg-purple-500/5 rounded-xl p-4 mb-4 border border-purple-500/20">
                                    <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">Include Script</span>
                                    <div class="flex items-center gap-3 mt-2 bg-black/40 rounded-lg px-4 py-3">
                                        <code id="sdkScriptTag" class="text-sm font-mono text-purple-300 break-all flex-1">&lt;script src="<?php echo htmlspecialchars($sdk_url, ENT_QUOTES, 'UTF-8'); ?>"&gt;&lt;/script&gt;</code>
                                        <button onclick="navigator.clipboard.writeText('<script src=\'<?php echo htmlspecialchars($sdk_url, ENT_QUOTES, 'UTF-8'); ?>\'><\/script>'); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i> Copy', 2000)"
                                            class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- SDK Quick Start -->
                                <div class="bg-black/60 rounded-xl p-5 border border-white/5 mb-4">
                                    <p class="text-gray-400 text-xs mb-3 font-mono">// Initialize the SDK</p>
                                    <pre class="text-sm font-mono text-emerald-400 whitespace-pre-wrap break-all">const store = new VMStore({
  storeId: '<?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?>',
  apiKey: 'YOUR_API_KEY'
});

// Inject default styles
store.ui.injectStyles();

// Render a product grid
store.ui.productGrid('#shop');

// Add cart badge to an element
store.ui.cartBadge('#cart-icon');

// Render interactive cart with checkout
store.ui.cartDrawer('#cart');</pre>
                                </div>

                                <!-- SDK API Reference -->
                                <div class="bg-white/5 rounded-xl p-4 mb-4">
                                    <h4 class="text-white font-bold text-sm mb-3"><i class="bi bi-code-slash"></i> SDK Methods</h4>
                                    <div class="space-y-2 text-xs font-mono">
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Products</span>
                                            <code class="text-gray-400">store.products.list({ page, limit }) / .get(id) / .search(query) / .byCategory(id)</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Cart</span>
                                            <code class="text-gray-400">store.cart.add(productId, qty) / .update(productId, qty) / .remove(productId) / .get() / .clear()</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Checkout</span>
                                            <code class="text-gray-400">store.checkout.redirect({ returnUrl }) / .create({ returnUrl }) / .complete(sessionId, customer)</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">UI</span>
                                            <code class="text-gray-400">store.ui.productGrid(el) / .productCard(el, product) / .cartBadge(el) / .cartDrawer(el) / .injectStyles()</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Events</span>
                                            <code class="text-gray-400">store.on('cart:updated' | 'cart:item-added' | 'cart:item-removed' | 'checkout:completed', callback)</code>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Raw API Quick Start -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <h3 class="text-lg font-bold text-white mb-4">Raw API (fetch)</h3>
                                <div class="bg-black/60 rounded-xl p-5 border border-white/5">
                                    <p class="text-gray-400 text-xs mb-3 font-mono">// Fetch products directly via the REST API</p>
                                    <pre class="text-sm font-mono text-emerald-400 whitespace-pre-wrap break-all">fetch('<?php echo htmlspecialchars($api_base_url, ENT_QUOTES, 'UTF-8'); ?>?state=products', {
  headers: { 'X-API-Key': 'YOUR_API_KEY' }
})
.then(res => res.json())
.then(data => console.log(data));</pre>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Generate Key Modal -->
                    <div id="generateKeyModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
                        <div class="bg-gray-900 border border-gray-700 rounded-2xl p-6 w-full max-w-md">
                            <h3 class="text-lg font-bold text-white mb-4">Generate New API Key</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="generate_api_key">
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-400 mb-2">Key Name</label>
                                    <input type="text" name="key_name" required placeholder="e.g. Production App, Mobile Client"
                                        class="w-full bg-black/50 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="flex items-center gap-3 justify-end">
                                    <button type="button" onclick="document.getElementById('generateKeyModal').classList.add('hidden')"
                                        class="bg-gray-700 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm transition-all">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2 rounded-lg text-sm font-bold transition-all">
                                        <i class="bi bi-key"></i> Generate Key
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php endif; ?>

                <?php if ($active_tab == 'console'):
                    // Load real store data for console
                    $console_store_url = __WEBSITE_DOMAIN__ ?? '';
                    $console_store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
                    $console_store_id = $console_store_record[0]['id'] ?? '';
                    $console_secret = get_setting($db_site, 'console_secret_key', '');
                    if (empty($console_secret)) {
                        $console_secret = 'vm_sec_' . bin2hex(random_bytes(12));
                        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('console_secret_key', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$console_secret, $console_secret]);
                    }
                ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Store Connection</h2>
                            <p class="text-sm text-gray-400 mt-2">Connect your desktop application to manage this store remotely.</p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- Connection Instructions -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-white mb-4">Connect Your Desktop Application</h3>
                                <p class="text-sm text-gray-400 mb-6">Use the credentials below to connect your downloaded store management app to this online store.</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Step 1 -->
                                    <div class="bg-white/5 rounded-xl p-5">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-8 h-8 bg-blue-500/20 rounded-full flex items-center justify-center">
                                                <span class="text-blue-400 font-bold text-sm">1</span>
                                            </div>
                                            <h4 class="text-white font-bold text-sm">Download Desktop App</h4>
                                        </div>
                                        <p class="text-gray-400 text-xs mb-4">Download the latest version of our desktop application for your operating system.</p>
                                        <div class="flex flex-wrap gap-3">
                                            <a href="#" class="flex items-center gap-2 bg-black/50 hover:bg-black/70 px-4 py-2 rounded-lg transition-colors">
                                                <i class="bi bi-windows text-blue-400"></i>
                                                <span class="text-white text-sm">Windows</span>
                                            </a>
                                            <a href="#" class="flex items-center gap-2 bg-black/50 hover:bg-black/70 px-4 py-2 rounded-lg transition-colors">
                                                <i class="bi bi-apple text-gray-400"></i>
                                                <span class="text-white text-sm">macOS</span>
                                            </a>
                                            <a href="#" class="flex items-center gap-2 bg-black/50 hover:bg-black/70 px-4 py-2 rounded-lg transition-colors">
                                                <i class="bi bi-ubuntu text-orange-400"></i>
                                                <span class="text-white text-sm">Linux</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="bg-white/5 rounded-xl p-5">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-8 h-8 bg-blue-500/20 rounded-full flex items-center justify-center">
                                                <span class="text-blue-400 font-bold text-sm">2</span>
                                            </div>
                                            <h4 class="text-white font-bold text-sm">Enter Credentials in App</h4>
                                        </div>
                                        <p class="text-gray-400 text-xs mb-4">Use these credentials when prompted by the desktop application.</p>
                                        <div class="space-y-3">
                                            <div class="bg-black/30 rounded-lg p-3">
                                                <span class="text-gray-500 text-xs uppercase tracking-wide">Store URL</span>
                                                <div class="flex items-center justify-between mt-1">
                                                    <code class="text-white font-mono text-sm break-all"><?= htmlspecialchars($console_store_url) ?></code>
                                                    <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($console_store_url) ?>')"
                                                        class="text-gray-400 hover:text-white text-xs ml-2">
                                                        <i class="bi bi-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="bg-black/30 rounded-lg p-3">
                                                <span class="text-gray-500 text-xs uppercase tracking-wide">Store ID</span>
                                                <div class="flex items-center justify-between mt-1">
                                                    <code class="text-white font-mono text-sm"><?= htmlspecialchars($console_store_id) ?></code>
                                                    <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($console_store_id) ?>')"
                                                        class="text-gray-400 hover:text-white text-xs ml-2">
                                                        <i class="bi bi-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Connection Token / Secret Key -->
                            <div class="bg-amber-500/10 rounded-xl p-5 mb-8 border border-amber-500/20">
                                <div class="flex items-start gap-3">
                                    <i class="bi bi-shield-lock-fill text-amber-400 text-xl"></i>
                                    <div class="flex-1">
                                        <h4 class="text-amber-400 font-bold text-sm uppercase tracking-wide">Connection Secret Key</h4>
                                        <p class="text-gray-400 text-xs mt-1 mb-4">This unique key authenticates your desktop app with this store. Keep it secure!</p>
                                        <div class="bg-black/50 rounded-lg p-3">
                                            <div class="flex items-center justify-between flex-wrap gap-3">
                                                <code class="text-white font-mono text-sm break-all" id="connection_secret"><?= htmlspecialchars($console_secret) ?></code>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" onclick="copySecret()"
                                                        class="bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg text-xs transition-colors">
                                                        <i class="bi bi-copy"></i> Copy
                                                    </button>
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Regenerating the secret key will disconnect all currently connected desktop apps. Continue?')">
                                                        <input type="hidden" name="action" value="save_console">
                                                        <input type="hidden" name="console[regenerate_secret]" value="1">
                                                        <button type="submit"
                                                            class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 px-3 py-1.5 rounded-lg text-xs transition-colors">
                                                            <i class="bi bi-arrow-repeat"></i> Regenerate
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-3">Regenerating the secret key will disconnect all currently connected desktop apps.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Troubleshooting & Help -->
                            <div class="bg-blue-500/5 rounded-xl p-5 border border-blue-500/20">
                                <div class="flex items-start gap-3">
                                    <i class="bi bi-question-circle-fill text-blue-400 text-xl"></i>
                                    <div class="flex-1">
                                        <h4 class="text-white font-bold text-sm mb-2">Troubleshooting</h4>
                                        <p class="text-gray-400 text-xs mb-3">Having trouble connecting? Try these
                                            solutions:</p>
                                        <ul class="text-xs text-gray-400 space-y-1 list-disc list-inside">
                                            <li>Ensure your desktop app is updated to the latest version</li>
                                            <li>Check that your firewall isn't blocking the connection</li>
                                            <li>Verify that the Store URL and Secret Key are entered correctly</li>
                                            <li>Try regenerating the connection secret key</li>
                                            <li>Contact support if issues persist</li>
                                        </ul>
                                        <div class="mt-4 flex flex-wrap gap-3">
                                            <a href="#"
                                                class="text-blue-400 hover:text-blue-300 text-xs transition-colors flex items-center gap-1">
                                                <i class="bi bi-file-text"></i> Documentation
                                            </a>
                                            <a href="#"
                                                class="text-blue-400 hover:text-blue-300 text-xs transition-colors flex items-center gap-1">
                                                <i class="bi bi-envelope"></i> Contact Support
                                            </a>
                                            <a href="#"
                                                class="text-blue-400 hover:text-blue-300 text-xs transition-colors flex items-center gap-1">
                                                <i class="bi bi-download"></i> Download App Again
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                    <script>
                        function copySecret() {
                            const secretElement = document.getElementById('connection_secret');
                            if (secretElement) {
                                navigator.clipboard.writeText(secretElement.textContent);
                                const btn = event.target.closest('button');
                                const originalText = btn.innerHTML;
                                btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
                                setTimeout(() => { btn.innerHTML = originalText; }, 2000);
                            }
                        }
                    </script>

                <?php endif; ?>

                <?php if ($active_tab == 'app'):
                    $discord_event_list = [
                        'new_order' => ['label' => 'New Order', 'desc' => 'When a customer places a new order'],
                        'payment_received' => ['label' => 'Payment Received', 'desc' => 'When an order payment is confirmed'],
                        'order_fulfilled' => ['label' => 'Order Fulfilled', 'desc' => 'When an order is shipped/delivered'],
                        'order_cancelled' => ['label' => 'Order Cancelled', 'desc' => 'When an order is cancelled'],
                        'new_review' => ['label' => 'New Review', 'desc' => 'When a customer leaves a product review'],
                        'low_stock' => ['label' => 'Low Stock Alert', 'desc' => 'When product stock falls below threshold'],
                        'new_customer' => ['label' => 'New Customer', 'desc' => 'When a new customer registers'],
                        'system_alert' => ['label' => 'System Alert', 'desc' => 'Critical system notifications'],
                    ];
                ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_discord">
                        <div class="v-card animate-slide-up">
                            <div class="v-card-header">
                                <h2 class="text-xl font-bold text-white">Plugins</h2>
                                <p class="text-sm text-gray-400 mt-2">Extend your store functionality with integrated plugins.</p>
                            </div>
                            <div class="v-card-body py-8 px-8">

                                <!-- Discord Plugin Card -->
                                <div class="bg-gradient-to-br from-[#5865F2]/10 to-[#7289DA]/5 rounded-xl border border-[#5865F2]/30 overflow-hidden">
                                    <div class="p-6 border-b border-[#5865F2]/20">
                                        <div class="flex items-center justify-between flex-wrap gap-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-14 h-14 bg-[#5865F2]/20 rounded-2xl flex items-center justify-center">
                                                    <i class="bi bi-discord text-3xl text-[#5865F2]"></i>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h3 class="text-xl font-bold text-white">Discord Integration</h3>
                                                        <?php if ($discord_enabled === '1'): ?>
                                                            <span class="bg-green-500/20 text-green-400 text-xs px-2 py-0.5 rounded-full">Active</span>
                                                        <?php else: ?>
                                                            <span class="bg-gray-500/20 text-gray-400 text-xs px-2 py-0.5 rounded-full">Inactive</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-gray-400 text-sm mt-1">Connect your Discord server for real-time store notifications</p>
                                                </div>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="hidden" name="discord[enabled]" value="0">
                                                <input type="checkbox" name="discord[enabled]" value="1" class="sr-only peer" id="discord_toggle" <?= $discord_enabled === '1' ? 'checked' : '' ?>>
                                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5865F2]"></div>
                                                <span class="ml-3 text-sm font-medium text-gray-300"><?= $discord_enabled === '1' ? 'Plugin Enabled' : 'Plugin Disabled' ?></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div id="discord_config" class="p-6" style="opacity:<?= $discord_enabled === '1' ? '1' : '0.5' ?>;pointer-events:<?= $discord_enabled === '1' ? 'auto' : 'none' ?>">
                                        <!-- Webhook URL -->
                                        <div class="mb-8">
                                            <label class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Discord Webhook URL</label>
                                            <input type="text" name="discord[webhook_url]" value="<?= htmlspecialchars($discord_webhook) ?>"
                                                placeholder="https://discord.com/api/webhooks/xxxxxxxxxx/xxxxxxxxxx"
                                                class="w-full bg-black/30 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-[#5865F2] font-mono">
                                            <p class="text-xs text-gray-500 mt-2">
                                                <i class="bi bi-question-circle"></i>
                                                Create a webhook in your Discord server: Server Settings &rarr; Integrations &rarr; Webhooks &rarr; New Webhook
                                            </p>
                                        </div>

                                        <!-- Notification Events -->
                                        <div class="mb-8">
                                            <h4 class="text-sm font-bold text-gray-300 mb-4 uppercase tracking-wide">Notification Events</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <?php foreach ($discord_event_list as $evt_key => $evt): ?>
                                                <label class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                    <input type="checkbox" name="discord_events[]" value="<?= $evt_key ?>" class="w-4 h-4 accent-[#5865F2]" <?= in_array($evt_key, $discord_events) ? 'checked' : '' ?>>
                                                    <div>
                                                        <span class="text-white text-sm"><?= htmlspecialchars($evt['label']) ?></span>
                                                        <p class="text-gray-500 text-xs"><?= htmlspecialchars($evt['desc']) ?></p>
                                                    </div>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Notification Format -->
                                        <div class="mb-8">
                                            <h4 class="text-sm font-bold text-gray-300 mb-4 uppercase tracking-wide">Notification Format</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Message Style</label>
                                                    <select name="discord[message_style]"
                                                        class="w-full bg-black/30 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-[#5865F2]">
                                                        <option value="embed" <?= $discord_style === 'embed' ? 'selected' : '' ?>>Rich Embed (Colorful & Detailed)</option>
                                                        <option value="simple" <?= $discord_style === 'simple' ? 'selected' : '' ?>>Simple Text Message</option>
                                                        <option value="compact" <?= $discord_style === 'compact' ? 'selected' : '' ?>>Compact Format</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Embed Color</label>
                                                    <div class="flex items-center gap-3">
                                                        <input type="color" name="discord[embed_color]" value="<?= htmlspecialchars($discord_color) ?>"
                                                            class="w-12 h-10 rounded-lg cursor-pointer bg-black/30 border border-gray-700">
                                                        <span class="text-white text-sm font-mono"><?= htmlspecialchars($discord_color) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Mention Settings -->
                                        <div class="mb-8">
                                            <h4 class="text-sm font-bold text-gray-300 mb-4 uppercase tracking-wide">Mention Settings</h4>
                                            <div class="bg-white/5 rounded-xl p-4 space-y-3">
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <input type="checkbox" name="discord_mentions[]" value="everyone" class="w-4 h-4 accent-[#5865F2]" <?= in_array('everyone', $discord_mentions) ? 'checked' : '' ?>>
                                                    <span class="text-white text-sm">@everyone for important orders</span>
                                                </label>
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <input type="checkbox" name="discord_mentions[]" value="here" class="w-4 h-4 accent-[#5865F2]" <?= in_array('here', $discord_mentions) ? 'checked' : '' ?>>
                                                    <span class="text-white text-sm">@here for low stock alerts</span>
                                                </label>
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <input type="checkbox" name="discord_mentions[]" value="role" class="w-4 h-4 accent-[#5865F2]" <?= in_array('role', $discord_mentions) ? 'checked' : '' ?>>
                                                    <span class="text-white text-sm">Mention specific role for new orders</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Advanced Settings -->
                                        <details class="mb-8">
                                            <summary class="cursor-pointer text-sm font-bold text-gray-400 hover:text-gray-300 transition-colors">
                                                <i class="bi bi-gear"></i> Advanced Settings
                                            </summary>
                                            <div class="mt-4 space-y-4 pl-4 border-l-2 border-gray-700">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Custom Webhook Name</label>
                                                    <input type="text" name="discord[bot_name]" value="<?= htmlspecialchars($discord_bot_name) ?>" placeholder="Store Notifications"
                                                        class="w-full bg-black/30 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-[#5865F2]">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-400 mb-2">Custom Avatar URL</label>
                                                    <input type="url" name="discord[avatar_url]" value="<?= htmlspecialchars($discord_avatar) ?>" placeholder="https://yourstore.com/logo.png"
                                                        class="w-full bg-black/30 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-[#5865F2]">
                                                </div>
                                            </div>
                                        </details>

                                        <!-- Save Button -->
                                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-800">
                                            <div class="text-xs text-gray-500">
                                                <i class="bi bi-shield-check"></i> Discord plugin v2.1.0
                                            </div>
                                            <button type="submit"
                                                class="bg-gradient-to-r from-[#5865F2] to-[#4752C4] hover:from-[#4752C4] hover:to-[#3B45A3] text-white px-8 py-2.5 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-[#5865F2]/30">
                                                <i class="bi bi-save"></i> Save Plugin Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- More Plugins -->
                                <div class="mt-8 text-center py-8 bg-white/5 rounded-xl border border-dashed border-gray-700">
                                    <i class="bi bi-puzzle text-4xl text-gray-600"></i>
                                    <p class="text-gray-500 text-sm mt-3">More plugins coming soon!</p>
                                    <p class="text-gray-600 text-xs mt-1">Check back later for additional integrations</p>
                                </div>

                            </div>
                        </div>
                    </form>

                    <script>
                        document.getElementById('discord_toggle')?.addEventListener('change', function (e) {
                            const configSection = document.getElementById('discord_config');
                            const label = e.target.nextElementSibling.nextElementSibling;
                            if (configSection) {
                                configSection.style.opacity = e.target.checked ? '1' : '0.5';
                                configSection.style.pointerEvents = e.target.checked ? 'auto' : 'none';
                            }
                            if (label) label.textContent = e.target.checked ? 'Plugin Enabled' : 'Plugin Disabled';
                        });
                    </script>

                <?php endif;
                 ?>



            </main>
        </div>
    </div>

</div>