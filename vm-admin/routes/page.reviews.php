<?php
$db = initiate_web_database();

if (!function_exists('vm_reviews_ensure_schema')) {
    function vm_reviews_ensure_schema($db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS product_reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            customer_name TEXT,
            customer_email TEXT,
            rating INTEGER NOT NULL DEFAULT 5,
            title TEXT,
            body TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->query("CREATE INDEX IF NOT EXISTS idx_product_reviews_product_id ON product_reviews(product_id)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_product_reviews_status ON product_reviews(status)");
    }
}

vm_reviews_ensure_schema($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = './reviews';
    if (!empty($_POST['product_id'])) {
        $redirect .= '?product_id=' . urlencode((string) $_POST['product_id']);
    }

    if ($action === 'save_review') {
        $id = (int) ($_POST['id'] ?? 0);
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $customer_name = trim((string) ($_POST['customer_name'] ?? ''));
        $customer_email = trim((string) ($_POST['customer_email'] ?? ''));
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $status = in_array($_POST['status'] ?? 'pending', ['pending', 'approved', 'hidden'], true) ? $_POST['status'] : 'pending';

        if ($id > 0) {
            $db->query("UPDATE product_reviews SET product_id = ?, customer_name = ?, customer_email = ?, rating = ?, title = ?, body = ?, status = ?, updated_at = datetime('now') WHERE id = ?", [
                $product_id,
                $customer_name !== '' ? $customer_name : null,
                $customer_email !== '' ? $customer_email : null,
                $rating,
                $title !== '' ? $title : null,
                $body !== '' ? $body : null,
                $status,
                $id
            ]);
        } else {
            $db->query("INSERT INTO product_reviews (product_id, customer_name, customer_email, rating, title, body, status) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                $product_id,
                $customer_name !== '' ? $customer_name : null,
                $customer_email !== '' ? $customer_email : null,
                $rating,
                $title !== '' ? $title : null,
                $body !== '' ? $body : null,
                $status
            ]);
        }
        header('Location: ' . $redirect . '&saved=1');
        exit;
    }

    if ($action === 'update_review_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'pending', ['pending', 'approved', 'hidden'], true) ? $_POST['status'] : 'pending';
        $db->query("UPDATE product_reviews SET status = ?, updated_at = datetime('now') WHERE id = ?", [$status, $id]);
        header('Location: ' . $redirect . '&saved=1');
        exit;
    }

    if ($action === 'delete_review') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->query("DELETE FROM product_reviews WHERE id = ?", [$id]);
        header('Location: ' . $redirect . '&deleted=1');
        exit;
    }
}

$selectedProductId = (int) ($_GET['product_id'] ?? 0);

$products = $db->query("SELECT id, name FROM products ORDER BY name ASC") ?: [];
$productMap = [];
foreach ($products as $product) {
    $productMap[(int) $product['id']] = $product['name'];
}

$where = '';
$params = [];
if ($selectedProductId > 0) {
    $where = 'WHERE r.product_id = ?';
    $params[] = $selectedProductId;
}

$reviews = $db->query("
    SELECT r.*, p.name AS product_name
    FROM product_reviews r
    LEFT JOIN products p ON p.id = r.product_id
    $where
    ORDER BY r.created_at DESC, r.id DESC
", $params) ?: [];

$stats = [
    'total' => count($reviews),
    'approved' => 0,
    'pending' => 0,
    'hidden' => 0,
];
foreach ($reviews as $review) {
    $stats[$review['status'] ?? 'pending'] = ($stats[$review['status'] ?? 'pending'] ?? 0) + 1;
}

function vm_reviews_star_icons(int $rating): string
{
    $rating = max(1, min(5, $rating));
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="bi bi-star' . ($i <= $rating ? '-fill text-amber-400' : ' text-zinc-600') . '"></i>';
    }
    return $html;
}
?>
<div class="flex flex-1 flex-col overflow-hidden bg-[#1b1b1c] min-h-screen text-zinc-100">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden p-6 lg:p-8 space-y-6">
        <section class="rounded-3xl border border-white/10 bg-[#202123] p-6 shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Catalog</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-white">Product reviews</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400">Moderate product feedback, update ratings, and keep customer comments tidy from one workspace.</p>
                </div>
                <div class="w-full lg:w-80">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Filter by product</label>
                    <select onchange="window.location.href=this.value" class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none focus:border-[#008060]">
                        <option value="./reviews">All products</option>
                        <?php foreach ($products as $product): ?>
                            <option value="./reviews?product_id=<?php echo (int) $product['id']; ?>" <?php echo $selectedProductId === (int) $product['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4"><p class="text-xs uppercase tracking-[0.18em] text-zinc-500">Total</p><p class="mt-3 text-2xl font-semibold text-white"><?php echo (int) $stats['total']; ?></p></div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4"><p class="text-xs uppercase tracking-[0.18em] text-zinc-500">Approved</p><p class="mt-3 text-2xl font-semibold text-white"><?php echo (int) $stats['approved']; ?></p></div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4"><p class="text-xs uppercase tracking-[0.18em] text-zinc-500">Pending</p><p class="mt-3 text-2xl font-semibold text-white"><?php echo (int) $stats['pending']; ?></p></div>
            <div class="rounded-2xl border border-white/10 bg-[#202123] p-4"><p class="text-xs uppercase tracking-[0.18em] text-zinc-500">Hidden</p><p class="mt-3 text-2xl font-semibold text-white"><?php echo (int) $stats['hidden']; ?></p></div>
        </section>

        <section class="rounded-3xl border border-white/10 bg-[#202123] overflow-hidden shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-white">Reviews</h2>
                    <p class="text-sm text-zinc-500">Showing <?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?></p>
                </div>
                <button onclick="openModal('add')" class="inline-flex items-center gap-2 rounded-full bg-[#008060] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#006e52]">
                    <i class="bi bi-plus-lg"></i> Add review
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-300">
                    <thead class="bg-white/5 text-xs uppercase tracking-[0.18em] text-zinc-500">
                        <tr>
                            <th class="px-5 py-4">Review</th>
                            <th class="px-5 py-4">Product</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php if (empty($reviews)): ?>
                            <tr><td colspan="4" class="px-5 py-16 text-center text-zinc-500">No reviews found for this filter.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reviews as $review):
                                $status = $review['status'] ?? 'pending';
                                $badge = $status === 'approved' ? 'bg-emerald-500/10 text-emerald-300 ring-1 ring-emerald-500/20' : ($status === 'hidden' ? 'bg-rose-500/10 text-rose-300 ring-1 ring-rose-500/20' : 'bg-amber-500/10 text-amber-300 ring-1 ring-amber-500/20');
                            ?>
                                <tr class="hover:bg-white/[0.03]">
                                    <td class="px-5 py-4">
                                        <div class="max-w-xl">
                                            <div class="flex items-center gap-2"><?php echo vm_reviews_star_icons((int) $review['rating']); ?></div>
                                            <p class="mt-2 font-medium text-white"><?php echo htmlspecialchars($review['title'] ?: 'Untitled review'); ?></p>
                                            <p class="mt-1 text-sm text-zinc-400"><?php echo htmlspecialchars($review['body'] ?: 'No review text provided.'); ?></p>
                                            <div class="mt-2 text-xs text-zinc-500">
                                                <?php echo htmlspecialchars($review['customer_name'] ?: 'Anonymous'); ?>
                                                <?php if (!empty($review['customer_email'])): ?>
                                                    <span class="mx-1">•</span><?php echo htmlspecialchars($review['customer_email']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-zinc-300"><?php echo htmlspecialchars($review['product_name'] ?: ($productMap[(int) $review['product_id']] ?? 'Unknown product')); ?></td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button onclick='openModal("edit", <?php echo json_encode($review); ?>)' class="h-8 w-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-sky-300" title="Edit review"><i class="bi bi-pencil-square"></i></button>
                                            <?php if ($status !== 'approved'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="update_review_status">
                                                    <input type="hidden" name="id" value="<?php echo (int) $review['id']; ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="h-8 w-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-emerald-300" title="Approve"><i class="bi bi-check2"></i></button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" onsubmit="return confirm('Delete this review?');">
                                                <input type="hidden" name="action" value="delete_review">
                                                <input type="hidden" name="id" value="<?php echo (int) $review['id']; ?>">
                                                <button type="submit" class="h-8 w-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-rose-300" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
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

<div id="reviewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <form method="POST" id="reviewForm" class="w-full max-w-2xl rounded-3xl border border-white/10 bg-[#202123] shadow-2xl">
            <input type="hidden" name="action" id="reviewAction" value="save_review">
            <input type="hidden" name="id" id="reviewId">
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-white" id="reviewModalTitle">Add Review</h3>
                    <p class="text-sm text-zinc-500">Create or update a product review.</p>
                </div>
                <button type="button" onclick="closeModal()" class="text-zinc-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="grid gap-4 px-6 py-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm text-zinc-300">Product</span>
                    <select name="product_id" id="reviewProductId" required class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none">
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo (int) $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-zinc-300">Status</span>
                    <select name="status" id="reviewStatus" class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-zinc-300">Customer name</span>
                    <input type="text" name="customer_name" id="reviewCustomerName" class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-zinc-300">Customer email</span>
                    <input type="email" name="customer_email" id="reviewCustomerEmail" class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-zinc-300">Rating</span>
                    <select name="rating" id="reviewRating" class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none">
                        <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
                    </select>
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-1.5 block text-sm text-zinc-300">Title</span>
                    <input type="text" name="title" id="reviewTitle" class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none">
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-1.5 block text-sm text-zinc-300">Body</span>
                    <textarea name="body" id="reviewBody" rows="5" class="w-full rounded-xl border border-white/10 bg-[#1b1b1c] px-4 py-3 text-sm text-white outline-none"></textarea>
                </label>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-white/10 px-6 py-4">
                <button type="button" onclick="closeModal()" class="rounded-full border border-white/10 px-4 py-2.5 text-sm font-semibold text-zinc-300 hover:bg-white/5">Cancel</button>
                <button type="submit" class="rounded-full bg-[#008060] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#006e52]">Save review</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode, review = null) {
    const modal = document.getElementById('reviewModal');
    const title = document.getElementById('reviewModalTitle');
    const action = document.getElementById('reviewAction');
    modal.classList.remove('hidden');
    if (mode === 'edit' && review) {
        title.textContent = 'Edit Review';
        action.value = 'save_review';
        document.getElementById('reviewId').value = review.id || '';
        document.getElementById('reviewProductId').value = review.product_id || '';
        document.getElementById('reviewCustomerName').value = review.customer_name || '';
        document.getElementById('reviewCustomerEmail').value = review.customer_email || '';
        document.getElementById('reviewRating').value = review.rating || '5';
        document.getElementById('reviewTitle').value = review.title || '';
        document.getElementById('reviewBody').value = review.body || '';
        document.getElementById('reviewStatus').value = review.status || 'pending';
    } else {
        title.textContent = 'Add Review';
        action.value = 'save_review';
        document.getElementById('reviewId').value = '';
        document.getElementById('reviewCustomerName').value = '';
        document.getElementById('reviewCustomerEmail').value = '';
        document.getElementById('reviewRating').value = '5';
        document.getElementById('reviewTitle').value = '';
        document.getElementById('reviewBody').value = '';
        document.getElementById('reviewStatus').value = 'pending';
    }
}
function closeModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}
</script>
