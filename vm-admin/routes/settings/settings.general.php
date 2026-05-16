<div>
    <h1 class="text-4xl font-black tracking-tight text-white mb-2">Settings</h1>
    <p class="text-gray-400 text-lg">Configure your store environment, branding, and integrations.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div onclick="window.location.href='?tab=branding'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-brush-fill text-xl"></i>
            </div>
            <span class="font-bold text-white">Store Branding</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your store branding.</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-brush-fill text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=email'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-envelope-fill text-xl"></i>
            </div>
            <span class="font-bold text-white">Email Configuration</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your email settings for transactional emails.</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-envelope-fill text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=payment'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-credit-card-fill text-xl"></i>
            </div>
            <span class="font-bold text-white">Payment Methods</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your payment methods</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-credit-card-fill text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=currency'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-currency-exchange text-xl"></i>
            </div>
            <span class="font-bold text-white">Currency</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your store currency</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-currency-exchange text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=dev'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-gear-fill text-xl"></i>
            </div>
            <span class="font-bold text-white">Developer Settings</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your developer settings</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-gear-fill text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=app'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-plugin text-xl"></i>
            </div>
            <span class="font-bold text-white">Application Extension</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Configure your Application Plugins</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-plugin text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=console'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-phone text-xl"></i>
            </div>
            <span class="font-bold text-white">Mobile App Console</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Connect the store to your mobile app.</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-phone text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=deployment'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-github text-xl"></i>
            </div>
            <span class="font-bold text-white">GitHub Deployment</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Connect your source code for automated delivery cycles.</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-github text-9xl"></i>
        </div>
    </div>

    <div onclick="window.location.href='?tab=domain'"
        class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-3 relative z-10">
            <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                <i class="bi bi-globe text-xl"></i>
            </div>
            <span class="font-bold text-white">Connect Domain</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed relative z-10">Connect your domain to your store.</p>
        <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
            <i class="bi bi-globe text-9xl"></i>
        </div>
    </div>
</div>
