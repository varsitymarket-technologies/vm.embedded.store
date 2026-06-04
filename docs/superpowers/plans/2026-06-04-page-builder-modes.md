# Page Builder Modes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add three explicit modes (Select, Interaction, Drag) to the Page Builder, with Drag supporting full re-parenting of elements.

**Architecture:** Single-engine approach with a `currentMode` flag inside `engine.js`. Parent (`builder.php`) sends `SET_MODE` via `postMessage` from a new topbar toggle. Existing event handlers gain a one-line guard at entry; Drag mode registers its own pointer handlers on entry and removes them on exit. No new files.

**Tech Stack:** Vanilla JS (ES6+), PHP 7.4, postMessage between parent + iframe, Bootstrap Icons for UI.

**Spec:** `docs/superpowers/specs/2026-06-04-page-builder-modes-design.md`

**Testing approach:** This is browser-driven UI in a PHP-served admin page. There is no automated test runner for `engine.js`. Verification is done **manually in the running app** after each task — open `http://localhost:8016/vm-admin/{domain}/builder` and exercise the changes. Each task lists a specific in-browser smoke check. Use the Playwright MCP browser tools if running automated checks.

---

## Pre-flight

### Task 0: Verify clean working tree and dev server running

**Files:** none

- [ ] **Step 1: Check working tree is clean of staged changes**

Run:
```bash
git status --short
```
Expected: only untracked artifacts (screenshots, `tests/tmp/`, `.playwright-mcp/`, `module/`memory`/`) and the modified `.env`. No modifications under `vm-admin/`, `pages/`, `themes/`, or `docs/`.

- [ ] **Step 2: Confirm Docker dev server is up**

Run:
```bash
docker ps --filter "name=vm-emb-sites" --format "{{.Names}} {{.Status}}"
```
Expected: one line showing `vm-emb-sites` and `Up ...`. If empty, run `docker-compose up -d` from the repo root and re-check.

- [ ] **Step 3: Confirm builder page loads**

Open `http://localhost:8016/vm-admin/<your-domain>/builder` in a browser (replace `<your-domain>` with a domain that has a site set up — check `/vm-admin` home if unsure).
Expected: the page builder UI renders with the dark Figma-style chrome, iframe shows the site, layers tree appears in the left panel.

If the page is blank or errors, fix that first before proceeding — every subsequent task depends on this page loading.

---

## File Structure

| File | Responsibility | Touched by |
| --- | --- | --- |
| `vm-admin/routes/builder/builder.php` | Parent topbar UI, `setMode()` JS function, keyboard shortcut listener, `SET_MODE` dispatch, panel UX per mode | Tasks 1, 2, 5, 8 |
| `vm-admin/routes/builder/engine.js` | Iframe-side `currentMode` state, event gates, `SET_MODE` handler, drag pointer logic, drop overlays | Tasks 3, 4, 6, 7 |

No new files. No PHP backend changes. No theme changes.

---

## Task 1: Add mode-switcher HTML to topbar

**Files:**
- Modify: `vm-admin/routes/builder/builder.php` (insert before existing undo/redo in `.fb-topbar-center`)

- [ ] **Step 1: Locate the topbar-center block**

The current markup at around line 450 looks like:
```html
<div class="fb-topbar-center">
    <button class="fb-tbtn" onclick="undo()" title="Undo"><i class="bi bi-arrow-counterclockwise"></i></button>
    <button class="fb-tbtn" onclick="redo()" title="Redo"><i class="bi bi-arrow-clockwise"></i></button>
    <div class="fb-sep"></div>
    <div class="fb-vp-group">
        <button class="fb-vp-btn on" data-vp="desktop" ...
```

- [ ] **Step 2: Insert the mode toggle group**

Add immediately after `<div class="fb-topbar-center">` and **before** the first Undo button:

```html
<div class="fb-vp-group" id="mode-group">
    <button class="fb-vp-btn on" data-mode="select"      onclick="setMode('select')"      title="Select (V)"><i class="bi bi-cursor-fill"></i></button>
    <button class="fb-vp-btn"    data-mode="interaction" onclick="setMode('interaction')" title="Interaction (H)"><i class="bi bi-hand-index"></i></button>
    <button class="fb-vp-btn"    data-mode="drag"        onclick="setMode('drag')"        title="Drag (M)"><i class="bi bi-arrows-move"></i></button>
</div>
<div class="fb-sep"></div>
```

The resulting topbar-center order is: `[Mode toggle] [sep] [Undo] [Redo] [sep] [Viewport toggle] [sep] [Zoom]`.

- [ ] **Step 3: Reload and verify visually**

Hard-reload the builder page. Expected:
- Three new icons appear in the topbar center, left of Undo/Redo, styled identically to the viewport toggle group on their right.
- Cursor icon is highlighted blue (active state).
- Hovering each button shows the respective tooltip: "Select (V)", "Interaction (H)", "Drag (M)".
- Clicking the buttons does nothing yet (no JS wired) — that's expected.

- [ ] **Step 4: Commit**

```bash
git add vm-admin/routes/builder/builder.php
git commit -m "feat(builder): add mode toggle UI to topbar"
```

---

## Task 2: Add `setMode()` function (parent-side stub)

**Files:**
- Modify: `vm-admin/routes/builder/builder.php` (inside the IIFE starting at `<script>` near line 963)

- [ ] **Step 1: Find a good insertion point**

Locate the line `let currentRightTab = 'design';` inside the IIFE (around line 974). Add the mode state next to it.

- [ ] **Step 2: Add mode state**

Add immediately after `let currentRightTab = 'design';`:

```js
let currentMode = 'select';
```

- [ ] **Step 3: Add `setMode()` function**

Add this function just before the closing `})();` of the IIFE at the very bottom of the `<script>` block. Look for the final lines `loadSite(); iframe.addEventListener('load', loadSite);` (or similar init code) and add `setMode` definition before them:

```js
function setMode(mode) {
    if (!['select','interaction','drag'].includes(mode)) return;
    currentMode = mode;
    document.querySelectorAll('#mode-group [data-mode]').forEach(b => {
        b.classList.toggle('on', b.dataset.mode === mode);
    });
    if (mode === 'interaction') {
        floatActions.classList.remove('show');
        panelEmpty.style.display = 'flex';
        panelDesign.style.display = 'none';
        panelInspect.style.display = 'none';
        const p = panelEmpty.querySelector('p');
        if (p) p.innerHTML = 'Switch to Select mode<br>to edit elements';
    } else {
        const p = panelEmpty.querySelector('p');
        if (p && p.textContent.startsWith('Switch to Select')) {
            p.innerHTML = 'Select an element to<br>inspect its properties';
        }
    }
    sendToIframe({ type: 'SET_MODE', mode });
}
window.setMode = setMode;
```

- [ ] **Step 4: Re-assert mode on engine ready**

Find the existing `if (msg.type === 'ENGINE_READY') { engineReady = true; }` line (around line 1016). Modify it to also re-send the mode:

```js
if (msg.type === 'ENGINE_READY') {
    engineReady = true;
    sendToIframe({ type: 'SET_MODE', mode: currentMode });
}
```

- [ ] **Step 5: Reload and verify in browser**

Hard-reload the builder page. Open browser DevTools console. Expected:
- No JS errors on load.
- Clicking the **Interaction** button: it becomes the `on` (blue) one, the other two lose `on`. Right panel shows "Switch to Select mode to edit elements" hint. Floating actions toolbar is hidden (if an element was selected).
- Clicking **Drag**: it becomes active, Interaction loses active. Right panel hint clears (or shows the normal "Select an element..." empty state).
- Clicking **Select**: returns to default state.
- Run in console: `window.setMode` — should be a function reference.
- Run in console: nothing observable happens in the iframe yet (engine doesn't know about modes) — that's expected; Task 3 wires the engine side.

- [ ] **Step 6: Commit**

```bash
git add vm-admin/routes/builder/builder.php
git commit -m "feat(builder): wire setMode() parent-side, dispatch SET_MODE to iframe"
```

---

## Task 3: Add `currentMode` state and `SET_MODE` handler in engine.js

**Files:**
- Modify: `vm-admin/routes/builder/engine.js`

- [ ] **Step 1: Add mode state variables**

At the top of the IIFE in `engine.js`, find the `// ── State ──` block (around line 9):
```js
// ── State ──
let selectedEl = null;
let hoveredEl = null;
let isEditing = false;
```

Add directly under those three lines:
```js
let currentMode = 'select';        // 'select' | 'interaction' | 'drag'
let dragSourceEl = null;
let dragGhost = null;
let dragStartX = 0;
let dragStartY = 0;
let isDragging = false;
let dropContainer = null;          // the currently-highlighted drop container
let dropAnchor = null;             // child to insertBefore (null = append)
const DRAG_THRESHOLD = 4;          // px before drag starts
const CONTAINER_TAGS = new Set([
    'DIV','SECTION','ARTICLE','HEADER','FOOTER','NAV','ASIDE','MAIN',
    'UL','OL','FORM','FIGURE','BODY'
]);
```

- [ ] **Step 2: Add `resetVisualState()` helper**

Find the `exitEditMode()` function (around line 434). Add this new function directly after it:

```js
function resetVisualState() {
    if (isEditing) exitEditMode();
    hideOverlay(selectOverlay);
    hideOverlay(hoverOverlay);
    tooltip.style.display = 'none';
    gridContainer.style.display = 'none';
    document.body.style.cursor = '';
}
```

- [ ] **Step 3: Add `SET_MODE` handler in the message listener**

Find the `window.addEventListener('message', function (e) {` block in the engine (around line 462). At the top of the handler, right after `if (!msg || !msg.type) return;`, add:

```js
if (msg.type === 'SET_MODE') {
    const next = msg.mode;
    if (!['select','interaction','drag'].includes(next)) return;
    if (next === currentMode) {
        sendToParent({ type: 'MODE_CHANGED', mode: currentMode });
        return;
    }
    resetVisualState();
    if (currentMode === 'drag') disableDragMode();
    currentMode = next;
    if (currentMode === 'drag') enableDragMode();
    sendToParent({ type: 'MODE_CHANGED', mode: currentMode });
    return;
}
```

- [ ] **Step 4: Add stub `enableDragMode()`/`disableDragMode()`**

Add these stubs directly after `resetVisualState()` so the `SET_MODE` handler doesn't throw a ReferenceError. Real implementation comes in Tasks 6–7:

```js
function enableDragMode() {
    document.body.style.cursor = 'grab';
}

function disableDragMode() {
    document.body.style.cursor = '';
    if (dragGhost) { dragGhost.remove(); dragGhost = null; }
    dragSourceEl = null;
    dropContainer = null;
    dropAnchor = null;
    isDragging = false;
}
```

- [ ] **Step 5: Reload and verify in browser**

Hard-reload the builder page. Open DevTools console for the **iframe** (right-click iframe → Inspect, or use the frame selector in DevTools).

Expected:
- No JS errors on load in either main frame or iframe.
- In iframe console run: `window.parent.postMessage({type:'SET_MODE', mode:'drag'}, '*');` — cursor in iframe becomes `grab` (you'll see it when hovering over the iframe area).
- Click the **Select** mode button in the topbar: iframe cursor returns to default.
- Click **Drag** in the topbar: iframe cursor is `grab`.
- Click **Interaction** in topbar: iframe cursor is default; any selected outlines clear.

- [ ] **Step 6: Commit**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder): engine SET_MODE handler + mode state, drag stubs"
```

---

## Task 4: Gate existing event handlers on mode

**Files:**
- Modify: `vm-admin/routes/builder/engine.js`

- [ ] **Step 1: Gate `mouseover` handler**

Find `document.addEventListener('mouseover', function (e) {` (around line 315). The current body starts with `if (isEditing) return;`. Add a mode check immediately above it so the function body begins:

```js
document.addEventListener('mouseover', function (e) {
    if (currentMode !== 'select') return;
    if (isEditing) return;
    const el = getTarget(e);
    // ...rest unchanged
```

- [ ] **Step 2: Gate `mousemove` handler**

Find `document.addEventListener('mousemove', function (e) {` (around line 336). Add at the very top of the function body:

```js
document.addEventListener('mousemove', function (e) {
    if (currentMode !== 'select') return;
    tooltip.style.left = (e.clientX + 14) + 'px';
    tooltip.style.top = (e.clientY - 28) + 'px';
}, true);
```

- [ ] **Step 3: Gate `mouseout` handler**

Find `document.addEventListener('mouseout', function (e) {` (around line 341). Add at the top:

```js
document.addEventListener('mouseout', function (e) {
    if (currentMode !== 'select') return;
    if (!e.relatedTarget || e.relatedTarget === document.documentElement) {
        hideOverlay(hoverOverlay);
        tooltip.style.display = 'none';
        gridContainer.style.display = 'none';
    }
}, true);
```

- [ ] **Step 4: Gate `click` handler**

Find `document.addEventListener('click', function (e) {` (around line 350). Add at the top:

```js
document.addEventListener('click', function (e) {
    if (currentMode === 'interaction') return;     // pass-through to page
    if (currentMode === 'drag') return;            // drag handlers own this
    const el = getTarget(e);
    if (!el) { deselect(); return; }
    // ...rest unchanged
```

- [ ] **Step 5: Gate `dblclick` handler**

Find `document.addEventListener('dblclick', function (e) {` (around line 366). Add at the top:

```js
document.addEventListener('dblclick', function (e) {
    if (currentMode !== 'select') return;
    const el = getTarget(e);
    // ...rest unchanged
```

- [ ] **Step 6: Gate `keydown` handler (partial — Escape stays universal)**

Find `document.addEventListener('keydown', function (e) {` (around line 387). The Escape key should still work in Drag mode to cancel an in-flight drag (handled in Task 6); in Interaction it has no effect; in Select it's the existing deselect/exit edit logic. Keep the existing logic gated so it only fires in Select mode:

```js
document.addEventListener('keydown', function (e) {
    if (currentMode === 'interaction') return;
    if (e.key === 'Escape') {
        if (currentMode === 'drag') return;        // Drag handles its own Escape (Task 6)
        if (isEditing) exitEditMode();
        else deselect();
    }
    if (e.key === 'Delete' && selectedEl && !isEditing && currentMode === 'select') {
        e.preventDefault();
        sendToParent({ type: 'REQUEST_DELETE' });
    }
}, true);
```

- [ ] **Step 7: Gate `focusout` handler**

Find `document.addEventListener('focusout', function (e) {` (around line 398). Add at the top:

```js
document.addEventListener('focusout', function (e) {
    if (currentMode !== 'select') return;
    if (isEditing && selectedEl && !selectedEl.contains(e.relatedTarget)) {
        exitEditMode();
    }
}, true);
```

- [ ] **Step 8: Gate `input` handler**

Find `document.addEventListener('input', function () {` (around line 405). Add at the top:

```js
document.addEventListener('input', function () {
    if (currentMode !== 'select') return;
    if (isEditing && selectedEl) {
        sendToParent({ type: 'CONTENT_CHANGED', text: selectedEl.innerHTML });
    }
}, true);
```

- [ ] **Step 9: Reload and verify in browser**

Hard-reload. Test the three modes:

**Select mode (default):**
- Hover any element → blue dashed outline appears, tooltip shows tag + size. Should be **identical** to current behavior.
- Click a heading → selected (solid blue outline), right panel populates.
- Double-click → inline editing works.
- Escape → deselects.

**Interaction mode (click the hand icon):**
- Hover any element → **no** dashed outline, no tooltip.
- Click a link inside the iframe (e.g. a navbar link) → it should attempt to navigate / behave as the real site. If the loaded site is a single-page snapshot with no working links, you may see anchor jumps or no movement — that's correct behavior (the link is no longer being swallowed).
- Click a button → triggers any onclick handler the site has.
- Right panel shows "Switch to Select mode to edit elements" hint.

**Drag mode (click the arrows icon):**
- Cursor is `grab` over iframe.
- Hover → no outlines.
- Click → nothing (drag handlers come in Task 6).

**Switch between modes**: overlays clear cleanly. If you were inline-editing in Select mode and switch to Interaction, edit mode exits without losing content.

- [ ] **Step 10: Commit**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder): gate engine event handlers on currentMode"
```

---

## Task 5: Parent-side keyboard shortcuts V/H/M

**Files:**
- Modify: `vm-admin/routes/builder/builder.php`

- [ ] **Step 1: Add the keyboard listener**

In `builder.php`, inside the IIFE (the same `<script>` block where `setMode()` was added in Task 2), add this listener just before the `loadSite()` call at the bottom:

```js
document.addEventListener('keydown', function (e) {
    const t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    const k = e.key.toLowerCase();
    if (k === 'v') { setMode('select');      e.preventDefault(); }
    if (k === 'h') { setMode('interaction'); e.preventDefault(); }
    if (k === 'm') { setMode('drag');        e.preventDefault(); }
});
```

- [ ] **Step 2: Reload and verify**

Hard-reload the builder page. Click somewhere outside any input in the builder UI (e.g. the canvas chrome) to ensure focus is on `document.body`.

Expected:
- Press `V` → Select mode activates (cursor button highlights).
- Press `H` → Interaction mode activates (hand icon highlights).
- Press `M` → Drag mode activates (arrows icon highlights), iframe cursor becomes `grab`.
- Focus into the right panel's "Class" input field and type `vhm` → letters appear in the input, mode does **not** change.

- [ ] **Step 3: Commit**

```bash
git add vm-admin/routes/builder/builder.php
git commit -m "feat(builder): keyboard shortcuts V/H/M for mode switching"
```

---

## Task 6: Drag pointer handlers — basic reorder (no re-parent yet)

**Files:**
- Modify: `vm-admin/routes/builder/engine.js`

This task implements drag with **sibling-only** reorder so we can verify the pointer logic end-to-end before adding the re-parent complexity. Task 7 extends it to full re-parenting.

- [ ] **Step 1: Add drop-container overlay CSS**

In `engine.js`, find `injectStyles()` (around line 23). Inside the template literal `style.textContent = ...`, add a new rule before the closing backtick:

```css
#vb-drop-container {
    position: fixed; pointer-events: none; z-index: 99996;
    border: 2px solid #0d99ff;
    background: rgba(13, 153, 255, 0.06);
    display: none;
}
```

- [ ] **Step 2: Create the drop-container overlay element**

Find `// ── Drop indicator ──` (around line 127). Add a sibling block immediately after the drop indicator creation:

```js
// ── Drop container highlight ──
const dropContainerOverlay = document.createElement('div');
dropContainerOverlay.id = 'vb-drop-container';
document.body.appendChild(dropContainerOverlay);
```

- [ ] **Step 3: Replace stub `enableDragMode()` and `disableDragMode()`**

Find the stubs added in Task 3 and replace with the real versions:

```js
function enableDragMode() {
    document.body.style.cursor = 'grab';
    document.addEventListener('mousedown', onDragStart, true);
    document.addEventListener('mousemove', onDragMove,  true);
    document.addEventListener('mouseup',   onDragEnd,   true);
    document.addEventListener('keydown',   onDragKey,   true);
    document.addEventListener('mouseleave', onDragCancel, true);
}

function disableDragMode() {
    document.body.style.cursor = '';
    document.removeEventListener('mousedown', onDragStart, true);
    document.removeEventListener('mousemove', onDragMove,  true);
    document.removeEventListener('mouseup',   onDragEnd,   true);
    document.removeEventListener('keydown',   onDragKey,   true);
    document.removeEventListener('mouseleave', onDragCancel, true);
    cleanupDrag();
}

function cleanupDrag() {
    if (dragGhost) { dragGhost.remove(); dragGhost = null; }
    dropContainerOverlay.style.display = 'none';
    dropIndicator.style.display = 'none';
    dragSourceEl = null;
    dropContainer = null;
    dropAnchor = null;
    isDragging = false;
}
```

- [ ] **Step 4: Add `onDragStart`**

Add this function directly after `cleanupDrag()`:

```js
function onDragStart(e) {
    if (e.button !== 0) return;                    // left-click only
    const el = getTarget(e);
    if (!el || el === document.body) return;
    dragSourceEl = el;
    dragStartX = e.clientX;
    dragStartY = e.clientY;
    e.preventDefault();
    e.stopPropagation();
}
```

- [ ] **Step 5: Add `onDragMove` (sibling-only version)**

```js
function onDragMove(e) {
    if (!dragSourceEl) return;
    if (!isDragging) {
        const dx = e.clientX - dragStartX;
        const dy = e.clientY - dragStartY;
        if (Math.hypot(dx, dy) < DRAG_THRESHOLD) return;
        beginDrag();
    }
    positionGhost(e.clientX, e.clientY);
    updateDropTarget(e.clientX, e.clientY);
}

function beginDrag() {
    isDragging = true;
    document.body.style.cursor = 'grabbing';
    const rect = dragSourceEl.getBoundingClientRect();
    dragGhost = dragSourceEl.cloneNode(true);
    dragGhost.style.cssText += `
        position: fixed !important;
        pointer-events: none !important;
        opacity: 0.6 !important;
        z-index: 100001 !important;
        left: ${rect.left}px !important;
        top: ${rect.top}px !important;
        width: ${rect.width}px !important;
        height: ${rect.height}px !important;
        margin: 0 !important;
        transform: none !important;
    `;
    document.body.appendChild(dragGhost);
}

function positionGhost(x, y) {
    if (!dragGhost) return;
    dragGhost.style.left = (x + 8) + 'px';
    dragGhost.style.top  = (y + 8) + 'px';
}
```

- [ ] **Step 6: Add `updateDropTarget` (sibling-only — uses dragSourceEl's parent)**

```js
function updateDropTarget(x, y) {
    if (!dragSourceEl || !dragSourceEl.parentElement) {
        dropContainerOverlay.style.display = 'none';
        dropIndicator.style.display = 'none';
        dropContainer = null;
        dropAnchor = null;
        return;
    }
    dropContainer = dragSourceEl.parentElement;
    highlightContainer(dropContainer);
    dropAnchor = findInsertionAnchor(dropContainer, x, y);
    showInsertionLine(dropContainer, dropAnchor);
}

function highlightContainer(container) {
    const r = container.getBoundingClientRect();
    dropContainerOverlay.style.display = 'block';
    dropContainerOverlay.style.left = r.left + 'px';
    dropContainerOverlay.style.top = r.top + 'px';
    dropContainerOverlay.style.width = r.width + 'px';
    dropContainerOverlay.style.height = r.height + 'px';
}

function findInsertionAnchor(container, x, y) {
    const cs = getComputedStyle(container);
    const horizontal = cs.flexDirection === 'row' || cs.flexDirection === 'row-reverse';
    const children = Array.from(container.children).filter(c =>
        c !== dragSourceEl && c !== dragGhost &&
        !(c.id && c.id.startsWith('vb-')) &&
        !(c.classList && (c.classList.contains('vb-grid-lines') || c.classList.contains('vb-drop-indicator')))
    );
    for (const child of children) {
        const r = child.getBoundingClientRect();
        const mid = horizontal ? (r.left + r.width / 2) : (r.top + r.height / 2);
        const cursor = horizontal ? x : y;
        if (cursor < mid) return child;
    }
    return null;     // append at end
}

function showInsertionLine(container, anchor) {
    const cs = getComputedStyle(container);
    const horizontal = cs.flexDirection === 'row' || cs.flexDirection === 'row-reverse';
    dropIndicator.className = 'vb-drop-indicator ' + (horizontal ? 'vertical' : 'horizontal');
    dropIndicator.style.display = 'block';
    const cRect = container.getBoundingClientRect();
    if (anchor) {
        const aRect = anchor.getBoundingClientRect();
        if (horizontal) {
            dropIndicator.style.left = aRect.left + 'px';
            dropIndicator.style.top = cRect.top + 'px';
            dropIndicator.style.height = cRect.height + 'px';
            dropIndicator.style.width = '2px';
            dropIndicator.style.right = '';
        } else {
            dropIndicator.style.top = aRect.top + 'px';
            dropIndicator.style.left = cRect.left + 'px';
            dropIndicator.style.width = cRect.width + 'px';
            dropIndicator.style.height = '2px';
            dropIndicator.style.right = '';
        }
    } else {
        // append at end — line at trailing edge of container
        if (horizontal) {
            dropIndicator.style.left = (cRect.right - 2) + 'px';
            dropIndicator.style.top = cRect.top + 'px';
            dropIndicator.style.height = cRect.height + 'px';
            dropIndicator.style.width = '2px';
            dropIndicator.style.right = '';
        } else {
            dropIndicator.style.top = (cRect.bottom - 2) + 'px';
            dropIndicator.style.left = cRect.left + 'px';
            dropIndicator.style.width = cRect.width + 'px';
            dropIndicator.style.height = '2px';
            dropIndicator.style.right = '';
        }
    }
}
```

- [ ] **Step 7: Add `onDragEnd`**

```js
function onDragEnd(e) {
    if (!isDragging) {
        cleanupDrag();
        return;
    }
    if (dropContainer && dropContainer.contains(document.body) === false) {
        try {
            dropContainer.insertBefore(dragSourceEl, dropAnchor || null);
            // Re-sync right panel and layers
            if (selectedEl === dragSourceEl) {
                const rect = dragSourceEl.getBoundingClientRect();
                positionOverlay(selectOverlay, rect, dragSourceEl.tagName.toLowerCase());
                sendToParent(getElementInfo(dragSourceEl));
            }
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        } catch (err) {
            sendToParent({ type: 'HTML_SYNC_ERROR', error: err.message });
        }
    }
    cleanupDrag();
    document.body.style.cursor = 'grab';
}
```

- [ ] **Step 8: Add `onDragKey` and `onDragCancel`**

```js
function onDragKey(e) {
    if (e.key === 'Escape' && isDragging) {
        e.preventDefault();
        cleanupDrag();
        document.body.style.cursor = 'grab';
    }
}

function onDragCancel() {
    if (isDragging) {
        cleanupDrag();
        document.body.style.cursor = 'grab';
    }
}
```

- [ ] **Step 9: Reload and verify**

Hard-reload the builder page. Switch to **Drag** mode (click the arrows icon or press `M`).

Test sibling reorder:
- Find a section with multiple sibling elements (e.g. a `<section>` with several `<h2>`, `<p>`, `<button>` children).
- Mouse down on one of them and drag it past another sibling. Expected:
  - Ghost clone follows cursor with 60% opacity.
  - Parent container shows blue outline.
  - A blue line appears between siblings showing insertion point.
  - On release: element moves to the new position; layers panel updates; ghost disappears; cursor returns to `grab`.
- Press Escape mid-drag → ghost vanishes, no DOM change.
- Move cursor outside iframe mid-drag → ghost vanishes, no DOM change.

Verify Interaction and Select modes still work after switching back.

- [ ] **Step 10: Commit**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder): drag mode with sibling reorder, ghost + drop indicators"
```

---

## Task 7: Extend drag to full re-parenting

**Files:**
- Modify: `vm-admin/routes/builder/engine.js`

- [ ] **Step 1: Replace `updateDropTarget` with re-parent version**

Replace the function added in Task 6 with this version that looks up the actual element under the cursor and walks to the nearest valid container:

```js
function updateDropTarget(x, y) {
    if (!dragSourceEl) return;

    // Hide ghost so elementFromPoint sees through it
    const ghostDisplay = dragGhost ? dragGhost.style.display : '';
    if (dragGhost) dragGhost.style.display = 'none';
    const under = document.elementFromPoint(x, y);
    if (dragGhost) dragGhost.style.display = ghostDisplay;

    if (!under) {
        dropContainerOverlay.style.display = 'none';
        dropIndicator.style.display = 'none';
        dropContainer = null;
        dropAnchor = null;
        return;
    }

    // Walk up to nearest container that is NOT the source or a descendant of it
    let container = under;
    while (container && container !== document.body.parentElement) {
        if (CONTAINER_TAGS.has(container.tagName)
            && container !== dragSourceEl
            && !dragSourceEl.contains(container)) {
            break;
        }
        container = container.parentElement;
    }

    if (!container || container === document.body.parentElement) {
        dropContainerOverlay.style.display = 'none';
        dropIndicator.style.display = 'none';
        dropContainer = null;
        dropAnchor = null;
        return;
    }

    dropContainer = container;
    highlightContainer(dropContainer);
    dropAnchor = findInsertionAnchor(dropContainer, x, y);
    showInsertionLine(dropContainer, dropAnchor);
}
```

- [ ] **Step 2: Tighten `onDragEnd` self-drop guard**

The Task 6 version of `onDragEnd` already trusts `dropContainer`. The `updateDropTarget` function rejects bad targets, so no additional guard is needed. But for safety, add an explicit check before insertion. Find the `try {` line in `onDragEnd` and replace its body with:

```js
        try {
            // Defensive: never allow inserting into self or descendant
            if (dropContainer === dragSourceEl || dragSourceEl.contains(dropContainer)) {
                cleanupDrag();
                document.body.style.cursor = 'grab';
                return;
            }
            dropContainer.insertBefore(dragSourceEl, dropAnchor || null);
            if (selectedEl === dragSourceEl) {
                const rect = dragSourceEl.getBoundingClientRect();
                positionOverlay(selectOverlay, rect, dragSourceEl.tagName.toLowerCase());
                sendToParent(getElementInfo(dragSourceEl));
            }
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        } catch (err) {
            sendToParent({ type: 'HTML_SYNC_ERROR', error: err.message });
        }
```

- [ ] **Step 3: Reload and verify re-parenting**

Hard-reload. Switch to **Drag** mode.

Test re-parent:
- Pick a `<button>` inside one `<section>`. Drag it over a different `<section>`'s `<div>` (or directly over the section itself).
- Expected:
  - As cursor enters the target container, the **target** container highlights blue (not the source's parent).
  - Insertion line shows inside the target.
  - On drop: button is moved into the new container; layers panel reflects the change; if button was selected, right panel updates with its new position.

Test self-drop prevention:
- Pick a `<section>` that contains a `<div>`. Try to drag the section into its own `<div>`.
- Expected: when cursor is over any descendant of the source, the container search walks up past them; if no other valid container exists above the cursor, no highlight appears and mouseup is a no-op.

Test drop on `<body>`:
- Drag an element to an area outside any other container (e.g. near the very top or bottom of the page).
- Expected: `<body>` becomes the drop target (highlights), element drops at top-level position.

Test all previous cases still work:
- Sibling reorder still works (same parent, different position).
- Escape mid-drag cancels.
- Mouse leaves iframe cancels.

- [ ] **Step 4: Commit**

```bash
git add vm-admin/routes/builder/engine.js
git commit -m "feat(builder): drag mode full re-parenting via elementFromPoint walk"
```

---

## Task 8: Final integration smoke test

**Files:** none (verification only)

- [ ] **Step 1: Run the full manual smoke checklist from the spec**

Open `http://localhost:8016/vm-admin/{domain}/builder` and run each of the 12 smoke tests from the spec section "Testing". Mark each passing inline below.

- [ ] **Smoke 1** — Mode toggle visual: three icons in topbar, Select active by default.
- [ ] **Smoke 2** — Select regression: hover overlay, click select, dblclick edit, right panel populates, floating actions visible.
- [ ] **Smoke 3** — Interaction mode: no overlays, links clickable, right panel hint, floating actions hidden.
- [ ] **Smoke 4** — Drag sibling reorder: ghost, indicator, drop reorders, layers update.
- [ ] **Smoke 5** — Drag re-parent: container highlights, drop changes parent.
- [ ] **Smoke 6** — Self-drop prevented: no highlight when dropping into self/descendant.
- [ ] **Smoke 7** — Escape during drag: cancels cleanly.
- [ ] **Smoke 8** — Mouse leave during drag: cancels cleanly.
- [ ] **Smoke 9** — Mode switch mid-state: overlays reset cleanly at each transition.
- [ ] **Smoke 10** — Keyboard V/H/M outside inputs works; inside inputs types normally.
- [ ] **Smoke 11** — Save round-trip: changes in Select + Drag persist to `builder.cache.html`.
- [ ] **Smoke 12** — Engine reinjection: mode re-applies after iframe reload.

- [ ] **Step 2: Check console for errors**

Open DevTools. Verify zero errors and zero `HTML_SYNC_ERROR` toasts during the smoke tests.

- [ ] **Step 3: Commit any final touch-ups**

If any test revealed a fix, make the smallest possible patch, commit with `fix(builder): ...` message, and re-run only the affected smoke.

- [ ] **Step 4: Mark the implementation done**

No commit needed at this step — the work is shipped task-by-task above.

---

## Self-Review Summary

**Spec coverage:**
- UI mode switcher → Task 1
- Parent ↔ engine protocol (`SET_MODE`, `MODE_CHANGED`) → Tasks 2, 3
- Engine state + event gating → Tasks 3, 4
- Drag pointer logic (mousedown/move/up + threshold + ghost) → Task 6
- Drop container highlight + insertion indicator → Task 6
- Full re-parenting via `elementFromPoint` + container walk → Task 7
- Self-drop prevention → Task 7
- Side panel behavior per mode → Task 2
- Keyboard shortcuts V/H/M → Task 5
- Re-assert mode on `ENGINE_READY` → Task 2
- Drag cancel via Escape / mouseleave → Task 6
- Error handling (unknown mode, insertBefore throw) → Tasks 3, 6/7
- Manual smoke tests → Task 8

**Type/name consistency:**
- `currentMode`, `setMode`, `SET_MODE`, `MODE_CHANGED` used identically in parent and engine sections.
- `dragSourceEl`, `dropContainer`, `dropAnchor`, `dragGhost`, `isDragging`, `DRAG_THRESHOLD`, `CONTAINER_TAGS` declared once in Task 3, used consistently in Tasks 6 and 7.
- `enableDragMode`/`disableDragMode`/`cleanupDrag` defined once each, no name drift.
- `onDragStart`/`onDragMove`/`onDragEnd`/`onDragKey`/`onDragCancel` all referenced and defined.

**Placeholder scan:** No TBD/TODO/"appropriate"/"similar to" placeholders. All code blocks contain complete, copy-pasteable snippets.
