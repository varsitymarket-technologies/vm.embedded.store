<div>
    <button onclick="window.location.href='?tab=general'"
        class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
        Back To Settings
    </button>
</div>
<br><br>

<form method="POST">
    <input type="hidden" name="action" value="save_branding">
    <div class="v-card animate-slide-up">
        <div class="v-card-header">
            <h2 class="text-xl font-bold text-white">Public Branding</h2>
            <p class="text-sm text-gray-400 mt-2">Personalize how your store appears to customers.</p>
        </div>
        <div class="v-card-body space-y-8">
            <div class="space-y-3">
                <label class="text-sm font-bold text-gray-200">Store Name</label>
                <input type="text" name="branding[wb_name]" value="<?= htmlspecialchars($site_name) ?>"
                    class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all shadow-inner"
                    placeholder="e.g. My Premium Store">
                <p class="text-[11px] text-gray-500">This name appears in browser tabs and customer emails.</p>
            </div>

            <div class="space-y-3 opacity-60">
                <label class="text-sm font-bold text-gray-200">Primary Public URL</label>
                <div class="relative">
                    <input type="text" value="<?= htmlspecialchars($site_domain) ?>" readonly
                        class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3.5 text-gray-500 cursor-not-allowed">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black bg-white/5 px-2 py-0.5 rounded uppercase">Connected</span>
                </div>
            </div>
        </div>
        <br>
        <div class="v-card-footer">
            <button type="submit"
                class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                Update Branding
            </button>
        </div>
    </div>
</form>
