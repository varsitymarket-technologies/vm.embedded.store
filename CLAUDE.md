# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Varsity Market Embedded Store Engine — a modular PHP-based commerce engine for deploying themeable, portable storefronts. Supports embedded deployment via iFrame, direct source export, and standalone PWA mode.

## Tech Stack

- **Backend**: PHP 7.4+ with SQLite 3 (via PDO)
- **Frontend**: Vanilla JavaScript (ES6+), CSS3, Font Awesome icons, Google Fonts (Inter)
- **Database**: SQLite 3 — main DB at `build/vm.engine.sql`, per-site DBs at `sites/{domain}/storage.data`
- **Server**: Apache/Nginx with Docker Compose (container: `vm-emb-sites`, port 8016→80, image: `sigblue/apache-php:83-full`)
- **PWA**: Service Worker (`sw.js`) with offline support and install prompt

## Running Locally

```bash
# Start with Docker
docker-compose up -d
# App available at http://localhost:8016

# Initialize/reset the database
php services/sys.database.php

# Database backup/restore
php services/sys.database.backup.php
php services/sys.database.restore.php
php services/sys.database.reboot.php
```

The Docker volume mounts `../data/` as `/var/www/html/data/` — the `.env` file is loaded from either `../data/.env` or the project root `.env`.

## Architecture

### Request Flow

`index.php` is the single entry point. It starts a session, includes `.register.php` (database bootstrap + theme sync), then `config.php` (defines global constants from DB). Routing uses `ex()` to parse URL segments from `REQUEST_URI`.

Top-level routes: `home`, `auth`, `payments`, `theme`, `export-frame`, `export-link`, `export-source` — mapped in `scripts.php:map()` to page files in `/pages/`.

Special routes bypass normal flow:
- `/apk` → `apk/index.php`
- `/sync-github` → `module/github.php`
- `/app` → `app/index.php` (PWA client)
- `/vm-admin` → `vm-admin/index.php` (admin panel)

### Admin Panel (`/vm-admin`)

Separate routing via `vm-admin/routes.php` using URL segment `ex(3)`. Routes include: products, categories, users, discounts, sales, delivery, logistics, orders, builder, settings, analytics, theme, deploy, payments, forms. Auth check redirects to `page.session-expired.php` if session is invalid.

Admin URL pattern: `/vm-admin/{domain}/{page}`

### Database Layer

`module/database.php` provides `database_manager` class — a PDO-based SQLite wrapper with `createTable()`, `executeSql()`, and `query()` (parameterized) methods.

Two database instances are initialized as global constants:
- `__DB_MODULE__` — main engine DB (`build/vm.engine.sql`) with tables: `sys_account`, `sys_banking`, `sys_auth`, `sys_websites`
- `__DB_WEBSITE__` — per-site DB (`sites/{domain}/storage.data`)

### Authentication

Session-based auth using AES-256-CBC encryption. Session stores encrypted account index in `$_SESSION['vm_index']` and key in `$_SESSION['vm_key']`. GitHub OAuth used for deployment integration.

### Themes

Themes live in `/themes/` (gitignored). Synced from remote GitHub repo (`varsitymarket-technologies/embedded-themes`) via `.register.php` on each request, using hash-based change detection. Theme templates use `e(__CONSTANT__)` pattern for variable interpolation.

### Deployment/Export

Three export modes in `/services/`: frame (iFrame embed), link (hosted URL), source (code download). Site skeletons in `/skel/` provide base JS API (`vm.api.js`) and theme engine (`vm.theme.js`).

## Key Constants (defined in config.php)

`__ACCOUNT_INDEX__`, `__USERNAME__`, `__DOMAIN__`, `__WEBSITE_DOMAIN__`, `__WEBSITE_THEME__`, `__WEBSITE_URL__`, `__WEBSITE_FRAME__`, `__CURRENCY_SIGN__`, `__WALLET_AMOUNT__`, `__WALLET_PERCENTAGE__`, `__BANKING_ACCOUNT_NUMBER__`, `__BANKING_ACCOUNT_TYPE__`, `__BANKING_SERVICE__`

## Conventions

- Page files follow `page.{name}.php` naming in both `/pages/` and `/vm-admin/routes/`
- Error pages: `error.{code}.{description}.php`
- Service scripts: `sys.{function}.php` or `{domain}.install.php`
- Modal components: `modal.{name}.php`
- The `@` error suppression operator is used extensively on `include_once` calls
- Error logging goes to `build/error-file.log`; debug output to `build/raw.debug`
