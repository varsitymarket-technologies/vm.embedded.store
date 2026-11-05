<?php
$console_store_url = __WEBSITE_DOMAIN__ ?? '';
$console_store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
if (empty($console_store_record) && !empty($domain)) {
    $console_store_record = $db_engine->query("SELECT * FROM sys_websites WHERE domain = ? LIMIT 1", [$domain]);
}
$console_store_id = $console_store_record[0]['id'] ?? '';
$console_secret = get_setting($db_site, 'console_secret_key', '');
if (empty($console_secret)) {
    $console_secret = 'vm_sec_' . bin2hex(random_bytes(12));
    if ($db_site !== null) {
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('console_secret_key', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$console_secret, $console_secret]);
    }
}
?>

<a href="?tab=general"
    class="mb-6 inline-flex items-center gap-2 text-sm font-medium text-zinc-400 transition-colors hover:text-white">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(340px,.85fr)]">
    <section class="overflow-hidden rounded-2xl border border-zinc-800 bg-[#202022]">
        <div class="border-b border-zinc-800 px-5 py-5 sm:px-7">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[.18em] text-violet-300">Campus Studio</p>
                    <h1 class="text-2xl font-bold tracking-tight text-white">Mobile app connection</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-zinc-400">Connect the upcoming Android companion app to this store. Keep your connection details private.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full border border-violet-400/20 bg-violet-400/10 px-3 py-1.5 text-xs font-medium text-violet-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-violet-300"></span> Android app in development
                </span>
            </div>
        </div>

        <div class="space-y-5 p-5 sm:p-7">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-800 bg-[#18181a] p-4">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-sm font-bold text-violet-300">1</span>
                        <div><h2 class="text-sm font-semibold text-white">Install Campus Studio</h2><p class="mt-0.5 text-xs text-zinc-500">Google Play release planned</p></div>
                    </div>
                    <p class="text-xs leading-5 text-zinc-400">The Android app is being built. This preview shows the planned connection and store dashboard experience.</p>
                    <span class="mt-3 inline-flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-900/70 px-3 py-2 text-xs text-zinc-400"><i class="bi bi-android2 text-emerald-400"></i> Coming soon</span>
                </div>

                <div class="rounded-xl border border-zinc-800 bg-[#18181a] p-4">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/15 text-sm font-bold text-sky-300">2</span>
                        <div><h2 class="text-sm font-semibold text-white">Connect this store</h2><p class="mt-0.5 text-xs text-zinc-500">Use the details below</p></div>
                    </div>
                    <p class="mb-3 text-xs leading-5 text-zinc-400">In the app, choose <strong class="font-medium text-zinc-200">Add a store</strong>, then enter this URL and Store ID.</p>
                    <div class="space-y-2">
                        <div class="rounded-lg bg-[#252526] px-3 py-2">
                            <span class="text-[10px] uppercase tracking-wider text-zinc-500">Store URL</span>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <code id="console_store_url" class="break-all font-mono text-xs text-white"><?= htmlspecialchars((string) $console_store_url, ENT_QUOTES, 'UTF-8') ?></code>
                                <button type="button" data-copy-target="console_store_url" aria-label="Copy store URL" class="shrink-0 text-zinc-500 hover:text-white"><i class="bi bi-copy"></i></button>
                            </div>
                        </div>
                        <div class="rounded-lg bg-[#252526] px-3 py-2">
                            <span class="text-[10px] uppercase tracking-wider text-zinc-500">Store ID</span>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <code id="console_store_id" class="break-all font-mono text-xs text-white"><?= htmlspecialchars((string) $console_store_id, ENT_QUOTES, 'UTF-8') ?></code>
                                <button type="button" data-copy-target="console_store_id" aria-label="Copy store ID" class="shrink-0 text-zinc-500 hover:text-white"><i class="bi bi-copy"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-amber-500/20 bg-amber-500/[.04] p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-400/10 text-amber-300"><i class="bi bi-shield-lock"></i></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-amber-200">Connection secret</h2>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-300/70">Keep private</span>
                        </div>
                        <p class="mt-1 mb-3 text-xs leading-5 text-zinc-400">This secret is intended for the Campus Studio connector. Never share it publicly.</p>
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-[#18181a] p-3">
                            <code class="min-w-0 flex-1 break-all font-mono text-xs text-zinc-100" id="connection_secret"><?= htmlspecialchars((string) $console_secret, ENT_QUOTES, 'UTF-8') ?></code>
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="button" data-copy-target="connection_secret" class="rounded-lg bg-zinc-800 px-3 py-2 text-xs text-white transition-colors hover:bg-zinc-700"><i class="bi bi-copy"></i> Copy</button>
                                <form method="POST" onsubmit="return confirm('Regenerating will disconnect connected apps. Continue?')">
                                    <input type="hidden" name="action" value="save_console">
                                    <input type="hidden" name="console[regenerate_secret]" value="1">
                                    <button type="submit" class="rounded-lg bg-amber-500/10 px-3 py-2 text-xs text-amber-300 transition-colors hover:bg-amber-500/20"><i class="bi bi-arrow-repeat"></i> Regenerate</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-sky-500/15 bg-sky-500/[.04] p-4">
                <i class="bi bi-info-circle mt-0.5 text-sky-300"></i>
                <p class="text-xs leading-5 text-zinc-400"><strong class="font-semibold text-sky-200">Integration status:</strong> the Android app preview is ready for design review. A secure mobile management API still needs to be connected before the app can manage live store data.</p>
            </div>
        </div>
    </section>

    <aside class="overflow-hidden rounded-2xl border border-zinc-800 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-violet-950/40 via-[#171719] to-[#111112] p-5 sm:p-7 xl:sticky xl:top-6">
        <div class="mb-5 flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-300">App preview</p>
                <h2 class="mt-1 text-lg font-bold text-white">Your store, on the go</h2>
            </div>
            <span class="rounded-full border border-zinc-700 bg-black/20 px-2.5 py-1 text-[10px] font-medium text-zinc-400">Concept</span>
        </div>

        <div class="mx-auto w-full max-w-[310px] rounded-[2.6rem] border-[7px] border-[#343438] bg-[#080809] p-2 shadow-[0_25px_70px_#0009]">
            <div class="relative min-h-[500px] overflow-hidden rounded-[2rem] bg-[#111113] px-4 pb-5 pt-5">
                <div class="mx-auto mb-5 h-1.5 w-16 rounded-full bg-zinc-700"></div>
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-500 text-white"><i class="bi bi-grid-1x2-fill"></i></div>
                        <div><p class="text-[10px] text-zinc-500">CAMPUS STUDIO</p><p class="text-sm font-semibold text-white">My workspace</p></div>
                    </div>
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-800 text-zinc-300"><i class="bi bi-bell"></i></span>
                </div>

                <div class="mb-4 rounded-2xl border border-violet-300/10 bg-gradient-to-br from-violet-600 to-indigo-800 p-4">
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-white/15 px-2 py-1 text-[9px] font-medium text-white">CONNECTED STORE</span>
                        <span class="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_12px_#6ee7b7]"></span>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-white"><?= htmlspecialchars((string) ($console_store_record[0]['name'] ?? 'Your Store'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="mt-1 truncate text-[10px] text-violet-100/80"><?= htmlspecialchars((string) $console_store_url, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="mt-4 flex items-center gap-2 text-[10px] text-white/80"><i class="bi bi-arrow-repeat"></i> Last synced just now</div>
                </div>

                <div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-semibold text-white">Overview</h3><span class="text-[9px] text-zinc-500">TODAY</span></div>
                <div class="mb-4 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-zinc-800 bg-[#19191c] p-3"><span class="text-[9px] text-zinc-500">ORDERS</span><div class="mt-1 flex items-end justify-between"><strong class="text-xl text-white">—</strong><i class="bi bi-bag-check text-violet-300"></i></div><p class="mt-1 text-[9px] text-zinc-600">Live data after setup</p></div>
                    <div class="rounded-xl border border-zinc-800 bg-[#19191c] p-3"><span class="text-[9px] text-zinc-500">REVENUE</span><div class="mt-1 flex items-end justify-between"><strong class="text-xl text-white">—</strong><i class="bi bi-graph-up-arrow text-emerald-300"></i></div><p class="mt-1 text-[9px] text-zinc-600">Live data after setup</p></div>
                </div>

                <div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-semibold text-white">Quick actions</h3><span class="text-[9px] text-zinc-500">PREVIEW</span></div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-xl border border-zinc-800 bg-[#19191c] px-2 py-3 text-center"><i class="bi bi-box-seam text-sm text-sky-300"></i><p class="mt-1.5 text-[9px] text-zinc-300">Products</p></div>
                    <div class="rounded-xl border border-zinc-800 bg-[#19191c] px-2 py-3 text-center"><i class="bi bi-receipt text-sm text-amber-300"></i><p class="mt-1.5 text-[9px] text-zinc-300">Orders</p></div>
                    <div class="rounded-xl border border-zinc-800 bg-[#19191c] px-2 py-3 text-center"><i class="bi bi-shop text-sm text-violet-300"></i><p class="mt-1.5 text-[9px] text-zinc-300">Store</p></div>
                </div>

                <div class="absolute inset-x-4 bottom-3 rounded-xl border border-amber-300/10 bg-amber-400/[.07] px-3 py-2 text-center text-[9px] text-amber-100/80"><i class="bi bi-stars mr-1"></i> Sample interface — live connection in progress</div>
            </div>
        </div>
        <p class="mx-auto mt-4 max-w-xs text-center text-xs leading-5 text-zinc-500">A first look at the Campus Studio Android companion. Final screens may evolve during development.</p>
    </aside>
</div>

<script>
    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.getElementById(button.dataset.copyTarget);
            if (!target) return;
            const original = button.innerHTML;
            try {
                await navigator.clipboard.writeText(target.textContent.trim());
                button.innerHTML = '<i class="bi bi-check2"></i> Copied';
                setTimeout(() => { button.innerHTML = original; }, 1800);
            } catch (error) {
                button.innerHTML = '<i class="bi bi-exclamation-circle"></i> Copy failed';
                setTimeout(() => { button.innerHTML = original; }, 1800);
            }
        });
    });
</script>