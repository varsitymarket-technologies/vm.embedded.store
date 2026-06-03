# Shopify CSV Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Shopify product CSV importer accessible from the admin Products page that previews then commits parsed rows into the per-site `products` and `categories` tables.

**Architecture:** A new pure-function CSV parser module (`module/shopify_csv_parser.php`) groups Shopify rows by `Handle` and emits one normalized product per variant. The existing admin Products page (`vm-admin/routes/page.products.php`) gains two new POST handlers (`preview_shopify_import`, `commit_shopify_import`) that wrap the parser plus DB writes, and a 3-state modal that drives upload → preview → confirm via `fetch()`.

**Tech Stack:** PHP 7.4+, PDO + SQLite (existing `database_manager`), vanilla JS (`fetch`, `FormData`, `File` API), Tailwind classes already used on the products page. No new dependencies.

**Spec:** [docs/superpowers/specs/2026-06-03-shopify-csv-import-design.md](../specs/2026-06-03-shopify-csv-import-design.md)

---

## File Map

**Create:**
- `module/shopify_csv_parser.php` — pure-PHP parser. Exposes one function `parse_shopify_csv(string $filePath): array` returning `['ok' => bool, 'error' => string|null, 'rows' => array]`.
- `tests/shopify_csv_parser_test.php` — standalone PHP test runner. Runnable as `php tests/shopify_csv_parser_test.php`. Exits with code 0 on pass, 1 on fail.
- `tests/fixtures/shopify_products_sample.csv` — realistic Shopify export covering all cases: single-variant ("Default Title"), multi-variant with Size + Color, product with image-only rows, product with empty price, product with no Type.
- `tests/fixtures/shopify_missing_columns.csv` — CSV missing the `Variant Price` column, used to assert the parser rejects it.

**Modify:**
- `vm-admin/routes/page.products.php` — top of file gains a parser include and two new POST action branches; the page title row gains an "Import from Shopify" button; a new modal markup block is appended before the existing `<script>` tag; the existing `<script>` tag gains import-flow JS.

**No schema changes.** Existing `products` + `categories` tables in `sites/{domain}/storage.data` are used as-is.

---

## Note on testing approach

This project has no PHP test framework installed. The parser is the only piece with non-trivial logic, so it gets a single standalone PHP test script that uses `assert()` plus a tiny `eq()` helper to print pass/fail per test and `exit(1)` on any failure. Runnable either locally if PHP is on PATH, or inside the Docker container:

```bash
# Local PHP
php tests/shopify_csv_parser_test.php

# Inside Docker
docker compose exec vm-emb-sites php /var/www/html/tests/shopify_csv_parser_test.php
```

The POST handlers and UI are validated by manual smoke tests using `curl` and a browser. There are no automated tests for those layers.

---

## Task 1: Build the Shopify CSV parser (TDD)

**Files:**
- Create: `tests/fixtures/shopify_products_sample.csv`
- Create: `tests/fixtures/shopify_missing_columns.csv`
- Create: `tests/shopify_csv_parser_test.php`
- Create: `module/shopify_csv_parser.php`

### Step 1.1: Create the happy-path fixture

- [ ] **Step 1.1: Write `tests/fixtures/shopify_products_sample.csv`**

Create directory `tests/fixtures/` if missing. Write the file with this exact content (UTF-8, no BOM, LF or CRLF both fine):

```csv
Handle,Title,Body (HTML),Vendor,Type,Tags,Published,Option1 Name,Option1 Value,Option2 Name,Option2 Value,Option3 Name,Option3 Value,Variant SKU,Variant Inventory Qty,Variant Price,Image Src,Image Position,Variant Image,Status
classic-tee,Classic Tee,<p>A <b>soft</b> cotton tee.</p>,Acme,Apparel,casual,TRUE,Size,Small,Color,Red,,,SKU-001,5,19.99,https://cdn.example.com/tee-front.jpg,1,,active
classic-tee,,,,,,,Size,Small,Color,Blue,,,SKU-002,8,19.99,,2,https://cdn.example.com/tee-blue.jpg,active
classic-tee,,,,,,,Size,Large,Color,Red,,,SKU-003,3,21.99,,,,active
classic-tee,,,,,,,,,,,,,,,,https://cdn.example.com/tee-back.jpg,3,,active
mug,Coffee Mug,<p>Ceramic 12oz.</p>,Acme,Drinkware,kitchen,TRUE,Title,Default Title,,,,,SKU-004,12,9.50,https://cdn.example.com/mug.jpg,1,,active
no-type,Mystery Item,<p>Uncategorized goodie.</p>,Acme,,misc,TRUE,Title,Default Title,,,,,SKU-005,1,5.00,,,,active
bad-price,Broken Product,<p>Has a bad price.</p>,Acme,Apparel,broken,TRUE,Title,Default Title,,,,,SKU-006,2,NOT_A_NUMBER,,,,active
```

Verify: file exists at `tests/fixtures/shopify_products_sample.csv`.

### Step 1.2: Create the missing-columns fixture

- [ ] **Step 1.2: Write `tests/fixtures/shopify_missing_columns.csv`**

```csv
Handle,Title,Body (HTML)
foo,Foo Product,A description
```

This omits `Variant Price`, which is required.

### Step 1.3: Write the failing parser test

- [ ] **Step 1.3: Create `tests/shopify_csv_parser_test.php`**

```php
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
```

- [ ] **Step 1.4: Run the test — expect failure**

```bash
php tests/shopify_csv_parser_test.php
```

Expected output: PHP error "Failed opening required ... shopify_csv_parser.php" or similar, because the parser doesn't exist yet. This confirms the test runner reaches the parser.

### Step 1.5: Implement the parser

- [ ] **Step 1.5: Create `module/shopify_csv_parser.php`**

```php
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
        $get = function (string $col) use ($row, $idx) {
            return isset($idx[$col]) && isset($row[$idx[$col]]) ? (string)$row[$idx[$col]] : '';
        };
        $handleVal = trim($get('Handle'));
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
```

- [ ] **Step 1.6: Run the test — expect pass**

```bash
php tests/shopify_csv_parser_test.php
```

Expected output ends with `9 passed, 0 failed` (or whatever the actual count is — every `eq()` should print `PASS`). Exit code 0.

If any test fails, fix the parser (not the test) and re-run. Do not proceed until all green.

- [ ] **Step 1.7: Commit**

```bash
git add module/shopify_csv_parser.php tests/shopify_csv_parser_test.php tests/fixtures/
git commit -m "feat: add Shopify CSV parser module with tests

Parses a Shopify products export, groups rows by Handle, and emits
one normalized product per variant. Drops the 'Default Title' suffix
for single-variant products. Skips image-only rows. Flags rows with
non-numeric prices via parse_error instead of failing the whole parse."
```

---

## Task 2: Add `preview_shopify_import` and `commit_shopify_import` POST handlers

**Files:**
- Modify: `vm-admin/routes/page.products.php` (top of file, inside the `if ($_SERVER['REQUEST_METHOD'] === 'POST')` block, near line 5–47)

### Step 2.1: Add the parser include at the top of the file

- [ ] **Step 2.1: Edit `vm-admin/routes/page.products.php`**

At line 1 (after the opening `<?php`), add:

```php
<?php
@include_once dirname(dirname(dirname(__FILE__))) . "/module/shopify_csv_parser.php";

$db = initiate_web_database();
```

So the first three lines become the snippet above (the existing `$db = initiate_web_database();` was already line 2).

### Step 2.2: Add the preview action handler

- [ ] **Step 2.2: Insert before the `delete_product` branch**

Find the existing line `} elseif ($action === 'delete_product') {` (around line 39). Insert this new branch **immediately before** it (so the chain becomes add → update → preview → commit → delete):

```php
    } elseif ($action === 'preview_shopify_import') {
        header('Content-Type: application/json');

        if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'error' => 'No file uploaded']);
            exit;
        }

        $tmp = $_FILES['file']['tmp_name'];
        $parsed = parse_shopify_csv($tmp);
        if (!$parsed['ok']) {
            echo json_encode(['ok' => false, 'error' => $parsed['error']]);
            exit;
        }

        $summary = ['insert' => 0, 'update' => 0, 'skip' => 0];
        $previewRows = [];
        foreach ($parsed['rows'] as $row) {
            if ($row['parse_error'] !== null) {
                $action_for_row = 'skip';
                $reason = $row['parse_error'];
            } else {
                $existing = $db->query(
                    "SELECT id FROM products WHERE LOWER(name) = LOWER(?) LIMIT 1",
                    [$row['name']]
                );
                $action_for_row = !empty($existing) ? 'update' : 'insert';
                $reason = null;
            }
            $summary[$action_for_row]++;
            $previewRows[] = [
                'action'   => $action_for_row,
                'name'     => $row['name'],
                'category' => $row['category'],
                'price'    => $row['price'],
                'stock'    => $row['stock'],
                'image'    => $row['image'],
                'reason'   => $reason,
            ];
        }

        if (count($previewRows) === 0) {
            echo json_encode(['ok' => false, 'error' => 'No rows found in CSV']);
            exit;
        }

        echo json_encode(['ok' => true, 'summary' => $summary, 'rows' => $previewRows]);
        exit;

    } elseif ($action === 'commit_shopify_import') {
        header('Content-Type: application/json');

        if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'error' => 'No file uploaded']);
            exit;
        }

        $tmp = $_FILES['file']['tmp_name'];
        $parsed = parse_shopify_csv($tmp);
        if (!$parsed['ok']) {
            echo json_encode(['ok' => false, 'error' => $parsed['error']]);
            exit;
        }

        $counts = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        // Cache category id lookups by lower-cased name to avoid repeat SELECTs.
        $categoryCache = [];
        $resolveCategory = function (string $catName) use ($db, &$categoryCache) {
            $catName = trim($catName);
            if ($catName === '') return null;
            $key = strtolower($catName);
            if (array_key_exists($key, $categoryCache)) return $categoryCache[$key];

            $existing = $db->query(
                "SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1",
                [$catName]
            );
            if (!empty($existing)) {
                $categoryCache[$key] = (int)$existing[0]['id'];
                return $categoryCache[$key];
            }
            $db->query("INSERT INTO categories (name) VALUES (?)", [$catName]);
            // Look the new id up (database_manager doesn't expose lastInsertId).
            $row = $db->query(
                "SELECT id FROM categories WHERE LOWER(name) = LOWER(?) ORDER BY id DESC LIMIT 1",
                [$catName]
            );
            $categoryCache[$key] = !empty($row) ? (int)$row[0]['id'] : null;
            return $categoryCache[$key];
        };

        $db->query("BEGIN TRANSACTION");
        try {
            foreach ($parsed['rows'] as $row) {
                if ($row['parse_error'] !== null) {
                    $counts['skipped']++;
                    $counts['errors'][] = ['name' => $row['name'], 'reason' => $row['parse_error']];
                    continue;
                }
                try {
                    $categoryId = $resolveCategory($row['category']);
                    $existing = $db->query(
                        "SELECT id FROM products WHERE LOWER(name) = LOWER(?) LIMIT 1",
                        [$row['name']]
                    );
                    if (!empty($existing)) {
                        $db->query(
                            "UPDATE products SET description = ?, price = ?, stock = ?, image = ?, category_id = ? WHERE id = ?",
                            [$row['description'], $row['price'], $row['stock'], $row['image'], $categoryId, $existing[0]['id']]
                        );
                        $counts['updated']++;
                    } else {
                        $db->query(
                            "INSERT INTO products (name, description, price, stock, image, category_id) VALUES (?, ?, ?, ?, ?, ?)",
                            [$row['name'], $row['description'], $row['price'], $row['stock'], $row['image'], $categoryId]
                        );
                        $counts['inserted']++;
                    }
                } catch (Throwable $e) {
                    $counts['skipped']++;
                    $counts['errors'][] = ['name' => $row['name'], 'reason' => $e->getMessage()];
                }
            }
            $db->query("COMMIT");
        } catch (Throwable $e) {
            $db->query("ROLLBACK");
            echo json_encode(['ok' => false, 'error' => 'Import failed: ' . $e->getMessage()]);
            exit;
        }

        echo json_encode(['ok' => true, 'counts' => $counts]);
        exit;

```

The existing `} elseif ($action === 'delete_product') {` line follows directly after this insertion.

### Step 2.3: Smoke-test the preview handler from the host

The handlers live behind admin auth, so the easiest end-to-end smoke is through the browser (Task 5). For a fast sanity check now, run the parser directly via the test runner (already done) — the handlers are thin wrappers around it.

- [ ] **Step 2.3: Syntax-check the modified file**

```bash
docker compose exec vm-emb-sites php -l /var/www/html/vm-admin/routes/page.products.php
```

Expected: `No syntax errors detected in /var/www/html/vm-admin/routes/page.products.php`.

If Docker isn't running, use local PHP if available: `php -l vm-admin/routes/page.products.php`.

### Step 2.4: Commit

- [ ] **Step 2.4: Commit**

```bash
git add vm-admin/routes/page.products.php
git commit -m "feat: add preview/commit POST handlers for Shopify import

Wraps the new shopify_csv_parser module behind two POST actions on
the admin Products page. Preview classifies each row as
insert/update/skip without writing. Commit auto-creates categories
from the Shopify Type column, matches products by case-insensitive
name, and runs inside a single transaction."
```

---

## Task 3: Add the "Import from Shopify" button and modal markup

**Files:**
- Modify: `vm-admin/routes/page.products.php` (page title row near line 83, and modal block before the closing `<script>` tag near line 374)

### Step 3.1: Add the import button next to "Add Product"

- [ ] **Step 3.1: Replace the page title row's button**

Find the existing block around line 83:

```php
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-500 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-200 font-medium text-sm shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30">
                        <i class="bi bi-plus-lg"></i> Add Product
                    </button>
```

Replace it with this two-button group:

```php
                    <div class="flex items-center gap-2">
                        <button onclick="openImportModal()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-200 font-medium text-sm border border-white/5">
                            <i class="bi bi-cloud-upload"></i> Import from Shopify
                        </button>
                        <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-500 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-200 font-medium text-sm shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30">
                            <i class="bi bi-plus-lg"></i> Add Product
                        </button>
                    </div>
```

### Step 3.2: Add the import modal markup

- [ ] **Step 3.2: Insert before the existing `<script>` tag**

Find the closing `</div>` of the existing product modal (near line 374, the line `        </div>` that closes `<div id="productModal" ...>`), then immediately after it (still before `<script>`), insert:

```php

        <!-- Shopify Import Modal -->
        <div id="importModal" class="fixed inset-0 z-50 hidden" aria-labelledby="import-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div id="importBackdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeImportModal()"></div>

                <div id="importPanel" class="relative w-full max-w-3xl bg-gray-800 rounded-2xl shadow-2xl shadow-black/40 border border-white/10 transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col">

                    <!-- Modal Header -->
                    <div class="flex justify-between items-center px-6 pt-6 pb-4 border-b border-white/5">
                        <div>
                            <h3 class="text-lg font-semibold text-white" id="import-modal-title">Import from Shopify</h3>
                            <p class="text-xs text-gray-500 mt-0.5" id="importSubtitle">Upload a Shopify products CSV export</p>
                        </div>
                        <button type="button" onclick="closeImportModal()" class="h-8 w-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <!-- State: Upload -->
                    <div id="importStateUpload" class="px-6 py-8">
                        <label id="importDropzone" class="relative block cursor-pointer">
                            <div class="flex flex-col items-center justify-center gap-3 bg-gray-700/30 border-2 border-dashed border-white/10 rounded-xl px-6 py-12 text-gray-400 hover:border-purple-500/50 hover:text-gray-300 hover:bg-gray-700/50 transition-all duration-200">
                                <i class="bi bi-cloud-arrow-up text-4xl"></i>
                                <div class="text-center">
                                    <p class="text-sm font-medium">Drop your Shopify products CSV here</p>
                                    <p class="text-xs text-gray-500 mt-1">or click to browse &middot; max 20 MB</p>
                                </div>
                            </div>
                            <input type="file" id="importFileInput" accept=".csv,text/csv" class="hidden">
                        </label>
                        <div id="importUploadError" class="hidden mt-3 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-300"></div>
                    </div>

                    <!-- State: Preview -->
                    <div id="importStatePreview" class="hidden flex-1 flex flex-col min-h-0">
                        <div class="px-6 pt-4 pb-3 border-b border-white/5">
                            <div class="flex flex-wrap gap-2" id="importSummary">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-green-500/10 text-green-400 ring-1 ring-green-500/20 text-xs font-medium">
                                    <i class="bi bi-plus-circle"></i> <span id="importSummaryInsert">0</span> to insert
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-500/10 text-blue-400 ring-1 ring-blue-500/20 text-xs font-medium">
                                    <i class="bi bi-arrow-clockwise"></i> <span id="importSummaryUpdate">0</span> to update
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-500/10 text-gray-400 ring-1 ring-gray-500/20 text-xs font-medium">
                                    <i class="bi bi-slash-circle"></i> <span id="importSummarySkip">0</span> to skip
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 py-3" style="max-height: calc(90vh - 240px);">
                            <table class="w-full text-left text-xs text-gray-400">
                                <thead class="text-[10px] uppercase text-gray-500 tracking-wider">
                                    <tr>
                                        <th class="px-2 py-2 font-semibold">Action</th>
                                        <th class="px-2 py-2 font-semibold">Name</th>
                                        <th class="px-2 py-2 font-semibold">Category</th>
                                        <th class="px-2 py-2 font-semibold text-right">Price</th>
                                        <th class="px-2 py-2 font-semibold text-right">Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="importPreviewBody" class="divide-y divide-white/5"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- State: Result -->
                    <div id="importStateResult" class="hidden px-6 py-10 text-center">
                        <div class="h-14 w-14 mx-auto rounded-full bg-green-500/10 flex items-center justify-center mb-4">
                            <i class="bi bi-check-lg text-2xl text-green-400"></i>
                        </div>
                        <p class="text-white font-medium" id="importResultHeading">Import complete</p>
                        <p class="text-gray-500 text-sm mt-1" id="importResultDetail"></p>
                        <div id="importResultErrors" class="hidden mt-4 text-left bg-gray-900/50 border border-white/5 rounded-lg p-3 max-h-40 overflow-y-auto">
                            <p class="text-xs font-semibold text-gray-400 mb-2">Skipped rows</p>
                            <ul id="importResultErrorList" class="text-xs text-gray-500 space-y-1"></ul>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-white/5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <button type="button" id="importBackBtn" onclick="importGoToUpload()" class="hidden px-4 py-2.5 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-all duration-150">
                            Back
                        </button>
                        <button type="button" id="importCancelBtn" onclick="closeImportModal()" class="px-4 py-2.5 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-white/5 transition-all duration-150">
                            Cancel
                        </button>
                        <button type="button" id="importConfirmBtn" onclick="importConfirm()" class="hidden px-5 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-medium text-white shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30 transition-all duration-200">
                            <span id="importConfirmLabel">Confirm Import</span>
                        </button>
                        <button type="button" id="importDoneBtn" onclick="importFinish()" class="hidden px-5 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-medium text-white shadow-lg shadow-purple-600/20 hover:shadow-purple-500/30 transition-all duration-200">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
```

### Step 3.3: Syntax-check and commit

- [ ] **Step 3.3: Syntax-check**

```bash
docker compose exec vm-emb-sites php -l /var/www/html/vm-admin/routes/page.products.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3.4: Commit**

```bash
git add vm-admin/routes/page.products.php
git commit -m "feat: add Shopify import button and modal markup

Adds the 'Import from Shopify' button next to 'Add Product' and a
three-state modal (upload / preview / result) using the existing
Tailwind styling conventions from the products page. JS to drive
it lands in the next commit."
```

---

## Task 4: Wire up the modal JS (upload → preview → commit)

**Files:**
- Modify: `vm-admin/routes/page.products.php` (existing `<script>` tag near line 376, append new functions before the closing `</script>`)

### Step 4.1: Append the import-flow JS

- [ ] **Step 4.1: Edit `vm-admin/routes/page.products.php`**

Find the closing `</script>` tag near line 523 (after `if (stockFilter) stockFilter.addEventListener('change', filterProducts);`). Immediately **before** that `</script>` tag, insert:

```javascript

        // --- Shopify Import Modal ---
        const importMaxBytes = 20 * 1024 * 1024;
        let importFile = null;

        function importSwitchState(stateId) {
            ['importStateUpload', 'importStatePreview', 'importStateResult'].forEach(id => {
                document.getElementById(id).classList.toggle('hidden', id !== stateId);
            });
            document.getElementById('importBackBtn').classList.toggle('hidden', stateId !== 'importStatePreview');
            document.getElementById('importCancelBtn').classList.toggle('hidden', stateId === 'importStateResult');
            document.getElementById('importConfirmBtn').classList.toggle('hidden', stateId !== 'importStatePreview');
            document.getElementById('importDoneBtn').classList.toggle('hidden', stateId !== 'importStateResult');
        }

        function openImportModal() {
            const modal = document.getElementById('importModal');
            const backdrop = document.getElementById('importBackdrop');
            const panel = document.getElementById('importPanel');
            importResetUploadState();
            importSwitchState('importStateUpload');
            modal.classList.remove('hidden');
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            });
        }

        function closeImportModal() {
            const modal = document.getElementById('importModal');
            const backdrop = document.getElementById('importBackdrop');
            const panel = document.getElementById('importPanel');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        function importResetUploadState() {
            importFile = null;
            const input = document.getElementById('importFileInput');
            if (input) input.value = '';
            const err = document.getElementById('importUploadError');
            err.classList.add('hidden');
            err.textContent = '';
        }

        function importGoToUpload() {
            importResetUploadState();
            importSwitchState('importStateUpload');
        }

        function importShowUploadError(msg) {
            const err = document.getElementById('importUploadError');
            err.textContent = msg;
            err.classList.remove('hidden');
        }

        function importValidateFile(file) {
            if (!file) return 'No file selected';
            if (file.size > importMaxBytes) return 'File is larger than 20 MB';
            const name = (file.name || '').toLowerCase();
            if (!name.endsWith('.csv')) return 'Only .csv files are accepted';
            return null;
        }

        function importHandleFile(file) {
            const err = importValidateFile(file);
            if (err) { importShowUploadError(err); return; }
            importFile = file;
            importRequestPreview();
        }

        async function importRequestPreview() {
            const dz = document.getElementById('importDropzone');
            dz.classList.add('opacity-50', 'pointer-events-none');
            try {
                const fd = new FormData();
                fd.append('action', 'preview_shopify_import');
                fd.append('file', importFile);
                const res = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (!data.ok) { importShowUploadError(data.error || 'Failed to parse CSV'); return; }
                importRenderPreview(data);
                importSwitchState('importStatePreview');
            } catch (e) {
                importShowUploadError('Network error: ' + e.message);
            } finally {
                dz.classList.remove('opacity-50', 'pointer-events-none');
            }
        }

        function importRenderPreview(data) {
            document.getElementById('importSummaryInsert').textContent = data.summary.insert;
            document.getElementById('importSummaryUpdate').textContent = data.summary.update;
            document.getElementById('importSummarySkip').textContent = data.summary.skip;

            const body = document.getElementById('importPreviewBody');
            body.innerHTML = '';
            const badge = {
                insert: '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-500/10 text-green-400 ring-1 ring-green-500/20 text-[10px] font-semibold uppercase">Insert</span>',
                update: '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 ring-1 ring-blue-500/20 text-[10px] font-semibold uppercase">Update</span>',
                skip:   '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-500/10 text-gray-400 ring-1 ring-gray-500/20 text-[10px] font-semibold uppercase">Skip</span>',
            };
            const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            data.rows.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-2 py-1.5 align-top">${badge[r.action] || ''}</td>
                    <td class="px-2 py-1.5 align-top text-white">
                        ${esc(r.name)}
                        ${r.reason ? `<div class="text-[10px] text-gray-500 mt-0.5">${esc(r.reason)}</div>` : ''}
                    </td>
                    <td class="px-2 py-1.5 align-top">${r.category ? esc(r.category) : '<span class="text-gray-600 italic">—</span>'}</td>
                    <td class="px-2 py-1.5 align-top text-right tabular-nums">${r.price != null ? Number(r.price).toFixed(2) : '—'}</td>
                    <td class="px-2 py-1.5 align-top text-right tabular-nums">${r.stock != null ? r.stock : '—'}</td>
                `;
                body.appendChild(tr);
            });
        }

        async function importConfirm() {
            const btn = document.getElementById('importConfirmBtn');
            const label = document.getElementById('importConfirmLabel');
            btn.disabled = true;
            label.textContent = 'Importing…';
            try {
                const fd = new FormData();
                fd.append('action', 'commit_shopify_import');
                fd.append('file', importFile);
                const res = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (!data.ok) {
                    importShowUploadError(data.error || 'Import failed');
                    importSwitchState('importStateUpload');
                    return;
                }
                importRenderResult(data.counts);
                importSwitchState('importStateResult');
            } catch (e) {
                importShowUploadError('Network error: ' + e.message);
                importSwitchState('importStateUpload');
            } finally {
                btn.disabled = false;
                label.textContent = 'Confirm Import';
            }
        }

        function importRenderResult(counts) {
            const detail = `${counts.inserted} new, ${counts.updated} updated, ${counts.skipped} skipped`;
            document.getElementById('importResultDetail').textContent = detail;
            const errBlock = document.getElementById('importResultErrors');
            const errList = document.getElementById('importResultErrorList');
            errList.innerHTML = '';
            if (counts.errors && counts.errors.length > 0) {
                counts.errors.forEach(e => {
                    const li = document.createElement('li');
                    li.textContent = `${e.name}: ${e.reason}`;
                    errList.appendChild(li);
                });
                errBlock.classList.remove('hidden');
            } else {
                errBlock.classList.add('hidden');
            }
        }

        function importFinish() {
            window.location.reload();
        }

        // Wire up file input + drag-and-drop on the dropzone.
        (function importInit() {
            const input = document.getElementById('importFileInput');
            const dz = document.getElementById('importDropzone');
            if (input) {
                input.addEventListener('change', e => {
                    const f = e.target.files && e.target.files[0];
                    if (f) importHandleFile(f);
                });
            }
            if (dz) {
                ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault(); e.stopPropagation();
                    dz.classList.add('ring-2', 'ring-purple-500/40');
                }));
                ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault(); e.stopPropagation();
                    dz.classList.remove('ring-2', 'ring-purple-500/40');
                }));
                dz.addEventListener('drop', e => {
                    const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                    if (f) importHandleFile(f);
                });
            }
        })();
```

### Step 4.2: Commit

- [ ] **Step 4.2: Commit**

```bash
git add vm-admin/routes/page.products.php
git commit -m "feat: wire up Shopify import modal JS (upload/preview/commit)

Adds file validation, drag-and-drop, fetch-based preview, and commit
flow with a result screen. File is held in JS between preview and
commit so the server keeps no temp state."
```

---

## Task 5: Manual end-to-end smoke test

**Files:** none (verification only)

### Step 5.1: Start the app and log in

- [ ] **Step 5.1: Start the stack**

```bash
docker compose up -d
```

Open `http://localhost:8016` in a browser. Log in and navigate to your store's Products page: `http://localhost:8016/vm-admin/<your-domain>/products`.

### Step 5.2: Happy path

- [ ] **Step 5.2: Import the sample fixture**

1. Click **Import from Shopify**.
2. Drag `tests/fixtures/shopify_products_sample.csv` from your file manager into the dropzone (or click and pick it).
3. Expect to land in the preview state with summary roughly: **5 to insert, 0 to update, 1 to skip** (the bad-price row is skipped).
4. The preview table should show:
   - `Classic Tee - Small / Red` — Insert — Apparel — 19.99 — 5
   - `Classic Tee - Small / Blue` — Insert — Apparel — 19.99 — 8
   - `Classic Tee - Large / Red` — Insert — Apparel — 21.99 — 3
   - `Coffee Mug` — Insert — Drinkware — 9.50 — 12
   - `Mystery Item` — Insert — — (empty) — 5.00 — 1
   - `Broken Product` — Skip — Apparel — 0.00 — 2 — reason "Variant Price is not numeric"
5. Click **Confirm Import**.
6. Result state shows "5 new, 0 updated, 1 skipped". Click **Done**.
7. The products table now contains the 5 new rows. The categories table now contains `Apparel` and `Drinkware` if they weren't already present.

### Step 5.3: Re-import to verify update-by-name

- [ ] **Step 5.3: Run the same import again**

1. Open the import modal, pick the same fixture.
2. Preview should now show: **0 to insert, 5 to update, 1 to skip**.
3. Confirm. Result shows "0 new, 5 updated, 1 skipped".

### Step 5.4: Error case

- [ ] **Step 5.4: Try the missing-columns fixture**

1. Open the import modal, pick `tests/fixtures/shopify_missing_columns.csv`.
2. Red error banner appears on the upload state: "Missing required column: Variant Price".
3. Modal does not transition to preview.

### Step 5.5: File-size guard

- [ ] **Step 5.5: Try a non-CSV file**

1. Open the import modal, try uploading any `.txt` file from your system.
2. Red error: "Only .csv files are accepted". No network request made (check DevTools Network tab).

### Step 5.6: Final commit (if any tweaks were needed)

- [ ] **Step 5.6: Wrap up**

If smoke tests revealed any issues you fixed inline, commit them:

```bash
git status   # confirm what changed
git add -p
git commit -m "fix: <describe issue found during smoke test>"
```

If everything worked first-try, no commit needed.

---

## Verification checklist

Before declaring the feature done, confirm:

- [ ] `php tests/shopify_csv_parser_test.php` exits 0 with all PASS.
- [ ] `docker compose exec vm-emb-sites php -l /var/www/html/vm-admin/routes/page.products.php` reports no syntax errors.
- [ ] Happy-path smoke test (Step 5.2) succeeded — 5 products created with expected names, prices, categories.
- [ ] Re-import smoke test (Step 5.3) showed all 5 as `update`, not duplicates.
- [ ] Missing-column CSV produces a clear error message in the modal.
- [ ] Auto-created categories appear in the Categories page dropdown afterwards.
