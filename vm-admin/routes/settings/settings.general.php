<!-- Page Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-white">Settings</h2>
        <p class="text-zinc-400 text-sm mt-1">Configure your store environment, branding, and integrations</p>
    </div>
</div>

<!-- Settings Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <a href="?tab=branding" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-violet-500/10 flex items-center justify-center group-hover:bg-violet-500/20 transition-colors">
                <i class="bi bi-brush text-violet-400"></i>
            </span>
            <span class="font-semibold text-white">Store Branding</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Logo, store name, description and visual identity</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=payment" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-colors">
                <i class="bi bi-credit-card text-emerald-400"></i>
            </span>
            <span class="font-semibold text-white">Payment Methods</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Configure COD, YOCO, PayPal and other gateways</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=currency" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center group-hover:bg-amber-500/20 transition-colors">
                <i class="bi bi-currency-exchange text-amber-400"></i>
            </span>
            <span class="font-semibold text-white">Currency</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Default currency, formatting and accepted currencies</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=email" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-sky-500/10 flex items-center justify-center group-hover:bg-sky-500/20 transition-colors">
                <i class="bi bi-envelope text-sky-400"></i>
            </span>
            <span class="font-semibold text-white">Email Configuration</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">SMTP settings and email templates for notifications</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=domain" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-teal-500/10 flex items-center justify-center group-hover:bg-teal-500/20 transition-colors">
                <i class="bi bi-globe text-teal-400"></i>
            </span>
            <span class="font-semibold text-white">Connect Domain</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Link a custom domain to your storefront</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=dev" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-orange-500/10 flex items-center justify-center group-hover:bg-orange-500/20 transition-colors">
                <i class="bi bi-code-slash text-orange-400"></i>
            </span>
            <span class="font-semibold text-white">Developer Settings</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">API keys, CORS domains and SDK configuration</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=app" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-indigo-500/10 flex items-center justify-center group-hover:bg-indigo-500/20 transition-colors">
                <i class="bi bi-plug text-indigo-400"></i>
            </span>
            <span class="font-semibold text-white">App Extensions</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Discord webhooks and third-party integrations</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=console" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-pink-500/10 flex items-center justify-center group-hover:bg-pink-500/20 transition-colors">
                <i class="bi bi-phone text-pink-400"></i>
            </span>
            <span class="font-semibold text-white">Mobile App Console</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Connect your store to the mobile app</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=agent" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-cyan-500/10 flex items-center justify-center group-hover:bg-cyan-500/20 transition-colors">
                <i class="bi bi-robot text-cyan-400"></i>
            </span>
            <span class="font-semibold text-white">AI Agent</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Configure the admin-only AI assistant and its MCP tools</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

    <a href="?tab=deployment" class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-violet-500/30 transition-all group relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-10 h-10 rounded-lg bg-zinc-700/50 flex items-center justify-center group-hover:bg-zinc-600/50 transition-colors">
                <i class="bi bi-github text-zinc-300"></i>
            </span>
            <span class="font-semibold text-white">GitHub Deployment</span>
        </div>
        <p class="text-zinc-500 text-xs leading-relaxed">Connect source code for automated deployment</p>
        <i class="bi bi-chevron-right text-zinc-700 absolute right-4 top-1/2 -translate-y-1/2 group-hover:text-violet-400 transition-colors"></i>
    </a>

</div>
