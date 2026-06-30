# Admin features

A tour of every admin section under `/vm-admin/<domain>/`. Auth and
per-store ownership are gated in `vm-admin/routes.php` — an operator
can only see admin pages for sites they own.

## Dashboard / Overview / Analytics

- **Dashboard** (`/home/`) — Operator-level landing page. Quick links
  to the admin panel for each store you own; new-feature highlights;
  developer settings.
- **Overview** (`/vm-admin/<domain>/`) — Per-store summary: pending
  order count, recent activity, quick links to common tasks.
- **Analytics** (`/vm-admin/<domain>/analytics`) — Real-time
  dashboard: page views, visitors, referrers, device breakdown.
  Tracks via a lightweight pixel that writes into the per-site DB.

## Catalog

### Products (`/products`)

Inventory CRUD with stat cards (total, in stock, out of stock, total
value), search, category filter, stock filter. Add Product opens a
modal with name/description/price/stock/image/category. Image upload
supports drag-drop, file picker, or pasted URL.

**Import from Shopify** — a second header button opens a 3-state
modal:

1. **Upload** — drop a Shopify products export CSV (≤ 20 MB).
2. **Preview** — table classifying each row as Insert / Update /
   Skip. Update matches by case-insensitive name. Variants become
   individual products (e.g. "T-Shirt - Small / Red"). Skipped rows
   show a reason ("Variant Price is not numeric", etc.).
3. **Result** — counts and a list of skipped rows.

Categories auto-create from the Shopify `Type` column. Image URLs
are stored as-is (no rehosting). All writes wrapped in a transaction.

Spec: [docs/superpowers/specs/2026-06-03-shopify-csv-import-design.md](superpowers/specs/2026-06-03-shopify-csv-import-design.md).

### Categories (`/categories`)

Category CRUD with product counts. Deleting a category first
detaches its products by setting `category_id = NULL`, then deletes
the row — required since `PRAGMA foreign_keys = ON` is now enabled
on every connection.

### Discounts (`/discounts`)

Manage discount codes (name, percentage, active flag).

### Flash Sales (`/sales`)

Time-bound sale campaigns.

## Orders & Fulfillment

### Orders (`/orders`)

Order list with status filters. Each order shows customer info,
items (decoded from the `items` JSON column), and total. Pending
orders get a badge in the sidebar.

### Payments (`/payments`)

Configure banking/payout destinations and view payment status.

### Delivery (`/delivery`)

Shipping configuration per store.

### Logistics (`/logistics`)

Logistics zone management and integrations.

## Website

### Themes (`/theme`)

Theme picker grid (typically 26+ themes synced from
`embedded-themes`). Filter pills (All / Standard / Premium),
search, hover preview, click Activate to flip the active theme.

A header button labeled **Upload Custom Theme** (or **Manage Custom
Theme** when one already exists) opens a modal:

- If no custom file: drop zone with file preview + Import & Activate.
- If a custom file exists: management card with Active badge,
  Activate (if not currently active), Edit in Builder, and Remove
  actions, plus a drop zone below for replacement uploads.

Custom themes land at `sites/<domain>/builder.cache.html`. The
`__custom__` theme marker tells the front controller to render it
as the storefront.

Spec: [docs/superpowers/specs/2026-06-03-custom-theme-upload-modal-design.md](superpowers/specs/2026-06-03-custom-theme-upload-modal-design.md).

### Page Builder (`/builder`)

Edit the active theme (or custom HTML file) in a built-in WYSIWYG
editor. Saves back into the same `builder.cache.html` so reloads
pick up changes immediately.

### Publish (`/deploy`)

Deploy the current store via the GitHub integration. Exports the
site files, pushes to a configured repo, optionally triggers a
hosting pipeline.

## System

### Customers (`/users`)

Browse the per-store customer table (sub-project A). Read-only — the
admin doesn't impersonate or alter customer credentials. Customer
self-service (profile updates, password change, address book) is via
the public store API endpoints documented in
[api.md](api.md).

### Forms (`/forms`)

Manage contact / lead-capture forms.

### Settings (`/settings`)

Tabbed configuration:

- **General** — store name, currency, contact info.
- **Banking** — payout destinations and account details.
- **Email** — SMTP host/port/user/pass + notification template
  (with live preview iframe). Send path itself isn't wired yet —
  noted in the customer-auth spec as a future addition.
- **Domains** — CORS whitelist for the public store API.
- **Developer** — API key management (generate, revoke, view audit
  log).

## Common patterns

- **Modals** use the same Tailwind chrome across admin pages
  (backdrop blur + scale-in animation + max-w panel + escape/X to
  close).
- **Form submission** uses plain HTML POSTs that redirect via
  `header("Location: ...")` and reload the page. Fetch-based AJAX
  is used only when the operation needs a multi-step UI flow
  (Shopify import preview, customer theme upload preview).
- **JSON endpoints in admin** must call `while (ob_get_level() > 0)
  { ob_end_clean(); }` before responding, because `interface.php`
  pre-buffers the entire sidebar HTML.

## Where to read next

- [API reference](api.md) — the storefront-facing surface.
- [Architecture](architecture.md) — how routing reaches the admin.
- [Quick start](quickstart.md) — get the admin running locally.
