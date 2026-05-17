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
        tooltip.style.left = (e.clientX + 14) + 'px';
        tooltip.style.top = (e.clientY - 28) + 'px';
    }, true);

    document.addEventListener('mouseout', function (e) {
        if (!e.relatedTarget || e.relatedTarget === document.documentElement) {
            hideOverlay(hoverOverlay);
            tooltip.style.display = 'none';
            gridContainer.style.display = 'none';
        }
    }, true);

    // ── Click to select ──
    document.addEventListener('click', function (e) {
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
        if (e.key === 'Escape') {
            if (isEditing) exitEditMode();
            else deselect();
        }
        if (e.key === 'Delete' && selectedEl && !isEditing) {
            e.preventDefault();
            sendToParent({ type: 'REQUEST_DELETE' });
        }
    }, true);

    document.addEventListener('focusout', function (e) {
        if (isEditing && selectedEl && !selectedEl.contains(e.relatedTarget)) {
            exitEditMode();
        }
    }, true);

    // ── Input tracking for live updates ──
    document.addEventListener('input', function () {
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
})();
