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

            </main>
        </div>
    </div>

</div>