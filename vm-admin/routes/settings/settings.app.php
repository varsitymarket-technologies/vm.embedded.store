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

<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<form method="POST">
    <input type="hidden" name="action" value="save_discord">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h2 class="text-lg font-bold text-white">App Extensions</h2>
            <p class="text-zinc-400 text-sm mt-1">Extend your store with integrated plugins</p>
        </div>
        <div class="p-5 space-y-6">

            <!-- Discord Plugin -->
            <div class="bg-zinc-800 rounded-xl overflow-hidden border border-zinc-700">
                <div class="p-4 border-b border-zinc-700">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                                <i class="bi bi-discord text-indigo-400 text-lg"></i>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-white font-semibold text-sm">Discord Integration</h3>
                                    <?php if ($discord_enabled === '1'): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">
                                            <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-zinc-700 text-zinc-400">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-zinc-500 text-xs mt-0.5">Real-time store notifications to your Discord server</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="discord[enabled]" value="0">
                            <input type="checkbox" name="discord[enabled]" value="1" class="sr-only peer" id="discord_toggle" <?= $discord_enabled === '1' ? 'checked' : '' ?>>
                            <div class="w-11 h-6 bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <div id="discord_config" class="p-4 space-y-5" style="opacity:<?= $discord_enabled === '1' ? '1' : '0.5' ?>;pointer-events:<?= $discord_enabled === '1' ? 'auto' : 'none' ?>">

                    <!-- Webhook URL -->
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Discord Webhook URL</label>
                        <input type="text" name="discord[webhook_url]" value="<?= htmlspecialchars($discord_webhook) ?>"
                            placeholder="https://discord.com/api/webhooks/xxxxxxxxxx/xxxxxxxxxx"
                            class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors font-mono">
                        <p class="text-zinc-500 text-xs mt-1"><i class="bi bi-info-circle"></i> Server Settings &rarr; Integrations &rarr; Webhooks &rarr; New Webhook</p>
                    </div>

                    <!-- Events -->
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-2">Notification Events</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <?php foreach ($discord_event_list as $evt_key => $evt): ?>
                            <label class="flex items-center gap-3 bg-zinc-900 rounded-lg px-3 py-2.5 cursor-pointer hover:bg-zinc-900/80 transition-colors">
                                <input type="checkbox" name="discord_events[]" value="<?= $evt_key ?>" class="w-4 h-4 accent-indigo-500 rounded" <?= in_array($evt_key, $discord_events) ? 'checked' : '' ?>>
                                <div>
                                    <span class="text-white text-xs font-medium"><?= htmlspecialchars($evt['label']) ?></span>
                                    <p class="text-zinc-500 text-[10px]"><?= htmlspecialchars($evt['desc']) ?></p>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Format -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Message Style</label>
                            <select name="discord[message_style]"
                                class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                                <option value="embed" <?= $discord_style === 'embed' ? 'selected' : '' ?>>Rich Embed</option>
                                <option value="simple" <?= $discord_style === 'simple' ? 'selected' : '' ?>>Simple Text</option>
                                <option value="compact" <?= $discord_style === 'compact' ? 'selected' : '' ?>>Compact</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Embed Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="discord[embed_color]" value="<?= htmlspecialchars($discord_color) ?>"
                                    class="w-10 h-8 rounded cursor-pointer bg-zinc-900 border border-zinc-700">
                                <span class="text-white text-xs font-mono"><?= htmlspecialchars($discord_color) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Mentions -->
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-2">Mention Settings</label>
                        <div class="bg-zinc-900 rounded-lg p-3 space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="discord_mentions[]" value="everyone" class="w-4 h-4 accent-indigo-500 rounded" <?= in_array('everyone', $discord_mentions) ? 'checked' : '' ?>>
                                <span class="text-white text-xs">@everyone for important orders</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="discord_mentions[]" value="here" class="w-4 h-4 accent-indigo-500 rounded" <?= in_array('here', $discord_mentions) ? 'checked' : '' ?>>
                                <span class="text-white text-xs">@here for low stock alerts</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="discord_mentions[]" value="role" class="w-4 h-4 accent-indigo-500 rounded" <?= in_array('role', $discord_mentions) ? 'checked' : '' ?>>
                                <span class="text-white text-xs">Mention specific role for new orders</span>
                            </label>
                        </div>
                    </div>

                    <!-- Advanced -->
                    <details>
                        <summary class="cursor-pointer text-zinc-400 hover:text-white text-xs font-medium transition-colors">
                            <i class="bi bi-gear"></i> Advanced Settings
                        </summary>
                        <div class="mt-3 space-y-3 pl-4 border-l-2 border-zinc-700">
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Custom Webhook Name</label>
                                <input type="text" name="discord[bot_name]" value="<?= htmlspecialchars($discord_bot_name) ?>" placeholder="Store Notifications"
                                    class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                            </div>
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Custom Avatar URL</label>
                                <input type="url" name="discord[avatar_url]" value="<?= htmlspecialchars($discord_avatar) ?>" placeholder="https://yourstore.com/logo.png"
                                    class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors">
                            </div>
                        </div>
                    </details>

                </div>
            </div>

            <!-- More Plugins -->
            <div class="flex flex-col items-center justify-center py-10 bg-zinc-800 rounded-xl border border-dashed border-zinc-700">
                <i class="bi bi-puzzle text-3xl text-zinc-600 mb-2"></i>
                <p class="text-zinc-500 text-sm">More plugins coming soon</p>
                <p class="text-zinc-600 text-xs mt-0.5">Check back later for additional integrations</p>
            </div>

        </div>
        <div class="px-5 py-4 border-t border-zinc-800 flex justify-end">
            <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                Save Plugin Settings
            </button>
        </div>
    </div>
</form>

<script>
document.getElementById('discord_toggle')?.addEventListener('change', function(e) {
    const cfg = document.getElementById('discord_config');
    if (cfg) {
        cfg.style.opacity = e.target.checked ? '1' : '0.5';
        cfg.style.pointerEvents = e.target.checked ? 'auto' : 'none';
    }
});
</script>
