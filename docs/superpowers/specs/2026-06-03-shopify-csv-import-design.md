# Shopify CSV Import — Design

**Date:** 2026-06-03
**Status:** Approved, ready for implementation plan
**Owner:** keenan@doneros.co

## Summary

Add a Shopify product CSV importer to the admin Products page. A user clicks
"Import from Shopify", drops their Shopify products export CSV, sees a preview
of what will be inserted/updated/skipped, and confirms to commit. Categories
are auto-created from the Shopify `Type` column. Each Shopify variant becomes
its own row in our flat `products` table. Existing products with matching
names are updated; new ones are inserted.

## Goals

- Let a store owner bulk-load products from a Shopify export with zero column
  mapping required.
- Make the operation safe to re-run: matching by name updates rather than
  duplicates.
- Preserve Shopify's variant granularity (size, color) by emitting one product
  per variant.
- Keep the import path synchronous and self-contained — no background jobs,
  no temp-file state on the server, no schema changes.

## Non-goals

- No background or queued imports.
- No column-mapping UI — Shopify's CSV headers are stable.
- No image downloading or rehosting — image URLs are stored as-is.
- No re-import history or undo.
- No CSV export.
- No support for non-Shopify CSV formats.

## User flow

1. User navigates to `/vm-admin/{domain}/products`.
2. Next to the existing **Add Product** button, a new **Import from Shopify**
   button is visible.
3. Click opens a modal in **Upload state**: drag-and-drop area + file picker
   fallback, accepts `.csv` only, rejects files larger than ~20 MB in JS.
4. On file selection, JS POSTs the file via `fetch()` to
   `action=preview_shopify_import`. The server parses, classifies each row,
   and returns JSON.
5. Modal transitions to **Preview state**:
   - Summary banner: counts for Insert / Update / Skip.
   - Scrollable table of normalized rows: name, category, price, stock,
     thumbnail, and a per-row badge (Insert / Update / Skip + reason).
   - **Confirm Import** and **Cancel** buttons.
6. On **Confirm Import**, JS re-POSTs the same `File` object (still held in
   memory) to `action=commit_shopify_import`. The server re-parses and commits.
7. Modal transitions to **Result state**: "Imported X new, updated Y. Z skipped."
   followed by a **Done** button that reloads the page so the products table
   reflects the new state.

Re-sending the file on confirm avoids server-side session or temp-file state.
The browser keeps the `File` reference between steps.

## Component split

- **`vm-admin/routes/page.products.php`** *(edit)* — adds the
  "Import from Shopify" button, the import modal markup, the upload/preview/commit
  JS, and the two new POST action handlers (`preview_shopify_import`,
  `commit_shopify_import`).
- **`module/shopify_csv_parser.php`** *(new)* — pure function
  `parse_shopify_csv(string $filePath): array` that returns normalized rows.
  Isolated so it can be unit-tested and reused if a CLI importer is added later.
- **No schema changes.** Works against the existing `products` and `categories`
  tables in the per-site `sites/{domain}/storage.data` DB.

## Parser logic (`shopify_csv_parser.php`)

Shopify exports place one product across multiple rows that share a `Handle`.
The parser groups by Handle, then emits **one normalized product per variant
row**.

### Required headers
`Handle`, `Title`, `Variant Price`. If any are missing, the parser returns
`["ok" => false, "error" => "Missing required column: ..."]` and the modal
shows a red banner.

### Optional headers used
`Body (HTML)`, `Variant Inventory Qty`, `Image Src`, `Variant Image`, `Type`,
`Option1 Value`, `Option2 Value`, `Option3 Value`.

### Per-handle processing

```text
For each Handle group (rows preserved in CSV order):
  base_title = first non-empty Title in group
  base_body  = strip_tags(first non-empty "Body (HTML)" in group)
  base_type  = first non-empty Type in group
  base_image = first non-empty "Image Src" in group

  For each row in group where "Variant Price" is non-empty:
    suffix_parts = filter_non_empty([Option1 Value, Option2 Value, Option3 Value])
    suffix       = trim(join(" / ", suffix_parts))
    if suffix == "Default Title": suffix = ""

    name        = base_title + (suffix ? " - " + suffix : "")
    image       = row["Variant Image"] || base_image || ""
    raw_price   = row["Variant Price"]
    raw_stock   = row["Variant Inventory Qty"]

    parse_error = null
    if not is_numeric(raw_price):  parse_error = "Variant Price is not numeric"
    elif base_title is empty:      parse_error = "Title is empty"

    emit {
      name, description: base_body, category: base_type, image,
      price: (float) raw_price,
      stock: is_numeric(raw_stock) ? (int) raw_stock : 0,
      parse_error
    }
```

Rows with an empty Variant Price are skipped silently — they are the extra-
image rows Shopify emits, not products. Rows where `parse_error` is non-null
are still emitted but flagged so the preview and commit phases can render
them as `skip` with the reason text.

### Edge cases handled by the parser
- UTF-8 BOM at file start is stripped.
- Missing optional columns are treated as empty.
- "Default Title" is Shopify's placeholder for single-variant products — the
  suffix is dropped so the imported name doesn't become `T-Shirt - Default Title`.
- `name` longer than 255 chars is trimmed.
- Empty `base_type` → `category` is the empty string (commit phase will leave
  `category_id` as `NULL`).
- Quoted fields with embedded commas/newlines: handled by PHP's `fgetcsv`.

## Commit logic (`page.products.php` POST handler)

`action=commit_shopify_import` re-runs the parser on the uploaded file, then
processes the normalized rows inside a single transaction.

```text
Begin transaction.
counts = { inserted: 0, updated: 0, skipped: 0, errors: [] }

For each normalized row:
  if row.parse_error is non-null:
    counts.skipped += 1
    counts.errors.push({ name: row.name, reason: row.parse_error })
    continue

  try:
    # Resolve category
    if row.category is non-empty:
      existing = SELECT id FROM categories WHERE LOWER(name) = LOWER(:cat) LIMIT 1
      category_id = existing.id || (INSERT INTO categories (name) ...).lastInsertId
    else:
      category_id = NULL

    # Match product by name (case-insensitive)
    existing = SELECT id FROM products WHERE LOWER(name) = LOWER(:name) LIMIT 1
    if existing:
      UPDATE products
         SET description = :desc, price = :price, stock = :stock,
             image = :img, category_id = :cat_id
       WHERE id = existing.id
      counts.updated += 1
    else:
      INSERT INTO products (name, description, price, stock, image, category_id) VALUES (...)
      counts.inserted += 1
  except per-row error:
    counts.skipped += 1
    counts.errors.push({ name: row.name, reason: "..." })

Commit transaction.
Respond JSON: { ok: true, counts: counts }
```

If a fatal error (DB connection lost, etc.) is thrown outside per-row handling,
the transaction is rolled back and the response is
`{ ok: false, error: "..." }`.

## Preview logic (`page.products.php` POST handler)

`action=preview_shopify_import` runs the same parser, then for **each
normalized row** does read-only lookups (no writes) to classify it:

- If `parse_error` is non-null → `skip` with reason from the parser.
- Else if a product with the same name (case-insensitive) exists → `update`.
- Else → `insert`.

The response is:

```json
{
  "ok": true,
  "summary": { "insert": 12, "update": 4, "skip": 1 },
  "rows": [
    { "action": "insert", "name": "...", "category": "Apparel", "price": 19.99, "stock": 12, "image": "https://...", "reason": null },
    { "action": "update", "name": "...", "category": "Apparel", "price": 24.99, "stock": 8,  "image": "https://...", "reason": null },
    { "action": "skip",   "name": "...", "category": "",        "price": null,  "stock": null, "image": "", "reason": "Variant Price is not a number" }
  ]
}
```

Preview never writes to the database. It is purely a dry run that displays in
the modal before the user confirms.

## Modal states (UI)

The modal has three states swapped by toggling CSS classes on inner containers.

**1. Upload state**
- Drag-and-drop zone + hidden `<input type="file" accept=".csv">`.
- Short helper text: "Drop your Shopify products export CSV here, or click to
  browse."
- Client-side guards: file size ≤ 20 MB, extension is `.csv`.

**2. Preview state**
- Summary banner (3 pill counts: green Insert, blue Update, gray Skip).
- Sticky-header scrollable table of rows with per-row action badges.
- **Back** (returns to Upload state) and **Confirm Import** buttons.
- Error banner if the parser returned `ok: false`.

**3. Result state**
- Success summary: "Imported X new, updated Y. Z rows skipped."
- Collapsible list of skipped rows + reasons (if any).
- **Done** button reloads the page so the products table reflects new data.

Modal styling reuses the existing Tailwind classes from the Add Product modal.

## Error handling

| Stage          | Failure mode                                  | Behavior                                                       |
|----------------|-----------------------------------------------|----------------------------------------------------------------|
| Client upload  | File >20 MB or non-CSV extension              | JS rejects with inline message; no request sent                |
| Preview parse  | Missing required header (`Handle`/`Title`/`Variant Price`) | Server returns `{ok:false,error:...}`; modal shows red banner  |
| Preview parse  | Empty file / zero rows                        | Server returns `{ok:false,error:"No rows found"}`              |
| Preview parse  | Per-row validation fail (bad price, etc.)     | Row appears in preview as `skip` with reason                   |
| Commit         | Per-row DB error                              | Row counted as skipped; reason stored; loop continues          |
| Commit         | Fatal error (transaction can't commit)        | Transaction rolled back; response `{ok:false,error:...}`       |

## Security

- The CSV upload accepts only files with extension `.csv` and size ≤ 20 MB,
  enforced both client-side and server-side.
- Uploaded files are read once via `fgetcsv` from the PHP-managed
  `$_FILES['file']['tmp_name']` path. They are never moved into a webroot-
  accessible directory.
- All DB writes go through the existing parameterized `database_manager::query`
  method — no string concatenation.
- The route is reached only via the existing `vm-admin` auth + domain-ownership
  check in `vm-admin/routes.php`, so only the store owner can import.
- HTML in `Body (HTML)` is stripped with `strip_tags()` before storage to
  avoid stored XSS when descriptions are later rendered.
- Image URLs are stored as plain text and rendered via `htmlspecialchars` in
  the existing products table — same risk surface as the manual Add Product
  flow.

## Open questions

None. All design decisions resolved during brainstorming on 2026-06-03.
