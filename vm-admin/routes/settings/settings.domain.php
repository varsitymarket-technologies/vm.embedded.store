<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-zinc-800">
        <h2 class="text-lg font-bold text-white">Domain Mapping</h2>
        <p class="text-zinc-400 text-sm mt-1">Connect your custom domain to your hosted repository</p>
    </div>
    <div class="p-5 space-y-8">

        <!-- Step 1: Select Provider -->
        <div>
            <div class="flex items-center gap-3 mb-4">
                <span class="w-7 h-7 rounded-full bg-violet-600 text-white flex items-center justify-center text-xs font-bold">1</span>
                <h3 class="text-sm font-semibold text-white">Select Provider</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-4 bg-zinc-800 rounded-xl border border-zinc-700 hover:border-violet-500/30 transition-all cursor-pointer group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-10 h-10 rounded-lg bg-zinc-700/50 flex items-center justify-center group-hover:bg-violet-500/10 transition-colors">
                            <i class="bi bi-github text-zinc-300"></i>
                        </span>
                        <span class="font-semibold text-white text-sm">GitHub Pages</span>
                    </div>
                    <p class="text-zinc-500 text-xs leading-relaxed">Optimized for free static hosting. High reliability.</p>
                </div>
                <div class="p-4 bg-zinc-800 rounded-xl border border-zinc-700 hover:border-violet-500/30 transition-all cursor-pointer group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-10 h-10 rounded-lg bg-zinc-700/50 flex items-center justify-center group-hover:bg-violet-500/10 transition-colors">
                            <i class="bi bi-triangle-fill text-zinc-300"></i>
                        </span>
                        <span class="font-semibold text-white text-sm">Vercel Edge</span>
                    </div>
                    <p class="text-zinc-500 text-xs leading-relaxed">Premium global performance and automatic SSL.</p>
                </div>
            </div>
        </div>

        <!-- Step 2: DNS Configuration -->
        <div>
            <div class="flex items-center gap-3 mb-4">
                <span class="w-7 h-7 rounded-full bg-violet-600 text-white flex items-center justify-center text-xs font-bold">2</span>
                <h3 class="text-sm font-semibold text-white">DNS Configuration</h3>
            </div>
            <div class="overflow-x-auto rounded-lg border border-zinc-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-zinc-800 text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Value</th>
                            <th class="px-4 py-3 font-medium text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/50">
                        <tr class="hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3">
                                <span class="bg-violet-500/10 text-violet-400 px-1.5 py-0.5 rounded text-[10px] font-medium uppercase">CNAME</span>
                            </td>
                            <td class="px-4 py-3 text-white font-mono">www</td>
                            <td class="px-4 py-3 text-violet-400 font-mono">cname.vercel-dns.com</td>
                            <td class="px-4 py-3 text-right"><i class="bi bi-check-circle-fill text-emerald-400"></i></td>
                        </tr>
                        <tr class="hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3">
                                <span class="bg-amber-500/10 text-amber-400 px-1.5 py-0.5 rounded text-[10px] font-medium uppercase">A</span>
                            </td>
                            <td class="px-4 py-3 text-white font-mono">@</td>
                            <td class="px-4 py-3 text-violet-400 font-mono"><?= $_SERVER['SERVER_ADDR'] ?></td>
                            <td class="px-4 py-3 text-right"><i class="bi bi-check-circle-fill text-emerald-400"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
