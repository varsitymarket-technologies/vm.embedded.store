<?php 
#   TITLE   : Pages Dashboard    
#   DESC    : The dashboard page of the application. 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30
?>

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
                    <button onclick="window.location='/vm-admin/<?php echo slugify(__DOMAIN__); ?>/home'" class="btn btn-primary">Login to Panel</button>
                </div>

                <div class="card">
                    <h3>Website Theme</h3>
                    <div class="balance"><?php echo __WEBSITE_THEME__; ?></div>
                    <p class="subtext">The active theme on the website agency</p>
                    <button onclick="window.location='/theme/'" class="btn btn-primary">Change Theme</button>
                </div>

                <div>
                    <div class="card status-card">
                        <h3><i class="fas fa-store"></i> Website Status</h3>
                        <div class="balance"><?php echo __DOMAIN__; ?></div>
                        <div id="store-status-indicator" class="status-badge active">Active</div>
                    </div>
                </div>
            </div>


            <div style="margin:0 auto; display:block; padding:2rem; max-width: 1200px;">
                <div class="card">
                <h3><i class="fas fa-wallet"></i> Your Webstore</h3>
                <div>
                    <iframe src="<?php echo __WEBSITE_URL__; ?>"
                    style="height: 75vh; width: 100%; border: none; border-radius:10px; " <?php // sandbox="" ?> 
                    frameborder="0"
                    ></iframe>
                </div>
                </div>
            </div>

            <h3 class="grid-layout">More Controls</h3>
            <div class="grid-layout">
                <div class="card">
                    <div class="balance">Paymenents</div>
                    <p class="subtext">Manage Your Webstore payments</p>
                    <button onclick="window.location='/payments/'" class="btn btn-primary">Manage Payments</button>
                </div>

                <div></div>

                <div></div>

            </div>


            <h3 class="grid-layout">Embed Your Store</h3>
            <div class="grid-layout">
                <div class="card">
                    <div class="balance">Source Page</div>
                    <p class="subtext">Get a source copy</p>
                    <button onclick="window.location='/payments/'" class="btn btn-primary">Download File</button>
                </div>

                <div class="card">
                    <div class="balance">Source Snippet</div>
                    <p class="subtext">Copy Code Script to paste into your embedded snippet</p>
                    <button onclick="window.location='/payments/'" class="btn btn-primary">Copy Code</button>
                </div>

                <div class="card">
                    <div class="balance">Source Link</div>
                    <p class="subtext">Get Access Link To Paste to your Website</p>
                    <button onclick="window.location='/payments/'" class="btn btn-primary">Copy Link</button>
                </div>

            </div>

        </div>

    </main>

    </div>
