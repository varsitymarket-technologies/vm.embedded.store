<?php
#   TITLE   : Admin Header Card
#   DESC    : The Admin Header Card
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 2.0.0
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/07/27

$header_account_name = __USERNAME__ ?? 'User';
$header_account_initial = strtoupper(substr($header_account_name ?: 'U', 0, 1));
$header_account_image = '';

try {
    $header_account_row = __DB_MODULE__->query("SELECT image, name FROM sys_account WHERE auth = ? LIMIT 1", [__ACCOUNT_INDEX__])[0] ?? [];
    $header_account_image = trim((string) ($header_account_row['image'] ?? ''));
    if (!empty($header_account_row['name'])) {
        $header_account_name = $header_account_row['name'];
        $header_account_initial = strtoupper(substr($header_account_name ?: 'U', 0, 1));
    }
} catch (\Throwable $e) {
    $header_account_image = '';
}
?>
<header class="admin-topbar flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10">
    <button id="sidebarOpen" onclick="open_menu()" class="admin-btn text-gray-400 hover:text-white md:hidden">
        <i class="bi bi-list text-2xl"></i>
    </button>
    <div class="flex items-center gap-4 ml-auto">
        <div class="relative group">
            <button onclick="window.location.href='<?php echo $admin_base; ?>account'" class="admin-btn flex items-center gap-2 text-sm font-medium text-white focus:outline-none">
                <div class="admin-badge h-8 w-8 overflow-hidden rounded-full flex items-center justify-center ring-1 ring-white/10">
                    <?php if (!empty($header_account_image)): ?>
                        <img src="<?php echo htmlspecialchars($header_account_image, ENT_QUOTES, 'UTF-8'); ?>" alt="Account avatar" class="h-full w-full object-cover">
                    <?php else: ?>
                        <span class="text-white text-xs font-bold"><?php echo htmlspecialchars($header_account_initial, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <span class="hidden md:block"><?php echo htmlspecialchars($header_account_name, ENT_QUOTES, 'UTF-8'); ?></span>
            </button>
        </div>
    </div>
</header>
