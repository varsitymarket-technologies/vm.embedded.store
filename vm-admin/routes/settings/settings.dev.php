<?php

                    $store_id = '';
                    $api_base_url = '';
                    $api_keys = [];
                    $new_key_display = $_GET['new_key'] ?? '';
                    $dev_error = '';
                    $private_pdo = null;

                    try {
                        $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
                        if (empty($store_record) && !empty($domain)) {
                            $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE domain = ? LIMIT 1", [$domain]);
                        }
                        $store_id = $store_record[0]['id'] ?? '';
                        $api_base_url = __WEBSITE_DOMAIN__ . "/store-access/" . $store_id . "/";

                        // Use get_private_pdo() (raw PDO) to avoid uncatchable fatal from database_manager
                        if (!empty($domain)) {
                            $private_pdo = get_private_pdo($domain);
                            if ($private_pdo) {
                                $stmt = $private_pdo->prepare("SELECT * FROM api_keys ORDER BY created_at DESC");
                                $stmt->execute();
                                $api_keys = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                            }
                        }
                    } catch (\Throwable $th) {
                        $dev_error = $th->getMessage();
                    }

                    $sdk_url = __WEBSITE_DOMAIN__ . "/store-access/" . $store_id . "/sdk/vm-store.js";
                ?>
                    <?php if (!empty($dev_error)): ?>
                    <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-exclamation-triangle text-amber-400"></i>
                            <div>
                                <p class="text-amber-400 font-bold text-sm">Configuration Notice</p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($dev_error, ENT_QUOTES, 'UTF-8'); ?>. Please ensure your store is properly configured.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <?php if (!empty($new_key_display)): ?>
                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-5 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="bi bi-check-circle-fill text-emerald-400 text-xl"></i>
                                <div class="flex-1">
                                    <h4 class="text-emerald-400 font-bold text-sm">New API Key Generated</h4>
                                    <p class="text-gray-400 text-xs mt-1 mb-3">Copy this key now. You won't be able to see the full key again.</p>
                                    <div class="flex items-center gap-3 bg-black/40 rounded-lg px-4 py-3">
                                        <code id="newKeyValue" class="text-sm font-mono text-white break-all flex-1"><?php echo htmlspecialchars($new_key_display, ENT_QUOTES, 'UTF-8'); ?></code>
                                        <button onclick="navigator.clipboard.writeText(document.getElementById('newKeyValue').textContent); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'"
                                            class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Developer Settings</h2>
                            <p class="text-sm text-gray-400 mt-2">API access and security credentials for external integrations.</p>
                        </div>
                        <div class="v-card-body py-8 px-8">

                            <!-- API Access Section -->
                            <div class="mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">API Access</h3>
                                        <p class="text-xs text-gray-500 mt-1">RESTful API endpoints for store management and automation</p>
                                    </div>
                                </div>

                                <!-- Store ID -->
                                <div class="bg-purple-500/5 rounded-xl p-4 mb-4 border border-purple-500/20">
                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <div>
                                            <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">Store ID</span>
                                            <p class="text-white font-mono text-sm mt-1"><?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?>'); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i> Copy', 2000)"
                                            class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- API Base URL -->
                                <div class="bg-blue-500/5 rounded-xl p-4 mb-5 border border-blue-500/20">
                                    <div class="flex items-center justify-between flex-wrap gap-3">
                                        <div>
                                            <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">API Endpoint</span>
                                            <p id="apiEndpoint" class="text-white font-mono text-sm mt-1 break-all"><?php echo htmlspecialchars($api_base_url, ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <button onclick="navigator.clipboard.writeText(document.getElementById('apiEndpoint').textContent); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i> Copy', 2000)"
                                            class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- Available Endpoints -->
                                <div class="bg-white/5 rounded-xl p-4 mb-6">
                                    <h4 class="text-white font-bold text-sm mb-3"><i class="bi bi-book"></i> Available Endpoints</h4>
                                    <div class="space-y-2">
                                        <p class="text-gray-500 text-[10px] uppercase tracking-wider font-bold mb-1">Read Data</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=products</code>
                                            <span class="text-gray-600 text-xs">— List products (paginated)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=product&id={id}</code>
                                            <span class="text-gray-600 text-xs">— Get single product</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=categories</code>
                                            <span class="text-gray-600 text-xs">— List categories</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=products_by_category&category_id={id}</code>
                                            <span class="text-gray-600 text-xs">— Products by category</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=search&q={query}</code>
                                            <span class="text-gray-600 text-xs">— Search products</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=discounts</code>
                                            <span class="text-gray-600 text-xs">— Active discounts</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=site</code>
                                            <span class="text-gray-600 text-xs">— Store info</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=orders&email={email}</code>
                                            <span class="text-gray-600 text-xs">— Order history by email</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded font-mono">GET</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart&cart_id={id}</code>
                                            <span class="text-gray-600 text-xs">— Get cart contents</span>
                                        </div>

                                        <p class="text-gray-500 text-[10px] uppercase tracking-wider font-bold mt-3 mb-1">Cart & Checkout</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_create</code>
                                            <span class="text-gray-600 text-xs">— Create new cart session</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_add</code>
                                            <span class="text-gray-600 text-xs">— Add item to cart</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_update</code>
                                            <span class="text-gray-600 text-xs">— Update item quantity</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=cart_remove</code>
                                            <span class="text-gray-600 text-xs">— Remove item from cart</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=checkout_create</code>
                                            <span class="text-gray-600 text-xs">— Create checkout session</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=checkout_complete</code>
                                            <span class="text-gray-600 text-xs">— Complete checkout with customer info</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded font-mono">POST</span>
                                            <code class="text-xs text-gray-400 font-mono">?state=order</code>
                                            <span class="text-gray-600 text-xs">— Place a direct order</span>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-xs mt-3">Pass API key via <code class="text-gray-500">X-API-Key</code> header, <code class="text-gray-500">Authorization: Bearer {key}</code>, or <code class="text-gray-500">?api_key={key}</code></p>
                                </div>
                            </div>

                            <!-- API Keys Section -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">API Keys</h3>
                                        <p class="text-xs text-gray-500 mt-1">Generate and manage API keys for external applications</p>
                                    </div>
                                    <button onclick="document.getElementById('generateKeyModal').classList.remove('hidden')"
                                        class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2">
                                        <i class="bi bi-plus-lg"></i> Generate New Key
                                    </button>
                                </div>

                                <?php if (!empty($api_keys)): ?>
                                <!-- Existing API Keys Table -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="border-b border-gray-700">
                                            <tr>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Key Name</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">API Key</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Created</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Last Used</th>
                                                <th class="pb-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <?php foreach ($api_keys as $key): ?>
                                            <tr class="<?php echo $key['active'] ? '' : 'opacity-50'; ?>">
                                                <td class="py-3 text-white text-sm"><?php echo htmlspecialchars($key['key_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="py-3">
                                                    <code class="bg-black/50 px-2 py-1 rounded text-xs font-mono text-gray-300"><?php echo htmlspecialchars(substr($key['api_key'], 0, 12), ENT_QUOTES, 'UTF-8'); ?>••••••••</code>
                                                </td>
                                                <td class="py-3">
                                                    <?php if ($key['active']): ?>
                                                        <span class="bg-green-500/20 text-green-400 text-xs px-2 py-0.5 rounded">Active</span>
                                                    <?php else: ?>
                                                        <span class="bg-red-500/20 text-red-400 text-xs px-2 py-0.5 rounded">Revoked</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 text-gray-400 text-xs"><?php echo htmlspecialchars($key['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="py-3 text-gray-400 text-xs"><?php echo $key['last_used'] ? htmlspecialchars($key['last_used'], ENT_QUOTES, 'UTF-8') : 'Never'; ?></td>
                                                <td class="py-3">
                                                    <div class="flex items-center gap-2">
                                                        <?php if ($key['active']): ?>
                                                        <form method="POST" style="display:inline" onsubmit="return confirm('Revoke this API key? It will no longer be able to access the API.')">
                                                            <input type="hidden" name="action" value="revoke_api_key">
                                                            <input type="hidden" name="key_id" value="<?php echo (int) $key['id']; ?>">
                                                            <button type="submit" class="text-amber-400 hover:text-amber-300 text-xs transition-colors" title="Revoke">
                                                                <i class="bi bi-slash-circle"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                        <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete this API key?')">
                                                            <input type="hidden" name="action" value="delete_api_key">
                                                            <input type="hidden" name="key_id" value="<?php echo (int) $key['id']; ?>">
                                                            <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition-colors" title="Delete">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <!-- No keys message -->
                                <div class="text-center py-8">
                                    <i class="bi bi-key text-4xl text-gray-600"></i>
                                    <p class="text-gray-500 text-sm mt-2">No API keys generated yet</p>
                                    <p class="text-gray-600 text-xs">Click "Generate New Key" to create your first API key</p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- JavaScript SDK Section -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <div class="flex items-center justify-between flex-wrap gap-4 mb-5">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">JavaScript SDK</h3>
                                        <p class="text-xs text-gray-500 mt-1">Drop-in storefront SDK for external websites — GitHub Pages, static sites, and more</p>
                                    </div>
                                </div>

                                <!-- SDK Script Tag -->
                                <div class="bg-purple-500/5 rounded-xl p-4 mb-4 border border-purple-500/20">
                                    <span class="text-xs font-mono text-gray-400 uppercase tracking-wider">Include Script</span>
                                    <div class="flex items-center gap-3 mt-2 bg-black/40 rounded-lg px-4 py-3">
                                        <code id="sdkScriptTag" class="text-sm font-mono text-purple-300 break-all flex-1">&lt;script src="<?php echo htmlspecialchars($sdk_url, ENT_QUOTES, 'UTF-8'); ?>"&gt;&lt;/script&gt;</code>
                                        <button onclick="navigator.clipboard.writeText('<script src=\'<?php echo htmlspecialchars($sdk_url, ENT_QUOTES, 'UTF-8'); ?>\'><\/script>'); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i> Copy', 2000)"
                                            class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                                            <i class="bi bi-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- SDK Quick Start -->
                                <div class="bg-black/60 rounded-xl p-5 border border-white/5 mb-4">
                                    <p class="text-gray-400 text-xs mb-3 font-mono">// Initialize the SDK</p>
                                    <pre class="text-sm font-mono text-emerald-400 whitespace-pre-wrap break-all">const store = new VMStore({
  storeId: '<?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?>',
  apiKey: 'YOUR_API_KEY'
});

// Inject default styles
store.ui.injectStyles();

// Render a product grid
store.ui.productGrid('#shop');

// Add cart badge to an element
store.ui.cartBadge('#cart-icon');

// Render interactive cart with checkout
store.ui.cartDrawer('#cart');</pre>
                                </div>

                                <!-- SDK API Reference -->
                                <div class="bg-white/5 rounded-xl p-4 mb-4">
                                    <h4 class="text-white font-bold text-sm mb-3"><i class="bi bi-code-slash"></i> SDK Methods</h4>
                                    <div class="space-y-2 text-xs font-mono">
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Products</span>
                                            <code class="text-gray-400">store.products.list({ page, limit }) / .get(id) / .search(query) / .byCategory(id)</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Cart</span>
                                            <code class="text-gray-400">store.cart.add(productId, qty) / .update(productId, qty) / .remove(productId) / .get() / .clear()</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Checkout</span>
                                            <code class="text-gray-400">store.checkout.redirect({ returnUrl }) / .create({ returnUrl }) / .complete(sessionId, customer)</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">UI</span>
                                            <code class="text-gray-400">store.ui.productGrid(el) / .productCard(el, product) / .cartBadge(el) / .cartDrawer(el) / .injectStyles()</code>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded shrink-0">Events</span>
                                            <code class="text-gray-400">store.on('cart:updated' | 'cart:item-added' | 'cart:item-removed' | 'checkout:completed', callback)</code>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Raw API Quick Start -->
                            <div class="border-t border-gray-800 pt-6 mb-8">
                                <h3 class="text-lg font-bold text-white mb-4">Raw API (fetch)</h3>
                                <div class="bg-black/60 rounded-xl p-5 border border-white/5">
                                    <p class="text-gray-400 text-xs mb-3 font-mono">// Fetch products directly via the REST API</p>
                                    <pre class="text-sm font-mono text-emerald-400 whitespace-pre-wrap break-all">fetch('<?php echo htmlspecialchars($api_base_url, ENT_QUOTES, 'UTF-8'); ?>?state=products', {
  headers: { 'X-API-Key': 'YOUR_API_KEY' }
})
.then(res => res.json())
.then(data => console.log(data));</pre>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Generate Key Modal -->
                    <div id="generateKeyModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
                        <div class="bg-gray-900 border border-gray-700 rounded-2xl p-6 w-full max-w-md">
                            <h3 class="text-lg font-bold text-white mb-4">Generate New API Key</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="generate_api_key">
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-400 mb-2">Key Name</label>
                                    <input type="text" name="key_name" required placeholder="e.g. Production App, Mobile Client"
                                        class="w-full bg-black/50 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="flex items-center gap-3 justify-end">
                                    <button type="button" onclick="document.getElementById('generateKeyModal').classList.add('hidden')"
                                        class="bg-gray-700 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm transition-all">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2 rounded-lg text-sm font-bold transition-all">
                                        <i class="bi bi-key"></i> Generate Key
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>


