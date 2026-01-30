    <!-- DASHBOARD SECTION (Hidden by default) -->
    <div id="dashboard-container" class="container">
        <?php @include_once "header.php"; ?> 

    <main>
        <div>
            
            <h3 class="grid-layout">Your Website</h3>
            <div class="grid-layout">
                <div class="card">
                    <h3>Admin Access</h3>
                    <div class="balance">Control Panel</div>
                    <p class="subtext">Control your sites activity</p>
                    <button onclick="window.location='/vm-admin/<?php echo slugify(__DOMAIN__); ?>/'" class="btn btn-primary">Login to Panel</button>
                </div>

                <div class="card">
                    <h3>Website Theme</h3>
                    <div class="balance"><?php echo __WEBSITE_THEME__; ?></div>
                    <p class="subtext">The active theme on the website agency</p>
                    <button class="btn btn-primary">Change Theme</button>
                </div>

                <div class="card status-card">
                    <h3><i class="fas fa-store"></i> Website Status</h3>
                    <div class="balance"><?php echo __DOMAIN__; ?></div>
                    <div id="store-status-indicator" class="status-badge active">Active</div>
                </div>


                <div>
                </div>
            </div>

            <h3 class="grid-layout">More Controls</h3>
            <div class="grid-layout">
                <div class="card">
                    <div class="balance">Paymenents</div>
                    <p class="subtext">Manage Your Webstore payments</p>
                    <button class="btn btn-primary">Manage Payments</button>
                </div>

                <div></div>

                <div></div>

            </div>



            <div style="margin:0 auto; display:block; padding:2rem; max-width: 1200px;">
                <div class="card">
                <h3><i class="fas fa-wallet"></i> Your Webstore</h3>
                <div>
                    <iframe src="<?php echo __WEBSITE_URL__; ?>"
                    style="height: 75vh; width: 100%; border: none; border-radius:10px; "
                    ></iframe>
                </div>
                <br>
                <div class="input-group">
                    <label>Webstore Frame Link</label>
                    <input value="<?php echo __WEBSITE_FRAME__; ?>" type="text">
                </div>
                </div>
            </div>
        </div>

    </main>

    </div>
