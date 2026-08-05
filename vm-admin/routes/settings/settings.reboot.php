<?php
$reboot_error = $_GET['error'] ?? '';
?>

<a href="?tab=general" class="inline-flex items-center gap-2 text-gray-500 hover:text-white text-sm mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<div class="max-w-4xl mx-auto">
    <div class="bg-[#252526] border border-zinc-800 rounded-2xl p-6 md:p-8">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-arrow-repeat text-amber-400 text-xl"></i>
            </div>

            <div class="flex-1">
                <h1 class="text-2xl font-bold text-white">Reboot Site</h1>
                <p class="text-sm text-zinc-400 mt-2 max-w-2xl">
                    Rebooting the site files will overwrite the current runtime files with the default files from the skel/ directory. This action will not affect the database or any user-generated content. Use this option if you suspect that your site files have been corrupted or modified in a way that is causing issues.
                </p>
            </div>
        </div>

        <?php if ($reboot_error === 'reboot_failed'): ?>
            <div class="mt-6 flex items-center gap-2 bg-rose-500/10 border border-rose-500/20 text-rose-200 px-4 py-2.5 rounded-lg text-sm font-medium">
                <i class="bi bi-exclamation-triangle"></i>
                The site reboot could not complete. Please check filesystem permissions and try again.
            </div>
        <?php endif; ?>

        <form method="POST" class="mt-8" onsubmit="return confirm('Reboot this site files from skel/? This will overwrite site runtime files but not the database. Continue?')">
            <input type="hidden" name="action" value="reboot_site">
            <button type="submit" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-400 text-black font-semibold px-5 py-3 rounded-xl transition">
                <i class="bi bi-arrow-repeat"></i>
                Reboot Site
            </button>
        </form>
    </div>
</div>
