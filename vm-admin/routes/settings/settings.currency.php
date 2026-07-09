<?php

#   TITLE   : Settings Currency Configuration   
#   DESC    : The currency configuration page for VarsityMarket SaaS platform, allowing administrators to set default currency, accepted currencies, and formatting options.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2029/07/09

// Define file path for caching currencies
$currencies_file = dirname(dirname(dirname(__DIR__))). '/assets/currencies.json';

// 1. Fetch from a public API if the local file doesn't exist
if (!file_exists($currencies_file)) {
    // Fetching from a public gist containing common currency codes, names, and symbols
    $api_url = 'https://gist.githubusercontent.com/ksafranski/2973986/raw/';
    $response = @file_get_contents($api_url);

    $currencies = [];
    if ($response) {
        $data = json_decode($response, true);
        foreach ($data as $code => $details) {
            $currencies[$code] = [
                'name' => $details['name'] ?? $code,
                'symbol' => $details['symbol'] ?? $code
            ];
        }
        // Save to file to prevent rate-limiting and speed up loads
        file_put_contents($currencies_file, json_encode($currencies, JSON_PRETTY_PRINT));
    } else {
        // Fallback array if the API fetch fails for any reason
        $currencies = [
            'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R'],
            'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
            'EUR' => ['name' => 'Euro', 'symbol' => '€'],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£']
        ];
    }
} else {
    // Load from local cache
    $currencies = json_decode(file_get_contents($currencies_file), true);
}

// 2. Simulated backend variables (usually fetched from your DB)
$cur_default = $_POST['currency']['default_currency'] ?? 'ZAR';
$cur_accepted = $_POST['accepted_currencies'] ?? ['ZAR', 'USD', 'BWP'];
$cur_symbol_pos = $_POST['currency']['symbol_position'] ?? 'left';
$cur_decimals = $_POST['currency']['decimal_places'] ?? '2';
$cur_thousands = $_POST['currency']['thousand_separator'] ?? 'space';
$cur_decimal_sep = $_POST['currency']['decimal_separator'] ?? '.';
$cur_auto_convert = isset($_POST['currency']['auto_conversion']) ? '1' : '0';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* Custom stealth scrollbar for the currency list */
    .stealth-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .stealth-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .stealth-scroll::-webkit-scrollbar-thumb {
        background-color: #3f3f46;
        border-radius: 4px;
    }

    .stealth-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #8b5cf6;
    }
</style>

<div class="min-h-screen bg-black text-zinc-300 p-6 font-sans selection:bg-violet-500/30">
    <a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-500 hover:text-violet-400 text-sm font-medium transition-colors mb-8">
        <i class="bi bi-arrow-left"></i> Back to Settings
    </a>

    <form method="POST" id="currency-setup-form" class="max-w-6xl mx-auto flex flex-col lg:flex-row gap-8">
        <input type="hidden" name="action" value="save_currency">

        <div class="w-full lg:w-1/3">
            <div class="sticky top-6 border border-zinc-800 rounded-xl overflow-hidden shadow-[0_0_30px_rgba(0,0,0,0.5)]">
                <div class="bg-zinc-900 border-b border-zinc-800 px-4 py-3 flex items-center justify-between">
                    <div class="flex gap-1.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-zinc-700"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-zinc-700"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-zinc-700"></div>
                    </div>
                    <span class="text-zinc-500 text-xs font-mono tracking-widest"><?php echo __DOMAIN__; ?></span>
                </div>

                <div class="p-6">
                    <div class="bg-zinc-900 rounded-lg p-1 border border-zinc-800/50 shadow-inner">
                        <div class="bg-black rounded-md p-5 border border-zinc-800">
                            <div class="h-28 rounded flex items-center justify-center mb-5 border border-zinc-800 relative overflow-hidden bg-gradient-to-tr from-zinc-900 to-black">
                                <div class="absolute inset-0 bg-violet-500/5 opacity-50"></div>
                                <i class="bi bi-box-seam text-zinc-700 text-4xl"></i>
                            </div>
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="text-white font-medium text-sm">Product Sample</h3>
                                    <p class="text-zinc-500 text-xs">This is a demo product</p>
                                </div>
                                <span class="bg-violet-900/30 text-violet-400 text-[10px] font-bold px-2 py-0.5 rounded border border-violet-500/20">ANNUAL</span>
                            </div>

                            <div class="mt-6 pt-4 border-t border-dashed border-zinc-800 flex items-end justify-between">
                                <span class="text-zinc-400 text-xs font-medium">Total Due</span>
                                <span id="live-price-preview" class="text-2xl font-bold text-white tracking-tight drop-shadow-md">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1">

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Currency Configuration</h2>
                <div class="flex items-center justify-between relative">
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-0.5 bg-zinc-800 -z-10"></div>

                    <button type="button" class="step-indicator flex flex-col items-center gap-2" data-target="1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-violet-500 text-violet-400 font-bold transition-all" id="ind-1">1</div>
                        <span class="text-xs font-medium text-zinc-400">Localization</span>
                    </button>

                    <button type="button" class="step-indicator flex flex-col items-center gap-2" data-target="2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-zinc-800 text-zinc-600 font-bold transition-all" id="ind-2">2</div>
                        <span class="text-xs font-medium text-zinc-400">Formatting</span>
                    </button>

                    <button type="button" class="step-indicator flex flex-col items-center gap-2" data-target="3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-zinc-800 text-zinc-600 font-bold transition-all" id="ind-3">3</div>
                        <span class="text-xs font-medium text-zinc-400">Automation</span>
                    </button>
                </div>
            </div>

            <div id="step-1" class="wizard-step bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-bold text-white mb-1">Store Localization</h3>
                <p class="text-zinc-500 text-sm mb-6">Select your primary operating currency and supported alternatives.</p>

                <div class="space-y-6">
                    <div>
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Primary Currency</label>
                        <select name="currency[default_currency]" id="input-default-currency"
                            class="w-full bg-black border border-zinc-700 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <?php foreach ($currencies as $code => $c): ?>
                                <option value="<?= $code ?>" <?= $cur_default === $code ? 'selected' : '' ?>>
                                    <?= $code ?> - <?= htmlspecialchars($c['name']) ?> (<?= $c['symbol'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: none;;">
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Supported Currencies</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 h-64 overflow-y-auto stealth-scroll pr-2 border border-zinc-800 p-2 rounded-lg bg-black">
                            <?php foreach ($currencies as $code => $c): ?>
                                <label class="flex items-center gap-3 bg-zinc-900 border border-zinc-800 rounded-md px-3 py-2 cursor-pointer hover:border-violet-500/50 transition-colors group">
                                    <input type="checkbox" name="accepted_currencies[]" value="<?= $code ?>"
                                        class="w-4 h-4 bg-black border-zinc-700 accent-violet-600 rounded"
                                        <?= in_array($code, $cur_accepted) ? 'checked' : '' ?>>
                                    <span class="text-zinc-300 text-sm group-hover:text-white transition-colors">
                                        <span class="font-medium text-violet-400 mr-1"><?= $code ?></span>
                                        <span class="text-zinc-500">(<?= $c['symbol'] ?>)</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="button" class="btn-next bg-violet-600 hover:bg-violet-500 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors" data-next="2">
                        Continue to Formatting <i class="bi bi-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <div id="step-2" class="wizard-step hidden bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-bold text-white mb-1">Visual Formatting</h3>
                <p class="text-zinc-500 text-sm mb-6">Customize how price tags are displayed across your SaaS application.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Symbol Position</label>
                        <select name="currency[symbol_position]" id="input-symbol-pos"
                            class="w-full bg-black border border-zinc-700 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors">
                            <option value="left" <?= $cur_symbol_pos === 'left' ? 'selected' : '' ?>>Left ($100)</option>
                            <option value="right" <?= $cur_symbol_pos === 'right' ? 'selected' : '' ?>>Right (100$)</option>
                            <option value="left_space" <?= $cur_symbol_pos === 'left_space' ? 'selected' : '' ?>>Left + Space ($ 100)</option>
                            <option value="right_space" <?= $cur_symbol_pos === 'right_space' ? 'selected' : '' ?>>Right + Space (100 $)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Decimal Places</label>
                        <select name="currency[decimal_places]" id="input-decimals"
                            class="w-full bg-black border border-zinc-700 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors">
                            <option value="0" <?= $cur_decimals === '0' ? 'selected' : '' ?>>0 (123)</option>
                            <option value="2" <?= $cur_decimals === '2' ? 'selected' : '' ?>>2 (123.45)</option>
                            <option value="3" <?= $cur_decimals === '3' ? 'selected' : '' ?>>3 (123.456)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Thousand Separator</label>
                        <select name="currency[thousand_separator]" id="input-thou-sep"
                            class="w-full bg-black border border-zinc-700 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors">
                            <option value="," <?= $cur_thousands === ',' ? 'selected' : '' ?>>Comma (1,234.56)</option>
                            <option value="." <?= $cur_thousands === '.' ? 'selected' : '' ?>>Period (1.234,56)</option>
                            <option value="space" <?= $cur_thousands === 'space' ? 'selected' : '' ?>>Space (1 234.56)</option>
                            <option value="none" <?= $cur_thousands === 'none' ? 'selected' : '' ?>>None (1234.56)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Decimal Separator</label>
                        <select name="currency[decimal_separator]" id="input-dec-sep"
                            class="w-full bg-black border border-zinc-700 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors">
                            <option value="." <?= $cur_decimal_sep === '.' ? 'selected' : '' ?>>Period (123.45)</option>
                            <option value="," <?= $cur_decimal_sep === ',' ? 'selected' : '' ?>>Comma (123,45)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" class="btn-prev text-zinc-400 hover:text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors" data-prev="1">
                        <i class="bi bi-arrow-left mr-1"></i> Back
                    </button>
                    <button type="button" class="btn-next bg-violet-600 hover:bg-violet-500 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors" data-next="3">
                        Continue to Automation <i class="bi bi-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <div id="step-3" class="wizard-step hidden bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-bold text-white mb-1">Exchange Rates & Completion</h3>
                <p class="text-zinc-500 text-sm mb-6">Finalize how foreign transactions are managed on your platform.</p>

                <div class="bg-black border border-zinc-800 rounded-lg p-5 mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-white font-medium text-sm flex items-center gap-2">
                                <i class="bi bi-arrow-repeat text-violet-400"></i> Auto-Sync Exchange Rates
                            </h4>
                            <p class="text-zinc-500 text-xs mt-1">Automatically update pricing daily using open market APIs.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="currency[auto_conversion]" value="1" class="sr-only peer" <?= $cur_auto_convert === '1' ? 'checked' : '' ?>>
                            <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" class="btn-prev text-zinc-400 hover:text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors" data-prev="2">
                        <i class="bi bi-arrow-left mr-1"></i> Back
                    </button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-[0_0_15px_rgba(139,92,246,0.4)] transition-all">
                        Deploy Settings
                    </button>
                </div>
            </div>
        </div>

        
    </form>
</div>

<script>
    const currenciesData = <?= json_encode($currencies) ?>;
    const baseValue = 1249500.85; // A large dummy value for the preview

    // 1. Wizard Navigation Logic
    document.querySelectorAll('.btn-next, .btn-prev, .step-indicator').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetStep = e.currentTarget.dataset.next || e.currentTarget.dataset.prev || e.currentTarget.dataset.target;
            if (targetStep) goToStep(parseInt(targetStep));
        });
    });

    function goToStep(stepNumber) {
        document.querySelectorAll('.wizard-step').forEach(step => step.classList.add('hidden'));
        document.getElementById(`step-${stepNumber}`).classList.remove('hidden');

        document.querySelectorAll('.step-indicator').forEach((ind, index) => {
            const circle = document.getElementById(`ind-${index + 1}`);
            if (index + 1 === stepNumber) {
                circle.className = "w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-violet-500 text-violet-400 font-bold transition-all shadow-[0_0_10px_rgba(139,92,246,0.3)]";
            } else if (index + 1 < stepNumber) {
                circle.className = "w-8 h-8 rounded-full flex items-center justify-center border-2 bg-violet-600 border-violet-600 text-white font-bold transition-all";
                circle.innerHTML = '<i class="bi bi-check"></i>';
            } else {
                circle.className = "w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-zinc-800 text-zinc-600 font-bold transition-all";
                circle.innerHTML = index + 1;
            }
        });
    }

    // 2. Live Preview Updater Logic
    function updatePreview() {
        const currencyCode = document.getElementById('input-default-currency').value;
        const symbolPos = document.getElementById('input-symbol-pos').value;
        const decimals = parseInt(document.getElementById('input-decimals').value);
        const thouSepRaw = document.getElementById('input-thou-sep').value;
        const decSep = document.getElementById('input-dec-sep').value;

        const symbol = currenciesData[currencyCode]?.symbol || currencyCode;
        const tSep = thouSepRaw === 'space' ? ' ' : (thouSepRaw === 'none' ? '' : thouSepRaw);

        // Format the mathematical value
        let parts = (baseValue.toFixed(decimals)).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, tSep);
        let formattedNum = parts.join(decSep);

        // Apply symbol positioning
        let finalOutput = '';
        switch (symbolPos) {
            case 'left':
                finalOutput = `${symbol}${formattedNum}`;
                break;
            case 'right':
                finalOutput = `${formattedNum}${symbol}`;
                break;
            case 'left_space':
                finalOutput = `${symbol} ${formattedNum}`;
                break;
            case 'right_space':
                finalOutput = `${formattedNum} ${symbol}`;
                break;
        }

        const targetNode = document.getElementById('live-price-preview');
        // Add a quick animation class
        targetNode.style.opacity = 0;
        setTimeout(() => {
            targetNode.innerText = finalOutput;
            targetNode.style.opacity = 1;
            targetNode.style.transition = 'opacity 0.2s ease-in-out';
        }, 150);
    }

    // Attach listeners to trigger preview updates on change
    const inputs = ['input-default-currency', 'input-symbol-pos', 'input-decimals', 'input-thou-sep', 'input-dec-sep'];
    inputs.forEach(id => {
        document.getElementById(id).addEventListener('change', updatePreview);
    });

    // Init the script
    goToStep(1);
    updatePreview();
</script>