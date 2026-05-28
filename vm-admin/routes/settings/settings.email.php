<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<form method="POST" id="emailForm">
    <input type="hidden" name="action" value="save_email_config">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h2 class="text-lg font-bold text-white">Email Configuration</h2>
            <p class="text-zinc-400 text-sm mt-1">Securely store SMTP and notification templates</p>
        </div>
        <div class="p-5 space-y-6">

            <!-- SMTP Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">SMTP Host</label>
                    <input type="text" name="email[host]" value="<?= htmlspecialchars($email_current['host']) ?>"
                        placeholder="smtp.gmail.com"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                </div>
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">SMTP Port</label>
                    <input type="text" name="email[port]" value="<?= htmlspecialchars($email_current['port']) ?>"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                </div>
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">Username</label>
                    <input type="text" name="email[user]" value="<?= htmlspecialchars($email_current['user']) ?>"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                </div>
                <div>
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">Password</label>
                    <input type="password" name="email[pass]" value="<?= htmlspecialchars($email_current['pass']) ?>"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                </div>
            </div>

            <!-- Template Editor -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium">Notification Template</label>
                        <p class="text-zinc-500 text-xs mt-0.5">Supports <code class="text-violet-400">{{name}}</code> and <code class="text-violet-400">{{message}}</code> tags</p>
                    </div>
                    <button type="button" onclick="togglePreview()"
                        class="text-violet-400 hover:text-violet-300 text-xs font-medium transition-colors flex items-center gap-1">
                        <i class="bi bi-eye"></i> Preview
                    </button>
                </div>
                <textarea name="email[template]" id="emailTemplate" rows="10"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 font-mono text-xs text-violet-300 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors leading-relaxed"><?= htmlspecialchars($email_current['template']) ?></textarea>
            </div>

        </div>
        <div class="px-5 py-4 border-t border-zinc-800 flex justify-end">
            <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                Save Configuration
            </button>
        </div>
    </div>
</form>

<!-- Preview Display -->
<div id="previewContainer" class="hidden mt-6">
    <div class="bg-zinc-900 border border-violet-500/30 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-zinc-800 flex items-center justify-between">
            <h3 class="text-xs font-medium text-violet-400 uppercase tracking-wider">Live Preview</h3>
            <button onclick="togglePreview()" class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg text-xs"></i></button>
        </div>
        <div class="bg-white">
            <iframe id="previewFrame" class="w-full border-none" style="height: 500px;"></iframe>
        </div>
    </div>
</div>

<script>
function togglePreview() {
    const container = document.getElementById('previewContainer');
    const isHidden = container.classList.contains('hidden');
    if (isHidden) {
        container.classList.remove('hidden');
        updatePreview();
        setTimeout(() => container.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
    } else {
        container.classList.add('hidden');
    }
}

function updatePreview() {
    const template = document.getElementById('emailTemplate').value;
    const frame = document.getElementById('previewFrame');
    let rendered = template
        .replace(/{{name}}/g, 'Valued Customer')
        .replace(/{{message}}/g, 'This is a sample encrypted notification sent from your Store Admin. The styling here will match how your customers see automated emails.')
        .replace(/{{link}}/g, '#');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open();
    doc.write(rendered);
    doc.close();
}
</script>
