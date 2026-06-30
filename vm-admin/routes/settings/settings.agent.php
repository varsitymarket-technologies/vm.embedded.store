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

<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<form method="POST">
    <input type="hidden" name="action" value="save_ai_agent">

    <div class="grid grid-cols-1 xl:grid-cols-[1.6fr_0.9fr] gap-5">
        <div class="space-y-5">
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-800 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white">AI Agent</h2>
                        <p class="text-zinc-400 text-sm mt-1">Configure the internal assistant that helps your admin team</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-wider <?= $agent_enabled ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' : 'border-zinc-700 bg-zinc-800 text-zinc-400' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $agent_enabled ? 'bg-emerald-400' : 'bg-zinc-500' ?>"></span>
                            <?= $agent_enabled ? 'Enabled' : 'Disabled' ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-500/20 bg-cyan-500/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-cyan-400">
                            <i class="bi bi-robot"></i> Admin only
                        </span>
                    </div>
                </div>
                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Assistant Name</label>
                            <input type="text" name="agent[assistant_name]" value="<?= htmlspecialchars($agent_name, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors" placeholder="Store Copilot">
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Role Label</label>
                            <input type="text" name="agent[assistant_role]" value="<?= htmlspecialchars($agent_role, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors" placeholder="Admin-only operations assistant">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Provider</label>
                            <select name="agent[provider]" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors">
                                <option value="openai" <?= $agent_provider === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                                <option value="mcp" <?= $agent_provider === 'mcp' ? 'selected' : '' ?>>MCP Bridge</option>
                                <option value="custom" <?= $agent_provider === 'custom' ? 'selected' : '' ?>>Custom</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Model</label>
                            <input type="text" name="agent[model]" value="<?= htmlspecialchars($agent_model, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:border-cyan-500 transition-colors" placeholder="gpt-4o-mini">
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Response Style</label>
                            <select name="agent[response_style]" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors">
                                <option value="concise" <?= $agent_style === 'concise' ? 'selected' : '' ?>>Concise</option>
                                <option value="balanced" <?= $agent_style === 'balanced' ? 'selected' : '' ?>>Balanced</option>
                                <option value="detailed" <?= $agent_style === 'detailed' ? 'selected' : '' ?>>Detailed</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-cyan-500/5 border border-cyan-500/15 rounded-xl p-4">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div>
                                <h4 class="text-white font-medium text-sm">OpenAI / ChatGPT Connection</h4>
                                <p class="text-zinc-500 text-xs mt-0.5">Use an OpenAI API key to connect the agent to ChatGPT-style responses.</p>
                            </div>
                            <span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-full <?= $agent_openai_connected ? 'bg-emerald-500/10 text-emerald-400' : 'bg-zinc-800 text-zinc-500' ?>">
                                <?= $agent_openai_connected ? 'Connected' : 'Not connected' ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">OpenAI API Key</label>
                                <input type="password" name="agent[openai_api_key]" value="" autocomplete="new-password" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors font-mono" placeholder="<?= $agent_openai_connected ? 'Saved already, leave blank to keep' : 'sk-...' ?>">
                                <p class="text-zinc-500 text-[11px] mt-1">This is how the page connects to OpenAI. ChatGPT account login is not exposed here.</p>
                            </div>
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Organization / Project ID</label>
                                <input type="text" name="agent[openai_org_id]" value="<?= htmlspecialchars($agent_openai_org, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors font-mono" placeholder="Optional">
                                <p class="text-zinc-500 text-[11px] mt-1">Optional if your OpenAI account uses a project or org header.</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="bi bi-plug"></i>
                                <?= $agent_openai_connected ? 'Update Connection' : 'Connect ChatGPT' ?>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Temperature</label>
                            <input type="number" step="0.1" min="0" max="2" name="agent[temperature]" value="<?= htmlspecialchars($agent_temperature, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Max Output Tokens</label>
                            <input type="number" min="128" step="1" name="agent[max_output_tokens]" value="<?= htmlspecialchars($agent_max_tokens, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors">
                        </div>
                        <div class="flex flex-col gap-3 justify-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="agent[enabled]" value="0">
                                <input type="checkbox" name="agent[enabled]" value="1" class="w-4 h-4 accent-cyan-500 rounded" <?= $agent_enabled ? 'checked' : '' ?>>
                                <span class="text-zinc-300 text-xs">Enable agent</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="agent[admin_only]" value="0">
                                <input type="checkbox" name="agent[admin_only]" value="1" class="w-4 h-4 accent-cyan-500 rounded" <?= $agent_admin_only ? 'checked' : '' ?>>
                                <span class="text-zinc-300 text-xs">Admin-only access</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold text-sm">System Prompt</h3>
                    <p class="text-zinc-500 text-xs mt-0.5">This instruction keeps the assistant focused on admin operations, not the storefront</p>
                </div>
                <div class="p-5">
                    <textarea name="agent[system_prompt]" rows="10" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm font-mono leading-relaxed focus:outline-none focus:border-cyan-500 transition-colors" placeholder="Write the guardrails and behavior for your assistant..."><?= htmlspecialchars($agent_prompt, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold text-sm">MCP Tool Scope</h3>
                    <p class="text-zinc-500 text-xs mt-0.5">Choose which admin surfaces the agent can inspect through MCP or internal actions</p>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($scope_options as $key => $scope): ?>
                        <label class="flex items-start gap-3 bg-zinc-800/60 border border-zinc-700/50 rounded-xl p-3 hover:border-cyan-500/30 transition-colors cursor-pointer">
                            <input type="checkbox" name="agent[allowed_scopes][]" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-4 h-4 accent-cyan-500 rounded" <?= in_array($key, $agent_scopes, true) ? 'checked' : '' ?>>
                            <span>
                                <span class="block text-sm font-medium text-white"><?= htmlspecialchars($scope['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="block text-xs text-zinc-500 mt-0.5"><?= htmlspecialchars($scope['desc'], ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="bg-gradient-to-br from-cyan-500/15 via-zinc-900 to-zinc-950 border border-cyan-500/20 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-cyan-500/15">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/15 flex items-center justify-center text-cyan-300">
                            <i class="bi bi-magic"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold text-sm">Assistant Workspace</h3>
                            <p class="text-zinc-500 text-xs mt-0.5">This is the admin side of the agent, not the customer chat</p>
                        </div>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div class="rounded-xl border border-white/5 bg-black/20 p-4">
                        <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-500 mb-2">Preview</p>
                        <div class="space-y-3">
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-cyan-500/15 flex items-center justify-center text-cyan-300 text-xs">
                                    <i class="bi bi-robot"></i>
                                </div>
                                <div class="flex-1 rounded-2xl rounded-tl-sm bg-zinc-900 border border-zinc-800 px-3 py-2 text-sm text-zinc-200">
                                    Ready to help with store operations, safely and with MCP tool access.
                                </div>
                            </div>
                            <div class="flex gap-3 justify-end">
                                <div class="max-w-[85%] rounded-2xl rounded-tr-sm bg-cyan-500 text-black px-3 py-2 text-sm font-medium">
                                    Show me today’s orders and low-stock items.
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <span class="px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-300 text-[10px] font-semibold uppercase tracking-wider">Orders</span>
                                <span class="px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-300 text-[10px] font-semibold uppercase tracking-wider">Products</span>
                                <span class="px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-300 text-[10px] font-semibold uppercase tracking-wider">Customers</span>
                                <span class="px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-300 text-[10px] font-semibold uppercase tracking-wider">Settings</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-xl bg-zinc-900/80 border border-zinc-800 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-white text-sm font-medium">MCP Bridge</p>
                                    <p class="text-zinc-500 text-xs mt-0.5">Optional connector for external tool execution</p>
                                </div>
                                <span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-full <?= $agent_mcp_enabled ? 'bg-emerald-500/10 text-emerald-400' : 'bg-zinc-800 text-zinc-500' ?>">
                                    <?= $agent_mcp_enabled ? 'On' : 'Off' ?>
                                </span>
                            </div>
                            <div class="mt-3 space-y-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="hidden" name="agent[mcp_enabled]" value="0">
                                    <input type="checkbox" name="agent[mcp_enabled]" value="1" class="w-4 h-4 accent-cyan-500 rounded" <?= $agent_mcp_enabled ? 'checked' : '' ?>>
                                    <span class="text-zinc-300 text-xs">Enable MCP tool bridge</span>
                                </label>
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Server Name</label>
                                        <input type="text" name="agent[mcp_server_name]" value="<?= htmlspecialchars($agent_mcp_name, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors font-mono" placeholder="vm-admin-mcp">
                                    </div>
                                    <div>
                                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Server URL</label>
                                        <input type="url" name="agent[mcp_server_url]" value="<?= htmlspecialchars($agent_mcp_url, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors font-mono" placeholder="https://your-mcp-host.example.com/sse">
                                        <p class="text-zinc-500 text-[11px] mt-1">Leave blank if the agent will use a local or embedded MCP transport.</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Transport</label>
                                            <select name="agent[mcp_transport]" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors">
                                                <option value="http" <?= $agent_mcp_transport === 'http' ? 'selected' : '' ?>>HTTP / SSE</option>
                                                <option value="stdio" <?= $agent_mcp_transport === 'stdio' ? 'selected' : '' ?>>Local STDIO</option>
                                                <option value="webhook" <?= $agent_mcp_transport === 'webhook' ? 'selected' : '' ?>>Webhook</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Auth Header</label>
                                            <input type="text" name="agent[mcp_auth_header]" value="<?= htmlspecialchars($agent_mcp_header, ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-cyan-500 transition-colors font-mono" placeholder="X-MCP-Key">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-zinc-900/80 border border-zinc-800 p-4">
                            <p class="text-white text-sm font-medium mb-2">Guardrails</p>
                            <ul class="space-y-2 text-xs text-zinc-400 leading-relaxed list-disc list-inside">
                                <li>This assistant is limited to admin workflows, not storefront visitor chats.</li>
                                <li>Use MCP for tool access when you want the agent to inspect or act on store data.</li>
                                <li>Keep destructive actions behind confirmation prompts or server-side approval checks.</li>
                                <li>Start with read-only scopes, then expand only what the assistant truly needs.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold text-sm">Quick Setup Checklist</h3>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-cyan-500/10 text-cyan-300 flex items-center justify-center text-[10px] font-bold mt-0.5">1</span>
                        <div>
                            <p class="text-white text-sm font-medium">Define the model</p>
                            <p class="text-zinc-500 text-xs mt-0.5">Set the provider, model, and output limits for the admin assistant.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-cyan-500/10 text-cyan-300 flex items-center justify-center text-[10px] font-bold mt-0.5">2</span>
                        <div>
                            <p class="text-white text-sm font-medium">Tune the prompt</p>
                            <p class="text-zinc-500 text-xs mt-0.5">Tell the assistant what it may and may not do inside the admin.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-cyan-500/10 text-cyan-300 flex items-center justify-center text-[10px] font-bold mt-0.5">3</span>
                        <div>
                            <p class="text-white text-sm font-medium">Connect MCP tools</p>
                            <p class="text-zinc-500 text-xs mt-0.5">Point the agent at an MCP server or local bridge for safe tool access.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 flex justify-end">
        <button type="submit" class="bg-cyan-600 hover:bg-cyan-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
            Save AI Agent Settings
        </button>
    </div>
</form>
