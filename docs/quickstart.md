# Quick start

This guide walks you from `git clone` to a running admin panel with a
demo store and seed products in under five minutes.

## Prerequisites

- Docker + Docker Compose
- A free TCP port on 8016 (or edit `docker-compose.yml` to remap)

A local PHP install isn't required for development — everything runs
inside the container.

## Start the stack

```bash
git clone https://github.com/varsitymarket-technologies/vm.embedded.store.git
cd vm.embedded.store
docker compose up -d
```

The stack runs Apache + PHP 8.3 from the `sigblue/apache-php:83-full`
image. The project root is mounted into the container at
`/var/www/html/public/`. Open `http://localhost:8016/`.

## Demo mode (fastest path)

Click **Demo Account** on the landing page. The engine creates a
disposable account and drops you partway through the store-setup
wizard. Two paths from here:

- **Walk the wizard** — Store Identity → Domain Setup → Business
  Profile → Launch. Click **Launch Store** at step 4 to provision
  the per-site DB and land in the admin panel.
- **Pick a previously-seeded store directly** — navigate to
  `/vm-admin/<domain>/` for any site under the `sites/` directory.
  The demo accounts already wired during prior sessions include
  `claude.test`, `something.less`, `debug.com`. The session is shared
  across these in demo mode.

## Run the admin panel

Once a store exists, the admin lives at:

```
http://localhost:8016/vm-admin/<your-domain>/
```

Top-level admin sections: Dashboard, Overview, Analytics, Products,
Categories, Discounts, Flash Sales, Orders, Payments, Delivery,
Logistics, Themes, Page Builder, Publish, Customers, Forms, Settings.
See [admin-features.md](admin-features.md) for the tour.

## Where data lives

Three storage tiers, each its own SQLite file:

| File | What's in it |
|---|---|
| `build/vm.engine.sql` | Main engine DB. Accounts, banking, auth, list of sites (`sys_account`, `sys_banking`, `sys_auth`, `sys_websites`). |
| `sites/<domain>/storage.data` | Per-site storefront data: products, categories, orders, sales, settings, customers, customer_sessions, customer_addresses. One per store. |
| `data/<sha256(domain)>/<domain>` | Private per-store DB outside the web root. API keys + API logs. |

The `data/` directory is created automatically on first use. If your
deploy environment doesn't allow writing to `../data/` (one level
above the project root), the engine falls back to `build/data/`.

## Reset the database

A wholesale reset wipes the engine DB and rebuilds the schema. Useful
for clean integration tests or after schema migrations:

```bash
docker compose exec vm-emb-sites php /var/www/html/public/services/sys.database.php
```

To preserve data with backup/restore:

```bash
# Snapshot
docker compose exec vm-emb-sites php /var/www/html/public/services/sys.database.backup.php

# Restore from the latest snapshot
docker compose exec vm-emb-sites php /var/www/html/public/services/sys.database.restore.php

# Hard reboot (resets state + restarts session)
docker compose exec vm-emb-sites php /var/www/html/public/services/sys.database.reboot.php
```

## Apply schema to existing per-site DBs

When new tables ship (e.g. `customers`, `customer_sessions`,
`customer_addresses`), pre-existing site DBs need
`services/database.install.php` re-run against each. The script is
idempotent — `CREATE TABLE IF NOT EXISTS` and PRAGMA-guarded ALTERs
mean it's safe to re-run on every site:

```bash
docker compose exec vm-emb-sites php -r '
define("__ANCHOR_SITE__", "your-domain.com");
require_once "/var/www/html/public/services/database.install.php";
echo "schema applied\n";
'
```

## Running tests

```bash
docker compose exec vm-emb-sites php /var/www/html/public/tests/customer_auth_test.php
docker compose exec vm-emb-sites php /var/www/html/public/tests/customer_account_test.php
docker compose exec vm-emb-sites php /var/www/html/public/tests/shopify_csv_parser_test.php
```

Each suite uses an `eq($expected, $actual, $msg)` helper and exits 0
on green, 1 on first failure. Test temp files land under `tests/tmp/`
and are cleaned up on success.

## Next reads

- [Architecture](architecture.md) — request flow, theme engine,
  module boundaries.
- [API reference](api.md) — every public storefront endpoint.
- [Admin features](admin-features.md) — what each admin section does.
