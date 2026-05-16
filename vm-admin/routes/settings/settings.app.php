<?php

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


