<?php
#   TITLE   : AI Website Builder
#   DESC    : Split-panel AI website builder — live preview + prompt interface.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 0.2.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/08/25

$store_name = website_data('name') ?: 'Untitled Store';
$store_domain = website_data('domain') ?: '';
$store_theme = website_data('theme') ?: 'default';
$ai_enabled = filter_var($_SERVER['ai_enabled'] ?? false, FILTER_VALIDATE_BOOL);
$project_root = dirname(dirname(dirname(__FILE__)));
$site_root = $project_root . '/sites/' . __DOMAIN__;
$builder_cache = $site_root . '/builder.cache.html';
$builder_previous = $site_root . '/builder.previous.html';
$builder_history_file = $site_root . '/builder.history.enc';
$legacy_builder_history_file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR . 'vm-ai-builder-history-'
    . hash('sha256', (string) __DOMAIN__ . '|' . (string) __ACCOUNT_INDEX__) . '.enc';

if (!function_exists('vm_ai_builder_read_history')) {
    function vm_ai_builder_read_history(string $path): array
    {
        if (!is_file($path)) return [];
        $encrypted = @file_get_contents($path);
        if (!is_string($encrypted) || $encrypted === '') return [];
        $json = __decryption__($encrypted, create_enc_key());
        $history = json_decode((string) $json, true);
        return is_array($history) ? array_values(array_filter($history, static function ($item) {
            return is_array($item) && isset($item['prompt'], $item['reply'], $item['created_at']);
        })) : [];
    }
}

if (!function_exists('vm_ai_builder_save_history')) {
    function vm_ai_builder_save_history(string $path, array $history): bool
    {
        $json = json_encode(array_slice($history, 0, 50), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) return false;
        $encrypted = __encryption__($json, create_enc_key());
        $tempPath = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $written = @file_put_contents($tempPath, $encrypted, LOCK_EX);
        if ($written === false || !@rename($tempPath, $path)) {
            @unlink($tempPath);
            return false;
        }
        @chmod($path, 0600);
        return true;
    }
}

// Migrate older history out of the temporary directory into durable site data.
if (!is_file($builder_history_file) && is_file($legacy_builder_history_file)) {
    $legacy_history = vm_ai_builder_read_history($legacy_builder_history_file);
    if ($legacy_history) {
        vm_ai_builder_save_history($builder_history_file, $legacy_history);
    }
}

if (empty($_SESSION['ai_builder_csrf'])) {
    $_SESSION['ai_builder_csrf'] = bin2hex(random_bytes(32));
}
$ai_builder_csrf = $_SESSION['ai_builder_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['ai_generate', 'ai_undo', 'ai_history'], true)) {
    // The admin interface buffers its document shell for normal page loads.
    // Drop that markup so fetch receives a standalone JSON response.
    while (ob_get_level() > 0) {
        if (!@ob_end_clean()) {
            break;
        }
    }
    header('Content-Type: application/json; charset=utf-8');

    if (!$ai_enabled || !hash_equals($ai_builder_csrf, (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'AI builder request is not authorized.']);
        exit;
    }

    if (($_POST['action'] ?? '') === 'ai_history') {
        echo json_encode(['ok' => true, 'history' => vm_ai_builder_read_history($builder_history_file)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (($_POST['action'] ?? '') === 'ai_undo') {
        if (!is_file($builder_previous)) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'There is no generated change to undo.']);
            exit;
        }
        if (!copy($builder_previous, $builder_cache)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Unable to restore the previous storefront.']);
            exit;
        }
        echo json_encode(['ok' => true, 'message' => 'The previous storefront was restored.']);
        exit;
    }

    $brief = trim((string) ($_POST['brief'] ?? ''));
    if ($brief === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Describe the storefront change first.']);
        exit;
    }

    try {
        $agent_config_path = $site_root . '/ai_agent.config.enc';
        $agent_config = [];
        if (is_file($agent_config_path)) {
            $decoded = __decryption__(file_get_contents($agent_config_path), create_enc_key());
            $agent_config = json_decode((string) $decoded, true) ?: [];
        }
        $provider = ($agent_config['provider'] ?? 'google') === 'openai' ? 'openai' : 'google';
        $provider_key_name = $provider === 'openai' ? 'openai_api_key' : 'gemini_api_key';
        $environment_key_name = $provider === 'openai' ? 'OPENAI_API_KEY' : 'GEMINI_API_KEY';
        $environment_model_name = $provider === 'openai' ? 'OPENAI_MODEL' : 'GEMINI_MODEL';
        $api_key = trim((string) ($agent_config[$provider_key_name] ?? ''));
        if ($api_key === '') {
            $api_key = trim((string) getenv($environment_key_name));
        }
        if ($api_key === '') {
            throw new RuntimeException('Connect an ' . ($provider === 'openai' ? 'OpenAI' : 'Gemini') . ' API key in AI Agent settings before using the website builder.');
        }
        putenv('AI_PROVIDER=' . $provider);
        putenv($environment_key_name . '=' . $api_key);
        if (!empty($agent_config['model'])) putenv($environment_model_name . '=' . $agent_config['model']);
        if (isset($agent_config['temperature'])) putenv('GEMINI_TEMPERATURE=' . $agent_config['temperature']);

        $engine_root = $project_root . '/engine/ai.engine';
        require_once $engine_root . '/bootstrap.php';
        $builder = websiteBuilder($engine_root);
        $existing_site_html = '';
        $existing_site_path = is_file($builder_cache)
            ? $builder_cache
            : ($site_root . '/pages/index.html');
        if (!is_file($existing_site_path)) {
            $existing_site_path = $site_root . '/pages/home.html';
        }
        if (is_file($existing_site_path)) {
            $loaded_site_html = file_get_contents($existing_site_path);
            $existing_site_html = is_string($loaded_site_html) ? $loaded_site_html : '';
        }
        if ($existing_site_html === '') {
            $preview_html = (string) ($_POST['current_site_html'] ?? '');
            if (strlen($preview_html) <= 100000 && stripos($preview_html, '<html') !== false) {
                $existing_site_html = $preview_html;
            }
        }
        if (is_file($builder_cache)) copy($builder_cache, $builder_previous);
        $contextual_brief = "Store name: {$store_name}\nActive theme: {$store_theme}\nStore domain: {$store_domain}\n\nRequested change:\n{$brief}";
        $result = $builder->build($contextual_brief, false, $builder_cache . '.generated', $existing_site_html);
        if (!is_file($result['path'])) {
            throw new RuntimeException('The AI response was generated but could not be saved to the storefront.');
        }
        if (!rename($result['path'], $builder_cache)) {
            throw new RuntimeException('The generated storefront could not be moved into place.');
        }
        $managerReply = 'Your storefront update is ready. I’ve refreshed the preview so you can review it.';
        $history = vm_ai_builder_read_history($builder_history_file);
        array_unshift($history, [
            'prompt' => $brief,
            'reply' => $managerReply,
            'created_at' => time(),
        ]);
        $historySaved = vm_ai_builder_save_history($builder_history_file, $history);
        echo json_encode([
            'ok' => true,
            'message' => $managerReply,
            'preview_url' => '/sites/' . rawurlencode((string) __DOMAIN__) . '/builder.cache.html?ai_preview=' . time(),
            'history_saved' => $historySaved,
        ], JSON_UNESCAPED_SLASHES);
    } catch (Throwable $exception) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $exception->getMessage()]);
    }
    exit;
}

if (!$ai_enabled) {
    echo '<div style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
        <div style="text-align:center;">
            <i class="bi bi-stars" style="font-size:3rem;color:#6b7280;"></i>
            <h2 style="color:#fff;margin-top:1rem;font-size:1.5rem;font-weight:700;">AI Builder Disabled</h2>
            <p style="color:#9ca3af;margin-top:.5rem;">This page is only available when AI is enabled for the admin session.</p>
            <a href="/vm-admin/' . htmlspecialchars(__DOMAIN__, ENT_QUOTES, "UTF-8") . '/" style="display:inline-block;margin-top:1.5rem;background:#7c3aed;color:#fff;padding:.625rem 1.5rem;border-radius:9999px;font-size:.875rem;font-weight:700;text-decoration:none;">Go to Dashboard</a>
        </div>
    </div>';
    return;
}

$builder_provider = 'google';
$builder_model = 'gemini';
$builder_connected = false;
try {
    $builder_agent_config_path = $site_root . '/ai_agent.config.enc';
    if (is_file($builder_agent_config_path)) {
        $builder_agent_config = json_decode((string) __decryption__(file_get_contents($builder_agent_config_path), create_enc_key()), true) ?: [];
        $builder_provider = ($builder_agent_config['provider'] ?? 'google') === 'openai' ? 'openai' : 'google';
        $builder_model = (string) ($builder_agent_config['model'] ?? ($builder_provider === 'openai' ? 'gpt-4.1-mini' : 'gemini-2.5-flash'));
        $builder_key_field = $builder_provider === 'openai' ? 'openai_api_key' : 'gemini_api_key';
        $builder_connected = !empty($builder_agent_config[$builder_key_field]);
    }
    $builder_environment_key = $builder_provider === 'openai' ? 'OPENAI_API_KEY' : 'GEMINI_API_KEY';
    $builder_connected = $builder_connected || trim((string) getenv($builder_environment_key)) !== '';
} catch (Throwable $exception) {
    error_log('Could not load AI builder provider status: ' . $exception->getMessage());
}
?>
<style>
    #ai-builder-root {
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow: hidden;
        background: #111113;
        color: #e4e4e7;
        font-family: 'Inter', sans-serif;
    }

    #ai-builder-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .625rem 1.25rem;
        background: #18181b;
        border-bottom: 1px solid rgba(255, 255, 255, .07);
        flex-shrink: 0;
        gap: 1rem;
        z-index: 10;
    }

    #ai-builder-topbar .logo-area {
        display: flex;
        align-items: center;
        gap: .625rem;
    }

    #ai-builder-topbar .badge {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        padding: .25rem .75rem;
        border-radius: 9999px;
        border: 1px solid rgba(139, 92, 246, .25);
        background: rgba(139, 92, 246, .12);
        font-size: .6875rem;
        font-weight: 600;
        color: #c4b5fd;
        letter-spacing: .06em;
    }

    #ai-builder-topbar .badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #a78bfa;
    }

    #ai-builder-topbar h1 {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
    }

    .vp-switcher {
        display: flex;
        align-items: center;
        gap: .25rem;
        background: #27272a;
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: .5rem;
        padding: .25rem;
    }

    .vp-switcher button {
        background: transparent;
        border: none;
        cursor: pointer;
        color: #71717a;
        padding: .375rem .625rem;
        border-radius: .375rem;
        font-size: .875rem;
        line-height: 1;
        transition: background .15s, color .15s;
    }

    .vp-switcher button.active,
    .vp-switcher button:hover {
        background: rgba(255, 255, 255, .08);
        color: #fff;
    }

    .topbar-actions {
        display: flex;
        align-items: center;
        gap: .625rem;
    }

    .topbar-actions a,
    .topbar-actions button {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        padding: .4375rem 1rem;
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s, border-color .15s;
    }

    .btn-ghost {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, .1);
        color: #a1a1aa;
    }

    .btn-ghost:hover {
        background: rgba(255, 255, 255, .06);
        color: #fff;
        border-color: rgba(255, 255, 255, .18);
    }

    .btn-ghost:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .btn-publish {
        background: #7c3aed;
        border: 1px solid #7c3aed;
        color: #fff;
    }

    .btn-publish:hover {
        background: #6d28d9;
        border-color: #6d28d9;
    }

    #ai-builder-body {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    #ai-preview-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-right: 1px solid rgba(255, 255, 255, .07);
    }

    .preview-url-bar {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .875rem;
        background: #1c1c1f;
        border-bottom: 1px solid rgba(255, 255, 255, .06);
        flex-shrink: 0;
    }

    .url-pill {
        flex: 1;
        background: #27272a;
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: .5rem;
        padding: .3125rem .75rem;
        font-size: .75rem;
        color: #71717a;
        display: flex;
        align-items: center;
        gap: .375rem;
        overflow: hidden;
    }

    .url-pill i {
        color: #52525b;
        flex-shrink: 0;
    }

    .url-pill span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .refresh-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        color: #52525b;
        padding: .375rem;
        border-radius: .375rem;
        font-size: .875rem;
        line-height: 1;
        transition: color .15s, background .15s;
    }

    .refresh-btn:hover {
        color: #fff;
        background: rgba(255, 255, 255, .07);
    }

    #ai-preview-wrap {
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #111113;
        align-items: center;
    }

    #ai-preview-veil {
        position: absolute;
        z-index: 5;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: rgba(17, 17, 19, .94);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        color: #e4e4e7;
        text-align: center;
    }

    #ai-preview-veil[hidden] { display: none; }

    .preview-veil-card { width: min(100%, 25rem); }
    .preview-veil-spinner {
        width: 2.5rem;
        height: 2.5rem;
        margin: 0 auto 1rem;
        border: 2px solid rgba(168, 199, 250, .18);
        border-top-color: #a8c7fa;
        border-radius: 50%;
        animation: manager-spin .8s linear infinite;
    }

    #ai-preview-frame {
        flex: 1;
        width: 100%;
        max-width: 100%;
        border: none;
        background: #fff;
        display: block;
        transition: max-width .3s ease;
    }

    #ai-prompt-panel {
        width: 380px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        background: #18181b;
        overflow: hidden;
    }

    .panel-tabs {
        display: flex;
        border-bottom: 1px solid rgba(255, 255, 255, .07);
        flex-shrink: 0;
    }

    .panel-tab {
        flex: 1;
        padding: .75rem .5rem;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        font-size: .6875rem;
        font-weight: 600;
        color: #52525b;
        letter-spacing: .08em;
        text-transform: uppercase;
        transition: color .15s, border-color .15s;
    }

    .panel-tab.active {
        color: #a78bfa;
        border-bottom-color: #7c3aed;
    }

    .panel-tab:hover:not(.active) {
        color: #a1a1aa;
    }

    .tab-content {
        display: none;
        flex: 1;
        flex-direction: column;
        overflow: hidden;
    }

    .tab-content.active {
        display: flex;
    }

    #chat-log {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: .75rem;
        scrollbar-width: thin;
        scrollbar-color: #3f3f46 transparent;
    }

    .chat-msg {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        max-width: 90%;
    }

    .chat-msg.assistant {
        align-self: flex-start;
    }

    .chat-msg.user {
        align-self: flex-end;
    }

    .chat-bubble {
        padding: .625rem .875rem;
        border-radius: .875rem;
        font-size: .8125rem;
        line-height: 1.55;
    }

    .chat-msg.assistant .chat-bubble {
        background: #27272a;
        border: 1px solid rgba(255, 255, 255, .07);
        color: #d4d4d8;
        border-bottom-left-radius: .25rem;
    }

    .chat-msg.user .chat-bubble {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        color: #fff;
        border-bottom-right-radius: .25rem;
    }

    .chat-meta {
        font-size: .6875rem;
        color: #52525b;
        padding: 0 .25rem;
    }

    .chat-msg.user .chat-meta {
        text-align: right;
    }

    .typing-indicator {
        display: flex;
        gap: .25rem;
        align-items: center;
        padding: .75rem .875rem;
        background: #27272a;
        border: 1px solid rgba(255, 255, 255, .07);
        border-radius: .875rem;
        border-bottom-left-radius: .25rem;
        width: fit-content;
    }

    .typing-indicator span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #71717a;
        animation: aib-bounce 1.2s infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: .15s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: .3s;
    }

    .manager-progress {
        width: min(94%, 340px);
        padding: .875rem 1rem;
        border: 1px solid rgba(138, 180, 248, .18);
        border-radius: 1rem;
        border-bottom-left-radius: .25rem;
        background: linear-gradient(145deg, rgba(138, 180, 248, .08), #202124 72%);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .16);
    }

    .manager-progress-head { display:flex; align-items:center; gap:.625rem; }
    .manager-progress-icon {
        display:grid; place-items:center; width:2rem; height:2rem; flex-shrink:0;
        border-radius:.7rem; color:#a8c7fa; background:rgba(138,180,248,.12);
    }
    .manager-spinner {
        width:.8rem; height:.8rem; border-radius:50%; border:2px solid rgba(168,199,250,.22);
        border-top-color:#a8c7fa; animation:manager-spin .8s linear infinite;
    }
    .manager-progress-track { height:3px; overflow:hidden; margin-top:.85rem; border-radius:99px; background:rgba(255,255,255,.08); }
    .manager-progress-track span {
        display:block; width:36%; height:100%; border-radius:inherit; background:#8ab4f8;
        animation:manager-flow 2.2s ease-in-out infinite alternate;
    }
    .manager-agent-steps { display:grid; gap:.4rem; margin-top:.75rem; }
    .manager-agent-step { display:flex; align-items:center; gap:.45rem; color:#737b86; font-size:.67rem; }
    .manager-agent-step i { width:.8rem; text-align:center; }
    .manager-agent-step.active { color:#c5d8f8; }
    .manager-agent-step.done { color:#81c995; }
    .context-agent-list { display:grid; gap:.65rem; margin-top:.75rem; }
    .context-agent-list > div { display:flex; align-items:flex-start; gap:.6rem; padding:.65rem; border:1px solid rgba(255,255,255,.07); border-radius:.7rem; background:rgba(255,255,255,.025); }
    .context-agent-list > div > i { color:#a8c7fa; margin-top:.1rem; }
    .context-agent-list strong,.context-agent-list small { display:block; }
    .context-agent-list strong { color:#e4e6eb; font-size:.72rem; }
    .context-agent-list small { margin-top:.15rem; color:#9097a1; font-size:.65rem; line-height:1.4; }
    @keyframes manager-spin { to { transform:rotate(360deg); } }
    @keyframes manager-flow { from { transform:translateX(-8%); } to { transform:translateX(190%); } }

    @keyframes aib-bounce {

        0%,
        60%,
        100% {
            transform: translateY(0);
        }

        30% {
            transform: translateY(-6px);
        }
    }

    .prompt-input-area {
        padding: .875rem;
        border-top: 1px solid rgba(255, 255, 255, .07);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .prompt-input-wrap {
        display: flex;
        align-items: flex-end;
        gap: .5rem;
        background: #27272a;
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: .75rem;
        padding: .625rem .75rem;
        transition: border-color .2s, box-shadow .2s;
    }

    .prompt-input-wrap:focus-within {
        border-color: rgba(139, 92, 246, .5);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, .08);
    }

    #prompt-textarea {
        flex: 1;
        background: transparent;
        border: none;
        outline: none;
        color: #e4e4e7;
        font-size: .8125rem;
        line-height: 1.5;
        resize: none;
        min-height: 20px;
        max-height: 120px;
        font-family: inherit;
    }

    #prompt-textarea::placeholder {
        color: #52525b;
    }

    #prompt-send-btn {
        background: #7c3aed;
        border: none;
        cursor: pointer;
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .875rem;
        flex-shrink: 0;
        transition: background .15s, transform .1s;
    }

    #prompt-send-btn:hover {
        background: #6d28d9;
    }

    #prompt-send-btn:active {
        transform: scale(.93);
    }

    #prompt-send-btn:disabled {
        background: #3f3f46;
        cursor: not-allowed;
    }

    .prompt-hints {
        display: flex;
        flex-wrap: wrap;
        gap: .375rem;
    }

    .prompt-hint-chip {
        padding: .25rem .625rem;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, .08);
        background: rgba(255, 255, 255, .03);
        font-size: .6875rem;
        color: #71717a;
        cursor: pointer;
        transition: background .15s, color .15s, border-color .15s;
    }

    .prompt-hint-chip:hover {
        background: rgba(139, 92, 246, .12);
        border-color: rgba(139, 92, 246, .3);
        color: #c4b5fd;
    }

    .context-scroll,
    .history-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: .75rem;
        scrollbar-width: thin;
        scrollbar-color: #3f3f46 transparent;
    }

    .context-card {
        background: #27272a;
        border: 1px solid rgba(255, 255, 255, .07);
        border-radius: .75rem;
        padding: .875rem;
    }

    .context-card-label {
        font-size: .6875rem;
        font-weight: 600;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #52525b;
        margin-bottom: .375rem;
    }

    .context-card-value {
        font-size: .875rem;
        font-weight: 500;
        color: #e4e4e7;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        display: inline-block;
        margin-right: .375rem;
        box-shadow: 0 0 6px rgba(34, 197, 94, .5);
    }

    .history-item {
        display: flex;
        align-items: flex-start;
        gap: .625rem;
        padding: .625rem .75rem;
        border-radius: .625rem;
        border: 1px solid rgba(255, 255, 255, .06);
        background: rgba(255, 255, 255, .02);
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }

    .history-item:hover {
        background: rgba(255, 255, 255, .05);
        border-color: rgba(255, 255, 255, .1);
    }

    .history-item i {
        color: #52525b;
        font-size: .875rem;
        flex-shrink: 0;
        margin-top: .125rem;
    }

    .hist-text {
        font-size: .8125rem;
        color: #a1a1aa;
        line-height: 1.4;
    }

    .hist-meta {
        font-size: .6875rem;
        color: #3f3f46;
        margin-top: .25rem;
    }
</style>

<div id="ai-builder-root" style="display: flex; width: -webkit-fill-available;">

    <!-- Top Bar -->
    <div id="ai-builder-topbar">
        
        <div class="logo-area">
<a style="padding:0.2rem 0.8rem; border-radius:1rem; " href="/vm-admin/<?php echo htmlspecialchars(__DOMAIN__, ENT_QUOTES, 'UTF-8'); ?>/" class="btn-ghost">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
            <div>
                <img src="/assets/favicon.png" style="max-width:2rem; ">
            </div>
            <div>
                <h1><?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?></h1>
                <a href="/vm-admin/<?php echo rawurlencode((string) __DOMAIN__); ?>/settings?tab=agent" style="display:inline-flex;align-items:center;gap:.35rem;margin-top:.2rem;color:<?= $builder_connected ? '#a8c7fa' : '#f6c453' ?>;font-size:.6875rem;text-decoration:none;">
                    <span style="width:6px;height:6px;border-radius:50%;background:<?= $builder_connected ? '#81c995' : '#f6c453' ?>;"></span>
                    <?= $builder_provider === 'openai' ? 'OpenAI' : 'Google Gemini' ?> · <?= htmlspecialchars($builder_model, ENT_QUOTES, 'UTF-8') ?><?= $builder_connected ? '' : ' · setup needed' ?>
                </a>
            </div>
        </div>

        <div class="vp-switcher">
            <button class="active" title="Desktop" onclick="setViewport('desktop',this)"><i
                    class="bi bi-display"></i></button>
            <button title="Tablet" onclick="setViewport('tablet',this)"><i class="bi bi-tablet"></i></button>
            <button title="Mobile" onclick="setViewport('mobile',this)"><i class="bi bi-phone"></i></button>
        </div>

        <div class="topbar-actions">
            <button class="btn-ghost" id="topbar-undo-btn" disabled onclick="undoLast()">
                <i class="bi bi-arrow-counterclockwise"></i> Undo
            </button>
            <button class="btn-publish" onclick="publishSite()">
                <i class="bi bi-rocket-takeoff"></i> Publish
            </button>
        </div>
    </div>

    <!-- Body -->
    <div id="ai-builder-body">

        <!-- Left: website preview -->
        <div id="ai-preview-panel">
            <div class="preview-url-bar">
                <button class="refresh-btn" title="Refresh preview" onclick="refreshPreview()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <div class="url-pill">
                    <i class="bi bi-lock-fill"></i>
                    <span
                        id="preview-url-label"><?php echo htmlspecialchars($store_domain ?: '(no domain set)', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <?php
            $store_url = __WEBSITE_URL__ ?? '#';
            if (is_file($builder_cache)) {
                $store_url = '/sites/' . rawurlencode((string) __DOMAIN__) . '/builder.cache.html?ai_preview=' . filemtime($builder_cache);
            }
            ?>
            <div id="ai-preview-wrap" style="max-width:100%; padding:0.65rem 0.8rem; background-color:#2f2f32; ">
                <iframe id="ai-preview-frame" src="<?php echo htmlspecialchars($store_url, ENT_QUOTES, 'UTF-8'); ?>" title="Website live preview"></iframe>
                <div id="ai-preview-veil" hidden role="status" aria-live="polite">
                    <div class="preview-veil-card">
                        <div class="preview-veil-spinner" aria-hidden="true"></div>
                        <p class="text-sm font-semibold text-white">Your project manager is building the update</p>
                        <p id="preview-veil-message" class="mt-2 text-xs leading-5 text-zinc-400">The finished preview will appear here when the new storefront is ready.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: AI prompt panel -->
        <div id="ai-prompt-panel">

            <div class="panel-tabs">
                <button id="chat-tab" class="panel-tab active" onclick="switchTab('chat',this)"><i
                        class="bi bi-stars"></i>&nbsp;Prompt</button>
                <button class="panel-tab" onclick="switchTab('context',this)"><i
                        class="bi bi-info-circle"></i>&nbsp;Context</button>
                <button class="panel-tab" onclick="switchTab('history',this); loadHistory(true)"><i
                        class="bi bi-clock-history"></i>&nbsp;History</button>
            </div>

            <!-- Prompt tab -->
            <div class="tab-content active" id="tab-chat">
                <div id="chat-log">
                    <div class="chat-msg assistant">
                        <div class="chat-bubble">👋 Hi, I’m your project manager. Tell me what you’d like to change — layout, copy, colors, or sections. I’ll coordinate the update and show the refreshed preview here. Larger updates can take a little while, so please keep this page open while I work.</div>
                        <span class="chat-meta">Project manager · just now</span>
                    </div>
                </div>
                <div class="prompt-input-area">
                    <div class="prompt-hints">
                        <span class="prompt-hint-chip" onclick="fillHint(this)">Change the hero banner</span>
                        <span class="prompt-hint-chip" onclick="fillHint(this)">Add a features section</span>
                        <span class="prompt-hint-chip" onclick="fillHint(this)">Update color scheme</span>
                        <span class="prompt-hint-chip" onclick="fillHint(this)">Add a contact form</span>
                    </div>
                    <div class="prompt-input-wrap">
                        <textarea id="prompt-textarea" rows="1" placeholder="Tell your project manager what to change…"
                            onkeydown="handlePromptKey(event)" oninput="autoResize(this)"></textarea>
                        <button id="prompt-send-btn" onclick="sendPrompt()" title="Send (Enter)">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Context tab -->
            <div class="tab-content" id="tab-context">
                <div class="context-scroll">
                    <div class="context-card">
                        <div class="context-card-label">Store name</div>
                        <div class="context-card-value">
                            <?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    <div class="context-card">
                        <div class="context-card-label">Domain</div>
                        <div class="context-card-value">
                            <?php echo htmlspecialchars($store_domain ?: '—', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    <div class="context-card">
                        <div class="context-card-label">Active theme</div>
                        <div class="context-card-value">
                            <?php echo htmlspecialchars($store_theme, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    <div class="context-card">
                        <div class="context-card-label">AI status</div>
                        <div class="context-card-value"><?= $builder_connected ? '<span class="status-dot"></span>Ready' : 'API key needed' ?></div>
                    </div>
                    <div class="context-card">
                        <div class="context-card-label">AI provider</div>
                        <div class="context-card-value"><?= $builder_provider === 'openai' ? 'OpenAI' : 'Google Gemini' ?> · <?= htmlspecialchars($builder_model, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="context-card">
                        <div class="context-card-label">Workflow</div>
                        <div class="context-card-value">The project manager coordinates three specialist agents for each update.</div>
                        <div class="context-agent-list">
                            <div><i class="bi bi-palette2"></i><span><strong>Design agent</strong><small>Layout, color, and visual direction</small></span></div>
                            <div><i class="bi bi-pencil-square"></i><span><strong>Content agent</strong><small>Store copy and section structure</small></span></div>
                            <div><i class="bi bi-window-stack"></i><span><strong>Storefront agent</strong><small>Applies the coordinated page update</small></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History tab -->
            <div class="tab-content" id="tab-history">
                <div class="history-scroll" id="history-scroll">
                    <div class="history-item"><i class="bi bi-arrow-repeat"></i><div><div class="hist-text">Loading previous chats…</div></div></div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const VP = { desktop: '100%', tablet: '768px', mobile: '390px' };

    function setViewport(mode, btn) {
        document.querySelectorAll('.vp-switcher button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const frame = document.getElementById('ai-preview-frame');
        frame.style.maxWidth = VP[mode];
        frame.style.width = VP[mode];
    }

    function refreshPreview() {
        const f = document.getElementById('ai-preview-frame');
        f.src = f.src;
    }

    function switchTab(name, btn) {
        document.querySelectorAll('.panel-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + name).classList.add('active');
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function handlePromptKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendPrompt(); }
    }

    function fillHint(chip) {
        const ta = document.getElementById('prompt-textarea');
        ta.value = chip.textContent.trim();
        ta.focus();
        autoResize(ta);
    }

    const AI_BUILDER_CSRF = <?php echo json_encode($ai_builder_csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    let promptHistory = [];
    let historyLoaded = false;
    let historyLoading = null;
    let typingEl = null;
    let previewLoadHandler = null;
    let isGenerating = false;
    let progressStageTimer = null;
    let progressClockTimer = null;
    let previousUndoEnabled = false;
    const managerUpdates = [
        { agent: 'Project manager', text: 'I’m reviewing your request and assigning the right specialists.' },
        { agent: 'Design agent', text: 'The design agent is shaping the layout and visual direction.' },
        { agent: 'Content agent', text: 'The content agent is coordinating the page copy and sections.' },
        { agent: 'Storefront agent', text: 'The storefront agent is applying the coordinated update.' }
    ];

    async function sendPrompt() {
        if (isGenerating) return;
        const ta = document.getElementById('prompt-textarea');
        const text = ta.value.trim();
        if (!text) return;

        appendMsg('user', text);
        ta.value = '';
        ta.style.height = 'auto';

        showTyping();
        try {
            let currentSiteHtml = '';
            try {
                const currentDocument = document.getElementById('ai-preview-frame').contentDocument;
                if (currentDocument?.documentElement) {
                    const html = currentDocument.documentElement.outerHTML;
                    if (html.length <= 100000) currentSiteHtml = html;
                }
            } catch (error) {
                // A storefront on another origin cannot be read from the admin frame.
            }
            const body = new URLSearchParams({
                action: 'ai_generate',
                csrf: AI_BUILDER_CSRF,
                brief: text,
                current_site_html: currentSiteHtml
            });
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(),
                credentials: 'same-origin'
            });
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                const looksLikeHtml = /^\s*<!doctype html|^\s*<html/i.test(responseText);
                throw new Error(looksLikeHtml
                    ? 'The admin returned a page instead of a builder response. Your session may have expired; reload and sign in again.'
                    : 'The builder returned an unreadable response. Please try again.');
            }
            if (!response.ok || !result.ok) throw new Error(result.error || 'The AI builder request failed.');
            removeTyping(true);
            const managerReply = result.message || 'Your storefront update is ready. I’ve refreshed the preview so you can review it.';
            promptHistory.unshift({ text, reply: managerReply, ts: new Date() });
            promptHistory = promptHistory.slice(0, 50);
            updateHistory();
            appendMsg('assistant', managerReply);
            if (!result.history_saved) {
                appendMsg('assistant', 'The storefront update completed, but this chat could not be saved to history. Check that the site data folder is writable.');
            }
            loadCompletedPreview(result.preview_url);
        } catch (error) {
            removeTyping(false);
            cancelPendingPreviewLoad();
            revealPreviewVeil();
            appendMsg('assistant', 'I couldn’t complete that storefront update: ' + (error.message || 'Unknown error.') + ' Your current preview is still available.');
        }
    }

    function appendMsg(role, text) {
        const log = document.getElementById('chat-log');
        const wrap = document.createElement('div');
        wrap.className = 'chat-msg ' + role;

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.textContent = text;

        const meta = document.createElement('span');
        meta.className = 'chat-meta';
        const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        meta.textContent = (role === 'user' ? 'You' : 'Project manager') + ' · ' + now;

        wrap.appendChild(bubble);
        wrap.appendChild(meta);
        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
    }

    function showTyping() {
        isGenerating = true;
        coverPreviewVeil();
        const undoButton = document.getElementById('topbar-undo-btn');
        previousUndoEnabled = !undoButton.disabled;
        undoButton.disabled = true;
        const sendButton = document.getElementById('prompt-send-btn');
        const textarea = document.getElementById('prompt-textarea');
        sendButton.disabled = true;
        textarea.disabled = true;

        const log = document.getElementById('chat-log');
        typingEl = document.createElement('div');
        typingEl.className = 'manager-progress';
        typingEl.setAttribute('role', 'status');
        typingEl.setAttribute('aria-live', 'polite');
        typingEl.innerHTML = '<div class="manager-progress-head"><span class="manager-progress-icon"><i class="bi bi-diagram-3"></i></span><div style="flex:1;min-width:0"><div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem"><strong style="font-size:.75rem;color:#e8eaed;font-weight:600">Manager agent team</strong><span class="manager-spinner" aria-hidden="true"></span></div><span id="manager-progress-status" style="display:block;margin-top:.3rem;color:#9aa0a6;font-size:.75rem;line-height:1.45"></span></div></div><div class="manager-agent-steps"><div class="manager-agent-step" data-step="0"><i class="bi bi-circle"></i>Project manager · planning</div><div class="manager-agent-step" data-step="1"><i class="bi bi-circle"></i>Design agent · visual direction</div><div class="manager-agent-step" data-step="2"><i class="bi bi-circle"></i>Content agent · page copy</div><div class="manager-agent-step" data-step="3"><i class="bi bi-circle"></i>Storefront agent · implementation</div></div><div class="manager-progress-track" aria-hidden="true"><span></span></div><div id="manager-progress-elapsed" style="margin-top:.45rem;text-align:right;color:#6f7782;font-size:.65rem"></div>';
        log.appendChild(typingEl);
        log.scrollTop = log.scrollHeight;
        let updateIndex = 0;
        const startedAt = Date.now();
        const status = typingEl.querySelector('#manager-progress-status');
        const elapsed = typingEl.querySelector('#manager-progress-elapsed');
        const updateStage = () => {
            if (!typingEl || !status.isConnected) return;
            const update = managerUpdates[updateIndex];
            status.textContent = update.text;
            typingEl.querySelectorAll('.manager-agent-step').forEach((step, index) => {
                step.classList.toggle('done', index < updateIndex);
                step.classList.toggle('active', index === updateIndex);
                const icon = step.querySelector('i');
                icon.className = index < updateIndex ? 'bi bi-check-circle-fill' : (index === updateIndex ? 'bi bi-arrow-repeat' : 'bi bi-circle');
            });
        };
        updateStage();
        progressStageTimer = setInterval(() => {
            if (updateIndex < managerUpdates.length - 1) updateIndex++;
            updateStage();
        }, 7000);
        const updateElapsed = () => {
            if (!typingEl || !elapsed.isConnected) return;
            const seconds = Math.floor((Date.now() - startedAt) / 1000);
            elapsed.textContent = seconds < 60 ? `Working · ${seconds}s` : `Working · ${Math.floor(seconds / 60)}m ${seconds % 60}s`;
        };
        updateElapsed();
        progressClockTimer = setInterval(updateElapsed, 1000);
        window.addEventListener('beforeunload', warnWhileGenerating);
    }

    function coverPreviewVeil() {
        const veil = document.getElementById('ai-preview-veil');
        if (veil) veil.hidden = false;
    }

    function revealPreviewVeil() {
        const veil = document.getElementById('ai-preview-veil');
        if (veil) veil.hidden = true;
    }

    function loadCompletedPreview(url) {
        const frame = document.getElementById('ai-preview-frame');
        cancelPendingPreviewLoad();
        const expectedPath = new URL(url, window.location.href).pathname;
        previewLoadHandler = () => {
            try {
                if (new URL(frame.contentWindow.location.href).pathname === expectedPath) {
                    cancelPendingPreviewLoad();
                    revealPreviewVeil();
                }
            } catch (error) {
                // The current page may still be the old cross-origin preview. Keep the veil until the new same-origin file loads.
            }
        };
        frame.addEventListener('load', previewLoadHandler);
        frame.src = url;
    }

    function cancelPendingPreviewLoad() {
        if (!previewLoadHandler) return;
        document.getElementById('ai-preview-frame').removeEventListener('load', previewLoadHandler);
        previewLoadHandler = null;
    }

    function warnWhileGenerating(event) {
        if (!isGenerating) return;
        event.preventDefault();
        event.returnValue = '';
    }

    function removeTyping(succeeded) {
        isGenerating = false;
        clearInterval(progressStageTimer);
        clearInterval(progressClockTimer);
        progressStageTimer = null;
        progressClockTimer = null;
        window.removeEventListener('beforeunload', warnWhileGenerating);
        if (typingEl) { typingEl.remove(); typingEl = null; }
        document.getElementById('prompt-send-btn').disabled = false;
        document.getElementById('prompt-textarea').disabled = false;
        document.getElementById('topbar-undo-btn').disabled = succeeded ? false : !previousUndoEnabled;
    }

    function updateHistory() {
        const scroll = document.getElementById('history-scroll');
        scroll.replaceChildren();
        if (!promptHistory.length) {
            const empty = document.createElement('div');
            empty.className = 'history-item';
            empty.innerHTML = '<i class="bi bi-chat-left-dots"></i><div><div class="hist-text">No saved chats yet</div><div class="hist-meta">Completed storefront updates will appear here.</div></div>';
            scroll.appendChild(empty);
            return;
        }

        promptHistory.forEach((item, index) => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'history-item';
            row.style.width = '100%';
            row.style.textAlign = 'left';
            row.addEventListener('click', () => restoreConversation(index));

            const icon = document.createElement('i');
            icon.className = 'bi bi-chat-left-dots';
            const content = document.createElement('div');
            content.style.minWidth = '0';
            const prompt = document.createElement('div');
            prompt.className = 'hist-text';
            prompt.textContent = item.text;
            const date = document.createElement('div');
            date.className = 'hist-meta';
            date.textContent = item.ts.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            content.append(prompt, date);
            row.append(icon, content);
            scroll.appendChild(row);
        });
    }

    function restoreConversation(index) {
        const item = promptHistory[index];
        if (!item) return;
        const log = document.getElementById('chat-log');
        log.replaceChildren();
        appendMsg('user', item.text);
        appendMsg('assistant', item.reply || 'I completed this storefront update.');
        const chatTab = document.getElementById('chat-tab');
        if (chatTab) switchTab('chat', chatTab);
    }

    async function loadHistory(force = false) {
        if (historyLoaded && !force) return;
        if (historyLoading) return historyLoading;
        historyLoading = (async () => {
            const scroll = document.getElementById('history-scroll');
            scroll.textContent = 'Loading previous chats…';
            try {
                const body = new URLSearchParams({ action: 'ai_history', csrf: AI_BUILDER_CSRF });
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                    credentials: 'same-origin'
                });
                const result = JSON.parse(await response.text());
                if (!response.ok || !result.ok || !Array.isArray(result.history)) {
                    throw new Error(result.error || 'Unable to load saved chats.');
                }
                const savedHistory = result.history.map(item => ({
                    text: String(item.prompt || ''),
                    reply: String(item.reply || ''),
                    ts: new Date(Number(item.created_at || 0) * 1000)
                })).filter(item => item.text !== '');
                const unsyncedLocal = promptHistory.filter(local => !savedHistory.some(saved =>
                    saved.text === local.text && Math.abs(saved.ts.getTime() - local.ts.getTime()) < 10000
                ));
                promptHistory = [...unsyncedLocal, ...savedHistory]
                    .sort((a, b) => b.ts.getTime() - a.ts.getTime())
                    .slice(0, 50);
                historyLoaded = true;
                updateHistory();
            } catch (error) {
                scroll.replaceChildren();
                const failed = document.createElement('div');
                failed.className = 'history-item';
                const message = document.createElement('div');
                message.className = 'hist-text';
                message.textContent = 'Could not retrieve previous chats: ' + (error.message || 'Request failed.');
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'hist-meta';
                retry.textContent = 'Try again';
                retry.addEventListener('click', () => loadHistory(true));
                failed.append(message, retry);
                scroll.appendChild(failed);
            }
        })().finally(() => { historyLoading = null; });
        return historyLoading;
    }

    async function undoLast() {
        const button = document.getElementById('topbar-undo-btn');
        button.disabled = true;
        try {
            const body = new URLSearchParams({ action: 'ai_undo', csrf: AI_BUILDER_CSRF });
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(),
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'Undo failed.');
            appendMsg('assistant', result.message);
            refreshPreview();
        } catch (error) {
            appendMsg('assistant', 'Could not undo the change: ' + (error.message || 'Unknown error.'));
            button.disabled = false;
        }
    }

    function publishSite() {
        window.location.href = '/vm-admin/<?php echo rawurlencode((string) __DOMAIN__); ?>/publish';
    }

    function escHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

</script>
