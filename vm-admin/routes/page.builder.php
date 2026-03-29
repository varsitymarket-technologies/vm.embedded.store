<?php
// --- SETUP ---
$siteDir = dirname(dirname(dirname(__FILE__))). "/sites/".__DOMAIN__;
$configFile = $siteDir . "/config.php";
$htmlFile = $siteDir . "/builder.cache.html";

// --- SAVE HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_all') {
    if (!is_dir($siteDir)) { mkdir($siteDir, 0755, true); }

    // Save Config
    $configContent = "<?php" . PHP_EOL;
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'CONF_') === 0) {
            $configContent .= "define(\"".str_replace('CONF_', '', $key)."\", \"" . addslashes($value) . "\");" . PHP_EOL;
        }
    }
    //file_put_contents($configFile, $configContent);

    // Save the full HTML (including updated <template> tags)
    if (isset($_POST['site_rendered_html'])) {
        file_put_contents($htmlFile, $_POST['site_rendered_html']);
    }

    header("Location: ?success=1");
    exit;
}

// --- LOAD CONFIG ---
$content = file_exists($configFile) ? file_get_contents($configFile) : '';
preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);
$config = [];
if (!empty($matches[1])) {
    foreach ($matches[1] as $idx => $key) { $config[$key] = $matches[2][$idx]; }
}
?>


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .canvas-area { background: #0c0d0e; background-image: radial-gradient(#222 1px, transparent 1px); background-size: 24px 24px; }
        .field-card { background: #1c1d21; border: 1px solid rgba(255,255,255,0.05); }
        iframe { width: 100%; height: 100%; background: white; border: none; }
    </style>

<body class="bg-[#0c0d0e] text-gray-300 overflow-hidden">


<div class="flex h-screen w-full">

    <!-- 
    <aside class="sidebar flex flex-col z-20">
        <div class="p-6 border-b border-white/5 flex items-center justify-between bg-black/20">
            <span class="font-bold text-[10px] uppercase tracking-widest text-white">Advanced Editor</span>
            <button onclick="saveViaForm()" class="bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black px-4 py-2 rounded-full uppercase shadow-lg shadow-blue-900/20">
                Publish
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-8 custom-scrollbar">
            <?php foreach (['Branding' => ['LOGO', 'ICON'], 'Content' => ['TITLE', 'TEXT']] as $name => $keys): ?>
                <div>
                    <h3 class="text-[10px] text-gray-500 font-bold uppercase mb-4"><?php echo $name; ?></h3>
                    <div class="space-y-3">
                        <?php foreach ($config as $key => $val): 
                            $match = false; foreach($keys as $k) if(strpos($key, $k) !== false) $match = true;
                            if(!$match) continue; ?>
                            <div class="field-card p-3 rounded-xl">
                                <label class="text-[9px] text-gray-500 font-bold block mb-1 uppercase"><?php echo $key; ?></label>
                                <textarea data-conf-key="CONF_<?php echo $key; ?>" class="w-full bg-transparent text-xs text-white outline-none min-h-[30px] resize-none"><?php echo $val; ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
    -->

    <main class="flex-1 canvas-area flex flex-col relative p-12 pt-2">
        <div class="p-6 border-b border-white/5 flex items-center justify-between bg-black/20">
            <span class="font-bold text-[10px] uppercase tracking-widest text-white">Page Editor</span>
            <button onclick="saveViaForm()" class="bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black px-4 py-2 rounded-full uppercase shadow-lg shadow-blue-900/20">
                Publish
            </button>
        </div>

        <div id="wrapper" class="w-full h-full bg-white rounded-2xl overflow-hidden shadow-2xl border-[8px] border-[#16171a]">
            <iframe src="<?php echo __WEBSITE_URL__; ?>" id="editor-iframe"></iframe>
        </div>
    </main>
</div>

<form id="hiddenSaveForm" method="POST" action="" style="display:none;">
    <input type="hidden" name="action" value="save_all">
    <input type="hidden" name="site_rendered_html" id="html_payload">
    <div id="config_inputs_container"></div>
</form>

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

    iframe.onload = function() {
        const doc = iframe.contentDocument || iframe.contentWindow.document;

        // 1. INJECT TOOLTIP UI
        const style = doc.createElement('style');
        style.innerHTML = `
            [contenteditable="true"]:hover, img:hover { outline: 2px dashed #3b82f6 !important; outline-offset: 2px; }
            #builder-tip { position: fixed; background: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; z-index: 9999; pointer-events: none; display: none; text-transform: uppercase; font-family: sans-serif; font-weight: bold; }
        `;
        doc.head.appendChild(style);

        const tip = doc.createElement('div');
        tip.id = 'builder-tip';
        doc.body.appendChild(tip);

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
        const syncChangeToTemplate = (liveEl) => {
            // Find which template this element belongs to
            const rootContainer = liveEl.closest('[data-source-tpl]');
            if (!rootContainer) return;

            const tplId = rootContainer.getAttribute('data-source-tpl');
            const tplIdx = rootContainer.getAttribute('data-tpl-idx');
            const tpl = doc.getElementById(tplId);
            
            if (!tpl) return;

            // Find the exact matching element inside the <template> fragment
            const targetRootInTpl = tpl.content.children[tplIdx];
            const path = getPath(liveEl, rootContainer);
            const targetEl = getElementByPath(targetRootInTpl, path);

            if (targetEl) {
                if (liveEl.tagName === 'IMG') targetEl.src = liveEl.src;
                else targetEl.innerHTML = liveEl.innerHTML;
                console.log(`[Editor] Synced changes safely to template: #${tplId}`);
            }
        };

        // 5. SETUP ELEMENTS
        const setupElement = (el) => {
            if (el.dataset.editorReady) return;
            el.dataset.editorReady = "true";

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

            el.onmouseover = () => { tip.style.display = 'block'; tip.innerText = el.tagName === 'IMG' ? 'Change Image' : 'Edit Content'; };
            el.onmousemove = (e) => { tip.style.left = (e.clientX + 10) + 'px'; tip.style.top = (e.clientY - 30) + 'px'; };
            el.onmouseleave = () => { tip.style.display = 'none'; };
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
        
        // Remove Editor UI elements
        cleanDoc.querySelector('#builder-tip')?.remove();
        
        // VERY IMPORTANT: Clear the live rendered app-root so we only save the raw templates
        // Adjust the selector to match whatever your SPA uses to hold the live page
        const appRoot = cleanDoc.querySelector('#app-root') || cleanDoc.querySelector('.js-grid');
        if (appRoot) appRoot.innerHTML = '';

        // Strip out all editor attributes so the saved HTML is perfectly clean
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