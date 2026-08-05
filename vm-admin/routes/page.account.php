<?php
#   TITLE   : Admin Profile Page
#   DESC    : The Admin profile page for the control panel
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 2.0.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/07/27

$account_row = __DB_MODULE__->query("SELECT * FROM sys_account WHERE auth = ? LIMIT 1", [__ACCOUNT_INDEX__])[0] ?? [];
$account_name = $account_row['name'] ?? (__USERNAME__ ?? 'User');
$account_email = $account_row['email'] ?? '';
$account_image = $account_row['image'] ?? '';
$account_auth = $account_row['auth'] ?? (__ACCOUNT_INDEX__ ?? '');
$account_created_at = $account_row['created_at'] ?? '';
$account_store_name = website_data('name') ?: 'Untitled Store';
$account_store_domain = website_data('domain') ?: '';
$account_store_theme = website_data('theme') ?: 'default';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_account_profile') {
    $profile = $_POST['account'] ?? [];
    $new_image = trim((string) ($profile['image'] ?? ''));

    if ($new_image === '') {
        $new_image = $account_image;
    }

    __DB_MODULE__->query(
        "UPDATE sys_account SET image = ? WHERE auth = ?",
        [$new_image, __ACCOUNT_INDEX__]
    );

    header('Location: ?saved=1');
    exit;
}

$account_error = $_GET['error'] ?? '';
$saved = isset($_GET['saved']);
$joined_label = $account_created_at ? date('M j, Y', strtotime($account_created_at)) : 'Unknown';
$initial = strtoupper(substr($account_name ?: $account_email ?: 'U', 0, 1));
?>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#252526]  text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-4">
            <section class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] px-5 py-4 shadow-2xl shadow-black/20">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-5">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            Operator profile
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">Account</h2>
                            <p class="max-w-2xl text-sm text-zinc-500">Update your operator identity and review the
                                store you are connected to.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="/vm-admin/<?php echo __DOMAIN__; ?>/"
                                class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-zinc-200 transition-colors hover:border-white/20 hover:bg-white/10">
                                <i class="bi bi-grid"></i>
                                Back to admin
                            </a>
                            <a href="/api/docs.html#customers"
                                class="inline-flex items-center gap-2 rounded-full border border-violet-500/20 bg-violet-500/10 px-4 py-2 text-sm font-medium text-violet-200 transition-colors hover:border-violet-400/30 hover:bg-violet-500/15">
                                <i class="bi bi-journal-text"></i>
                                API docs
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Display name</p>
                            <p class="mt-2 text-sm font-semibold text-white">
                                <?= htmlspecialchars($account_name, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mt-1 text-xs text-zinc-500">
                                <?= htmlspecialchars($account_email ?: 'No email set', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Store</p>
                            <p class="mt-2 text-sm font-semibold text-white">
                                <?= htmlspecialchars($account_store_name, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mt-1 text-xs text-zinc-500">
                                <?= htmlspecialchars($account_store_domain, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Theme</p>
                            <p class="mt-2 text-sm font-semibold text-white">
                                <?= htmlspecialchars($account_store_theme, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mt-1 text-xs text-zinc-500">Current storefront theme</p>
                        </div>
                    </div>
                </div>
            </section>

            <?php if ($saved): ?>
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200">
                    Profile saved successfully.
                </div>
            <?php elseif ($account_error === 'name'): ?>
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-200">
                    Please add a display name.
                </div>
            <?php elseif ($account_error === 'email'): ?>
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-200">
                    Please add an email address.
                </div>
            <?php elseif ($account_error === 'conflict'): ?>
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-200">
                    That name or email is already used by another account.
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-[1.15fr_0.85fr] gap-4">
                <section
                    class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/20 overflow-hidden">
                    <div class="border-b border-white/5 px-5 py-4">
                        <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Profile</p>
                        <h3 class="mt-1 text-base font-semibold text-white">Edit your details</h3>
                    </div>

                    <form method="POST" class="space-y-5 px-5 py-5">
                        <input type="hidden" name="action" value="save_account_profile">

                        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-white/5 ring-1 ring-white/5">
                                    <?php if (!empty($account_image)): ?>
                                        <img src="<?= htmlspecialchars($account_image, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Account avatar" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <span
                                            class="text-lg font-semibold text-white"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-white">
                                        <?= htmlspecialchars($account_name, ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="truncate text-xs text-zinc-500">
                                        <?= htmlspecialchars($account_email ?: 'No email set', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p class="mt-2 text-xs text-zinc-600">Operator ID: <span
                                            class="font-mono text-zinc-400"><?= htmlspecialchars($account_auth, ENT_QUOTES, 'UTF-8') ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Joined</p>
                                <p class="mt-2 text-sm font-medium text-zinc-200">
                                    <?= htmlspecialchars($joined_label, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Connection</p>
                                <p class="mt-2 text-sm font-medium text-zinc-200">Active admin session</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Avatar URL</label>
                            <input type="url" name="account[image]"
                                value="<?= htmlspecialchars($account_image, ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full rounded-xl border border-white/10 bg-[#07070a] px-4 py-3 text-sm text-white focus:border-violet-500/50 focus:outline-none"
                                placeholder="https://...">
                            <p class="mt-1.5 text-xs text-zinc-500">Used only for the admin profile display.</p>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-full bg-violet-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-violet-500">
                                <i class="bi bi-check2"></i>
                                Save profile
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-4">
                    <section
                        class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/20 overflow-hidden">
                        <div class="border-b border-white/5 px-5 py-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Workspace</p>
                            <h3 class="mt-1 text-base font-semibold text-white">Store context</h3>
                        </div>
                        <div class="space-y-3 px-5 py-5">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Store name</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    <?= htmlspecialchars($account_store_name, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Website</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    <?= htmlspecialchars($account_store_domain, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Theme</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    <?= htmlspecialchars($account_store_theme, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[1rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/20 overflow-hidden">
                        <div class="border-b border-white/5 px-5 py-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Actions</p>
                            <h3 class="mt-1 text-base font-semibold text-white">Account operations</h3>
                        </div>
                        <div class="space-y-3 px-5 py-5">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.18em] text-zinc-500">Joined</p>
                                <p class="mt-2 text-zinc-200">
                                    <?= htmlspecialchars($joined_label, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <a href="/logout.php"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-zinc-200 transition-colors hover:border-rose-500/20 hover:bg-rose-500/10 hover:text-rose-200">
                                <i class="bi bi-box-arrow-right"></i>
                                Sign out
                            </a>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>
</div>