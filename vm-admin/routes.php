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
    "analytics" => "page.analytics.php",
    "theme" => "page.theme.php",
    "deploy" => "page.deploy.php",
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

#Include The Web File 
@include_once dirname(__FILE__) . "/routes/" . $file;
?>