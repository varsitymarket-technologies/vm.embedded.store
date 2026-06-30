<?php
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
    $new_name = trim((string)($profile['name'] ?? ''));
    $new_email = trim((string)($profile['email'] ?? ''));
    $new_image = trim((string)($profile['image'] ?? ''));

    if ($new_name === '') {
        header('Location: ?error=name');
        exit;
    }

    if ($new_email === '') {
        header('Location: ?error=email');
        exit;
    }

    $conflict = __DB_MODULE__->query(
        "SELECT id FROM sys_account WHERE (name = ? OR email = ?) AND auth != ? LIMIT 1",
        [$new_name, $new_email, __ACCOUNT_INDEX__]
    );

    if (!empty($conflict)) {
        header('Location: ?error=conflict');
        exit;
    }

    if ($new_image === '') {
        $new_image = $account_image;
    }

    __DB_MODULE__->query(
        "UPDATE sys_account SET name = ?, email = ?, image = ? WHERE auth = ?",
        [$new_name, $new_email, $new_image, __ACCOUNT_INDEX__]
    );

    header('Location: ?saved=1');
    exit;
}

$account_error = $_GET['error'] ?? '';
?>

<div class="flex flex-1 flex-col h-screen overflow-hidden bg-[#09090b] text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-4">
            <section class="rounded-[2rem] border border-white/10 bg-white/[0.03] px-5 py-4 shadow-2xl shadow-black/20 backdrop-blur-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">Account</h2>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300">Profile</span>
                        </div>
                        <p class="max-w-2xl text-sm text-zinc-500">Update your operator identity and review the store you are connected to.</p>
                    </div>
                    <a href="/vm-admin/<?php echo __DOMAIN__; ?>/" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-zinc-200 transition-colors hover:border-white/20 hover:bg-white/10">
                        Back to admin
                    </a>
                </div>
                <?php if (isset($_GET['saved'])): ?>
                    <p class="mt-3 text-xs text-zinc-400">Profile saved.</p>
                <?php elseif ($account_error === 'name'): ?>
                    <p class="mt-3 text-xs text-rose-300">Please add a display name.</p>
                <?php elseif ($account_error === 'email'): ?>
                    <p class="mt-3 text-xs text-rose-300">Please add an email address.</p>
                <?php elseif ($account_error === 'conflict'): ?>
                    <p class="mt-3 text-xs text-rose-300">That name or email is already used by another account.</p>
                <?php endif; ?>
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-[1.25fr_0.75fr] gap-4">
                <section class="rounded-[2rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/30 overflow-hidden">
                    <div class="border-b border-white/5 px-5 py-4">
                        <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">Profile</p>
                        <h3 class="mt-1 text-base font-semibold text-white">Edit your details</h3>
                    </div>
                    <form method="POST" class="space-y-5 px-5 py-5">
                        <input type="hidden" name="action" value="save_account_profile">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                                <?php if (!empty($account_image)): ?>
                                    <img src="<?= htmlspecialchars($account_image, ENT_QUOTES, 'UTF-8') ?>" alt="Account avatar" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <span class="text-lg font-semibold text-white"><?= htmlspecialchars(strtoupper(substr($account_name, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white"><?= htmlspecialchars($account_name, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-zinc-500"><?= htmlspecialchars($account_email ?: 'No email set', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-zinc-400">Display name</label>
                                <input type="text" name="account[name]" value="<?= htmlspecialchars($account_name, ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-white/10 bg-[#07070a] px-4 py-3 text-sm text-white focus:border-cyan-500/50 focus:outline-none" placeholder="Your name">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-zinc-400">Email address</label>
                                <input type="email" name="account[email]" value="<?= htmlspecialchars($account_email, ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-white/10 bg-[#07070a] px-4 py-3 text-sm text-white focus:border-cyan-500/50 focus:outline-none" placeholder="you@example.com">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-zinc-400">Avatar URL</label>
                            <input type="url" name="account[image]" value="<?= htmlspecialchars($account_image, ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-white/10 bg-[#07070a] px-4 py-3 text-sm text-white focus:border-cyan-500/50 focus:outline-none" placeholder="https://...">
                            <p class="mt-1.5 text-xs text-zinc-500">Optional. Used only for the admin profile display.</p>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-cyan-500 px-4 py-2 text-sm font-medium text-black transition-colors hover:bg-cyan-400">
                                <i class="bi bi-check2"></i>
                                Save profile
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-[2rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/30 overflow-hidden">
                        <div class="border-b border-white/5 px-5 py-4">
                            <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">Linked store</p>
                            <h3 class="mt-1 text-base font-semibold text-white">Current context</h3>
                        </div>
                        <div class="space-y-3 px-5 py-5">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Store name</p>
                                <p class="mt-2 text-sm font-medium text-white"><?= htmlspecialchars($account_store_name, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Domain</p>
                                <p class="mt-2 text-sm font-medium text-white"><?= htmlspecialchars($account_store_domain, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Theme</p>
                                <p class="mt-2 text-sm font-medium text-white"><?= htmlspecialchars($account_store_theme, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[2rem] border border-white/10 bg-[#0b0b0f] shadow-2xl shadow-black/30 overflow-hidden">
                        <div class="border-b border-white/5 px-5 py-4">
                            <p class="text-xs uppercase tracking-[0.3em] text-zinc-600">Security</p>
                            <h3 class="mt-1 text-base font-semibold text-white">Account identifiers</h3>
                        </div>
                        <div class="space-y-3 px-5 py-5 text-sm">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Account auth</p>
                                <p class="mt-2 break-all font-mono text-xs text-zinc-200"><?= htmlspecialchars(substr($account_auth, 0, 10) . '...' . substr($account_auth, -6), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] uppercase tracking-[0.3em] text-zinc-600">Joined</p>
                                <p class="mt-2 text-zinc-200"><?= $account_created_at ? htmlspecialchars(date('M j, Y', strtotime($account_created_at)), ENT_QUOTES, 'UTF-8') : 'Unknown' ?></p>
                            </div>
                            <a href="/logout.php" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-medium text-zinc-200 transition-colors hover:border-rose-500/20 hover:bg-rose-500/10 hover:text-rose-200">
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
