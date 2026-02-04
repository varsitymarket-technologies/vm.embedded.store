        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <h2 class="text-2xl font-semibold mb-6">Dashboard Overview</h2>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4 mb-8">
                    <!-- Card 1 -->
                    <div class="rounded-xl bg-gray-800 p-6 shadow-sm border border-white/5 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Total Users</p>
                                <p class="text-3xl font-bold text-white mt-2">1,250</p>
                            </div>
                            <div class="p-3 bg-purple-500/10 rounded-full text-purple-500">
                                <i class="bi bi-people text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div class="rounded-xl bg-gray-800 p-6 shadow-sm border border-white/5 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Total Sales</p>
                                <p class="text-3xl font-bold text-white mt-2">$45,200</p>
                            </div>
                            <div class="p-3 bg-green-500/10 rounded-full text-green-500">
                                <i class="bi bi-currency-dollar text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div class="rounded-xl bg-gray-800 p-6 shadow-sm border border-white/5 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Orders</p>
                                <p class="text-3xl font-bold text-white mt-2">320</p>
                            </div>
                            <div class="p-3 bg-blue-500/10 rounded-full text-blue-500">
                                <i class="bi bi-cart text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Card 4 -->
                    <div class="rounded-xl bg-gray-800 p-6 shadow-sm border border-white/5 relative overflow-hidden group hover:-translate-y-1 transition-transform duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-400">Pending</p>
                                <p class="text-3xl font-bold text-white mt-2">15</p>
                            </div>
                            <div class="p-3 bg-orange-500/10 rounded-full text-orange-500">
                                <i class="bi bi-hourglass-split text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="px-6 py-4 border-b border-white/10">
                        <h3 class="text-lg font-semibold">Recent Orders</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-700/50 text-xs uppercase text-gray-300">
                                <tr>
                                    <th scope="col" class="px-6 py-3">#ID</th>
                                    <th scope="col" class="px-6 py-3">Customer</th>
                                    <th scope="col" class="px-6 py-3">Product</th>
                                    <th scope="col" class="px-6 py-3">Amount</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr class="hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 font-medium text-white">1001</td>
                                    <td class="px-6 py-4">John Doe</td>
                                    <td class="px-6 py-4">Premium Plan</td>
                                    <td class="px-6 py-4">$99.00</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-medium text-green-500">Completed</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="text-purple-500 hover:text-purple-400 font-medium">View</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 font-medium text-white">1002</td>
                                    <td class="px-6 py-4">Jane Smith</td>
                                    <td class="px-6 py-4">Basic Plan</td>
                                    <td class="px-6 py-4">$29.00</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-yellow-500/10 px-2.5 py-0.5 text-xs font-medium text-yellow-500">Pending</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="text-purple-500 hover:text-purple-400 font-medium">View</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 font-medium text-white">1003</td>
                                    <td class="px-6 py-4">Mike Johnson</td>
                                    <td class="px-6 py-4">Pro Plan</td>
                                    <td class="px-6 py-4">$199.00</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-medium text-red-500">Cancelled</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="text-purple-500 hover:text-purple-400 font-medium">View</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
