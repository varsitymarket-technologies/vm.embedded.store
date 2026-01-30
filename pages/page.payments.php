<div id="dashboard-container" class="container">
    <?php @include_once "header.php"; ?> 

    <h3 class="grid-layout">Store Wallet</h3>
        <div class="grid-layout">
            
            <div class="card withdraw-card">
                <h3><i class="fas fa-money-bill-wave"></i>Banking Account</h3>
                <form id="withdraw-form">
                    <div class="input-group">
                        <label>Account Number</label>
                        <input type="text" value="<?php echo __BANKING_ACCOUNT_NUMBER__; ?>">
                        <label>Account Type</label>
                        <input type="text" value="<?php echo __BANKING_ACCOUNT_TYPE__; ?>">
                        <label>Bank</label>
                        <input type="text" value="<?php echo __BANKING_SERVICE__; ?>">
                    </div>
                </form>
                <div id="withdraw-message"></div>
            </div>

            <div class="card">
                <h3>Wallet Balance</h3>
                <div class="balance"><?php echo __CURRENCY_SIGN__.__WALLET_AMOUNT__; ?></div>
                <p class="subtext">The available balance remaining on your store wallet</p>
                <br>
                <h3>Admin Fees:</h3>
                <div class="balance"><?php echo __WALLET_PERCENTAGE__; ?>% </div>
                <p class="subtext">The service fee for using the plugin services.</p>
                
            </div>

        </div>


    <div class="lg:col-span-2">
    <h3 class="text-lg font-bold mb-6 text-white grid-layout">Transaction History</h3>
                
    <div class="grid-layout">
    <div class="bg-zinc-900 border border-zinc-800" style="border-radius:10px;">
        <table class="w-full text-left">
            <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold tracking-widest border-b border-zinc-800">
                <tr>
                    <th class="p-4">Reference</th>
                    <th class="p-4">Type</th>
                    <th class="p-4 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                                                    

            <tr>
                <td>Som</td>
                <td>Som</td>
                <td>Som</td>
            </tr>
            </tbody>
        </table>
                                            
        <div class="p-12 text-center text-zinc-600 text-sm">No transaction records found.</div>
        </div>
    </div>
    </div>
</div>