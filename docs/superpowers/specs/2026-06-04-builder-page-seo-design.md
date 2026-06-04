# Page Builder — Page Settings (SEO + Header Tags)

**Date:** 2026-06-04
**Status:** Approved (design)
**Scope:** `vm-admin/routes/builder/builder.php`, `vm-admin/routes/builder/engine.js`

## Problem

The page builder edits elements in the iframe `<body>` but exposes no UI for the iframe `<head>`. Users cannot set `<title>`, meta description, canonical URL, social cards (Open Graph / Twitter), favicons, theme color, or paste analytics/preconnect snippets without hand-editing HTML through the Inspect tab. SEO is a baseline requirement for any storefront the builder produces, so this gap forces every site through manual file editing.

## Goal

Add a page-level **Page** tab to the left panel that reads and writes the iframe `<head>` in place, with no new persistence layer. On save, the existing `EXPORT_HTML` → `save_html` → `builder.cache.html` pipeline already captures everything.

## Non-Goals

- No SERP / social preview cards (could come later).
- No per-element / per-product SEO overrides — this is page-level only.
- No `<meta http-equiv>` editor; covered by the Custom Head textarea.
- No structured-data (JSON-LD) builder; covered by the Custom Head textarea.
- No `hreflang` / i18n alternates.
- No new database tables, columns, or JSON files.

## Architecture

### Placement

Add a third tab to the left panel, next to **Layers** and **Add**, labeled **Page**. It is page-scoped and remains available regardless of element selection. The existing right panel (Design / Inspect) stays element-scoped and untouched.

### Data flow

```
┌──────────── Builder UI (parent) ────────────┐         ┌────── iframe + engine.js ──────┐
│                                             │         │                                │
│  setLeftTab('page') ── postMessage GET_HEAD ─┼────────▶│ readHead() → HEAD_DATA         │
│                                             │◀────────┤                                │
│  applyHeadData(data) → fill form fields     │         │                                │
│                                             │         │                                │
│  onHeadFieldChange(kind, value)             │         │                                │
│      ── postMessage UPDATE_HEAD ─────────────┼────────▶│ upsertHeadTag(kind, value)     │
│                                             │         │   or removeHeadTag(kind)       │
│                                             │         │                                │
│  Save (existing flow) ─ EXPORT_HTML ─────────┼────────▶│ serialize whole document       │
│                                             │◀────────┤ HTML_CONTENT                   │
│  doSaveHtml(html) → POST save_html → builder.cache.html                                │
└─────────────────────────────────────────────┘         └────────────────────────────────┘
```

Single source of truth: the iframe's `<head>` DOM. Form fields populate from it on tab open and write back on edit. Save serializes the whole document — no separate persistence path for SEO data.

### Tag-map registry (in `engine.js`)

A flat object describes how each form field maps to a head element. The engine uses it for both read and upsert:

```js
const HEAD_MAP = {
  title:            { type: 'title' },
  description:      { type: 'meta', match: { name: 'description' },     attr: 'content' },
  keywords:         { type: 'meta', match: { name: 'keywords' },        attr: 'content' },
  canonical:        { type: 'link', match: { rel: 'canonical' },        attr: 'href'    },
  robots:           { type: 'meta', match: { name: 'robots' },          attr: 'content' },

  ogTitle:          { type: 'meta', match: { property: 'og:title' },       attr: 'content' },
  ogDescription:    { type: 'meta', match: { property: 'og:description' }, attr: 'content' },
  ogImage:          { type: 'meta', match: { property: 'og:image' },       attr: 'content' },
  ogUrl:            { type: 'meta', match: { property: 'og:url' },         attr: 'content' },
  ogType:           { type: 'meta', match: { property: 'og:type' },        attr: 'content' },

  twitterCard:      { type: 'meta', match: { name: 'twitter:card' },        attr: 'content' },
  twitterTitle:     { type: 'meta', match: { name: 'twitter:title' },       attr: 'content' },
  twitterDescription:{ type: 'meta', match: { name: 'twitter:description' },attr: 'content' },
  twitterImage:     { type: 'meta', match: { name: 'twitter:image' },       attr: 'content' },

  favicon:          { type: 'link', match: { rel: 'icon' },              attr: 'href' },
  appleTouchIcon:   { type: 'link', match: { rel: 'apple-touch-icon' },  attr: 'href' },
  themeColor:       { type: 'meta', match: { name: 'theme-color' },      attr: 'content' },

  customHead:       { type: 'custom' }  // sentinel-bounded block, see below
};
```

### Read

`getHeadData()` walks `HEAD_MAP`, looks up each entry in `document.head`, and returns a flat object of current values. Missing tags resolve to `''`. The `customHead` slot is read by extracting text between the sentinel comments (see below).

### Upsert / remove

`updateHeadTag(kind, value)`:

1. Look up `HEAD_MAP[kind]`.
2. Find the existing element via `document.head.querySelector(...)`.
3. If `value` is non-empty:
   - If element exists: set the configured attribute (or `textContent` for `<title>`).
   - If element does not exist: create it with all `match` attributes set, set the value attribute, append to `head`.
4. If `value` is empty/whitespace:
   - If element exists: remove it. (Exception: `<title>` is set to empty string rather than removed, since HTML5 requires a title element.)

### Custom head block (sentinel comments)

Free-form HTML lives between two sentinel comments so we can find and replace it without scanning the rest of `<head>`:

```html
<!-- vm-builder:custom-head:start -->
<script>/* user-provided */</script>
<link rel="preconnect" href="https://...">
<!-- vm-builder:custom-head:end -->
```

- **Read:** walk `head.childNodes`; the substring between the two comment nodes is the textarea value. Absent → empty.
- **Write:** if both sentinel comments exist, replace nodes between them. If absent, append both sentinels with the new content at the end of `<head>`.
- **Empty value:** keep the sentinel pair (cheap; avoids re-creating each edit) or remove both — either is acceptable; the engine removes both when the textarea is empty to keep head tidy.

### Messaging contract additions

| Message            | Direction     | Payload                              | Effect                                                    |
|--------------------|---------------|--------------------------------------|-----------------------------------------------------------|
| `HEAD_DATA`        | engine→parent | `{ type, data: { …flat values… } }`  | Parent fills the Page-tab form fields.                    |
| `GET_HEAD`         | parent→engine | `{ type }`                           | Engine replies with `HEAD_DATA`.                          |
| `UPDATE_HEAD`      | parent→engine | `{ type, kind, value }`              | Engine upserts/removes the tag, optionally replies HEAD_DATA. |

`HEAD_DATA` is also emitted once on `ENGINE_READY` so the parent can prime the form without a round-trip if the user opens the Page tab early.

## UI breakdown (`builder.php`)

### Tab button (added inside `.fb-panel-tabs`)

```html
<button class="fb-ptab" data-tab="page" onclick="setLeftTab('page')">Page</button>
```

`setLeftTab('page')` shows `#tab-page`, hides `#tab-layers` and `#tab-add`, and triggers `requestHead()`.

### Panel body (`#tab-page`)

Five collapsible `fb-section`s, reusing the existing `fb-sec-header` / `fb-sec-body` / `fb-row` / `fb-input` / `fb-input-sm` classes — no new CSS:

1. **SEO** — title, description (textarea), keywords, canonical, robots (`<select>`: `index, follow` / `noindex` / `nofollow` / `noindex, nofollow`).
2. **Social (Open Graph)** — og:title, og:description (textarea), og:image, og:url, og:type (`<select>`: `website` / `article` / `product`).
3. **Twitter** — twitter:card (`<select>`: `summary` / `summary_large_image`), twitter:title, twitter:description (textarea), twitter:image.
4. **Icons & Theme** — favicon, apple-touch-icon, theme-color (`<input type="color">` swatch + text input, matching existing `fb-color-row` pattern).
5. **Custom Head HTML** — single `<textarea>` with `rows="6"` and a small explanatory hint.

### JS additions (in `builder.php` `<script>`)

- `setLeftTab(name)` updated to handle the new `'page'` value (current `setLeftTab` already toggles between two tabs; extend to three).
- `requestHead()` — `sendToIframe({ type: 'GET_HEAD' })`.
- `applyHeadData(data)` — write each value into its matching `#prop-head-*` input.
- `onHeadFieldChange(kind, value)` — debounced (~200ms per field) `sendToIframe({ type: 'UPDATE_HEAD', kind, value })`.
- `window` `'message'` handler extended with `HEAD_DATA` case calling `applyHeadData(msg.data)`.

### Engine.js additions

- `getHeadData()`, `updateHeadTag(kind, value)`, `removeHeadTag(kind)`, `readCustomHead()`, `writeCustomHead(html)`.
- `ENGINE_READY` post step extended to also `postMessage({ type: 'HEAD_DATA', data: getHeadData() })`.
- Message handler extended with `GET_HEAD` and `UPDATE_HEAD` cases.

## Edge cases

- **No `<head>` present in cache HTML:** `getHeadData()` returns empty values; `updateHeadTag` creates `<head>` if missing (defensive — the export pipeline always provides one).
- **Multiple matching tags** (e.g., two `<meta name="description">`): operate on the first match; do not deduplicate automatically (out of scope, may surprise users).
- **Title removed:** keep `<title>` element with empty text rather than removing it (HTML5 conformance + parser quirks).
- **Custom-head HTML with broken markup:** engine sets `head.innerHTML` for that range; if the browser refuses to parse, the next read returns whatever the browser kept. No validation layer.
- **Theme-color color picker:** mirrors existing `fb-color-row` pattern from the Design tab (Fill / Stroke sections).
- **Save flow:** unchanged. The existing `EXPORT_HTML` returns `doc.documentElement.outerHTML`, which already includes the modified head.

## Risks

- **User pastes a `<script>` into Custom Head that breaks the builder iframe load.** Mitigation: scripts in `<head>` execute when the iframe re-loads (only on full builder reload). Inline edits don't re-execute, so the live builder is safe; on reload, a bad script could break the preview. Acceptable — user-authored risk, parallel to inserting custom HTML in body.
- **Tag duplication if a theme also writes the same tag dynamically.** The engine writes to the static head; runtime-injected tags from theme JS sit alongside. For SEO purposes the static tags are what crawlers see, so the static wins. No mitigation needed.

## Files changed

- `vm-admin/routes/builder/builder.php` — new tab button, new `#tab-page` markup, JS handlers, message-case extension.
- `vm-admin/routes/builder/engine.js` — HEAD_MAP, read/upsert/remove helpers, custom-head sentinel handling, GET_HEAD / UPDATE_HEAD messages, HEAD_DATA emit on ready.

No PHP, schema, or routing changes.

## Test plan

- Open builder on a site with an existing `<title>` and meta description → Page tab loads values.
- Edit title → iframe `<title>` updates live → Save → reload builder → values persist.
- Clear a non-title field → corresponding tag removed from head.
- Set robots to `noindex` → `<meta name="robots" content="noindex">` appears in head.
- Set theme color via color picker → both swatch and text input stay in sync.
- Paste `<script>console.log('hi')</script>` into Custom Head → save → reload builder → script tag present between sentinels.
- Clear Custom Head textarea → sentinels removed on next save.
- Verify Layers/Add tabs still work after switching to Page and back.
