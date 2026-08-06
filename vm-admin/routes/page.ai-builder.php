<?php
#   TITLE   : AI Website Builder
#   DESC    : Placeholder page shell for a future AI-assisted website builder.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 0.1.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/07/27

$store_name = website_data('name') ?: 'Untitled Store';
$store_domain = website_data('domain') ?: '';
$store_theme = website_data('theme') ?: 'default';
$ai_enabled = filter_var($_SERVER['ai_enabled'] ?? false, FILTER_VALIDATE_BOOL);

if (!$ai_enabled) {
    echo '<div class="flex items-center justify-center min-h-[60vh]" style="display:block; margin: auto;">
        <div class="text-center">
            <i class="bi bi-stars text-6xl text-gray-600"></i>
            <h2 class="text-2xl font-bold text-white mt-4">AI Builder Disabled</h2>
            <p class="text-gray-400 mt-2">This page is only available when AI is enabled for the admin session.</p>
            <a href="/vm-admin/' . htmlspecialchars(__DOMAIN__, ENT_QUOTES, "UTF-8") . '/" class="inline-block mt-6 bg-purple-600 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-purple-500 transition-all">Go to Dashboard</a>
        </div>
    </div>';
    return;
}
?>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#252526]  text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-4">
            <section class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] px-5 py-4 shadow-2xl shadow-black/20">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1 text-xs font-medium text-violet-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-violet-400"></span>
                            Prototype page
                        </div>
                        <h2 class="mt-3 text-xl font-semibold tracking-tight text-white sm:text-2xl">AI Website Builder
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm text-zinc-500">
                            This is a page shell for testing the new AI Website Builder experience. No features are
                            wired yet.
                        </p>
                    </div>
                    <a href="/vm-admin/<?php echo htmlspecialchars(__DOMAIN__, ENT_QUOTES, 'UTF-8'); ?>/"
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-zinc-200 transition-colors hover:border-white/20 hover:bg-white/10">
                        <i class="bi bi-grid"></i>
                        Back to admin
                    </a>
                </div>
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-[1fr_0.7fr] gap-4">
                <section
                    class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/20 overflow-hidden">
                    <div class="border-b border-white/5 px-5 py-4">
                        <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Canvas</p>
                        <h3 class="mt-1 text-base font-semibold text-white">Builder workspace</h3>
                    </div>
                    <div class="p-5">
                        <div
                            class="flex min-h-[420px] items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/[0.02]">
                            <div class="max-w-sm text-center">
                                <div
                                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-violet-500/20 bg-violet-500/10 text-violet-300">
                                    <i class="bi bi-stars text-xl"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-white">Empty builder shell</h4>
                                <p class="mt-2 text-sm text-zinc-500">
                                    We’re only testing the page structure for now. This space can later hold prompts,
                                    previews, or generation controls.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section
                        class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/20 overflow-hidden">
                        <div class="border-b border-white/5 px-5 py-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Context</p>
                            <h3 class="mt-1 text-base font-semibold text-white">Store details</h3>
                        </div>
                        <div class="space-y-3 px-5 py-5">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Store name</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    <?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Domain</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    <?php echo htmlspecialchars($store_domain, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Theme</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    <?php echo htmlspecialchars($store_theme, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/20 overflow-hidden">
                        <div class="border-b border-white/5 px-5 py-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Notes</p>
                            <h3 class="mt-1 text-base font-semibold text-white">Test mode</h3>
                        </div>
                        <div class="space-y-3 px-5 py-5">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-sm text-zinc-300">No prompts, no generation, no API wiring.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-sm text-zinc-300">Just the page layout and route scaffold for now.</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>
</div>