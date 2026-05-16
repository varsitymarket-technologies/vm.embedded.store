<div>
    <button onclick="window.location.href='?tab=general'"
        class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
        Back To Settings
    </button>
</div>
<br><br>

<div class="v-card animate-slide-up">
    <div class="v-card-header">
        <h2 class="text-xl font-bold text-white">Domain Mapping</h2>
        <p class="text-sm text-gray-400 mt-2">Connect your custom domain to your hosted repository.</p>
    </div>
    <div class="v-card-body space-y-12">
        <div>
            <div class="flex items-center gap-3 mb-6">
                <span class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-black text-xs">1</span>
                <h3 class="text-sm font-black text-white uppercase tracking-widest">Select Provider</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-github text-xl"></i>
                        </div>
                        <span class="font-bold text-white">GitHub Pages</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Optimized for free static hosting. High reliability.</p>
                    <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-github text-9xl"></i>
                    </div>
                </div>
                <div class="p-6 bg-black/40 rounded-2xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer group relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-3 relative z-10">
                        <div class="p-3 bg-white/5 rounded-xl group-hover:bg-purple-600/20 transition-colors">
                            <i class="bi bi-triangle-fill text-xl"></i>
                        </div>
                        <span class="font-bold text-white">Vercel Edge</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed relative z-10">Premium global performance and automatic SSL.</p>
                    <div class="absolute -right-4 -bottom-4 opacity-[0.02] transform rotate-12 group-hover:opacity-[0.05] transition-opacity">
                        <i class="bi bi-triangle-fill text-9xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-center gap-3 mb-6">
                <span class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-black text-xs">2</span>
                <h3 class="text-sm font-black text-white uppercase tracking-widest">DNS Configuration</h3>
            </div>
            <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#080808]">
                <table class="w-full text-left text-xs font-mono">
                    <thead class="bg-white/5 text-gray-500 uppercase tracking-tighter">
                        <tr>
                            <th class="px-6 py-4 font-black">Type</th>
                            <th class="px-6 py-4 font-black">Name</th>
                            <th class="px-6 py-4 font-black">Value</th>
                            <th class="px-6 py-4 font-black text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4">
                                <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-purple-500/20">CNAME</span>
                            </td>
                            <td class="px-6 py-4 text-gray-300 font-bold">www</td>
                            <td class="px-6 py-4 text-purple-400">cname.vercel-dns.com</td>
                            <td class="px-6 py-4 text-right"><i class="bi bi-check-circle-fill text-emerald-500"></i></td>
                        </tr>
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4">
                                <span class="bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border border-orange-500/20">A</span>
                            </td>
                            <td class="px-6 py-4 text-gray-300 font-bold">@</td>
                            <td class="px-6 py-4 text-purple-400"><?= $_SERVER['SERVER_ADDR'] ?></td>
                            <td class="px-6 py-4 text-right"><i class="bi bi-cloud-check-fill text-emerald-500"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<br><br>
