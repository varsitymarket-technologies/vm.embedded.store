<?php
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

<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<form method="POST">
    <input type="hidden" name="action" value="save_payment">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h2 class="text-lg font-bold text-white">Payment Methods</h2>
            <p class="text-zinc-400 text-sm mt-1">Configure how your customers can pay for their orders</p>
        </div>
        <div class="p-5 space-y-4">

            <!-- Cash on Delivery -->
            <div class="bg-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <i class="bi bi-cash-stack text-emerald-400"></i>
                        </span>
                        <div>
                            <h4 class="text-white font-medium text-sm">Cash on Delivery</h4>
                            <p class="text-zinc-500 text-xs">Pay in cash when the order arrives</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="payment[cod_enabled]" value="0">
                        <input type="checkbox" name="payment[cod_enabled]" value="1" class="sr-only peer" id="cod_toggle" <?= $pay_cod_enabled === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
                <div id="cod_settings" class="mt-4 pt-3 border-t border-zinc-700/50 grid grid-cols-1 md:grid-cols-2 gap-3" style="opacity:<?= $pay_cod_enabled === '1' ? '1' : '0.5' ?>">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Min Order Amount</label>
                        <input type="number" step="0.01" name="payment[cod_min_amount]" value="<?= htmlspecialchars($pay_cod_min) ?>" placeholder="No minimum"
                            class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Additional Fee</label>
                        <input type="number" step="0.01" name="payment[cod_fee]" value="<?= htmlspecialchars($pay_cod_fee) ?>"
                            class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500 transition-colors">
                    </div>
                </div>
            </div>

            <!-- YOCO -->
            <div class="bg-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <i class="bi bi-credit-card-2-front text-violet-400"></i>
                        </span>
                        <div>
                            <h4 class="text-white font-medium text-sm">YOCO (Credit Card)</h4>
                            <p class="text-zinc-500 text-xs">Visa, Mastercard, Amex via YOCO</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="payment[yoco_enabled]" value="0">
                        <input type="checkbox" name="payment[yoco_enabled]" value="1" class="sr-only peer" id="yoco_toggle" <?= $pay_yoco_enabled === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                    </label>
                </div>
                <div id="yoco_settings" class="mt-4 pt-3 border-t border-zinc-700/50 space-y-3 <?= $pay_yoco_enabled !== '1' ? 'hidden' : '' ?>">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">YOCO Secret Key</label>
                        <input type="password" name="payment[yoco_secret]" value="<?= htmlspecialchars($pay_yoco_secret) ?>" placeholder="sk_test_xxxxxxxxxxxxx"
                            class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors font-mono">
                        <p class="text-zinc-500 text-xs mt-1">Find in YOCO Dashboard &rarr; Developers &rarr; API Keys</p>
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">YOCO Public Key</label>
                        <input type="text" name="payment[yoco_public]" value="<?= htmlspecialchars($pay_yoco_public) ?>" placeholder="pk_test_xxxxxxxxxxxxx"
                            class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors font-mono">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Payment Mode</label>
                            <select name="payment[yoco_mode]"
                                class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors">
                                <option value="test" <?= $pay_yoco_mode === 'test' ? 'selected' : '' ?>>Test Mode</option>
                                <option value="live" <?= $pay_yoco_mode === 'live' ? 'selected' : '' ?>>Live Mode</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Transaction Fee (%)</label>
                            <input type="number" step="0.1" name="payment[yoco_fee]" value="<?= htmlspecialchars($pay_yoco_fee) ?>"
                                class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors">
                        </div>
                    </div>
                </div>
            </div>

            <!-- PayPal -->
            <div class="bg-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-lg bg-sky-500/10 flex items-center justify-center">
                            <i class="bi bi-paypal text-sky-400"></i>
                        </span>
                        <div>
                            <h4 class="text-white font-medium text-sm">PayPal</h4>
                            <p class="text-zinc-500 text-xs">PayPal wallet, credit cards, and more</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="payment[paypal_enabled]" value="0">
                        <input type="checkbox" name="payment[paypal_enabled]" value="1" class="sr-only peer" id="paypal_toggle" <?= $pay_paypal_enabled === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
                    </label>
                </div>
                <div id="paypal_settings" class="mt-4 pt-3 border-t border-zinc-700/50 space-y-3 <?= $pay_paypal_enabled !== '1' ? 'hidden' : '' ?>">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">PayPal Client ID</label>
                        <input type="text" name="payment[paypal_client_id]" value="<?= htmlspecialchars($pay_paypal_client) ?>" placeholder="AYxXxxxxxx...xxxxx"
                            class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500 transition-colors font-mono">
                        <p class="text-zinc-500 text-xs mt-1">Get from PayPal Developer Dashboard &rarr; Apps & Credentials</p>
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">PayPal Secret Key</label>
                        <input type="password" name="payment[paypal_secret]" value="<?= htmlspecialchars($pay_paypal_secret) ?>" placeholder="EJxxxxxx...xxxxx"
                            class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500 transition-colors font-mono">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Environment</label>
                            <select name="payment[paypal_env]"
                                class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500 transition-colors">
                                <option value="sandbox" <?= $pay_paypal_env === 'sandbox' ? 'selected' : '' ?>>Sandbox (Test)</option>
                                <option value="production" <?= $pay_paypal_env === 'production' ? 'selected' : '' ?>>Production (Live)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Transaction Fee (%)</label>
                            <input type="number" step="0.1" name="payment[paypal_fee]" value="<?= htmlspecialchars($pay_paypal_fee) ?>"
                                class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500 transition-colors">
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="payment[paypal_guest_checkout]" value="0">
                        <input type="checkbox" name="payment[paypal_guest_checkout]" value="1" class="w-4 h-4 accent-sky-500 rounded" <?= $pay_paypal_guest === '1' ? 'checked' : '' ?>>
                        <span class="text-zinc-300 text-xs">Allow guest checkout (credit/debit cards without PayPal account)</span>
                    </label>
                </div>
            </div>

        </div>
        <div class="px-5 py-4 border-t border-zinc-800 flex items-center justify-between">
            <span class="text-zinc-500 text-xs"><i class="bi bi-shield-check"></i> AES-256-CBC encrypted</span>
            <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                Save Payment Settings
            </button>
        </div>
    </div>
</form>

<script>
document.getElementById('cod_toggle')?.addEventListener('change', function(e) {
    const s = document.getElementById('cod_settings');
    if (s) s.style.opacity = e.target.checked ? '1' : '0.5';
});
document.getElementById('yoco_toggle')?.addEventListener('change', function(e) {
    const s = document.getElementById('yoco_settings');
    if (s) s.classList.toggle('hidden', !e.target.checked);
});
document.getElementById('paypal_toggle')?.addEventListener('change', function(e) {
    const s = document.getElementById('paypal_settings');
    if (s) s.classList.toggle('hidden', !e.target.checked);
});
</script>
