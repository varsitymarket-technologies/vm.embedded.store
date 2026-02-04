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

        $sql = "INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)";
        $db->query($sql, [$name, $description, $price, $stock, $image]);
        
        // Refresh to prevent resubmission
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_product') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $image = $_POST['image'] ?? '';

        $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?";
        $db->query($sql, [$name, $description, $price, $stock, $image, $id]);
        
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

// Fetch Products
$products = $db->query("SELECT * FROM products ORDER BY id DESC");
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10">
                <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <div class="flex items-center gap-4 ml-auto">
                    <button class="relative text-gray-400 hover:text-white">
                        <i class="bi bi-bell text-xl"></i>
                        <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-purple-500"></span>
                    </button>
                    <div class="relative group">
                        <button class="flex items-center gap-2 text-sm font-medium text-white focus:outline-none">
                            <div class="h-8 w-8 rounded-full bg-purple-600 flex items-center justify-center">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="hidden md:block">Admin</span>
                            <i class="bi bi-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <!-- Dropdown -->
                        <div class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block border border-white/10">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profile</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Settings</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Sign out</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Store Products</h2>
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="bi bi-plus-lg"></i> Add Product
                    </button>
                </div>

                <!-- Products Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-700/50 text-xs uppercase text-gray-300">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Product</th>
                                    <th scope="col" class="px-6 py-3">Price</th>
                                    <th scope="col" class="px-6 py-3">Stock</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="bi bi-box-seam text-4xl mb-2"></i>
                                                <p>No products found. Click "Add Product" to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                    <tr class="hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4 font-medium text-white">#<?php echo $product['id']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <?php if (!empty($product['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" class="h-10 w-10 rounded object-cover bg-gray-700">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 rounded bg-gray-700 flex items-center justify-center text-gray-500">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="font-medium text-white"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 40)) . (strlen($product['description'] ?? '') > 40 ? '...' : ''); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-white">$<?php echo number_format($product['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $product['stock'] > 0 ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'; ?>">
                                                <?php echo $product['stock']; ?> in stock
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-3">
                                                <button onclick='openModal("edit", <?php echo json_encode($product); ?>)' class="text-blue-400 hover:text-blue-300 transition-colors" title="Edit">
                                                    <i class="bi bi-pencil-square text-lg"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="text-red-400 hover:text-red-300 transition-colors" title="Delete">
                                                        <i class="bi bi-trash text-lg"></i>
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
                </div>
            </main>
        </div>

        <!-- Modal -->
        <div id="productModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>

                <div class="relative inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-white/10">
                    <form method="POST" id="productForm">
                        <input type="hidden" name="action" id="formAction" value="add_product">
                        <input type="hidden" name="id" id="productId">
                        
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-5">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modalTitle">Add New Product</h3>
                                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Product Name</label>
                                    <input type="text" name="name" id="productName" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Description</label>
                                    <textarea name="description" id="productDescription" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"></textarea>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-1">Price ($)</label>
                                        <input type="number" step="0.01" name="price" id="productPrice" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-1">Stock Quantity</label>
                                        <input type="number" name="stock" id="productStock" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Image URL</label>
                                    <input type="text" name="image" id="productImage" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors" placeholder="https://example.com/image.jpg">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-700/30 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-white/5">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Save Product
                            </button>
                            <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-600 shadow-sm px-4 py-2 bg-transparent text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openModal(mode, product = null) {
            const modal = document.getElementById('productModal');
            const form = document.getElementById('productForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            
            modal.classList.remove('hidden');
            
            if (mode === 'edit' && product) {
                title.textContent = 'Edit Product';
                action.value = 'update_product';
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productDescription').value = product.description;
                document.getElementById('productPrice').value = product.price;
                document.getElementById('productStock').value = product.stock;
                document.getElementById('productImage').value = product.image;
            } else {
                title.textContent = 'Add New Product';
                action.value = 'add_product';
                form.reset();
                document.getElementById('productId').value = '';
            }
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }
        </script>
