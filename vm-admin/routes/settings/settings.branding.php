<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<form method="POST">
    <input type="hidden" name="action" value="save_branding">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h2 class="text-lg font-bold text-white">Store Branding</h2>
            <p class="text-zinc-400 text-sm mt-1">Personalize how your store appears to customers</p>
        </div>
        <div class="p-5 space-y-5">
            <div>
                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Store Name</label>
                <input type="text" name="branding[wb_name]" value="<?= htmlspecialchars($site_name) ?>"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                    placeholder="e.g. My Premium Store">
                <p class="text-zinc-500 text-xs mt-1.5">This name appears in browser tabs and customer emails.</p>
            </div>

            <div>
                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Primary Public URL</label>
                <input
                    type="text"
                    name="branding[domain]"
                    value="<?= htmlspecialchars($site_domain) ?>"
                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors font-mono"
                    placeholder="store.example.com"
                >
                <p class="text-zinc-500 text-xs mt-1.5">Changing this will move the store files to the new domain path and update the store registry.</p>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-zinc-800 flex justify-end">
            <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                Update Branding
            </button>
        </div>
    </div>
</form>
