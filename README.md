<div align="center">
  <img src="https://avatars.githubusercontent.com/u/219999828?s=400&u=2166fd2a4b7e592c0f1e9893a34aeb1105bc6bea&v=4" width="100px" alt="Varsity Market Logo">
  <h1>Embedded Engine</h1>
  <p>Build, manage, and launch beautiful online stores — without writing a single line of code.</p>

  [![PWA Ready](https://img.shields.io/badge/PWA-Ready-success?style=for-the-badge&logo=pwa)](https://web.dev/progressive-web-apps/)
  [![Docker](https://img.shields.io/badge/Docker-Compose-blue?style=for-the-badge&logo=docker)](docker-compose.yml)
  [![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)](LICENSE)
</div>

---

## What is Embedded Engine?

**Embedded Engine** is the platform that powers online stores built by Varsity Market Technologies. It's the software "engine" that sits behind every store — handling everything from displaying products to processing orders to managing customers.

Think of it like the engine under the hood of a car. You don't need to know how it works to drive — you just use the dashboard. For store owners, that dashboard is the **admin panel**. For shoppers, it's the **storefront** they see and buy from.

One installation of Embedded Engine can run **many different stores at the same time**, each with its own look, products, customers, and settings.

---

## What can it do?

### 🛍️ Run a complete online store
Every store built on Embedded Engine comes with everything a shopper expects:

- Browse products by category
- Add items to a cart and check out
- Create a customer account and track order history
- Pay online or request a cash payment

### 🎨 Choose or upload a storefront design
Stores are fully themeable. There are **26+ ready-made designs** to choose from — each one a complete, working storefront. If none of them fit, you can upload your own custom design. You can even edit pages directly inside the admin panel without touching any code.

### 🤖 AI Website Builder *(in development)*
The AI Builder lets you describe what you want your website to look like — in plain English — and the system will generate or update your storefront for you. You see a live preview of your site on one side, and type your instructions on the other. No design skills required.

### 📦 Manage products with ease
- Add, edit, and organise products into categories
- Set prices, stock levels, descriptions, and images
- Import products in bulk from a Shopify CSV export — the engine handles everything automatically

### 🛒 Handle orders end-to-end
- View incoming orders as they arrive
- Process online payments or approve cash orders manually
- Issue discounts and track sales performance over time

### 👥 Customer accounts
Every store has its own customer base. Shoppers can register, log in, save their address, and see their order history. Store owners can see and manage all customers from the admin panel.

### 📊 Analytics and reporting
Get a clear picture of how your store is performing — sales over time, popular products, customer activity — all in one place.

### 🚀 Deploy and go live
When you're ready, publishing your store is a single click. The engine handles putting it online at your chosen domain.

---

## Who is it for?

Embedded Engine is for **anyone who wants to sell online** — whether you're an independent seller, a small business, or a campus marketplace. You don't need to be a developer to use it. The admin panel is designed to be straightforward and self-explanatory.

For the people who *do* maintain and extend the system — developers and system administrators — there is a separate technical guide that covers installation, APIs, and internals.

---

## How does a store get created?

Here's the basic journey from zero to a live online store:

1. **Install the engine** — done once by a system administrator (see the technical docs)
2. **Create a new store** — give it a name and a domain in the admin panel
3. **Pick a design** — choose from the theme library or upload a custom one
4. **Add your products** — manually or by importing a CSV
5. **Configure payments and delivery** — set up how you accept money and where you ship
6. **Go live** — publish the store and share your link

From that point on, you manage everything — orders, customers, discounts, content — from the admin panel.

---

## The admin panel

Every store has its own admin panel, accessible at `/vm-admin/{your-store}/`. From there, store owners can manage:

| Section | What it does |
|---|---|
| **Products** | Add, edit, organise, and import your product catalogue |
| **Categories** | Group products to make browsing easier for customers |
| **Orders** | View and process incoming orders |
| **Customers / Users** | See who's registered and manage accounts |
| **Discounts** | Create discount codes and promotional offers |
| **Payments** | Configure how you accept money |
| **Delivery** | Set delivery zones, fees, and courier options |
| **Themes** | Switch your store design or upload a custom one |
| **Page Builder** | Edit individual pages of your storefront visually |
| **AI Builder** | Describe changes to your site in plain language |
| **Analytics** | Track sales, traffic, and store performance |
| **Forms** | Manage contact and enquiry form submissions |
| **Settings** | Store name, domain, and general configuration |
| **Deploy** | Publish your store and push updates live |

---

## The storefront

What your customers see is a fast, mobile-friendly website that works on any device. It supports:

- Browsing and searching products
- A full shopping cart and checkout
- Customer account creation and login
- Order confirmation and history
- Offline support (the store still works if your connection drops temporarily)

Storefronts can also be embedded inside another website using an `<iframe>`, hosted at a dedicated URL, or exported as standalone files — giving you flexibility in how and where you deploy.

---

*For technical setup, API documentation, and developer guides, see the [docs/](docs/) folder.*

---

**Varsity Market Technologies**
