<?php
#   TITLE   : Admin Routing Scripts   
#   DESC    : The Admin Routing Scripts 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30

$data = [
    "auth" => "page.auth.php",
    "home" => "page.home.php",
    "products" => "page.products.php",
    "categories" => "page.categories.php",
    "users" => "page.users.php",
    "discounts" => "page.discounts.php",
    "sales" => "page.sales.php",
    "delivery" => "page.delivery.php",
    "logistics" => "page.logistics.php",
    "orders" => "page.orders.php",
    "builder" => "page.builder.php",
    "settings" => "page.settings.php",
    "agent" => "page.agent.php",
    "analytics" => "page.analytics.php",
    "theme" => "page.theme.php",
    "deploy" => "page.deploy.php",
    "publish" => "page.publish.php",
    "payments" => "page.payments.php",
    "forms" => "page.forms.php",
    "session" => "page.session-expired.php",
];

@$file = $data[ex(3)] ?? $data['home'];

if (empty(__ACCOUNT_INDEX__)) {
    $file = $data["session"];
} else if ((account_data('auth') !== __ACCOUNT_INDEX__)) {
    $file = $data["session"];
}


// --- Domain & Store Ownership Check ---
$db_engine = __DB_MODULE__;
$domain = __DOMAIN__;
$url_domain = ex(2);

// Verify the logged-in user owns the store they're trying to access
$store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
$owned_domain = $store_record[0]['domain'] ?? null;

if (empty($owned_domain)) {
    // User has no store — redirect to setup
    echo '<div class="flex items-center justify-center min-h-[60vh]"  style="display:block; margin: auto;">
        <div class="text-center">
            <i class="bi bi-shop text-6xl text-gray-600"></i>
            <h2 class="text-2xl font-bold text-white mt-4">No Store Found</h2>
            <p class="text-gray-400 mt-2">You need to create a store before accessing the control panel.</p>
            <a href="/home/" class="inline-block mt-6 bg-purple-600 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-purple-500 transition-all">Go to Dashboard</a>
        </div>
    </div>';
    return;
}

// Prevent accessing another user's store via URL manipulation
if (!empty($url_domain) && $url_domain !== $owned_domain) {
    echo '<div class="flex items-center justify-center min-h-[60vh]" style="display:block; margin: auto;">
        <div class="text-center">
            <i class="bi bi-shield-x text-6xl text-red-500"></i>
            <h2 class="text-2xl font-bold text-white mt-4">Access Denied</h2>
            <p class="text-gray-400 mt-2">You do not have permission to manage this store.</p>
            <a href="/vm-admin/' . htmlspecialchars($owned_domain, ENT_QUOTES, 'UTF-8') . '/settings" class="inline-block mt-6 bg-purple-600 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-purple-500 transition-all">Go to Your Settings</a>
        </div>
    </div>';
    return;
}

#Include The Web File 
@include_once dirname(__FILE__) . "/routes/" . $file;
?>
