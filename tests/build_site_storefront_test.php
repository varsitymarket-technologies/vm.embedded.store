<?php
require_once __DIR__ . '/../build/site/api.kit';

$products = api()->async_products();
if (!is_array($products) || count($products) < 1) {
    fwrite(STDERR, "Expected fallback product catalog, got: " . var_export($products, true) . PHP_EOL);
    exit(1);
}

$product = api()->async_single_products($products[0]['id'] ?? 1);
if (!is_array($product) || empty($product['name'])) {
    fwrite(STDERR, "Expected single-product payload with a name, got: " . var_export($product, true) . PHP_EOL);
    exit(1);
}

$related = api()->async_related_products($product['id']);
if (!is_array($related) || count($related) < 1) {
    fwrite(STDERR, "Expected related products, got: " . var_export($related, true) . PHP_EOL);
    exit(1);
}

echo "Storefront data layer is working." . PHP_EOL;
