<?php
#   TITLE   : Shopify CSV Parser
#   DESC    : Parses a Shopify products export CSV into normalized rows
#             suitable for our product table. One emitted row per Shopify
#             handle/product; variants and gallery images are preserved as
#             arrays so the admin can store richer product metadata.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES

/**
 * Parse a Shopify products export CSV.
 *
 * @param string $filePath Absolute path to the uploaded CSV.
 * @return array {
 *   'ok'    => bool,
 *   'error' => string|null,        // populated when ok=false
 *   'rows'  => array<int, array{
 *       name: string,
 *       description: string,
 *       category: string,
 *       image: string,
 *       gallery: array<int, string>,
 *       variants: array<int, array{
 *           label: string,
 *           price: float,
 *           stock: int,
 *           image: string,
 *           sku: string,
 *       }>,
 *       variant_count: int,
 *       gallery_count: int,
 *       price: float,
 *       stock: int,
 *       notes: array<int, string>,
 *       parse_error: string|null,
 *   }>
 * }
 */
function parse_shopify_csv(string $filePath): array
{
    if (!is_readable($filePath)) {
        return ['ok' => false, 'error' => 'CSV file is not readable', 'rows' => []];
    }

    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        return ['ok' => false, 'error' => 'Failed to open CSV file', 'rows' => []];
    }

    $headers = fgetcsv($handle);
    if ($headers === false || $headers === null) {
        fclose($handle);
        return ['ok' => false, 'error' => 'CSV is empty', 'rows' => []];
    }

    // Strip UTF-8 BOM from first header cell if present.
    if (isset($headers[0]) && substr($headers[0], 0, 3) === "\xEF\xBB\xBF") {
        $headers[0] = substr($headers[0], 3);
    }

    $required = ['Handle', 'Title', 'Variant Price'];
    foreach ($required as $col) {
        if (!in_array($col, $headers, true)) {
            fclose($handle);
            return ['ok' => false, 'error' => "Missing required column: $col", 'rows' => []];
        }
    }

    // Index headers by name for O(1) lookup per row.
    $idx = array_flip($headers);

    // Pass 1: read every data row, group by Handle (preserve in-file order).
    $groups = [];   // handle => array of rows
    $order  = [];   // handles in first-seen order
    while (($row = fgetcsv($handle)) !== false) {
        if ($row === [null] || count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) {
            continue;
        }
        $handleVal = isset($idx['Handle'], $row[$idx['Handle']]) ? trim((string)$row[$idx['Handle']]) : '';
        if ($handleVal === '') {
            continue;
        }
        if (!isset($groups[$handleVal])) {
            $groups[$handleVal] = [];
            $order[] = $handleVal;
        }
        $groups[$handleVal][] = $row;
    }
    fclose($handle);

    // Pass 2: emit normalized products.
    $rows = [];
    foreach ($order as $handleVal) {
        $group = $groups[$handleVal];

        $baseTitle = '';
        $baseBody  = '';
        $baseType  = '';
        $baseImage = '';
        foreach ($group as $r) {
            if ($baseTitle === '' && isset($idx['Title'])) {
                $baseTitle = trim((string)($r[$idx['Title']] ?? ''));
            }
            if ($baseBody === '' && isset($idx['Body (HTML)'])) {
                $body = trim((string)($r[$idx['Body (HTML)']] ?? ''));
                if ($body !== '') {
                    $baseBody = trim(strip_tags($body));
                }
            }
            if ($baseType === '' && isset($idx['Type'])) {
                $baseType = trim((string)($r[$idx['Type']] ?? ''));
            }
            if ($baseImage === '' && isset($idx['Image Src'])) {
                $baseImage = trim((string)($r[$idx['Image Src']] ?? ''));
            }
            if ($baseTitle !== '' && $baseBody !== '' && $baseType !== '' && $baseImage !== '') {
                break;
            }
        }

        $gallery = [];
        $variants = [];
        $primaryImage = '';
        $defaultPrice = null;
        $totalStock = 0;
        $parseErrors = [];

        foreach ($group as $r) {
            $rowImage = isset($idx['Image Src']) ? trim((string)($r[$idx['Image Src']] ?? '')) : '';
            if ($rowImage !== '' && !in_array($rowImage, $gallery, true)) {
                $gallery[] = $rowImage;
            }

            $rawPrice = isset($idx['Variant Price']) ? trim((string)($r[$idx['Variant Price']] ?? '')) : '';
            if ($rawPrice === '') {
                // Image-only or metadata row — skip silently.
                continue;
            }

            if (!is_numeric($rawPrice)) {
                $parseErrors[] = 'Variant price is not numeric';
                continue;
            }

            $opt1 = isset($idx['Option1 Value']) ? trim((string)($r[$idx['Option1 Value']] ?? '')) : '';
            $opt2 = isset($idx['Option2 Value']) ? trim((string)($r[$idx['Option2 Value']] ?? '')) : '';
            $opt3 = isset($idx['Option3 Value']) ? trim((string)($r[$idx['Option3 Value']] ?? '')) : '';
            $suffix = trim(implode(' / ', array_filter([$opt1, $opt2, $opt3], fn($v) => $v !== '')));
            if ($suffix === 'Default Title') {
                $suffix = '';
            }

            $variantImage = isset($idx['Variant Image']) ? trim((string)($r[$idx['Variant Image']] ?? '')) : '';
            if ($variantImage !== '' && !in_array($variantImage, $gallery, true)) {
                $gallery[] = $variantImage;
            }
            $image = $variantImage !== '' ? $variantImage : ($rowImage !== '' ? $rowImage : $baseImage);

            $rawStock = isset($idx['Variant Inventory Qty']) ? trim((string)($r[$idx['Variant Inventory Qty']] ?? '')) : '';
            $stock = is_numeric($rawStock) ? (int)$rawStock : 0;
            $totalStock += $stock;

            if ($primaryImage === '' && $image !== '') {
                $primaryImage = $image;
            }

            $variants[] = [
                'label' => $suffix !== '' ? $suffix : 'Default',
                'price' => (float)$rawPrice,
                'stock' => $stock,
                'image' => $image,
                'sku' => isset($idx['Variant SKU']) ? trim((string)($r[$idx['Variant SKU']] ?? '')) : '',
            ];

            if ($defaultPrice === null) {
                $defaultPrice = (float)$rawPrice;
            }
        }

        $gallery = array_values(array_unique(array_filter(array_merge([$baseImage], $gallery), fn($v) => trim((string)$v) !== '')));
        if ($primaryImage === '') {
            $primaryImage = $gallery[0] ?? $baseImage;
        }

        $parseError = null;
        if ($baseTitle === '') {
            $parseError = 'Title is empty';
        } elseif (empty($variants)) {
            $parseError = 'No valid variants found';
        }

        if ($defaultPrice === null) {
            $defaultPrice = 0.0;
        }

        $rows[] = [
            'name'         => $baseTitle,
            'description'  => $baseBody,
            'category'     => $baseType,
            'image'        => $primaryImage,
            'gallery'      => $gallery,
            'variants'     => $variants,
            'variant_count' => count($variants),
            'gallery_count' => count($gallery),
            'price'        => $defaultPrice,
            'stock'        => $totalStock,
            'parse_error'  => $parseError,
            'notes'        => $parseErrors,
        ];
    }

    return ['ok' => true, 'error' => null, 'rows' => $rows];
}
