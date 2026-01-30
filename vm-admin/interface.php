<?php 
#   TITLE   : Admin Interface   
#   DESC    : The Interface handling the Admin GUI 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varsity Market | Embedded Mini Store Admin</title>
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
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <link href="/assets/favicon.png" rel="icon">
    <link href="/assets/favicon.png" rel="apple-touch-icon">
</head>
<body class="bg-gray-900 text-white font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="absolute z-20 h-full w-64 -translate-x-full transform bg-gray-800 transition-transform duration-300 ease-in-out md:relative md:translate-x-0 border-r border-white/10">
            <nav class="mt-4 px-2">
                <div style="display: flex; align-items: center; justify-content: flex-end;">
                    <button id="sidebarClose" class="md:hidden text-gray-400 hover:text-white">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>

                <div class="text-xl font-bold text-purple-500 flex items-center gap-2">
                    <img src="/assets/favicon.png" style="height: 100%; margin: 1rem auto; display: block; width: 8rem;">
                </div>

                <a href="../home" class="group flex items-center rounded-lg bg-purple-600 px-4 py-2 text-white">
                    <i class="bi bi-grid-fill mr-3"></i>
                    <span>Dashboard</span>
                </a>

                <a href="analytics" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-bar-chart-line-fill mr-3"></i>
                    <span>Analytics</span>
                </a>
                
                <a href="users" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-people-fill mr-3"></i>
                    <span>Users</span>
                </a>
                <a href="categories" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-tags-fill mr-3"></i>
                    <span>Categories</span>
                </a>
                <a href="products" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-box-seam-fill mr-3"></i>
                    <span>Products</span>
                </a>
                <a href="discounts" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-percent mr-3"></i>
                    <span>Discounts</span>
                </a>
                <a href="sales" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-tag-fill mr-3"></i>
                    <span>Sales</span>
                </a>
                <a href="orders" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-cart-fill mr-3"></i>
                    <span>Orders</span>
                </a>
                <a href="builder" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-palette-fill mr-3"></i>
                    <span>Builder</span>
                </a>
                <a href="settings" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-gear-fill mr-3"></i>
                    <span>Settings</span>
                </a>
                
            </nav>
        </aside>

        <?php @include_once "routes.php"; ?>

    </div>
    <script src="admin.js"></script>
</body>
</html>