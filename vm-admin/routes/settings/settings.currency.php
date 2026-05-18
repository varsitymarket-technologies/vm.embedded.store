<?php
$currencies = [
    'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R'],
    'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
    'EUR' => ['name' => 'Euro', 'symbol' => "\u{20AC}"],
    'GBP' => ['name' => 'British Pound', 'symbol' => "\u{00A3}"],
    'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$'],
    'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
    'JPY' => ['name' => 'Japanese Yen', 'symbol' => "\u{00A5}"],
    'CNY' => ['name' => 'Chinese Yuan', 'symbol' => "\u{00A5}"],
    'INR' => ['name' => 'Indian Rupee', 'symbol' => "\u{20B9}"],
    'NGN' => ['name' => 'Nigerian Naira', 'symbol' => "\u{20A6}"],
    'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KSh'],
    'BWP' => ['name' => 'Botswana Pula', 'symbol' => 'P'],
];
?>

<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<form method="POST">
    <input type="hidden" name="action" value="save_currency">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h2 class="text-lg font-bold text-white">Store Currencies</h2>
            <p class="text-zinc-400 text-sm mt-1">Configure your store's default currency and formatting options</p>
        </div>
        <div class="p-5 space-y-6">

            <!-- Default Currency -->
            <div>
                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Default Currency</label>
                <select name="currency[default_currency]"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                    <?php foreach ($currencies as $code => $c): ?>
                        <option value="<?= $code ?>" <?= $cur_default === $code ? 'selected' : '' ?>><?= $code ?> - <?= htmlspecialchars($c['name']) ?> (<?= $c['symbol'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="text-zinc-500 text-xs mt-1.5">This is the main currency displayed on your storefront.</p>
            </div>

            <!-- Accepted Currencies -->
            <div>
                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Accepted Currencies</label>
                <p class="text-zinc-500 text-xs mb-3">Customers can pay using any of these currencies (auto-converted).</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    <?php foreach ($currencies as $code => $c): ?>
                    <label class="flex items-center gap-3 bg-zinc-800 rounded-lg px-3 py-2 cursor-pointer hover:bg-zinc-700/50 transition-colors">
                        <input type="checkbox" name="accepted_currencies[]" value="<?= $code ?>" class="w-4 h-4 accent-violet-500 rounded" <?= in_array($code, $cur_accepted) ? 'checked' : '' ?>>
                        <span class="text-white text-sm"><?= $code ?> (<?= $c['symbol'] ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Format Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">Symbol Position</label>
                    <select name="currency[symbol_position]"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                        <option value="left" <?= $cur_symbol_pos === 'left' ? 'selected' : '' ?>>Left ($100)</option>
                        <option value="right" <?= $cur_symbol_pos === 'right' ? 'selected' : '' ?>>Right (100$)</option>
                        <option value="left_space" <?= $cur_symbol_pos === 'left_space' ? 'selected' : '' ?>>Left with space ($ 100)</option>
                        <option value="right_space" <?= $cur_symbol_pos === 'right_space' ? 'selected' : '' ?>>Right with space (100 $)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">Decimal Places</label>
                    <select name="currency[decimal_places]"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                        <option value="0" <?= $cur_decimals === '0' ? 'selected' : '' ?>>0 (123)</option>
                        <option value="2" <?= $cur_decimals === '2' ? 'selected' : '' ?>>2 (123.45)</option>
                        <option value="3" <?= $cur_decimals === '3' ? 'selected' : '' ?>>3 (123.456)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">Thousand Separator</label>
                    <select name="currency[thousand_separator]"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                        <option value="," <?= $cur_thousands === ',' ? 'selected' : '' ?>>Comma (1,234.56)</option>
                        <option value="." <?= $cur_thousands === '.' ? 'selected' : '' ?>>Period (1.234,56)</option>
                        <option value="space" <?= $cur_thousands === 'space' ? 'selected' : '' ?>>Space (1 234.56)</option>
                        <option value="none" <?= $cur_thousands === 'none' ? 'selected' : '' ?>>None (1234.56)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">Decimal Separator</label>
                    <select name="currency[decimal_separator]"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                        <option value="." <?= $cur_decimal_sep === '.' ? 'selected' : '' ?>>Period (123.45)</option>
                        <option value="," <?= $cur_decimal_sep === ',' ? 'selected' : '' ?>>Comma (123,45)</option>
                    </select>
                </div>
            </div>

            <!-- Auto Conversion Toggle -->
            <div class="bg-zinc-800 rounded-lg p-4">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h4 class="text-white font-medium text-sm">Automatic Currency Conversion</h4>
                        <p class="text-zinc-500 text-xs mt-0.5">Enable real-time exchange rate updates via API</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="currency[auto_conversion]" value="1" class="sr-only peer" <?= $cur_auto_convert === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                    </label>
                </div>
            </div>

        </div>
        <div class="px-5 py-4 border-t border-zinc-800 flex justify-end">
            <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                Save Currency Settings
            </button>
        </div>
    </div>
</form>
