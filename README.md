<div align="center">
  <img src="https://avatars.githubusercontent.com/u/219999828?s=400&u=2166fd2a4b7e592c0f1e9893a34aeb1105bc6bea&v=4" width="120px" alt="Varsity Market Logo">
  <h1>Varsity Market: Embedded Store Engine</h1>
  <p>A modular PHP-based commerce engine for themeable, portable storefronts.</p>

  [![PWA Ready](https://img.shields.io/badge/PWA-Ready-success?style=for-the-badge&logo=pwa)](https://web.dev/progressive-web-apps/)
  [![Docker](https://img.shields.io/badge/Docker-Compose-blue?style=for-the-badge&logo=docker)](docker-compose.yml)
  [![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)](LICENSE)
</div>

---

## Overview

Varsity Market is a self-hostable commerce engine that lets a single
operator run many themeable storefronts from one installation. Each
store gets its own SQLite database, a customizable theme from the
shared theme library, and a public REST API for headless clients. The
engine supports embedded deployment via iframe, hosted URLs, or
exported source — pick what fits your distribution.

It is small enough to read end-to-end and modular enough to extend
without forking. There is no build step for the storefront — themes
are vanilla HTML/JS that talk to the engine's API.

## Quick start

```bash
git clone https://github.com/varsitymarket-technologies/vm.embedded.store.git
cd vm.embedded.store
docker compose up -d
# App available at http://localhost:8016 — click "Demo Account"
# to skip the wizard and land on a pre-seeded admin panel.
```

Need more detail (wizard walkthrough, where data lives, reset
commands)? See [docs/quickstart.md](docs/quickstart.md).

## Features

- **26+ themes out of the box** — full client-side SPAs with cart,
  product pages, checkout, and dashboard. Synced from the
  [embedded-themes](https://github.com/varsitymarket-technologies/embedded-themes)
  repository on each request via hash-based change detection.
- **Custom theme upload** — drop an HTML file into the admin and it
  becomes your active storefront. Edit further in the Page Builder.
- **Shopify CSV import** — drop a Shopify products export and a
  preview shows which rows will insert/update/skip. Variants become
  individual products; categories auto-create from the Shopify Type
  column.
- **Customer accounts** — per-site customer DB with bearer-token
  auth via `X-Customer-Token`. Endpoints for register/login/logout,
  profile updates, password change, order history, and a reusable
  address book.
- **Public store API** — `/store-access/{store_id}/?state=...` with
  store-level API keys. Headless clients (themes, mobile apps,
  external sites) consume the same surface.
- **Admin panel** — products, categories, orders, discounts, themes,
  page builder, deploy, payments, forms, settings, analytics. All
  under `/vm-admin/{domain}/`.
- **PWA mode** — service worker (`sw.js`) with offline support and
  install prompt.
- **Zero external dependencies** — SQLite for storage, vanilla JS
  for the storefront, no composer install required.

## Documentation

| Doc | What you'll find |
|---|---|
| [Quick start](docs/quickstart.md) | Docker setup, demo mode, first store walkthrough, data layout. |
| [Architecture](docs/architecture.md) | Request flow, storage layers, theme engine, module boundaries. |
| [API reference](docs/api.md) | Every `state=` endpoint with request/response shapes, auth model, status codes. |
| [Admin features](docs/admin-features.md) | Tour of every admin section with the workflows they support. |

Specs and implementation plans for individual features live under
[docs/superpowers/specs/](docs/superpowers/specs/) and
[docs/superpowers/plans/](docs/superpowers/plans/).

## Project structure

```text
├── api/                  Public store API (state= router + SDK)
├── app/                  PWA client-side resources
├── build/                Main engine SQLite DB + scratch logs
├── docs/                 Documentation (this README links into here)
├── module/               Core PHP modules (db, customer auth + account,
│                         shopify CSV parser, github sync)
├── pages/                Public-facing pages (auth, payments, exports)
├── services/             CLI maintenance scripts + install
├── sites/                Per-site DBs and config (one dir per store)
├── skel/                 Theme runtime (vm.theme.js, vm.api.js)
├── skin/                 Shared admin CSS
├── tests/                Standalone PHP test runners
├── themes/               Theme library (cloned + synced from remote)
├── vm-admin/             Admin panel (routing + per-page handlers)
├── docker-compose.yml    Local dev stack
└── index.php             Front controller — all routes start here
```

## Testing

There is no PHP test framework installed. Tests are standalone PHP
scripts under `tests/` that use a tiny `eq($expected, $actual, $msg)`
helper. Run a single suite from inside the container:

```bash
docker compose exec vm-emb-sites php /var/www/html/public/tests/customer_auth_test.php
docker compose exec vm-emb-sites php /var/www/html/public/tests/customer_account_test.php
docker compose exec vm-emb-sites php /var/www/html/public/tests/shopify_csv_parser_test.php
```

Each suite exits 0 when green, 1 on the first failure.

## Maintenance commands

| Command | Description |
| :--- | :--- |
| `php services/sys.database.php` | Initialize or reset the core database. |
| `php services/sys.database.backup.php` | Generate a timestamped backup of the database. |
| `php services/sys.database.reboot.php` | Perform a clean system reboot and state reset. |
| `php services/sys.database.restore.php` | Restore the database from the latest backup. |

---

**Varsity Market Technologies**
