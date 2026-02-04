<h1 align="center">
    <a><img src="https://avatars.githubusercontent.com/u/219999828?s=400&u=2166fd2a4b7e592c0f1e9893a34aeb1105bc6bea&v=4" width="175px" alt="< VM.EMBEDDED SITES >"></a>
</h1>

# VM.EMBEDDED.SITES

<p align="center"> <strong>A lightweight, plug-and-play e-commerce micro-service.</strong>

Turn any static page (Google Sites, Cloudflare Pages, Github pages) into a fully functional storefront. </p>

# What is vm.embedded.sites ?
vm.embedded.sites is a self-hosted e-commerce plugin designed for users who need a professional shop without the overhead of developing a custom application. It splits into two parts: a high -performance Shop Frontend for customers and a robust Admin Control Panel for mnagement. 

## Why use this?
- Zero-Friction Intergration: Embed via `<iframe>` or simply export your page into a single exported web page. 
- Lightweight: No heavy databases or complex dependencies required.
- Decoupled Management: Update your products in the Admin Panel; the changes will instantly be reflected on your static website.

# App Features
## Shop Frontend
- SPE Architecture: Single Page Expirience [Shop, Product, and Checkout views transition without reloads]. 
- Responsive: Optimized for mobile shoppers using Bootstrap 5. 
- Page Designs: 
The page themes allow you to change your store designs to how you want your store to look like.
- Simple Export: This tool exports your page into two formats `<iframe>` or `<source code>`. This makes it easy to embed the store to your static web page.

## Admin Control Panel
<p align="center"> <img src="https://raw.githubusercontent.com/varsitymarket-technologies/vm.embedded.store/refs/heads/master/assets/admin_ui.png" width="100%" alt="Admin UI Preview"> </p>

- Compehensive Management: Dedicated pages for User Roles, Product Catalogs, Order Tracking.

## Creating Your Own Template 
Read the `TEMPLATE.md` file.

# Tech Stack 

| Section | Technologies |
| --- | --- | --- |
| Backend | PHP 7.4+ |
| Shop UI | Tailwind CSS, JS |

# Database & CLI Tools
The system includes built-in services for database health and backups via the PHP CLI.
- Initialize/Restart Database:
```shell 
php services/sys.database.php
```
- Create Manual Backup:
```shell 
php services/sys.database.backup.php
```
- Restore from Rollback:
```shell 
php services/sys.database.restore.php [backup_file]
# example: php /services/sys.database.restore.php /build/engine.backup
```

# API Reference 
The frontend fetches data from `api.php`. 

## Products 
Endpoint: `Get api.php?state=products`
- Function: Returns all the products in the Micro Store Application. 

## Category 
Endpoint: `Get api.php?state=categories`
- Function: Returns all the products in the Micro Store Application. 