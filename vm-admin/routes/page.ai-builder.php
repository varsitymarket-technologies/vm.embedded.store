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
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #111113;
        align-items: center;
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
            <div class="badge">
                <span class="dot"></span>
                AI Builder
            </div>
            <h1><?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>

        <div class="vp-switcher">
            <button class="active" title="Desktop" onclick="setViewport('desktop',this)"><i
                    class="bi bi-display"></i></button>
            <button title="Tablet" onclick="setViewport('tablet',this)"><i class="bi bi-tablet"></i></button>
            <button title="Mobile" onclick="setViewport('mobile',this)"><i class="bi bi-phone"></i></button>
        </div>

        <div class="topbar-actions">
            <a href="/vm-admin/<?php echo htmlspecialchars(__DOMAIN__, ENT_QUOTES, 'UTF-8'); ?>/" class="btn-ghost">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
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
            <div id="ai-preview-wrap">
                <iframe id="ai-preview-frame" src="/" title="Website live preview"></iframe>
            </div>
        </div>

        <!-- Right: AI prompt panel -->
        <div id="ai-prompt-panel">

            <div class="panel-tabs">
                <button class="panel-tab active" onclick="switchTab('chat',this)"><i
                        class="bi bi-stars"></i>&nbsp;Prompt</button>
                <button class="panel-tab" onclick="switchTab('context',this)"><i
                        class="bi bi-info-circle"></i>&nbsp;Context</button>
                <button class="panel-tab" onclick="switchTab('history',this)"><i
                        class="bi bi-clock-history"></i>&nbsp;History</button>
            </div>

            <!-- Prompt tab -->
            <div class="tab-content active" id="tab-chat">
                <div id="chat-log">
                    <div class="chat-msg assistant">
                        <div class="chat-bubble">👋 Hey! I'm your AI website builder. Tell me what you'd like to change
                            — layout, copy, colors, sections — and I'll generate the updates for you.</div>
                        <span class="chat-meta">AI Builder · just now</span>
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
                        <textarea id="prompt-textarea" rows="1" placeholder="Describe what you want to build or change…"
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
                        <div class="context-card-value"><span class="status-dot"></span>Ready</div>
                    </div>
                    <div class="context-card">
                        <div class="context-card-label">Builder mode</div>
                        <div class="context-card-value">Prototype / UI scaffold</div>
                    </div>
                </div>
            </div>

            <!-- History tab -->
            <div class="tab-content" id="tab-history">
                <div class="history-scroll" id="history-scroll">
                    <div class="history-item">
                        <i class="bi bi-chat-left-dots"></i>
                        <div>
                            <div class="hist-text">No prompt history yet</div>
                            <div class="hist-meta">Start a conversation to see history here</div>
                        </div>
                    </div>
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

    let promptHistory = [];
    let typingEl = null;

    function sendPrompt() {
        const ta = document.getElementById('prompt-textarea');
        const text = ta.value.trim();
        if (!text) return;

        appendMsg('user', text);
        ta.value = '';
        ta.style.height = 'auto';

        promptHistory.unshift({ text, ts: new Date() });
        updateHistory();
        document.getElementById('topbar-undo-btn').disabled = false;

        showTyping();
        // Stub — wire up real API here
        setTimeout(() => {
            removeTyping();
            appendMsg('assistant', '⚙️ Received: "' + escHtml(text) + '". API not yet wired — changes will appear here once connected.');
        }, 1800);
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
        meta.textContent = (role === 'user' ? 'You' : 'AI Builder') + ' · ' + now;

        wrap.appendChild(bubble);
        wrap.appendChild(meta);
        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
    }

    function showTyping() {
        const log = document.getElementById('chat-log');
        typingEl = document.createElement('div');
        typingEl.className = 'typing-indicator';
        typingEl.innerHTML = '<span></span><span></span><span></span>';
        log.appendChild(typingEl);
        log.scrollTop = log.scrollHeight;
        document.getElementById('prompt-send-btn').disabled = true;
    }

    function removeTyping() {
        if (typingEl) { typingEl.remove(); typingEl = null; }
        document.getElementById('prompt-send-btn').disabled = false;
    }

    function updateHistory() {
        const scroll = document.getElementById('history-scroll');
        scroll.innerHTML = promptHistory.map(h =>
            `<div class="history-item" onclick="fillHint({textContent:'${escHtml(h.text)}'})">
            <i class="bi bi-chat-left-dots"></i>
            <div>
                <div class="hist-text">${escHtml(h.text)}</div>
                <div class="hist-meta">${h.ts.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
            </div>
        </div>`
        ).join('');
    }

    function undoLast() {
        appendMsg('assistant', '↩️ Undo is not yet wired to the backend. This will revert the last AI-generated change.');
    }

    function publishSite() {
        appendMsg('assistant', '🚀 Publish triggered. Wire this up to your deploy pipeline to go live.');
    }

    function escHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
</script>