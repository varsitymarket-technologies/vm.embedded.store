<?php
$site_dir = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__;
$agent_path = $site_dir . "/ai_agent.config.enc";

function vm_ai_agent_encrypt_key()
{
    return create_enc_key();
}

function vm_ai_agent_config_path()
{
    return dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/ai_agent.config.enc";
}

function vm_ai_agent_load_config()
{
    $path = vm_ai_agent_config_path();
    if (!file_exists($path)) {
        return [];
    }
    $encrypted = file_get_contents($path);
    $json = __decryption__($encrypted, vm_ai_agent_encrypt_key());
    return json_decode($json, true) ?: [];
}

function vm_ai_agent_save_config(array $config)
{
    $path = vm_ai_agent_config_path();
    if (!file_exists(dirname($path))) {
        @mkdir(dirname($path), 0777, true);
    }
    $json = json_encode($config);
    $encrypted = __encryption__($json, vm_ai_agent_encrypt_key());
    file_put_contents($path, $encrypted);
}

function vm_ai_agent_get_thread_key()
{
    return 'vm_ai_agent_thread_' . (__DOMAIN__ ?? 'default');
}

function vm_ai_agent_history()
{
    $key = vm_ai_agent_get_thread_key();
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    return $_SESSION[$key];
}

function vm_ai_agent_save_history(array $history)
{
    $key = vm_ai_agent_get_thread_key();
    $_SESSION[$key] = array_slice($history, -20);
}

$agent_config = array_merge([
    'enabled' => '1',
    'admin_only' => '1',
    'assistant_name' => 'Store Copilot',
    'assistant_role' => 'Admin-only operations assistant',
    'provider' => 'openai',
    'model' => 'gpt-4o-mini',
    'openai_api_key' => '',
    'openai_org_id' => '',
    'temperature' => '0.2',
    'max_output_tokens' => '1200',
    'response_style' => 'concise',
    'system_prompt' => "You are the store's internal admin assistant. Help with operations, reporting, inventory, orders, customers, settings, and publishing. Never act on public storefront requests. Ask before destructive actions and summarize the impact before making changes.",
    'mcp_enabled' => '1',
    'mcp_server_name' => 'vm-admin-mcp',
    'mcp_server_url' => '',
    'mcp_transport' => 'http',
    'mcp_auth_header' => 'X-MCP-Key',
    'allowed_scopes' => ['orders', 'products', 'customers', 'settings'],
], vm_ai_agent_load_config() ?: []);

function vm_ai_agent_call_openai(array $agent_config, array $history, string $user_message): array
{
    $api_key = trim($agent_config['openai_api_key'] ?? '');
    if ($api_key === '') {
        return ['ok' => false, 'reply' => 'Connect OpenAI in Settings to chat here.'];
    }

    $provider = strtolower($agent_config['provider'] ?? 'openai');
    if ($provider !== 'openai' && $provider !== 'mcp') {
        return ['ok' => false, 'reply' => 'This assistant is not connected to OpenAI yet.'];
    }

    $messages = [
        [
            'role' => 'system',
            'content' => trim((string)($agent_config['system_prompt'] ?? ''))
        ]
    ];

    foreach (array_slice($history, -12) as $entry) {
        if (empty($entry['role']) || empty($entry['content'])) {
            continue;
        }
        $messages[] = [
            'role' => $entry['role'],
            'content' => $entry['content']
        ];
    }

    $payload = [
        'model' => $agent_config['model'] ?: 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => (float)($agent_config['temperature'] ?? 0.2),
        'max_tokens' => (int)($agent_config['max_output_tokens'] ?? 1200),
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ];
    $org = trim($agent_config['openai_org_id'] ?? '');
    if ($org !== '') {
        $headers[] = 'OpenAI-Organization: ' . $org;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'reply' => 'OpenAI request failed: ' . $curl_error];
    }

    $decoded = json_decode($response, true);
    if ($http_code < 200 || $http_code >= 300) {
        $error_message = $decoded['error']['message'] ?? ('OpenAI returned HTTP ' . $http_code);
        return ['ok' => false, 'reply' => $error_message];
    }

    $reply = $decoded['choices'][0]['message']['content'] ?? '';
    if ($reply === '') {
        $reply = 'OpenAI returned an empty response.';
    }

    return ['ok' => true, 'reply' => $reply];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $history = vm_ai_agent_history();

    if ($action === 'clear_ai_agent_chat') {
        vm_ai_agent_save_history([]);
        header('Location: ?cleared=1');
        exit;
    }

    if ($action === 'send_ai_agent_message') {
        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $history[] = [
                'role' => 'user',
                'content' => $message,
                'time' => time(),
            ];

            $reply_result = vm_ai_agent_call_openai($agent_config, $history, $message);
            $history[] = [
                'role' => 'assistant',
                'content' => $reply_result['reply'],
                'time' => time(),
                'error' => !$reply_result['ok'],
            ];

            vm_ai_agent_save_history($history);
        }

        header('Location: ?sent=1');
        exit;
    }
}

$history = vm_ai_agent_history();
$assistant_name = $agent_config['assistant_name'] ?? 'Store Copilot';
$assistant_role = $agent_config['assistant_role'] ?? 'Admin-only operations assistant';
$agent_enabled = ($agent_config['enabled'] ?? '1') === '1';
$agent_openai_connected = !empty($agent_config['openai_api_key'] ?? '');
$agent_model = $agent_config['model'] ?? 'gpt-4o-mini';
$agent_scopes = $agent_config['allowed_scopes'] ?? [];
?>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#09090b] text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto">
        <div class="px-8 pt-8 pb-6">
            <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold tracking-tight">AI Agent</h2>
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-wider <?= $agent_enabled ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' : 'border-zinc-700 bg-zinc-800 text-zinc-400' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $agent_enabled ? 'bg-emerald-400' : 'bg-zinc-500' ?>"></span>
                            <?= $agent_enabled ? 'Enabled' : 'Disabled' ?>
                        </span>
                    </div>
                    <p class="text-sm text-zinc-500 mt-1">Direct chat workspace for admin-only assistant tasks</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <a href="/vm-admin/<?php echo __DOMAIN__; ?>/settings?tab=agent" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700/60 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="bi bi-gear"></i>
                        Configure
                    </a>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="clear_ai_agent_chat">
                        <button type="submit" class="inline-flex items-center gap-2 bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-zinc-300 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="bi bi-trash3"></i>
                            Clear chat
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="px-8 pb-8">
            <div class="grid grid-cols-1 xl:grid-cols-[1.5fr_0.85fr] gap-5">
                <section class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden flex flex-col min-h-[70vh]">
                    <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-white font-semibold text-sm"><?= htmlspecialchars($assistant_name, ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="text-zinc-500 text-xs mt-0.5"><?= htmlspecialchars($assistant_role, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] uppercase tracking-wider font-semibold">
                            <span class="px-2.5 py-1 rounded-full <?= $agent_openai_connected ? 'bg-cyan-500/10 text-cyan-300 border border-cyan-500/20' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20' ?>">
                                <?= $agent_openai_connected ? 'OpenAI connected' : 'Needs connection' ?>
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-zinc-800 text-zinc-300 border border-zinc-700/60"><?= htmlspecialchars($agent_model, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-5 space-y-4 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.08),_transparent_40%)]">
                        <?php if (empty($history)): ?>
                            <div class="rounded-2xl border border-cyan-500/15 bg-cyan-500/5 p-5">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-300">
                                        <i class="bi bi-robot"></i>
                                    </div>
                                    <div>
                                        <p class="text-white text-sm font-medium">Start a conversation</p>
                                        <p class="text-zinc-500 text-sm mt-1">Ask for order summaries, product checks, customer lookup, settings guidance, or MCP tool planning.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($history as $entry): ?>
                            <?php
                                $role = $entry['role'] ?? 'assistant';
                                $content = $entry['content'] ?? '';
                                $time = isset($entry['time']) ? date('g:i A', (int)$entry['time']) : '';
                                $is_error = !empty($entry['error']);
                            ?>
                            <div class="flex <?php echo $role === 'user' ? 'justify-end' : 'justify-start'; ?>">
                                <div class="max-w-[85%] rounded-2xl px-4 py-3 border <?php echo $role === 'user' ? 'bg-cyan-500 text-black border-cyan-400/30 rounded-tr-sm' : ($is_error ? 'bg-rose-500/10 text-rose-200 border-rose-500/20 rounded-tl-sm' : 'bg-zinc-800 text-zinc-200 border-zinc-700/60 rounded-tl-sm'); ?>">
                                    <div class="whitespace-pre-wrap text-sm leading-relaxed"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="mt-2 text-[10px] uppercase tracking-wider <?php echo $role === 'user' ? 'text-black/60' : 'text-zinc-500'; ?>">
                                        <?php echo $role === 'user' ? 'You' : ($is_error ? 'Connection issue' : $assistant_name); ?>
                                        <?php if ($time): ?>
                                            <span class="mx-1">·</span><?php echo $time; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t border-zinc-800 p-4">
                        <form method="POST" class="space-y-3">
                            <input type="hidden" name="action" value="send_ai_agent_message">
                            <textarea
                                name="message"
                                rows="3"
                                class="w-full resize-none rounded-xl bg-zinc-950 border border-zinc-700 px-4 py-3 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-cyan-500 transition-colors"
                                placeholder="Ask the assistant to review orders, look up customers, summarize sales, or explain a setting..."
                            ></textarea>
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <p class="text-xs text-zinc-500">Replies appear here directly. No popup messages.</p>
                                <button type="submit" class="inline-flex items-center gap-2 bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    <i class="bi bi-send"></i>
                                    Send
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <aside class="space-y-5">
                    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-zinc-800">
                            <h3 class="text-white font-semibold text-sm">Connection Status</h3>
                            <p class="text-zinc-500 text-xs mt-0.5">Use the settings page to connect OpenAI</p>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-zinc-500 text-xs uppercase tracking-wider">Provider</span>
                                <span class="text-white text-sm font-medium"><?= htmlspecialchars($agent_config['provider'] ?? 'openai', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-zinc-500 text-xs uppercase tracking-wider">Model</span>
                                <span class="text-white text-sm font-medium"><?= htmlspecialchars($agent_model, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-zinc-500 text-xs uppercase tracking-wider">MCP</span>
                                <span class="text-white text-sm font-medium"><?= ($agent_config['mcp_enabled'] ?? '1') === '1' ? 'Enabled' : 'Disabled' ?></span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-zinc-500 text-xs uppercase tracking-wider">Scopes</span>
                                <span class="text-white text-sm font-medium"><?= count($agent_scopes) ?></span>
                            </div>

                            <?php if (!$agent_openai_connected): ?>
                                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                                    <p class="text-amber-300 text-sm font-medium">Not connected yet</p>
                                    <p class="text-zinc-400 text-xs mt-1">Go to Settings and paste an OpenAI API key to activate live responses.</p>
                                    <a href="/vm-admin/<?php echo __DOMAIN__; ?>/settings?tab=agent" class="inline-flex items-center gap-2 mt-3 text-xs bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 px-3 py-2 rounded-lg transition-colors">
                                        <i class="bi bi-plug"></i> Connect ChatGPT
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-4">
                                    <p class="text-cyan-300 text-sm font-medium">Live mode active</p>
                                    <p class="text-zinc-400 text-xs mt-1">Messages are sent to OpenAI and the answer is rendered inline here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-zinc-800">
                            <h3 class="text-white font-semibold text-sm">What this page does</h3>
                        </div>
                        <div class="p-5 space-y-3 text-sm text-zinc-400 leading-relaxed">
                            <p>1. Keeps the assistant visible as a real chat workspace.</p>
                            <p>2. Uses your configured OpenAI API key for direct replies.</p>
                            <p>3. Stores the conversation in your admin session so you can keep working.</p>
                            <p>4. Leaves the storefront alone because this is admin-only.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>
