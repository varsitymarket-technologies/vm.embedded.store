<div align="center">
  <img src="https://avatars.githubusercontent.com/u/219999828?s=400&u=2166fd2a4b7e592c0f1e9893a34aeb1105bc6bea&v=4" width="120px" alt="Varsity Market Logo">
  <h1>Varsity Market: Embedded Store Engine</h1>
  <p>A modular PHP-based commerce engine for themeable, portable storefronts.</p>

  [![PWA Ready](https://img.shields.io/badge/PWA-Ready-success?style=for-the-badge&logo=pwa)](https://web.dev/progressive-web-apps/)
  [![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)](LICENSE)
</div>

---

## Overview

**Varsity Market** is a lightweight engine designed to deploy and manage embedded e-commerce storefronts. Built with a focus on portability and performance, it allows for seamless integration into existing sites or standalone deployment as a Progressive Web App (PWA).

### Core Features
- **Theme-Based Architecture**: Modular frontend templates located in the `/themes` directory.
- **Embedded Deployment**: Support for exporting storefronts via `iFrame` or direct source code distribution.
- **Unified Admin Dashboard**: A centralized interface (`/vm-admin`) for managing inventory, orders, and configuration.
- **Storage**: High-efficiency SQLite 3 backend for zero-dependency portability.
- **Cloud Integration**: Built-in support for Cloudflare (DNS management) and GitHub (automated deployment workflows).

## Technical Stack
- **Languages**: PHP 7.4+, JavaScript (ES6+), CSS3
- **Database**: SQLite 3
- **Protocol**: RESTful API for internal services
- **PWA**: Service Worker integration for offline capabilities

## Directory Structure
- `/vm-admin`: Administrative console and backend logic.
- `/services`: System scripts for database maintenance and export utilities.
- `/themes`: Directory for UI/UX templates.
- `/module`: Core application modules (e.g., GitHub integration).
- `/app`: PWA client-side resources.

## Getting Started

### Prerequisites
- PHP 7.4 or higher
- SQLite 3 extension enabled
- Web server (Apache/Nginx) with rewrite support

### Installation
1. Clone the repository to your server root.
2. Configure the `.env` file with your credentials (GitHub, Cloudflare, etc.).
3. Initialize the database:
   ```bash
   php services/sys.database.php
   ```

## Maintenance Commands
The engine provides several CLI tools for system management:

| Command | Description |
| :--- | :--- |
| `php services/sys.database.php` | Initialize or reset the core database. |
| `php services/sys.database.backup.php` | Generate a timestamped backup of the database. |
| `php services/sys.database.reboot.php` | Perform a clean system reboot and state reset. |
| `php services/sys.database.restore.php` | Restore the database from the latest backup. |

---
**Varsity Market Technologies**