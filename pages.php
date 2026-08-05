<?php
#   TITLE   : Control Panel Pages Router
#   DESC    : Routes the operator control panel pages into their handlers.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.2
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/07/31

@include_once dirname(__FILE__) . "/boot.php";
@include_once dirname(__FILE__) . "/config.php";

$route = trim((string) ex(1));
if ($route === '') {
    $route = 'home';
}

$page_map = [
    'auth' => 'page.auth.php',
    'home' => 'page.dashboard.php',
    'dashboard' => 'page.dashboard.php',
    'export-code' => 'page.export.code.php',
    'export-frame' => 'page.export.frame.php',
    'export-link' => 'page.export.link.php',
    'mobile' => 'page.mobile.php',
    'payments' => 'page.payments.php',
    'publish' => 'page.publish.php',
    'setup' => 'page.setup.php',
    'theme' => 'page.theme.php',
];

if (empty(__ACCOUNT_INDEX__)) {
    $route = 'auth';
} elseif ((account_data('auth') !== __ACCOUNT_INDEX__)) {
    $route = 'auth';
}

$db_engine = __DB_MODULE__;
$domain = __DOMAIN__ ?? '';
$store_record = [];
$owned_domain = null;

if (!empty(__ACCOUNT_INDEX__) && $db_engine) {
    try {
        $store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
        $owned_domain = $store_record[0]['domain'] ?? null;
    } catch (\Throwable $e) {
        $owned_domain = null;
    }
}

if ($route !== 'auth') {
    if (empty($owned_domain) || empty($domain) || $owned_domain !== $domain) {
        $route = 'setup';
    }
}

$file = $page_map[$route] ?? $page_map['home'];
$page_file = dirname(__FILE__) . '/pages/' . $file;

if (!file_exists($page_file)) {
    $page_file = dirname(__FILE__) . '/pages/error.404.lost.php';
}

include_once $page_file;

