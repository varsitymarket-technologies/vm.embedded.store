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
$agent_quick_prompts = [
    [
        'label' => 'Summarize today',
        'prompt' => 'Summarize today\'s most important store activity in a short admin briefing.',
    ],
    [
        'label' => 'Check inventory',
        'prompt' => 'Review the inventory state and call out any products that need attention.',
    ],
    [
        'label' => 'Customer lookup',
        'prompt' => 'Help me find the right customer record and explain what data to check first.',
    ],
    [
        'label' => 'Publishing help',
        'prompt' => 'Walk me through the safest way to publish changes without breaking the storefront.',
    ],
];
$agent_notice = '';
if (isset($_GET['sent'])) {
    $agent_notice = 'Message sent.';
} elseif (isset($_GET['cleared'])) {
    $agent_notice = 'Chat cleared.';
}
?>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#09090b] text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex h-full w-full max-w-6xl flex-col gap-4">
            <section class="rounded-[1rem] border border-white/10 bg-white/[0.03] px-5 py-4 shadow-2xl shadow-black/20 backdrop-blur-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl font-semibold tracking-tight text-white sm:text-2xl"><?= htmlspecialchars($assistant_name, ENT_QUOTES, 'UTF-8') ?></h2>
                            
                        </div>
                        <p class="max-w-2xl text-sm text-zinc-500"><?= htmlspecialchars($assistant_role, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.18em]">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openAgentModal('agentPromptModal')" class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-zinc-200 transition-colors hover:border-cyan-500/30 hover:bg-[#ffffffc2]/10 hover:text-cyan-200">
                                Prompts
                            </button>
                            <button type="button" onclick="openAgentModal('agentStatusModal')" class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-zinc-200 transition-colors hover:border-white/20 hover:bg-white/10">
                                Status
                            </button>
                        </div>
                    </div>
                </div>
                <?php if ($agent_notice !== ''): ?>
                    <p class="mt-3 text-xs text-zinc-400"><?= htmlspecialchars($agent_notice, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </section>

            <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-[1rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/30">
                <div id="agentChatScroll" class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-6">
                    <div class="mx-auto flex max-w-3xl flex-col gap-4">
                        <?php if (empty($history)): ?>
                            <div class="rounded-[1.5rem] border border-cyan-500/15 bg-[#ffffffc2]/5 px-5 py-5">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-cyan-500/20 bg-[#ffffffc2]/10 text-cyan-300">
                                        <i class="bi bi-robot"></i>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-sm font-medium text-white">Start a conversation</p>
                                        <p class="text-sm leading-relaxed text-zinc-400">Ask for order summaries, product checks, customer lookup, publishing guidance, or a safe next step for admin work.</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <?php foreach ($agent_quick_prompts as $prompt): ?>
                                        <button type="button" onclick="setAgentPrompt(<?= htmlspecialchars(json_encode($prompt['prompt']), ENT_QUOTES, 'UTF-8') ?>)" class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-zinc-300 transition-colors hover:border-cyan-500/30 hover:bg-[#ffffffc2]/10 hover:text-cyan-200">
                                            <?= htmlspecialchars($prompt['label'], ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    <?php endforeach; ?>
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
                            <div class="flex <?= $role === 'user' ? 'justify-end' : 'justify-start' ?>">
                                <div class="max-w-[88%] rounded-[1.5rem] border px-4 py-3 sm:max-w-[78%] <?= $role === 'user' ? 'rounded-br-md border-cyan-400/30 bg-[#ffffffc2] text-black' : ($is_error ? 'rounded-bl-md border-rose-500/20 bg-[#272624a8] text-rose-100' : 'rounded-bl-md border-zinc-800 bg-zinc-900 text-zinc-200') ?>">
                                    <div class="whitespace-pre-wrap text-sm leading-relaxed"><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="mt-2 text-[10px] uppercase tracking-[0.22em] <?= $role === 'user' ? 'text-black/60' : 'text-zinc-500' ?>">
                                        <?= $role === 'user' ? 'You' : ($is_error ? 'Connection issue' : $assistant_name) ?>
                                        <?php if ($time): ?>
                                            <span class="mx-1">·</span><?= $time ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="border-t border-white/5 px-4 py-4 sm:px-6">
                    <form method="POST" class="mx-auto max-w-3xl space-y-3">
                        <input type="hidden" name="action" value="send_ai_agent_message">
                        <textarea
                            id="agentMessage"
                            name="message"
                            rows="3"
                            class="w-full resize-none rounded-[1.25rem] border border-white/10 bg-[#07070a] px-4 py-3 text-sm text-white placeholder:text-zinc-600 focus:border-cyan-500/50 focus:outline-none"
                            placeholder="Ask the assistant to review orders, look up a customer, summarize sales, or explain a setting..."></textarea>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-zinc-500">Use the modal options for quick actions and status.</p>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="openAgentModal('agentOptionsModal')" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-zinc-200 transition-colors hover:border-white/20 hover:bg-white/10">
                                    Options
                                </button>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#ffffffc2] px-4 py-2 text-sm font-medium text-black transition-colors hover:bg-cyan-400">
                                    <i class="bi bi-send"></i>
                                    Send
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <div id="agentOptionsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="agentOptionsTitle">
        <div class="absolute inset-0" onclick="closeAgentModal('agentOptionsModal')"></div>
        <div class="relative w-full max-w-lg rounded-[2rem] border border-white/10 bg-[#111115] p-5 shadow-2xl shadow-black/50">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="agentOptionsTitle" class="text-lg font-semibold text-white">Options</h3>
                    <p class="mt-1 text-sm text-zinc-500">Quick actions live here instead of the old sidebar.</p>
                </div>
                <button type="button" onclick="closeAgentModal('agentOptionsModal')" class="rounded-full border border-white/10 bg-white/5 p-2 text-zinc-400 transition-colors hover:text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="mt-5 grid gap-3">
                <button type="button" onclick="closeAgentModal('agentOptionsModal'); openAgentModal('agentStatusModal');" class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4 text-left transition-colors hover:border-cyan-500/20 hover:bg-[#ffffffc2]/5">
                    <span>
                        <span class="block text-sm font-medium text-white">View status</span>
                        <span class="mt-1 block text-xs text-zinc-500">See provider, model, and scope details.</span>
                    </span>
                    <i class="bi bi-chevron-right text-zinc-500"></i>
                </button>
                <button type="button" onclick="closeAgentModal('agentOptionsModal'); openAgentModal('agentPromptModal');" class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4 text-left transition-colors hover:border-cyan-500/20 hover:bg-[#ffffffc2]/5">
                    <span>
                        <span class="block text-sm font-medium text-white">Quick prompts</span>
                        <span class="mt-1 block text-xs text-zinc-500">Drop a ready-made prompt into the composer.</span>
                    </span>
                    <i class="bi bi-chevron-right text-zinc-500"></i>
                </button>
                <a href="/vm-admin/<?php echo __DOMAIN__; ?>/settings?tab=agent" class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4 text-left transition-colors hover:border-white/20 hover:bg-white/10">
                    <span>
                        <span class="block text-sm font-medium text-white">Open settings</span>
                        <span class="mt-1 block text-xs text-zinc-500">Adjust keys, prompts, and MCP options.</span>
                    </span>
                    <i class="bi bi-gear text-zinc-500"></i>
                </a>
                <form method="POST" onsubmit="closeAgentModal('agentOptionsModal')" class="rounded-2xl border border-rose-500/15 bg-rose-500/5">
                    <input type="hidden" name="action" value="clear_ai_agent_chat">
                    <button type="submit" class="flex w-full items-center justify-between px-4 py-4 text-left transition-colors hover:bg-[#272624a8]">
                        <span>
                            <span class="block text-sm font-medium text-rose-100">Clear chat</span>
                            <span class="mt-1 block text-xs text-rose-200/70">Remove the current conversation from this session.</span>
                        </span>
                        <i class="bi bi-trash3 text-rose-200/70"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="agentStatusModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="agentStatusTitle">
        <div class="absolute inset-0" onclick="closeAgentModal('agentStatusModal')"></div>
        <div class="relative w-full max-w-xl rounded-[2rem] border border-white/10 bg-[#111115] p-5 shadow-2xl shadow-black/50">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="agentStatusTitle" class="text-lg font-semibold text-white">Connection status</h3>
                    <p class="mt-1 text-sm text-zinc-500">A compact readout of the agent configuration.</p>
                </div>
                <button type="button" onclick="closeAgentModal('agentStatusModal')" class="rounded-full border border-white/10 bg-white/5 p-2 text-zinc-400 transition-colors hover:text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Provider</p>
                    <p class="mt-2 text-sm font-medium text-white"><?= htmlspecialchars($agent_config['provider'] ?? 'openai', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Model</p>
                    <p class="mt-2 text-sm font-medium text-white"><?= htmlspecialchars($agent_model, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">MCP</p>
                    <p class="mt-2 text-sm font-medium text-white"><?= ($agent_config['mcp_enabled'] ?? '1') === '1' ? 'Enabled' : 'Disabled' ?></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Scopes</p>
                    <p class="mt-2 text-sm font-medium text-white"><?= count($agent_scopes) ?></p>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border <?= $agent_openai_connected ? 'border-cyan-500/20 bg-[#ffffffc2]/5' : 'border-amber-500/20 bg-amber-500/5' ?> p-4">
                <p class="<?= $agent_openai_connected ? 'text-cyan-300' : 'text-amber-300' ?> text-sm font-medium"><?= $agent_openai_connected ? 'OpenAI connected' : 'No API key connected' ?></p>
                <p class="mt-1 text-sm text-zinc-400"><?= $agent_openai_connected ? 'Messages are sent live and the response is rendered in the chat panel.' : 'Connect an API key in Settings to enable live responses.' ?></p>
            </div>
        </div>
    </div>

    <div id="agentPromptModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="agentPromptTitle">
        <div class="absolute inset-0" onclick="closeAgentModal('agentPromptModal')"></div>
        <div class="relative w-full max-w-lg rounded-[2rem] border border-white/10 bg-[#111115] p-5 shadow-2xl shadow-black/50">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="agentPromptTitle" class="text-lg font-semibold text-white">Quick prompts</h3>
                    <p class="mt-1 text-sm text-zinc-500">Click one to drop it into the chat composer.</p>
                </div>
                <button type="button" onclick="closeAgentModal('agentPromptModal')" class="rounded-full border border-white/10 bg-white/5 p-2 text-zinc-400 transition-colors hover:text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="mt-5 grid gap-3">
                <?php foreach ($agent_quick_prompts as $prompt): ?>
                    <button type="button" onclick="setAgentPrompt(<?= htmlspecialchars(json_encode($prompt['prompt']), ENT_QUOTES, 'UTF-8') ?>)" class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4 text-left transition-colors hover:border-cyan-500/20 hover:bg-[#ffffffc2]/5">
                        <span class="block text-sm font-medium text-white"><?= htmlspecialchars($prompt['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mt-1 block text-xs leading-relaxed text-zinc-500"><?= htmlspecialchars($prompt['prompt'], ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function openAgentModal(id) {
            var modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAgentModal(id) {
            var modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function setAgentPrompt(prompt) {
            var composer = document.getElementById('agentMessage');
            if (!composer) return;
            composer.value = prompt;
            composer.focus();
            closeAgentModal('agentPromptModal');
        }

        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape') return;
            ['agentOptionsModal', 'agentStatusModal', 'agentPromptModal'].forEach(function(id) {
                closeAgentModal(id);
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            var scroller = document.getElementById('agentChatScroll');
            if (scroller) {
                scroller.scrollTop = scroller.scrollHeight;
            }
        });
    </script>
</div>