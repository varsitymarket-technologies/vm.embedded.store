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
}

// --- 3. Load Current Configs ---
$email_current = load_encrypted_config($config_path, $config_key);
$site_name = website_data('name');
$site_domain = website_data('domain');
$site_theme = website_data('theme');

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

                <?php if ($active_tab == 'currency'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Store Currencies</h2>
                            <p class="text-sm text-gray-400 mt-2">Configure your store's default currency and available
                                payment options.
                            </p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- Default Currency Selection -->
                            <div class="mb-8">
                                <label class="block text-sm font-bold text-gray-300 mb-3 uppercase tracking-wide">Default
                                    Currency</label>
                                <select
                                    class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="USD" selected>🇺🇸 USD - US Dollar ($)</option>
                                    <option value="EUR">🇪🇺 EUR - Euro (€)</option>
                                    <option value="GBP">🇬🇧 GBP - British Pound (£)</option>
                                    <option value="CAD">🇨🇦 CAD - Canadian Dollar (C$)</option>
                                    <option value="AUD">🇦🇺 AUD - Australian Dollar (A$)</option>
                                    <option value="JPY">🇯🇵 JPY - Japanese Yen (¥)</option>
                                    <option value="CNY">🇨🇳 CNY - Chinese Yuan (¥)</option>
                                    <option value="INR">🇮🇳 INR - Indian Rupee (₹)</option>
                                    <option value="AED">🇦🇪 AED - Dirham (د.إ)</option>
                                    <option value="SAR">🇸🇦 SAR - Saudi Riyal (﷼)</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-2">This is the main currency displayed on your
                                    storefront.</p>
                            </div>

                            <!-- Accepted Currencies (Multi-select / Toggle List) -->
                            <div class="mb-8">
                                <label class="block text-sm font-bold text-gray-300 mb-3 uppercase tracking-wide">Accepted
                                    Currencies</label>
                                <p class="text-xs text-gray-500 mb-3">Customers can pay using any of these currencies
                                    (auto-converted).</p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <label
                                        class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-white/10 transition-colors">
                                        <input type="checkbox" class="w-4 h-4 accent-blue-500" checked>
                                        <span class="text-white text-sm">🇺🇸 USD ($)</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-white/10 transition-colors">
                                        <input type="checkbox" class="w-4 h-4 accent-blue-500" checked>
                                        <span class="text-white text-sm">🇪🇺 EUR (€)</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-white/10 transition-colors">
                                        <input type="checkbox" class="w-4 h-4 accent-blue-500">
                                        <span class="text-white text-sm">🇬🇧 GBP (£)</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-white/10 transition-colors">
                                        <input type="checkbox" class="w-4 h-4 accent-blue-500">
                                        <span class="text-white text-sm">🇨🇦 CAD (C$)</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-white/10 transition-colors">
                                        <input type="checkbox" class="w-4 h-4 accent-blue-500">
                                        <span class="text-white text-sm">🇦🇺 AUD (A$)</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-white/10 transition-colors">
                                        <input type="checkbox" class="w-4 h-4 accent-blue-500">
                                        <span class="text-white text-sm">🇯🇵 JPY (¥)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Currency Format Settings -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <label class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Symbol
                                        Position</label>
                                    <select
                                        class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                        <option value="left">Left ($100)</option>
                                        <option value="right">Right (100$)</option>
                                        <option value="left_space">Left with space ($ 100)</option>
                                        <option value="right_space">Right with space (100 $)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Decimal
                                        Places</label>
                                    <select
                                        class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                        <option value="0">0 (123)</option>
                                        <option value="2" selected>2 (123.45)</option>
                                        <option value="3">3 (123.456)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Thousand Separator & Decimal Separator -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <label
                                        class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Thousand
                                        Separator</label>
                                    <select
                                        class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                        <option value="," selected>Comma (1,234.56)</option>
                                        <option value=".">Period (1.234,56)</option>
                                        <option value="space">Space (1 234.56)</option>
                                        <option value="none">None (1234.56)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">Decimal
                                        Separator</label>
                                    <select
                                        class="w-full bg-white/5 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                                        <option value="." selected>Period (123.45)</option>
                                        <option value=",">Comma (123,45)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Auto-Conversion Toggle -->
                            <div class="bg-white/5 rounded-xl p-5 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <div>
                                        <h4 class="text-white font-bold text-sm uppercase tracking-wide">Automatic Currency
                                            Conversion</h4>
                                        <p class="text-gray-500 text-xs mt-1">Enable real-time exchange rate updates via API
                                        </p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div
                                            class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-300">Enabled</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div
                                class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-4 border-t border-gray-800">
                                <button onclick="alert('Currency settings saved!')"
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-3 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-blue-900/30">
                                    💾 Save Currency Settings
                                </button>
                            </div>

                        </div>
                    </div>

                <?php endif; ?>

                <?php if ($active_tab == 'payment'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Payment Methods</h2>
                            <p class="text-sm text-gray-400 mt-2">Configure how your customers can pay for their orders.</p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- Cash on Delivery Option -->
                            <div class="bg-white/5 rounded-xl p-5 mb-6">
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                                            <i class="bi bi-cash-stack text-2xl text-emerald-400"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-white font-bold text-base">Cash on Delivery</h4>
                                            <p class="text-gray-500 text-xs mt-1">Customers pay in cash when they receive
                                                their order</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" id="cod_toggle" checked>
                                        <div
                                            class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600">
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-300">Enabled</span>
                                    </label>
                                </div>

                                <!-- Additional COD Settings (shown when enabled) -->
                                <div id="cod_settings" class="mt-5 pt-4 border-t border-gray-700/50">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 mb-2">Minimum Order Amount
                                                (Optional)</label>
                                            <input type="number" step="0.01" placeholder="No minimum"
                                                class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 mb-2">Additional Fee</label>
                                            <input type="number" step="0.01" value="0.00"
                                                class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500">
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-3">⚠️ Cash on Delivery is only available for local
                                        deliveries</p>
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
                                            <p class="text-gray-500 text-xs mt-1">Accept Visa, Mastercard, and American
                                                Express via YOCO</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" id="yoco_toggle">
                                        <div
                                            class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600">
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-300">Disabled</span>
                                    </label>
                                </div>

                                <!-- YOCO Configuration (shown when enabled) -->
                                <div id="yoco_settings" class="mt-5 pt-4 border-t border-gray-700/50 hidden">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 mb-2">YOCO Secret
                                                Key</label>
                                            <input type="password" placeholder="sk_test_xxxxxxxxxxxxx"
                                                class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500 font-mono">
                                            <p class="text-xs text-gray-500 mt-1">Find this in your YOCO dashboard under
                                                Developers → API Keys</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 mb-2">YOCO Public
                                                Key</label>
                                            <input type="text" placeholder="pk_test_xxxxxxxxxxxxx"
                                                class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500 font-mono">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Payment
                                                    Mode</label>
                                                <select
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500">
                                                    <option value="test">🔧 Test Mode</option>
                                                    <option value="live">🚀 Live Mode</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Transaction Fee
                                                    (%)</label>
                                                <input type="number" step="0.1" value="2.9"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-purple-500">
                                            </div>
                                        </div>
                                        <button
                                            class="text-xs text-purple-400 hover:text-purple-300 transition-colors flex items-center gap-1">
                                            <i class="bi bi-question-circle"></i> Test YOCO Connection
                                        </button>
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
                                            <p class="text-gray-500 text-xs mt-1">Accept payments via PayPal wallet, credit
                                                cards, and more</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" id="paypal_toggle">
                                        <div
                                            class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-300">Disabled</span>
                                    </label>
                                </div>

                                <!-- PayPal Configuration (shown when enabled) -->
                                <div id="paypal_settings" class="mt-5 pt-4 border-t border-gray-700/50 hidden">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 mb-2">PayPal Client
                                                ID</label>
                                            <input type="text" placeholder="AYxXxxxxxx...xxxxx"
                                                class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 font-mono">
                                            <p class="text-xs text-gray-500 mt-1">Get from PayPal Developer Dashboard → Apps
                                                & Credentials</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-400 mb-2">PayPal Secret
                                                Key</label>
                                            <input type="password" placeholder="EJxxxxxx...xxxxx"
                                                class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 font-mono">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="block text-xs font-bold text-gray-400 mb-2">Environment</label>
                                                <select
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                                                    <option value="sandbox">🧪 Sandbox (Test)</option>
                                                    <option value="production">🌍 Production (Live)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Transaction Fee
                                                    (%)</label>
                                                <input type="number" step="0.1" value="3.4"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" id="paypal_credit_card" class="accent-blue-500">
                                            <label for="paypal_credit_card" class="text-xs text-gray-300">Allow guest
                                                checkout (credit/debit cards without PayPal account)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method Order (Drag & Drop style) -->
                            <div class="bg-white/5 rounded-xl p-5 mb-8">
                                <h4 class="text-white font-bold text-sm uppercase tracking-wide mb-3">Payment Method Order
                                </h4>
                                <p class="text-gray-500 text-xs mb-4">Drag to rearrange the order payment options appear at
                                    checkout</p>

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between bg-black/30 rounded-lg px-4 py-2.5 cursor-move">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-grip-vertical text-gray-500"></i>
                                            <i class="bi bi-cash-stack text-emerald-400"></i>
                                            <span class="text-white text-sm">Cash on Delivery</span>
                                        </div>
                                        <span class="text-xs text-gray-500">Enabled</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between bg-black/30 rounded-lg px-4 py-2.5 cursor-move opacity-50">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-grip-vertical text-gray-500"></i>
                                            <i class="bi bi-credit-card-2-front text-purple-400"></i>
                                            <span class="text-white text-sm">YOCO (Credit Card)</span>
                                        </div>
                                        <span class="text-xs text-gray-500">Disabled</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between bg-black/30 rounded-lg px-4 py-2.5 cursor-move opacity-50">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-grip-vertical text-gray-500"></i>
                                            <i class="bi bi-paypal text-blue-400"></i>
                                            <span class="text-white text-sm">PayPal</span>
                                        </div>
                                        <span class="text-xs text-gray-500">Disabled</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div
                                class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-800">
                                <div class="text-xs text-gray-500">
                                    <i class="bi bi-shield-check"></i> All payments are securely processed via PCI-compliant
                                    gateways
                                </div>
                                <button onclick="alert('Payment method settings saved!')"
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-3 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-blue-900/30">
                                    💳 Save Payment Settings
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- JavaScript to handle toggle visibility -->
                    <script>
                        document.getElementById('cod_toggle')?.addEventListener('change', function (e) {
                            const settings = document.getElementById('cod_settings');
                            if (settings) {
                                settings.style.opacity = e.target.checked ? '1' : '0.5';
                            }
                        });

                        document.getElementById('yoco_toggle')?.addEventListener('change', function (e) {
                            const settings = document.getElementById('yoco_settings');
                            const label = e.target.nextElementSibling.nextElementSibling;
                            if (settings) {
                                settings.classList.toggle('hidden', !e.target.checked);
                            }
                            if (label) label.textContent = e.target.checked ? 'Enabled' : 'Disabled';
                        });

                        document.getElementById('paypal_toggle')?.addEventListener('change', function (e) {
                            const settings = document.getElementById('paypal_settings');
                            const label = e.target.nextElementSibling.nextElementSibling;
                            if (settings) {
                                settings.classList.toggle('hidden', !e.target.checked);
                            }
                            if (label) label.textContent = e.target.checked ? 'Enabled' : 'Disabled';
                        });
                    </script>

                <?php endif; ?>

                <?php if ($active_tab == 'dev'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Developer Settings</h2>
                            <p class="text-sm text-gray-400 mt-2">API access and security credentials for external
                                integrations.</p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- API Access Section -->
                            <div class="mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">API Access</h3>
                                        <p class="text-xs text-gray-500 mt-1">RESTful API endpoints for store management and
                                            automation</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" id="api_toggle" checked>
                                        <div
                                            class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-300">Enabled</span>
                                    </label>
                                </div>

                                <!-- API Base URL -->
                                <div class="bg-blue-500/5 rounded-xl p-4 mb-5 border border-blue-500/20">
                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <div>
                                            <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">API
                                                Endpoint</span>
                                            <p class="text-white font-mono text-sm mt-1 break-all">
                                                https://api.yourstore.com/v1/</p>
                                        </div>
                                        <button onclick="navigator.clipboard.writeText('https://api.yourstore.com/v1/')"
                                            class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- API Documentation Link -->
                                <div class="bg-white/5 rounded-xl p-4 mb-6">
                                    <div class="flex items-center gap-3">
                                        <i class="bi bi-file-text-fill text-2xl text-blue-400"></i>
                                        <div class="flex-1">
                                            <h4 class="text-white font-bold text-sm">API Documentation</h4>
                                            <p class="text-gray-500 text-xs">Complete reference for endpoints,
                                                authentication, and webhooks</p>
                                        </div>
                                        <a href="#"
                                            class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors">
                                            Read Docs <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- API Keys Section -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">API Keys</h3>
                                        <p class="text-xs text-gray-500 mt-1">Generate and manage API keys for external
                                            applications</p>
                                    </div>
                                    <button onclick="alert('Generate new API key')"
                                        class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2">
                                        <i class="bi bi-plus-lg"></i> Generate New Key
                                    </button>
                                </div>

                                <!-- Existing API Keys Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="border-b border-gray-700">
                                            <tr>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                    Key Name</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                    API Key</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                    Created</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                    Last Used</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                    Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 text-white text-sm">Production App</td>
                                                <td class="py-3">
                                                    <code
                                                        class="bg-black/50 px-2 py-1 rounded text-xs font-mono text-gray-300">vm_live_••••••••••••••••</code>
                                                </td>
                                                <td class="py-3 text-gray-400 text-xs">2024-12-01</td>
                                                <td class="py-3 text-gray-400 text-xs">2 hours ago</td>
                                                <td class="py-3">
                                                    <div class="flex items-center gap-2">
                                                        <button onclick="alert('Revoke this API key?')"
                                                            class="text-red-400 hover:text-red-300 text-xs transition-colors">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                        <button
                                                            onclick="navigator.clipboard.writeText('vm_live_xxxxxxxxxxxx')"
                                                            class="text-gray-400 hover:text-white text-xs transition-colors">
                                                            <i class="bi bi-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 text-white text-sm">Development Client</td>
                                                <td class="py-3">
                                                    <code
                                                        class="bg-black/50 px-2 py-1 rounded text-xs font-mono text-gray-300">vm_test_••••••••••••••••</code>
                                                </td>
                                                <td class="py-3 text-gray-400 text-xs">2024-12-15</td>
                                                <td class="py-3 text-gray-400 text-xs">Never</td>
                                                <td class="py-3">
                                                    <div class="flex items-center gap-2">
                                                        <button onclick="alert('Revoke this API key?')"
                                                            class="text-red-400 hover:text-red-300 text-xs transition-colors">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                        <button
                                                            onclick="navigator.clipboard.writeText('vm_test_xxxxxxxxxxxx')"
                                                            class="text-gray-400 hover:text-white text-xs transition-colors">
                                                            <i class="bi bi-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- No keys message (alternative) -->
                                <div id="no_keys_message" class="text-center py-8 hidden">
                                    <i class="bi bi-key text-4xl text-gray-600"></i>
                                    <p class="text-gray-500 text-sm mt-2">No API keys generated yet</p>
                                    <p class="text-gray-600 text-xs">Click "Generate New Key" to create your first API key
                                    </p>
                                </div>
                            </div>

                            <!-- Security Keys Rollback Section -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Security Keys Rollback</h3>
                                        <p class="text-xs text-gray-500 mt-1">Manage and rollback your security credentials
                                        </p>
                                    </div>
                                </div>

                                <!-- Current Security Keys -->
                                <div class="bg-amber-500/10 rounded-xl p-5 mb-6 border border-amber-500/20">
                                    <div class="flex items-start gap-3">
                                        <i class="bi bi-exclamation-triangle-fill text-amber-400 text-xl"></i>
                                        <div class="flex-1">
                                            <h4 class="text-amber-400 font-bold text-sm uppercase tracking-wide">Active
                                                Security Keys</h4>
                                            <div class="mt-3 space-y-3">
                                                <div
                                                    class="flex items-center justify-between flex-wrap gap-2 bg-black/30 rounded-lg px-3 py-2">
                                                    <span class="text-gray-300 text-xs font-mono">Encryption Key
                                                        (Primary)</span>
                                                    <code
                                                        class="text-xs font-mono text-gray-400">aes_256_••••••••••••••••••••</code>
                                                    <button onclick="alert('Key rolled back to previous version')"
                                                        class="text-amber-400 hover:text-amber-300 text-xs transition-colors">
                                                        Rollback <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </div>
                                                <div
                                                    class="flex items-center justify-between flex-wrap gap-2 bg-black/30 rounded-lg px-3 py-2">
                                                    <span class="text-gray-300 text-xs font-mono">JWT Secret</span>
                                                    <code
                                                        class="text-xs font-mono text-gray-400">jwt_sec_••••••••••••••••••••</code>
                                                    <button onclick="alert('JWT secret rolled back')"
                                                        class="text-amber-400 hover:text-amber-300 text-xs transition-colors">
                                                        Rollback <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </div>
                                                <div
                                                    class="flex items-center justify-between flex-wrap gap-2 bg-black/30 rounded-lg px-3 py-2">
                                                    <span class="text-gray-300 text-xs font-mono">Webhook Secret</span>
                                                    <code
                                                        class="text-xs font-mono text-gray-400">wh_sec_••••••••••••••••••••</code>
                                                    <button onclick="alert('Webhook secret rolled back')"
                                                        class="text-amber-400 hover:text-amber-300 text-xs transition-colors">
                                                        Rollback <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-3">⚠️ Rolling back security keys will
                                                invalidate existing sessions</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Key Version History -->
                                <div class="mb-6">
                                    <h4 class="text-sm font-bold text-gray-300 mb-3 uppercase tracking-wide">Key Version
                                        History</h4>
                                    <div class="space-y-3">
                                        <div
                                            class="flex items-center justify-between flex-wrap gap-3 bg-white/5 rounded-lg px-4 py-3">
                                            <div>
                                                <span class="text-white text-sm font-medium">v2.1.0</span>
                                                <span class="text-gray-500 text-xs ml-2">Current</span>
                                            </div>
                                            <span class="text-gray-400 text-xs">Rotated on Dec 20, 2024</span>
                                            <button onclick="alert('Restore this version')"
                                                class="text-blue-400 hover:text-blue-300 text-xs transition-colors">
                                                Restore <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </div>
                                        <div
                                            class="flex items-center justify-between flex-wrap gap-3 bg-white/5 rounded-lg px-4 py-3 opacity-75">
                                            <div>
                                                <span class="text-white text-sm font-medium">v2.0.0</span>
                                            </div>
                                            <span class="text-gray-400 text-xs">Rotated on Nov 15, 2024</span>
                                            <button onclick="alert('Restore version v2.0.0')"
                                                class="text-blue-400 hover:text-blue-300 text-xs transition-colors">
                                                Restore <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </div>
                                        <div
                                            class="flex items-center justify-between flex-wrap gap-3 bg-white/5 rounded-lg px-4 py-3 opacity-75">
                                            <div>
                                                <span class="text-white text-sm font-medium">v1.5.0</span>
                                            </div>
                                            <span class="text-gray-400 text-xs">Rotated on Oct 01, 2024</span>
                                            <button onclick="alert('Restore version v1.5.0')"
                                                class="text-blue-400 hover:text-blue-300 text-xs transition-colors">
                                                Restore <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bulk Rollback Action -->
                                <div class="bg-red-500/5 rounded-xl p-5 border border-red-500/20">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-exclamation-octagon-fill text-red-400 text-xl"></i>
                                            <div>
                                                <h4 class="text-white font-bold text-sm">Emergency Rollback</h4>
                                                <p class="text-gray-500 text-xs">Rollback all security keys to previous
                                                    stable version</p>
                                            </div>
                                        </div>
                                        <button
                                            onclick="if(confirm('⚠️ WARNING: This will rollback ALL security keys and may disrupt active integrations. Continue?')) alert('Emergency rollback initiated')"
                                            class="bg-red-600/80 hover:bg-red-600 text-white px-5 py-2 rounded-lg text-xs font-bold transition-all">
                                            <i class="bi bi-shield-exclamation"></i> Emergency Rollback
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Webhooks Section -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Webhooks</h3>
                                        <p class="text-xs text-gray-500 mt-1">Configure endpoints for real-time event
                                            notifications</p>
                                    </div>
                                    <button onclick="alert('Add webhook endpoint')"
                                        class="bg-white/10 hover:bg-white/20 text-white px-5 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                        <i class="bi bi-plus-lg"></i> Add Webhook
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div class="bg-white/5 rounded-xl p-4">
                                        <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-white text-sm font-medium">Order Updates</span>
                                                <span
                                                    class="bg-green-500/20 text-green-400 text-xs px-2 py-0.5 rounded">Active</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button onclick="alert('Test webhook')"
                                                    class="text-gray-400 hover:text-white text-xs">
                                                    <i class="bi bi-send"></i> Test
                                                </button>
                                                <button onclick="alert('Delete webhook')"
                                                    class="text-red-400 hover:text-red-300 text-xs">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <code
                                            class="text-xs font-mono text-gray-400 break-all">https://your-app.com/webhooks/order-update</code>
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            <span class="text-xs bg-white/5 px-2 py-0.5 rounded">order.created</span>
                                            <span class="text-xs bg-white/5 px-2 py-0.5 rounded">order.paid</span>
                                            <span class="text-xs bg-white/5 px-2 py-0.5 rounded">order.fulfilled</span>
                                            <span class="text-xs bg-white/5 px-2 py-0.5 rounded">order.cancelled</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Save & Reset Buttons -->
                            <div
                                class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-800">
                                <div class="text-xs text-gray-500">
                                    <i class="bi bi-code-slash"></i> API Rate Limit: 1000 requests per minute
                                </div>
                                <div class="flex items-center gap-3">
                                    <button onclick="alert('Settings reset to default')"
                                        class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2.5 rounded-full text-sm font-medium transition-all">
                                        Reset to Default
                                    </button>
                                    <button onclick="alert('Developer settings saved!')"
                                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-2.5 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-blue-900/30">
                                        <i class="bi bi-save"></i> Save Settings
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- JavaScript for API toggle and key management -->
                    <script>
                        // API toggle handler
                        document.getElementById('api_toggle')?.addEventListener('change', function (e) {
                            const label = e.target.nextElementSibling.nextElementSibling;
                            if (label) label.textContent = e.target.checked ? 'Enabled' : 'Disabled';

                            // Show/hide API related sections
                            const apiSections = document.querySelectorAll('.api-dependent');
                            apiSections.forEach(section => {
                                section.style.opacity = e.target.checked ? '1' : '0.5';
                                section.style.pointerEvents = e.target.checked ? 'auto' : 'none';
                            });
                        });

                        // Optional: Show no keys message if table empty (demo)
                        const keyRows = document.querySelectorAll('tbody tr');
                        const noKeysMsg = document.getElementById('no_keys_message');
                        if (keyRows.length === 0 && noKeysMsg) {
                            noKeysMsg.classList.remove('hidden');
                        }
                    </script>

                <?php endif; ?>

                <?php if ($active_tab == 'console'): ?>
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
                            <p class="text-sm text-gray-400 mt-2">Connect your desktop application to manage this store
                                remotely.</p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- Connection Status -->
                            <div class="mb-8">
                                <div
                                    class="bg-gradient-to-r from-green-500/10 to-emerald-500/10 rounded-xl p-5 border border-green-500/20">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                            <div>
                                                <h3 class="text-green-400 font-bold text-sm uppercase tracking-wide">
                                                    Connection Active</h3>
                                                <p class="text-gray-300 text-sm mt-1">Your desktop app is connected to this
                                                    store</p>
                                            </div>
                                        </div>
                                        <button onclick="alert('Disconnect desktop app?')"
                                            class="bg-red-500/20 hover:bg-red-500/30 text-red-400 px-4 py-2 rounded-lg text-xs font-bold transition-colors">
                                            <i class="bi bi-plug"></i> Disconnect
                                        </button>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-green-500/20">
                                        <div class="flex items-center gap-4 text-xs">
                                            <span class="text-gray-400">Last sync:</span>
                                            <span class="text-gray-300">Just now</span>
                                            <span class="text-gray-400">Connected device:</span>
                                            <span class="text-gray-300">DESKTOP-ABC123 (Windows)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Connection Instructions -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-white mb-4">Connect Your Desktop Application</h3>
                                <p class="text-sm text-gray-400 mb-6">Use the credentials below to connect your downloaded
                                    store management app to this online store.</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Step 1 -->
                                    <div class="bg-white/5 rounded-xl p-5">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div
                                                class="w-8 h-8 bg-blue-500/20 rounded-full flex items-center justify-center">
                                                <span class="text-blue-400 font-bold text-sm">1</span>
                                            </div>
                                            <h4 class="text-white font-bold text-sm">Download Desktop App</h4>
                                        </div>
                                        <p class="text-gray-400 text-xs mb-4">Download the latest version of our desktop
                                            application for your operating system.</p>
                                        <div class="flex flex-wrap gap-3">
                                            <a href="#"
                                                class="flex items-center gap-2 bg-black/50 hover:bg-black/70 px-4 py-2 rounded-lg transition-colors">
                                                <i class="bi bi-windows text-blue-400"></i>
                                                <span class="text-white text-sm">Windows</span>
                                            </a>
                                            <a href="#"
                                                class="flex items-center gap-2 bg-black/50 hover:bg-black/70 px-4 py-2 rounded-lg transition-colors">
                                                <i class="bi bi-apple text-gray-400"></i>
                                                <span class="text-white text-sm">macOS</span>
                                            </a>
                                            <a href="#"
                                                class="flex items-center gap-2 bg-black/50 hover:bg-black/70 px-4 py-2 rounded-lg transition-colors">
                                                <i class="bi bi-ubuntu text-orange-400"></i>
                                                <span class="text-white text-sm">Linux</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="bg-white/5 rounded-xl p-5">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div
                                                class="w-8 h-8 bg-blue-500/20 rounded-full flex items-center justify-center">
                                                <span class="text-blue-400 font-bold text-sm">2</span>
                                            </div>
                                            <h4 class="text-white font-bold text-sm">Enter Credentials in App</h4>
                                        </div>
                                        <p class="text-gray-400 text-xs mb-4">Use these credentials when prompted by the
                                            desktop application.</p>
                                        <div class="space-y-3">
                                            <div class="bg-black/30 rounded-lg p-3">
                                                <span class="text-gray-500 text-xs uppercase tracking-wide">Store URL</span>
                                                <div class="flex items-center justify-between mt-1">
                                                    <code
                                                        class="text-white font-mono text-sm break-all">https://yourstore.varsitymarket.com</code>
                                                    <button
                                                        onclick="navigator.clipboard.writeText('https://yourstore.varsitymarket.com')"
                                                        class="text-gray-400 hover:text-white text-xs ml-2">
                                                        <i class="bi bi-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="bg-black/30 rounded-lg p-3">
                                                <span class="text-gray-500 text-xs uppercase tracking-wide">Store ID</span>
                                                <div class="flex items-center justify-between mt-1">
                                                    <code class="text-white font-mono text-sm">store_8f7g3h2j9k1l</code>
                                                    <button onclick="navigator.clipboard.writeText('store_8f7g3h2j9k1l')"
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
                                        <h4 class="text-amber-400 font-bold text-sm uppercase tracking-wide">Connection
                                            Secret Key</h4>
                                        <p class="text-gray-400 text-xs mt-1 mb-4">This unique key authenticates your
                                            desktop app with this store. Keep it secure!</p>

                                        <div class="bg-black/50 rounded-lg p-3">
                                            <div class="flex items-center justify-between flex-wrap gap-3">
                                                <code class="text-white font-mono text-sm break-all"
                                                    id="connection_secret">vm_sec_8f7g3h2j9k1l_4m5n6p7q8r9s</code>
                                                <div class="flex items-center gap-2">
                                                    <button onclick="copySecret()"
                                                        class="bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg text-xs transition-colors">
                                                        <i class="bi bi-copy"></i> Copy
                                                    </button>
                                                    <button onclick="regenerateSecret()"
                                                        class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 px-3 py-1.5 rounded-lg text-xs transition-colors">
                                                        <i class="bi bi-arrow-repeat"></i> Regenerate
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-3">
                                            ⚠️ Regenerating the secret key will disconnect all currently connected desktop
                                            apps.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Connection QR Code (Optional) -->
                            <div class="mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Quick Connect via QR Code</h3>
                                        <p class="text-xs text-gray-400 mt-1">Scan with your desktop app to connect
                                            instantly</p>
                                    </div>
                                    <button onclick="alert('QR code refreshed')"
                                        class="text-gray-400 hover:text-white text-sm transition-colors">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh
                                    </button>
                                </div>
                                <div class="flex justify-center">
                                    <div class="bg-white p-4 rounded-2xl inline-block">
                                        <div class="w-48 h-48 bg-gray-800 rounded-xl flex items-center justify-center">
                                            <!-- Simulated QR Code -->
                                            <div class="text-center">
                                                <i class="bi bi-qr-code-scan text-7xl text-gray-600"></i>
                                                <p class="text-xs text-gray-500 mt-2">QR Code Placeholder</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-center text-xs text-gray-500 mt-3">Open desktop app → Click "Scan QR" → Scan
                                    this code</p>
                            </div>

                            <!-- Connected Devices -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Connected Devices</h3>
                                        <p class="text-xs text-gray-400 mt-1">Devices currently connected to this store</p>
                                    </div>
                                    <button onclick="alert('Revoke all devices')"
                                        class="text-red-400 hover:text-red-300 text-sm transition-colors">
                                        <i class="bi bi-trash3"></i> Revoke All
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div class="bg-white/5 rounded-xl p-4">
                                        <div class="flex items-center justify-between flex-wrap gap-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                                    <i class="bi bi-laptop text-blue-400 text-lg"></i>
                                                </div>
                                                <div>
                                                    <h4 class="text-white font-bold text-sm">DESKTOP-ABC123</h4>
                                                    <p class="text-gray-500 text-xs">Windows 11 • Connected 2 hours ago</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded-full">Active</span>
                                                <button onclick="alert('Revoke this device')"
                                                    class="text-red-400 hover:text-red-300 text-sm ml-2">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-white/5 rounded-xl p-4 opacity-60">
                                        <div class="flex items-center justify-between flex-wrap gap-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-gray-500/20 rounded-lg flex items-center justify-center">
                                                    <i class="bi bi-phone text-gray-400 text-lg"></i>
                                                </div>
                                                <div>
                                                    <h4 class="text-white font-bold text-sm">iPhone - John's Phone</h4>
                                                    <p class="text-gray-500 text-xs">iOS 17 • Last seen yesterday</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="bg-gray-500/20 text-gray-400 text-xs px-2 py-1 rounded-full">Offline</span>
                                                <button onclick="alert('Revoke this device')"
                                                    class="text-red-400 hover:text-red-300 text-sm ml-2">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Connection Logs -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Recent Connection Activity</h3>
                                        <p class="text-xs text-gray-400 mt-1">Monitor desktop app connection history</p>
                                    </div>
                                    <button onclick="alert('View full logs')"
                                        class="text-blue-400 hover:text-blue-300 text-sm transition-colors">
                                        View All <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between flex-wrap gap-2 text-sm py-2 border-b border-gray-800">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-check-circle-fill text-green-500 text-xs"></i>
                                            <span class="text-gray-300">Desktop app connected</span>
                                        </div>
                                        <span class="text-gray-500 text-xs">Today, 10:32 AM</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between flex-wrap gap-2 text-sm py-2 border-b border-gray-800">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-arrow-repeat text-blue-400 text-xs"></i>
                                            <span class="text-gray-300">Data sync completed</span>
                                        </div>
                                        <span class="text-gray-500 text-xs">Today, 10:30 AM</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between flex-wrap gap-2 text-sm py-2 border-b border-gray-800">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-key-fill text-amber-400 text-xs"></i>
                                            <span class="text-gray-300">New connection token generated</span>
                                        </div>
                                        <span class="text-gray-500 text-xs">Yesterday, 3:15 PM</span>
                                    </div>
                                    <div class="flex items-center justify-between flex-wrap gap-2 text-sm py-2">
                                        <div class="flex items-center gap-3">
                                            <i class="bi bi-laptop text-gray-500 text-xs"></i>
                                            <span class="text-gray-300">Desktop app (DESKTOP-XYZ789) disconnected</span>
                                        </div>
                                        <span class="text-gray-500 text-xs">Yesterday, 2:00 PM</span>
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

                            <!-- Save Button -->
                            <div
                                class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 mt-4 border-t border-gray-800">
                                <div class="text-xs text-gray-500">
                                    <i class="bi bi-shield-check"></i> All connections are encrypted via TLS 1.3
                                </div>
                                <button onclick="alert('Store connection settings saved!')"
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-3 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-blue-900/30">
                                    <i class="bi bi-save"></i> Save Connection Settings
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- JavaScript for secret key management -->
                    <script>
                        function copySecret() {
                            const secretElement = document.getElementById('connection_secret');
                            if (secretElement) {
                                navigator.clipboard.writeText(secretElement.textContent);
                                // Optional: Show temporary notification
                                const btn = event.target.closest('button');
                                const originalText = btn.innerHTML;
                                btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
                                setTimeout(() => {
                                    btn.innerHTML = originalText;
                                }, 2000);
                            }
                        }

                        function regenerateSecret() {
                            if (confirm('⚠️ Regenerating the secret key will disconnect all currently connected desktop apps. Continue?')) {
                                // Simulate regeneration
                                const newSecret = 'vm_sec_' + Math.random().toString(36).substring(2, 15) + '_' + Math.random().toString(36).substring(2, 10);
                                document.getElementById('connection_secret').textContent = newSecret;
                                alert('New connection secret key generated successfully!');
                            }
                        }
                    </script>

                <?php endif; ?>

                <?php if ($active_tab == 'app'): ?>
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Plugins</h2>
                            <p class="text-sm text-gray-400 mt-2">Extend your store functionality with integrated plugins.
                            </p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- Discord Plugin Card -->
                            <div
                                class="bg-gradient-to-br from-[#5865F2]/10 to-[#7289DA]/5 rounded-xl border border-[#5865F2]/30 overflow-hidden">

                                <!-- Plugin Header -->
                                <div class="p-6 border-b border-[#5865F2]/20">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-14 h-14 bg-[#5865F2]/20 rounded-2xl flex items-center justify-center">
                                                <i class="bi bi-discord text-3xl text-[#5865F2]"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <h3 class="text-xl font-bold text-white">Discord Integration</h3>
                                                    <span
                                                        class="bg-green-500/20 text-green-400 text-xs px-2 py-0.5 rounded-full">Active</span>
                                                </div>
                                                <p class="text-gray-400 text-sm mt-1">Connect your Discord server for
                                                    real-time store notifications</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer" id="discord_toggle" checked>
                                            <div
                                                class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5865F2]">
                                            </div>
                                            <span class="ml-3 text-sm font-medium text-gray-300">Plugin Enabled</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Plugin Configuration Body -->
                                <div id="discord_config" class="p-6">

                                    <!-- Webhook URL Configuration -->
                                    <div class="mb-8">
                                        <label class="block text-sm font-bold text-gray-300 mb-2 uppercase tracking-wide">
                                            Discord Webhook URL
                                        </label>
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <input type="text"
                                                placeholder="https://discord.com/api/webhooks/xxxxxxxxxx/xxxxxxxxxx"
                                                value="https://discord.com/api/webhooks/1234567890/ABCDEFGHIJKLMNOPQRSTUVWXYZ"
                                                class="flex-1 bg-black/30 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-[#5865F2] font-mono">
                                            <button onclick="alert('Test webhook connection')"
                                                class="bg-[#5865F2] hover:bg-[#4752C4] text-white px-6 py-3 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                <i class="bi bi-send"></i> Test Connection
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="bi bi-question-circle"></i>
                                            Create a webhook in your Discord server: Server Settings → Integrations →
                                            Webhooks → New Webhook
                                        </p>
                                    </div>

                                    <!-- Notification Events -->
                                    <div class="mb-8">
                                        <h4 class="text-sm font-bold text-gray-300 mb-4 uppercase tracking-wide">
                                            Notification Events</h4>
                                        <p class="text-xs text-gray-500 mb-4">Choose which store events trigger Discord
                                            notifications</p>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]" checked>
                                                <div>
                                                    <span class="text-white text-sm">🛒 New Order</span>
                                                    <p class="text-gray-500 text-xs">When a customer places a new order</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]" checked>
                                                <div>
                                                    <span class="text-white text-sm">💰 Payment Received</span>
                                                    <p class="text-gray-500 text-xs">When an order payment is confirmed</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]" checked>
                                                <div>
                                                    <span class="text-white text-sm">📦 Order Fulfilled</span>
                                                    <p class="text-gray-500 text-xs">When an order is shipped/delivered</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]">
                                                <div>
                                                    <span class="text-white text-sm">❌ Order Cancelled</span>
                                                    <p class="text-gray-500 text-xs">When an order is cancelled by
                                                        customer/admin</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]">
                                                <div>
                                                    <span class="text-white text-sm">⭐ New Review</span>
                                                    <p class="text-gray-500 text-xs">When a customer leaves a product review
                                                    </p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]" checked>
                                                <div>
                                                    <span class="text-white text-sm">📉 Low Stock Alert</span>
                                                    <p class="text-gray-500 text-xs">When product stock falls below
                                                        threshold</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]">
                                                <div>
                                                    <span class="text-white text-sm">👤 New Customer</span>
                                                    <p class="text-gray-500 text-xs">When a new customer registers</p>
                                                </div>
                                            </label>

                                            <label
                                                class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 cursor-pointer hover:bg-white/10 transition-colors">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]">
                                                <div>
                                                    <span class="text-white text-sm">🔔 System Alert</span>
                                                    <p class="text-gray-500 text-xs">Critical system notifications</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Notification Format -->
                                    <div class="mb-8">
                                        <h4 class="text-sm font-bold text-gray-300 mb-4 uppercase tracking-wide">
                                            Notification Format</h4>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <!-- Embed Style -->
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Message
                                                    Style</label>
                                                <select
                                                    class="w-full bg-black/30 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-[#5865F2]">
                                                    <option value="embed" selected>Rich Embed (Colorful & Detailed)</option>
                                                    <option value="simple">Simple Text Message</option>
                                                    <option value="compact">Compact Format</option>
                                                </select>
                                            </div>

                                            <!-- Embed Color -->
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Embed
                                                    Color</label>
                                                <div class="flex items-center gap-3">
                                                    <input type="color" value="#5865F2"
                                                        class="w-12 h-10 rounded-lg cursor-pointer bg-black/30 border border-gray-700">
                                                    <input type="text" value="#5865F2"
                                                        class="flex-1 bg-black/30 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm font-mono focus:outline-none focus:border-[#5865F2]">
                                                    <span class="text-xs text-gray-500">Discord Blurple</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mention Settings -->
                                    <div class="mb-8">
                                        <h4 class="text-sm font-bold text-gray-300 mb-4 uppercase tracking-wide">Mention
                                            Settings</h4>

                                        <div class="bg-white/5 rounded-xl p-4 space-y-3">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]">
                                                <span class="text-white text-sm">@everyone for important orders</span>
                                            </label>
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]">
                                                <span class="text-white text-sm">@here for low stock alerts</span>
                                            </label>
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]" checked>
                                                <span class="text-white text-sm">Mention specific role for new orders</span>
                                            </label>

                                            <div class="pl-7">
                                                <select
                                                    class="w-full md:w-64 bg-black/30 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-[#5865F2]">
                                                    <option value="">Select role to mention</option>
                                                    <option value="staff">@Store Staff</option>
                                                    <option value="admin">@Admin</option>
                                                    <option value="manager">@Manager</option>
                                                    <option value="owner">@Owner</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Test Notification Section -->
                                    <div class="bg-[#5865F2]/5 rounded-xl p-5 mb-8 border border-[#5865F2]/20">
                                        <div class="flex items-center justify-between flex-wrap gap-4">
                                            <div class="flex items-center gap-3">
                                                <i class="bi bi-bell-fill text-[#5865F2] text-xl"></i>
                                                <div>
                                                    <h4 class="text-white font-bold text-sm">Send Test Notification</h4>
                                                    <p class="text-gray-500 text-xs">Verify your Discord integration is
                                                        working correctly</p>
                                                </div>
                                            </div>
                                            <button
                                                onclick="alert('Test notification sent to Discord! Check your channel.')"
                                                class="bg-[#5865F2] hover:bg-[#4752C4] text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                                                <i class="bi bi-send"></i> Send Test Message
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Advanced Settings -->
                                    <details class="mb-8">
                                        <summary
                                            class="cursor-pointer text-sm font-bold text-gray-400 hover:text-gray-300 transition-colors">
                                            <i class="bi bi-gear"></i> Advanced Settings
                                        </summary>
                                        <div class="mt-4 space-y-4 pl-4 border-l-2 border-gray-700">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Custom Webhook
                                                    Name</label>
                                                <input type="text" placeholder="Store Notifications"
                                                    value="Varsity Market Store"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-[#5865F2]">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-400 mb-2">Custom Avatar
                                                    URL</label>
                                                <input type="url" placeholder="https://yourstore.com/logo.png"
                                                    class="w-full bg-black/30 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-[#5865F2]">
                                            </div>
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]">
                                                <span class="text-white text-sm">Include store performance metrics in daily
                                                    summary</span>
                                            </label>
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" class="w-4 h-4 accent-[#5865F2]" checked>
                                                <span class="text-white text-sm">Show customer details in order
                                                    notifications</span>
                                            </label>
                                        </div>
                                    </details>

                                    <!-- Save Button -->
                                    <div
                                        class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-800">
                                        <div class="text-xs text-gray-500">
                                            <i class="bi bi-shield-check"></i> Discord plugin v2.1.0 | Last synced: Just now
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button
                                                onclick="if(confirm('Reset Discord plugin to default settings?')) alert('Discord settings reset!')"
                                                class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2.5 rounded-full text-sm font-medium transition-all">
                                                Reset to Default
                                            </button>
                                            <button onclick="alert('Discord plugin settings saved!')"
                                                class="bg-gradient-to-r from-[#5865F2] to-[#4752C4] hover:from-[#4752C4] hover:to-[#3B45A3] text-white px-8 py-2.5 rounded-full font-black text-sm transition-all transform hover:scale-105 shadow-xl shadow-[#5865F2]/30">
                                                <i class="bi bi-save"></i> Save Plugin Settings
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- No Other Plugins Installed Message -->
                            <div class="mt-8 text-center py-8 bg-white/5 rounded-xl border border-dashed border-gray-700">
                                <i class="bi bi-puzzle text-4xl text-gray-600"></i>
                                <p class="text-gray-500 text-sm mt-3">More plugins coming soon!</p>
                                <p class="text-gray-600 text-xs mt-1">Check back later for additional integrations</p>
                            </div>

                        </div>
                    </div>

                    <!-- JavaScript for Discord plugin toggle -->
                    <script>
                        document.getElementById('discord_toggle')?.addEventListener('change', function (e) {
                            const configSection = document.getElementById('discord_config');
                            const label = e.target.nextElementSibling.nextElementSibling;

                            if (configSection) {
                                if (e.target.checked) {
                                    configSection.style.opacity = '1';
                                    configSection.style.pointerEvents = 'auto';
                                    if (label) label.textContent = 'Plugin Enabled';
                                } else {
                                    configSection.style.opacity = '0.5';
                                    configSection.style.pointerEvents = 'none';
                                    if (label) label.textContent = 'Plugin Disabled';
                                }
                            }
                        });
                    </script>

                <?php endif; ?>



            </main>
        </div>
    </div>

</div>