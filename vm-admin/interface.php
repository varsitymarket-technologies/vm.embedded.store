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

                <a href="/../home/" class="group flex items-center rounded-lg bg-purple-600 px-4 py-2 text-white">
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
                    <span>Page Builder</span>
                </a>
                <a href="settings" class="group mt-1 flex items-center rounded-lg px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white transition-colors">
                    <i class="bi bi-gear-fill mr-3"></i>
                    <span>Settings</span>
                </a>
                
            </nav>
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
        </style>
        <script>
                            function open_menu() {
                                document.getElementById("myNav").style.width = "100%";
                            }

                            function close_menu() {
                                document.getElementById("myNav").style.width = "0%";
                            }

                            function reload_page(url){
                                if (url == "#"){
                                    location.reload(); 
                                }else{
                                    location.href = url; 
                                }
                            }
        </script>
        <div id="myNav" class="menu-overlay" style="width: 0%;">
                                <a class="closebtn" onclick="close_menu();">×</a>
                                <div class="overlay-content">
                                    <img onclick="reload_page('#')" src="/assets/favicon.png" style="width:20vh; height:auto; margin:auto; ">
                                    <a></a>

                                    <a href="/../home/" class="sesedesedwsedwdd">
                                        <i class="bi bi-grid-fill mr-3"></i>
                                        <span>Dashboard</span>
                                    </a>

                                    <a href="analytics" class="sesedesedwsedwdd">
                                        <i class="bi bi-bar-chart-line-fill mr-3"></i>
                                        <span>Analytics</span>
                                    </a>

                                    <a href="users"  class="sesedesedwsedwdd">                    
                                        <i class="bi bi-people-fill mr-3"></i>
                                        <span>Users</span>
                                    </a>

                                    <a href="categories" class="sesedesedwsedwdd">
                                        <i class="bi bi-tags-fill mr-3"></i>
                                        <span>Categories</span>
                                    </a>

                                    <a href="products" class="sesedesedwsedwdd">
                                        <i class="bi bi-box-seam-fill mr-3"></i>
                                        <span>Products</span>
                                    </a>

                                    <a href="discounts" class="sesedesedwsedwdd">
                                        <i class="bi bi-percent mr-3"></i>
                                        <span>Discounts</span>
                                    </a>

                                    <a href="sales" class="sesedesedwsedwdd">
                                        <i class="bi bi-currency-dollar mr-3"></i>
                                        <span>Sales</span>
                                    </a>
                                    <a href="orders" class="sesedesedwsedwdd">
                                        <i class="bi bi-cart-check mr-3"></i>
                                        <span>Orders</span>
                                    </a>
                                    <a href="builder" class="sesedesedwsedwdd">
                                        <i class="bi bi-layout-text-sidebar mr-3"></i>
                                        <span>Page Builder</span>
                                    </a>
                                    <a href="settings" class="sesedesedwsedwdd">
                                        <i class="bi bi-gear mr-3"></i>
                                        <span>Settings</span>
                                    </a>
                                    <a style="margin:1rem"></a>
                                    <br>
                                    <a style="margin:0.4rem"></a>
                                    <br>
                                    <p style="font-size:9px; padding: 0rem 0px 8rem 0px">Powered by VARSITYMARKET <span style="font-size: 8px; font-weight: 800;">technologies<span></p>

                                </div>
        </div>

        <?php @include_once "routes.php"; ?>

    </div>
    <script src="admin.js"></script>
</body>
</html>