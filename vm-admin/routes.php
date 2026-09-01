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
    "reviews" => "page.reviews.php",
    "categories" => "page.categories.php",
    "users" => "page.users.php",
    "discounts" => "page.discounts.php",
    "sales" => "page.sales.php",
    "delivery" => "page.delivery.php",
    "logistics" => "page.logistics.php",
    "orders" => "page.orders.php",
    "builder" => "page.builder.php",
    "page" => "page.page.php",
    "settings" => "page.settings.php",
    "agent" => "page.agent.php",
    "account" => "page.account.php",
    "analytics" => "page.analytics.php",
    "theme" => "page.theme.php",
    "deploy" => "page.deploy.php",
    "publish" => "page.publish.php",
    "payments" => "page.payments.php",
    "export" => "page.export.php",
    "forms" => "page.forms.php",
    "ai-builder" => "page.ai-builder.php",
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
    echo '<div class="flex min-h-[60vh] items-center justify-center px-6" style="width:100%;height:100%;">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#202123] p-8 text-center shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#c8c8c81a]">
                <i class="bi bi-person-lock text-2xl text-white-400"></i>
            </div>
            <h2 class="mt-5 text-xl font-semibold text-white">Sign in to continue</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-400">Please sign in again to continue using the system.</p>
            <a href="/auth/" class="mt-6 inline-flex items-center gap-2 rounded-full bg-purple-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#006e52]">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign in again
            </a>
        </div>
    </div>';
    return;
}

// Prevent accessing another user's store via URL manipulation
if (!empty($url_domain) && $url_domain !== $owned_domain) {
    echo '<div class="flex min-h-[60vh] items-center justify-center px-6" style="width:100%;height:100%;">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#202123] p-8 text-center shadow-[0_18px_45px_rgba(0,0,0,0.24)]">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#c8c8c81a]">
                <i class="bi bi-person-lock text-2xl text-white-400"></i>
            </div>
            <h2 class="mt-5 text-xl font-semibold text-white">Sign in to continue</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-400">Please sign in again to continue using the system.</p>
            <a href="/auth/" class="mt-6 inline-flex items-center gap-2 rounded-full bg-purple-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#006e52]">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign in again
            </a>
        </div>
    </div>';
    return;
}

#Include The Web File 
@include_once dirname(__FILE__) . "/routes/" . $file;
?>
