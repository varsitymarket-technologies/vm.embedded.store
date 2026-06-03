<?php
// Standalone test runner for module/shopify_csv_parser.php
// Usage: php tests/shopify_csv_parser_test.php

require_once __DIR__ . '/../module/shopify_csv_parser.php';

$pass = 0;
$fail = 0;

function eq($expected, $actual, string $msg): void {
    global $pass, $fail;
    if ($expected === $actual) {
        echo "  PASS  $msg\n";
        $pass++;
    } else {
        echo "  FAIL  $msg\n";
        echo "        expected: " . var_export($expected, true) . "\n";
        echo "        actual:   " . var_export($actual, true) . "\n";
        $fail++;
    }
}

echo "== shopify_csv_parser ==\n";

// --- Test 1: missing required column rejected ---
$result = parse_shopify_csv(__DIR__ . '/fixtures/shopify_missing_columns.csv');
eq(false, $result['ok'], 'missing Variant Price → ok=false');
eq(true, is_string($result['error']) && stripos($result['error'], 'Variant Price') !== false,
    'error message names the missing column');

// --- Test 2: happy-path parses expected number of products ---
$result = parse_shopify_csv(__DIR__ . '/fixtures/shopify_products_sample.csv');
eq(true, $result['ok'], 'happy path → ok=true');
// classic-tee has 3 variants, mug has 1, no-type has 1, bad-price has 1 = 6 rows
eq(6, count($result['rows']), 'emits 6 normalized rows');

$rows = $result['rows'];

// --- Test 3: first variant of classic-tee ---
eq('Classic Tee - Small / Red', $rows[0]['name'], 'multi-variant name joins options with " / "');
eq(19.99, $rows[0]['price'], 'price cast to float');
eq(5, $rows[0]['stock'], 'stock cast to int');
eq('https://cdn.example.com/tee-front.jpg', $rows[0]['image'], 'first variant uses base Image Src');
eq('Apparel', $rows[0]['category'], 'category from Type column');
eq('A soft cotton tee.', $rows[0]['description'], 'description has HTML tags stripped');
eq(null, $rows[0]['parse_error'], 'valid row has parse_error=null');

// --- Test 4: second variant uses Variant Image when present ---
eq('Classic Tee - Small / Blue', $rows[1]['name'], 'second variant name');
eq('https://cdn.example.com/tee-blue.jpg', $rows[1]['image'], 'second variant uses Variant Image over base Image Src');

// --- Test 5: third variant inherits base image when neither variant image nor row image present ---
eq('Classic Tee - Large / Red', $rows[2]['name'], 'third variant name');
eq('https://cdn.example.com/tee-front.jpg', $rows[2]['image'], 'third variant falls back to base Image Src');

// --- Test 6: Default Title suffix is dropped ---
eq('Coffee Mug', $rows[3]['name'], 'single-variant product drops "Default Title" suffix');
eq(9.5, $rows[3]['price'], 'mug price');
eq('Drinkware', $rows[3]['category'], 'mug category');

// --- Test 7: empty Type leaves category empty ---
eq('Mystery Item', $rows[4]['name'], 'no-type product name');
eq('', $rows[4]['category'], 'empty Type → empty category string');

// --- Test 8: image-only row (empty Variant Price) is skipped ---
// classic-tee has an image-only 4th row that should NOT appear in $rows
foreach ($rows as $r) {
    if ($r['name'] === 'Classic Tee' && $r['price'] === 0.0) {
        eq('skipped', 'emitted', 'image-only row should be skipped, not emitted');
    }
}

// --- Test 9: bad price flagged with parse_error ---
eq('Broken Product', $rows[5]['name'], 'bad-price row still emitted');
eq(true, is_string($rows[5]['parse_error']) && stripos($rows[5]['parse_error'], 'numeric') !== false,
    'bad price → parse_error mentions "numeric"');

// --- Summary ---
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
