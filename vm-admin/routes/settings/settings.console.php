<?php
$console_store_url = __WEBSITE_DOMAIN__ ?? '';
$console_store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
if (empty($console_store_record) && !empty($domain)) {
    $console_store_record = $db_engine->query("SELECT * FROM sys_websites WHERE domain = ? LIMIT 1", [$domain]);
}
$console_store_id = $console_store_record[0]['id'] ?? '';
$console_secret = get_setting($db_site, 'console_secret_key', '');
if (empty($console_secret)) {
    $console_secret = 'vm_sec_' . bin2hex(random_bytes(12));
    if ($db_site !== null) {
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('console_secret_key', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$console_secret, $console_secret]);
    }
}
?>

<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-zinc-800">
        <h2 class="text-lg font-bold text-white">Mobile App Console</h2>
        <p class="text-zinc-400 text-sm mt-1">Connect your desktop or mobile application to manage this store remotely</p>
    </div>
    <div class="p-5 space-y-6">

        <!-- Connection Steps -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Step 1 -->
            <div class="bg-zinc-800 rounded-xl p-4">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-7 h-7 rounded-full bg-violet-600 text-white flex items-center justify-center text-xs font-bold">1</span>
                    <h4 class="text-white font-medium text-sm">Download App</h4>
                </div>
                <p class="text-zinc-500 text-xs mb-3">Download the latest version for your operating system.</p>
                <div class="flex flex-wrap gap-2">
                    <a href="#" class="flex items-center gap-2 bg-zinc-900 hover:bg-zinc-700 px-3 py-1.5 rounded-lg transition-colors text-xs text-white">
                        <i class="bi bi-windows text-sky-400"></i> Windows
                    </a>
                    <a href="#" class="flex items-center gap-2 bg-zinc-900 hover:bg-zinc-700 px-3 py-1.5 rounded-lg transition-colors text-xs text-white">
                        <i class="bi bi-apple text-zinc-400"></i> macOS
                    </a>
                    <a href="#" class="flex items-center gap-2 bg-zinc-900 hover:bg-zinc-700 px-3 py-1.5 rounded-lg transition-colors text-xs text-white">
                        <i class="bi bi-ubuntu text-amber-400"></i> Linux
                    </a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-zinc-800 rounded-xl p-4">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-7 h-7 rounded-full bg-violet-600 text-white flex items-center justify-center text-xs font-bold">2</span>
                    <h4 class="text-white font-medium text-sm">Enter Credentials</h4>
                </div>
                <p class="text-zinc-500 text-xs mb-3">Use these credentials when prompted by the app.</p>
                <div class="space-y-2">
                    <div class="bg-zinc-900 rounded-lg px-3 py-2">
                        <span class="text-zinc-500 text-[10px] uppercase tracking-wider">Store URL</span>
                        <div class="flex items-center justify-between mt-0.5">
                            <code class="text-white font-mono text-xs break-all"><?= htmlspecialchars($console_store_url) ?></code>
                            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($console_store_url) ?>')" class="text-zinc-500 hover:text-white text-xs ml-2"><i class="bi bi-copy"></i></button>
                        </div>
                    </div>
                    <div class="bg-zinc-900 rounded-lg px-3 py-2">
                        <span class="text-zinc-500 text-[10px] uppercase tracking-wider">Store ID</span>
                        <div class="flex items-center justify-between mt-0.5">
                            <code class="text-white font-mono text-xs"><?= htmlspecialchars($console_store_id) ?></code>
                            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($console_store_id) ?>')" class="text-zinc-500 hover:text-white text-xs ml-2"><i class="bi bi-copy"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secret Key -->
        <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="bi bi-shield-lock text-amber-400 mt-0.5"></i>
                <div class="flex-1">
                    <h4 class="text-amber-400 font-medium text-sm">Connection Secret Key</h4>
                    <p class="text-zinc-500 text-xs mt-1 mb-3">This key authenticates your app with this store. Keep it secure.</p>
                    <div class="bg-zinc-900 rounded-lg px-3 py-2">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <code class="text-white font-mono text-sm break-all" id="connection_secret"><?= htmlspecialchars($console_secret) ?></code>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="copySecret()"
                                    class="bg-zinc-800 hover:bg-zinc-700 text-white px-3 py-1 rounded-lg text-xs transition-colors">
                                    <i class="bi bi-copy"></i> Copy
                                </button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Regenerating will disconnect all connected apps. Continue?')">
                                    <input type="hidden" name="action" value="save_console">
                                    <input type="hidden" name="console[regenerate_secret]" value="1">
                                    <button type="submit" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 px-3 py-1 rounded-lg text-xs transition-colors">
                                        <i class="bi bi-arrow-repeat"></i> Regenerate
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="bg-zinc-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="bi bi-question-circle text-sky-400 mt-0.5"></i>
                <div>
                    <h4 class="text-white font-medium text-sm mb-2">Troubleshooting</h4>
                    <ul class="text-xs text-zinc-400 space-y-1 list-disc list-inside">
                        <li>Ensure your app is updated to the latest version</li>
                        <li>Check that your firewall isn't blocking the connection</li>
                        <li>Verify Store URL and Secret Key are entered correctly</li>
                        <li>Try regenerating the connection secret key</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function copySecret() {
    const el = document.getElementById('connection_secret');
    if (el) {
        navigator.clipboard.writeText(el.textContent);
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    }
}
</script>
