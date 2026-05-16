<?php
#   TITLE   : Pages Dashboard (Modern Refactor)
#   DESC    : Optimized UI for mobile and desktop consistency.
#   VERSION : 1.3.0

$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$store_name = website_data('name') ?: 'My Store';
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

    /* Carousel */
    .carousel-wrapper {
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius);
        border: 1px solid #2d2d2d;
        margin-bottom: 2rem;
    }

    .carousel-track {
        display: flex;
        transition: transform 0.5s ease;
    }

    .carousel-slide {
        min-width: 100%;
        min-height: 220px;
        padding: 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0.75rem;
        position: relative;
        background-size: cover;
        background-position: center;
    }

    .carousel-slide::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.2) 100%);
        z-index: 1;
    }

    .carousel-slide > * {
        position: relative;
        z-index: 2;
    }

    .carousel-slide .slide-badge {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .carousel-slide h3 {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
        color: #fff;
        text-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }

    .carousel-slide p {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.8);
        line-height: 1.5;
        margin: 0;
        max-width: 600px;
    }

    .carousel-slide .slide-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #fff;
        text-decoration: none;
        transition: var(--transition);
        width: fit-content;
        backdrop-filter: blur(4px);
    }

    .carousel-slide .slide-cta:hover {
        filter: brightness(1.2);
        transform: translateX(3px);
    }

    .carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.5);
        border: none;
        color: #fff;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        backdrop-filter: blur(4px);
    }

    .carousel-nav:hover { background: rgba(0,0,0,0.8); }
    .carousel-nav.prev { left: 12px; }
    .carousel-nav.next { right: 12px; }

    .carousel-dots {
        position: absolute;
        bottom: 14px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
    }

    .carousel-dots button {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.3);
        cursor: pointer;
        transition: var(--transition);
    }

    .carousel-dots button.active {
        background: #fff;
        transform: scale(1.3);
    }

    @media (max-width: 768px) {
        .grid-container {
            grid-template-columns: 1fr;
        }
        .carousel-slide {
            padding: 1.5rem;
        }
        .carousel-slide h3 {
            font-size: 1.1rem;
        }
    }
</style>
    <?php @include_once "header.php"; ?>
<main class="grid-layout">
    <div class="dashboard-wrapper">

        <!-- What's New & Tips Carousel -->
        <div class="carousel-wrapper" id="carousel-wrapper">
            <div class="carousel-track" id="carousel-track">

                <!-- Slide 1: Welcome -->
                <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&q=80');">
                    <span class="slide-badge" style="color: #a78bfa;">Getting Started</span>
                    <h3>Welcome to <?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>Add products, pick a theme, connect payments, and publish. Your customers are waiting.</p>
                    <a href="<?php echo $admin_base; ?>products" class="slide-cta" style="background: rgba(124,58,237,0.9);">
                        Add Products <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Slide 2: New Feature - Analytics -->
                <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&q=80');">
                    <span class="slide-badge" style="color: #34d399;">New Feature</span>
                    <h3>Real-Time Analytics Dashboard</h3>
                    <p>Track page views, unique visitors, referrers, and device breakdowns with a lightweight tag.</p>
                    <a href="<?php echo $admin_base; ?>analytics" class="slide-cta" style="background: rgba(5,150,105,0.9);">
                        View Analytics <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Slide 3: Tip - Product Photography -->
                <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200&q=80');">
                    <span class="slide-badge" style="color: #f472b6;">Pro Tip</span>
                    <h3>Great Photos Sell Products</h3>
                    <p>Use natural lighting, clean backgrounds, and multiple angles. Customers buy what they can see clearly.</p>
                    <a href="<?php echo $admin_base; ?>products" class="slide-cta" style="background: rgba(219,39,119,0.9);">
                        Edit Products <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Slide 4: New Feature - API & SDK -->
                <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1200&q=80');">
                    <span class="slide-badge" style="color: #60a5fa;">New Feature</span>
                    <h3>Public Store API & JavaScript SDK</h3>
                    <p>Embed your products anywhere or build custom integrations. Full cart and checkout support.</p>
                    <a href="<?php echo $admin_base; ?>settings?tab=dev" class="slide-cta" style="background: rgba(37,99,235,0.9);">
                        Developer Settings <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Slide 5: Growth Tip -->
                <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1607082349566-187342175e2f?w=1200&q=80');">
                    <span class="slide-badge" style="color: #fbbf24;">Growth Tip</span>
                    <h3>Boost Conversions With Urgency</h3>
                    <p>Flash sales and limited-time discounts create urgency. Pair with free delivery thresholds for maximum impact.</p>
                    <a href="<?php echo $admin_base; ?>discounts" class="slide-cta" style="background: rgba(217,119,6,0.9);">
                        Create Discount <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

            </div>

            <button class="carousel-nav prev" onclick="carouselPrev()"><i class="bi bi-chevron-left"></i></button>
            <button class="carousel-nav next" onclick="carouselNext()"><i class="bi bi-chevron-right"></i></button>

            <div class="carousel-dots" id="carousel-dots">
                <button class="active" onclick="carouselGo(0)"></button>
                <button onclick="carouselGo(1)"></button>
                <button onclick="carouselGo(2)"></button>
                <button onclick="carouselGo(3)"></button>
                <button onclick="carouselGo(4)"></button>
            </div>
        </div>

        <script>
        (function() {
            var current = 0, total = 5;
            var track = document.getElementById('carousel-track');
            var dots = document.getElementById('carousel-dots').children;
            var autoplay;

            function update() {
                track.style.transform = 'translateX(-' + (current * 100) + '%)';
                for (var i = 0; i < dots.length; i++) {
                    dots[i].className = i === current ? 'active' : '';
                }
            }

            window.carouselNext = function() { current = (current + 1) % total; update(); resetAuto(); };
            window.carouselPrev = function() { current = (current - 1 + total) % total; update(); resetAuto(); };
            window.carouselGo = function(i) { current = i; update(); resetAuto(); };

            function resetAuto() { clearInterval(autoplay); autoplay = setInterval(window.carouselNext, 8000); }
            resetAuto();

            // Swipe support for mobile
            var startX = 0;
            var wrapper = document.getElementById('carousel-wrapper');
            wrapper.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; });
            wrapper.addEventListener('touchend', function(e) {
                var diff = startX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) { diff > 0 ? window.carouselNext() : window.carouselPrev(); }
            });
        })();
        </script>

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
                    <div style="font-size:2.5em;" class="card-value"><?php echo __WEBSITE_THEME__; ?></div>
                    <p class="subtext">Customizing the look of your digital storefront.</p>
                </div>
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