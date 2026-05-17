<?php
$db = initiate_web_database();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $image = $_POST['image'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $category_id = $category_id === '' ? null : (int)$category_id;

        $sql = "INSERT INTO products (name, description, price, stock, image, category_id) VALUES (?, ?, ?, ?, ?, ?)";
        $db->query($sql, [$name, $description, $price, $stock, $image, $category_id]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_product') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $image = $_POST['image'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $category_id = $category_id === '' ? null : (int)$category_id;

        $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ?, category_id = ? WHERE id = ?";
        $db->query($sql, [$name, $description, $price, $stock, $image, $category_id, $id]);

        echo "<script>window.location.href = window.location.href;</script>";
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
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-500 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-200 font-medium text-sm shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30">
                        <i class="bi bi-plus-lg"></i> Add Product
                    </button>
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
                <div id="modalPanel" class="relative w-full max-w-lg bg-gray-800 rounded-2xl shadow-2xl shadow-black/40 border border-white/10 transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col">
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
                        <div class="px-6 py-5 space-y-5 overflow-y-auto" style="max-height: calc(90vh - 140px);">
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
                container.classList.add('hidden');
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
            if (e.key === 'Escape') closeModal();
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
        </script>
