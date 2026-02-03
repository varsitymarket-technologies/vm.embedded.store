<h1 align="center">
    <a><img src="https://avatars.githubusercontent.com/u/219999828?s=400&u=2166fd2a4b7e592c0f1e9893a34aeb1105bc6bea&v=4" width="175px" alt="< VM.EMBEDDED SITES >"></a>
</h1>

# Varsity Market - Embedded Mini Store

A lightweight, embedded e-commerce micro-service designed to act as a plugin for Google Sites, Cloudflare Pages and other platforms. This project provides a complete solution with a customer-facing shop and a comprehensive admin control panel.

## Overview

This solution is built to be hosted on a PHP server and embedded via `<iframe>` into Google Sites. It separates the administrative interface from the public shop view, allowing for secure and easy management of products and orders.

## Features

### Shop Frontend
*   **Responsive Design**: Built with Bootstrap 5 for compatibility across devices.
*   **Single Page Experience**: Smooth navigation between Shop, Product Details, and Checkout views without page reloads.
*   **Shopping Cart**: Client-side cart management with instant updates.
*   **Embeddable**: Optimized for embedding within Google Sites containers.

### Admin Control Panel
<h1 align="center">
    <a><img src="https://raw.githubusercontent.com/varsitymarket-technologies/vm.embedded.store/refs/heads/master/assets/admin_ui.png" width="200px" alt="< VM.MAKHESA >"></a>
</h1>

*   **Modern UI**: Dark-themed interface built with Tailwind CSS.
*   **Dashboard**: Real-time overview of sales, users, and orders.
*   **Responsive Sidebar**: Collapsible navigation for mobile management.
*   **Management Modules**:
    *   Dashboard Overview
    *   User Management
    *   Product Catalog
    *   Order Tracking
    *   Settings

## Tech Stack

*   **Backend**: PHP
*   **Frontend (Shop)**: HTML5, Bootstrap 5, JavaScript
*   **Frontend (Admin)**: HTML5, Tailwind CSS, JavaScript

## Installation & Usage

1.  **Deploy**: Upload the project folders to your PHP web hosting environment.
2.  **Google Sites Integration**:
    *   Open your Google Site editor.
    *   Select **Embed** > **By URL**.
    *   Enter the URL to your hosted shop page (e.g., `sites/osmossis/index.html`).
3.  **Administration**:
    *   Navigate to `/vm-admin/interface.php` to access the control panel.

## Directory Structure

*   `/sites/`: Contains the public-facing shop template.
*   `/vm-admin/`: Contains the admin dashboard, styles, and logic.
*   `/themes/`: Stores reusable theme templates (e.g., `exalt`).
*   `scripts.php`: Core system logic and database initialization.

## Configuration

### System Database 

#### Database Restoration
To restart The System database use the following service scripts `/services/sys.database.php`. 
```shell 
php /services/sys.database.php
```
#### Database Rollback
The system allows users to restore their database as a rollback feature incase of database damages. If you wish to restore the database run the following scripts `/services/sys.database.restore.php`. To execute the command run it as follows 
```shell 
php /services/sys.database.restore.php [backup_file]
# e.g php /services/sys.database.restore.php /build/engine.backup
```

#### Database Backup
To Make a manual backup of the database you can execute the following scripts to backup the current state of the database. ` php services/sys.database.backup.php`. To execute the command run it as follows:
```shell 
php /services/sys.database.backup.php
```
The system will then show where the backup file is stored. 


Each site instance (e.g., inside `/sites/`) is configured via a `config.php` file. Key constants include:

*   `__SITE_TITLE__`: The display title of the store.
*   `__CURRENCY__`: The currency symbol used for pricing.
*   `__API_LINK__`: The relative path to the API handler.
*   `__SHOP_INTRO__` & `__SHOP_DESCRIPTION__`: Text content for the shop header.

## API Reference

The frontend communicates with a backend PHP API to fetch dynamic content.

*   **Endpoint**: `api.php`
*   **Query Parameters**:
    *   `?state=products`: Returns a JSON array of product objects containing `id`, `name`, `price`, `image`, and `description`.
