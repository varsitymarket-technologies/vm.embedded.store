<?php
#   TITLE   : Admin Interface
#   DESC    : The Interface handling the Admin GUI
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/01/30
ob_start();
?>

<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embedded Store Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            900: '#101010',
                            800: '#1a1a1a',
                            700: '#222323',
                        },
                        purple: {
                            500: '#7a1aab',
                            600: '#7a1aab',
                        },
                        shopifyBg: '#1a1a1a',      /* Shopify Admin Dark base background */
                        shopifyCard: '#202123',    /* Surface containers */
                        shopifyBorder: '#303134',  /* Subtle, crisp dividers */
                        shopifyText: '#e3e3e3',    /* Primary text */
                        shopifySecondary: '#a9a9a9',/* Secondary labels */
                        shopifyGreen: '#008060',    /* Shopify Core Green brand asset */
                        shopifyGreenHover: '#006e52'
                    }
                }
            }
        }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <link href="/assets/favicon.png" rel="icon">
    <link href="/assets/icon-192.png" rel="apple-touch-icon">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#7a1aab">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="VarsityMarket">

    <style>
        /* Modern browsers — Chrome 121+, Edge 121+, Firefox, Safari 18.2+ */
* {
  scrollbar-width: 8px;
  scrollbar-color:#64606b #09090b00;
}

/* Legacy WebKit — older Chrome/Edge/Safari, radius, borders & shadows */
*::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

*::-webkit-scrollbar-track {
  background-color: #1e1b4b;
  border-radius: 4px;
  padding: 1px;
}

*::-webkit-scrollbar-track:hover {
  background-color: #312e81;
}

*::-webkit-scrollbar-track:active {
  background-color: #3730a3;
}

*::-webkit-scrollbar-thumb {
  background-color: #7c3aed;
  border-radius: 4px;
  min-height: 30px;
  box-shadow: 0 0 6px rgba(129,140,248, 0.60);
}

*::-webkit-scrollbar-thumb:hover {
  background-color: #818cf8;
}

*::-webkit-scrollbar-thumb:active {
  background-color: #a5b4fc;
}

*::-webkit-scrollbar-button {
  display: none;
  width: 0;
  height: 0;
}
</style>
</head>

<body class="bg-gray-900 text-white font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php
            $admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
            $current_page = ex(3) ?: 'home';
            $store_name = website_data('name') ?: 'My Store';
            $store_domain = __DOMAIN__ ?? '';
            $store_url = __WEBSITE_FRAME__ ?? '';

            // Helper for nav link classes
            function nav_cls($page, $current) {
                $base = 'group mt-1 flex items-center rounded-lg px-4 py-2 transition-colors';
                return $page === $current
                    ? $base . ' bg-purple-600 text-white'
                    : $base . ' text-gray-400 hover:bg-gray-700 hover:text-white';
            }

            // Lightweight badge counts
            $pending_orders = 0;
            try {
                $site_db = __DB_WEBSITE__;
                if ($site_db) {
                    $oc = $site_db->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
                    $pending_orders = (int)($oc[0]['cnt'] ?? 0);
                }
            } catch (\Throwable $e) {}
        ?>
        <aside id="sidebar" style="overflow:auto;"
            class="absolute z-20 h-full w-64 -translate-x-full transform bg-gray-800 transition-transform duration-300 ease-in-out md:relative md:translate-x-0 border-r border-white/10">
            <div class="flex flex-col h-full">
            <nav class="mt-4 px-2 flex-1">
                <div style="display: flex; align-items: center; justify-content: flex-end;">
                    <button id="sidebarClose" class="md:hidden text-gray-400 hover:text-white">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>

                <!-- Store Identity -->
                <div class="px-3 pb-4 mb-3 border-b border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-purple-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-shop text-white text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-white text-sm font-bold truncate"><?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="text-gray-500 text-xs truncate"><?php echo htmlspecialchars($store_domain, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($store_url)): ?>
                    <a href="<?php echo htmlspecialchars($store_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"
                        class="mt-3 flex items-center justify-center gap-2 rounded-lg border border-white/10 px-3 py-1.5 text-xs text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>View Store</span>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Main -->
                <a href="/home/" class="<?php echo nav_cls('dashboard', $current_page); ?>">
                    <i class="bi bi-house-fill mr-3"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?php echo $admin_base; ?>" class="<?php echo nav_cls('home', $current_page); ?>">
                    <i class="bi bi-grid-1x2-fill mr-3"></i>
                    <span>Overview</span>
                </a>
                <?php if (isset($_SERVER['__AI_EXTENSION__'])): ?> 
                <?php if ($_SERVER['__AI_EXTENSION__']): ?>
                    <a href="<?php echo $admin_base; ?>agent" class="<?php echo nav_cls('agent', $current_page); ?>">
                        <i class="bi bi-robot mr-3"></i>
                        <span>AI Agent</span>
                    </a>
                <?php endif; endif; ?>

                <a href="<?php echo $admin_base; ?>analytics" class="<?php echo nav_cls('analytics', $current_page); ?>">
                    <i class="bi bi-graph-up mr-3"></i>
                    <span>Analytics</span>
                </a>

                <!-- Catalog -->
                <div class="sidebar-section" data-section="catalog">
                    <button onclick="toggleSection('catalog')" class="w-full flex items-center justify-between mt-4 mb-1 px-3 cursor-pointer group">
                        <span style="font-size: 9px;" class="uppercase tracking-widest text-gray-500 group-hover:text-gray-300 transition-colors">Catalog</span>
                        <i class="bi bi-chevron-down text-gray-600 text-xs transition-transform" id="chevron-catalog"></i>
                    </button>
                    <div id="section-catalog">
                        <a href="<?php echo $admin_base; ?>products" class="<?php echo nav_cls('products', $current_page); ?>">
                            <i class="bi bi-box-seam-fill mr-3"></i>
                            <span>Products</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>categories" class="<?php echo nav_cls('categories', $current_page); ?>">
                            <i class="bi bi-tags-fill mr-3"></i>
                            <span>Categories</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>discounts" class="<?php echo nav_cls('discounts', $current_page); ?>">
                            <i class="bi bi-percent mr-3"></i>
                            <span>Discounts</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>sales" class="<?php echo nav_cls('sales', $current_page); ?>">
                            <i class="bi bi-lightning-fill mr-3"></i>
                            <span>Flash Sales</span>
                        </a>
                    </div>
                </div>

                <!-- Orders & Fulfillment -->
                <div class="sidebar-section" data-section="orders">
                    <button onclick="toggleSection('orders')" class="w-full flex items-center justify-between mt-4 mb-1 px-3 cursor-pointer group">
                        <span style="font-size: 9px;" class="uppercase tracking-widest text-gray-500 group-hover:text-gray-300 transition-colors">Orders & Fulfillment</span>
                        <i class="bi bi-chevron-down text-gray-600 text-xs transition-transform" id="chevron-orders"></i>
                    </button>
                    <div id="section-orders">
                        <a href="<?php echo $admin_base; ?>orders" class="<?php echo nav_cls('orders', $current_page); ?> justify-between">
                            <div class="flex items-center">
                                <i class="bi bi-receipt mr-3"></i>
                                <span>Orders</span>
                            </div>
                            <?php if ($pending_orders > 0): ?>
                            <span class="bg-red-500 text-white text-xs font-bold rounded-full h-5 min-w-[20px] flex items-center justify-center px-1.5"><?php echo $pending_orders > 99 ? '99+' : $pending_orders; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo $admin_base; ?>payments" class="<?php echo nav_cls('payments', $current_page); ?>">
                            <i class="bi bi-wallet2 mr-3"></i>
                            <span>Payments</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>delivery" class="<?php echo nav_cls('delivery', $current_page); ?>">
                            <i class="bi bi-truck mr-3"></i>
                            <span>Delivery</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>logistics" class="<?php echo nav_cls('logistics', $current_page); ?>">
                            <i class="bi bi-globe-americas mr-3"></i>
                            <span>Logistics</span>
                        </a>
                    </div>
                </div>

                <!-- Website -->
                <div class="sidebar-section" data-section="website">
                    <button onclick="toggleSection('website')" class="w-full flex items-center justify-between mt-4 mb-1 px-3 cursor-pointer group">
                        <span style="font-size: 9px;" class="uppercase tracking-widest text-gray-500 group-hover:text-gray-300 transition-colors">Website</span>
                        <i class="bi bi-chevron-down text-gray-600 text-xs transition-transform" id="chevron-website"></i>
                    </button>
                    <div id="section-website">
                        <a href="<?php echo $admin_base; ?>theme" class="<?php echo nav_cls('theme', $current_page); ?>">
                            <i class="bi bi-palette-fill mr-3"></i>
                            <span>Themes</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>builder" class="<?php echo nav_cls('builder', $current_page); ?>">
                            <i class="bi bi-layout-wtf mr-3"></i>
                            <span>Page Builder</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>publish" class="<?php echo nav_cls('deploy', $current_page); ?>">
                            <i class="bi bi-rocket-takeoff-fill mr-3"></i>
                            <span>Publish</span>
                        </a>
                    </div>
                </div>

                <!-- System -->
                <div class="sidebar-section" data-section="system">
                    <button onclick="toggleSection('system')" class="w-full flex items-center justify-between mt-4 mb-1 px-3 cursor-pointer group">
                        <span style="font-size: 9px;" class="uppercase tracking-widest text-gray-500 group-hover:text-gray-300 transition-colors">System</span>
                        <i class="bi bi-chevron-down text-gray-600 text-xs transition-transform" id="chevron-system"></i>
                    </button>
                    <div id="section-system">
                        <a href="<?php echo $admin_base; ?>users" class="<?php echo nav_cls('users', $current_page); ?>">
                            <i class="bi bi-people-fill mr-3"></i>
                            <span>Customers</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>forms" class="<?php echo nav_cls('forms', $current_page); ?>">
                            <i class="bi bi-ui-checks-grid mr-3"></i>
                            <span>Forms</span>
                        </a>
                        <a href="<?php echo $admin_base; ?>settings" class="<?php echo nav_cls('settings', $current_page); ?>">
                            <i class="bi bi-gear-fill mr-3"></i>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>

            </nav>

            <!-- User Footer -->
            <div class="px-3 py-4 border-t border-white/5">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full bg-purple-600 flex items-center justify-center shrink-0">
                        <span class="text-white text-xs font-bold"><?php echo strtoupper(substr(__USERNAME__ ?? 'U', 0, 1)); ?></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-sm font-medium truncate"><?php echo htmlspecialchars(__USERNAME__ ?? 'User', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <a href="/logout.php" title="Sign out" class="text-gray-500 hover:text-red-400 transition-colors">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
            </div>
        </aside>

        <style>
            .menu-overlay {
                height: 100%;
                width: 0;
                position: fixed;
                z-index: 100;
                top: 0;
                left: 0;
                background-color: rgb(0, 0, 0);
                background-color: rgba(0, 0, 0, 0.9);
                overflow-x: hidden;
                transition: 0.5s;
                max-width: 60vh;
                opacity: 1;
                pointer-events: all;
            }

            .menu-overlay a {
                padding: 8px;
                text-decoration: none;
                font-size: 2vh;
                color: #818181;
                display: block;
                transition: 0.3s;
            }

            .overlay-content {
                position: relative;
                top: 5%;
                width: 100%;
                text-align: center;
                margin-top: 30px;
            }

            .thththththt {
                font-size: 2rem;
            }

            .closebtn {
                font-size: 3rem !important;
                display: flex !important;
                margin: 10px !important;
                padding: 0;
                height: auto;
                /* position: absolute; */
                /* top: 2rem; */
                /* left: 3rem; */
                flex-direction: row-reverse !important;
            }

            .sesedesedwsedwdd {
                text-align: start;
                margin: 0.6rem 0 0 3rem;
            }

            .sesedesedwsedwdd i {
                font-size: 1.1rem !important;
            }

            .anch_item {
                font-size: 0.7rem !important;
                text-align: left !important;
                margin: 0.5rem 0 0 3rem !important;
                padding: 0;
            }
        </style>
        <script>
            function open_menu() {
                document.getElementById("myNav").style.width = "100%";
            }

            function close_menu() {
                document.getElementById("myNav").style.width = "0%";
            }

            function reload_page(url) {
                if (url == "#") {
                    location.reload();
                } else {
                    location.href = url;
                }
            }

            // Collapsible sidebar sections with localStorage persistence
            function toggleSection(name) {
                var section = document.getElementById('section-' + name);
                var chevron = document.getElementById('chevron-' + name);
                if (!section) return;
                var collapsed = JSON.parse(localStorage.getItem('sidebar_collapsed') || '{}');
                if (section.style.display === 'none') {
                    section.style.display = '';
                    if (chevron) chevron.style.transform = '';
                    delete collapsed[name];
                } else {
                    section.style.display = 'none';
                    if (chevron) chevron.style.transform = 'rotate(-90deg)';
                    collapsed[name] = true;
                }
                localStorage.setItem('sidebar_collapsed', JSON.stringify(collapsed));
            }

            // Restore collapsed state on load
            document.addEventListener('DOMContentLoaded', function() {
                var collapsed = JSON.parse(localStorage.getItem('sidebar_collapsed') || '{}');
                Object.keys(collapsed).forEach(function(name) {
                    var section = document.getElementById('section-' + name);
                    var chevron = document.getElementById('chevron-' + name);
                    if (section) section.style.display = 'none';
                    if (chevron) chevron.style.transform = 'rotate(-90deg)';
                });
            });
        </script>
        <div id="myNav" class="menu-overlay" style="width: 0%;">
            <a class="closebtn" onclick="close_menu();">×</a>
            <div class="overlay-content">

                <div class="px-8 pb-4 mb-3 border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-purple-600 flex items-center justify-center shrink-0">
                            <i class="bi bi-shop text-white text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-white text-sm font-bold truncate"><?php echo htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="text-gray-500 text-xs truncate"><?php echo htmlspecialchars($store_domain, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                    <br>
                    <?php if (!empty($store_url)): ?>
                    <a href="<?php echo htmlspecialchars($store_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"
                        class="mt-3 flex items-center justify-center gap-2 rounded-lg border border-white/10 px-3 py-1.5 text-xs text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>View Store</span>
                    </a>
                    <?php endif; ?>
                </div>

                <a></a>

                <a href="/home/" class="sesedesedwsedwdd">
                    <i class="bi bi-house-fill mr-3"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?php echo $admin_base; ?>home" class="sesedesedwsedwdd">
                    <i class="bi bi-grid-1x2-fill mr-3"></i>
                    <span>Overview</span>
                </a>

                <a href="<?php echo $admin_base; ?>analytics" class="sesedesedwsedwdd">
                    <i class="bi bi-graph-up mr-3"></i>
                    <span>Analytics</span>
                </a>

                <a class="anch_item">
                    <span>Catalog</span>
                </a>

                <a href="<?php echo $admin_base; ?>products" class="sesedesedwsedwdd">
                    <i class="bi bi-box-seam-fill mr-3"></i>
                    <span>Products</span>
                </a>
                <a href="<?php echo $admin_base; ?>categories" class="sesedesedwsedwdd">
                    <i class="bi bi-tags-fill mr-3"></i>
                    <span>Categories</span>
                </a>
                <a href="<?php echo $admin_base; ?>discounts" class="sesedesedwsedwdd">
                    <i class="bi bi-percent mr-3"></i>
                    <span>Discounts</span>
                </a>
                <a href="<?php echo $admin_base; ?>sales" class="sesedesedwsedwdd">
                    <i class="bi bi-lightning-fill mr-3"></i>
                    <span>Flash Sales</span>
                </a>

                <a class="anch_item">
                    <span>Orders & Fulfillment</span>
                </a>

                <a href="<?php echo $admin_base; ?>orders" class="sesedesedwsedwdd">
                    <i class="bi bi-receipt mr-3"></i>
                    <span>Orders<?php if ($pending_orders > 0) echo ' <span style="background:#7a1aab;padding:1px 6px;border-radius:99px;font-size:0.6rem;margin-left:4px;">'.$pending_orders.'</span>'; ?></span>
                </a>
                <a href="<?php echo $admin_base; ?>payments" class="sesedesedwsedwdd">
                    <i class="bi bi-wallet2 mr-3"></i>
                    <span>Payments</span>
                </a>
                <a href="<?php echo $admin_base; ?>delivery" class="sesedesedwsedwdd">
                    <i class="bi bi-truck mr-3"></i>
                    <span>Delivery</span>
                </a>
                <a href="<?php echo $admin_base; ?>logistics" class="sesedesedwsedwdd">
                    <i class="bi bi-globe-americas mr-3"></i>
                    <span>Logistics</span>
                </a>

                <a class="anch_item">
                    <span>Website</span>
                </a>
                <a href="<?php echo $admin_base; ?>theme" class="sesedesedwsedwdd">
                    <i class="bi bi-palette-fill mr-3"></i>
                    <span>Themes</span>
                </a>
                <a href="<?php echo $admin_base; ?>builder" class="sesedesedwsedwdd">
                    <i class="bi bi-layout-wtf mr-3"></i>
                    <span>Page Builder</span>
                </a>
                <a href="<?php echo $admin_base; ?>publish" class="sesedesedwsedwdd">
                    <i class="bi bi-rocket-takeoff-fill mr-3"></i>
                    <span>Publish</span>
                </a>

                <a class="anch_item">
                    <span>System</span>
                </a>
                <a href="<?php echo $admin_base; ?>users" class="sesedesedwsedwdd">
                    <i class="bi bi-people-fill mr-3"></i>
                    <span>Customers</span>
                </a>
                <a href="<?php echo $admin_base; ?>forms" class="sesedesedwsedwdd">
                    <i class="bi bi-ui-checks-grid mr-3"></i>
                    <span>Forms</span>
                </a>
                <a href="<?php echo $admin_base; ?>settings" class="sesedesedwsedwdd">
                    <i class="bi bi-gear-fill mr-3"></i>
                    <span>Settings</span>
                </a>

                <a style="margin:1rem"></a>
                <br>
                <div style="padding: 1rem 2rem 8rem; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width:28px;height:28px;border-radius:50%;background:#7a1aab;display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:0.65rem;font-weight:700;color:#fff;"><?php echo strtoupper(substr(__USERNAME__ ?? 'U', 0, 1)); ?></span>
                    </div>
                    <span style="font-size:0.75rem;color:#999;"><?php echo htmlspecialchars(__USERNAME__ ?? 'User', ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="/logout.php" style="margin-left:auto;font-size:0.75rem;color:#666;">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>

            </div>
        </div>

        <?php @include_once "routes.php"; ?>

    </div>
    <script src="admin.js"></script>
    <script src="/assets/pwa.js"></script>
</body>

</html>