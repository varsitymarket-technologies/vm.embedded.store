<?php
$db = initiate_web_database();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $image = $_POST['image'] ?? '';

        $sql = "INSERT INTO categories (name, description, image) VALUES (?, ?, ?)";
        $db->query($sql, [$name, $description, $image]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_category') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $image = $_POST['image'] ?? '';

        $sql = "UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?";
        $db->query($sql, [$name, $description, $image, $id]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_category') {
        $id = $_POST['id'] ?? 0;
        // Detach products before deleting: products.category_id REFERENCES
        // categories(id) with no ON DELETE action, and FK enforcement is
        // enabled per-connection, so a raw DELETE would fail when the
        // category has products. Null the link first to preserve the
        // pre-FK-enforcement behavior (orphan products with NULL category).
        $db->query("UPDATE products SET category_id = NULL WHERE category_id = ?", [$id]);
        $db->query("DELETE FROM categories WHERE id = ?", [$id]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Fetch Categories with product counts
$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.id DESC");

// Compute stats
$totalCategories = count($categories);
$withProducts = 0;
$emptyCategories = 0;
foreach ($categories as $cat) {
    if ((int)$cat['product_count'] > 0) {
        $withProducts++;
    } else {
        $emptyCategories++;
    }
}
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">

                <!-- Page Title Row -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Categories</h2>
                        <p class="text-sm text-gray-400 mt-1">Organize your products into browsable groups</p>
                    </div>
                    <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg font-medium text-sm transition-colors shadow-lg shadow-purple-600/20">
                        <i class="bi bi-plus-lg"></i> Add Category
                    </button>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-purple-600/20 text-purple-400">
                                <i class="bi bi-tags-fill text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Categories</p>
                                <p class="text-2xl font-bold text-white"><?php echo $totalCategories; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-emerald-600/20 text-emerald-400">
                                <i class="bi bi-check-circle-fill text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">With Products</p>
                                <p class="text-2xl font-bold text-white"><?php echo $withProducts; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-amber-600/20 text-amber-400">
                                <i class="bi bi-inbox-fill text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Empty Categories</p>
                                <p class="text-2xl font-bold text-white"><?php echo $emptyCategories; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative max-w-md">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="text" id="categorySearch" placeholder="Search categories..." oninput="filterCategories()" class="w-full bg-gray-800 border border-white/10 rounded-lg pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                    </div>
                </div>

                <!-- Category Cards Grid -->
                <?php if (empty($categories)): ?>
                    <div class="flex flex-col items-center justify-center py-24 text-center">
                        <div class="flex items-center justify-center h-20 w-20 rounded-full bg-gray-800 border border-white/5 mb-5">
                            <i class="bi bi-tags text-3xl text-gray-600"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">No categories yet</h3>
                        <p class="text-sm text-gray-500 mb-6 max-w-sm">Categories help your customers find products faster. Create your first category to get started.</p>
                        <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg font-medium text-sm transition-colors">
                            <i class="bi bi-plus-lg"></i> Create First Category
                        </button>
                    </div>
                <?php else: ?>
                    <div id="categoriesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <?php foreach ($categories as $category): ?>
                        <div class="category-card group relative bg-gray-800 rounded-xl border border-white/5 overflow-hidden hover:border-purple-500/30 transition-all duration-300 hover:shadow-lg hover:shadow-purple-500/5" data-name="<?php echo htmlspecialchars(strtolower($category['name'])); ?>" data-desc="<?php echo htmlspecialchars(strtolower($category['description'] ?? '')); ?>">
                            <!-- Card Image -->
                            <div class="relative aspect-[16/9] bg-gray-900 overflow-hidden">
                                <?php if (!empty($category['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="h-full w-full flex items-center justify-center">
                                        <i class="bi bi-image text-4xl text-gray-700"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Product count badge -->
                                <div class="absolute top-3 right-3 bg-gray-900/80 backdrop-blur-sm text-white text-xs font-bold px-2.5 py-1 rounded-full border border-white/10">
                                    <?php echo (int)$category['product_count']; ?> product<?php echo (int)$category['product_count'] !== 1 ? 's' : ''; ?>
                                </div>

                                <!-- Hover overlay with actions -->
                                <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                    <button onclick='openModal("edit", <?php echo json_encode($category); ?>)' class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Delete this category? Products inside will become uncategorized.');" class="inline">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="flex items-center gap-2 bg-red-600/80 hover:bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                                            <i class="bi bi-trash3"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4">
                                <h3 class="text-white font-semibold text-base truncate"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <?php if (!empty($category['description'])): ?>
                                    <p class="text-gray-400 text-sm mt-1 line-clamp-2"><?php echo htmlspecialchars($category['description']); ?></p>
                                <?php else: ?>
                                    <p class="text-gray-600 text-sm mt-1 italic">No description</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- No search results message -->
                    <div id="noResults" class="hidden flex flex-col items-center justify-center py-16 text-center">
                        <i class="bi bi-search text-3xl text-gray-600 mb-3"></i>
                        <p class="text-gray-400 font-medium">No categories match your search</p>
                        <p class="text-sm text-gray-600 mt-1">Try a different keyword</p>
                    </div>
                <?php endif; ?>

            </main>
        </div>

        <!-- Modal -->
        <div id="categoryModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>

            <!-- Modal Panel -->
            <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
                <div id="modalPanel" class="pointer-events-auto w-full max-w-lg bg-gray-800 rounded-xl border border-white/10 shadow-2xl transform transition-all duration-300 scale-95 opacity-0">
                    <form method="POST" id="categoryForm">
                        <input type="hidden" name="action" id="formAction" value="add_category">
                        <input type="hidden" name="id" id="categoryId">

                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                            <h3 class="text-lg font-semibold text-white" id="modalTitle">Add New Category</h3>
                            <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/5">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                            <!-- Category Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Category Name</label>
                                <input type="text" name="name" id="categoryName" required placeholder="e.g. Electronics, Clothing..." class="w-full bg-gray-700 border border-white/10 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Description</label>
                                <textarea name="description" id="categoryDescription" rows="3" placeholder="Brief description of this category..." class="w-full bg-gray-700 border border-white/10 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors resize-none"></textarea>
                            </div>

                            <!-- Image Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Category Image</label>

                                <!-- Image Preview -->
                                <div id="imagePreviewContainer" class="hidden mb-3 relative w-full aspect-[16/9] rounded-xl overflow-hidden bg-gray-900 border border-white/10 group">
                                    <img id="imagePreview" src="" class="h-full w-full object-cover">
                                    <button type="button" onclick="removeImage()" class="absolute top-2 right-2 bg-red-500/90 hover:bg-red-500 text-white rounded-lg p-1.5 opacity-0 group-hover:opacity-100 transition-all">
                                        <i class="bi bi-trash3 text-sm"></i>
                                    </button>
                                </div>

                                <!-- Upload Area -->
                                <label class="relative block cursor-pointer">
                                    <div class="flex flex-col items-center justify-center gap-2 bg-gray-700/50 border-2 border-dashed border-white/10 rounded-xl px-4 py-8 text-gray-400 hover:border-purple-500/50 hover:text-gray-300 transition-all">
                                        <i class="bi bi-cloud-arrow-up text-3xl"></i>
                                        <span class="text-xs font-bold uppercase tracking-widest">Click to Upload Image</span>
                                        <span class="text-xs text-gray-600">PNG, JPG, GIF up to 5MB</span>
                                    </div>
                                    <input type="file" id="categoryImageFile" accept="image/*" class="hidden" onchange="handleImageUpload(this)">
                                </label>

                                <!-- Divider -->
                                <div class="flex items-center gap-3 my-3">
                                    <div class="h-px flex-1 bg-white/5"></div>
                                    <span class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">or paste URL</span>
                                    <div class="h-px flex-1 bg-white/5"></div>
                                </div>

                                <!-- URL Input -->
                                <input type="text" name="image" id="categoryImage" placeholder="https://example.com/image.jpg" oninput="previewFromUrl(this.value)" class="w-full bg-gray-700 border border-white/10 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-white/5 bg-gray-800/50">
                            <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-sm font-medium text-white transition-colors shadow-lg shadow-purple-600/20">
                                Save Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        </style>

        <script>
        // --- Image Handling ---
        function handleImageUpload(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64String = e.target.result;
                    document.getElementById('categoryImage').value = base64String;
                    showPreview(base64String);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewFromUrl(url) {
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                showPreview(url);
            } else if (!url) {
                hidePreview();
            }
        }

        function showPreview(src) {
            const preview = document.getElementById('imagePreview');
            const container = document.getElementById('imagePreviewContainer');
            preview.src = src;
            container.classList.remove('hidden');
        }

        function hidePreview() {
            document.getElementById('imagePreviewContainer').classList.add('hidden');
        }

        function removeImage() {
            document.getElementById('categoryImage').value = '';
            document.getElementById('categoryImageFile').value = '';
            hidePreview();
        }

        // --- Modal ---
        function openModal(mode, category = null) {
            const modal = document.getElementById('categoryModal');
            const panel = document.getElementById('modalPanel');
            const form = document.getElementById('categoryForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');

            modal.classList.remove('hidden');

            // Trigger entrance animation
            requestAnimationFrame(function() {
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            });

            if (mode === 'edit' && category) {
                title.textContent = 'Edit Category';
                action.value = 'update_category';
                document.getElementById('categoryId').value = category.id;
                document.getElementById('categoryName').value = category.name || '';
                document.getElementById('categoryDescription').value = category.description || '';
                document.getElementById('categoryImage').value = category.image || '';

                if (category.image) {
                    showPreview(category.image);
                } else {
                    hidePreview();
                }
            } else {
                title.textContent = 'Add New Category';
                action.value = 'add_category';
                form.reset();
                document.getElementById('categoryId').value = '';
                hidePreview();
            }
        }

        function closeModal() {
            const modal = document.getElementById('categoryModal');
            const panel = document.getElementById('modalPanel');

            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');

            setTimeout(function() {
                modal.classList.add('hidden');
            }, 200);
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // --- Search / Filter ---
        function filterCategories() {
            const query = document.getElementById('categorySearch').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.category-card');
            const grid = document.getElementById('categoriesGrid');
            const noResults = document.getElementById('noResults');
            let visibleCount = 0;

            cards.forEach(function(card) {
                const name = card.getAttribute('data-name') || '';
                const desc = card.getAttribute('data-desc') || '';
                const matches = name.includes(query) || desc.includes(query);
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            if (grid) grid.style.display = visibleCount > 0 ? '' : 'none';
            if (noResults) noResults.style.display = visibleCount === 0 ? '' : 'none';
        }
        </script>