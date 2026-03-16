<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-4 md:p-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-white">Dashboard Overview</h2>
                <p class="text-gray-400 text-sm">Real-time insights for your business performance.</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="flex items-center gap-2 px-4 py-2 bg-gray-800 border border-white/10 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 transition">
                    <i class="bi bi-download"></i> Export Report
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4 mb-8">
            
            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Total Revenue</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo $currency_symbol . number_format($total_revenue, 2); ?>
                        </p>
                        <span class="text-green-500 text-xs font-medium flex items-center gap-1 mt-2">
                            <i class="bi bi-arrow-up-short"></i> +12.5% <span class="text-gray-500">vs last month</span>
                        </span>
                    </div>
                    <div class="p-3 bg-green-500/10 rounded-xl text-green-500">
                        <i class="bi bi-currency-dollar text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Total Orders</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo number_format($total_orders); ?>
                        </p>
                        <span class="text-blue-500 text-xs font-medium flex items-center gap-1 mt-2">
                            <i class="bi bi-bag-check"></i> 48 <span class="text-gray-500">new today</span>
                        </span>
                    </div>
                    <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
                        <i class="bi bi-cart3 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Products</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo number_format($total_products); ?>
                        </p>
                        <span class="text-purple-500 text-xs font-medium flex items-center gap-1 mt-2">
                             Active Inventory
                        </span>
                    </div>
                    <div class="p-3 bg-purple-500/10 rounded-xl text-purple-500">
                        <i class="bi bi-box-seam text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-gray-800 p-6 border border-white/5 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-xs uppercase tracking-wider font-semibold text-gray-500">Customers</p>
                        <p class="text-3xl font-bold text-white mt-2">
                            <?php echo number_format($total_users); ?>
                        </p>
                        <span class="text-orange-500 text-xs font-medium flex items-center gap-1 mt-2">
                            <i class="bi bi-person-plus"></i> +3% <span class="text-gray-500">growth</span>
                        </span>
                    </div>
                    <div class="p-3 bg-orange-500/10 rounded-xl text-orange-500">
                        <i class="bi bi-people text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-gray-800 border border-white/5 shadow-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Recent Transactions</h3>
                <a href="orders.php" class="text-sm text-purple-400 hover:text-purple-300 font-medium transition">View All Orders</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-900/50 text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Order ID</th>
                            <th class="px-6 py-4 font-semibold">Customer</th>
                            <th class="px-6 py-4 font-semibold">Amount</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
                        // Fetching specific recent orders for the table
                        $recent_orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                        foreach ($recent_orders as $order):
                            $status_class = match($order['status']) {
                                'completed' => 'bg-green-500/10 text-green-500',
                                'pending'   => 'bg-yellow-500/10 text-yellow-500',
                                'cancelled' => 'bg-red-500/10 text-red-500',
                                default     => 'bg-gray-500/10 text-gray-400',
                            };
                        ?>
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="px-6 py-4 font-mono text-gray-400 group-hover:text-purple-400 transition">
                                #<?php echo $order['id']; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold text-white">
                                        <?php echo strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <span class="text-gray-200 font-medium"><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown'); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-white font-semibold">
                                <?php echo $currency_symbol . number_format($order['total_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide <?php echo $status_class; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="p-2 hover:bg-gray-700 rounded-lg text-gray-400 hover:text-white transition">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
