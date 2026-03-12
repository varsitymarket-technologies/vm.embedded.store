<?php 
#   TITLE   : Pages Dashboard (Modern Refactor)   
#   DESC    : Optimized UI for mobile and desktop consistency.
#   VERSION : 1.1.0
?>

<style>
    :root {
        --bg-color: #0d0d0d;
        --card-bg: #1a1a1a;
        --accent: #8a2be2;
        --text-main: #ffffff;
        --text-muted: #a0a0a0;
        --border-radius: 12px;
        --transition: all 0.3s ease;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: 'Inter', -apple-system, sans-serif;
        margin: 0;
        padding: 0;
    }

    .dashboard-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.5rem;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 2rem 0 1rem;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Slick Grid System */
    .grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .glass-card {
        background: var(--card-bg);
        border: 1px solid #2d2d2d;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .glass-card:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
    }

    .card-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }

    .card-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .subtext {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
        line-height: 1.4;
    }

    /* Buttons */
    .btn-action {
        background: var(--accent);
        color: white;
        border: none;
        padding: 0.8rem 1.2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        transition: var(--transition);
        width: 100%;
    }

    .btn-action:hover {
        filter: brightness(1.2);
        box-shadow: 0 4px 15px rgba(138, 43, 226, 0.3);
    }

    /* Live Preview Section */
    .preview-container {
        background: var(--card-bg);
        border-radius: var(--border-radius);
        overflow: hidden;
        border: 1px solid #2d2d2d;
        margin: 2rem 0;
    }

    .preview-header {
        padding: 1rem;
        background: #252525;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dot { width: 10px; height: 10px; border-radius: 50%; background: #444; }

    /* Badge */
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 800;
        background: rgba(0, 255, 127, 0.1);
        color: #00ff7f;
        border: 1px solid rgba(0, 255, 127, 0.3);
    }

    @media (max-width: 768px) {
        .grid-container {
            grid-template-columns: 1fr;
        }
    }
</style>
    <?php @include_once "header.php"; ?> 
<main class="grid-layout"> 
    <div>
        <h3 class="section-title">Your Website</h3>
        <div class="grid-container">
            <div class="glass-card">
                <div>
                    <div class="card-label">Admin Access</div>
                    <div class="card-value">Control Panel</div>
                    <p class="subtext">Manage site activity and user settings.</p>
                </div>
                <button onclick="window.location='/vm-admin/<?php echo (__DOMAIN__); ?>/home'" class="btn-action">Open Panel</button>
            </div>

            <div class="glass-card">
                <div>
                    <div class="card-label">Website Theme</div>
                    <div class="card-value"><?php echo __WEBSITE_THEME__; ?></div>
                    <p class="subtext">Customizing the look of your digital storefront.</p>
                </div>
                <button onclick="window.location='/theme/'" class="btn-action">Change Theme</button>
            </div>

            <div class="glass-card">
                <div>
                    <div class="card-label">Website Status</div>
                    <div class="card-value"><?php echo __DOMAIN__; ?></div>
                    <div class="status-badge">ACTIVE</div>
                </div>
                <p class="subtext" style="margin-top:1.5rem;">Your store is live and accepting traffic.</p>
            </div>
        </div>

        <h3 class="section-title">Live Storefront</h3>
        <div class="preview-container">
            <div class="preview-header">
                <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                <span style="font-size: 12px; color: var(--text-muted); margin-left: 10px;"><?php echo __DOMAIN__; ?></span>
            </div>
            <iframe src="<?php echo __WEBSITE_URL__; ?>" style="height: 60vh; width: 100%; border: none;" frameborder="0"></iframe>
        </div>

    </div>
</main>