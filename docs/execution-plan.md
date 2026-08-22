# Client Portal Execution Plan

## Goal
Build the client portal into a polished, durable experience in the spirit of Obsidian or Shopify, but only for the customer-facing portal. This plan is meant to guide multiple agents working in parallel without drifting into unrelated admin, engine, or infrastructure work.

## Product Shape

The portal should feel like a focused workspace for customers to:
- discover products
- browse collections
- manage their cart
- complete checkout
- view orders and account history
- return later and pick up where they left off

The experience should be calm, fast, and predictable. The interface should prioritize scanning, comparison, and conversion over marketing flourish.

## Design Principles

- Keep the portal utilitarian and responsive.
- Use a dense information hierarchy, not a decorative landing page.
- Make the primary actions obvious on every screen.
- Favor stable layouts, minimal motion, and direct navigation.
- Preserve the existing site structure and API contracts unless a change is explicitly part of the plan.
- Treat the storefront as the source of truth for customer interactions, not the admin panel.

## Boundaries

In scope:
- public storefront pages
- customer auth and account views
- cart and checkout flow
- product browsing and search
- order history and post-purchase state
- portal-level page structure, navigation, and content architecture

Out of scope:
- admin back office workflows
- engine bootstrapping and deployment internals
- unrelated theme library refactors
- brand marketing pages unless they directly support the portal flow

## Milestones

### Phase 1. Portal Foundation

Deliver a clean, consistent shell for the portal.

Tasks:
- define the main portal navigation model
- establish shared page layout, headers, and footers
- confirm route naming across home, products, product detail, cart, checkout, account, orders, search, and static info pages
- make sure the selected page renders from the correct source files

Done when:
- a user can move across the portal without layout jumps
- each page has a clear role and entry point
- the shell works across mobile and desktop

### Phase 2. Catalog Experience

Make product browsing feel like a real storefront rather than a demo.

Tasks:
- product grid and collection browsing
- collection filters and category navigation
- search experience with empty states and result states
- product detail page structure
- related products and back-navigation patterns

Done when:
- users can find products in under a few interactions
- collection and search states are legible
- product detail content is structured and repeatable

### Phase 3. Cart and Checkout

Turn browsing into purchase flow.

Tasks:
- cart drawer or cart page behavior
- quantity changes, remove actions, and subtotal updates
- checkout entry point and checkout state handling
- shipping/contact/payment step layout
- completion state and return path after checkout

Done when:
- a customer can move from product selection to completed checkout without confusion
- completion redirects are deterministic
- cart state survives normal navigation

### Phase 4. Customer Account

Make the portal useful after the first purchase.

Tasks:
- login and registration entry points
- account overview
- order history
- address book or saved profile data where supported
- password and session management

Done when:
- signed-in customers can review their history and return quickly
- the account area feels like part of the same portal system

### Phase 5. Trust and Support

Add the details that make the portal feel complete.

Tasks:
- shipping, returns, FAQ, and policy pages
- contact/support entry points
- payment trust cues
- privacy and terms surfaces where needed

Done when:
- customers have clear answers before and after checkout
- support pages are accessible from the main flow

### Phase 6. Portal Polish

Improve the last 10 percent.

Tasks:
- refine spacing and typography
- remove dead ends and broken links
- improve empty states and loading states
- make sure mobile behavior is clean on all major portal pages
- verify that no page is visually fighting the rest of the portal

Done when:
- the portal feels cohesive and intentional
- there are no obvious rough edges in the primary purchase path

## Working Rules For Agents

1. Read the existing portal structure before editing.
2. Prefer the current page patterns over inventing new ones.
3. Keep changes narrow and file-local unless a larger structural move is clearly required.
4. If a page renderer already exists, adapt it instead of replacing it.
5. If a change affects navigation, update every route that depends on it.
6. If a change affects checkout or account state, verify the full flow end to end.
7. When in doubt, preserve the current source of truth and improve around it.

## Implementation Order

Suggested order for agents:
1. portal shell and routing
2. catalog and product pages
3. cart and checkout
4. account and orders
5. support and trust pages
6. final polish and regression checks

## Exit Criteria

The portal is ready for the next stage when:
- customers can browse, select, checkout, and return to account history cleanly
- the page architecture matches the runtime renderer
- the interface feels coherent on phone and desktop
- the remaining work is mainly refinement, not repair

