<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-zinc-800">
        <h2 class="text-lg font-bold text-white">Advanced Deployment</h2>
        <p class="text-zinc-400 text-sm mt-1">Connect your source code for automated delivery cycles</p>
    </div>
    <div class="flex flex-col items-center justify-center py-16 px-6">
        <?php if (isset($_SESSION['github_token'])): ?>
            <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-6">
                <i class="bi bi-check-circle text-4xl text-emerald-400"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Store Deployment Ready</h3>
            <p class="text-zinc-400 text-sm max-w-sm text-center leading-relaxed">
                Your store is connected to your GitHub account. You can now deploy your store from the main Deploy page.
            </p>
        <?php else: ?>
            <div class="w-16 h-16 rounded-2xl bg-zinc-800 flex items-center justify-center mb-6">
                <i class="bi bi-github text-4xl text-zinc-300"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Connect GitHub Repository</h3>
            <p class="text-zinc-400 text-sm max-w-sm text-center mb-8 leading-relaxed">
                Authorize Varsity Market to automate your builds and push production updates directly to your hosting provider.
            </p>
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <a onclick="window.location.href=`https://github.com/login/oauth/authorize?client_id=<?php echo $_SERVER['__GITHUB_APK_CLIENT__']; ?>`"
                    class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors cursor-pointer">
                    <i class="bi bi-github"></i> Authorize GitHub
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
