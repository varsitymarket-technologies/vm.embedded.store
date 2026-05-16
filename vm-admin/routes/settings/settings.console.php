<?php

                    // Load real store data for console
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


