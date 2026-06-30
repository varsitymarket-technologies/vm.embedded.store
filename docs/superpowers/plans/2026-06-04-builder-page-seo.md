# Builder Page Settings (SEO + Header Tags) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a page-scoped **Page** tab to the builder's left panel that reads, edits, and writes head-level tags — title, meta description/keywords/canonical/robots, Open Graph + Twitter cards, favicon/apple-touch-icon/theme-color, and a free-form custom-head HTML block — directly into the iframe `<head>`, persisted through the existing save flow.

**Architecture:** Two files only. `engine.js` (iframe side) gains a `HEAD_MAP` registry plus `getHeadData()` / `updateHeadTag()` / custom-head sentinel helpers, emits `HEAD_DATA` on ready, and handles incoming `GET_HEAD` / `UPDATE_HEAD` messages. `builder.php` (parent side) gains a third left-panel tab with five collapsible sections, plus the JS to request, fill, debounce, and forward field changes. Save is unchanged — the existing `GET_HTML` → `HTML_CONTENT` → `save_html` POST pipeline already serializes the whole document including head.

**Tech Stack:** Vanilla JS (ES6+), PHP 7.4, postMessage between parent + iframe, Bootstrap Icons.

**Spec:** `docs/superpowers/specs/2026-06-04-builder-page-seo-design.md`

**Testing approach:** This is browser-driven UI in a PHP-served admin page. There is no automated test runner for `engine.js`. Verification is done **manually in the running app** after each task — open `http://localhost:8016/vm-admin/{domain}/builder` and exercise the changes. Each task lists a specific in-browser smoke check. For JS-only behaviours that don't have visible UI yet, use the browser DevTools console (or the Playwright MCP `browser_evaluate` tool) to call the function and inspect the return value.

---

## Pre-flight

### Task 0: Verify clean working tree and dev server running

**Files:** none

- [ ] **Step 1: Check working tree state**

Run:
```bash
git status --short
```
Expected: no modifications under `vm-admin/routes/builder/`. Pre-existing modifications in `.env`, `app/deploy/skel/`, `module/`, `pages/`, `vm-admin/routes/page.deploy.php`, etc. are fine — they don't overlap with this work.

- [ ] **Step 2: Confirm Docker dev server is up**

Run:
```bash
docker ps --filter "name=vm-emb-sites" --format "{{.Names}} {{.Status}}"
```
Expected: one line showing `vm-emb-sites Up ...`. If empty, run `docker-compose up -d` from the repo root and re-check.

- [ ] **Step 3: Confirm builder page loads**

Open `http://localhost:8016/vm-admin/<your-domain>/builder` in a browser (use a domain that has a site set up — check `/vm-admin` home if unsure; `reiddrop.com` is known to have a `builder.cache.html`).
Expected: the builder UI renders with the dark Figma-style chrome, iframe shows the site, layers tree appears in the left panel, the left panel has two tabs labelled **Layers** and **Add**.

If the page is blank or errors, fix that first before proceeding.

---

## File Structure

| File | Responsibility | Touched by |
| --- | --- | --- |
| `vm-admin/routes/builder/engine.js` | Iframe-side `HEAD_MAP` registry, `getHeadData()` read, `updateHeadTag()` upsert/remove, custom-head sentinel helpers, `HEAD_DATA` emit on ready, `GET_HEAD` / `UPDATE_HEAD` message cases | Tasks 1, 2, 3, 4 |
| `vm-admin/routes/builder/builder.php` | New `Page` tab button in `.fb-panel-tabs`, new `#tab-page` markup with five `.fb-section` blocks, `setLeftTab` extension, `requestHead()` / `applyHeadData()` / `onHeadFieldChange()` JS, `HEAD_DATA` message-case extension | Tasks 5, 6, 7 |

No new files. No PHP backend changes. No theme, schema, or routing changes.

---

## Task 1: Add HEAD_MAP registry and getHeadData() to engine.js

**Files:**
- Modify: `vm-admin/routes/builder/engine.js` (insert helpers near the existing helpers section, around line 270 — just after `getHtmlSnippet`, before `sendToParent`)

- [ ] **Step 1: Locate insertion point**

The current code at line ~270 has:
```js
    function getHtmlSnippet(el) {
        // ... existing ...
    }

    function sendToParent(data) {
        window.parent.postMessage(data, '*');
    }
```

We will insert the head helpers between `getHtmlSnippet` and `sendToParent`.

- [ ] **Step 2: Insert HEAD_MAP and read helpers**

After the closing `}` of `getHtmlSnippet` and before `function sendToParent`, add:

```js
    // ── Head tag registry ──
    const HEAD_MAP = {
        title:              { type: 'title' },
        description:        { type: 'meta', match: { name: 'description' },        attr: 'content' },
        keywords:           { type: 'meta', match: { name: 'keywords' },           attr: 'content' },
        canonical:          { type: 'link', match: { rel:  'canonical' },          attr: 'href'    },
        robots:             { type: 'meta', match: { name: 'robots' },             attr: 'content' },
        ogTitle:            { type: 'meta', match: { property: 'og:title' },       attr: 'content' },
        ogDescription:      { type: 'meta', match: { property: 'og:description' }, attr: 'content' },
        ogImage:            { type: 'meta', match: { property: 'og:image' },       attr: 'content' },
        ogUrl:              { type: 'meta', match: { property: 'og:url' },         attr: 'content' },
        ogType:             { type: 'meta', match: { property: 'og:type' },        attr: 'content' },
        twitterCard:        { type: 'meta', match: { name: 'twitter:card' },        attr: 'content' },
        twitterTitle:       { type: 'meta', match: { name: 'twitter:title' },       attr: 'content' },
        twitterDescription: { type: 'meta', match: { name: 'twitter:description' }, attr: 'content' },
        twitterImage:       { type: 'meta', match: { name: 'twitter:image' },       attr: 'content' },
        favicon:            { type: 'link', match: { rel: 'icon' },                attr: 'href' },
        appleTouchIcon:     { type: 'link', match: { rel: 'apple-touch-icon' },    attr: 'href' },
        themeColor:         { type: 'meta', match: { name: 'theme-color' },        attr: 'content' },
        customHead:         { type: 'custom' }
    };

    const CUSTOM_HEAD_START = 'vm-builder:custom-head:start';
    const CUSTOM_HEAD_END   = 'vm-builder:custom-head:end';

    function headSelector(entry) {
        if (entry.type === 'title') return 'title';
        const tag = entry.type;
        const attrs = Object.entries(entry.match)
            .map(([k, v]) => `[${k}="${v.replace(/"/g, '\\"')}"]`)
            .join('');
        return tag + attrs;
    }

    function readCustomHead() {
        const head = document.head;
        if (!head) return '';
        let startNode = null, endNode = null;
        for (const node of head.childNodes) {
            if (node.nodeType !== 8) continue; // 8 = COMMENT_NODE
            const t = node.nodeValue.trim();
            if (t === CUSTOM_HEAD_START) startNode = node;
            else if (t === CUSTOM_HEAD_END) { endNode = node; break; }
        }
        if (!startNode || !endNode) return '';
        const parts = [];
        let cursor = startNode.nextSibling;
        while (cursor && cursor !== endNode) {
            parts.push(cursor.nodeType === 1 ? cursor.outerHTML : (cursor.nodeValue || ''));
            cursor = cursor.nextSibling;
        }
        return parts.join('').trim();
    }

    function getHeadData() {
        const data = {};
        const head = document.head;
        for (const [key, entry] of Object.entries(HEAD_MAP)) {
            if (entry.type === 'custom') {
                data[key] = readCustomHead();
                continue;
            }
            if (entry.type === 'title') {
                const t = head ? head.querySelector('title') : null;
                data[key] = t ? (t.textContent || '') : '';
                continue;
            }
            const el = head ? head.querySelector(headSelector(entry)) : null;
            data[key] = el ? (el.getAttribute(entry.attr) || '') : '';
        }
        return data;
    }
```

- [ ] **Step 3: Smoke check — getHeadData() returns expected shape**

Reload the builder page in the browser. Open DevTools, switch the console **context** to the builder iframe (the dropdown next to the filter input — pick the entry that is NOT `top`; it will be the inner site). Run:

```js
// Expose temporarily for inspection:
window.__test_getHeadData = (function(){ /* paste the body of getHeadData inline */ })();
```

Easier alternative: from the parent (top) console, run:
```js
document.getElementById('builder-iframe').contentWindow.eval(
    "Object.keys((function(){ const m={title:document.title}; return m; })())"
);
```

The cleanest smoke check (no eval): we'll wire HEAD_DATA on ready in the next task, which gives a visible verification — proceed to Task 2.

- [ ] **Step 4: Commit**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder/engine): HEAD_MAP registry + getHeadData reader"
```

---

## Task 2: Emit HEAD_DATA on ENGINE_READY and handle GET_HEAD

**Files:**
- Modify: `vm-admin/routes/builder/engine.js` (the `window.addEventListener('message', ...)` block around line 731, and the bottom of the IIFE around line 895)

- [ ] **Step 1: Add GET_HEAD case to the message handler**

Find the message handler that begins at:
```js
    window.addEventListener('message', function (e) {
        const msg = e.data;
        if (!msg || !msg.type) return;

        if (msg.type === 'SET_MODE') {
```

After the existing `GET_LAYERS` case (the last `if (msg.type === 'GET_LAYERS')` block, ~line 846), and **before** the closing `});` of the message handler, add:

```js
        if (msg.type === 'GET_HEAD') {
            sendToParent({ type: 'HEAD_DATA', data: getHeadData() });
        }
```

- [ ] **Step 2: Emit HEAD_DATA on ready**

The IIFE currently ends with:
```js
    // Notify parent we're ready
    sendToParent({ type: 'ENGINE_READY' });
    sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
})();
```

Add one line between the layers update and the closing IIFE:
```js
    // Notify parent we're ready
    sendToParent({ type: 'ENGINE_READY' });
    sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
    sendToParent({ type: 'HEAD_DATA',    data: getHeadData() });
})();
```

- [ ] **Step 3: Smoke check — HEAD_DATA arrives at the parent**

Reload the builder. In the **parent (top)** DevTools console run:
```js
let received = null;
const listener = e => { if (e.data && e.data.type === 'HEAD_DATA') received = e.data.data; };
window.addEventListener('message', listener);
document.getElementById('builder-iframe').contentWindow.postMessage({ type: 'GET_HEAD' }, '*');
setTimeout(() => { console.log('HEAD_DATA:', received); window.removeEventListener('message', listener); }, 200);
```

Expected (for `reiddrop.com` whose cache contains `<title>TERRA // Thoughtful Objects & Apparel</title>`):
```
HEAD_DATA: { title: "TERRA // Thoughtful Objects & Apparel", description: "", keywords: "", canonical: "", robots: "", ogTitle: "", ..., customHead: "" }
```
All 18 keys present. Title populated. Other keys empty strings (not `undefined`).

- [ ] **Step 4: Commit**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder/engine): emit HEAD_DATA on ready + handle GET_HEAD"
```

---

## Task 3: Add updateHeadTag() upsert/remove helper

**Files:**
- Modify: `vm-admin/routes/builder/engine.js` (insert right after `getHeadData` — same region as Task 1)

- [ ] **Step 1: Add write helpers**

Immediately after the `getHeadData` function closing `}` and before `function sendToParent`, add:

```js
    function writeCustomHead(html) {
        const head = document.head;
        if (!head) return;
        let startNode = null, endNode = null;
        for (const node of head.childNodes) {
            if (node.nodeType !== 8) continue;
            const t = node.nodeValue.trim();
            if (t === CUSTOM_HEAD_START) startNode = node;
            else if (t === CUSTOM_HEAD_END) { endNode = node; break; }
        }
        // Remove existing block (if any)
        if (startNode && endNode) {
            let cursor = startNode.nextSibling;
            while (cursor && cursor !== endNode) {
                const next = cursor.nextSibling;
                cursor.remove();
                cursor = next;
            }
            startNode.remove();
            endNode.remove();
        }
        // Insert new block only if value is non-empty
        const trimmed = (html || '').trim();
        if (!trimmed) return;
        const start = document.createComment(' ' + CUSTOM_HEAD_START + ' ');
        const end   = document.createComment(' ' + CUSTOM_HEAD_END + ' ');
        const fragment = document.createRange().createContextualFragment(trimmed);
        head.appendChild(start);
        head.appendChild(fragment);
        head.appendChild(end);
    }

    function updateHeadTag(kind, value) {
        const entry = HEAD_MAP[kind];
        if (!entry) return;
        const head = document.head;
        if (!head) return;
        const trimmed = value == null ? '' : String(value);

        if (entry.type === 'custom') {
            writeCustomHead(trimmed);
            return;
        }

        if (entry.type === 'title') {
            let t = head.querySelector('title');
            if (!t) { t = document.createElement('title'); head.appendChild(t); }
            t.textContent = trimmed; // keep <title> element even if empty (HTML5 requires)
            return;
        }

        const selector = headSelector(entry);
        let el = head.querySelector(selector);

        if (trimmed === '') {
            if (el) el.remove();
            return;
        }

        if (!el) {
            el = document.createElement(entry.type); // 'meta' or 'link'
            for (const [k, v] of Object.entries(entry.match)) el.setAttribute(k, v);
            head.appendChild(el);
        }
        el.setAttribute(entry.attr, trimmed);
    }
```

- [ ] **Step 2: Commit (no smoke check yet — wired up in Task 4)**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder/engine): updateHeadTag upsert/remove + writeCustomHead"
```

---

## Task 4: Handle UPDATE_HEAD messages

**Files:**
- Modify: `vm-admin/routes/builder/engine.js` (the message handler block, right after the `GET_HEAD` case added in Task 2)

- [ ] **Step 1: Add UPDATE_HEAD case**

After the `GET_HEAD` case added in Task 2, add:

```js
        if (msg.type === 'UPDATE_HEAD') {
            updateHeadTag(msg.kind, msg.value);
        }
```

The order inside the message handler should now read:
```js
        if (msg.type === 'GET_LAYERS') { /* existing */ }
        if (msg.type === 'GET_HEAD') {
            sendToParent({ type: 'HEAD_DATA', data: getHeadData() });
        }
        if (msg.type === 'UPDATE_HEAD') {
            updateHeadTag(msg.kind, msg.value);
        }
    });
```

- [ ] **Step 2: Smoke check — title roundtrip**

Reload the builder. In the **parent (top)** DevTools console:
```js
const iframe = document.getElementById('builder-iframe').contentWindow;
iframe.postMessage({ type: 'UPDATE_HEAD', kind: 'title', value: 'NEW TEST TITLE' }, '*');
setTimeout(() => {
    console.log('title in iframe head:', iframe.document.querySelector('head title').textContent);
}, 100);
```
Expected: `title in iframe head: NEW TEST TITLE`

Then test create + remove of a meta tag:
```js
iframe.postMessage({ type: 'UPDATE_HEAD', kind: 'description', value: 'hello' }, '*');
setTimeout(() => console.log('desc:', iframe.document.head.querySelector('meta[name="description"]')?.outerHTML), 100);
// → <meta name="description" content="hello">
iframe.postMessage({ type: 'UPDATE_HEAD', kind: 'description', value: '' }, '*');
setTimeout(() => console.log('desc after clear:', iframe.document.head.querySelector('meta[name="description"]')), 200);
// → null (tag removed)
```

Test custom head:
```js
iframe.postMessage({ type: 'UPDATE_HEAD', kind: 'customHead', value: '<script>console.log("hi")</script>' }, '*');
setTimeout(() => console.log('head html:', iframe.document.head.innerHTML.includes('vm-builder:custom-head:start')), 100);
// → true
iframe.postMessage({ type: 'UPDATE_HEAD', kind: 'customHead', value: '' }, '*');
setTimeout(() => console.log('head html after clear:', iframe.document.head.innerHTML.includes('vm-builder:custom-head:start')), 200);
// → false
```

If any expected output is wrong, fix before moving on.

- [ ] **Step 3: Commit**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder/engine): handle UPDATE_HEAD message"
```

---

## Task 5: Add "Page" tab button and empty tab container in builder.php

**Files:**
- Modify: `vm-admin/routes/builder/builder.php` — the `.fb-panel-tabs` block (~line 481) and the `.fb-panel-content` block (~line 485)

- [ ] **Step 1: Add the tab button**

Locate (around line 481):
```html
            <div class="fb-panel-tabs">
                <button class="fb-ptab on" data-tab="layers" onclick="setLeftTab('layers')">Layers</button>
                <button class="fb-ptab" data-tab="add" onclick="setLeftTab('add')">Add</button>
            </div>
```

Change to:
```html
            <div class="fb-panel-tabs">
                <button class="fb-ptab on" data-tab="layers" onclick="setLeftTab('layers')">Layers</button>
                <button class="fb-ptab" data-tab="add" onclick="setLeftTab('add')">Add</button>
                <button class="fb-ptab" data-tab="page" onclick="setLeftTab('page')">Page</button>
            </div>
```

- [ ] **Step 2: Add the empty tab container**

Locate (around line 485, just inside `.fb-panel-content`):
```html
            <div class="fb-panel-content" id="left-content">
                <!-- Layers Tab -->
                <div class="fb-layers" id="tab-layers"></div>

                <!-- Add Elements Tab -->
                <div class="fb-add-grid" id="tab-add" style="display:none">
                    ...
                </div>
            </div>
```

After the closing `</div>` of `#tab-add` and before the closing `</div>` of `#left-content`, add:
```html
                <!-- Page Settings Tab -->
                <div id="tab-page" style="display:none"></div>
```

The result:
```html
            <div class="fb-panel-content" id="left-content">
                <!-- Layers Tab -->
                <div class="fb-layers" id="tab-layers"></div>

                <!-- Add Elements Tab -->
                <div class="fb-add-grid" id="tab-add" style="display:none">
                    ... existing add-element categories ...
                </div>

                <!-- Page Settings Tab -->
                <div id="tab-page" style="display:none"></div>
            </div>
```

- [ ] **Step 3: Extend setLeftTab to handle 'page'**

Locate (around line 1374):
```js
    window.setLeftTab = function(tab) {
        document.querySelectorAll('.fb-ptab').forEach(b => b.classList.toggle('on', b.dataset.tab === tab));
        document.getElementById('tab-layers').style.display = tab === 'layers' ? 'block' : 'none';
        document.getElementById('tab-add').style.display = tab === 'add' ? 'block' : 'none';
    };
```

Replace with:
```js
    window.setLeftTab = function(tab) {
        document.querySelectorAll('.fb-ptab').forEach(b => b.classList.toggle('on', b.dataset.tab === tab));
        document.getElementById('tab-layers').style.display = tab === 'layers' ? 'block' : 'none';
        document.getElementById('tab-add').style.display    = tab === 'add'    ? 'block' : 'none';
        document.getElementById('tab-page').style.display   = tab === 'page'   ? 'block' : 'none';
        if (tab === 'page') requestHead();
    };
```

Note: `requestHead` will be defined in Task 7. For now, the function call will throw a `ReferenceError` if a user actually clicks the Page tab — that's acceptable mid-plan and will resolve at Task 7. **Do not click the Page tab between Task 5 and Task 7.**

- [ ] **Step 4: Smoke check — third tab visible**

Hard-reload the builder. Left panel now shows three tabs: **Layers** | **Add** | **Page**. Clicking Layers and Add still works exactly as before. Do not click Page yet.

- [ ] **Step 5: Commit**

```bash
git add vm-admin/routes/builder/builder.php
git commit -m "feat(builder): add Page tab button + empty container"
```

---

## Task 6: Add the five SEO/social/twitter/icons/custom sections markup

**Files:**
- Modify: `vm-admin/routes/builder/builder.php` — fill the empty `#tab-page` div added in Task 5

- [ ] **Step 1: Replace the empty tab-page with the full markup**

Locate:
```html
                <!-- Page Settings Tab -->
                <div id="tab-page" style="display:none"></div>
```

Replace with:
```html
                <!-- Page Settings Tab -->
                <div id="tab-page" style="display:none">

                    <!-- SEO -->
                    <div class="fb-section">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            SEO <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">Title</span>
                                <input class="fb-input" id="prop-head-title" placeholder="Page title" oninput="onHeadFieldChange('title', this.value)">
                            </div>
                            <div style="color:var(--fig-text4);font-size:9px;margin:6px 0 4px;">Description</div>
                            <textarea class="fb-input" style="width:100%;" id="prop-head-description" rows="3" placeholder="Meta description" oninput="onHeadFieldChange('description', this.value)"></textarea>
                            <div class="fb-row" style="margin-top:6px">
                                <span class="fb-lbl">Keywords</span>
                                <input class="fb-input" id="prop-head-keywords" placeholder="comma, separated" oninput="onHeadFieldChange('keywords', this.value)">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Canonical</span>
                                <input class="fb-input" id="prop-head-canonical" placeholder="https://..." oninput="onHeadFieldChange('canonical', this.value)">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Robots</span>
                                <select class="fb-input" id="prop-head-robots" onchange="onHeadFieldChange('robots', this.value)">
                                    <option value="">—</option>
                                    <option value="index, follow">index, follow</option>
                                    <option value="noindex">noindex</option>
                                    <option value="nofollow">nofollow</option>
                                    <option value="noindex, nofollow">noindex, nofollow</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Social (Open Graph) -->
                    <div class="fb-section">
                        <div class="fb-sec-header" onclick="toggleSec(this)">
                            Social (Open Graph) <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">og:title</span>
                                <input class="fb-input" id="prop-head-ogTitle" placeholder="Shared title" oninput="onHeadFieldChange('ogTitle', this.value)">
                            </div>
                            <div style="color:var(--fig-text4);font-size:9px;margin:6px 0 4px;">og:description</div>
                            <textarea class="fb-input" style="width:100%;" id="prop-head-ogDescription" rows="2" placeholder="Shared description" oninput="onHeadFieldChange('ogDescription', this.value)"></textarea>
                            <div class="fb-row" style="margin-top:6px">
                                <span class="fb-lbl">og:image</span>
                                <input class="fb-input" id="prop-head-ogImage" placeholder="https://..." oninput="onHeadFieldChange('ogImage', this.value)">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">og:url</span>
                                <input class="fb-input" id="prop-head-ogUrl" placeholder="https://..." oninput="onHeadFieldChange('ogUrl', this.value)">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">og:type</span>
                                <select class="fb-input" id="prop-head-ogType" onchange="onHeadFieldChange('ogType', this.value)">
                                    <option value="">—</option>
                                    <option value="website">website</option>
                                    <option value="article">article</option>
                                    <option value="product">product</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Twitter -->
                    <div class="fb-section">
                        <div class="fb-sec-header" onclick="toggleSec(this)">
                            Twitter <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">Card</span>
                                <select class="fb-input" id="prop-head-twitterCard" onchange="onHeadFieldChange('twitterCard', this.value)">
                                    <option value="">—</option>
                                    <option value="summary">summary</option>
                                    <option value="summary_large_image">summary_large_image</option>
                                </select>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Title</span>
                                <input class="fb-input" id="prop-head-twitterTitle" placeholder="Tweet title" oninput="onHeadFieldChange('twitterTitle', this.value)">
                            </div>
                            <div style="color:var(--fig-text4);font-size:9px;margin:6px 0 4px;">Description</div>
                            <textarea class="fb-input" style="width:100%;" id="prop-head-twitterDescription" rows="2" placeholder="Tweet description" oninput="onHeadFieldChange('twitterDescription', this.value)"></textarea>
                            <div class="fb-row" style="margin-top:6px">
                                <span class="fb-lbl">Image</span>
                                <input class="fb-input" id="prop-head-twitterImage" placeholder="https://..." oninput="onHeadFieldChange('twitterImage', this.value)">
                            </div>
                        </div>
                    </div>

                    <!-- Icons & Theme -->
                    <div class="fb-section">
                        <div class="fb-sec-header" onclick="toggleSec(this)">
                            Icons &amp; Theme <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">Favicon</span>
                                <input class="fb-input" id="prop-head-favicon" placeholder="/favicon.ico" oninput="onHeadFieldChange('favicon', this.value)">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Apple</span>
                                <input class="fb-input" id="prop-head-appleTouchIcon" placeholder="/apple-touch-icon.png" oninput="onHeadFieldChange('appleTouchIcon', this.value)">
                            </div>
                            <div style="color:var(--fig-text4);font-size:9px;margin:6px 0 4px;">Theme color</div>
                            <div class="fb-color-row">
                                <input type="color" class="fb-swatch" id="cp-head-themeColor" oninput="onHeadColorChange('themeColor', 'cp-head-themeColor', 'prop-head-themeColor')">
                                <input class="fb-input" id="prop-head-themeColor" placeholder="#000000" oninput="onHeadFieldChange('themeColor', this.value)">
                            </div>
                        </div>
                    </div>

                    <!-- Custom Head HTML -->
                    <div class="fb-section">
                        <div class="fb-sec-header" onclick="toggleSec(this)">
                            Custom Head HTML <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div style="color:var(--fig-text4);font-size:9px;margin-bottom:4px;">Inserted between sentinel comments at the end of &lt;head&gt;. Paste scripts, preconnect links, analytics, JSON-LD, etc.</div>
                            <textarea class="fb-input" style="width:100%;font-family:'SF Mono','Consolas',monospace;font-size:10px;" id="prop-head-customHead" rows="8" placeholder="<!-- analytics, preconnect, JSON-LD --&gt;" oninput="onHeadFieldChange('customHead', this.value)"></textarea>
                        </div>
                    </div>

                </div>
```

- [ ] **Step 2: Smoke check — markup renders without breaking the page**

Hard-reload the builder. The Layers and Add tabs still render normally. **Do not click the Page tab yet** (handlers wired in Task 7). View source / Elements panel: confirm `#tab-page` exists with five `.fb-section` children.

- [ ] **Step 3: Commit**

```bash
git add vm-admin/routes/builder/builder.php
git commit -m "feat(builder): Page tab markup — SEO + social + twitter + icons + custom head"
```

---

## Task 7: Wire requestHead / applyHeadData / onHeadFieldChange + HEAD_DATA message case

**Files:**
- Modify: `vm-admin/routes/builder/builder.php` — the `<script>` block: message-handler around line 1019, and a new helpers block near other `window.*` handlers (~line 1330)

- [ ] **Step 1: Add HEAD_DATA case to the message handler**

Locate (around line 1019):
```js
    window.addEventListener('message', function(e) {
        const msg = e.data;
        if (!msg || !msg.type) return;

        if (msg.type === 'ENGINE_READY') { engineReady = true; sendToIframe({ type: 'SET_MODE', mode: currentMode }); }
        if (msg.type === 'ELEMENT_SELECTED') { currentElement = msg; showProperties(msg); }
        ...
        if (msg.type === 'LAYERS_UPDATE') { renderLayers(msg.layers); }
        if (msg.type === 'HTML_SYNC_ERROR') { showToast('HTML error: ' + msg.error); }
    });
```

Add a `HEAD_DATA` case at the end of the `if (...)` chain (before the closing `});`):
```js
        if (msg.type === 'HEAD_DATA') { applyHeadData(msg.data); }
```

- [ ] **Step 2: Add head-related state at the top of the IIFE**

Near the existing `let currentMode = 'select';` (around line 981), add:
```js
    let headData = {};
    let suppressHeadEcho = false;
    const headDebounce = {};
```

- [ ] **Step 3: Add the head helpers**

Find the existing `window.onColorChange` definition (around line 1349). Above it (or anywhere clearly inside the IIFE among other `window.*` definitions), add:

```js
    // ── Page tab (head tags) ──
    function requestHead() {
        sendToIframe({ type: 'GET_HEAD' });
    }

    function applyHeadData(data) {
        headData = data || {};
        suppressHeadEcho = true;
        try {
            const fields = [
                'title','description','keywords','canonical','robots',
                'ogTitle','ogDescription','ogImage','ogUrl','ogType',
                'twitterCard','twitterTitle','twitterDescription','twitterImage',
                'favicon','appleTouchIcon','themeColor','customHead'
            ];
            fields.forEach(kind => {
                const el = document.getElementById('prop-head-' + kind);
                if (!el) return;
                const v = data && data[kind] != null ? data[kind] : '';
                if (el.tagName === 'SELECT') {
                    let matched = false;
                    for (let i = 0; i < el.options.length; i++) {
                        if (el.options[i].value === v) { el.selectedIndex = i; matched = true; break; }
                    }
                    if (!matched) el.selectedIndex = 0;
                } else {
                    el.value = v;
                }
            });
            // Sync theme-color swatch
            const themeVal = (data && data.themeColor) || '';
            const swatch = document.getElementById('cp-head-themeColor');
            if (swatch && /^#[0-9a-fA-F]{6}$/.test(themeVal)) swatch.value = themeVal;
        } finally {
            suppressHeadEcho = false;
        }
    }

    window.onHeadFieldChange = function(kind, value) {
        if (suppressHeadEcho) return;
        clearTimeout(headDebounce[kind]);
        headDebounce[kind] = setTimeout(() => {
            sendToIframe({ type: 'UPDATE_HEAD', kind, value });
        }, 200);
    };

    window.onHeadColorChange = function(kind, swatchId, inputId) {
        const val = document.getElementById(swatchId).value;
        document.getElementById(inputId).value = val;
        window.onHeadFieldChange(kind, val);
    };
```

- [ ] **Step 4: Smoke check — Page tab is now fully wired**

Hard-reload the builder. Click the **Page** tab:
- Sections render, **SEO** section is open, others collapsed (matches their `fb-sec-header` vs `fb-sec-header open` class).
- The Title field is pre-populated with the existing `<title>` text from the site cache (e.g. `TERRA // Thoughtful Objects & Apparel`).
- Other empty fields show empty inputs.

Edit the Title field — type ` EDIT` at the end. Switch to the Layers tab and back to Page. Title still shows the new value (it was written to the iframe and read back).

Type a description: `My test description`. In DevTools, inspect the iframe `<head>`:
```js
document.getElementById('builder-iframe').contentDocument.head.querySelector('meta[name="description"]').outerHTML
```
Expected: `<meta name="description" content="My test description">`

Clear the description field. Re-run the same DevTools snippet:
```js
document.getElementById('builder-iframe').contentDocument.head.querySelector('meta[name="description"]')
```
Expected: `null` (tag removed).

Edit the Custom Head textarea — paste `<link rel="preconnect" href="https://fonts.googleapis.com">`. After ~200ms, in DevTools:
```js
document.getElementById('builder-iframe').contentDocument.head.innerHTML.includes('vm-builder:custom-head:start')
```
Expected: `true`.

- [ ] **Step 5: Commit**

```bash
git add vm-admin/routes/builder/builder.php
git commit -m "feat(builder): wire Page tab — request/apply/edit head tags"
```

---

## Task 8: End-to-end manual verification (save + reload + persistence)

**Files:** none

- [ ] **Step 1: Set every field type at least once**

Reload the builder. Click **Page** tab. Edit:
- Title → `Smoke Test Title`
- Description → `Smoke test description.`
- Robots → select `noindex`
- og:title → `OG smoke title`
- og:image → `https://example.com/og.png`
- twitter:card → `summary_large_image`
- Favicon → `/favicon.ico`
- Theme color → click swatch, pick `#0d99ff`
- Custom Head HTML → `<script>window.SMOKE = 1;</script>`

- [ ] **Step 2: Save**

Click the **Save** button (top-right). Wait for the `Saved` toast.

- [ ] **Step 3: Inspect the persisted cache file**

From a terminal:
```bash
head -c 2000 sites/<your-domain>/builder.cache.html
```
Expected — within the head:
- `<title>Smoke Test Title</title>`
- `<meta name="description" content="Smoke test description.">`
- `<meta name="robots" content="noindex">`
- `<meta property="og:title" content="OG smoke title">`
- `<meta property="og:image" content="https://example.com/og.png">`
- `<meta name="twitter:card" content="summary_large_image">`
- `<link rel="icon" href="/favicon.ico">`
- `<meta name="theme-color" content="#0d99ff">`
- `<!-- vm-builder:custom-head:start -->`
- `<script>window.SMOKE = 1;</script>`
- `<!-- vm-builder:custom-head:end -->`

The engine's own `<script id="vb-engine-script">` should **not** be present (the existing `GET_HTML` strips it).

- [ ] **Step 4: Reload-persistence check**

Hard-reload the builder. Click **Page** tab. Confirm every field shows the value you set in Step 1, including theme-color swatch and the custom-head textarea.

- [ ] **Step 5: Cross-tab regression check**

Switch to Layers — layer tree renders correctly. Select an element in the iframe → the right panel (Design / Inspect) still works.
Switch to Add → element categories render. Insert a Section → it appears in the iframe.
Switch back to Page → values still populated.

- [ ] **Step 6: Clear-removes-tag persistence**

Clear the Robots select (back to `—`). Save. Re-inspect `builder.cache.html`:
```bash
grep -c 'name="robots"' sites/<your-domain>/builder.cache.html
```
Expected: `0` (tag removed).

Clear the Custom Head textarea. Save. Re-grep:
```bash
grep -c 'vm-builder:custom-head' sites/<your-domain>/builder.cache.html
```
Expected: `0` (both sentinel comments removed).

- [ ] **Step 7: Final commit (only if any tweaks were needed during smoke)**

If everything passed, no commit needed.
If you fixed a bug found during smoke:
```bash
git add vm-admin/routes/builder/
git commit -m "fix(builder): <what you fixed>"
```

---

## Done criteria

- All eight tasks committed.
- Page tab present and fully functional in left panel next to Layers / Add.
- All 18 head fields read on tab open, debounced-write on edit, removed when cleared, persisted through Save.
- Custom-head sentinel block round-trips through save/reload.
- Layers, Add, Design, and Inspect tabs still behave exactly as before.
- No PHP, schema, or backend changes.
