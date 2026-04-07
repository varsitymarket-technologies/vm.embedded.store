<?php
// --- SETUP ---
$siteDir = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__;
$configFile = $siteDir . "/config.php";
$htmlFile = $siteDir . "/builder.cache.html";

// --- SAVE HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_all') {
    if (!is_dir($siteDir)) {
        mkdir($siteDir, 0755, true);
    }

    // Save Config
    $configContent = "<?php" . PHP_EOL;
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'CONF_') === 0) {
            $configContent .= "define(\"" . str_replace('CONF_', '', $key) . "\", \"" . addslashes($value) . "\");" . PHP_EOL;
        }
    }
    //file_put_contents($configFile, $configContent);

    // Save the full HTML (including updated <template> tags)
    if (isset($_POST['site_rendered_html'])) {
        file_put_contents($htmlFile, $_POST['site_rendered_html']);
    }

    header("Location: #");
}

// --- LOAD CONFIG ---
$content = file_exists($configFile) ? file_get_contents($configFile) : '';
preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);
$config = [];
if (!empty($matches[1])) {
    foreach ($matches[1] as $idx => $key) {
        $config[$key] = $matches[2][$idx];
    }
}
?>


<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .canvas-area {
        background: #0c0d0e;
        background-image: radial-gradient(#222 1px, transparent 1px);
        background-size: 24px 24px;
    }

    .field-card {
        background: #1c1d21;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    iframe {
        width: 100%;
        height: 100%;
        background: white;
        border: none;
    }
</style>

<body class="bg-[#0c0d0e] text-gray-300 overflow-hidden">

    <div class="flex h-screen w-full flex-col overflow-hidden">

        <!-- Sidebar replaced with Config Modal -->

        <?php @include_once "header.php"; ?>


        <main class="flex-1 canvas-area flex flex-col relative p-2 pt-2">
            <div class="p-4 md:p-6 border-b border-white/5 flex items-center justify-between bg-black/20 shrink-0">
                <span class="font-bold text-[10px] uppercase tracking-widest text-white">Page Editor</span>
                <div class="flex gap-2">
                    <button onclick="saveViaForm()"
                        style="border-radius: 10px; border-style: solid; border-width: 1px; border-color: #fff;"
                        class="cursor-pointer border border-white/10 border-radius-lg border-solid bg-gray-800 hover:bg-gray-700 text-white text-[10px] font-black px-4 py-2 rounded-full uppercase shadow-lg shadow-blue-900/20 transition-colors">
                        Save Changes
                    </button>
                </div>
            </div>

            <div id="wrapper"
                class="w-full h-full bg-white rounded-2xl overflow-hidden shadow-2xl border-[8px] border-[#16171a]">
                <iframe src="<?php echo __WEBSITE_URL__; ?>" id="editor-iframe"></iframe>
            </div>
        </main>
    </div>

    <form id="hiddenSaveForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="action" value="save_all">
        <input type="hidden" name="site_rendered_html" id="html_payload">
        <div id="config_inputs_container"></div>
    </form>

    <div id="configModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
        <div
            class="bg-[#1c1d21] p-6 rounded-2xl w-full max-w-md max-h-[80vh] overflow-y-auto custom-scrollbar border border-white/10 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-white font-bold uppercase tracking-wider text-xs">Site Settings</h2>
                <button onclick="closeConfigModal()" class="text-gray-400 hover:text-white"><i
                        class="bi bi-x-lg"></i></button>
            </div>

            <div class="space-y-6">
                <?php foreach (['Branding' => ['LOGO', 'ICON'], 'Content' => ['TITLE', 'TEXT']] as $name => $keys): ?>
                    <div>
                        <h3 class="text-[10px] text-gray-500 font-bold uppercase mb-4"><?php echo $name; ?></h3>
                        <div class="space-y-3">
                            <?php foreach ($config as $key => $val):
                                $match = false;
                                foreach ($keys as $k)
                                    if (strpos($key, $k) !== false)
                                        $match = true;
                                if (!$match)
                                    continue; ?>
                                <div class="field-card p-3 rounded-xl">
                                    <label
                                        class="text-[9px] text-gray-500 font-bold block mb-1 uppercase"><?php echo $key; ?></label>
                                    <textarea data-conf-key="CONF_<?php echo $key; ?>"
                                        class="w-full bg-transparent text-xs text-white outline-none min-h-[30px] resize-none"><?php echo $val; ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-8 flex justify-end">
                <button onclick="closeConfigModal()"
                    class="bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black px-4 py-2 rounded-full uppercase shadow-lg shadow-blue-900/20">Done</button>
            </div>
        </div>
    </div>

    <script>
        function openConfigModal() { document.getElementById('configModal').classList.remove('hidden'); }
        function closeConfigModal() { document.getElementById('configModal').classList.add('hidden'); }
    </script>

    <!--
<script>
    const iframe = document.getElementById('editor-iframe');

    iframe.onload = function() {
        const doc = iframe.contentDocument || iframe.contentWindow.document;

        // 1. Inject Tooltip UI
        const style = doc.createElement('style');
        style.innerHTML = `
            [contenteditable="true"]:hover, img:hover { outline: 2px dashed #3b82f6 !important; outline-offset: 2px; }
            #builder-tip { position: fixed; background: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; z-index: 9999; pointer-events: none; display: none; text-transform: uppercase; font-family: sans-serif; }
        `;
        doc.head.appendChild(style);

        const tip = doc.createElement('div');
        tip.id = 'builder-tip';
        doc.body.appendChild(tip);

        // 2. Setup Logic with Template Mirroring
        const setupElement = (el) => {
            if (el.dataset.editorReady) return;
            el.dataset.editorReady = "true";

            // Make editable
            if (el.tagName === 'IMG') {
                el.onclick = (e) => {
                    e.preventDefault();
                    const url = prompt("New Image URL:", el.src);
                    if (url) {
                        el.src = url;
                        syncChangeToTemplate(el);
                    }
                };
            } else {
                el.contentEditable = true;
                el.oninput = () => syncChangeToTemplate(el);
            }

            // Tooltip events
            el.onmouseover = () => { tip.style.display = 'block'; tip.innerText = el.tagName === 'IMG' ? 'Change Image' : 'Edit Content'; };
            el.onmousemove = (e) => { tip.style.left = (e.clientX + 10) + 'px'; tip.style.top = (e.clientY - 30) + 'px'; };
            el.onmouseleave = () => { tip.style.display = 'none'; };
        };

        // 3. The Mirroring Function
        // This finds where the element exists in the <template> tags and updates it there
        const syncChangeToTemplate = (liveEl) => {
            const doc = iframe.contentDocument;
            const templates = doc.querySelectorAll('template');
            
            // We need a unique way to find the element. 
            // If you have many H1s, we use the index/position.
            templates.forEach(tpl => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = tpl.innerHTML;
                
                // We try to match the live element to a node in the template
                // A better way is to add data-id to your template tags
                const tag = liveEl.tagName;
                const index = Array.from(liveEl.parentNode.children).indexOf(liveEl);
                
                // Find all elements of this tag in the rendered root
                const renderedItems = liveEl.closest('#app-root')?.querySelectorAll(tag) || [];
                const itemPos = Array.from(renderedItems).indexOf(liveEl);

                // Find that same position in the template
                const tplItems = tempDiv.querySelectorAll(tag);
                if (tplItems[itemPos]) {
                    if (tag === 'IMG') tplItems[itemPos].src = liveEl.src;
                    else tplItems[itemPos].innerHTML = liveEl.innerHTML;
                    
                    // Update the actual template tag
                    tpl.innerHTML = tempDiv.innerHTML;
                    console.log(`Synced ${tag} to template: ${tpl.id}`);
                }
            });
        };

        // Initial Setup
        const targets = 'h1, h2, h3, p, span, a, img, button';
        doc.querySelectorAll(targets).forEach(setupElement);

        // 4. Watch for Page Switches (JS rendering new templates)
        new MutationObserver((ms) => {
            ms.forEach(m => m.addedNodes.forEach(n => {
                if (n.nodeType === 1) {
                    if (n.matches(targets)) setupElement(n);
                    n.querySelectorAll(targets).forEach(setupElement);
                }
            }));
        }).observe(doc.body, { childList: true, subtree: true });
    };

    function saveViaForm() {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        const form = document.getElementById('hiddenSaveForm');
        
        // 1. Sync Sidebar inputs to hidden form
        const configContainer = document.getElementById('config_inputs_container');
        configContainer.innerHTML = '';
        document.querySelectorAll('[data-conf-key]').forEach(area => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = area.getAttribute('data-conf-key');
            input.value = area.value;
            configContainer.appendChild(input);
        });

        // 2. Clone and Clean HTML
        // This captures the whole document including the updated <template> tags
        const cleanDoc = doc.documentElement.cloneNode(true);
        cleanDoc.querySelector('#builder-tip')?.remove();
        
        // Clear the app-root so it doesn't save temporary session data
        const appRoot = cleanDoc.querySelector('#app-root') || cleanDoc.querySelector('.js-grid');
        if (appRoot) appRoot.innerHTML = '';

        cleanDoc.querySelectorAll('[contenteditable]').forEach(e => e.removeAttribute('contenteditable'));
        cleanDoc.querySelectorAll('[data-editor-ready]').forEach(e => delete e.dataset.editorReady);
        
        document.getElementById('html_payload').value = cleanDoc.outerHTML;
        form.submit();
    }
</script>
-->

    <script>
        const iframe = document.getElementById('editor-iframe');

        iframe.onload = function () {
            const doc = iframe.contentDocument || iframe.contentWindow.document;

            // 1. INJECT PREMIUM TOOLTIP UI & MODALS
            const style = doc.createElement('style');
            style.id = 'builder-style';
            style.innerHTML = `
            .builder-hover { outline: 1px dashed rgba(59, 130, 246, 0.4) !important; outline-offset: 2px; }
            .builder-selected { outline: 2px solid #3b82f6 !important; outline-offset: 2px; }
            
            #builder-tip { position: absolute; display: none; z-index: 99999; font-family: system-ui, -apple-system, sans-serif; white-space: nowrap; margin-top: -46px; backdrop-filter: blur(12px); background: rgba(255,255,255,0.95); border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 4px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); pointer-events: none; opacity: 0; transition: opacity 0.15s ease, transform 0.15s ease; transform: translateY(4px); }
            #builder-tip.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
            
            .builder-tag-badge { font-size: 9px; font-weight: 800; color: #6b7280; text-transform: uppercase; padding: 0 6px; letter-spacing: 0.5px; border-right: 1px solid #e5e7eb; margin-right: 4px; display: inline-flex; align-items: center; justify-content: center; height: 100%; vertical-align: middle; }
            
            .builder-btn { background: transparent; color: #4b5563; width: 28px; height: 28px; padding: 0; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease; vertical-align: middle; }
            .builder-btn:hover { background: #f3f4f6; color: #111827; }
            .builder-btn-primary { color: #3b82f6; }
            .builder-btn-primary:hover { background: #eff6ff; color: #2563eb; }
            .builder-btn-danger { color: #ef4444; }
            .builder-btn-danger:hover { background: #fef2f2; color: #dc2626; }
            
            /* Insert Blocks Popup */
            #builder-insert-menu { position: absolute; display: none; flex-direction: column; z-index: 100000; background: #ffffff; border-radius: 12px; box-shadow: 0 12px 36px -12px rgba(0,0,0,0.25); padding: 8px; width: 160px; border: 1px solid rgba(0,0,0,0.08); font-family: system-ui, -apple-system, sans-serif; pointer-events: none; opacity: 0; transition: opacity 0.15s ease, transform 0.15s ease; transform: translateY(-4px); }
            #builder-insert-menu.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
            #builder-insert-menu .menu-title { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; padding: 4px 8px; margin-bottom: 4px; letter-spacing: 0.5px; }
            .builder-block-btn { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; padding: 8px 10px; background: transparent; border: none; border-radius: 8px; cursor: pointer; color: #374151; font-size: 13px; font-weight: 500; transition: background 0.1s; }
            .builder-block-btn:hover { background: #f3f4f6; color: #111827; }
            .builder-block-btn i { font-size: 14px; color: #6b7280; width: 16px; text-align: center; }
            
            /* Custom Prompt Modal */
            #builder-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(3px); z-index: 1000000; display: none; align-items: center; justify-content: center; font-family: system-ui, -apple-system, sans-serif; padding: 20px; }
            #builder-modal { background: #ffffff; border-radius: 16px; padding: 24px; width: 100%; max-width: 400px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: scale(0.95); opacity: 0; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
            #builder-modal-overlay.visible { display: flex; animation: fadeIn 0.2s forwards; }
            #builder-modal-overlay.visible #builder-modal { transform: scale(1); opacity: 1; }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            
            #builder-modal h3 { margin: 0 0 16px 0; font-size: 18px; color: #111827; font-weight: 700; }
            #builder-modal input { border: 1px solid #d1d5db; padding: 12px 14px; border-radius: 10px; font-size: 14px; width: 100%; box-sizing: border-box; margin-bottom: 20px; outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
            #builder-modal input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
            #builder-modal .actions { display: flex; justify-content: flex-end; gap: 10px; }
            .modal-btn { padding: 10px 18px; border-radius: 10px; font-size: 14px; cursor: pointer; border: none; font-weight: 600; transition: all 0.15s; }
            .modal-cancel { background: #f3f4f6; color: #4b5563; }
            .modal-cancel:hover { background: #e5e7eb; color: #1f2937; }
            .modal-save { background: #3b82f6; color: #ffffff; }
            .modal-save:hover { background: #2563eb; }
        `;
            doc.head.appendChild(style);

            if (!doc.querySelector('link[href*="bootstrap-icons"]')) {
                const link = doc.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css';
                doc.head.appendChild(link);
            }

            const tip = doc.createElement('div');
            tip.id = 'builder-tip';
            tip.innerHTML = `
            <div class="builder-tag-badge" id="btn-tag-name">TAG</div>
            <button class="builder-btn builder-btn-primary" id="btn-edit" title="Edit Content"><i class="bi bi-pencil-fill"></i></button>
            <div style="width: 1px; height: 16px; background: #e5e7eb; margin: 0 4px; display: inline-block; vertical-align: middle;"></div>
            <button class="builder-btn" id="btn-add-before" title="Insert Before"><i class="bi bi-arrow-bar-up"></i></button>
            <button class="builder-btn" id="btn-add-inside" title="Insert Inside"><i class="bi bi-plus-square-dotted"></i></button>
            <button class="builder-btn" id="btn-add-after" title="Insert After"><i class="bi bi-arrow-bar-down"></i></button>
            <div style="width: 1px; height: 16px; background: #e5e7eb; margin: 0 4px; display: inline-block; vertical-align: middle;"></div>
            <button class="builder-btn builder-btn-danger" id="btn-delete" title="Delete"><i class="bi bi-trash3-fill"></i></button>
        `;
            doc.body.appendChild(tip);

            const insertMenu = doc.createElement('div');
            insertMenu.id = 'builder-insert-menu';
            insertMenu.innerHTML = `
            <div class="menu-title">Insert Block</div>
            <button class="builder-block-btn" data-type="h2"><i class="bi bi-type-h2"></i> Heading 2</button>
            <button class="builder-block-btn" data-type="h3"><i class="bi bi-type-h3"></i> Heading 3</button>
            <button class="builder-block-btn" data-type="p"><i class="bi bi-textarea-t"></i> Paragraph</button>
            <button class="builder-block-btn" data-type="button"><i class="bi bi-hand-index-thumb"></i> Button</button>
            <button class="builder-block-btn" data-type="img"><i class="bi bi-image"></i> Image</button>
            <div style="height: 1px; background: #e5e7eb; margin: 6px 0;"></div>
            <button class="builder-block-btn" data-type="custom"><i class="bi bi-code-slash"></i> Custom HTML</button>
        `;
            doc.body.appendChild(insertMenu);

            const modalOverlay = doc.createElement('div');
            modalOverlay.id = 'builder-modal-overlay';
            modalOverlay.innerHTML = `
            <div id="builder-modal">
                <h3 id="modal-title">Enter Value</h3>
                <input type="text" id="modal-input" autocomplete="off">
                <div class="actions">
                    <button class="modal-btn modal-cancel" id="modal-cancel">Cancel</button>
                    <button class="modal-btn modal-save" id="modal-save">Apply</button>
                </div>
            </div>
        `;
            doc.body.appendChild(modalOverlay);

            const fileInput = doc.createElement('input');
            fileInput.type = 'file';
            fileInput.id = 'builder-file-upload';
            fileInput.accept = 'image/*';
            fileInput.style.display = 'none';
            doc.body.appendChild(fileInput);

            fileInput.onchange = (e) => {
                const file = e.target.files[0];
                if (!file || !currentElement) return;
                const reader = new FileReader();
                reader.onload = (event) => {
                    if (currentElement.tagName === 'IMG') {
                        currentElement.src = event.target.result;
                    } else {
                        currentElement.style.backgroundImage = `url(${event.target.result})`;
                    }
                    syncChangeToTemplate(currentElement);
                };
                reader.readAsDataURL(file);
            };

            let currentElement = null;
            let insertPosition = null;

            // Custom Modal API
            const showModal = (title, placeholder, onSave) => {
                const mTitle = doc.getElementById('modal-title');
                const mInput = doc.getElementById('modal-input');
                mTitle.innerText = title;
                mInput.value = '';
                mInput.placeholder = placeholder;
                modalOverlay.classList.add('visible');
                mInput.focus();

                const cleanup = () => {
                    modalOverlay.classList.remove('visible');
                    doc.getElementById('modal-save').onclick = null;
                    doc.getElementById('modal-cancel').onclick = null;
                };

                doc.getElementById('modal-cancel').onclick = cleanup;
                doc.getElementById('modal-save').onclick = () => {
                    if (mInput.value) onSave(mInput.value);
                    cleanup();
                };
            };

            const selectElement = (el) => {
                if (currentElement) {
                    currentElement.classList.remove('builder-selected');
                    currentElement.blur();
                    currentElement.removeAttribute('contenteditable');
                    insertMenu.classList.remove('visible');
                }
                currentElement = el;
                currentElement.classList.add('builder-selected');
                doc.getElementById('btn-tag-name').innerText = el.tagName;

                const rect = el.getBoundingClientRect();
                const win = iframe.contentWindow;
                tip.style.display = 'block';
                tip.classList.add('visible');

                const topPos = Math.max(10, rect.top + win.scrollY - Number(window.getComputedStyle(tip).height.replace('px', '') || 46) - 10);
                tip.style.left = Math.max(10, rect.left + win.scrollX) + 'px';
                tip.style.top = topPos + 'px';
            };

            // Hide UI when clicking outside of any editable element
            doc.addEventListener('click', (e) => {
                if (currentElement && !currentElement.contains(e.target) && !tip.contains(e.target) && !insertMenu.contains(e.target) && !modalOverlay.contains(e.target)) {
                    currentElement.classList.remove('builder-selected');
                    currentElement.contentEditable = "false";
                    currentElement.removeAttribute('contenteditable');
                    tip.classList.remove('visible');
                    insertMenu.classList.remove('visible');
                    currentElement = null;
                }
            });

            // ==========================================
            // 2. THE FIX: PRE-TAG TEMPLATES
            // We tag the top-level elements inside every template with their source ID
            // When your SPA clones them, the live elements will inherit these tags.
            // ==========================================
            doc.querySelectorAll('template').forEach(tpl => {
                Array.from(tpl.content.children).forEach((child, idx) => {
                    // Only tag if it hasn't been tagged yet
                    if (!child.hasAttribute('data-source-tpl')) {
                        child.setAttribute('data-source-tpl', tpl.id);
                        child.setAttribute('data-tpl-idx', idx);
                    }
                });
            });

            // 3. EXACT PATH FINDER
            // Calculates the exact family tree path from the root container down to the edited element
            const getPath = (element, root) => {
                const path = [];
                let current = element;
                while (current !== root && current) {
                    path.unshift(Array.from(current.parentNode.children).indexOf(current));
                    current = current.parentNode;
                }
                return path;
            };

            const getElementByPath = (root, path) => {
                let current = root;
                for (let i = 0; i < path.length; i++) {
                    current = current.children[path[i]];
                    if (!current) return null;
                }
                return current;
            };

            // 4. SMART SYNC (Only updates the correct template)
            const syncChangeToTemplate = (liveEl, syncEntireRoot = false) => {
                // Find which template this element belongs to
                const rootContainer = liveEl.closest('[data-source-tpl]');
                if (!rootContainer) return;

                const tplId = rootContainer.getAttribute('data-source-tpl');
                const tplIdx = rootContainer.getAttribute('data-tpl-idx');
                const tpl = doc.getElementById(tplId);

                if (!tpl) return;

                // Find the exact matching element inside the <template> fragment
                const targetRootInTpl = tpl.content.children[tplIdx];

                if (syncEntireRoot) {
                    const clone = liveEl.cloneNode(true);
                    clone.querySelectorAll('[contenteditable]').forEach(e => e.removeAttribute('contenteditable'));
                    clone.querySelectorAll('[data-editor-ready]').forEach(e => delete e.dataset.editorReady);
                    clone.querySelectorAll('[data-source-tpl]').forEach(e => {
                        e.removeAttribute('data-source-tpl');
                        e.removeAttribute('data-tpl-idx');
                    });
                    clone.removeAttribute('data-source-tpl');
                    clone.removeAttribute('data-tpl-idx');
                    clone.removeAttribute('contenteditable');
                    delete clone.dataset.editorReady;
                    targetRootInTpl.innerHTML = clone.innerHTML;
                    console.log(`[Editor] Synced full root to template: #${tplId}`);
                    return;
                }

                const path = getPath(liveEl, rootContainer);
                const targetEl = getElementByPath(targetRootInTpl, path);

                if (targetEl) {
                    if (liveEl.tagName === 'IMG') targetEl.src = liveEl.src;
                    else targetEl.innerHTML = liveEl.innerHTML;
                    console.log(`[Editor] Synced changes safely to template: #${tplId}`);
                }
            };

            doc.getElementById('btn-edit').onclick = (e) => {
                if (!currentElement) return;
                if (currentElement.tagName === 'IMG') {
                    doc.getElementById('builder-file-upload').click();
                } else {
                    currentElement.contentEditable = "true";
                    currentElement.focus();
                }
            };

            const openInsertMenu = (position, btnEl) => {
                if (!currentElement) return;
                insertPosition = position;
                const rect = btnEl.getBoundingClientRect();
                const win = iframe.contentWindow;
                insertMenu.style.display = 'flex';
                insertMenu.classList.add('visible');
                insertMenu.style.left = (rect.left + win.scrollX - 70) + 'px';
                insertMenu.style.top = (rect.bottom + win.scrollY + 10) + 'px';
            };

            doc.querySelectorAll('.builder-block-btn').forEach(btn => {
                btn.onclick = () => {
                    const type = btn.getAttribute('data-type');
                    insertMenu.classList.remove('visible');

                    if (type === 'custom') {
                        showModal("Custom HTML", "<div>...</div>", (html) => executeInsert(html));
                        return;
                    }

                    const templates = {
                        'h2': '<h2 style="font-size: 24px; font-weight: bold; margin-bottom: 16px;">New Heading</h2>',
                        'h3': '<h3 style="font-size: 20px; font-weight: 600; margin-bottom: 12px;">Subheading</h3>',
                        'p': '<p style="color: #4b5563; margin-bottom: 16px; line-height: 1.6;">This is a premium paragraph block.</p>',
                        'button': '<button style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 500; display: inline-block;">Click Here</button>',
                        'img': '<img src="https://via.placeholder.com/600x400" alt="Placeholder" style="width: 100%; border-radius: 12px; margin-bottom: 16px;">'
                    };
                    executeInsert(templates[type]);
                };
            });

            const executeInsert = (html) => {
                if (!currentElement || !insertPosition) return;
                const tempDiv = doc.createElement('div');
                tempDiv.innerHTML = html;
                const newEl = tempDiv.firstElementChild;
                if (!newEl) return;

                if (insertPosition === 'before') currentElement.before(newEl);
                if (insertPosition === 'after') currentElement.after(newEl);
                if (insertPosition === 'inside') currentElement.appendChild(newEl);

                const rootContainer = currentElement.closest('[data-source-tpl]');
                if (rootContainer) syncChangeToTemplate(rootContainer, true);

                setupElement(newEl);
                newEl.querySelectorAll(targets).forEach(setupElement);
            };

            doc.getElementById('btn-add-before').onclick = (e) => openInsertMenu('before', e.currentTarget);
            doc.getElementById('btn-add-after').onclick = (e) => openInsertMenu('after', e.currentTarget);
            doc.getElementById('btn-add-inside').onclick = (e) => openInsertMenu('inside', e.currentTarget);
            doc.getElementById('btn-delete').onclick = () => {
                if (!currentElement) return;
                const rootContainer = currentElement.closest('[data-source-tpl]');
                currentElement.remove();
                tip.classList.remove('visible');
                if (rootContainer) syncChangeToTemplate(rootContainer, true);
            };

            // 5. SETUP ELEMENTS
            const setupElement = (el) => {
                if (el.closest('#builder-tip') || el.closest('#builder-insert-menu') || el.closest('#builder-modal-overlay')) return;
                if (el.dataset.editorReady) return;
                el.dataset.editorReady = "true";

                if (el.tagName !== 'IMG') {
                    el.oninput = () => syncChangeToTemplate(el);
                }

                el.onclick = (e) => {
                    e.stopPropagation(); // Stop bubbling immediately so click doesn't hit parent editable elements
                    if (el.isContentEditable || el.contentEditable === "true") return; // Allow normal cursor placement
                    if (el.tagName === 'BUTTON' || el.tagName === 'A') e.preventDefault(); // Stop links/buttons from redirecting
                    selectElement(el);
                };

                el.onmouseover = (e) => {
                    if (!currentElement || (currentElement !== el)) {
                        e.stopPropagation();
                        el.classList.add('builder-hover');
                    }
                };
                el.onmouseout = () => el.classList.remove('builder-hover');
            };

            // Initial setup for anything currently on screen
            const targets = 'h1, h2, h3, h4, p, span, a, img, button, label';
            doc.querySelectorAll(targets).forEach(setupElement);

            // 6. OBSERVE PAGE SWITCHES
            new MutationObserver((ms) => {
                ms.forEach(m => m.addedNodes.forEach(n => {
                    if (n.nodeType === 1) {
                        if (n.matches(targets)) setupElement(n);
                        n.querySelectorAll(targets).forEach(setupElement);
                    }
                }));
            }).observe(doc.body, { childList: true, subtree: true });
        };

        // ==========================================
        // SAVE LOGIC
        // ==========================================
        function saveViaForm() {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const form = document.getElementById('hiddenSaveForm');

            // Setup Config Inputs
            const configContainer = document.getElementById('config_inputs_container');
            configContainer.innerHTML = '';
            document.querySelectorAll('[data-conf-key]').forEach(area => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = area.getAttribute('data-conf-key');
                input.value = area.value;
                configContainer.appendChild(input);
            });

            // Clone DOM for saving
            const cleanDoc = doc.documentElement.cloneNode(true);

            // Remove Editor UI elements completely to prevent saving
            ['#builder-tip', '#builder-style', '#builder-insert-menu', '#builder-modal-overlay'].forEach(id => {
                cleanDoc.querySelector(id)?.remove();
            });

            // VERY IMPORTANT: Clear the live rendered app-root so we only save the raw templates
            // Adjust the selector to match whatever your SPA uses to hold the live page
            const appRoot = cleanDoc.querySelector('#app-root') || cleanDoc.querySelector('.js-grid');
            if (appRoot) appRoot.innerHTML = '';

            // Strip out all editor attributes so the saved HTML is perfectly clean
            cleanDoc.querySelectorAll('.builder-selected').forEach(e => e.classList.remove('builder-selected'));
            cleanDoc.querySelectorAll('.builder-hover').forEach(e => e.classList.remove('builder-hover'));
            cleanDoc.querySelectorAll('[contenteditable]').forEach(e => e.removeAttribute('contenteditable'));
            cleanDoc.querySelectorAll('[data-editor-ready]').forEach(e => delete e.dataset.editorReady);
            cleanDoc.querySelectorAll('[data-source-tpl]').forEach(e => {
                e.removeAttribute('data-source-tpl');
                e.removeAttribute('data-tpl-idx');
            });

            document.getElementById('html_payload').value = cleanDoc.outerHTML;
            form.submit();
        }
    </script>

</body>

</html>