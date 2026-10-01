<?php
$agent_name = (string) ($agent_current['assistant_name'] ?? 'Store Assistant');
$agent_role = (string) ($agent_current['assistant_role'] ?? 'Your AI powered store operations assistant');
$agent_provider = ($agent_current['provider'] ?? 'google') === 'openai' ? 'openai' : 'google';
$agent_model = (string) ($agent_current['model'] ?? 'gemini-2.5-flash');
$agent_gemini_key = (string) ($agent_current['gemini_api_key'] ?? '');
$agent_openai_key = (string) ($agent_current['openai_api_key'] ?? '');
$agent_temperature = (string) ($agent_current['temperature'] ?? '0.2');
$agent_max_tokens = (string) ($agent_current['max_output_tokens'] ?? '1200');
$agent_style = (string) ($agent_current['response_style'] ?? 'balanced');
$agent_connected = $agent_provider === 'openai' ? $agent_openai_key !== '' : $agent_gemini_key !== '';

$gemini_models = [
    'gemini-3.8-flash' => 'Gemini 3.8 Flash',
    'gemini-3.5-flash' => 'Gemini 3.5 Flash',
    'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite',
    'gemini-2.5-flash' => 'Gemini 2.5 Flash',
];
$openai_models = [
    'gpt-4.1-mini' => 'GPT-4.1 mini',
    'gpt-4.1' => 'GPT-4.1',
    'gpt-4o-mini' => 'GPT-4o mini',
    'gpt-4o' => 'GPT-4o',
];
$model_options = $agent_provider === 'openai' ? $openai_models : $gemini_models;
if (!array_key_exists($agent_model, $model_options)) {
    $agent_model = $agent_provider === 'openai' ? 'gpt-4.1-mini' : 'gemini-2.5-flash';
}
$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<style>
    .ai-settings input, .ai-settings select, .ai-settings textarea { color-scheme: dark; }
    .ai-settings input:focus, .ai-settings select:focus, .ai-settings textarea:focus { outline: 2px solid rgba(138,180,248,.6); outline-offset: 1px; }
    .ai-provider-card:has(input:checked) { border-color: rgba(138,180,248,.65); background: rgba(138,180,248,.08); box-shadow: 0 0 0 1px rgba(138,180,248,.16); }
</style>

<div class="ai-settings mx-auto max-w-6xl pb-12 text-zinc-100">
    <a href="?tab=general" class="mb-6 inline-flex items-center gap-2 text-sm text-zinc-400 transition hover:text-white">
        <i class="bi bi-arrow-left"></i> Settings
    </a>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="action" value="save_ai_agent">

        <header class="mb-8 flex flex-col gap-5 border-b border-white/10 pb-7 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-blue-300/20 bg-blue-300/10 px-3 py-1 text-xs font-medium text-blue-200">
                    <span class="h-1.5 w-1.5 rounded-full <?= $agent_connected ? 'bg-emerald-400' : 'bg-amber-300' ?>"></span>
                    <?= $agent_connected ? 'Provider connected' : 'Setup required' ?>
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-white">AI setup</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">Choose the model provider for your store assistant and website builder. Your API keys stay encrypted in this store’s settings.</p>
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-950/30 transition hover:bg-blue-400">
                <i class="bi bi-check2"></i> Save changes
            </button>
        </header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(260px,.8fr)]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-white/10 bg-[#18191d] p-5 sm:p-6">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[.18em] text-blue-300">01 · Provider</p>
                        <h2 class="mt-2 text-lg font-semibold text-white">Choose an AI provider</h2>
                        <p class="mt-1 text-sm text-zinc-400">This provider powers the assistant and all four website builder specialists.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="ai-provider-card flex cursor-pointer gap-4 rounded-xl border border-white/10 bg-black/10 p-4 transition hover:border-white/20">
                            <input class="mt-1 accent-blue-400" type="radio" name="agent[provider]" value="google" <?= $agent_provider === 'google' ? 'checked' : '' ?>>
                            <span class="min-w-0">
                                <span class="flex items-center gap-2 font-medium text-white"><span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-400/10 text-blue-300"><i class="bi bi-stars"></i></span> Google Gemini</span>
                                <span class="mt-3 block text-xs leading-5 text-zinc-400">Use Gemini models through Google AI Studio.</span>
                                <span class="mt-3 inline-flex items-center gap-1.5 text-xs <?= $agent_gemini_key !== '' ? 'text-emerald-300' : 'text-zinc-500' ?>"><i class="bi <?= $agent_gemini_key !== '' ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i><?= $agent_gemini_key !== '' ? 'Key saved' : 'Key not added' ?></span>
                            </span>
                        </label>
                        <label class="ai-provider-card flex cursor-pointer gap-4 rounded-xl border border-white/10 bg-black/10 p-4 transition hover:border-white/20">
                            <input class="mt-1 accent-blue-400" type="radio" name="agent[provider]" value="openai" <?= $agent_provider === 'openai' ? 'checked' : '' ?>>
                            <span class="min-w-0">
                                <span class="flex items-center gap-2 font-medium text-white"><span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-400/10 text-emerald-300"><i class="bi bi-cpu"></i></span> OpenAI</span>
                                <span class="mt-3 block text-xs leading-5 text-zinc-400">Use GPT models with an OpenAI Platform API key.</span>
                                <span class="mt-3 inline-flex items-center gap-1.5 text-xs <?= $agent_openai_key !== '' ? 'text-emerald-300' : 'text-zinc-500' ?>"><i class="bi <?= $agent_openai_key !== '' ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i><?= $agent_openai_key !== '' ? 'Key saved' : 'Key not added' ?></span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="agent-model" class="mb-2 block text-sm font-medium text-zinc-200">Model</label>
                            <select id="agent-model" name="agent[model]" class="w-full rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm">
                                <?php foreach (['google' => $gemini_models, 'openai' => $openai_models] as $provider_key => $models): ?>
                                    <?php foreach ($models as $model_id => $model_label): ?>
                                        <option value="<?= $esc($model_id) ?>" data-provider="<?= $provider_key ?>" <?= $agent_model === $model_id ? 'selected' : '' ?>><?= $esc($model_label) ?></option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1.5 text-xs text-zinc-500">Select a model available to your provider account.</p>
                        </div>
                        <div>
                            <label for="agent-style" class="mb-2 block text-sm font-medium text-zinc-200">Response style</label>
                            <select id="agent-style" name="agent[response_style]" class="w-full rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm">
                                <option value="concise" <?= $agent_style === 'concise' ? 'selected' : '' ?>>Concise</option>
                                <option value="balanced" <?= $agent_style === 'balanced' ? 'selected' : '' ?>>Balanced</option>
                                <option value="detailed" <?= $agent_style === 'detailed' ? 'selected' : '' ?>>Detailed</option>
                            </select>
                            <p class="mt-1.5 text-xs text-zinc-500">Controls how much detail the assistant returns.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-white/10 bg-[#18191d] p-5 sm:p-6">
                    <div class="mb-5">
                        <p class="text-xs font-semibold uppercase tracking-[.18em] text-blue-300">02 · API keys</p>
                        <h2 class="mt-2 text-lg font-semibold text-white">Connect your accounts</h2>
                        <p class="mt-1 text-sm text-zinc-400">You can save both keys and switch providers whenever you need.</p>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-white/10 bg-black/10 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label for="gemini-key" class="font-medium text-white">Google AI Studio key</label>
                                <?php if ($agent_gemini_key !== ''): ?><span class="text-xs text-emerald-300"><i class="bi bi-shield-check"></i> Saved securely</span><?php endif; ?>
                            </div>
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                <input id="gemini-key" type="password" name="agent[gemini_api_key]" autocomplete="new-password" placeholder="AIza…" class="min-w-0 flex-1 rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm placeholder:text-zinc-600">
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 px-3 py-2.5 text-sm text-zinc-300 transition hover:bg-white/5"><i class="bi bi-box-arrow-up-right"></i> Get Gemini key</a>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">Leave blank to keep the saved key.</p>
                        </div>

                        <div class="rounded-xl border border-white/10 bg-black/10 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label for="openai-key" class="font-medium text-white">OpenAI API key</label>
                                <?php if ($agent_openai_key !== ''): ?><span class="text-xs text-emerald-300"><i class="bi bi-shield-check"></i> Saved securely</span><?php endif; ?>
                            </div>
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                <input id="openai-key" type="password" name="agent[openai_api_key]" autocomplete="new-password" placeholder="sk-…" class="min-w-0 flex-1 rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm placeholder:text-zinc-600">
                                <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 px-3 py-2.5 text-sm text-zinc-300 transition hover:bg-white/5"><i class="bi bi-box-arrow-up-right"></i> Get OpenAI key</a>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">Use an OpenAI Platform API key. A ChatGPT login or subscription is not an API key. Leave blank to keep the saved key.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-white/10 bg-[#18191d] p-5 sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-blue-300">03 · Assistant</p>
                    <h2 class="mt-2 text-lg font-semibold text-white">Name and behavior</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="assistant-name" class="mb-2 block text-sm font-medium text-zinc-200">Assistant name</label>
                            <input id="assistant-name" type="text" name="agent[assistant_name]" value="<?= $esc($agent_name) ?>" class="w-full rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label for="assistant-role" class="mb-2 block text-sm font-medium text-zinc-200">Short description</label>
                            <input id="assistant-role" type="text" name="agent[assistant_role]" value="<?= $esc($agent_role) ?>" class="w-full rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm">
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="agent-temperature" class="mb-2 block text-sm font-medium text-zinc-200">Creativity</label>
                            <input id="agent-temperature" type="number" step="0.1" min="0" max="1" name="agent[temperature]" value="<?= $esc($agent_temperature) ?>" class="w-full rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm">
                            <p class="mt-1.5 text-xs text-zinc-500">Lower is more focused; higher is more varied.</p>
                        </div>
                        <div>
                            <label for="agent-tokens" class="mb-2 block text-sm font-medium text-zinc-200">Response limit</label>
                            <input id="agent-tokens" type="number" min="128" max="8192" name="agent[max_output_tokens]" value="<?= $esc($agent_max_tokens) ?>" class="w-full rounded-xl border border-white/10 bg-[#101114] px-3 py-2.5 text-sm">
                            <p class="mt-1.5 text-xs text-zinc-500">Maximum generated tokens for assistant replies.</p>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-blue-300/15 bg-gradient-to-br from-blue-400/10 to-transparent p-5">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-300/15 text-lg text-blue-200"><i class="bi bi-diagram-3"></i></div>
                    <h2 class="mt-4 font-semibold text-white">Your AI team</h2>
                    <p class="mt-1 text-sm leading-6 text-zinc-400">The website builder coordinates four specialist agents. They share the provider and model selected here.</p>
                    <ul class="mt-5 space-y-3 text-sm text-zinc-300">
                        <li class="flex gap-3"><i class="bi bi-compass text-blue-300"></i><span><strong class="font-medium text-white">Brand strategist</strong><small class="mt-0.5 block text-xs text-zinc-500">Audience and direction</small></span></li>
                        <li class="flex gap-3"><i class="bi bi-layout-wtf text-blue-300"></i><span><strong class="font-medium text-white">Experience designer</strong><small class="mt-0.5 block text-xs text-zinc-500">Structure and visual system</small></span></li>
                        <li class="flex gap-3"><i class="bi bi-chat-square-text text-blue-300"></i><span><strong class="font-medium text-white">Copywriter</strong><small class="mt-0.5 block text-xs text-zinc-500">Storefront messaging</small></span></li>
                        <li class="flex gap-3"><i class="bi bi-code-slash text-blue-300"></i><span><strong class="font-medium text-white">Frontend engineer</strong><small class="mt-0.5 block text-xs text-zinc-500">Responsive page implementation</small></span></li>
                    </ul>
                </section>

                <section class="rounded-2xl border border-white/10 bg-[#18191d] p-5">
                    <h2 class="font-semibold text-white">Current setup</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3"><dt class="text-zinc-500">Provider</dt><dd id="current-provider" class="text-right text-zinc-200"><?= $agent_provider === 'openai' ? 'OpenAI' : 'Google Gemini' ?></dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-zinc-500">Model</dt><dd id="current-model" class="text-right text-zinc-200"><?= $esc($agent_model) ?></dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-zinc-500">Connection</dt><dd id="current-connection" class="text-right <?= $agent_connected ? 'text-emerald-300' : 'text-amber-300' ?>"><?= $agent_connected ? 'Ready' : 'Needs API key' ?></dd></div>
                    </dl>
                    <p class="mt-4 border-t border-white/10 pt-4 text-xs leading-5 text-zinc-500">API keys are sent to the selected provider only when you use the assistant or website builder.</p>
                </section>
            </aside>
        </div>
    </form>
</div>

<script>
(() => {
    const model = document.getElementById('agent-model');
    const providers = document.querySelectorAll('input[name="agent[provider]"]');
    if (!model || !providers.length) return;
    const providerLabel = document.getElementById('current-provider');
    const modelLabel = document.getElementById('current-model');
    const connectionLabel = document.getElementById('current-connection');
    const updateModels = () => {
        const provider = document.querySelector('input[name="agent[provider]"]:checked')?.value || 'google';
        const options = [...model.options].filter(option => option.dataset.provider === provider);
        const current = model.value;
        model.querySelectorAll('option').forEach(option => { option.hidden = option.dataset.provider !== provider; });
        if (!options.some(option => option.value === current)) model.value = options[0]?.value || '';
        if (providerLabel) providerLabel.textContent = provider === 'openai' ? 'OpenAI' : 'Google Gemini';
        if (modelLabel) modelLabel.textContent = model.value;
        if (connectionLabel) {
            const keyInput = document.getElementById(provider === 'openai' ? 'openai-key' : 'gemini-key');
            const saved = document.querySelector('.ai-provider-card input[value="' + provider + '"]')?.closest('.ai-provider-card')?.textContent.includes('Key saved');
            const connected = Boolean(saved || keyInput?.value.trim());
            connectionLabel.textContent = connected ? 'Ready' : 'Needs API key';
            connectionLabel.className = 'text-right ' + (connected ? 'text-emerald-300' : 'text-amber-300');
        }
    };
    providers.forEach(input => input.addEventListener('change', updateModels));
    model.addEventListener('change', updateModels);
    document.getElementById('gemini-key')?.addEventListener('input', updateModels);
    document.getElementById('openai-key')?.addEventListener('input', updateModels);
    updateModels();
})();
</script>
