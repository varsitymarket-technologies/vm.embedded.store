<script src="https://cdn.tailwindcss.com"></script>

<style>
    /* Handling the iOS Notch and Home Indicator */
    :root {
        --safe-top: env(safe-area-inset-top, 0px);
        --safe-bottom: env(safe-area-inset-bottom, 0px);
    }

    body {
        background-color: #09090b;
        /* Zinc 950 */
        color: #ffffff;
        overscroll-behavior: none;
        /* Prevents browser "bounce" */
    }

    .pwa-container {
        padding-top: calc(var(--safe-top) + 1.5rem);
        padding-bottom: calc(var(--safe-bottom) + 1.5rem);
        min-height: 100dvh;
        /* Dynamic viewport height for mobile */
    }

    /* Simple fade-in animation for the intro */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade {
        animation: fadeIn 0.6s ease-out forwards;
    }
</style>

<div class="pwa-container flex flex-col items-center px-6">

    <div id="app-intro" class="w-full max-w-sm text-center mt-8 animate-fade">
        <div class="mb-8 inline-block">
            <div class="w-20 h-20 bg-grey rounded-2xl flex items-center justify-center shadow-xl">
                <img src="/assets/favicon.png" alt="Logo" class="w-12 h-12">
            </div>
        </div>

        <h1 class="text-3xl font-bold tracking-tight mb-2">Admin Console</h1>
        <p class="text-zinc-400 text-sm mb-12">Manage your embedded stores from anywhere.</p>
    </div>

    <div id="auth-form" class="w-full max-w-sm space-y-6 animate-fade" style="animation-delay: 0.2s;">

        <div class="space-y-4">
            <div class="form-group">
                <label class="text-xs uppercase tracking-widest text-zinc-500 mb-1.5 ml-1">Store Environment URL</label>
                <input type="text" placeholder="https://yourstore.com"
                    class="w-full bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-white/20 transition-all">
            </div>

            <div class="form-group">
                <label class="text-xs uppercase tracking-widest text-zinc-500 mb-1.5 ml-1">Access Token</label>
                <input type="password" placeholder="vm_live_xxxxxxxxxxxx"
                    class="w-full bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-white/20 transition-all">
            </div>
        </div>

        <div class="flex flex-col gap-3 pt-4">
            <button
                class="w-full bg-white text-black font-bold py-4 rounded-xl active:scale-[0.98] transition-transform">
                Connect To Store
            </button>

            <button onclick="openExternal('https://varsitymarket.tech/')"
                class="w-full bg-zinc-900 text-zinc-300 border border-zinc-800 font-medium py-4 rounded-xl active:scale-[0.98] transition-transform">
                Register Your Store
            </button>

            <script>
                function openExternal(url) {
                    // This tells the OS to handle the URL outside the current context
                    const remote = window.open(url, '_system');

                    // Fallback if _system is ignored
                    if (!remote) {
                        window.location.assign(url);
                    }
                }
            </script>
        </div>

        <div class="text-center mt-8">
            <p class="text-zinc-600 text-[11px] uppercase tracking-[0.2em]">
                Powered By VMTECH
            </p>
        </div>
    </div>

</div>

<script>
    // Logic to hide/show sections if you want a multi-step intro
    // document.getElementById('app-intro').classList.add('hidden');
</script>