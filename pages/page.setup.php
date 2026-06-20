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
        ['Streetwear', 'Customised Clothing and Accessories', 'https://mercha.co.za/wp-content/uploads/2025/08/DARK-HOMEPAGE.pdf-1-scaled-e1755757658874.png'],
        ['Apparel', 'T-shirts, hoodies, jackets and more', 'https://mercha.co.za/wp-content/uploads/2025/08/DARK-HOMEPAGE.pdf-scaled-e1755757458722.png'],
        ['Accessories', 'Bags, watches, sunglasses and jewelry', 'https://mercha.co.za/wp-content/uploads/2025/08/DARK-HOMEPAGE.pdf-3-scaled-e1755757708529.png'],
        ['Merch', 'Custom Clothing and Accessories', 'https://mercha.co.za/wp-content/uploads/2025/08/DARK-HOMEPAGE.pdf-2-scaled-e1755757767206.png'],
    ];
    foreach ($categories as $c) {
        $db->query("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)", $c);
    }

    // Products
    $products = [
        [1, 'Urban Runner Pro', 'Lightweight running shoes with responsive cushioning and breathable mesh upper. Perfect for daily training.', 1299.99, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=300&fit=crop', 25],
        [1, 'Classic Leather Boot', 'Premium full-grain leather boots with Goodyear welt construction. Built to last a lifetime.', 2499.00, 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?w=400&h=300&fit=crop', 12],
            [1, 'TBHBG 220GSM OVRSZD TEE', 'This is a new era of A-Reece. The P2 Collection embodies the power of owning every version of yourself. Each T-shirt is crafted from a premium, heavyweight 220gsm cotton blend, designed for lasting quality and comfort. With its oversized fit and everyday versatility, the collection offers a graphic streetwear staple made for the day-one fans.',499.99, 'https://mercha.co.za/wp-content/uploads/2025/08/Mocks-134-1000x1259.png', 40],
            [2, 'Deadlines SLVLSS Tee', 'The Deadlines Sleeveless T-Shirt by A-Reece: Crafted from 100% premium cotton for a comfortable, breathable feel, this unisex sleeveless tee delivers a bold streetwear edge with an easy, relaxed fit. Featuring the full project track list on the back and “DEADLINES” in striking red on the front, it comes in both classic black and clean white editions, offering two versatile looks for any wardrobe.', 399.99, 'https://mercha.co.za/wp-content/uploads/2025/07/Mocks-94-1000x1259.webp', 60],
        [2, 'Tech Fleece Hoodie', 'Midweight hoodie with kangaroo pocket and adjustable hood. Soft brushed interior.', 899.99, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=400&h=300&fit=crop', 18],
        [2, 'Denim Trucker Jacket', 'Classic denim jacket with chest pockets and adjustable waist tabs. Stonewash finish.', 1199.00, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=400&h=300&fit=crop', 8],
            [3, 'HEATSKRS HOODIE', 'Available in black and off-white, with a big red print saying ‘culture comes alive’. The hoodie is crafted from a premium, heavyweight cotton blend that is soft, warm, and built to last. Its oversized fit ensures everyday comfort. Get yours now.', 799.00, 'https://mercha.co.za/wp-content/uploads/2025/09/Mocks-_Hoodie-Back-1000x1259.png', 15],
            [3, 'SHINE O’ CLOCK Hoodie', 'It’s time to shine in the Shine O’ Clock hoodie, a statement piece suitable for any occasion. This hoodie features bold and graphic prints that encapsulate Jay Jody’s new body of work. An essential piece for fans and lovers of graphic hoodies.', 699.99, 'https://mercha.co.za/wp-content/uploads/2025/08/Shine-Black-Hoodie_back_1721644978-1000x1260.webp', 30],
        [3, 'Minimalist Watch', 'Swiss quartz movement with sapphire crystal glass. Genuine leather strap, 40mm case.', 1899.00, 'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?w=400&h=300&fit=crop', 0],
        [4, 'Wireless Noise-Cancelling Headphones', 'Premium over-ear headphones with active noise cancellation. 30-hour battery life.', 2999.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop', 22],
            [4, 'HEATSKRS OVRSZD TEE', 'The Heatskrs Oversize black T-shirt is available in black and off-white, with a big red print saying ‘culture comes alive’. This T-shirt is crafted from a premium, heavyweight cotton blend that is soft, warm, and built to last. Its oversized fit ensures everyday comfort. Get yours now.', 499.00, 'https://mercha.co.za/wp-content/uploads/2025/09/Mocks-_B-Tee-Back-1000x1259.png', 35],
            [4, 'Underdog Hoodie', 'Embrace A-Reece’s essence with his official brand merch. Made from 100% breathable cotton, this unisex hoodie features a hand-written print by A-Reece on the back and his signature tattoos on the sleeve, creating a personal connection to his artistry. Available in both black and grey, this hoodie sports a baggy fit for ultimate comfort.', 699.99, 'https://mercha.co.za/wp-content/uploads/2025/07/Underdog-114-1000x1259.webp', 45],
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
        [['Urban Runner Pro', 1, 1299.99], ['Deadlines SLVLSS Tee', 2, 349.00]],
        [['Tech Fleece Hoodie', 1, 899.99]],
        [['Leather Crossbody Bag', 1, 799.00], ['Aviator Sunglasses', 1, 599.99]],
        [['Wireless Noise-Cancelling Headphones', 1, 2999.00], ['Classic Leather Boot', 1, 2499.00]],
        [['Underdog Hoodie', 2, 699.99], ['
HEATSKRS OVRSZD TEE', 1, 499.00]],
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

    if (!empty($_POST['promotion_data'])) {
        @include_once dirname(__FILE__)."/includes/marketing.php";

        $store_identity = ['name'=>$name,
        'email'=>$_POST['wb_email'] ?? 'contact@' . $domain,
        'phone'=>$_POST['wb_contact'] ?? 'Null']; ;

        $domain = $website_domain;
        $business = ['description'=>$_POST['wb_desc'] ?? 'No description provided.',
        'industry'=>$_POST['wb_industry'] ?? 'General',
        'country'=>$_POST['bcity'] ?? 'Unknown'];
        $e = send_notification_webhook($store_identity,$domain, $business); 
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
