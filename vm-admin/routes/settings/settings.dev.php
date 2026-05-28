<?php
$store_id = '';
$api_base_url = '';
$api_keys = [];
$cors_domains = [];
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

    if (!empty($domain)) {
        $private_pdo = get_private_pdo($domain);
        if ($private_pdo) {
            $stmt = $private_pdo->prepare("SELECT * FROM api_keys ORDER BY created_at DESC");
            $stmt->execute();
            $api_keys = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stmt2 = $private_pdo->prepare("SELECT * FROM cors_domains ORDER BY created_at DESC");
            $stmt2->execute();
            $cors_domains = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }
} catch (\Throwable $th) {
    $dev_error = $th->getMessage();
}

$sdk_url = __WEBSITE_DOMAIN__ . "/store-access/" . $store_id . "/sdk/vm-store.js";
?>

<a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white text-sm font-medium transition-colors mb-6">
    <i class="bi bi-arrow-left"></i> Back to Settings
</a>

<?php if (!empty($dev_error)): ?>
<div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 mb-4 flex items-center gap-3">
    <i class="bi bi-exclamation-triangle text-amber-400"></i>
    <div>
        <p class="text-amber-400 font-medium text-sm">Configuration Notice</p>
        <p class="text-zinc-400 text-xs"><?php echo htmlspecialchars($dev_error, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($new_key_display)): ?>
<div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mb-4">
    <div class="flex items-start gap-3">
        <i class="bi bi-check-circle-fill text-emerald-400"></i>
        <div class="flex-1">
            <h4 class="text-emerald-400 font-medium text-sm">New API Key Generated</h4>
            <p class="text-zinc-400 text-xs mt-1 mb-2">Copy this key now. You won't be able to see the full key again.</p>
            <div class="flex items-center gap-2 bg-zinc-900 rounded-lg px-3 py-2">
                <code id="newKeyValue" class="text-sm font-mono text-white break-all flex-1"><?php echo htmlspecialchars($new_key_display, ENT_QUOTES, 'UTF-8'); ?></code>
                <button onclick="navigator.clipboard.writeText(document.getElementById('newKeyValue').textContent); this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied'"
                    class="text-xs bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1 rounded-lg transition-colors whitespace-nowrap">
                    <i class="bi bi-copy"></i> Copy
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="space-y-4">

    <!-- API Access -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h2 class="text-lg font-bold text-white">API Access</h2>
            <p class="text-zinc-400 text-sm mt-1">RESTful API endpoints for store management and automation</p>
        </div>
        <div class="p-5 space-y-4">

            <!-- Store ID & Base URL -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="bg-violet-500/5 border border-violet-500/20 rounded-lg px-4 py-3">
                    <span class="text-zinc-500 text-[10px] font-medium uppercase tracking-wider">Store ID</span>
                    <div class="flex items-center justify-between mt-1">
                        <code class="text-white font-mono text-sm"><?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?></code>
                        <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?>'); this.innerHTML='<i class=\'bi bi-check-lg\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i>', 2000)"
                            class="text-zinc-500 hover:text-white text-xs transition-colors"><i class="bi bi-copy"></i></button>
                    </div>
                </div>
                <div class="bg-sky-500/5 border border-sky-500/20 rounded-lg px-4 py-3">
                    <span class="text-zinc-500 text-[10px] font-medium uppercase tracking-wider">API Endpoint</span>
                    <div class="flex items-center justify-between mt-1">
                        <code id="apiEndpoint" class="text-white font-mono text-sm break-all"><?php echo htmlspecialchars($api_base_url, ENT_QUOTES, 'UTF-8'); ?></code>
                        <button onclick="navigator.clipboard.writeText(document.getElementById('apiEndpoint').textContent); this.innerHTML='<i class=\'bi bi-check-lg\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i>', 2000)"
                            class="text-zinc-500 hover:text-white text-xs transition-colors ml-2"><i class="bi bi-copy"></i></button>
                    </div>
                </div>
            </div>

            <!-- Endpoints -->
            <div class="bg-zinc-800 rounded-lg p-4">
                <h4 class="text-white font-medium text-sm mb-3"><i class="bi bi-book"></i> Available Endpoints</h4>
                <div class="space-y-1.5 text-xs">
                    <p class="text-zinc-500 text-[10px] uppercase tracking-wider font-medium mb-1">Read Data</p>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=products</code><span class="text-zinc-600">— List products</span></div>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=product&id={id}</code><span class="text-zinc-600">— Single product</span></div>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=categories</code><span class="text-zinc-600">— List categories</span></div>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=search&q={query}</code><span class="text-zinc-600">— Search products</span></div>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=discounts</code><span class="text-zinc-600">— Active discounts</span></div>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=site</code><span class="text-zinc-600">— Store info</span></div>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=orders&email={email}</code><span class="text-zinc-600">— Order history</span></div>
                    <div class="flex items-center gap-2"><span class="bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-mono text-[10px]">GET</span><code class="text-zinc-400 font-mono">?state=cart&cart_id={id}</code><span class="text-zinc-600">— Cart contents</span></div>

                    <p class="text-zinc-500 text-[10px] uppercase tracking-wider font-medium mt-3 mb-1">Cart & Checkout</p>
                    <div class="flex items-center gap-2"><span class="bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded font-mono text-[10px]">POST</span><code class="text-zinc-400 font-mono">?state=cart_create</code><span class="text-zinc-600">— Create cart</span></div>
                    <div class="flex items-center gap-2"><span class="bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded font-mono text-[10px]">POST</span><code class="text-zinc-400 font-mono">?state=cart_add</code><span class="text-zinc-600">— Add to cart</span></div>
                    <div class="flex items-center gap-2"><span class="bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded font-mono text-[10px]">POST</span><code class="text-zinc-400 font-mono">?state=cart_update</code><span class="text-zinc-600">— Update quantity</span></div>
                    <div class="flex items-center gap-2"><span class="bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded font-mono text-[10px]">POST</span><code class="text-zinc-400 font-mono">?state=cart_remove</code><span class="text-zinc-600">— Remove item</span></div>
                    <div class="flex items-center gap-2"><span class="bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded font-mono text-[10px]">POST</span><code class="text-zinc-400 font-mono">?state=checkout_create</code><span class="text-zinc-600">— Create checkout</span></div>
                    <div class="flex items-center gap-2"><span class="bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded font-mono text-[10px]">POST</span><code class="text-zinc-400 font-mono">?state=checkout_complete</code><span class="text-zinc-600">— Complete checkout</span></div>
                    <div class="flex items-center gap-2"><span class="bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded font-mono text-[10px]">POST</span><code class="text-zinc-400 font-mono">?state=order</code><span class="text-zinc-600">— Place order</span></div>
                </div>
                <p class="text-zinc-600 text-xs mt-3">Auth via <code class="text-zinc-500">X-API-Key</code> header, <code class="text-zinc-500">Authorization: Bearer {key}</code>, or <code class="text-zinc-500">?api_key={key}</code></p>
            </div>

        </div>
    </div>

    <!-- API Keys -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
            <div>
                <h3 class="text-white font-semibold text-sm">API Keys</h3>
                <p class="text-zinc-500 text-xs mt-0.5">Generate and manage keys for external applications</p>
            </div>
            <button onclick="document.getElementById('generateKeyModal').classList.remove('hidden')"
                class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-1.5">
                <i class="bi bi-plus-lg"></i> Generate Key
            </button>
        </div>
        <?php if (!empty($api_keys)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-5 py-3 font-medium">Key</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Created</th>
                        <th class="px-5 py-3 font-medium">Last Used</th>
                        <th class="px-5 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/50">
                    <?php foreach ($api_keys as $key): ?>
                    <tr class="hover:bg-zinc-800/30 transition-colors group <?php echo $key['active'] ? '' : 'opacity-50'; ?>">
                        <td class="px-5 py-3 text-white text-sm"><?php echo htmlspecialchars($key['key_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-5 py-3">
                            <code class="bg-zinc-800 px-2 py-0.5 rounded text-xs font-mono text-zinc-300"><?php echo htmlspecialchars(substr($key['api_key'], 0, 12), ENT_QUOTES, 'UTF-8'); ?>••••</code>
                        </td>
                        <td class="px-5 py-3">
                            <?php if ($key['active']): ?>
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">
                                    <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-red-500/10 text-red-400">
                                    <span class="w-1 h-1 rounded-full bg-red-400"></span> Revoked
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-zinc-500 text-xs"><?php echo htmlspecialchars($key['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-5 py-3 text-zinc-500 text-xs"><?php echo $key['last_used'] ? htmlspecialchars($key['last_used'], ENT_QUOTES, 'UTF-8') : 'Never'; ?></td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php if ($key['active']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Revoke this API key?')">
                                    <input type="hidden" name="action" value="revoke_api_key">
                                    <input type="hidden" name="key_id" value="<?php echo (int) $key['id']; ?>">
                                    <button type="submit" class="p-1.5 rounded-md hover:bg-zinc-700 text-amber-400 transition-colors" title="Revoke"><i class="bi bi-slash-circle"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Permanently delete this key?')">
                                    <input type="hidden" name="action" value="delete_api_key">
                                    <input type="hidden" name="key_id" value="<?php echo (int) $key['id']; ?>">
                                    <button type="submit" class="p-1.5 rounded-md hover:bg-red-900/30 text-red-400 transition-colors" title="Delete"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500">
            <i class="bi bi-key text-3xl mb-2"></i>
            <p class="text-sm">No API keys generated yet</p>
            <p class="text-zinc-600 text-xs mt-0.5">Click "Generate Key" to create your first API key</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- CORS Domains -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h3 class="text-white font-semibold text-sm">Allowed Domains (CORS)</h3>
            <p class="text-zinc-500 text-xs mt-0.5">Only these domains can make browser API requests. Empty = allow all.</p>
        </div>
        <div class="p-5">
            <form method="POST" class="flex items-center gap-2 mb-4">
                <input type="hidden" name="action" value="add_cors_domain">
                <input type="text" name="cors_domain" required placeholder="https://example.com"
                    class="flex-1 bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors font-mono"
                    pattern=".*\..*" title="Enter a valid domain">
                <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-xs font-medium transition-colors whitespace-nowrap">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </form>

            <?php if (!empty($cors_domains)): ?>
            <div class="space-y-2">
                <?php foreach ($cors_domains as $cd): ?>
                <div class="flex items-center justify-between bg-zinc-800 rounded-lg px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-globe2 text-sky-400 text-xs"></i>
                        <code class="text-sm font-mono text-white"><?php echo htmlspecialchars($cd['domain'], ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <form method="POST" class="inline" onsubmit="return confirm('Remove this domain?')">
                        <input type="hidden" name="action" value="remove_cors_domain">
                        <input type="hidden" name="cors_id" value="<?php echo (int) $cd['id']; ?>">
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs transition-colors"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-amber-500/5 border border-amber-500/15 rounded-lg p-3 flex items-start gap-2">
                <i class="bi bi-info-circle text-amber-400 mt-0.5 text-xs"></i>
                <div>
                    <p class="text-amber-400 text-xs font-medium">Open Access Mode</p>
                    <p class="text-zinc-400 text-[10px] mt-0.5">All origins (<code class="text-zinc-500">*</code>) currently allowed. Add domains to restrict.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript SDK -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-800">
            <h3 class="text-white font-semibold text-sm">JavaScript SDK</h3>
            <p class="text-zinc-500 text-xs mt-0.5">Drop-in storefront SDK for external websites</p>
        </div>
        <div class="p-5 space-y-4">

            <!-- Script Tag -->
            <div class="bg-violet-500/5 border border-violet-500/20 rounded-lg px-4 py-3">
                <span class="text-zinc-500 text-[10px] font-medium uppercase tracking-wider">Include Script</span>
                <div class="flex items-center gap-2 mt-1">
                    <code id="sdkScriptTag" class="text-sm font-mono text-violet-300 break-all flex-1">&lt;script src="<?php echo htmlspecialchars($sdk_url, ENT_QUOTES, 'UTF-8'); ?>"&gt;&lt;/script&gt;</code>
                    <button onclick="navigator.clipboard.writeText('<script src=\'<?php echo htmlspecialchars($sdk_url, ENT_QUOTES, 'UTF-8'); ?>\'><\/script>'); this.innerHTML='<i class=\'bi bi-check-lg\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i>', 2000)"
                        class="text-zinc-500 hover:text-white text-xs transition-colors"><i class="bi bi-copy"></i></button>
                </div>
            </div>

            <!-- Quick Start -->
            <div class="bg-zinc-800 rounded-lg p-4 border border-zinc-700">
                <p class="text-zinc-500 text-xs mb-2 font-mono">// Initialize the SDK</p>
                <pre class="text-xs font-mono text-emerald-400 whitespace-pre-wrap break-all leading-relaxed">const store = new VMStore({
  storeId: '<?php echo htmlspecialchars($store_id, ENT_QUOTES, 'UTF-8'); ?>',
  apiKey: 'YOUR_API_KEY'
});

store.ui.injectStyles();
store.ui.productGrid('#shop');
store.ui.cartBadge('#cart-icon');
store.ui.cartDrawer('#cart');</pre>
            </div>

            <!-- SDK Methods -->
            <div class="bg-zinc-800 rounded-lg p-4">
                <h4 class="text-white font-medium text-xs mb-2"><i class="bi bi-code-slash"></i> SDK Methods</h4>
                <div class="space-y-1.5 text-[11px] font-mono">
                    <div class="flex items-start gap-2"><span class="bg-violet-500/10 text-violet-400 px-1.5 py-0.5 rounded shrink-0">Products</span><code class="text-zinc-400">.products.list() / .get(id) / .search(q) / .byCategory(id)</code></div>
                    <div class="flex items-start gap-2"><span class="bg-violet-500/10 text-violet-400 px-1.5 py-0.5 rounded shrink-0">Cart</span><code class="text-zinc-400">.cart.add(id, qty) / .update(id, qty) / .remove(id) / .get() / .clear()</code></div>
                    <div class="flex items-start gap-2"><span class="bg-violet-500/10 text-violet-400 px-1.5 py-0.5 rounded shrink-0">Checkout</span><code class="text-zinc-400">.checkout.redirect({returnUrl}) / .create() / .complete()</code></div>
                    <div class="flex items-start gap-2"><span class="bg-violet-500/10 text-violet-400 px-1.5 py-0.5 rounded shrink-0">UI</span><code class="text-zinc-400">.ui.productGrid(el) / .cartBadge(el) / .cartDrawer(el) / .injectStyles()</code></div>
                    <div class="flex items-start gap-2"><span class="bg-violet-500/10 text-violet-400 px-1.5 py-0.5 rounded shrink-0">Events</span><code class="text-zinc-400">.on('cart:updated' | 'cart:item-added' | 'checkout:completed', cb)</code></div>
                </div>
            </div>

            <!-- Raw API -->
            <div class="bg-zinc-800 rounded-lg p-4 border border-zinc-700">
                <p class="text-zinc-500 text-xs mb-2 font-mono">// Raw fetch example</p>
                <pre class="text-xs font-mono text-emerald-400 whitespace-pre-wrap break-all leading-relaxed">fetch('<?php echo htmlspecialchars($api_base_url, ENT_QUOTES, 'UTF-8'); ?>?state=products', {
  headers: { 'X-API-Key': 'YOUR_API_KEY' }
})
.then(res => res.json())
.then(data => console.log(data));</pre>
            </div>

        </div>
    </div>

</div>

<!-- Generate Key Modal -->
<div id="generateKeyModal" class="hidden fixed inset-0 z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="this.parentElement.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-zinc-900 border border-zinc-800 rounded-xl w-full max-w-md shadow-2xl">
            <form method="POST">
                <input type="hidden" name="action" value="generate_api_key">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold">Generate API Key</h3>
                    <button type="button" onclick="document.getElementById('generateKeyModal').classList.add('hidden')" class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-5">
                    <label class="block text-zinc-400 text-xs font-medium mb-1.5">Key Name</label>
                    <input type="text" name="key_name" required placeholder="e.g. Production App, Mobile Client"
                        class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                </div>
                <div class="px-5 py-4 border-t border-zinc-800 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('generateKeyModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white transition-colors">Cancel</button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="bi bi-key"></i> Generate Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
