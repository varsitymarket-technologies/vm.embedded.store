<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Payments Page</h2>
                </div>

                                
                <div class="flex flex-1 flex-col bg-gray-900 text-white min-h-screen p-2" x-data="dashboard()">
                    <div class="max-w-7xl mx-auto w-full space-y-8">
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <template x-for="stat in stats" :key="stat.label">
                                <div class="bg-gray-800 border border-white/5 p-5 rounded-2xl">
                                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider" x-text="stat.label"></p>
                                    <div class="flex items-end justify-between mt-2">
                                        <h2 class="text-2xl font-bold" x-text="stat.value"></h2>
                                        <span class="text-xs font-medium" :class="stat.trendUp ? 'text-green-400' : 'text-red-400'" 
                                            x-text="(stat.trendUp ? '↑ ' : '↓ ') + stat.percentage"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            
                            <div class="lg:col-span-2 bg-gray-800 border border-white/5 rounded-3xl p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-bold">Revenue Growth</h3>
                                    <select class="bg-gray-900 border-none text-xs rounded-lg px-2 py-1 outline-none">
                                        <option>Last 7 Days</option>
                                        <option>Last 30 Days</option>
                                    </select>
                                </div>
                                <div class="h-64">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>

                            <div class="bg-gradient-to-b from-purple-600/20 to-gray-800 border border-purple-500/20 rounded-3xl p-8 relative overflow-hidden">
                                <div class="relative z-10">
                                    <p class="text-purple-300 text-sm mb-1">Available to Payout</p>
                                    <h1 class="text-5xl font-black mb-6">$<span x-text="wallet.balance.toLocaleString()"></span></h1>
                                    
                                    <form @submit.prevent="submitWithdrawal" class="space-y-4">
                                        <div>
                                            <label class="text-[10px] uppercase text-purple-300 font-bold">Withdrawal Amount</label>
                                            <input type="number" x-model="withdrawAmount" 
                                                class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 outline-none focus:border-purple-400 transition-all"
                                                placeholder="0.00">
                                            <p x-show="withdrawAmount > wallet.balance" class="text-red-400 text-[10px] mt-1 font-bold">
                                                ⚠️ Exceeds available balance!
                                            </p>
                                        </div>
                                        <button type="submit" 
                                                :disabled="withdrawAmount <= 0 || withdrawAmount > wallet.balance || loading"
                                                class="w-full bg-white text-black font-bold py-4 rounded-xl hover:scale-[1.02] active:scale-[0.98] disabled:opacity-30 transition-all">
                                            <span x-show="!loading">Request Instant Payout</span>
                                            <span x-show="loading">Processing...</span>
                                        </button>
                                    </form>
                                </div>
                                <div class="absolute -right-20 -top-20 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <div class="lg:col-span-2 bg-gray-800 border border-white/5 rounded-3xl overflow-hidden">
                                <div class="p-6 border-b border-white/5 flex justify-between items-center">
                                    <h3 class="text-lg font-bold">Recent Transactions</h3>
                                    <button class="text-xs text-purple-400 hover:underline">View All Report</button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="bg-white/5 text-[10px] uppercase text-gray-400">
                                            <tr>
                                                <th class="px-6 py-4">Transaction ID</th>
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Method</th>
                                                <th class="px-6 py-4 text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-white/5">
                                            <template x-for="tx in transactions" :key="tx.id">
                                                <tr class="hover:bg-white/5 transition-colors">
                                                    <td class="px-6 py-4 font-mono text-xs text-gray-400" x-text="tx.id"></td>
                                                    <td class="px-6 py-4">
                                                        <span :class="{
                                                            'bg-green-500/10 text-green-500': tx.status === 'Completed',
                                                            'bg-yellow-500/10 text-yellow-500': tx.status === 'Pending'
                                                        }" class="px-2 py-1 rounded text-[10px] font-bold" x-text="tx.status"></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-300" x-text="tx.method"></td>
                                                    <td class="px-6 py-4 text-right font-bold" x-text="'$' + tx.amount"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="bg-gray-800 border border-white/5 rounded-3xl p-6">
                                <h3 class="text-lg font-bold mb-6 text-purple-400">Payout Destination</h3>
                                <div class="space-y-4">
                                    <div class="p-4 bg-black/20 border border-white/5 rounded-2xl">
                                        <label class="text-[10px] text-gray-500 uppercase">Bank Name</label>
                                        <p class="font-medium" x-text="bank.name || 'Not Linked'"></p>
                                    </div>
                                    <div class="p-4 bg-black/20 border border-white/5 rounded-2xl">
                                        <label class="text-[10px] text-gray-500 uppercase">Account Number</label>
                                        <p class="font-mono text-sm" x-text="bank.account || '•••• •••• ••••'"></p>
                                    </div>
                                    <button class="w-full border border-white/10 hover:bg-white/5 py-3 rounded-xl text-sm transition-all">
                                        Update Banking Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                function dashboard() {
                    return {
                        loading: false,
                        withdrawAmount: 0,
                        // PLACEHOLDER DATA (This would normally come from your PHP API)
                        stats: [
                            { label: 'Gross Revenue', value: '$12,450.00', percentage: '14%', trendUp: true },
                            { label: 'Avg Order Value', value: '$84.20', percentage: '2%', trendUp: false },
                            { label: 'Total Payouts', value: '$8,100.00', percentage: '8%', trendUp: true },
                            { label: 'Success Rate', value: '99.2%', percentage: '0.4%', trendUp: true },
                        ],
                        wallet: { balance: 4350.25 },
                        bank: { name: 'Chase Business High-Yield', account: 'XXXX-9901' },
                        transactions: [
                            { id: 'TXN-99201', status: 'Completed', method: 'Bank Transfer', amount: '1,200.00' },
                            { id: 'TXN-99198', status: 'Pending', method: 'Direct Deposit', amount: '450.00' },
                            { id: 'TXN-99150', status: 'Completed', method: 'Bank Transfer', amount: '80.00' },
                        ],

                        init() {
                            this.renderChart();
                        },

                        renderChart() {
                            const ctx = document.getElementById('revenueChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                                    datasets: [{
                                        label: 'Revenue',
                                        data: [1200, 1900, 1500, 2500, 2200, 3100, 2800],
                                        borderColor: '#a855f7',
                                        tension: 0.4,
                                        fill: true,
                                        backgroundColor: 'rgba(168, 85, 247, 0.1)'
                                    }]
                                },
                                options: { 
                                    maintainAspectRatio: false, 
                                    plugins: { legend: { display: false } },
                                    scales: { y: { display: false }, x: { grid: { display: false } } }
                                }
                            });
                        },

                        async submitWithdrawal() {
                            this.loading = true;
                            // API CALL LOGIC:
                            // const response = await fetch('/api/v1/payments.php', {
                            //    method: 'POST',
                            //    body: JSON.stringify({ action: 'withdraw', amount: this.withdrawAmount })
                            // });
                            
                            setTimeout(() => {
                                alert(`Success! $${this.withdrawAmount} sent to your bank.`);
                                this.wallet.balance -= this.withdrawAmount;
                                this.withdrawAmount = 0;
                                this.loading = false;
                            }, 1500);
                        }
                    }
                }
                </script>
            </main>
        </div>
    