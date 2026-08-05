<?php
$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$site_url = "http://" . __DOMAIN__;

// Init Local Deployment DB
$db = initiate_web_database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'publish') {
            @include_once dirname(dirname(dirname(__FILE__))) . "/services/export.store.source.php";
            $html_content = export_application(__DOMAIN__, __WEBSITE_DOMAIN__);

            $user = __USERNAME__;
            $res = deploy_engine_website(__DOMAIN__, $html_content, $user);

            $hash = substr(md5($html_content . time()), 0, 10);

            try {
                $db->query("INSERT INTO deployments (version_hash, html_content) VALUES (?, ?)", [$hash, $html_content]);
            } catch (Exception $e) {
                $db->query("CREATE TABLE IF NOT EXISTS deployments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    version_hash TEXT NOT NULL,
                    html_content TEXT NOT NULL,
                    status TEXT DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $db->query("INSERT INTO deployments (version_hash, html_content) VALUES (?, ?)", [$hash, $html_content]);
            }

            header("Location: {$admin_base}publish?success=deployed");
            exit;
        } elseif ($_POST['action'] === 'rollback') {
            $id = $_POST['deployment_id'] ?? 0;

            try {
                $row = $db->query("SELECT html_content FROM deployments WHERE id = ? LIMIT 1", [$id]);

                if (!empty($row[0])) {
                    $html_content = $row[0]['html_content'];
                    $user = __USERNAME__;
                    $res = deploy_engine_website(__DOMAIN__, $html_content, $user);

                    $hash = substr(md5($html_content . time()), 0, 10);
                    $db->query("INSERT INTO deployments (version_hash, html_content, status) VALUES (?, ?, ?)", [$hash, $html_content, 'rollback']);

                    header("Location: {$admin_base}publish?success=rollback");
                    exit;
                }
            } catch (Exception $e) {
            }
        }
    }
}

$deployments = [];
try {
    $deployments = $db->query("SELECT * FROM deployments ORDER BY created_at DESC");
} catch (Exception $e) {
}

$verification_domain = engine_validate_domain_ownership(__DOMAIN__);
$domain_connected = engine_validate_domain(__DOMAIN__);
$publish_state = $domain_connected === true ? 'connected' : (($verification_domain === true) ? 'verified' : 'unverified');

if ($domain_connected == true) {
    $domain_source = "http://" . __DOMAIN__;
} else if ($verification_domain == true) {
    $domain_source = "http://" . get_domain() . "/pages/error.500.deployment.php";
} else {
    $domain_source = "http://" . get_domain() . "/pages/error.500.verification.php";
}

?>
<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden bg-[#1b1b1c] text-zinc-100">
    <?php @include_once "header.php"; ?>

    <?php if (!isset($_SERVER['__ENGINE_SOURCE__'])): ?>
        <div class="flex items-center justify-center flex-1 px-6" style="min-height: 60vh;">
            <div class="w-full max-w-xl rounded-3xl border border-amber-500/20 bg-amber-500/10 p-6 text-amber-50 shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
                <div class="flex items-start gap-4">
                    <span class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center shrink-0">
                        <i class="bi bi-exclamation-triangle text-amber-400 text-lg"></i>
                    </span>
                    <div>
                        <h3 class="text-white font-semibold text-sm">Embedded Engine not connected</h3>
                        <p class="text-zinc-300 text-xs mt-1 leading-relaxed">This embedded engine is not connected to the
                            remote server. Your website cannot be published with this engine.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <main class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">

            <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#242424] text-white">
                <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)] lg:p-8">
                    <div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Publish your store with a clean, controlled release flow.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-white sm:text-base">
                            Review your live connection, verify DNS status, push a fresh deployment, and keep every version available for rollback.
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button onclick="document.getElementById('publishForm').submit();" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <span>Publish Changes</span>
                            </button>
                            <button onclick="window.location.href='<?php echo $admin_base; ?>deploy'" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                                <i class="bi bi-github"></i>
                                <span>Deploy with GitHub</span>
                            </button>
                            <form id="publishForm" method="POST" class="hidden">
                                <input type="hidden" name="action" value="publish">
                            </form>
                        </div>

                    </div>

                </div>
            </section>

            <div id="publishProgress" class="hidden rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-400 border-t-transparent"></div>
                    <div class="text-sm">
                        <span class="font-semibold text-emerald-300">Publishing live modifications...</span>
                        <span class="ml-1 text-emerald-100/80">CDN nodes are syncing cache files.</span>
                    </div>
                </div>
            </div>

            <section style="display:block;" class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(280px,0.8fr)]">
                <div class="space-y-6">
                    <?php if (($verification_domain !== true) && ($domain_connected !== true)): ?>
                        <div class="rounded-3xl border border-amber-500/20 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="block gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10">
                                        <i class="bi bi-shield-exclamation text-lg text-amber-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-white">Verify domain ownership</h3>
                                        <p class="mt-1 text-xs leading-relaxed text-zinc-400">Add the TXT record below to confirm this domain belongs to your store.</p>
                                        <div class="mt-4 rounded-2xl border border-white/10 bg-[#1b1b1c] p-4 font-mono text-[11px] text-zinc-200">
                                            <div class="flex justify-between gap-4"><span class="text-zinc-500">Type</span><span>TXT</span></div>
                                            <div class="mt-2 flex justify-between gap-4"><span class="text-zinc-500">Name</span><span>@</span></div>
                                            <div class="mt-2 flex justify-between gap-4"><span class="text-zinc-500">Value</span><span class="truncate select-all text-right" title="Click to select value"><?php echo hash("sha256", __DOMAIN__); ?></span></div>
                                        </div>
                                    </div>
                                </div>
                                <button onclick="document.getElementById('dnsModal').showModal()" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white transition hover:bg-white/10">
                                    Verify domain
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (($domain_connected == false) && ($verification_domain == true)): ?>
                        <div class="rounded-3xl border border-sky-500/20 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="block gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-500/10">
                                        <i class="bi bi-link-45deg text-lg text-sky-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-white">Connect your domain</h3>
                                        <p class="mt-1 text-xs leading-relaxed text-zinc-400">Point your domain at the embedded engine so the live store can go public.</p>
                                        <div class="mt-4 rounded-2xl border border-white/10 bg-[#1b1b1c] p-4 font-mono text-[11px] text-zinc-200">
                                            <div class="flex justify-between gap-4"><span class="text-zinc-500">Type</span><span>A Record</span></div>
                                            <div class="mt-2 flex justify-between gap-4"><span class="text-zinc-500">Name</span><span>@</span></div>
                                            <div class="mt-2 flex justify-between gap-4"><span class="text-zinc-500">Value</span><span>84.12.34.23</span></div>
                                        </div>
                                    </div>
                                </div>
                                <button onclick="document.getElementById('dnsModal').showModal()" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white transition hover:bg-white/10">
                                    Connect domain
                                </button>
                            </div>
                        </div>
                    <?php elseif ($domain_connected == true): ?>
                        <div class="rounded-3xl border border-emerald-500/20 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10">
                                    <i class="bi bi-check-circle text-lg text-emerald-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-white">Engine connected and ready</h3>
                                    <p class="mt-1 text-xs text-zinc-400">Your custom domain is verified and pointed correctly. Publishing is ready.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="overflow-hidden rounded-3xl border border-white/10 bg-[#202123] shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                        <div class="flex items-center justify-between border-b border-white/10 bg-white/5 px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-red-500/60"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500/60"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500/60"></span>
                                </div>
                                <span class="select-all font-mono text-xs text-zinc-400"><?php echo $site_url; ?></span>
                            </div>
                            <a href="<?php echo $domain_source ?>" target="_blank" class="inline-flex items-center gap-1 text-xs font-medium text-zinc-300 transition hover:text-white">
                                <span>Open live site</span>
                                <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                            </a>
                        </div>
                        <iframe src="<?php echo $domain_source ?>" class="w-full border-none bg-zinc-950" style="height: 60vh;" frameborder="0"></iframe>
                    </div>
                </div>

                <aside class="space-y-6 pt-6">

                    <section class="rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Version history</p>
                        <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                            <div class="max-h-[520px] overflow-y-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="sticky top-0 bg-[#202123]">
                                        <tr class="border-b border-white/10 text-[11px] uppercase tracking-[0.18em] text-zinc-500">
                                            <th class="px-4 py-3 font-semibold">Version</th>
                                            <th class="px-4 py-3 font-semibold">Status</th>
                                            <th class="px-4 py-3 pr-5 text-right font-semibold">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/10 text-zinc-300">
                                        <?php if (empty($deployments)): ?>
                                            <tr>
                                                <td colspan="3" class="px-4 py-10 text-center">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/5">
                                                            <i class="bi bi-cloud-arrow-up text-zinc-500 text-lg"></i>
                                                        </span>
                                                        <p class="text-sm font-medium text-zinc-300">No deployments yet</p>
                                                        <p class="text-xs text-zinc-500">Publish your store to create the first version.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($deployments as $index => $dep): ?>
                                                <tr class="transition hover:bg-white/[0.03]">
                                                    <td class="px-4 py-4 font-mono text-xs <?php echo $index === 0 ? 'text-white' : 'text-zinc-400'; ?>">
                                                        v_<?php echo htmlspecialchars($dep['version_hash']); ?>
                                                        <div class="mt-1 text-[10px] text-zinc-600">UID <?php echo $dep['id']; ?></div>
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        <?php if ($index === 0): ?>
                                                            <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-1 text-[11px] font-semibold text-emerald-300 ring-1 ring-emerald-500/20">Current</span>
                                                        <?php elseif ($dep['status'] === 'rollback'): ?>
                                                            <span class="inline-flex items-center rounded-full bg-rose-500/10 px-2 py-1 text-[11px] font-semibold text-rose-300 ring-1 ring-rose-500/20">Rolled back</span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center rounded-full bg-white/5 px-2 py-1 text-[11px] font-semibold text-zinc-400 ring-1 ring-white/10">Archived</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-4 py-4 pr-5 text-right text-xs text-zinc-400">
                                                        <?php echo date('M j, Y g:i A', strtotime($dep['created_at'])); ?>
                                                        <div class="mt-2">
                                                            <?php if ($index !== 0): ?>
                                                                <form method="POST" onsubmit="return confirm('Are you sure you want to rollback to this version?');" class="inline">
                                                                    <input type="hidden" name="action" value="rollback">
                                                                    <input type="hidden" name="deployment_id" value="<?php echo $dep['id']; ?>">
                                                                    <button type="submit" class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-white/10">
                                                                        Restore
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <span class="text-zinc-500">Live</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </aside>
            </section>
        </main>

    <?php endif; ?>
</div>

<!-- DNS Modal -->
<dialog id="dnsModal" class="bg-[#202123] border border-white/10 rounded-3xl p-0 w-[90%] max-w-md shadow-2xl text-white overflow-hidden">
    <div class="px-5 py-4 border-b border-white/10">
        <h3 class="text-white font-semibold text-sm">DNS Authentication Required</h3>
        <p class="text-xs text-zinc-500 mt-1">Verify domain ownership by adding the following DNS record.</p>
    </div>

    <div class="p-5">
        <?php if (($domain_connected == false) && ($verification_domain == true)): ?>
            <p class="text-xs text-zinc-400 leading-relaxed mb-4">To complete domain connection, add the following A record
                to your DNS provider configurations (Cloudflare, GoDaddy, Namecheap, etc.).</p>
            <table class="w-full text-xs font-mono bg-[#1b1b1c] border border-white/10 rounded-2xl p-3 block space-y-2">
                <tr class="flex">
                    <td class="text-zinc-500 w-16">Type</td>
                    <td class="text-zinc-300">A Record</td>
                </tr>
                <tr class="flex">
                    <td class="text-zinc-500 w-16">Name</td>
                    <td class="text-zinc-300">@</td>
                </tr>
                <tr class="flex">
                    <td class="text-zinc-500 w-16">Value</td>
                    <td class="text-zinc-300"><?php echo $_SERVER['__SERVER_IP__'] ?? 'Unconfigured' ?></td>
                </tr>
                <tr class="flex">
                    <td class="text-zinc-500 w-16">TTL</td>
                    <td class="text-zinc-300">3600 (or Automatic)</td>
                </tr>
            </table>
        <?php else: ?>
            <p class="text-xs text-zinc-400 leading-relaxed mb-4">To verify ownership of your domain, please add the
                following TXT record to your DNS provider configurations (Cloudflare, GoDaddy, Namecheap, etc.).</p>
            <table class="w-full text-xs font-mono bg-[#1b1b1c] border border-white/10 rounded-2xl p-3 block space-y-2">
                <tr class="flex">
                    <td class="text-zinc-500 w-16">Type</td>
                    <td class="text-zinc-300">TXT</td>
                </tr>
                <tr class="flex">
                    <td class="text-zinc-500 w-16">Name</td>
                    <td class="text-zinc-300">@</td>
                </tr>
                <tr class="flex">
                    <td class="text-zinc-500 w-16">Value</td>
                    <td class="text-zinc-300 truncate select-all" title="Click to select value">
                        vm_<?php echo hash("sha256", __DOMAIN__); ?></td>
                </tr>
                <tr class="flex">
                    <td class="text-zinc-500 w-16">TTL</td>
                    <td class="text-zinc-300">3600 (or Automatic)</td>
                </tr>
            </table>
        <?php endif; ?>
        <p class="text-[10px] text-zinc-600 mt-4 leading-normal">Note: DNS changes can take anywhere from a few minutes
            up to 24 hours to propagate globally.</p>
    </div>

    <div class="px-5 py-4 border-t border-zinc-800 flex justify-end gap-2 bg-[#252526]/50">
        <button onclick="document.getElementById('dnsModal').close()"
            class="px-3 py-1.5 text-xs font-medium text-zinc-400 hover:text-white transition-colors">Cancel</button>
        <button id="verifyBtn"
            class="bg-[#008060] hover:bg-[#006e52] text-white px-4 py-1.5 rounded-full text-xs font-medium transition-colors">Check
            DNS Record</button>
    </div>
</dialog>

<script>
    const modal = document.getElementById('dnsModal');
    const verifyBtn = document.getElementById('verifyBtn');

    if (verifyBtn) {
        verifyBtn.addEventListener('click', () => {
            verifyBtn.textContent = 'Verifying...';
            verifyBtn.disabled = true;

            setTimeout(() => {
                alert('DNS record not found yet. Please wait a few minutes and try again.');
                verifyBtn.textContent = 'Check DNS Record';
                verifyBtn.disabled = false;
            }, 1500);
        });
    }

    if (modal) {
        modal.addEventListener('click', (e) => {
            const dialogDimensions = modal.getBoundingClientRect();
            if (
                e.clientX < dialogDimensions.left ||
                e.clientX > dialogDimensions.right ||
                e.clientY < dialogDimensions.top ||
                e.clientY > dialogDimensions.bottom
            ) {
                modal.close();
            }
        });
    }

    const publishForm = document.getElementById('publishForm');
    if (publishForm) {
        publishForm.addEventListener('submit', () => {
            const progress = document.getElementById('publishProgress');
            if (progress) progress.classList.remove('hidden');
        });
    }
</script>