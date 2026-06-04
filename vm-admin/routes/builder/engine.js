/**
 * SPA Website Builder Engine
 * Injected into the iframe to provide visual editing capabilities.
 * Communicates with the parent builder via postMessage.
 */
(function () {
    'use strict';

    // ── State ──
    let selectedEl = null;
    let hoveredEl = null;
    let isEditing = false;
    let currentMode = 'select';        // 'select' | 'interaction' | 'drag'
    let dragSourceEl = null;
    let dragGhost = null;
    let dragStartX = 0;
    let dragStartY = 0;
    let isDragging = false;
    let dropContainer = null;          // currently-highlighted drop container
    let dropAnchor = null;             // child to insertBefore (null = append)
    const DRAG_THRESHOLD = 4;          // px before drag starts
    const CONTAINER_TAGS = new Set([
        'DIV','SECTION','ARTICLE','HEADER','FOOTER','NAV','ASIDE','MAIN',
        'UL','OL','FORM','FIGURE','BODY'
    ]);

    // ── Overlay elements ──
    const hoverOverlay = createOverlay('vb-hover-overlay');
    const selectOverlay = createOverlay('vb-select-overlay');
    const tooltip = createTooltip();

    // ── Inject builder styles ──
    injectStyles();

    // ── Setup ──
    function injectStyles() {
        if (document.getElementById('vb-engine-styles')) return;
        const style = document.createElement('style');
        style.id = 'vb-engine-styles';
        style.textContent = `
            #vb-hover-overlay {
                position: fixed; pointer-events: none; z-index: 99998;
                border: 1.5px dashed rgba(59, 130, 246, 0.7);
                background: rgba(59, 130, 246, 0.04);
                transition: all 0.08s ease-out;
                display: none;
            }
            #vb-select-overlay {
                position: fixed; pointer-events: none; z-index: 99999;
                border: 2px solid #0d99ff;
                background: rgba(13, 153, 255, 0.04);
                display: none;
            }
            #vb-select-overlay::before {
                content: attr(data-tag);
                position: absolute; top: -20px; left: -2px;
                background: #0d99ff; color: #fff;
                font-size: 9px; font-weight: 600; font-family: 'Inter', sans-serif;
                padding: 2px 6px; border-radius: 2px 2px 0 0;
                text-transform: lowercase; letter-spacing: 0.3px;
            }
            #vb-select-overlay .vb-handle {
                position: absolute; width: 8px; height: 8px;
                background: #fff; border: 1.5px solid #0d99ff;
                border-radius: 50%; pointer-events: none;
            }
            #vb-select-overlay .vb-handle.tl { top: -4px; left: -4px; }
            #vb-select-overlay .vb-handle.tr { top: -4px; right: -4px; }
            #vb-select-overlay .vb-handle.bl { bottom: -4px; left: -4px; }
            #vb-select-overlay .vb-handle.br { bottom: -4px; right: -4px; }
            #vb-select-overlay .vb-handle.tm { top: -4px; left: 50%; margin-left: -4px; }
            #vb-select-overlay .vb-handle.bm { bottom: -4px; left: 50%; margin-left: -4px; }
            #vb-select-overlay .vb-handle.ml { top: 50%; margin-top: -4px; left: -4px; }
            #vb-select-overlay .vb-handle.mr { top: 50%; margin-top: -4px; right: -4px; }

            #vb-tooltip {
                position: fixed; z-index: 100000; pointer-events: none;
                background: #1e1e1e; color: #b3b3b3; font-size: 10px;
                font-family: 'Inter', sans-serif; padding: 3px 7px;
                border-radius: 3px;
                display: none; white-space: nowrap;
                box-shadow: 0 2px 8px rgba(0,0,0,0.5);
            }
            #vb-tooltip .vb-tip-tag { color: #fff; font-weight: 600; }
            #vb-tooltip .vb-tip-dim { color: #888; margin-left: 6px; }

            [data-vb-editing="true"] {
                outline: 2px solid #0d99ff !important;
                outline-offset: 1px;
                cursor: text !important;
            }

            .vb-grid-lines {
                position: fixed; pointer-events: none; z-index: 99997;
                display: none;
            }
            .vb-grid-line {
                position: absolute; background: rgba(255, 0, 128, 0.15);
            }
            .vb-grid-line.h { height: 1px; left: 0; right: 0; }
            .vb-grid-line.v { width: 1px; top: 0; bottom: 0; }

            .vb-drop-indicator {
                position: fixed; pointer-events: none; z-index: 99999;
                background: #0d99ff; display: none;
            }
            .vb-drop-indicator.horizontal { height: 2px; left: 0; right: 0; }
            .vb-drop-indicator.vertical { width: 2px; top: 0; bottom: 0; }

            #vb-drop-container {
                position: fixed; pointer-events: none; z-index: 99996;
                border: 2px solid #0d99ff;
                background: rgba(13, 153, 255, 0.06);
                display: none;
            }
        `;
        document.head.appendChild(style);
    }

    function createOverlay(id) {
        let el = document.getElementById(id);
        if (el) return el;
        el = document.createElement('div');
        el.id = id;
        if (id === 'vb-select-overlay') {
            el.innerHTML = '<div class="vb-handle tl"></div><div class="vb-handle tr"></div><div class="vb-handle bl"></div><div class="vb-handle br"></div><div class="vb-handle tm"></div><div class="vb-handle bm"></div><div class="vb-handle ml"></div><div class="vb-handle mr"></div>';
        }
        document.body.appendChild(el);
        return el;
    }

    function createTooltip() {
        let el = document.getElementById('vb-tooltip');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'vb-tooltip';
        document.body.appendChild(el);
        return el;
    }

    // ── Grid lines container ──
    const gridContainer = document.createElement('div');
    gridContainer.className = 'vb-grid-lines';
    document.body.appendChild(gridContainer);

    // ── Drop indicator ──
    const dropIndicator = document.createElement('div');
    dropIndicator.className = 'vb-drop-indicator horizontal';
    document.body.appendChild(dropIndicator);

    // ── Drop container highlight ──
    const dropContainerOverlay = document.createElement('div');
    dropContainerOverlay.id = 'vb-drop-container';
    document.body.appendChild(dropContainerOverlay);

    // ── Helpers ──
    const EDITABLE_TAGS = new Set([
        'H1','H2','H3','H4','H5','H6','P','SPAN','A','BUTTON','LABEL',
        'LI','TD','TH','BLOCKQUOTE','FIGCAPTION','DIV','SECTION','ARTICLE',
        'HEADER','FOOTER','NAV','ASIDE','MAIN','IMG','INPUT','TEXTAREA',
        'SELECT','UL','OL','TABLE','FORM','FIGURE','VIDEO','HR'
    ]);

    function isEditable(el) {
        if (!el || el.nodeType !== 1) return false;
        if (el.id && el.id.startsWith('vb-')) return false;
        if (el.classList && el.classList.contains('vb-grid-lines')) return false;
        if (el.classList && el.classList.contains('vb-drop-indicator')) return false;
        if (el === document.body || el === document.documentElement) return false;
        return EDITABLE_TAGS.has(el.tagName);
    }

    function getTarget(e) {
        let el = e.target;
        while (el && !isEditable(el)) el = el.parentElement;
        return el;
    }

    function positionOverlay(overlay, rect, tag) {
        overlay.style.display = 'block';
        overlay.style.left = rect.left + 'px';
        overlay.style.top = rect.top + 'px';
        overlay.style.width = rect.width + 'px';
        overlay.style.height = rect.height + 'px';
        if (tag) overlay.setAttribute('data-tag', tag);
    }

    function hideOverlay(overlay) {
        overlay.style.display = 'none';
    }

    function getElementInfo(el) {
        if (!el) return null;
        const cs = getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        return {
            type: 'ELEMENT_SELECTED',
            tag: el.tagName,
            id: el.id || '',
            classes: (el.className || '').toString().replace(/\bvb-[\w-]+\b/g, '').trim(),
            text: el.tagName === 'IMG' ? '' : el.innerText?.substring(0, 200) || '',
            src: el.tagName === 'IMG' ? (el.src || '') : '',
            href: el.tagName === 'A' ? (el.getAttribute('href') || '') : '',
            alt: el.alt || '',
            // Computed styles
            fontSize: el.style.fontSize || cs.fontSize,
            fontWeight: el.style.fontWeight || cs.fontWeight,
            fontFamily: cs.fontFamily,
            fontStyle: cs.fontStyle,
            color: cs.color,
            backgroundColor: cs.backgroundColor,
            textAlign: cs.textAlign,
            lineHeight: cs.lineHeight,
            letterSpacing: cs.letterSpacing,
            textDecoration: cs.textDecorationLine || cs.textDecoration,
            textTransform: cs.textTransform,
            // Box model
            paddingTop: cs.paddingTop, paddingRight: cs.paddingRight,
            paddingBottom: cs.paddingBottom, paddingLeft: cs.paddingLeft,
            marginTop: cs.marginTop, marginRight: cs.marginRight,
            marginBottom: cs.marginBottom, marginLeft: cs.marginLeft,
            // Border
            borderRadius: cs.borderRadius,
            borderTopLeftRadius: cs.borderTopLeftRadius,
            borderTopRightRadius: cs.borderTopRightRadius,
            borderBottomRightRadius: cs.borderBottomRightRadius,
            borderBottomLeftRadius: cs.borderBottomLeftRadius,
            borderWidth: cs.borderWidth,
            borderStyle: cs.borderStyle,
            borderColor: cs.borderColor,
            // Size & Layout
            width: rect.width, height: rect.height,
            cssWidth: el.style.width || cs.width,
            cssHeight: el.style.height || cs.height,
            minWidth: cs.minWidth, maxWidth: cs.maxWidth,
            minHeight: cs.minHeight, maxHeight: cs.maxHeight,
            display: cs.display,
            position: cs.position,
            flexDirection: cs.flexDirection,
            justifyContent: cs.justifyContent,
            alignItems: cs.alignItems,
            gap: cs.gap,
            flexWrap: cs.flexWrap,
            overflow: cs.overflow,
            // Effects
            opacity: cs.opacity,
            boxShadow: cs.boxShadow,
            cursor: cs.cursor,
            // Background
            backgroundImage: cs.backgroundImage,
            backgroundSize: cs.backgroundSize,
            backgroundPosition: cs.backgroundPosition,
            // HTML snippet (outer element only, children summarized)
            htmlSnippet: getHtmlSnippet(el)
        };
    }

    function getHtmlSnippet(el) {
        try {
            const clone = el.cloneNode(true);
            // Remove builder overlays from clone
            clone.querySelectorAll('[id^="vb-"], [class*="vb-"]').forEach(n => n.remove());
            clone.removeAttribute('contenteditable');
            clone.removeAttribute('data-vb-editing');
            // Get outer HTML but limit size
            let html = clone.outerHTML;
            if (html.length > 3000) html = html.substring(0, 3000) + '\n<!-- ... truncated -->';
            return html;
        } catch(e) { return ''; }
    }

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

    function sendToParent(data) {
        window.parent.postMessage(data, '*');
    }

    // ── Build layers tree ──
    function buildLayersTree() {
        const layers = [];
        let index = 0;
        function walk(el, depth) {
            if (!el || el.nodeType !== 1) return;
            if (el.id && el.id.startsWith('vb-')) return;
            if (el.classList && (el.classList.contains('vb-grid-lines') || el.classList.contains('vb-grid-line') || el.classList.contains('vb-drop-indicator'))) return;
            if (el.tagName === 'SCRIPT' || el.tagName === 'STYLE' || el.tagName === 'LINK' || el.tagName === 'META') return;

            const tag = el.tagName.toLowerCase();
            const label = el.id ? `${tag}#${el.id}` : el.className ? `${tag}.${el.className.toString().split(' ')[0]}` : tag;
            const text = el.tagName === 'IMG' ? (el.alt || 'image') : (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3 ? el.textContent.substring(0, 30).trim() : '');

            layers.push({
                index: index++,
                tag: tag,
                label: label,
                text: text,
                depth: depth,
                selected: el === selectedEl,
                childCount: el.children.length
            });

            Array.from(el.children).forEach(child => walk(child, depth + 1));
        }
        Array.from(document.body.children).forEach(child => walk(child, 0));
        return layers;
    }

    // ── Grid lines ──
    function showGridLines(el) {
        gridContainer.innerHTML = '';
        if (!el) { gridContainer.style.display = 'none'; return; }

        const siblings = el.parentElement ? Array.from(el.parentElement.children) : [];
        if (siblings.length < 2) { gridContainer.style.display = 'none'; return; }

        gridContainer.style.display = 'block';

        siblings.forEach(sib => {
            if (sib.id && sib.id.startsWith('vb-')) return;
            if (sib === el) return;
            if (sib.classList && (sib.classList.contains('vb-grid-lines') || sib.classList.contains('vb-drop-indicator'))) return;
            const r = sib.getBoundingClientRect();
            addGridLine('h', r.top);
            addGridLine('h', r.top + r.height);
            addGridLine('v', r.left);
            addGridLine('v', r.left + r.width);
        });
    }

    function addGridLine(dir, pos) {
        const line = document.createElement('div');
        line.className = 'vb-grid-line ' + dir;
        if (dir === 'h') {
            line.style.top = pos + 'px';
        } else {
            line.style.left = pos + 'px';
        }
        gridContainer.appendChild(line);
    }

    // ── Event handlers ──
    document.addEventListener('mouseover', function (e) {
        if (currentMode !== 'select') return;
        if (isEditing) return;
        const el = getTarget(e);
        if (!el || el === selectedEl) {
            if (hoveredEl && hoveredEl !== selectedEl) hideOverlay(hoverOverlay);
            hoveredEl = null;
            gridContainer.style.display = 'none';
            return;
        }
        hoveredEl = el;
        const rect = el.getBoundingClientRect();
        positionOverlay(hoverOverlay, rect);
        showGridLines(el);

        const dims = `${Math.round(rect.width)} × ${Math.round(rect.height)}`;
        tooltip.innerHTML = `<span class="vb-tip-tag">${el.tagName.toLowerCase()}</span>` +
            (el.className ? `<span style="color:#9cdcfe">.${el.className.toString().split(' ')[0]}</span>` : '') +
            `<span class="vb-tip-dim">${dims}</span>`;
        tooltip.style.display = 'block';
    }, true);

    document.addEventListener('mousemove', function (e) {
        if (currentMode !== 'select') return;
        tooltip.style.left = (e.clientX + 14) + 'px';
        tooltip.style.top = (e.clientY - 28) + 'px';
    }, true);

    document.addEventListener('mouseout', function (e) {
        if (currentMode !== 'select') return;
        if (!e.relatedTarget || e.relatedTarget === document.documentElement) {
            hideOverlay(hoverOverlay);
            tooltip.style.display = 'none';
            gridContainer.style.display = 'none';
        }
    }, true);

    // ── Click to select ──
    document.addEventListener('click', function (e) {
        if (currentMode === 'interaction') return;     // pass-through to page
        if (currentMode === 'drag') return;            // drag handlers own this
        const el = getTarget(e);
        if (!el) { deselect(); return; }

        if (el.tagName === 'A' || el.tagName === 'BUTTON' || el.tagName === 'INPUT' || el.tagName === 'SELECT') {
            e.preventDefault();
        }
        e.stopPropagation();

        if (el === selectedEl && isEditing) return;
        if (el === selectedEl) return;

        select(el);
    }, true);

    // ── Double-click to edit inline ──
    document.addEventListener('dblclick', function (e) {
        if (currentMode !== 'select') return;
        const el = getTarget(e);
        if (!el) return;
        if (el.tagName === 'IMG' || el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' || el.tagName === 'HR') return;
        e.preventDefault();
        e.stopPropagation();

        if (selectedEl !== el) select(el);

        isEditing = true;
        el.setAttribute('data-vb-editing', 'true');
        el.contentEditable = 'true';
        el.focus();
        hideOverlay(selectOverlay);
        hideOverlay(hoverOverlay);
        gridContainer.style.display = 'none';

        sendToParent({ type: 'EDIT_MODE', editing: true });
    }, true);

    // ── Exit editing on blur or Escape ──
    document.addEventListener('keydown', function (e) {
        if (currentMode === 'interaction') return;
        if (e.key === 'Escape') {
            if (currentMode === 'drag') return;        // Drag handles its own Escape
            if (isEditing) exitEditMode();
            else deselect();
        }
        if (e.key === 'Delete' && selectedEl && !isEditing && currentMode === 'select') {
            e.preventDefault();
            sendToParent({ type: 'REQUEST_DELETE' });
        }
    }, true);

    document.addEventListener('focusout', function (e) {
        if (currentMode !== 'select') return;
        if (isEditing && selectedEl && !selectedEl.contains(e.relatedTarget)) {
            exitEditMode();
        }
    }, true);

    // ── Input tracking for live updates ──
    document.addEventListener('input', function () {
        if (currentMode !== 'select') return;
        if (isEditing && selectedEl) {
            sendToParent({ type: 'CONTENT_CHANGED', text: selectedEl.innerHTML });
        }
    }, true);

    // ── Selection / Deselection ──
    function select(el) {
        deselect(false);
        selectedEl = el;
        const rect = el.getBoundingClientRect();
        positionOverlay(selectOverlay, rect, el.tagName.toLowerCase());
        hideOverlay(hoverOverlay);
        sendToParent(getElementInfo(el));
        sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
    }

    function deselect(notify) {
        if (isEditing) exitEditMode();
        if (selectedEl) {
            selectedEl = null;
        }
        hideOverlay(selectOverlay);
        if (notify !== false) {
            sendToParent({ type: 'ELEMENT_DESELECTED' });
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        }
    }

    function exitEditMode() {
        if (!selectedEl) return;
        isEditing = false;
        selectedEl.contentEditable = 'false';
        selectedEl.removeAttribute('contenteditable');
        selectedEl.removeAttribute('data-vb-editing');
        const rect = selectedEl.getBoundingClientRect();
        positionOverlay(selectOverlay, rect, selectedEl.tagName.toLowerCase());
        sendToParent({ type: 'EDIT_MODE', editing: false });
        sendToParent({ type: 'CONTENT_CHANGED', html: selectedEl.innerHTML });
    }

    // ── Mode helpers ──
    function resetVisualState() {
        if (isEditing) exitEditMode();
        hideOverlay(selectOverlay);
        hideOverlay(hoverOverlay);
        tooltip.style.display = 'none';
        gridContainer.style.display = 'none';
        document.body.style.cursor = '';
    }

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

    function onDragStart(e) {
        if (e.button !== 0) return;
        const el = getTarget(e);
        if (!el || el === document.body) return;
        dragSourceEl = el;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        e.preventDefault();
        e.stopPropagation();
    }

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

    function updateDropTarget(x, y) {
        if (!dragSourceEl) return;

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
        return null;
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

    function onDragEnd(e) {
        if (!isDragging) {
            cleanupDrag();
            return;
        }
        if (dropContainer) {
            try {
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
        }
        cleanupDrag();
        document.body.style.cursor = 'grab';
    }

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

    // ── Scroll handler: reposition overlays ──
    document.addEventListener('scroll', function () {
        if (selectedEl) {
            const rect = selectedEl.getBoundingClientRect();
            positionOverlay(selectOverlay, rect, selectedEl.tagName.toLowerCase());
        }
    }, true);

    window.addEventListener('resize', function () {
        if (selectedEl) {
            const rect = selectedEl.getBoundingClientRect();
            positionOverlay(selectOverlay, rect, selectedEl.tagName.toLowerCase());
        }
    });

    // ── Receive commands from parent ──
    window.addEventListener('message', function (e) {
        const msg = e.data;
        if (!msg || !msg.type) return;

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

        if (msg.type === 'APPLY_STYLE' && selectedEl) {
            Object.entries(msg.styles).forEach(([prop, val]) => {
                selectedEl.style[prop] = val;
            });
            const rect = selectedEl.getBoundingClientRect();
            positionOverlay(selectOverlay, rect, selectedEl.tagName.toLowerCase());
            sendToParent(getElementInfo(selectedEl));
        }

        if (msg.type === 'SET_ATTRIBUTE' && selectedEl) {
            if (msg.attr === 'src' && selectedEl.tagName === 'IMG') selectedEl.src = msg.value;
            if (msg.attr === 'href' && selectedEl.tagName === 'A') selectedEl.setAttribute('href', msg.value);
            if (msg.attr === 'alt') selectedEl.alt = msg.value;
            if (msg.attr === 'innerText') selectedEl.innerText = msg.value;
            if (msg.attr === 'innerHTML') selectedEl.innerHTML = msg.value;
            if (msg.attr === 'id') selectedEl.id = msg.value;
            if (msg.attr === 'className') selectedEl.className = msg.value;
        }

        if (msg.type === 'SET_OUTER_HTML' && selectedEl) {
            try {
                const parent = selectedEl.parentElement;
                if (!parent) return;
                const temp = document.createElement('div');
                temp.innerHTML = msg.html;
                const newEl = temp.firstElementChild;
                if (newEl) {
                    parent.replaceChild(newEl, selectedEl);
                    select(newEl);
                    sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
                }
            } catch(e) {
                sendToParent({ type: 'HTML_SYNC_ERROR', error: e.message });
            }
        }

        if (msg.type === 'DELETE_ELEMENT' && selectedEl) {
            const el = selectedEl;
            deselect();
            el.remove();
            sendToParent({ type: 'ELEMENT_DELETED' });
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        }

        if (msg.type === 'DUPLICATE_ELEMENT' && selectedEl) {
            const clone = selectedEl.cloneNode(true);
            selectedEl.parentElement.insertBefore(clone, selectedEl.nextSibling);
            select(clone);
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        }

        if (msg.type === 'MOVE_ELEMENT' && selectedEl) {
            const parent = selectedEl.parentElement;
            if (!parent) return;
            if (msg.direction === 'up' && selectedEl.previousElementSibling) {
                parent.insertBefore(selectedEl, selectedEl.previousElementSibling);
            } else if (msg.direction === 'down' && selectedEl.nextElementSibling) {
                parent.insertBefore(selectedEl.nextElementSibling, selectedEl);
            }
            const rect = selectedEl.getBoundingClientRect();
            positionOverlay(selectOverlay, rect, selectedEl.tagName.toLowerCase());
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        }

        if (msg.type === 'INSERT_ELEMENT') {
            const el = createElementFromTemplate(msg.template);
            if (!el) return;

            // Insert relative to selected element or append to body
            if (selectedEl) {
                const container = ['DIV','SECTION','ARTICLE','HEADER','FOOTER','NAV','ASIDE','MAIN','UL','OL','FORM','FIGURE'].includes(selectedEl.tagName);
                if (container) {
                    selectedEl.appendChild(el);
                } else {
                    selectedEl.parentElement.insertBefore(el, selectedEl.nextSibling);
                }
            } else {
                document.body.appendChild(el);
            }
            select(el);
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        }

        if (msg.type === 'GET_HTML') {
            const clone = document.documentElement.cloneNode(true);
            clone.querySelectorAll('#vb-engine-styles, #vb-hover-overlay, #vb-select-overlay, #vb-tooltip, .vb-grid-lines, .vb-drop-indicator, script[data-vb-engine]').forEach(e => e.remove());
            clone.querySelectorAll('[data-vb-editing]').forEach(e => e.removeAttribute('data-vb-editing'));
            clone.querySelectorAll('[contenteditable]').forEach(e => e.removeAttribute('contenteditable'));
            sendToParent({ type: 'HTML_CONTENT', html: '<!DOCTYPE html>\n' + clone.outerHTML });
        }

        if (msg.type === 'SELECT_BY_INDEX') {
            const all = document.querySelectorAll('body *:not([id^="vb-"]):not(.vb-grid-lines):not(.vb-grid-line):not(.vb-drop-indicator):not(script):not(style)');
            if (all[msg.index]) select(all[msg.index]);
        }

        if (msg.type === 'GET_LAYERS') {
            sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
        }

        if (msg.type === 'GET_HEAD') {
            sendToParent({ type: 'HEAD_DATA', data: getHeadData() });
        }
    });

    // ── Element templates ──
    function createElementFromTemplate(template) {
        const templates = {
            // Layout
            'section': () => { const el = document.createElement('section'); el.style.cssText = 'padding: 60px 24px;'; el.innerHTML = '<h2>New Section</h2><p>Add your content here.</p>'; return el; },
            'container': () => { const el = document.createElement('div'); el.style.cssText = 'max-width: 1200px; margin: 0 auto; padding: 24px;'; return el; },
            'div': () => { const el = document.createElement('div'); el.style.cssText = 'padding: 16px;'; return el; },
            'flex-row': () => { const el = document.createElement('div'); el.style.cssText = 'display: flex; gap: 16px; padding: 16px;'; el.innerHTML = '<div style="flex:1;padding:16px;background:#f0f0f0;border-radius:8px;">Column 1</div><div style="flex:1;padding:16px;background:#f0f0f0;border-radius:8px;">Column 2</div>'; return el; },
            'flex-col': () => { const el = document.createElement('div'); el.style.cssText = 'display: flex; flex-direction: column; gap: 12px; padding: 16px;'; el.innerHTML = '<div style="padding:16px;background:#f0f0f0;border-radius:8px;">Row 1</div><div style="padding:16px;background:#f0f0f0;border-radius:8px;">Row 2</div>'; return el; },
            'grid-2': () => { const el = document.createElement('div'); el.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 16px;'; el.innerHTML = '<div style="padding:16px;background:#f0f0f0;border-radius:8px;">Cell 1</div><div style="padding:16px;background:#f0f0f0;border-radius:8px;">Cell 2</div><div style="padding:16px;background:#f0f0f0;border-radius:8px;">Cell 3</div><div style="padding:16px;background:#f0f0f0;border-radius:8px;">Cell 4</div>'; return el; },
            'grid-3': () => { const el = document.createElement('div'); el.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; padding: 16px;'; el.innerHTML = '<div style="padding:16px;background:#f0f0f0;border-radius:8px;">Cell 1</div><div style="padding:16px;background:#f0f0f0;border-radius:8px;">Cell 2</div><div style="padding:16px;background:#f0f0f0;border-radius:8px;">Cell 3</div>'; return el; },
            // Text
            'h1': () => { const el = document.createElement('h1'); el.textContent = 'Heading 1'; el.style.cssText = 'font-size: 2.5rem; font-weight: 800; margin-bottom: 12px;'; return el; },
            'h2': () => { const el = document.createElement('h2'); el.textContent = 'Heading 2'; el.style.cssText = 'font-size: 2rem; font-weight: 700; margin-bottom: 10px;'; return el; },
            'h3': () => { const el = document.createElement('h3'); el.textContent = 'Heading 3'; el.style.cssText = 'font-size: 1.5rem; font-weight: 600; margin-bottom: 8px;'; return el; },
            'paragraph': () => { const el = document.createElement('p'); el.textContent = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'; el.style.cssText = 'line-height: 1.7; color: #555;'; return el; },
            'span': () => { const el = document.createElement('span'); el.textContent = 'Text span'; return el; },
            'link': () => { const el = document.createElement('a'); el.href = '#'; el.textContent = 'Link text'; el.style.cssText = 'color: #0d99ff; text-decoration: underline;'; return el; },
            'blockquote': () => { const el = document.createElement('blockquote'); el.textContent = 'A quote or highlighted text goes here.'; el.style.cssText = 'border-left: 4px solid #0d99ff; padding: 16px 24px; margin: 16px 0; background: #f8f9fa; font-style: italic;'; return el; },
            // Media
            'image': () => { const el = document.createElement('img'); el.src = 'https://placehold.co/600x400/e2e8f0/64748b?text=Image'; el.alt = 'Placeholder'; el.style.cssText = 'max-width: 100%; height: auto; border-radius: 8px;'; return el; },
            'video': () => { const el = document.createElement('video'); el.setAttribute('controls', ''); el.style.cssText = 'max-width: 100%; border-radius: 8px;'; el.innerHTML = '<source src="" type="video/mp4">'; return el; },
            'hr': () => { const el = document.createElement('hr'); el.style.cssText = 'border: none; border-top: 1px solid #e0e0e0; margin: 24px 0;'; return el; },
            // Interactive
            'button': () => { const el = document.createElement('button'); el.textContent = 'Button'; el.style.cssText = 'padding: 12px 24px; background: #0d99ff; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;'; return el; },
            'button-outline': () => { const el = document.createElement('button'); el.textContent = 'Button'; el.style.cssText = 'padding: 12px 24px; background: transparent; color: #0d99ff; border: 2px solid #0d99ff; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;'; return el; },
            'input': () => { const el = document.createElement('input'); el.type = 'text'; el.placeholder = 'Enter text...'; el.style.cssText = 'padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; width: 100%;'; return el; },
            'form': () => { const el = document.createElement('form'); el.style.cssText = 'display: flex; flex-direction: column; gap: 12px; padding: 24px;'; el.innerHTML = '<input type="text" placeholder="Name" style="padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;"><input type="email" placeholder="Email" style="padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;"><button style="padding:12px 24px;background:#0d99ff;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Submit</button>'; return el; },
            // List
            'ul': () => { const el = document.createElement('ul'); el.style.cssText = 'padding-left: 24px; margin: 12px 0;'; el.innerHTML = '<li>List item 1</li><li>List item 2</li><li>List item 3</li>'; return el; },
            'ol': () => { const el = document.createElement('ol'); el.style.cssText = 'padding-left: 24px; margin: 12px 0;'; el.innerHTML = '<li>List item 1</li><li>List item 2</li><li>List item 3</li>'; return el; },
            // Prebuilt
            'hero': () => { const el = document.createElement('section'); el.style.cssText = 'padding: 80px 24px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;'; el.innerHTML = '<h1 style="font-size:2.5rem;font-weight:800;margin-bottom:12px;">Hero Title</h1><p style="font-size:1.1rem;opacity:0.9;max-width:500px;margin:0 auto 24px;">A subtitle or description goes here</p><button style="background:#fff;color:#764ba2;border:none;padding:14px 32px;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">Get Started</button>'; return el; },
            'card': () => { const el = document.createElement('div'); el.style.cssText = 'background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; max-width: 350px;'; el.innerHTML = '<img src="https://placehold.co/350x200/e2e8f0/64748b?text=Card+Image" style="width:100%;height:200px;object-fit:cover;" alt="Card"><div style="padding:20px;"><h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px;">Card Title</h3><p style="color:#666;line-height:1.6;margin-bottom:16px;">Card description text goes here.</p><button style="padding:10px 20px;background:#0d99ff;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Learn More</button></div>'; return el; },
            'nav': () => { const el = document.createElement('nav'); el.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);'; el.innerHTML = '<div style="font-weight:800;font-size:1.2rem;">Logo</div><div style="display:flex;gap:24px;"><a href="#" style="text-decoration:none;color:#333;font-weight:500;">Home</a><a href="#" style="text-decoration:none;color:#333;font-weight:500;">About</a><a href="#" style="text-decoration:none;color:#333;font-weight:500;">Contact</a></div>'; return el; },
            'footer': () => { const el = document.createElement('footer'); el.style.cssText = 'padding: 40px 24px; background: #1a1a1a; color: #999; text-align: center;'; el.innerHTML = '<p style="margin-bottom:16px;">© 2026 Your Company. All rights reserved.</p><div style="display:flex;justify-content:center;gap:16px;"><a href="#" style="color:#999;text-decoration:none;">Privacy</a><a href="#" style="color:#999;text-decoration:none;">Terms</a><a href="#" style="color:#999;text-decoration:none;">Contact</a></div>'; return el; },
            'testimonial': () => { const el = document.createElement('div'); el.style.cssText = 'padding: 32px; background: #f8f9fa; border-radius: 12px; text-align: center; max-width: 500px;'; el.innerHTML = '<p style="font-size:1.1rem;font-style:italic;color:#555;line-height:1.7;margin-bottom:16px;">"This product changed my life. Highly recommended!"</p><div style="font-weight:700;color:#333;">Jane Doe</div><div style="color:#888;font-size:0.9rem;">CEO, Company</div>'; return el; },
        };

        const factory = templates[template];
        return factory ? factory() : null;
    }

    // Notify parent we're ready
    sendToParent({ type: 'ENGINE_READY' });
    sendToParent({ type: 'LAYERS_UPDATE', layers: buildLayersTree() });
    sendToParent({ type: 'HEAD_DATA',    data: getHeadData() });
})();
