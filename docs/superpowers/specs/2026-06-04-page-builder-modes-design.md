# Page Builder: Interaction and Drag Modes — Design

**Date:** 2026-06-04
**Owner:** keenan-co
**Scope:** `vm-admin/routes/builder/` (`builder.php`, `engine.js`)

## Problem

The Page Builder (`/vm-admin/{domain}/builder`) currently has a single, implicit interaction model: clicks are intercepted to select elements for property editing, double-clicks enable inline content editing. Two needs are unmet:

1. The user cannot navigate the loaded site like a normal visitor. Links and buttons are swallowed by `e.preventDefault()` in the engine's click handler, so SPA-style routing or anchor navigation inside the iframe is impossible while editing.
2. There is no visual drag-to-reorder. Element movement is limited to up/down nudges via the floating actions toolbar (`MOVE_ELEMENT`), and only within the current parent.

## Goal

Introduce two explicit modes alongside the existing behavior, exposed as a 3-way toggle in the builder topbar:

- **Select** (default, unchanged behavior) — click to select, double-click to inline-edit, right panel shows properties.
- **Interaction** — links, buttons, forms, and JS-driven UI inside the iframe behave as on the live site. No selection, no overlays.
- **Drag** — direct drag-on-hover lets the user move any editable element into any valid container (full re-parenting).

## Non-Goals

- Multi-select drag
- Dragging from the Layers panel into the canvas
- Drag-to-trash drop zone
- Cross-page or cross-iframe drag
- Undo/redo support for drag operations (existing `undo()`/`redo()` topbar buttons are already stubs; out of scope)
- Persisting mode across sessions (always boots in Select)

## Architecture

Single-engine, mode-flag approach. `engine.js` (injected into the builder iframe) gains a `currentMode` state variable. Existing top-level event listeners gain a one-line guard at entry that branches on mode. Drag mode registers its own pointer listeners on entry and unregisters them on exit. The parent (`builder.php`) sends `SET_MODE` via `postMessage` when the user clicks a mode toggle button.

Rejected alternatives:

- **Separate engine bundles per mode** (re-inject script on switch) — flickers overlays, loses state, duplicates utilities, harder to share `selectedEl`.
- **Transparent overlay layer** for click capture — overlays don't actually reduce complexity, add positioning fragility on scroll/resize, hover detection becomes harder.

## Components

### 1. UI: Mode Switcher (parent — `builder.php`)

Three-button segmented control in `.fb-topbar-center`, inserted **before** the existing undo/redo buttons. Reuses `.fb-vp-group` and `.fb-vp-btn` styles so it visually matches the viewport switcher next to it.

```html
<div class="fb-vp-group" id="mode-group">
    <button class="fb-vp-btn on" data-mode="select"      onclick="setMode('select')"      title="Select (V)"><i class="bi bi-cursor-fill"></i></button>
    <button class="fb-vp-btn"    data-mode="interaction" onclick="setMode('interaction')" title="Interaction (H)"><i class="bi bi-hand-index"></i></button>
    <button class="fb-vp-btn"    data-mode="drag"        onclick="setMode('drag')"        title="Drag (M)"><i class="bi bi-arrows-move"></i></button>
</div>
<div class="fb-sep"></div>
```

Resulting topbar layout (left to right within center):
`[Select|Interact|Drag]  ↶ ↷  | [Desktop|Tablet|Mobile] | 100%`

Default active mode: **Select**.

### 2. Parent ↔ Engine Protocol

**New parent → iframe message:**

```js
{ type: 'SET_MODE', mode: 'select' | 'interaction' | 'drag' }
```

Sent on every mode toggle, and re-asserted on `ENGINE_READY` (in case the iframe was reloaded).

**New iframe → parent message:**

```js
{ type: 'MODE_CHANGED', mode: 'select' | 'interaction' | 'drag' }
```

Confirms the engine applied the mode. Parent uses this to update toolbar state defensively.

Existing messages (`ELEMENT_SELECTED`, `ELEMENT_DESELECTED`, `LAYERS_UPDATE`, `CONTENT_CHANGED`, `EDIT_MODE`, `HTML_CONTENT`, etc.) are unchanged. The engine suppresses event-driven emission in Interaction mode; in Drag mode, `LAYERS_UPDATE` and `ELEMENT_SELECTED` fire after a successful drop so the right panel re-syncs to the moved element.

### 3. Engine State and Event Gating

Add at the top of the IIFE in `engine.js`:

```js
let currentMode = 'select';        // 'select' | 'interaction' | 'drag'
let dragSourceEl = null;
let dragGhost = null;
let dragStartX = 0, dragStartY = 0;
let isDragging = false;
const DRAG_THRESHOLD = 4;          // px
```

Every existing top-level handler gets a guard at entry. Pattern (`click` shown; same applies to `mouseover`, `mousemove`, `mouseout`, `dblclick`, `keydown`, `focusout`, `input`):

```js
document.addEventListener('click', function (e) {
    if (currentMode === 'interaction') return;     // pass-through to page
    if (currentMode === 'drag') return;            // drag handlers own this
    // ...existing select-mode logic unchanged...
}, true);
```

`SET_MODE` handler:

```js
if (msg.type === 'SET_MODE') {
    const next = msg.mode;
    if (next === currentMode) return;
    resetVisualState();                 // clears overlays, cursor, exits inline edit
    currentMode = next;
    if (currentMode === 'drag')        enableDragMode();
    else                                disableDragMode();
    if (currentMode === 'interaction')  document.body.style.cursor = '';
    sendToParent({ type: 'MODE_CHANGED', mode: currentMode });
}
```

`resetVisualState()`:
- `hideOverlay(selectOverlay)`, `hideOverlay(hoverOverlay)`
- `tooltip.style.display = 'none'`
- `gridContainer.style.display = 'none'`
- If `isEditing` → call `exitEditMode()`
- `document.body.style.cursor = ''`
- Does **not** clear `selectedEl` reference (so switching to Drag preserves which element the right panel is showing).

### 4. Drag Mode Logic

`enableDragMode()`:

```js
function enableDragMode() {
    document.body.style.cursor = 'grab';
    document.addEventListener('mousedown', onDragStart, true);
    document.addEventListener('mousemove', onDragMove,  true);
    document.addEventListener('mouseup',   onDragEnd,   true);
}
```

`disableDragMode()` removes those listeners and any in-flight ghost/indicator.

**`onDragStart(e)`:**
- `el = getTarget(e)`; if not editable or is `<body>`/`<html>` → return
- `dragSourceEl = el`
- `dragStartX = e.clientX`, `dragStartY = e.clientY`
- `e.preventDefault()` (suppress text selection)
- Do **not** mutate DOM yet — wait for threshold

**`onDragMove(e)`:**
- If `!dragSourceEl` → return
- If `!isDragging` and `Math.hypot(e.clientX - dragStartX, e.clientY - dragStartY) < DRAG_THRESHOLD` → return
- If just crossed threshold:
  - `isDragging = true`
  - Create `dragGhost` as a clone of `dragSourceEl`, `position:fixed`, `pointer-events:none`, `opacity:0.6`, `z-index:100001`, sized to source rect; append to `document.body`
  - `document.body.style.cursor = 'grabbing'`
- Position ghost at `(e.clientX + 8, e.clientY + 8)`
- Hide ghost temporarily, call `document.elementFromPoint(e.clientX, e.clientY)`, restore ghost
- Walk up to nearest **container** ancestor (tagName in `CONTAINER_TAGS`, see below). Skip if ancestor is `dragSourceEl` or any descendant of it
- Highlight that container with a new `vb-drop-container` overlay (2px solid `#0d99ff` outline, positioned on container's rect)
- Within the container's children, find the child whose midpoint (along the container's flex/block axis) is closest to the cursor. Position the existing `.vb-drop-indicator` line at the insertion point:
  - Orientation: vertical if container's computed `flex-direction` is `row` or `row-reverse`; horizontal otherwise
  - Insertion point: between two children, or at the start/end if the cursor is past the first/last child

**`onDragEnd(e)`:**
- If `isDragging` and a valid `(targetContainer, anchorChild)` pair was last computed:
  - `targetContainer.insertBefore(dragSourceEl, anchorChild || null)` (null appends)
  - Reposition `selectOverlay` on `dragSourceEl`
  - `sendToParent(getElementInfo(dragSourceEl))` — right panel re-syncs
  - `sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() })`
- Always clean up: remove `dragGhost` from DOM, hide drop container overlay and drop indicator, reset `dragSourceEl = null`, `isDragging = false`, restore cursor to `'grab'` (still in Drag mode)
- If `isDragging` was never true (mouseup before threshold) → no-op, no select fallthrough

**Container set** (reuse the same list `INSERT_ELEMENT` already uses for `container` checks):

```js
const CONTAINER_TAGS = new Set([
    'DIV','SECTION','ARTICLE','HEADER','FOOTER','NAV','ASIDE','MAIN',
    'UL','OL','FORM','FIGURE','BODY'
]);
```

`BODY` is included so the user can drop at the top level.

**New overlay** (`vb-drop-container`) — added to the existing `injectStyles()` block:

```css
#vb-drop-container {
    position: fixed; pointer-events: none; z-index: 99996;
    border: 2px solid #0d99ff;
    background: rgba(13, 153, 255, 0.06);
    display: none;
}
```

Created lazily in `enableDragMode()` and removed in `disableDragMode()`.

**Edge cases:**

| Case | Behavior |
| --- | --- |
| Escape during drag | Cancel: remove ghost, hide indicators, reset state, no DOM change |
| Cursor leaves iframe during drag | Treat as Escape (listen `mouseleave` on `document`) |
| Drop on no valid container | No-op, source returns to origin |
| Drop on `dragSourceEl` itself or a descendant | Drop target rejected during `onDragMove` (never highlighted) |
| Source is `<body>` / `<html>` | `onDragStart` returns early |
| Mouseup before threshold | Treated as a click, no-op in Drag mode |
| Element with inline `position: absolute` | Drop proceeds; visual may jump — acceptable, user fixes in property panel |

### 5. Side Panel Behavior per Mode (parent — `builder.php`)

`setMode(mode)` in the parent (defined inside the existing IIFE):

```js
function setMode(mode) {
    currentMode = mode;
    document.querySelectorAll('#mode-group [data-mode]').forEach(b => {
        b.classList.toggle('on', b.dataset.mode === mode);
    });
    if (mode === 'interaction') {
        floatActions.classList.remove('show');
        panelEmpty.style.display = 'flex';
        panelDesign.style.display = 'none';
        panelInspect.style.display = 'none';
        panelEmpty.querySelector('p').innerHTML = 'Switch to Select mode<br>to edit elements';
    } else {
        const p = panelEmpty.querySelector('p');
        if (p.textContent.startsWith('Switch to Select')) {
            p.innerHTML = 'Select an element to<br>inspect its properties';
        }
        // Drag mode: if an element is currently selected, keep panel populated.
        // Otherwise empty state is fine.
    }
    sendToIframe({ type: 'SET_MODE', mode });
}
window.setMode = setMode;
```

Re-assert mode on engine ready:

```js
if (msg.type === 'ENGINE_READY') {
    engineReady = true;
    sendToIframe({ type: 'SET_MODE', mode: currentMode });
}
```

### 6. Keyboard Shortcuts (parent — `builder.php`)

Document-level `keydown` listener on the parent, registered in the IIFE init:

```js
document.addEventListener('keydown', function (e) {
    const t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key === 'v' || e.key === 'V') { setMode('select');      e.preventDefault(); }
    if (e.key === 'h' || e.key === 'H') { setMode('interaction'); e.preventDefault(); }
    if (e.key === 'm' || e.key === 'M') { setMode('drag');        e.preventDefault(); }
});
```

## Data Flow

```
User clicks topbar mode button
    │
    ▼
setMode('drag')  ──► sendToIframe({ type:'SET_MODE', mode:'drag' })
    │
    ▼ (parent updates panel UX immediately)
    │
                                                   ┌─────────────────────────┐
                                                   │ engine.js (in iframe)   │
                                                   │                         │
postMessage 'SET_MODE' ──────────────────────────► │ resetVisualState()      │
                                                   │ currentMode = 'drag'    │
                                                   │ enableDragMode()        │
                                                   │ register pointer hdlrs  │
                                                   │ cursor: grab            │
                                                   └────────────┬────────────┘
                                                                │
                                          ◄─── postMessage 'MODE_CHANGED'
```

```
Drag operation:

mousedown on element  ──► capture source, threshold-wait
mousemove crosses 4px ──► create ghost, highlight container, show drop indicator
mouseup over valid    ──► insertBefore, send ELEMENT_SELECTED + LAYERS_UPDATE
mouseup invalid       ──► clean up only, no DOM change
Escape during drag    ──► clean up only, no DOM change
```

## Error Handling

- **Container search returns `null`** (cursor over `<html>` margin): hide drop overlays, allow mouseup as no-op.
- **`insertBefore` throws** (target detached during drag): swallow, hide indicators, fire no message. The next `LAYERS_UPDATE` from another action will reconcile.
- **Engine not ready when parent sends `SET_MODE`**: queue is implicit — parent always re-sends mode on `ENGINE_READY`, so an early `SET_MODE` that arrives before the engine is listening is recovered on ready.
- **`SET_MODE` with unknown mode value**: engine ignores via explicit allow-list check (`if (!['select','interaction','drag'].includes(next)) return`) before any state mutation.

## Testing

Manual smoke tests (run all before considering the change complete):

1. **Mode toggle renders** — load `/vm-admin/{domain}/builder`, three icons present in topbar center, Select active.
2. **Select regression** — hover shows dashed overlay, click selects, double-click inline-edits, right panel populates, floating actions visible. **Must be unchanged from current behavior.**
3. **Interaction mode** — switch via toolbar button: hover overlays gone, cursor default, clicking a link in the iframe navigates / triggers the site's real handler, right panel shows "Switch to Select" hint, floating actions hidden.
4. **Drag basic reorder** — switch to Drag, drag a section past its sibling: ghost follows cursor, drop indicator line shows between siblings, drop reorders DOM, layers panel reflects new order.
5. **Drag re-parent** — drag a `<button>` from one `<section>` into a `<div>` in another section: target container highlights blue, drop succeeds, DOM reflects new parent.
6. **Drag self-prevention** — try to drop a `<section>` into its own descendant `<div>`: never highlights as valid target, mouseup is no-op.
7. **Drag cancel via Escape** — mid-drag press Escape: ghost removed, cursor reset to grab, source element unchanged in DOM.
8. **Drag cancel via mouse leave** — drag past the iframe edge: same as Escape.
9. **Mode switch mid-state** — select an element in Select, switch to Interaction, switch to Drag, back to Select: overlays/cursor reset cleanly at each transition; previously selected element is no longer highlighted after passing through Interaction (selected reference may persist but visual is cleared).
10. **Keyboard shortcuts** — focus the canvas region, press V/H/M, mode toggles. With focus inside any right-panel `<input>`, V/H/M type letters normally — no mode change.
11. **Save round-trip** — make changes in both Select (style edit) and Drag (re-parent), hit Save: `GET_HTML` returns the modified DOM, `builder.cache.html` is written, next page load shows the new structure.
12. **Engine reinjection** — trigger a reload of the iframe (e.g. via Save flow if it re-renders); after `ENGINE_READY` fires, the current mode is re-applied.

## Open Questions

None at design time. All scope questions resolved during brainstorming:

- Mode set: three modes (Select default, Interaction, Drag) — chosen.
- Drag scope: full re-parenting — chosen.
- Drag trigger: direct drag on hover with 4px threshold — chosen.
- Toolbar placement: topbar-center, left of undo/redo — chosen.
- Mode persistence: not persisted, resets to Select on load — chosen.

## Files Touched

| File | Change |
| --- | --- |
| `vm-admin/routes/builder/builder.php` | Add mode switcher HTML in topbar; add `setMode()` JS; add keyboard shortcut listener; re-assert mode on `ENGINE_READY` |
| `vm-admin/routes/builder/engine.js` | Add `currentMode` state and event gates; add `SET_MODE` message handler; add `resetVisualState()`; add `enableDragMode()`/`disableDragMode()`; add drag pointer handlers (`onDragStart`/`onDragMove`/`onDragEnd`); add `vb-drop-container` overlay and CSS |

No new files. No PHP/database changes. No theme or `/themes/` changes.
