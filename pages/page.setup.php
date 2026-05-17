<?php 
#   TITLE   : Page Setup    
#   DESC    : The setup page of the application. 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30
?>


<?php
function seed_demo_data($db) {
    // Categories
    $categories = [
        ['Footwear', 'Shoes, sneakers, boots and sandals', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=300&fit=crop'],
        ['Apparel', 'T-shirts, hoodies, jackets and more', 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=400&h=300&fit=crop'],
        ['Accessories', 'Bags, watches, sunglasses and jewelry', 'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?w=400&h=300&fit=crop'],
        ['Electronics', 'Gadgets, headphones and tech gear', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop'],
    ];
    foreach ($categories as $c) {
        $db->query("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)", $c);
    }

    // Products
    $products = [
        [1, 'Urban Runner Pro', 'Lightweight running shoes with responsive cushioning and breathable mesh upper. Perfect for daily training.', 1299.99, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=300&fit=crop', 25],
        [1, 'Classic Leather Boot', 'Premium full-grain leather boots with Goodyear welt construction. Built to last a lifetime.', 2499.00, 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?w=400&h=300&fit=crop', 12],
        [1, 'Summer Slide Sandal', 'Comfortable slide sandals with contoured footbed. Ideal for beach days and casual outings.', 449.99, 'https://images.unsplash.com/photo-1603487742131-4160ec999306?w=400&h=300&fit=crop', 40],
        [2, 'Oversized Graphic Tee', 'Relaxed fit cotton tee with screen-printed artwork. Pre-shrunk and garment dyed.', 349.00, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=300&fit=crop', 60],
        [2, 'Tech Fleece Hoodie', 'Midweight hoodie with kangaroo pocket and adjustable hood. Soft brushed interior.', 899.99, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=400&h=300&fit=crop', 18],
        [2, 'Denim Trucker Jacket', 'Classic denim jacket with chest pockets and adjustable waist tabs. Stonewash finish.', 1199.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=400&h=300&fit=crop', 8],
        [3, 'Leather Crossbody Bag', 'Compact crossbody bag with adjustable strap and magnetic closure. Multiple compartments.', 799.00, 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=400&h=300&fit=crop', 15],
        [3, 'Aviator Sunglasses', 'Polarized UV400 lenses with gold metal frame. Includes protective carrying case.', 599.99, 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=400&h=300&fit=crop', 30],
        [3, 'Minimalist Watch', 'Swiss quartz movement with sapphire crystal glass. Genuine leather strap, 40mm case.', 1899.00, 'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?w=400&h=300&fit=crop', 0],
        [4, 'Wireless Noise-Cancelling Headphones', 'Premium over-ear headphones with active noise cancellation. 30-hour battery life.', 2999.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop', 22],
        [4, 'Portable Bluetooth Speaker', 'Waterproof speaker with 360-degree sound. 12-hour playtime, USB-C charging.', 1499.00, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400&h=300&fit=crop', 35],
        [4, 'Smart Fitness Band', 'Heart rate monitor, sleep tracking, and 7-day battery. Water resistant to 50m.', 699.99, 'https://images.unsplash.com/photo-1575311373937-040b8e1fd5b6?w=400&h=300&fit=crop', 45],
    ];
    foreach ($products as $p) {
        $db->query("INSERT INTO products (category_id, name, description, price, image, stock) VALUES (?, ?, ?, ?, ?, ?)", $p);
    }

    // Orders
    $statuses = ['pending', 'processing', 'completed', 'completed', 'completed'];
    $customers = [
        ['Thabo Mokoena', 'thabo@example.com'],
        ['Sarah Johnson', 'sarah.j@example.com'],
        ['Nomsa Dlamini', 'nomsa.d@example.com'],
        ['James van der Merwe', 'james.vdm@example.com'],
        ['Lerato Khumalo', 'lerato.k@example.com'],
    ];
    $order_items = [
        [['Urban Runner Pro', 1, 1299.99], ['Oversized Graphic Tee', 2, 349.00]],
        [['Tech Fleece Hoodie', 1, 899.99]],
        [['Leather Crossbody Bag', 1, 799.00], ['Aviator Sunglasses', 1, 599.99]],
        [['Wireless Noise-Cancelling Headphones', 1, 2999.00], ['Classic Leather Boot', 1, 2499.00]],
        [['Smart Fitness Band', 2, 699.99], ['Portable Bluetooth Speaker', 1, 1499.00]],
    ];
    for ($i = 0; $i < count($customers); $i++) {
        $total = 0;
        $items = [];
        foreach ($order_items[$i] as $item) {
            $total += $item[1] * $item[2];
            $items[] = ['name' => $item[0], 'qty' => $item[1], 'price' => $item[2]];
        }
        $db->query(
            "INSERT INTO orders (customer_name, customer_email, total_amount, status, created_at) VALUES (?, ?, ?, ?, datetime('now', ?))",
            [$customers[$i][0], $customers[$i][1], $total, $statuses[$i], '-' . $i . ' days']
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = dirname(dirname(__FILE__))."/build/vm.engine.sql";
    $dbm = new database_manager($file);

    # Receive Data From The Forms
    $website_name = $_POST['wb_name'];
    
    // Domain Selection Logic
    $domain_type = $_POST['domain_type'] ?? 'custom';
    if ($domain_type === 'subdomain' && isset($_SERVER['PARENT_DOMAIN'])) {
        $prefix = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['subdomain_prefix']));
        $website_domain = $prefix . "." . $_SERVER['PARENT_DOMAIN'];
    } else {
        $website_domain = $_POST['wb_domain'];
    }

    $account_index = __ACCOUNT_INDEX__;
    $name = $website_name;
    $domain = $website_domain;
    $theme = "default";
    
    $hash_key = hash('sha256',uniqid('key'));
    $signature_key = str_shuffle(hash('sha256',uniqid('signature')));

    $account_data = base_encryption(json_encode([
        "street" => "Default",
        "city" => $_POST['bcity'] ?? "Default",
        "state" => "Default",
        "zip" => "0000",
        "country" => "South Africa",
    ],JSON_PRETTY_PRINT));

    @$e = database_services($domain);
    $e = website_services($domain,$theme);

    $sql = "UPDATE sys_account SET `data` = ? WHERE (`auth` = ?);";
    $e = $dbm->query($sql, [$account_data, $account_index]);

    $sql = "INSERT INTO `sys_websites` (`name`,`domain`,`theme`,`hash_key`,`account_index`) VALUES (?, ?, ?, ?, ?)";
    $e = $dbm->query($sql, [$name, $domain, $theme, $hash_key, $account_index]);

    // Load demo data if requested
    if (!empty($_POST['load_demo_data'])) {
        $demo_db_file = dirname(dirname(__FILE__)) . '/sites/' . $domain . '/storage.data';
        if (file_exists($demo_db_file)) {
            $demo_db = new database_manager($demo_db_file);
            seed_demo_data($demo_db);
        }
    }

    echo "<script>window.location.href = '/home/';</script>";
    exit;
}

?>
    <!-- DASHBOARD SECTION (Hidden by default) -->
    <div id="dashboard-container" class="container">
        <?php @include_once "header.php"; ?> 

    <main>
        <div>
            
            <?php
            $active_billing = false; 
            if ($active_billing){
                @include_once "modal.billing.setup.php";
            }else{
                @include_once "modal.setup.php";
            }
            
            ?>

        </div>

    </main>

    </div>
