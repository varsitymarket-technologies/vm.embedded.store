<?php
session_start();

$testCart = sys_get_temp_dir() . '/build-site-cart-' . getmypid() . '.json';
if (file_exists($testCart)) {
    @unlink($testCart);
}
putenv('BUILD_SITE_CART_PATH=' . $testCart);

require_once __DIR__ . '/../build/site/cart-store.php';

$product = ['id' => '123', 'name' => 'Test Product', 'price' => 99.99, 'image' => '/img/test.jpg'];
$cart = customer_cart_add($product);
if (($cart['123']['qty'] ?? 0) !== 1) {
    fwrite(STDERR, 'add failed: ' . var_export($cart, true) . PHP_EOL);
    exit(1);
}

$cart = customer_cart_update_qty('123', 2);
if (($cart['123']['qty'] ?? 0) !== 2) {
    fwrite(STDERR, 'update failed: ' . var_export($cart, true) . PHP_EOL);
    exit(1);
}

$cart = customer_cart_remove('123');
if (!empty($cart)) {
    fwrite(STDERR, 'remove failed: ' . var_export($cart, true) . PHP_EOL);
    exit(1);
}

echo 'Cart store persistence is working.' . PHP_EOL;
