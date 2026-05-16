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


