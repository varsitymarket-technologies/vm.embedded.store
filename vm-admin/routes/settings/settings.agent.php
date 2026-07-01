<?php
$agent_name = $agent_current['assistant_name'] ?? 'Store Copilot';
$agent_role = $agent_current['assistant_role'] ?? 'Admin-only operations assistant';
$agent_provider = $agent_current['provider'] ?? 'openai';
$agent_model = $agent_current['model'] ?? 'gpt-5';
$agent_openai_key = $agent_current['openai_api_key'] ?? '';
$agent_openai_org = $agent_current['openai_org_id'] ?? '';
$agent_temperature = $agent_current['temperature'] ?? '0.2';
$agent_max_tokens = $agent_current['max_output_tokens'] ?? '1200';
$agent_style = $agent_current['response_style'] ?? 'concise';
$agent_prompt = $agent_current['system_prompt'] ?? '';
$agent_enabled = ($agent_current['enabled'] ?? '1') === '1';
$agent_admin_only = ($agent_current['admin_only'] ?? '1') === '1';
$agent_mcp_enabled = ($agent_current['mcp_enabled'] ?? '1') === '1';
$agent_mcp_name = $agent_current['mcp_server_name'] ?? 'vm-admin-mcp';
$agent_mcp_url = $agent_current['mcp_server_url'] ?? '';
$agent_mcp_transport = $agent_current['mcp_transport'] ?? 'http';
$agent_mcp_header = $agent_current['mcp_auth_header'] ?? 'X-MCP-Key';
$agent_scopes = $agent_current['allowed_scopes'] ?? ['orders', 'products', 'customers', 'settings'];
$agent_openai_connected = !empty($agent_openai_key);

$scope_options = [
    'orders' => ['label' => 'Orders', 'desc' => 'Review, summarize, and help manage order workflows'],
    'products' => ['label' => 'Products', 'desc' => 'Inspect inventory, pricing, and catalog changes'],
    'customers' => ['label' => 'Customers', 'desc' => 'Search profiles, notes, and customer history'],
    'settings' => ['label' => 'Settings', 'desc' => 'Read and explain admin configuration safely'],
    'theme' => ['label' => 'Themes', 'desc' => 'Assist with theme selection and layout guidance'],
    'analytics' => ['label' => 'Analytics', 'desc' => 'Summarize performance and usage signals'],
    'payments' => ['label' => 'Payments', 'desc' => 'Help with gateway setup and payment status'],
    'support' => ['label' => 'Support', 'desc' => 'Draft replies and internal action plans'],
];
?>

<style>
    input[type="text"],
    input[type="url"],
    input[type="number"],
    input[type="password"],
    select,
    textarea {
        background-color: #222424 !important;
        /* Indigo-600 */
    }
</style>
<!-- Shopify-style Minimal AI Agent Settings -->
<a href="?tab=general" class="inline-flex items-center gap-2 text-gray-500 hover:text-white-900 text-sm mb-6">
    <i class="bi bi-chevron-left"></i>
    Back to Settings
</a>

<form method="POST">
    <input type="hidden" name="action" value="save_ai_agent">

    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-white-900">AI Agent</h1>
                <p class="text-sm text-gray-500 mt-1">Configure your internal AI assistant</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $agent_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                    <?= $agent_enabled ? 'Enabled' : 'Disabled' ?>
                </span>

                <button type="submit"
                    class="px-4 py-2 bg-black text-white rounded-lg text-sm font-medium hover:opacity-90">
                    Save Changes
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[2fr_1fr] gap-6">

            <!-- Main -->
            <div class="space-y-6">

                <div class="bg-gray-800 border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-xl">
                    <div class="p-5 border-b border-white/5 p-5 transition-all duration-200 hover:border-white/10">
                        <h2 class="font-semibold text-white-900">General</h2>
                    </div>

                    <div class="p-5 space-y-5">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-2">Assistant Name</label>
                                <input type="text"
                                    name="agent[assistant_name]"
                                    value="<?= htmlspecialchars($agent_name, ENT_QUOTES, 'UTF-8') ?>"
                                    class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-2">Role</label>
                                <input type="text"
                                    name="agent[assistant_role]"
                                    value="<?= htmlspecialchars($agent_role, ENT_QUOTES, 'UTF-8') ?>"
                                    class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-2">Provider</label>
                                <select name="agent[provider]" class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                                    <option value="openai" <?= $agent_provider === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                                    <option value="mcp" <?= $agent_provider === 'mcp' ? 'selected' : '' ?>>MCP</option>
                                    <option value="custom" <?= $agent_provider === 'custom' ? 'selected' : '' ?>>Custom</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-2">Model</label>
                                <input type="text"
                                    name="agent[model]"
                                    value="<?= htmlspecialchars($agent_model, ENT_QUOTES, 'UTF-8') ?>"
                                    class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-2">Response Style</label>
                                <select name="agent[response_style]" class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                                    <option value="concise">Concise</option>
                                    <option value="balanced">Balanced</option>
                                    <option value="detailed">Detailed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-xl">
                    <div class="p-5 border-b border-white/5 p-5 transition-all duration-200 hover:border-white/10">
                        <h2 class="font-semibold text-white-900">OpenAI Connection</h2>
                    </div>

                    <div class="p-5 grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-2">API Key</label>
                            <input type="password"
                                name="agent[openai_api_key]"
                                class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2"
                                placeholder="sk-...">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Organization / Project</label>
                            <input type="text"
                                name="agent[openai_org_id]"
                                value="<?= htmlspecialchars($agent_openai_org, ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-xl">
                    <div class="p-5 border-b border-white/5 p-5 transition-all duration-200 hover:border-white/10">
                        <h2 class="font-semibold text-white-900">System Prompt</h2>
                    </div>

                    <div class="p-5">
                        <textarea rows="12"
                            name="agent[system_prompt]"
                            class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-3 font-mono text-sm"><?= htmlspecialchars($agent_prompt, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <div class="bg-gray-800 border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-xl p-5">
                    <h3 class="font-semibold text-white-900 mb-4">Model Settings</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Temperature</label>
                            <input type="number"
                                step="0.1"
                                name="agent[temperature]"
                                value="<?= htmlspecialchars($agent_temperature, ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Max Tokens</label>
                            <input type="number"
                                name="agent[max_output_tokens]"
                                value="<?= htmlspecialchars($agent_max_tokens, ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 border border-white/5 p-5 transition-all duration-200 hover:border-white/10 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-600 mb-2">Quick Setup</h3>
                    <ol class="space-y-2 text-sm text-gray-600">
                        <li>1. Select a model</li>
                        <li>2. Add API credentials</li>
                        <li>3. Configure prompt</li>
                        <li>4. Enable assistant</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
</form>