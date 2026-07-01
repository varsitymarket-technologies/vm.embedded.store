<?php
@include_once dirname(dirname(dirname(__FILE__))) . "/module/shopify_csv_parser.php";

$db = initiate_web_database();

if (!function_exists('vm_products_json_array')) {
    function vm_products_json_array($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
}

if (!function_exists('vm_products_unique_values')) {
    function vm_products_unique_values(array $values): array
    {
        $clean = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }
            if (!in_array($value, $clean, true)) {
                $clean[] = $value;
            }
        }
        return $clean;
    }
}

if (!function_exists('vm_products_split_urls')) {
    function vm_products_split_urls($value): array
    {
        if (is_array($value)) {
            return vm_products_unique_values($value);
        }

        if (!is_string($value)) {
            return [];
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        if ($value[0] === '[' || $value[0] === '{') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return vm_products_unique_values($decoded);
            }
        }

        $parts = preg_split('/[\r\n,;]+/', $value) ?: [];
        return vm_products_unique_values($parts);
    }
}

if (!function_exists('vm_products_prepare_variants')) {
    function vm_products_prepare_variants(array $source, string $fallback_name, float $fallback_price, int $fallback_stock, string $fallback_image): array
    {
        $labels = $source['variant_label'] ?? [];
        $prices = $source['variant_price'] ?? [];
        $stocks = $source['variant_stock'] ?? [];
        $images = $source['variant_image'] ?? [];
        $skus   = $source['variant_sku'] ?? [];

        if (!is_array($labels)) $labels = [];
        if (!is_array($prices)) $prices = [];
        if (!is_array($stocks)) $stocks = [];
        if (!is_array($images)) $images = [];
        if (!is_array($skus))   $skus = [];

        $count = max(count($labels), count($prices), count($stocks), count($images), count($skus));
        $variants = [];
        $errors = [];

        for ($i = 0; $i < $count; $i++) {
            $label = trim((string)($labels[$i] ?? ''));
            $price_raw = trim((string)($prices[$i] ?? ''));
            $stock_raw = trim((string)($stocks[$i] ?? ''));
            $image = trim((string)($images[$i] ?? ''));
            $sku = trim((string)($skus[$i] ?? ''));

            $has_any_content = ($label !== '' || $price_raw !== '' || $stock_raw !== '' || $image !== '' || $sku !== '');
            if (!$has_any_content) {
                continue;
            }

            if ($label === '') {
                $label = $fallback_name !== '' ? 'Default' : 'Variant ' . ($i + 1);
            }

            if ($price_raw === '' || !is_numeric($price_raw)) {
                $errors[] = 'Variation "' . $label . '" needs a numeric price.';
                continue;
            }

            if ($stock_raw === '' || !is_numeric($stock_raw)) {
                $stock_raw = '0';
            }

            $variants[] = [
                'label' => $label,
                'price' => (float)$price_raw,
                'stock' => (int)$stock_raw,
                'image' => $image,
                'sku' => $sku,
            ];
        }

        if (!empty($errors)) {
            return ['ok' => false, 'error' => implode(' ', $errors), 'variants' => []];
        }

        if (empty($variants)) {
            $variants[] = [
                'label' => 'Default',
                'price' => $fallback_price,
                'stock' => $fallback_stock,
                'image' => $fallback_image,
                'sku' => '',
            ];
        }

        return ['ok' => true, 'error' => null, 'variants' => $variants];
    }
}

if (!function_exists('vm_products_ensure_schema')) {
    function vm_products_ensure_schema($db): void
    {
        $columns = [];
        foreach ($db->query("PRAGMA table_info(products)") ?: [] as $col) {
            if (!empty($col['name'])) {
                $columns[] = $col['name'];
            }
        }

        if (!in_array('gallery_json', $columns, true)) {
            $db->query("ALTER TABLE products ADD COLUMN gallery_json TEXT DEFAULT '[]'");
        }
        if (!in_array('variants_json', $columns, true)) {
            $db->query("ALTER TABLE products ADD COLUMN variants_json TEXT DEFAULT '[]'");
        }
    }
}

vm_products_ensure_schema($db);

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $image = trim((string)($_POST['image'] ?? ''));
        $gallery = vm_products_split_urls((string)($_POST['gallery_urls'] ?? ''));
        $category_id = $_POST['category_id'] ?? null;
        $category_id = $category_id === '' ? null : (int)$category_id;

        $variant_payload = vm_products_prepare_variants($_POST, $name, $price, $stock, $image);
        if (!$variant_payload['ok']) {
            header('Location: ?error=' . urlencode($variant_payload['error']));
            exit;
        }
        $variants = $variant_payload['variants'];
        $primary_image = $image !== '' ? $image : ($variants[0]['image'] ?? '');
        if ($primary_image !== '' && !in_array($primary_image, $gallery, true)) {
            array_unshift($gallery, $primary_image);
        }
        $gallery = vm_products_unique_values($gallery);
        if ($primary_image === '' && !empty($gallery)) {
            $primary_image = $gallery[0];
        }
        $price = (float)($variants[0]['price'] ?? $price);
        $stock = array_sum(array_map(fn($variant) => (int)($variant['stock'] ?? 0), $variants));
        $gallery_json = json_encode($gallery);
        $variants_json = json_encode($variants);

        $sql = "INSERT INTO products (name, description, price, stock, image, category_id, gallery_json, variants_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $db->query($sql, [$name, $description, $price, $stock, $primary_image, $category_id, $gallery_json, $variants_json]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_product') {
        $id = $_POST['id'] ?? 0;
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $image = trim((string)($_POST['image'] ?? ''));
        $gallery = vm_products_split_urls((string)($_POST['gallery_urls'] ?? ''));
        $category_id = $_POST['category_id'] ?? null;
        $category_id = $category_id === '' ? null : (int)$category_id;

        $variant_payload = vm_products_prepare_variants($_POST, $name, $price, $stock, $image);
        if (!$variant_payload['ok']) {
            header('Location: ?error=' . urlencode($variant_payload['error']));
            exit;
        }
        $variants = $variant_payload['variants'];
        $primary_image = $image !== '' ? $image : ($variants[0]['image'] ?? '');
        if ($primary_image !== '' && !in_array($primary_image, $gallery, true)) {
            array_unshift($gallery, $primary_image);
        }
        $gallery = vm_products_unique_values($gallery);
        if ($primary_image === '' && !empty($gallery)) {
            $primary_image = $gallery[0];
        }
        $price = (float)($variants[0]['price'] ?? $price);
        $stock = array_sum(array_map(fn($variant) => (int)($variant['stock'] ?? 0), $variants));
        $gallery_json = json_encode($gallery);
        $variants_json = json_encode($variants);

        $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ?, category_id = ?, gallery_json = ?, variants_json = ? WHERE id = ?";
        $db->query($sql, [$name, $description, $price, $stock, $primary_image, $category_id, $gallery_json, $variants_json, $id]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'preview_shopify_import') {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json');

        if (empty($_FILES['file'])) {
            echo json_encode(['ok' => false, 'error' => 'No file uploaded']);
            exit;
        }
        $uploadErr = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadErr !== UPLOAD_ERR_OK) {
            $message = 'Upload failed';
            if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
                $message = 'File exceeds the server upload size limit';
            } elseif ($uploadErr === UPLOAD_ERR_NO_FILE) {
                $message = 'No file uploaded';
            } elseif ($uploadErr === UPLOAD_ERR_PARTIAL) {
                $message = 'Upload was interrupted before completion';
            }
            echo json_encode(['ok' => false, 'error' => $message]);
            exit;
        }

        if (($_FILES['file']['size'] ?? 0) > 20 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'error' => 'File exceeds 20 MB limit']);
            exit;
        }

        $tmp = $_FILES['file']['tmp_name'];
        $parsed = parse_shopify_csv($tmp);
        if (!$parsed['ok']) {
            echo json_encode(['ok' => false, 'error' => $parsed['error']]);
            exit;
        }

        $summary = ['insert' => 0, 'update' => 0, 'skip' => 0];
        $previewRows = [];
        foreach ($parsed['rows'] as $row) {
            if ($row['parse_error'] !== null) {
                $action_for_row = 'skip';
                $reason = $row['parse_error'];
            } else {
                $existing = $db->query(
                    "SELECT id FROM products WHERE LOWER(name) = LOWER(?) LIMIT 1",
                    [$row['name']]
                );
                $action_for_row = !empty($existing) ? 'update' : 'insert';
                $reason = null;
            }
            $summary[$action_for_row]++;
            $previewRows[] = [
                'action'   => $action_for_row,
                'name'     => $row['name'],
                'category' => $row['category'],
                'variants' => count($row['variants'] ?? []),
                'gallery'  => count($row['gallery'] ?? []),
                'price'    => $row['price'],
                'stock'    => $row['stock'],
                'image'    => $row['image'],
                'notes'    => $row['notes'] ?? [],
                'reason'   => $reason,
            ];
        }

        if (count($previewRows) === 0) {
            echo json_encode(['ok' => false, 'error' => 'No rows found in CSV']);
            exit;
        }

        echo json_encode(['ok' => true, 'summary' => $summary, 'rows' => $previewRows]);
        exit;

    } elseif ($action === 'commit_shopify_import') {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json');

        if (empty($_FILES['file'])) {
            echo json_encode(['ok' => false, 'error' => 'No file uploaded']);
            exit;
        }
        $uploadErr = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadErr !== UPLOAD_ERR_OK) {
            $message = 'Upload failed';
            if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
                $message = 'File exceeds the server upload size limit';
            } elseif ($uploadErr === UPLOAD_ERR_NO_FILE) {
                $message = 'No file uploaded';
            } elseif ($uploadErr === UPLOAD_ERR_PARTIAL) {
                $message = 'Upload was interrupted before completion';
            }
            echo json_encode(['ok' => false, 'error' => $message]);
            exit;
        }

        if (($_FILES['file']['size'] ?? 0) > 20 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'error' => 'File exceeds 20 MB limit']);
            exit;
        }

        $tmp = $_FILES['file']['tmp_name'];
        $parsed = parse_shopify_csv($tmp);
        if (!$parsed['ok']) {
            echo json_encode(['ok' => false, 'error' => $parsed['error']]);
            exit;
        }

        $counts = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        // Cache category id lookups by lower-cased name to avoid repeat SELECTs.
        $categoryCache = [];
        $resolveCategory = function (string $catName) use ($db, &$categoryCache) {
            $catName = trim($catName);
            if ($catName === '') return null;
            $key = strtolower($catName);
            if (array_key_exists($key, $categoryCache)) return $categoryCache[$key];

            $existing = $db->query(
                "SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1",
                [$catName]
            );
            if (!empty($existing)) {
                $categoryCache[$key] = (int)$existing[0]['id'];
                return $categoryCache[$key];
            }
            $db->query("INSERT INTO categories (name) VALUES (?)", [$catName]);
            // Look the new id up (database_manager doesn't expose lastInsertId).
            $row = $db->query(
                "SELECT id FROM categories WHERE LOWER(name) = LOWER(?) ORDER BY id DESC LIMIT 1",
                [$catName]
            );
            $categoryCache[$key] = !empty($row) ? (int)$row[0]['id'] : null;
            return $categoryCache[$key];
        };

        $db->query("BEGIN TRANSACTION");
        try {
            foreach ($parsed['rows'] as $row) {
                if ($row['parse_error'] !== null) {
                    $counts['skipped']++;
                    $counts['errors'][] = ['name' => $row['name'], 'reason' => $row['parse_error']];
                    continue;
                }
                // database_manager::query() swallows PDOExceptions and returns [];
                // DB errors during INSERT/UPDATE are logged via trigger_error() but
                // cannot be detected here. Counts may slightly overstate on DB failure.

                $categoryId = $resolveCategory($row['category']);
                if ($categoryId === null && $row['category'] !== '') {
                    $counts['skipped']++;
                    $counts['errors'][] = [
                        'name'   => $row['name'],
                        'reason' => 'Could not resolve or create category: ' . $row['category'],
                    ];
                    continue;
                }

                $existing = $db->query(
                    "SELECT id FROM products WHERE LOWER(name) = LOWER(?) LIMIT 1",
                    [$row['name']]
                );
                if (!empty($existing)) {
                    $db->query(
                        "UPDATE products SET description = ?, price = ?, stock = ?, image = ?, category_id = ?, gallery_json = ?, variants_json = ? WHERE id = ?",
                        [
                            $row['description'],
                            $row['price'],
                            $row['stock'],
                            $row['image'],
                            $categoryId,
                            json_encode($row['gallery'] ?? []),
                            json_encode($row['variants'] ?? []),
                            $existing[0]['id'],
                        ]
                    );
                    $counts['updated']++;
                } else {
                    $db->query(
                        "INSERT INTO products (name, description, price, stock, image, category_id, gallery_json, variants_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $row['name'],
                            $row['description'],
                            $row['price'],
                            $row['stock'],
                            $row['image'],
                            $categoryId,
                            json_encode($row['gallery'] ?? []),
                            json_encode($row['variants'] ?? []),
                        ]
                    );
                    $counts['inserted']++;
                }
            }
            $db->query("COMMIT");
        } catch (Throwable $e) {
            $db->query("ROLLBACK");
            echo json_encode(['ok' => false, 'error' => 'Import failed: ' . $e->getMessage()]);
            exit;
        }

        echo json_encode(['ok' => true, 'counts' => $counts]);
        exit;

    } elseif ($action === 'delete_product') {
        $id = $_POST['id'] ?? 0;
        $sql = "DELETE FROM products WHERE id = ?";
        $db->query($sql, [$id]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Fetch Products with category name via LEFT JOIN
$products = $db->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");

// Fetch Categories for filter & dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC");

// Compute stats
$totalProducts = count($products);
$inStock = 0;
$outOfStock = 0;
$totalValue = 0;
foreach ($products as $p) {
    if ((int)$p['stock'] > 0) {
        $inStock++;
    } else {
        $outOfStock++;
    }
    $totalValue += (float)$p['price'] * (int)$p['stock'];
}
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">

                <!-- Page Title Row -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Products</h2>
                        <p class="text-sm text-gray-500 mt-1">Manage your store inventory</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="openImportModal()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-200 font-medium text-sm border border-white/5">
                            <i class="bi bi-cloud-upload"></i> Import from Shopify
                        </button>
                        <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-500 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-200 font-medium text-sm shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30">
                            <i class="bi bi-plus-lg"></i> Add Product
                        </button>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5 transition-all duration-200 hover:border-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Products</span>
                            <div class="h-9 w-9 rounded-lg bg-purple-600/10 flex items-center justify-center">
                                <i class="bi bi-box-seam text-purple-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalProducts; ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5 transition-all duration-200 hover:border-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">In Stock</span>
                            <div class="h-9 w-9 rounded-lg bg-green-600/10 flex items-center justify-center">
                                <i class="bi bi-check-circle text-green-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $inStock; ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5 transition-all duration-200 hover:border-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Out of Stock</span>
                            <div class="h-9 w-9 rounded-lg bg-red-600/10 flex items-center justify-center">
                                <i class="bi bi-exclamation-circle text-red-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $outOfStock; ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5 transition-all duration-200 hover:border-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Value</span>
                            <div class="h-9 w-9 rounded-lg bg-blue-600/10 flex items-center justify-center">
                                <i class="bi bi-wallet2 text-blue-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo __CURRENCY_SIGN__ . number_format($totalValue, 2); ?></p>
                    </div>
                </div>

                <!-- Search & Filter Bar -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <div class="relative flex-1">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                        <input type="text" id="searchInput" placeholder="Search products..." class="w-full bg-gray-800 border border-white/5 rounded-lg pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                    </div>
                    <select id="categoryFilter" class="bg-gray-800 border border-white/5 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors min-w-[180px] appearance-none cursor-pointer" style="background-image:url('data:image/svg+xml;utf8,<svg fill=\"%239ca3af\" viewBox=\"0 0 20 20\" xmlns=\"http://www.w3.org/2000/svg\"><path fill-rule=\"evenodd\" d=\"M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z\" clip-rule=\"evenodd\"/></svg>');background-repeat:no-repeat;background-position:right 12px center;background-size:16px;padding-right:36px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="stockFilter" class="bg-gray-800 border border-white/5 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors min-w-[150px] appearance-none cursor-pointer" style="background-image:url('data:image/svg+xml;utf8,<svg fill=\"%239ca3af\" viewBox=\"0 0 20 20\" xmlns=\"http://www.w3.org/2000/svg\"><path fill-rule=\"evenodd\" d=\"M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z\" clip-rule=\"evenodd\"/></svg>');background-repeat:no-repeat;background-position:right 12px center;background-size:16px;padding-right:36px;">
                        <option value="">All Stock</option>
                        <option value="in">In Stock</option>
                        <option value="low">Low Stock</option>
                        <option value="out">Out of Stock</option>
                    </select>
                </div>

                <!-- Products Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-800/80 text-[11px] uppercase text-gray-500 tracking-wider border-b border-white/5">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-semibold">Product</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Category</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Price</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Stock</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                                    <th scope="col" class="px-6 py-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody" class="divide-y divide-white/5">
                                <?php if (empty($products)): ?>
                                    <tr id="emptyState">
                                        <td colspan="6" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="h-20 w-20 rounded-2xl bg-gray-700/50 flex items-center justify-center mb-4">
                                                    <i class="bi bi-box-seam text-4xl text-gray-600"></i>
                                                </div>
                                                <p class="text-gray-400 font-medium mb-1">No products yet</p>
                                                <p class="text-gray-600 text-sm mb-4">Get started by adding your first product to the store.</p>
                                                <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-500 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-2 transition-all duration-200">
                                                    <i class="bi bi-plus-lg"></i> Add Product
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product):
                                        $stock = (int)$product['stock'];
                                        $product_gallery = vm_products_json_array($product['gallery_json'] ?? []);
                                        $product_variants = vm_products_json_array($product['variants_json'] ?? []);
                                        $gallery_count = count($product_gallery);
                                        $variant_count = count($product_variants);
                                        if ($stock <= 0) {
                                            $badgeClass = 'bg-red-500/10 text-red-400 ring-1 ring-red-500/20';
                                            $badgeText = 'Out of Stock';
                                            $stockStatus = 'out';
                                        } elseif ($stock < 5) {
                                            $badgeClass = 'bg-yellow-500/10 text-yellow-400 ring-1 ring-yellow-500/20';
                                            $badgeText = 'Low Stock';
                                            $stockStatus = 'low';
                                        } else {
                                            $badgeClass = 'bg-green-500/10 text-green-400 ring-1 ring-green-500/20';
                                            $badgeText = 'In Stock';
                                            $stockStatus = 'in';
                                        }
                                    ?>
                                    <tr class="product-row hover:bg-white/[0.02] transition-colors duration-150" data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>" data-category="<?php echo $product['category_id'] ?? ''; ?>" data-stock="<?php echo $stockStatus; ?>">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3.5">
                                                <?php if (!empty($product['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" class="h-11 w-11 rounded-lg object-cover bg-gray-700 ring-1 ring-white/10 flex-shrink-0">
                                                <?php else: ?>
                                                    <div class="h-11 w-11 rounded-lg bg-gray-700/60 flex items-center justify-center text-gray-500 ring-1 ring-white/5 flex-shrink-0">
                                                        <i class="bi bi-image text-lg"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="min-w-0">
                                                    <div class="font-medium text-white truncate"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div class="text-xs text-gray-500 truncate max-w-[220px]"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)) . (strlen($product['description'] ?? '') > 50 ? '...' : ''); ?></div>
                                                    <?php if ($variant_count > 1 || $gallery_count > 0): ?>
                                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                                            <?php if ($variant_count > 0): ?>
                                                                <span class="inline-flex items-center rounded-full bg-cyan-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-cyan-300 ring-1 ring-cyan-500/20">
                                                                    <?= $variant_count ?> variant<?= $variant_count !== 1 ? 's' : '' ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($gallery_count > 0): ?>
                                                                <span class="inline-flex items-center rounded-full bg-violet-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-violet-300 ring-1 ring-violet-500/20">
                                                                    <?= $gallery_count ?> image<?= $gallery_count !== 1 ? 's' : '' ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if (!empty($product['category_name'])): ?>
                                                <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-700/50 px-2.5 py-1 text-xs font-medium text-gray-300 ring-1 ring-white/5">
                                                    <i class="bi bi-tag text-[10px] text-gray-500"></i>
                                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-600 italic">Uncategorized</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-white font-medium tabular-nums"><?php echo __CURRENCY_SIGN__ . number_format($product['price'], 2); ?></td>
                                        <td class="px-6 py-4 text-white tabular-nums"><?php echo $product['stock']; ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold <?php echo $badgeClass; ?>">
                                                <?php echo $badgeText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-1 justify-end">
                                                <button onclick='openModal("edit", <?php echo json_encode($product); ?>)' class="h-8 w-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="h-8 w-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-all duration-150" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- No Results State (hidden by default, shown by JS filter) -->
                    <div id="noResults" class="hidden px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="h-16 w-16 rounded-2xl bg-gray-700/50 flex items-center justify-center mb-3">
                                <i class="bi bi-search text-2xl text-gray-600"></i>
                            </div>
                            <p class="text-gray-400 font-medium mb-1">No matching products</p>
                            <p class="text-gray-600 text-sm">Try adjusting your search or filter criteria.</p>
                        </div>
                    </div>
                </div>

                <!-- Result count -->
                <div class="mt-3 text-xs text-gray-600" id="resultCount">
                    <?php echo $totalProducts; ?> product<?php echo $totalProducts !== 1 ? 's' : ''; ?> total
                </div>

            </main>
        </div>

        <!-- Modal -->
        <div id="productModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <!-- Backdrop -->
                <div id="modalBackdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeModal()"></div>

                <!-- Modal Panel -->
                <div id="modalPanel" class="relative w-full max-w-3xl bg-gray-800 rounded-2xl shadow-2xl shadow-black/40 border border-white/10 transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col">
                    <form method="POST" id="productForm">
                        <input type="hidden" name="action" id="formAction" value="add_product">
                        <input type="hidden" name="id" id="productId">

                        <!-- Modal Header -->
                        <div class="flex justify-between items-center px-6 pt-6 pb-4 border-b border-white/5">
                            <div>
                                <h3 class="text-lg font-semibold text-white" id="modalTitle">Add New Product</h3>
                                <p class="text-xs text-gray-500 mt-0.5" id="modalSubtitle">Fill in the details below</p>
                            </div>
                            <button type="button" onclick="closeModal()" class="h-8 w-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>

                        <!-- Modal Body (scrollable) -->
                        <div class="px-6 py-5 space-y-5 overflow-y-auto" style="max-height: calc(90vh - 160px);">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Product Name <span class="text-red-400">*</span></label>
                                <input type="text" name="name" id="productName" required class="w-full bg-gray-700 border border-white/5 rounded-lg px-3.5 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="e.g. Classic T-Shirt">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Category</label>
                                <select name="category_id" id="productCategory" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3.5 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors appearance-none cursor-pointer" style="background-image:url('data:image/svg+xml;utf8,<svg fill=\"%239ca3af\" viewBox=\"0 0 20 20\" xmlns=\"http://www.w3.org/2000/svg\"><path fill-rule=\"evenodd\" d=\"M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z\" clip-rule=\"evenodd\"/></svg>');background-repeat:no-repeat;background-position:right 12px center;background-size:16px;padding-right:36px;">
                                    <option value="">No category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Description</label>
                                <textarea name="description" id="productDescription" rows="3" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3.5 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500 resize-none" placeholder="Brief product description..."></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Price (<?php echo __CURRENCY_SIGN__; ?>) <span class="text-red-400">*</span></label>
                                    <input type="number" step="0.01" min="0" name="price" id="productPrice" required class="w-full bg-gray-700 border border-white/5 rounded-lg px-3.5 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Stock Quantity <span class="text-red-400">*</span></label>
                                    <input type="number" min="0" name="stock" id="productStock" required class="w-full bg-gray-700 border border-white/5 rounded-lg px-3.5 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="0">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-gray-300">Product Image</label>

                                    <!-- Image Preview -->
                                    <div id="imagePreviewContainer" class="hidden">
                                        <div class="relative inline-block rounded-xl overflow-hidden bg-gray-900 border border-white/10 group">
                                            <img id="imagePreview" src="" class="h-28 w-28 object-cover">
                                            <button type="button" onclick="removeImage()" class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                                <i class="bi bi-trash text-white text-lg"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Upload Area -->
                                    <label class="relative block cursor-pointer">
                                        <div class="flex items-center justify-center gap-3 bg-gray-700/40 border border-dashed border-white/10 rounded-xl px-4 py-5 text-gray-400 hover:border-purple-500/50 hover:text-gray-300 hover:bg-gray-700/60 transition-all duration-200">
                                            <i class="bi bi-cloud-arrow-up text-xl"></i>
                                            <div>
                                                <span class="text-sm font-medium">Click to upload</span>
                                                <span class="text-xs text-gray-500 block">PNG, JPG, GIF, WEBP</span>
                                            </div>
                                        </div>
                                        <input type="file" id="productImageFile" accept="image/*" class="hidden" onchange="handleImageUpload(this)">
                                    </label>

                                    <!-- URL Input -->
                                    <div class="relative">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="h-px flex-1 bg-white/5"></div>
                                            <span class="text-[10px] text-gray-600 font-semibold uppercase tracking-widest">or paste URL</span>
                                            <div class="h-px flex-1 bg-white/5"></div>
                                        </div>
                                        <input type="text" name="image" id="productImage" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3.5 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="https://example.com/image.jpg" oninput="previewUrlImage(this.value)">
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-gray-300">Gallery Images</label>
                                    <input type="hidden" name="gallery_urls" id="productGallery">
                                    <input type="file" id="productGalleryFiles" accept="image/*" multiple class="hidden" onchange="handleGalleryUpload(this)">
                                    <div class="rounded-xl border border-dashed border-white/10 bg-gray-700/30 p-4 space-y-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-white">Add gallery images</p>
                                                <p class="text-[11px] text-gray-500 mt-0.5">Select multiple files and we’ll store them as the product gallery.</p>
                                            </div>
                                            <button type="button" onclick="document.getElementById('productGalleryFiles').click()" class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-white transition-colors hover:border-purple-500/40 hover:bg-purple-500/10">
                                                <i class="bi bi-folder-plus"></i> Choose files
                                            </button>
                                        </div>
                                        <div id="galleryPreviewEmpty" class="rounded-lg border border-white/5 bg-gray-800/40 px-4 py-5 text-center text-xs text-gray-500">
                                            No gallery images selected yet.
                                        </div>
                                        <div id="galleryPreviewGrid" class="grid grid-cols-2 gap-3"></div>
                                    </div>
                                    <p class="text-[11px] text-gray-500 leading-relaxed">The main image stays as the primary image. Gallery images are saved in order and used for product slides.</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300">Variations</label>
                                        <p class="text-[11px] text-gray-500 mt-0.5">Add size, color, or any other option. Each row can have its own image.</p>
                                    </div>
                                    <button type="button" onclick="addVariantRow()" class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-white transition-colors hover:border-purple-500/40 hover:bg-purple-500/10">
                                        <i class="bi bi-plus-lg"></i> Add variation
                                    </button>
                                </div>
                                <div id="variantList" class="space-y-3"></div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-white/5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                            <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-all duration-150">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-medium text-white shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30 transition-all duration-200">
                                <span id="submitBtnText">Add Product</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Shopify Import Modal -->
        <div id="importModal" class="fixed inset-0 z-50 hidden" aria-labelledby="import-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div id="importBackdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeImportModal()"></div>

                <div id="importPanel" class="relative w-full max-w-3xl bg-gray-800 rounded-2xl shadow-2xl shadow-black/40 border border-white/10 transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col">

                    <!-- Modal Header -->
                    <div class="flex justify-between items-center px-6 pt-6 pb-4 border-b border-white/5">
                        <div>
                            <h3 class="text-lg font-semibold text-white" id="import-modal-title">Import from Shopify</h3>
                            <p class="text-xs text-gray-500 mt-0.5" id="importSubtitle">Upload a Shopify products CSV export</p>
                        </div>
                        <button type="button" onclick="closeImportModal()" class="h-8 w-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <!-- State: Upload -->
                    <div id="importStateUpload" class="px-6 py-8">
                        <label id="importDropzone" class="relative block cursor-pointer">
                            <div class="flex flex-col items-center justify-center gap-3 bg-gray-700/30 border-2 border-dashed border-white/10 rounded-xl px-6 py-12 text-gray-400 hover:border-purple-500/50 hover:text-gray-300 hover:bg-gray-700/50 transition-all duration-200">
                                <i class="bi bi-cloud-arrow-up text-4xl"></i>
                                <div class="text-center">
                                    <p class="text-sm font-medium">Drop your Shopify products CSV here</p>
                                    <p class="text-xs text-gray-500 mt-1">or click to browse &middot; max 20 MB</p>
                                </div>
                            </div>
                            <input type="file" id="importFileInput" accept=".csv,text/csv" class="hidden">
                        </label>
                        <div id="importUploadError" class="hidden mt-3 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-300"></div>
                    </div>

                    <!-- State: Preview -->
                    <div id="importStatePreview" class="hidden flex-1 flex flex-col min-h-0">
                        <div class="px-6 pt-4 pb-3 border-b border-white/5">
                            <div class="flex flex-wrap gap-2" id="importSummary">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-green-500/10 text-green-400 ring-1 ring-green-500/20 text-xs font-medium">
                                    <i class="bi bi-plus-circle"></i> <span id="importSummaryInsert">0</span> to insert
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-500/10 text-blue-400 ring-1 ring-blue-500/20 text-xs font-medium">
                                    <i class="bi bi-arrow-clockwise"></i> <span id="importSummaryUpdate">0</span> to update
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-500/10 text-gray-400 ring-1 ring-gray-500/20 text-xs font-medium">
                                    <i class="bi bi-slash-circle"></i> <span id="importSummarySkip">0</span> to skip
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 py-3" style="max-height: calc(90vh - 240px);">
                            <table class="w-full text-left text-xs text-gray-400">
                                <thead class="text-[10px] uppercase text-gray-500 tracking-wider">
                                    <tr>
                                        <th class="px-2 py-2 font-semibold">Action</th>
                                        <th class="px-2 py-2 font-semibold">Name</th>
                                        <th class="px-2 py-2 font-semibold">Category</th>
                                        <th class="px-2 py-2 font-semibold text-right">Variants</th>
                                        <th class="px-2 py-2 font-semibold text-right">Images</th>
                                        <th class="px-2 py-2 font-semibold text-right">Price</th>
                                        <th class="px-2 py-2 font-semibold text-right">Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="importPreviewBody" class="divide-y divide-white/5"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- State: Result -->
                    <div id="importStateResult" class="hidden px-6 py-10 text-center">
                        <div class="h-14 w-14 mx-auto rounded-full bg-green-500/10 flex items-center justify-center mb-4">
                            <i class="bi bi-check-lg text-2xl text-green-400"></i>
                        </div>
                        <p class="text-white font-medium" id="importResultHeading">Import complete</p>
                        <p class="text-gray-500 text-sm mt-1" id="importResultDetail"></p>
                        <div id="importResultErrors" class="hidden mt-4 text-left bg-gray-900/50 border border-white/5 rounded-lg p-3 max-h-40 overflow-y-auto">
                            <p class="text-xs font-semibold text-gray-400 mb-2">Skipped rows</p>
                            <ul id="importResultErrorList" class="text-xs text-gray-500 space-y-1"></ul>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-white/5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <button type="button" id="importBackBtn" onclick="importGoToUpload()" class="hidden px-4 py-2.5 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-all duration-150">
                            Back
                        </button>
                        <button type="button" id="importCancelBtn" onclick="closeImportModal()" class="px-4 py-2.5 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-all duration-150">
                            Cancel
                        </button>
                        <button type="button" id="importConfirmBtn" onclick="importConfirm()" class="hidden px-5 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-medium text-white shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30 transition-all duration-200">
                            <span id="importConfirmLabel">Confirm Import</span>
                        </button>
                        <button type="button" id="importDoneBtn" onclick="importFinish()" class="hidden px-5 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-medium text-white shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30 transition-all duration-200">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // --- Image Handling ---
        function handleImageUpload(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64String = e.target.result;
                    document.getElementById('productImage').value = base64String;
                    showPreview(base64String);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewUrlImage(url) {
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                showPreview(url);
            }
        }

        function showPreview(src) {
            const preview = document.getElementById('imagePreview');
            const container = document.getElementById('imagePreviewContainer');
            preview.src = src;
            container.classList.remove('hidden');
        }

        function removeImage() {
            document.getElementById('productImage').value = '';
            document.getElementById('productImageFile').value = '';
            document.getElementById('imagePreviewContainer').classList.add('hidden');
        }

        let galleryImages = [];

        function readFileAsDataUrl(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve({
                    name: file.name || 'Gallery image',
                    src: e.target.result
                });
                reader.onerror = () => reject(new Error('Unable to read ' + (file.name || 'file')));
                reader.readAsDataURL(file);
            });
        }

        function syncGalleryField() {
            const galleryField = document.getElementById('productGallery');
            if (!galleryField) return;
            galleryField.value = JSON.stringify(galleryImages.map(item => item.src));
        }

        function renderGalleryPreviews() {
            const grid = document.getElementById('galleryPreviewGrid');
            const empty = document.getElementById('galleryPreviewEmpty');
            if (!grid || !empty) return;

            grid.innerHTML = '';
            galleryImages.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'group relative overflow-hidden rounded-xl border border-white/10 bg-gray-900/60';
                card.innerHTML = `
                    <img src="${escapeHtmlAttr(item.src)}" alt="" class="h-28 w-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 bg-gradient-to-t from-black/80 via-black/25 to-transparent p-3">
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-medium text-white">${escapeHtmlAttr(item.name || 'Gallery image')}</p>
                            <p class="text-[10px] text-gray-300">Image ${index + 1}</p>
                        </div>
                        <button type="button" onclick="removeGalleryImage(${index})" class="rounded-md bg-black/45 px-2 py-1 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-500/70">
                            Remove
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });

            empty.classList.toggle('hidden', galleryImages.length > 0);
            grid.classList.toggle('hidden', galleryImages.length === 0);
            syncGalleryField();
        }

        async function handleGalleryUpload(input) {
            if (!input.files || input.files.length === 0) {
                return;
            }

            const files = Array.from(input.files);
            try {
                const loaded = await Promise.all(files.map(readFileAsDataUrl));
                galleryImages = galleryImages.concat(loaded);
                renderGalleryPreviews();
            } catch (error) {
                console.error(error);
            } finally {
                input.value = '';
            }
        }

        function removeGalleryImage(index) {
            if (index < 0 || index >= galleryImages.length) return;
            galleryImages.splice(index, 1);
            renderGalleryPreviews();
        }

        function setGalleryImagesFromList(urls) {
            galleryImages = (Array.isArray(urls) ? urls : []).map((src, index) => ({
                name: 'Gallery image ' + (index + 1),
                src: src
            }));
            renderGalleryPreviews();
        }

        function clearGalleryImages() {
            galleryImages = [];
            const input = document.getElementById('productGalleryFiles');
            if (input) input.value = '';
            renderGalleryPreviews();
        }

        function safeParseJsonArray(value) {
            if (!value) return [];
            if (Array.isArray(value)) return value;
            try {
                const parsed = JSON.parse(value);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function escapeHtmlAttr(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function buildVariantRow(variant = {}) {
            const row = document.createElement('div');
            row.className = 'variant-row rounded-xl border border-white/5 bg-gray-700/30 p-4';
            row.innerHTML = `
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.3em] text-gray-500">Variation</p>
                        <p class="text-xs text-gray-400 mt-0.5">Give each variant its own image and price.</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-red-400 transition-colors" onclick="removeVariantRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-400 mb-1">Label</label>
                        <input type="text" name="variant_label[]" value="${escapeHtmlAttr(variant.label || variant.name || '')}" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="Small / Red">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-400 mb-1">Image URL</label>
                        <input type="text" name="variant_image[]" value="${escapeHtmlAttr(variant.image || '')}" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="https://example.com/variant.jpg">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-400 mb-1">Price</label>
                        <input type="number" step="0.01" min="0" name="variant_price[]" value="${variant.price ?? ''}" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-400 mb-1">Stock</label>
                        <input type="number" min="0" name="variant_stock[]" value="${variant.stock ?? 0}" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="0">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-medium text-gray-400 mb-1">SKU</label>
                        <input type="text" name="variant_sku[]" value="${escapeHtmlAttr(variant.sku || '')}" class="w-full bg-gray-700 border border-white/5 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors placeholder-gray-500" placeholder="Optional SKU">
                    </div>
                </div>
            `;
            return row;
        }

        function addVariantRow(variant = {}) {
            const list = document.getElementById('variantList');
            if (!list) return;
            list.appendChild(buildVariantRow(variant));
        }

        function clearVariantRows() {
            const list = document.getElementById('variantList');
            if (!list) return;
            list.innerHTML = '';
        }

        function renderVariantRows(variants) {
            clearVariantRows();
            const list = document.getElementById('variantList');
            if (!list) return;
            const rows = Array.isArray(variants) ? variants : [];
            if (rows.length === 0) {
                addVariantRow();
                return;
            }
            rows.forEach(variant => addVariantRow(variant));
        }

        function removeVariantRow(button) {
            const row = button.closest('.variant-row');
            if (row) row.remove();
            const list = document.getElementById('variantList');
            if (list && list.children.length === 0) {
                addVariantRow();
            }
        }

        // --- Modal ---
        function openModal(mode, product = null) {
            const modal = document.getElementById('productModal');
            const backdrop = document.getElementById('modalBackdrop');
            const panel = document.getElementById('modalPanel');
            const form = document.getElementById('productForm');
            const title = document.getElementById('modalTitle');
            const subtitle = document.getElementById('modalSubtitle');
            const action = document.getElementById('formAction');
            const container = document.getElementById('imagePreviewContainer');
            const preview = document.getElementById('imagePreview');
            const submitBtn = document.getElementById('submitBtnText');

            modal.classList.remove('hidden');

            // Trigger animation on next frame
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            });

            if (mode === 'edit' && product) {
                title.textContent = 'Edit Product';
                subtitle.textContent = 'Update the product details';
                action.value = 'update_product';
                submitBtn.textContent = 'Save Changes';
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.name || '';
                document.getElementById('productDescription').value = product.description || '';
                document.getElementById('productPrice').value = product.price;
                document.getElementById('productStock').value = product.stock;
                document.getElementById('productImage').value = product.image || '';
                document.getElementById('productCategory').value = product.category_id || '';
                const galleryItems = safeParseJsonArray(product.gallery_json);
                if (product.image && !galleryItems.includes(product.image)) {
                    galleryItems.unshift(product.image);
                }
                setGalleryImagesFromList(galleryItems);

                const existingVariants = safeParseJsonArray(product.variants_json);
                if (existingVariants.length > 0) {
                    renderVariantRows(existingVariants);
                } else {
                    renderVariantRows([{
                        label: 'Default',
                        price: product.price,
                        stock: product.stock,
                        image: product.image || '',
                        sku: ''
                    }]);
                }

                if (product.image) {
                    preview.src = product.image;
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            } else {
                title.textContent = 'Add New Product';
                subtitle.textContent = 'Fill in the details below';
                action.value = 'add_product';
                submitBtn.textContent = 'Add Product';
                form.reset();
                document.getElementById('productId').value = '';
                document.getElementById('productCategory').value = '';
                clearGalleryImages();
                container.classList.add('hidden');
                clearVariantRows();
                addVariantRow();
            }
        }

        function closeModal() {
            const modal = document.getElementById('productModal');
            const backdrop = document.getElementById('modalBackdrop');
            const panel = document.getElementById('modalPanel');

            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            const importModal = document.getElementById('importModal');
            if (importModal && !importModal.classList.contains('hidden')) {
                closeImportModal();
            } else {
                closeModal();
            }
        });

        // --- Client-side Filtering ---
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const stockFilter = document.getElementById('stockFilter');

        function filterProducts() {
            const query = searchInput.value.toLowerCase().trim();
            const catId = categoryFilter.value;
            const stockVal = stockFilter.value;
            const rows = document.querySelectorAll('.product-row');
            const noResults = document.getElementById('noResults');
            const resultCount = document.getElementById('resultCount');
            let visible = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const category = row.getAttribute('data-category');
                const stock = row.getAttribute('data-stock');

                const matchSearch = !query || name.includes(query);
                const matchCategory = !catId || category === catId;
                const matchStock = !stockVal || stock === stockVal;

                if (matchSearch && matchCategory && matchStock) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (rows.length > 0) {
                noResults.classList.toggle('hidden', visible > 0);
            }
            resultCount.textContent = visible + ' product' + (visible !== 1 ? 's' : '') + ' shown';
        }

        if (searchInput) searchInput.addEventListener('input', filterProducts);
        if (categoryFilter) categoryFilter.addEventListener('change', filterProducts);
        if (stockFilter) stockFilter.addEventListener('change', filterProducts);

        // --- Shopify Import Modal ---
        const importMaxBytes = 20 * 1024 * 1024;
        let importFile = null;

        function importSwitchState(stateId) {
            ['importStateUpload', 'importStatePreview', 'importStateResult'].forEach(id => {
                document.getElementById(id).classList.toggle('hidden', id !== stateId);
            });
            document.getElementById('importBackBtn').classList.toggle('hidden', stateId !== 'importStatePreview');
            document.getElementById('importCancelBtn').classList.toggle('hidden', stateId === 'importStateResult');
            document.getElementById('importConfirmBtn').classList.toggle('hidden', stateId !== 'importStatePreview');
            document.getElementById('importDoneBtn').classList.toggle('hidden', stateId !== 'importStateResult');
        }

        function openImportModal() {
            const modal = document.getElementById('importModal');
            const backdrop = document.getElementById('importBackdrop');
            const panel = document.getElementById('importPanel');
            importResetUploadState();
            importSwitchState('importStateUpload');
            modal.classList.remove('hidden');
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            });
        }

        function closeImportModal() {
            const modal = document.getElementById('importModal');
            const backdrop = document.getElementById('importBackdrop');
            const panel = document.getElementById('importPanel');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        function importResetUploadState() {
            importFile = null;
            const input = document.getElementById('importFileInput');
            if (input) input.value = '';
            const err = document.getElementById('importUploadError');
            err.classList.add('hidden');
            err.textContent = '';
        }

        function importGoToUpload() {
            importResetUploadState();
            importSwitchState('importStateUpload');
        }

        function importShowUploadError(msg) {
            const err = document.getElementById('importUploadError');
            err.textContent = msg;
            err.classList.remove('hidden');
        }

        function importValidateFile(file) {
            if (!file) return 'No file selected';
            if (file.size > importMaxBytes) return 'File is larger than 20 MB';
            const name = (file.name || '').toLowerCase();
            if (!name.endsWith('.csv')) return 'Only .csv files are accepted';
            return null;
        }

        function importHandleFile(file) {
            const err = importValidateFile(file);
            if (err) { importShowUploadError(err); return; }
            importFile = file;
            importRequestPreview();
        }

        async function importRequestPreview() {
            const dz = document.getElementById('importDropzone');
            dz.classList.add('opacity-50', 'pointer-events-none');
            try {
                const fd = new FormData();
                fd.append('action', 'preview_shopify_import');
                fd.append('file', importFile);
                const res = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
                if (!res.ok) throw new Error('Server error ' + res.status);
                const data = await res.json();
                if (!data.ok) { importShowUploadError(data.error || 'Failed to parse CSV'); return; }
                importRenderPreview(data);
                importSwitchState('importStatePreview');
            } catch (e) {
                importShowUploadError('Network error: ' + e.message);
            } finally {
                dz.classList.remove('opacity-50', 'pointer-events-none');
            }
        }

        function importRenderPreview(data) {
            document.getElementById('importSummaryInsert').textContent = data.summary.insert;
            document.getElementById('importSummaryUpdate').textContent = data.summary.update;
            document.getElementById('importSummarySkip').textContent = data.summary.skip;

            const body = document.getElementById('importPreviewBody');
            body.innerHTML = '';
            const badge = {
                insert: '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-500/10 text-green-400 ring-1 ring-green-500/20 text-[10px] font-semibold uppercase">Insert</span>',
                update: '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 ring-1 ring-blue-500/20 text-[10px] font-semibold uppercase">Update</span>',
                skip:   '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-500/10 text-gray-400 ring-1 ring-gray-500/20 text-[10px] font-semibold uppercase">Skip</span>',
            };
            const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            data.rows.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-2 py-1.5 align-top">${badge[r.action] || ''}</td>
                    <td class="px-2 py-1.5 align-top text-white">
                        ${esc(r.name)}
                        ${r.reason ? `<div class="text-[10px] text-gray-500 mt-0.5">${esc(r.reason)}</div>` : ''}
                        ${r.notes && r.notes.length ? `<div class="text-[10px] text-amber-400 mt-0.5">${esc(r.notes.join(' '))}</div>` : ''}
                    </td>
                    <td class="px-2 py-1.5 align-top">${r.category ? esc(r.category) : '<span class="text-gray-600 italic">—</span>'}</td>
                    <td class="px-2 py-1.5 align-top text-right tabular-nums">${r.variants ?? 0}</td>
                    <td class="px-2 py-1.5 align-top text-right tabular-nums">${r.gallery ?? 0}</td>
                    <td class="px-2 py-1.5 align-top text-right tabular-nums">${r.price != null && r.action !== 'skip' ? Number(r.price).toFixed(2) : '—'}</td>
                    <td class="px-2 py-1.5 align-top text-right tabular-nums">${r.stock != null && r.action !== 'skip' ? esc(r.stock) : '—'}</td>
                `;
                body.appendChild(tr);
            });
        }

        async function importConfirm() {
            if (!importFile) return;
            const btn = document.getElementById('importConfirmBtn');
            const label = document.getElementById('importConfirmLabel');
            btn.disabled = true;
            label.textContent = 'Importing…';
            try {
                const fd = new FormData();
                fd.append('action', 'commit_shopify_import');
                fd.append('file', importFile);
                const res = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
                if (!res.ok) throw new Error('Server error ' + res.status);
                const data = await res.json();
                if (!data.ok) {
                    importResetUploadState();
                    importShowUploadError(data.error || 'Import failed');
                    importSwitchState('importStateUpload');
                    return;
                }
                importRenderResult(data.counts);
                importSwitchState('importStateResult');
            } catch (e) {
                importResetUploadState();
                importShowUploadError('Network error: ' + e.message);
                importSwitchState('importStateUpload');
            } finally {
                btn.disabled = false;
                label.textContent = 'Confirm Import';
            }
        }

        function importRenderResult(counts) {
            const detail = `${counts.inserted} new, ${counts.updated} updated, ${counts.skipped} skipped`;
            document.getElementById('importResultDetail').textContent = detail;
            const errBlock = document.getElementById('importResultErrors');
            const errList = document.getElementById('importResultErrorList');
            errList.innerHTML = '';
            if (counts.errors && counts.errors.length > 0) {
                counts.errors.forEach(e => {
                    const li = document.createElement('li');
                    li.textContent = `${e.name}: ${e.reason}`;
                    errList.appendChild(li);
                });
                errBlock.classList.remove('hidden');
            } else {
                errBlock.classList.add('hidden');
            }
        }

        function importFinish() {
            window.location.reload();
        }

        // Wire up file input + drag-and-drop on the dropzone.
        (function importInit() {
            const input = document.getElementById('importFileInput');
            const dz = document.getElementById('importDropzone');
            if (input) {
                input.addEventListener('change', e => {
                    const f = e.target.files && e.target.files[0];
                    if (f) importHandleFile(f);
                });
            }
            if (dz) {
                ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault(); e.stopPropagation();
                    dz.classList.add('ring-2', 'ring-purple-500/40');
                }));
                dz.addEventListener('dragleave', e => {
                    e.preventDefault(); e.stopPropagation();
                    dz.classList.remove('ring-2', 'ring-purple-500/40');
                });
                dz.addEventListener('drop', e => {
                    e.preventDefault(); e.stopPropagation();
                    dz.classList.remove('ring-2', 'ring-purple-500/40');
                    const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                    if (f) importHandleFile(f);
                });
            }
        })();
        </script>
