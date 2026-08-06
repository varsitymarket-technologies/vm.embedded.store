<?php
$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$site_dir = dirname(dirname(dirname(__FILE__))) . '/sites/' . (__DOMAIN__ ?? '');
$pages_dir = $site_dir . '/data/pages';
$builder_cache = $site_dir . '/builder.cache.html';

if (!is_dir($pages_dir)) {
    @mkdir($pages_dir, 0775, true);
}

function vm_page_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-') ?: 'page';
}

function vm_page_label(string $slug): string
{
    return ucfirst(str_replace(['-', '_'], ' ', $slug));
}

function vm_page_scan(string $pages_dir): array
{
    $pages = [];
    foreach (glob($pages_dir . '/*.page') ?: [] as $file) {
        $slug = basename($file, '.page');
        $pages[] = [
            'slug' => $slug,
            'label' => vm_page_label($slug),
            'modified' => @filemtime($file) ?: time(),
            'file' => $file,
        ];
    }

    usort($pages, fn($a, $b) => strcmp($a['slug'], $b['slug']));
    return $pages;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $title = trim($_POST['page_title'] ?? '');
    $slug = vm_page_slug($_POST['page_slug'] ?? $title);
    $source_slug = vm_page_slug($_POST['source_slug'] ?? '');

    if ($action === 'create_page' || $action === 'duplicate_page') {
        $file = $pages_dir . '/' . $slug . '.page';
        if ($slug === 'index') {
            $slug = 'home';
            $file = $pages_dir . '/home.page';
        }

        if ($action === 'duplicate_page') {
            $source_file = $pages_dir . '/' . $source_slug . '.page';
            if ($source_slug === 'index' && file_exists($builder_cache)) {
                $source_file = $builder_cache;
            }

            if (!file_exists($source_file)) {
                header('Location: ' . $admin_base . 'page?error=source-missing');
                exit;
            }
            if (file_exists($file)) {
                header('Location: ' . $admin_base . 'page?error=exists');
                exit;
            }

            copy($source_file, $file);
            header('Location: ' . $admin_base . 'page?success=duplicated&slug=' . urlencode($slug));
            exit;
        }

        if ($title === '') {
            header('Location: ' . $admin_base . 'page?error=missing-title');
            exit;
        }
        if (file_exists($file)) {
            header('Location: ' . $admin_base . 'page?error=exists');
            exit;
        }

        $content = trim($_POST['page_content'] ?? '');
        $summary = trim($_POST['page_summary'] ?? '');
        if ($content === '') {
            $content = '<section class="max-w-4xl mx-auto px-6 py-16"><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p>' . htmlspecialchars($summary !== '' ? $summary : 'Start building this page in the page builder.', ENT_QUOTES, 'UTF-8') . '</p></section>';
        }

        file_put_contents($file, $content);
        header('Location: ' . $admin_base . 'page?success=created&slug=' . urlencode($slug));
        exit;
    }
}

$pages = vm_page_scan($pages_dir);
$selected = vm_page_slug($_GET['page_slug'] ?? ($_GET['slug'] ?? 'index'));
$selected_page = null;
foreach ($pages as $page) {
    if ($page['slug'] === $selected) {
        $selected_page = $page;
        break;
    }
}
if ($selected_page === null && !empty($pages)) {
    $selected_page = $pages[0];
}

$status = $_GET['success'] ?? ($_GET['error'] ?? '');
$status_slug = $_GET['slug'] ?? '';
$page_count = count($pages);
?>
<div class="flex flex-1 flex-col overflow-hidden bg-[#1b1b1c] text-zinc-100 min-h-screen">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">
        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#242425] text-slate-900 shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
            
            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(290px,0.8fr)] lg:p-8">
                <div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Manage storefront pages.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white sm:text-base">
                        Keep every page in your store, manage your store website pages.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="<?php echo $admin_base; ?>builder<?php echo $selected_page ? '?page=' . urlencode($selected_page['slug']) : ''; ?>" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-[#006e52]">
                            <i class="bi bi-brush"></i>
                            <span>Open Builder</span>
                        </a>
                        <button type="button" onclick="openPageModal('create')" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                            <i class="bi bi-plus-lg"></i>
                            <span>New Page</span>
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pages</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-950"><?php echo $page_count; ?></p>
                        <p class="mt-1 text-sm text-slate-500">All active pages in this store</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/85 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current page</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">
                            <?php echo $selected_page ? htmlspecialchars($selected_page['label'], ENT_QUOTES, 'UTF-8') : 'No pages yet'; ?>
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            <?php echo $selected_page ? 'data/pages/' . htmlspecialchars($selected_page['slug'], ENT_QUOTES, 'UTF-8') . '.page' : 'Ready to build'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($status === 'created'): ?>
            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">Page created<?php echo $status_slug ? ': ' . htmlspecialchars($status_slug, ENT_QUOTES, 'UTF-8') : ''; ?>.</div>
        <?php elseif ($status === 'duplicated'): ?>
            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">Page duplicated<?php echo $status_slug ? ': ' . htmlspecialchars($status_slug, ENT_QUOTES, 'UTF-8') : ''; ?>.</div>
        <?php elseif ($status === 'missing-title'): ?>
            <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">Give the page a title before creating it.</div>
        <?php elseif ($status === 'exists' || $status === 'source-missing'): ?>
            <div class="rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">That page could not be saved.</div>
        <?php endif; ?>

        <section class="rounded-3xl border border-white/10 bg-[#202123] p-5 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Pages table</p>
                    <h2 class="mt-1 text-2xl font-semibold text-white">All store pages</h2>
                </div>
                <button type="button" onclick="openPageModal('create')" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                    <i class="bi bi-plus-lg"></i>
                    <span>Create page</span>
                </button>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-white/10">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white/5 text-xs uppercase tracking-[0.18em] text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Page</th>
                            <th class="px-4 py-3 font-semibold">File</th>
                            <th class="px-4 py-3 font-semibold">Updated</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-zinc-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <i class="bi bi-file-earmark-text text-4xl text-zinc-700"></i>
                                        <p class="text-sm font-medium text-zinc-300">No pages yet</p>
                                        <p class="text-xs text-zinc-500">Create your first page to start building.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                                <?php $is_home = in_array($page['slug'], ['index', 'home'], true); ?>
                                <tr class="hover:bg-white/[0.03] transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h3 class="truncate text-sm font-semibold text-white"><?php echo htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <?php if ($is_home): ?>
                                                    <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-300">Home</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mt-1 truncate text-xs text-zinc-500">/<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 font-mono text-xs text-zinc-300">data/pages/<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>.page</td>
                                    <td class="px-4 py-4 text-sm text-zinc-300"><?php echo date('M j, Y', (int) $page['modified']); ?></td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-zinc-200">
                                            <?php echo $is_home ? 'Homepage' : 'Content page'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="<?php echo $admin_base; ?>builder?page=<?php echo urlencode($page['slug']); ?>" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#006e52]">
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit</span>
                                            </a>
                                            <button type="button" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-zinc-100 transition hover:bg-white/10" onclick="openPageModal('duplicate', '<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                <i class="bi bi-copy"></i>
                                                <span>Duplicate</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div id="pageModal" class="fixed inset-0 z-[200000] hidden items-center justify-center bg-black/70 px-4">
    <div class="w-full max-w-xl rounded-3xl border border-white/10 bg-[#202123] p-6 shadow-2xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 id="pageModalTitle" class="text-lg font-semibold text-white">New Page</h2>
                <p class="text-xs text-zinc-500">Create a page or duplicate an existing one from here.</p>
            </div>
            <button type="button" onclick="closePageModal()" class="rounded-lg border border-white/10 bg-white/5 p-2 text-zinc-400 hover:text-white">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" class="mt-5 space-y-4">
            <input type="hidden" name="action" id="pageModalAction" value="create_page">
            <input type="hidden" name="source_slug" id="pageModalSourceSlug" value="">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label>
                    <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-zinc-400">Page title</span>
                    <input name="page_title" id="pageModalTitleInput" type="text" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none focus:border-[#008060]" placeholder="About Us">
                </label>
                <label>
                    <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-zinc-400">URL slug</span>
                    <input name="page_slug" id="pageModalSlugInput" type="text" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none focus:border-[#008060]" placeholder="about-us">
                </label>
            </div>

            <label class="block" id="pageModalSummaryWrap">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-zinc-400">Summary</span>
                <input name="page_summary" id="pageModalSummaryInput" type="text" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none focus:border-[#008060]" placeholder="Short description">
            </label>

            <label class="block" id="pageModalContentWrap">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-zinc-400">Starter content</span>
                <textarea name="page_content" id="pageModalContentInput" rows="10" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none focus:border-[#008060]" placeholder="Leave blank to use the default starter section."></textarea>
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closePageModal()" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-zinc-200 hover:bg-white/10">Cancel</button>
                <button id="pageModalSubmit" type="submit" class="rounded-full bg-[#008060] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006e52]">Create Page</button>
            </div>
        </form>
    </div>
</div>

<script>
    const pageSelect = document.getElementById('pageSelect');
    const builderLaunch = document.getElementById('builderLaunch');
    const modal = document.getElementById('pageModal');
    const modalTitle = document.getElementById('pageModalTitle');
    const modalAction = document.getElementById('pageModalAction');
    const modalSourceSlug = document.getElementById('pageModalSourceSlug');
    const modalTitleInput = document.getElementById('pageModalTitleInput');
    const modalSlugInput = document.getElementById('pageModalSlugInput');
    const modalSummaryWrap = document.getElementById('pageModalSummaryWrap');
    const modalContentWrap = document.getElementById('pageModalContentWrap');
    const modalSubmit = document.getElementById('pageModalSubmit');

    function slugify(text) {
        return String(text || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function openPageModal(mode, sourceSlug = '', sourceLabel = '') {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (mode === 'duplicate') {
            modalTitle.textContent = 'Duplicate Page';
            modalAction.value = 'duplicate_page';
            modalSourceSlug.value = sourceSlug;
            modalTitleInput.value = sourceLabel ? sourceLabel + ' Copy' : 'Copy';
            modalSlugInput.value = sourceSlug ? sourceSlug + '-copy' : '';
            modalSummaryWrap.style.display = 'none';
            modalContentWrap.style.display = 'none';
            modalSubmit.textContent = 'Duplicate';
        } else {
            modalTitle.textContent = 'Create Page';
            modalAction.value = 'create_page';
            modalSourceSlug.value = '';
            modalTitleInput.value = '';
            modalSlugInput.value = '';
            modalSummaryWrap.style.display = 'block';
            modalContentWrap.style.display = 'block';
            modalSubmit.textContent = 'Create Page';
        }
        setTimeout(() => modalTitleInput.focus(), 50);
    }

    function closePageModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closePageModal();
    });

    modalTitleInput.addEventListener('input', () => {
        if (modalAction.value === 'create_page') {
            modalSlugInput.value = slugify(modalTitleInput.value);
        }
    });

    pageSelect?.addEventListener('change', () => {
        const slug = pageSelect.value;
        builderLaunch.href = '<?php echo $admin_base; ?>builder?page=' + encodeURIComponent(slug);
    });
</script>
