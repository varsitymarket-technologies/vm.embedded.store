<?php
$agent_name        = $agent_current['assistant_name']  ?? 'Store Copilot';
$agent_role        = $agent_current['assistant_role']  ?? 'Admin-only operations assistant';
$agent_provider    = $agent_current['provider']        ?? 'google';
$agent_model       = $agent_current['model']           ?? 'gemini-1.5-flash';
$agent_gemini_key  = $agent_current['gemini_api_key']  ?? '';
$agent_temperature = $agent_current['temperature']     ?? '0.2';
$agent_max_tokens  = $agent_current['max_output_tokens'] ?? '1200';
$agent_style       = $agent_current['response_style']  ?? 'concise';
$agent_prompt      = $agent_current['system_prompt']   ?? '';
$agent_enabled     = ($agent_current['enabled']        ?? '1') === '1';
$agent_admin_only  = ($agent_current['admin_only']     ?? '1') === '1';
$agent_mcp_enabled = ($agent_current['mcp_enabled']    ?? '1') === '1';
$agent_mcp_name    = $agent_current['mcp_server_name'] ?? 'vm-admin-mcp';
$agent_mcp_url     = $agent_current['mcp_server_url']  ?? '';
$agent_mcp_transport = $agent_current['mcp_transport'] ?? 'http';
$agent_mcp_header  = $agent_current['mcp_auth_header'] ?? 'X-MCP-Key';
$agent_scopes      = $agent_current['allowed_scopes']  ?? ['orders', 'products', 'customers', 'settings'];
$agent_connected   = !empty($agent_gemini_key);

$gemini_models = [
    'gemini-1.5-flash'       => 'Gemini 1.5 Flash — Fast & efficient',
    'gemini-1.5-flash-8b'    => 'Gemini 1.5 Flash-8B — Lightweight',
    'gemini-1.5-pro'         => 'Gemini 1.5 Pro — Advanced reasoning',
    'gemini-2.0-flash'       => 'Gemini 2.0 Flash — Next-gen speed',
    'gemini-2.0-flash-lite'  => 'Gemini 2.0 Flash-Lite — Ultra-light',
];

$scope_options = [
    'orders'    => ['label' => 'Orders',    'desc' => 'Review, summarize, and help manage order workflows'],
    'products'  => ['label' => 'Products',  'desc' => 'Inspect inventory, pricing, and catalog changes'],
    'customers' => ['label' => 'Customers', 'desc' => 'Search profiles, notes, and customer history'],
    'settings'  => ['label' => 'Settings',  'desc' => 'Read and explain admin configuration safely'],
    'theme'     => ['label' => 'Themes',    'desc' => 'Assist with theme selection and layout guidance'],
    'analytics' => ['label' => 'Analytics', 'desc' => 'Summarize performance and usage signals'],
    'payments'  => ['label' => 'Payments',  'desc' => 'Help with gateway setup and payment status'],
    'support'   => ['label' => 'Support',   'desc' => 'Draft replies and internal action plans'],
];
?>

<style>
    input[type="text"],
    input[type="url"],
    input[type="number"],
    input[type="password"],
    select,
    textarea {
        background-color: #18181c !important;
        color: #e4e4e7 !important;
    }

    /* Brain-acquisition pulse ring */
    @keyframes brain-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(66, 133, 244, 0.0); }
        50%       { box-shadow: 0 0 0 8px rgba(66, 133, 244, 0.12); }
    }
    @keyframes orbit {
        from { transform: rotate(0deg) translateX(22px) rotate(0deg); }
        to   { transform: rotate(360deg) translateX(22px) rotate(-360deg); }
    }
    @keyframes orbit2 {
        from { transform: rotate(120deg) translateX(18px) rotate(-120deg); }
        to   { transform: rotate(480deg) translateX(18px) rotate(-480deg); }
    }
    @keyframes orbit3 {
        from { transform: rotate(240deg) translateX(15px) rotate(-240deg); }
        to   { transform: rotate(600deg) translateX(15px) rotate(-600deg); }
    }
    @keyframes synapse-blink {
        0%, 100% { opacity: 0.2; transform: scale(1); }
        50%       { opacity: 1;   transform: scale(1.4); }
    }
    .brain-orb {
        animation: brain-pulse 3s ease-in-out infinite;
    }
    .orbit-dot-1 { animation: orbit  4s linear infinite; }
    .orbit-dot-2 { animation: orbit2 3s linear infinite; }
    .orbit-dot-3 { animation: orbit3 5s linear infinite; }
    .synapse { animation: synapse-blink 2s ease-in-out infinite; }
    .synapse:nth-child(2) { animation-delay: 0.4s; }
    .synapse:nth-child(3) { animation-delay: 0.8s; }
    .synapse:nth-child(4) { animation-delay: 1.2s; }

    .google-gradient { background: linear-gradient(135deg, #4285F4 0%, #34A853 33%, #FBBC05 66%, #EA4335 100%); }
    .gemini-border   { border-color: rgba(66,133,244,0.25) !important; }
    .gemini-glow     { box-shadow: 0 0 24px rgba(66,133,244,0.08); }
</style>

<a href="?tab=general" class="inline-flex items-center gap-2 text-gray-500 hover:text-white text-sm mb-6">
    <i class="bi bi-chevron-left"></i>
    Back to Settings
</a>

<?php if (isset($_SERVER['__AI_EXTENSION__'])): ?>
    <?php if ($_SERVER['__AI_EXTENSION__']): ?>

        <form method="POST">
            <input type="hidden" name="action" value="save_ai_agent">

            <div class="max-w-7xl mx-auto">

                <!-- ── Page header ── -->
                <div class="flex items-center justify-between mb-8 gap-4 flex-wrap">
                    <div class="flex items-center gap-4">

                        <!-- Animated brain orb -->
                        <div class="brain-orb relative flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-blue-500/20 bg-[#0d0d14]">
                            <!-- core icon -->
                            <svg class="w-7 h-7 text-blue-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C10.3 2 9 3.3 9 5c0 .4.1.8.2 1.1C7.9 6.7 7 7.9 7 9.3c0 .5.1 1 .3 1.4C6.5 11.4 6 12.4 6 13.5 6 15.4 7.3 17 9 17.4V19h6v-1.6c1.7-.4 3-2 3-3.9 0-1.1-.5-2.1-1.3-2.8.2-.4.3-.9.3-1.4 0-1.4-.9-2.6-2.2-3.2C14.9 5.8 15 5.4 15 5c0-1.7-1.3-3-3-3z" fill="currentColor" opacity="0.8"/>
                                <circle cx="12" cy="21" r="1" fill="currentColor" opacity="0.5"/>
                            </svg>
                            <!-- orbiting dots -->
                            <span class="orbit-dot-1 absolute h-1.5 w-1.5 rounded-full bg-blue-400 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></span>
                            <span class="orbit-dot-2 absolute h-1 w-1 rounded-full bg-green-400 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></span>
                            <span class="orbit-dot-3 absolute h-1 w-1 rounded-full bg-yellow-400 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></span>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold text-white flex items-center gap-2">
                                AI Agent
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 tracking-wide">
                                    Google AI Studio
                                </span>
                            </h1>
                            <p class="text-sm text-gray-500 mt-0.5">Powered by Gemini — configure how your agent acquires its intelligence</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $agent_enabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-zinc-800 text-zinc-500 border border-white/5' ?>">
                            <?= $agent_enabled ? 'Active' : 'Inactive' ?>
                        </span>
                        <button type="submit" class="px-5 py-2 rounded-xl text-sm font-medium text-white transition-all"
                            style="background: linear-gradient(135deg,#4285F4,#34A853);">
                            Save Changes
                        </button>
                    </div>
                </div>

                <!-- ── Brain acquisition hero banner ── -->
                <div class="mb-6 rounded-2xl border border-blue-500/15 bg-[#0a0a12] p-5 gemini-glow overflow-hidden relative">
                    <div class="absolute inset-0 pointer-events-none" aria-hidden="true"
                        style="background: radial-gradient(ellipse 60% 50% at 80% 50%, rgba(66,133,244,0.07) 0%, transparent 70%);">
                    </div>
                    <div class="relative flex items-center gap-6 flex-wrap">
                        <!-- Synapse dots -->
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="synapse h-2 w-2 rounded-full bg-blue-400"></span>
                            <span class="synapse h-2 w-2 rounded-full bg-green-400"></span>
                            <span class="synapse h-2 w-2 rounded-full bg-yellow-400"></span>
                            <span class="synapse h-2 w-2 rounded-full bg-red-400"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white">
                                <?= $agent_connected
                                    ? '🧠 Brain acquired — agent is live on Gemini'
                                    : '⚡ Awaiting brain — paste your Gemini API key below to activate' ?>
                            </p>
                            <p class="text-xs text-zinc-500 mt-0.5">
                                <?= $agent_connected
                                    ? 'Your store agent is drawing its intelligence from Google\'s Gemini models via AI Studio.'
                                    : 'Get a free API key at aistudio.google.com → "Get API key". No credit card required for free tier.' ?>
                            </p>
                        </div>
                        <?php if (!$agent_connected): ?>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener"
                                class="shrink-0 inline-flex items-center gap-2 rounded-xl border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-xs font-semibold text-blue-300 hover:bg-blue-500/20 transition-colors">
                                <i class="bi bi-box-arrow-up-right"></i> Get API Key
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-[2fr_1fr] gap-6">

                    <!-- ── MAIN COLUMN ── -->
                    <div class="space-y-6">

                        <!-- General -->
                        <div class="bg-[#111115] border border-white/5 rounded-2xl overflow-hidden hover:border-white/10 transition-colors">
                            <div class="px-5 py-4 border-b border-white/5">
                                <h2 class="font-semibold text-white text-sm">General</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Assistant Name</label>
                                        <input type="text" name="agent[assistant_name]"
                                            value="<?= htmlspecialchars($agent_name, ENT_QUOTES, 'UTF-8') ?>"
                                            class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Role</label>
                                        <input type="text" name="agent[assistant_role]"
                                            value="<?= htmlspecialchars($agent_role, ENT_QUOTES, 'UTF-8') ?>"
                                            class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm">
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Provider</label>
                                        <select name="agent[provider]" class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm">
                                            <option value="google" <?= $agent_provider === 'google' ? 'selected' : '' ?>>Google AI Studio (Gemini)</option>
                                            <option value="mcp"    <?= $agent_provider === 'mcp'    ? 'selected' : '' ?>>MCP</option>
                                            <option value="custom" <?= $agent_provider === 'custom' ? 'selected' : '' ?>>Custom</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Response Style</label>
                                        <select name="agent[response_style]" class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm">
                                            <option value="concise"  <?= $agent_style === 'concise'  ? 'selected' : '' ?>>Concise</option>
                                            <option value="balanced" <?= $agent_style === 'balanced' ? 'selected' : '' ?>>Balanced</option>
                                            <option value="detailed" <?= $agent_style === 'detailed' ? 'selected' : '' ?>>Detailed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gemini API Connection -->
                        <div class="bg-[#111115] border gemini-border rounded-2xl overflow-hidden hover:border-blue-500/30 transition-colors gemini-glow">
                            <div class="px-5 py-4 border-b border-blue-500/10 flex items-center gap-3">
                                <!-- Google coloured G -->
                                <svg width="18" height="18" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M43.6 20.5H42V20H24v8h11.3C33.9 32.2 29.4 35 24 35c-6.1 0-11-4.9-11-11s4.9-11 11-11c2.8 0 5.3 1 7.2 2.7l5.7-5.7C33.4 7.1 28.9 5 24 5 13.5 5 5 13.5 5 24s8.5 19 19 19 19-8.5 19-19c0-1.2-.1-2.3-.4-3.5z" fill="#FBC02D"/>
                                    <path d="M6.3 14.7l6.6 4.8C14.6 16 19 13 24 13c2.8 0 5.3 1 7.2 2.7l5.7-5.7C33.4 7.1 28.9 5 24 5c-7.7 0-14.3 4.4-17.7 9.7z" fill="#E53935"/>
                                    <path d="M24 43c4.8 0 9.1-1.8 12.4-4.8l-6.1-5.1C28.5 34.5 26.3 35 24 35c-5.3 0-9.8-2.7-11.3-6.5l-6.5 5C9.6 38.5 16.3 43 24 43z" fill="#4CAF50"/>
                                    <path d="M43.6 20.5H42V20H24v8h11.3c-.7 1.8-1.9 3.4-3.4 4.6l6.1 5.1C37.5 40.5 43 33 43 24c0-1.2-.1-2.3-.4-3.5z" fill="#1565C0"/>
                                </svg>
                                <h2 class="font-semibold text-white text-sm">Google AI Studio Connection</h2>
                                <?php if ($agent_connected): ?>
                                    <span class="ml-auto text-xs font-medium text-emerald-400 flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 inline-block synapse"></span> Connected
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Gemini API Key</label>
                                    <div class="relative">
                                        <input type="password" name="agent[gemini_api_key]"
                                            class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm pr-10"
                                            placeholder="AIza...">
                                        <i class="bi bi-key-fill absolute right-3 top-1/2 -translate-y-1/2 text-zinc-600 text-xs"></i>
                                    </div>
                                    <p class="mt-1.5 text-xs text-zinc-600">Leave blank to keep the existing key. Get yours free at
                                        <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener"
                                            class="text-blue-400 hover:underline">aistudio.google.com</a>.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Model</label>
                                    <select name="agent[model]" class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm">
                                        <?php foreach ($gemini_models as $val => $label): ?>
                                            <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= $agent_model === $val ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="mt-1.5 text-xs text-zinc-600">Flash models are faster and cheaper; Pro has stronger reasoning.</p>
                                </div>
                            </div>
                        </div>

                        <!-- System Prompt -->
                        <div class="bg-[#111115] border border-white/5 rounded-2xl overflow-hidden hover:border-white/10 transition-colors">
                            <div class="px-5 py-4 border-b border-white/5">
                                <h2 class="font-semibold text-white text-sm">System Prompt</h2>
                                <p class="text-xs text-zinc-600 mt-0.5">This is the agent's core personality and operating rules — its "brain seed".</p>
                            </div>
                            <div class="p-5">
                                <textarea rows="10" name="agent[system_prompt]"
                                    class="w-full border border-white/5 rounded-xl px-3 py-3 font-mono text-sm leading-relaxed"><?= htmlspecialchars($agent_prompt, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                    </div><!-- /main -->

                    <!-- ── SIDEBAR ── -->
                    <div class="space-y-6">

                        <!-- Switches -->
                        <div class="bg-[#111115] border border-white/5 rounded-2xl overflow-hidden">
                            <div class="px-5 py-4 border-b border-white/5">
                                <h3 class="font-semibold text-white text-sm">Status & Access</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <label class="flex items-center justify-between gap-3 cursor-pointer group">
                                    <div>
                                        <p class="text-sm font-medium text-white">Enable Agent</p>
                                        <p class="text-xs text-zinc-500">Show the agent chat panel in the admin</p>
                                    </div>
                                    <div class="relative">
                                        <input type="checkbox" name="agent[enabled]" value="1" class="sr-only peer" <?= $agent_enabled ? 'checked' : '' ?>>
                                        <div class="w-10 h-5 rounded-full bg-zinc-700 peer-checked:bg-emerald-500 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></div>
                                    </div>
                                </label>
                                <label class="flex items-center justify-between gap-3 cursor-pointer group">
                                    <div>
                                        <p class="text-sm font-medium text-white">Admin Only</p>
                                        <p class="text-xs text-zinc-500">Restrict the agent to admin-level users</p>
                                    </div>
                                    <div class="relative">
                                        <input type="checkbox" name="agent[admin_only]" value="1" class="sr-only peer" <?= $agent_admin_only ? 'checked' : '' ?>>
                                        <div class="w-10 h-5 rounded-full bg-zinc-700 peer-checked:bg-blue-500 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Model tuning -->
                        <div class="bg-[#111115] border border-white/5 rounded-2xl overflow-hidden">
                            <div class="px-5 py-4 border-b border-white/5">
                                <h3 class="font-semibold text-white text-sm">Tuning</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Temperature
                                        <span class="text-zinc-600 normal-case ml-1">(0 = precise, 1 = creative)</span>
                                    </label>
                                    <input type="number" step="0.1" min="0" max="1" name="agent[temperature]"
                                        value="<?= htmlspecialchars($agent_temperature, ENT_QUOTES, 'UTF-8') ?>"
                                        class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-zinc-500 mb-1.5 uppercase tracking-wider">Max Output Tokens</label>
                                    <input type="number" name="agent[max_output_tokens]"
                                        value="<?= htmlspecialchars($agent_max_tokens, ENT_QUOTES, 'UTF-8') ?>"
                                        class="w-full border border-white/5 rounded-xl px-3 py-2 text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Quick setup guide -->
                        <div class="rounded-2xl border border-blue-500/15 bg-[#0a0a12] p-5">
                            <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                                <i class="bi bi-lightning-charge-fill text-yellow-400"></i> Quick Setup
                            </h3>
                            <ol class="space-y-2.5 text-xs text-zinc-500">
                                <li class="flex items-start gap-2">
                                    <span class="shrink-0 mt-px h-4 w-4 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-[10px]">1</span>
                                    Visit <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-400 hover:underline">aistudio.google.com</a> and create a key
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="shrink-0 mt-px h-4 w-4 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-[10px]">2</span>
                                    Paste it in the Gemini API Key field above
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="shrink-0 mt-px h-4 w-4 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-[10px]">3</span>
                                    Pick a model — <strong class="text-zinc-400">1.5 Flash</strong> is recommended to start
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="shrink-0 mt-px h-4 w-4 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-[10px]">4</span>
                                    Enable the agent and save
                                </li>
                            </ol>
                        </div>

                    </div><!-- /sidebar -->

                </div>
            </div>
        </form>

    <?php endif; ?>
<?php endif; ?>