<?php
#   TITLE   : Shopify CSV Parser
#   DESC    : Parses a Shopify products export CSV into normalized rows
#             suitable for our flat `products` table. One emitted row per
#             Shopify variant; categories surface as a plain string (resolved
#             to category_id by the caller).
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
 *       price: float,
 *       stock: int,
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

        foreach ($group as $r) {
            $rawPrice = isset($idx['Variant Price']) ? trim((string)($r[$idx['Variant Price']] ?? '')) : '';
            if ($rawPrice === '') {
                // Image-only or metadata row — skip silently.
                continue;
            }

            $opt1 = isset($idx['Option1 Value']) ? trim((string)($r[$idx['Option1 Value']] ?? '')) : '';
            $opt2 = isset($idx['Option2 Value']) ? trim((string)($r[$idx['Option2 Value']] ?? '')) : '';
            $opt3 = isset($idx['Option3 Value']) ? trim((string)($r[$idx['Option3 Value']] ?? '')) : '';
            $suffix = trim(implode(' / ', array_filter([$opt1, $opt2, $opt3], fn($v) => $v !== '')));
            if ($suffix === 'Default Title') {
                $suffix = '';
            }

            $name = $baseTitle;
            if ($suffix !== '') {
                $name .= ' - ' . $suffix;
            }
            if (function_exists('mb_substr')) {
                $name = mb_substr($name, 0, 255);
            } else {
                $name = substr($name, 0, 255);
            }

            $variantImage = isset($idx['Variant Image']) ? trim((string)($r[$idx['Variant Image']] ?? '')) : '';
            $image = $variantImage !== '' ? $variantImage : $baseImage;

            $rawStock = isset($idx['Variant Inventory Qty']) ? trim((string)($r[$idx['Variant Inventory Qty']] ?? '')) : '';
            $stock = is_numeric($rawStock) ? (int)$rawStock : 0;

            $parseError = null;
            if (!is_numeric($rawPrice)) {
                $parseError = 'Variant Price is not numeric';
            } elseif ($baseTitle === '') {
                $parseError = 'Title is empty';
            }

            $rows[] = [
                'name'        => $name,
                'description' => $baseBody,
                'category'    => $baseType,
                'image'       => $image,
                'price'       => (float)$rawPrice,
                'stock'       => $stock,
                'parse_error' => $parseError,
            ];
        }
    }

    return ['ok' => true, 'error' => null, 'rows' => $rows];
}
