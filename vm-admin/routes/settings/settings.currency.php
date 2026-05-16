<?php

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


