<?php
/**
 * SPA Website Builder - Figma-style UI
 * Loads the target site in an iframe and provides visual editing.
 */

$domain = defined('__DOMAIN__') ? __DOMAIN__ : '';
$root_dir = dirname(dirname(dirname(dirname(__FILE__))));
$site_dir = $root_dir . "/sites/" . $domain;
$builder_cache = $site_dir . "/builder.cache.html";
$admin_base = '/vm-admin/' . $domain . '/';

// Load HTML content directly for iframe injection
$site_html_content = '';
if (file_exists($builder_cache)) {
    $site_html_content = file_get_contents($builder_cache);
} else {
    $active_theme_file = $site_dir . "/theme";
    $active_theme_name = file_exists($active_theme_file) ? trim(file_get_contents($active_theme_file)) : '';
    $theme_index = $root_dir . '/themes/' . $active_theme_name . '/index.php';
    if (file_exists($theme_index)) {
        ob_start();
        @include $theme_index;
        $site_html_content = ob_get_clean();
    } elseif (!empty($domain)) {
        @include_once $root_dir . "/services/export.store.source.php";
        if (function_exists('export_application')) {
            $website_domain = defined('__WEBSITE_DOMAIN__') ? __WEBSITE_DOMAIN__ : '';
            try { $site_html_content = export_application($domain, $website_domain); } catch (\Throwable $e) {}
        }
    }
}

// Fallback starter template
if (empty(trim($site_html_content ?? ''))) {
    $site_html_content = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Store</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Inter, system-ui, sans-serif; background: #fafafa; color: #1a1a1a; }
.hero { padding: 80px 24px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
.hero h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 12px; }
.hero p { font-size: 1.1rem; opacity: 0.9; max-width: 500px; margin: 0 auto 24px; }
.hero button { background: #fff; color: #764ba2; border: none; padding: 14px 32px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
.section { padding: 60px 24px; max-width: 900px; margin: 0 auto; }
.section h2 { font-size: 1.6rem; font-weight: 700; margin-bottom: 16px; }
.section p { color: #555; line-height: 1.7; }
</style>
</head>
<body>
<div class="hero">
    <h1>Welcome to My Store</h1>
    <p>Edit this page using the builder. Click any element to modify it.</p>
    <button>Shop Now</button>
</div>
<div class="section">
    <h2>About Us</h2>
    <p>This is your store template. Double-click text to edit inline, or use the properties panel on the right to adjust styles.</p>
</div>
</body>
</html>';
}

// Save handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'save_html') {
        $html = $_POST['html'] ?? '';
        if (!empty($html) && !empty($domain)) {
            if (!is_dir($site_dir)) @mkdir($site_dir, 0755, true);
            file_put_contents($builder_cache, $html);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Missing data']);
        }
        exit;
    }
}
?>

<!-- Builder: full viewport overlay -->
<style>
    /* Override admin layout */
    .grid-layout, .flex, body > div { display: contents !important; }
    #sidebar, .sidebar, header, .admin-header { display: none !important; }
    * { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --fig-bg: #1e1e1e;
        --fig-surface: #2c2c2c;
        --fig-surface2: #383838;
        --fig-border: #444;
        --fig-border-subtle: #363636;
        --fig-text: #e0e0e0;
        --fig-text2: #b3b3b3;
        --fig-text3: #888;
        --fig-text4: #666;
        --fig-accent: #0d99ff;
        --fig-accent-hover: #38b6ff;
        --fig-danger: #f24822;
        --fig-success: #14ae5c;
        --fig-radius: 6px;
    }

    .fb-root {
        display: flex; flex-direction: column;
        width: 100vw; height: 100vh;
        background: var(--fig-bg); color: var(--fig-text);
        font-family: 'Inter', -apple-system, system-ui, sans-serif;
        font-size: 11px; -webkit-font-smoothing: antialiased;
        position: fixed; top: 0; left: 0; z-index: 9999;
    }

    /* ── Top Bar ── */
    .fb-topbar {
        height: 48px; min-height: 48px;
        display: flex; align-items: center;
        padding: 0 12px;
        background: var(--fig-surface);
        border-bottom: 1px solid var(--fig-border-subtle);
        z-index: 100;
    }
    .fb-topbar-left {
        display: flex; align-items: center; gap: 4px; width: 241px; min-width: 241px;
        border-right: 1px solid var(--fig-border-subtle);
        padding-right: 12px; margin-right: 12px;
    }
    .fb-topbar-center { flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px; }
    .fb-topbar-right { display: flex; align-items: center; gap: 4px; width: 248px; min-width: 248px; justify-content: flex-end; }

    .fb-logo {
        display: flex; align-items: center; gap: 8px;
        font-weight: 700; font-size: 13px; color: var(--fig-text);
        padding: 0 8px;
    }
    .fb-logo svg { width: 20px; height: 20px; }

    .fb-tbtn {
        display: flex; align-items: center; justify-content: center; gap: 5px;
        height: 32px; padding: 0 10px; border: none; border-radius: 6px;
        background: transparent; color: var(--fig-text2); font-size: 11px;
        cursor: pointer; transition: all 0.12s; white-space: nowrap;
        font-family: inherit;
    }
    .fb-tbtn:hover { background: var(--fig-surface2); color: var(--fig-text); }
    .fb-tbtn.active { background: var(--fig-accent); color: #fff; }
    .fb-tbtn i { font-size: 15px; }

    .fb-tbtn-accent {
        background: var(--fig-accent); color: #fff; font-weight: 600;
    }
    .fb-tbtn-accent:hover { background: var(--fig-accent-hover); color: #fff; }

    .fb-sep { width: 1px; height: 20px; background: var(--fig-border-subtle); margin: 0 4px; }

    .fb-vp-group { display: flex; background: var(--fig-bg); border-radius: 6px; overflow: hidden; border: 1px solid var(--fig-border-subtle); }
    .fb-vp-btn {
        width: 32px; height: 28px; border: none; background: transparent;
        color: var(--fig-text3); cursor: pointer; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.12s;
    }
    .fb-vp-btn:hover { color: var(--fig-text2); }
    .fb-vp-btn.on { background: var(--fig-accent); color: #fff; }

    /* ── Main body ── */
    .fb-main { display: flex; flex: 1; overflow: hidden; }

    /* ── Left Panel ── */
    .fb-left {
        width: 241px; min-width: 241px; background: var(--fig-surface);
        border-right: 1px solid var(--fig-border-subtle);
        display: flex; flex-direction: column; overflow: hidden;
    }
    .fb-left.collapsed { width: 0; min-width: 0; border: none; overflow: hidden; }

    .fb-panel-tabs {
        display: flex; border-bottom: 1px solid var(--fig-border-subtle);
        background: var(--fig-surface);
    }
    .fb-ptab {
        flex: 1; height: 36px; border: none; background: transparent;
        color: var(--fig-text3); font-size: 11px; font-weight: 500;
        cursor: pointer; transition: all 0.12s; font-family: inherit;
        border-bottom: 2px solid transparent;
    }
    .fb-ptab:hover { color: var(--fig-text2); }
    .fb-ptab.on { color: var(--fig-text); border-bottom-color: var(--fig-accent); }

    .fb-panel-content { flex: 1; overflow-y: auto; }
    .fb-panel-content::-webkit-scrollbar { width: 4px; }
    .fb-panel-content::-webkit-scrollbar-track { background: transparent; }
    .fb-panel-content::-webkit-scrollbar-thumb { background: var(--fig-border); border-radius: 4px; }

    /* Layers */
    .fb-layers { padding: 4px 0; }
    .fb-layer {
        display: flex; align-items: center; gap: 6px;
        height: 28px; padding: 0 12px; cursor: pointer;
        color: var(--fig-text2); font-size: 11px;
        transition: background 0.1s; white-space: nowrap; overflow: hidden;
    }
    .fb-layer:hover { background: var(--fig-surface2); }
    .fb-layer.sel { background: rgba(13, 153, 255, 0.15); color: var(--fig-accent); }
    .fb-layer i { font-size: 12px; color: var(--fig-text4); flex-shrink: 0; }
    .fb-layer.sel i { color: var(--fig-accent); }
    .fb-layer-label { overflow: hidden; text-overflow: ellipsis; }
    .fb-layer-text { color: var(--fig-text4); font-size: 10px; margin-left: auto; overflow: hidden; text-overflow: ellipsis; max-width: 80px; flex-shrink: 0; }

    /* Add Elements */
    .fb-add-grid { padding: 8px; }
    .fb-add-category {
        margin-bottom: 4px;
    }
    .fb-add-cat-header {
        display: flex; align-items: center; gap: 6px;
        padding: 6px 8px; color: var(--fig-text3); font-size: 10px;
        font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .fb-add-items { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 0 4px 8px; }
    .fb-add-item {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 4px; padding: 10px 4px; border: 1px solid var(--fig-border-subtle);
        border-radius: 6px; background: transparent; color: var(--fig-text2);
        cursor: pointer; transition: all 0.12s; font-family: inherit; font-size: 10px;
    }
    .fb-add-item:hover { background: var(--fig-surface2); border-color: var(--fig-accent); color: var(--fig-text); }
    .fb-add-item i { font-size: 16px; }

    /* ── Canvas ── */
    .fb-canvas {
        flex: 1; display: flex; align-items: center; justify-content: center;
        background: var(--fig-bg);
        background-image: radial-gradient(circle, #333 1px, transparent 1px);
        background-size: 20px 20px;
        overflow: hidden; position: relative;
    }
    .fb-frame {
        background: #fff; transition: width 0.3s ease, height 0.3s ease;
        box-shadow: 0 0 0 1px rgba(255,255,255,0.04), 0 16px 48px rgba(0,0,0,0.4);
        border-radius: 2px; overflow: hidden; position: relative;
    }
    .fb-frame.desktop { width: 100%; height: 100%; border-radius: 0; }
    .fb-frame.tablet { width: 768px; height: 100%; }
    .fb-frame.mobile { width: 375px; height: 100%; }
    .fb-frame iframe { width: 100%; height: 100%; border: none; display: block; }

    /* ── Right Panel ── */
    .fb-right {
        width: 248px; min-width: 248px; background: var(--fig-surface);
        border-left: 1px solid var(--fig-border-subtle);
        display: flex; flex-direction: column; overflow: hidden;
    }
    .fb-right.collapsed { width: 0; min-width: 0; border: none; overflow: hidden; }

    .fb-right-tabs {
        display: flex; border-bottom: 1px solid var(--fig-border-subtle);
    }
    .fb-rtab {
        flex: 1; height: 36px; border: none; background: transparent;
        color: var(--fig-text3); font-size: 11px; font-weight: 500;
        cursor: pointer; transition: all 0.12s; font-family: inherit;
        border-bottom: 2px solid transparent;
    }
    .fb-rtab:hover { color: var(--fig-text2); }
    .fb-rtab.on { color: var(--fig-text); border-bottom-color: var(--fig-accent); }

    .fb-right-body { flex: 1; overflow-y: auto; }
    .fb-right-body::-webkit-scrollbar { width: 4px; }
    .fb-right-body::-webkit-scrollbar-track { background: transparent; }
    .fb-right-body::-webkit-scrollbar-thumb { background: var(--fig-border); border-radius: 4px; }

    /* Empty state */
    .fb-empty {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center; height: 100%; text-align: center;
        padding: 24px; color: var(--fig-text4);
    }
    .fb-empty i { font-size: 28px; margin-bottom: 10px; color: var(--fig-border); }
    .fb-empty p { font-size: 11px; line-height: 1.5; }

    /* Property sections */
    .fb-section { border-bottom: 1px solid var(--fig-border-subtle); }
    .fb-sec-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 12px; cursor: pointer; user-select: none;
        font-size: 11px; font-weight: 600; color: var(--fig-text2);
    }
    .fb-sec-header:hover { color: var(--fig-text); }
    .fb-sec-header .chev { font-size: 8px; transition: transform 0.15s; color: var(--fig-text4); }
    .fb-sec-header.open .chev { transform: rotate(90deg); }
    .fb-sec-body { padding: 0 12px 10px; display: none; }
    .fb-sec-header.open + .fb-sec-body { display: block; }

    /* Property rows */
    .fb-row { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
    .fb-lbl { font-size: 11px; color: var(--fig-text3); width: 48px; flex-shrink: 0; }
    .fb-input {
        flex: 1; height: 28px; background: var(--fig-bg); border: 1px solid transparent;
        border-radius: 4px; padding: 0 8px; color: var(--fig-text); font-size: 11px;
        font-family: inherit; outline: none; transition: border-color 0.12s;
        min-width: 0;
    }
    .fb-input:hover { border-color: var(--fig-border); }
    .fb-input:focus { border-color: var(--fig-accent); }
    .fb-input::placeholder { color: var(--fig-text4); }

    textarea.fb-input { height: auto; padding: 6px 8px; resize: vertical; }

    select.fb-input {
        appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 6px center;
        padding-right: 20px;
    }

    .fb-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; }
    .fb-4col { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 4px; }

    .fb-input-sm {
        height: 28px; background: var(--fig-bg); border: 1px solid transparent;
        border-radius: 4px; padding: 0 6px; color: var(--fig-text); font-size: 10px;
        font-family: inherit; outline: none; text-align: center; width: 100%;
        transition: border-color 0.12s;
    }
    .fb-input-sm:hover { border-color: var(--fig-border); }
    .fb-input-sm:focus { border-color: var(--fig-accent); }

    .fb-color-row { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
    .fb-swatch {
        width: 24px; height: 24px; border: 1px solid var(--fig-border);
        border-radius: 4px; cursor: pointer; padding: 0;
        background: none; appearance: none; flex-shrink: 0;
    }
    .fb-swatch::-webkit-color-swatch-wrapper { padding: 0; }
    .fb-swatch::-webkit-color-swatch { border: none; border-radius: 3px; }

    /* Align / format buttons */
    .fb-icon-row { display: flex; gap: 2px; }
    .fb-icon-btn {
        width: 28px; height: 28px; border: 1px solid transparent; border-radius: 4px;
        background: transparent; color: var(--fig-text3); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; transition: all 0.12s;
    }
    .fb-icon-btn:hover { background: var(--fig-surface2); color: var(--fig-text); border-color: var(--fig-border-subtle); }
    .fb-icon-btn.on { background: var(--fig-bg); color: var(--fig-accent); border-color: var(--fig-accent); }

    /* Action buttons */
    .fb-action {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        width: 100%; height: 30px; border: 1px solid var(--fig-border-subtle); border-radius: 6px;
        background: transparent; color: var(--fig-text2); font-size: 11px;
        cursor: pointer; transition: all 0.12s; margin-bottom: 4px; font-family: inherit;
    }
    .fb-action:hover { background: var(--fig-surface2); border-color: var(--fig-border); }
    .fb-action.danger { color: var(--fig-danger); border-color: rgba(242,72,34,0.25); }
    .fb-action.danger:hover { background: rgba(242,72,34,0.08); }

    /* Floating Actions Toolbar */
    .fb-float-actions {
        position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%);
        display: none; align-items: center; gap: 2px;
        background: var(--fig-surface); border: 1px solid var(--fig-border);
        border-radius: 10px; padding: 4px 6px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.04);
        z-index: 1000;
    }
    .fb-float-actions.show { display: flex; }
    .fb-float-btn {
        width: 32px; height: 32px; border: none; border-radius: 6px;
        background: transparent; color: var(--fig-text2); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; transition: all 0.12s; position: relative;
    }
    .fb-float-btn:hover { background: var(--fig-surface2); color: var(--fig-text); }
    .fb-float-btn.danger:hover { background: rgba(242,72,34,0.12); color: var(--fig-danger); }
    .fb-float-sep { width: 1px; height: 20px; background: var(--fig-border); margin: 0 2px; }
    .fb-float-btn[title]:hover::after {
        content: attr(title); position: absolute; bottom: 110%; left: 50%; transform: translateX(-50%);
        background: var(--fig-bg); color: var(--fig-text); font-size: 10px; padding: 3px 8px;
        border-radius: 4px; white-space: nowrap; pointer-events: none;
        border: 1px solid var(--fig-border-subtle);
    }

    /* Inspect HTML snippet */
    .inspect-section { padding: 12px; border-bottom: 1px solid var(--fig-border-subtle); }
    .inspect-label {
        font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--fig-text3); margin-bottom: 8px;
    }
    .inspect-code {
        background: var(--fig-bg); border: 1px solid var(--fig-border-subtle); border-radius: 6px;
        padding: 10px 12px; max-height: 220px; overflow: auto; position: relative;
    }
    .inspect-code pre {
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: 11px;
        color: var(--fig-text2); white-space: pre-wrap; word-break: break-all;
        line-height: 1.6; margin: 0; tab-size: 2; outline: none;
    }
    .inspect-code pre[contenteditable]:focus {
        background: rgba(13, 153, 255, 0.04);
        box-shadow: inset 0 0 0 1px rgba(13, 153, 255, 0.2);
        border-radius: 4px;
    }
    .inspect-code .hl-tag { color: #f07178; }
    .inspect-code .hl-attr { color: #ffcb6b; }
    .inspect-code .hl-val { color: #c3e88d; }
    .inspect-code .hl-text { color: #b3b3b3; }
    .inspect-copy {
        position: absolute; top: 6px; right: 6px;
        width: 26px; height: 26px; border: 1px solid var(--fig-border-subtle); border-radius: 4px;
        background: var(--fig-surface); color: var(--fig-text3); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; transition: all 0.15s;
    }
    .inspect-copy:hover { background: var(--fig-surface2); color: var(--fig-text); }

    /* Toast */
    .fb-toast {
        position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(40px);
        background: var(--fig-surface); color: var(--fig-text);
        border: 1px solid var(--fig-border-subtle);
        padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 500;
        z-index: 100001; opacity: 0; pointer-events: none;
        box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        transition: all 0.25s ease;
    }
    .fb-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); pointer-events: auto; }
</style>

<div class="fb-root">
    <!-- Top Bar -->
    <div class="fb-topbar">
        <div class="fb-topbar-left">
        <a href="<?php echo $admin_base; ?>home" class="fb-tbtn" title="Back">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="fb-logo">
                <img src="/assets/favicon.png" alt="Logo" class="w-6 h-6">
                Page Builder
            </div>
            
        </div>

        <div class="fb-topbar-center">
            <div class="fb-vp-group" id="mode-group">
                <button class="fb-vp-btn on" data-mode="select"      onclick="setMode('select')"      title="Select (V)"><i class="bi bi-cursor-fill"></i></button>
                <button class="fb-vp-btn"    data-mode="interaction" onclick="setMode('interaction')" title="Interaction (H)"><i class="bi bi-hand-index"></i></button>
                <button class="fb-vp-btn"    data-mode="drag"        onclick="setMode('drag')"        title="Drag (M)"><i class="bi bi-arrows-move"></i></button>
            </div>
            <div class="fb-sep"></div>
            <button class="fb-tbtn" onclick="undo()" title="Undo"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button class="fb-tbtn" onclick="redo()" title="Redo"><i class="bi bi-arrow-clockwise"></i></button>
            <div class="fb-sep"></div>
            <div class="fb-vp-group">
                <button class="fb-vp-btn on" data-vp="desktop" onclick="setViewport('desktop')" title="Desktop"><i class="bi bi-display"></i></button>
                <button class="fb-vp-btn" data-vp="tablet" onclick="setViewport('tablet')" title="Tablet"><i class="bi bi-tablet"></i></button>
                <button class="fb-vp-btn" data-vp="mobile" onclick="setViewport('mobile')" title="Mobile"><i class="bi bi-phone"></i></button>
            </div>
            <div class="fb-sep"></div>
            <span style="color:var(--fig-text3);font-size:11px" id="zoom-label">100%</span>
        </div>

        <div class="fb-topbar-right">
            <button class="fb-tbtn" onclick="previewSite()" title="Preview"><i class="bi bi-play-fill"></i></button>
            <div class="fb-sep"></div>
            <button class="fb-tbtn fb-tbtn-accent" onclick="saveSite()">Save</button>
        </div>
    </div>

    <!-- Main Area -->
    <div class="fb-main">
        <!-- Left Panel -->
        <div class="fb-left" id="left-panel">
            <div class="fb-panel-tabs">
                <button class="fb-ptab on" data-tab="layers" onclick="setLeftTab('layers')">Layers</button>
                <button class="fb-ptab" data-tab="add" onclick="setLeftTab('add')">Add</button>
                <button class="fb-ptab" data-tab="page" onclick="setLeftTab('page')">Page</button>
            </div>

            <div class="fb-panel-content" id="left-content">
                <!-- Layers Tab -->
                <div class="fb-layers" id="tab-layers"></div>

                <!-- Add Elements Tab -->
                <div class="fb-add-grid" id="tab-add" style="display:none">

                    <div class="fb-add-category">
                        <div class="fb-add-cat-header">Layout</div>
                        <div class="fb-add-items">
                            <button class="fb-add-item" onclick="insertElement('section')"><i class="bi bi-layout-text-window"></i>Section</button>
                            <button class="fb-add-item" onclick="insertElement('container')"><i class="bi bi-bounding-box"></i>Container</button>
                            <button class="fb-add-item" onclick="insertElement('div')"><i class="bi bi-square"></i>Div</button>
                            <button class="fb-add-item" onclick="insertElement('flex-row')"><i class="bi bi-layout-split"></i>Flex Row</button>
                            <button class="fb-add-item" onclick="insertElement('flex-col')"><i class="bi bi-layout-three-columns"></i>Flex Col</button>
                            <button class="fb-add-item" onclick="insertElement('grid-2')"><i class="bi bi-grid"></i>Grid 2x</button>
                            <button class="fb-add-item" onclick="insertElement('grid-3')"><i class="bi bi-grid-3x3"></i>Grid 3x</button>
                        </div>
                    </div>

                    <div class="fb-add-category">
                        <div class="fb-add-cat-header">Text</div>
                        <div class="fb-add-items">
                            <button class="fb-add-item" onclick="insertElement('h1')"><i class="bi bi-type-h1"></i>H1</button>
                            <button class="fb-add-item" onclick="insertElement('h2')"><i class="bi bi-type-h2"></i>H2</button>
                            <button class="fb-add-item" onclick="insertElement('h3')"><i class="bi bi-type-h3"></i>H3</button>
                            <button class="fb-add-item" onclick="insertElement('paragraph')"><i class="bi bi-text-paragraph"></i>Paragraph</button>
                            <button class="fb-add-item" onclick="insertElement('link')"><i class="bi bi-link-45deg"></i>Link</button>
                            <button class="fb-add-item" onclick="insertElement('blockquote')"><i class="bi bi-blockquote-left"></i>Quote</button>
                        </div>
                    </div>

                    <div class="fb-add-category">
                        <div class="fb-add-cat-header">Media</div>
                        <div class="fb-add-items">
                            <button class="fb-add-item" onclick="insertElement('image')"><i class="bi bi-image"></i>Image</button>
                            <button class="fb-add-item" onclick="insertElement('video')"><i class="bi bi-play-btn"></i>Video</button>
                            <button class="fb-add-item" onclick="insertElement('hr')"><i class="bi bi-dash-lg"></i>Divider</button>
                        </div>
                    </div>

                    <div class="fb-add-category">
                        <div class="fb-add-cat-header">Interactive</div>
                        <div class="fb-add-items">
                            <button class="fb-add-item" onclick="insertElement('button')"><i class="bi bi-square-fill"></i>Button</button>
                            <button class="fb-add-item" onclick="insertElement('button-outline')"><i class="bi bi-square"></i>Outline Btn</button>
                            <button class="fb-add-item" onclick="insertElement('input')"><i class="bi bi-input-cursor-text"></i>Input</button>
                            <button class="fb-add-item" onclick="insertElement('form')"><i class="bi bi-ui-radios"></i>Form</button>
                        </div>
                    </div>

                    <div class="fb-add-category">
                        <div class="fb-add-cat-header">Lists</div>
                        <div class="fb-add-items">
                            <button class="fb-add-item" onclick="insertElement('ul')"><i class="bi bi-list-ul"></i>Unordered</button>
                            <button class="fb-add-item" onclick="insertElement('ol')"><i class="bi bi-list-ol"></i>Ordered</button>
                        </div>
                    </div>

                    <div class="fb-add-category">
                        <div class="fb-add-cat-header">Prebuilt</div>
                        <div class="fb-add-items">
                            <button class="fb-add-item" onclick="insertElement('hero')"><i class="bi bi-stars"></i>Hero</button>
                            <button class="fb-add-item" onclick="insertElement('card')"><i class="bi bi-card-heading"></i>Card</button>
                            <button class="fb-add-item" onclick="insertElement('nav')"><i class="bi bi-list"></i>Navbar</button>
                            <button class="fb-add-item" onclick="insertElement('footer')"><i class="bi bi-layout-text-window-reverse"></i>Footer</button>
                            <button class="fb-add-item" onclick="insertElement('testimonial')"><i class="bi bi-chat-quote"></i>Testimonial</button>
                        </div>
                    </div>
                </div>

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
            </div>
        </div>

        <!-- Canvas -->
        <div class="fb-canvas">
            <div class="fb-frame desktop" id="canvas-frame">
                <iframe id="builder-iframe" src="about:blank"></iframe>
            </div>

            <!-- Floating Actions Toolbar -->
            <div class="fb-float-actions" id="float-actions">
                <button class="fb-float-btn" onclick="moveElement('up')" title="Move Up"><i class="bi bi-arrow-up"></i></button>
                <button class="fb-float-btn" onclick="moveElement('down')" title="Move Down"><i class="bi bi-arrow-down"></i></button>
                <div class="fb-float-sep"></div>
                <button class="fb-float-btn" onclick="duplicateElement()" title="Duplicate"><i class="bi bi-copy"></i></button>
                <div class="fb-float-sep"></div>
                <button class="fb-float-btn danger" onclick="deleteElement()" title="Delete"><i class="bi bi-trash3"></i></button>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="fb-right" id="right-panel">
            <div class="fb-right-tabs">
                <button class="fb-rtab on" data-rtab="design" onclick="setRightTab('design')">Design</button>
                <button class="fb-rtab" data-rtab="inspect" onclick="setRightTab('inspect')">Inspect</button>
            </div>

            <div class="fb-right-body" id="right-body">
                <!-- Empty state -->
                <div class="fb-empty" id="panel-empty">
                    <i class="bi bi-cursor"></i>
                    <p>Select an element to<br>inspect its properties</p>
                </div>

                <!-- Design Tab -->
                <div id="panel-design" style="display:none">
                    <!-- Element info header -->
                    <div class="fb-section">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Element <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">Tag</span>
                                <span id="prop-tag" style="font-weight:600;color:var(--fig-accent);font-family:monospace;font-size:12px"></span>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">W × H</span>
                                <span id="prop-size" style="color:var(--fig-text3);font-family:monospace"></span>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">ID</span>
                                <input class="fb-input" id="prop-id" placeholder="none" oninput="onAttrChange('id')">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Class</span>
                                <input class="fb-input" id="prop-classes" placeholder="none" oninput="onAttrChange('className')">
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="fb-section" id="sec-content">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Content <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <textarea style="width:100%;" class="fb-input" id="prop-text" rows="3" placeholder="Text content..." oninput="onPropChange('text')"></textarea>
                        </div>
                    </div>

                    <!-- Image (for IMG) -->
                    <div class="fb-section" id="sec-image" style="display:none">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Image <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row"><span class="fb-lbl">Src</span><input class="fb-input" id="prop-src" placeholder="https://..." oninput="onPropChange('src')"></div>
                            <div class="fb-row"><span class="fb-lbl">Alt</span><input class="fb-input" id="prop-alt" placeholder="Description" oninput="onPropChange('alt')"></div>
                        </div>
                    </div>

                    <!-- Link (for A) -->
                    <div class="fb-section" id="sec-link" style="display:none">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Link <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row"><span class="fb-lbl">URL</span><input class="fb-input" id="prop-href" placeholder="https://..." oninput="onPropChange('href')"></div>
                        </div>
                    </div>

                    <!-- Layout -->
                    <div class="fb-section">
                        <div class="fb-sec-header" onclick="toggleSec(this)">
                            Layout <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">Display</span>
                                <select class="fb-input" id="prop-display" onchange="onStyleChange('display')">
                                    <option value="">—</option>
                                    <option value="block">Block</option>
                                    <option value="flex">Flex</option>
                                    <option value="grid">Grid</option>
                                    <option value="inline">Inline</option>
                                    <option value="inline-block">Inline Block</option>
                                    <option value="inline-flex">Inline Flex</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Dir</span>
                                <select class="fb-input" id="prop-flexDirection" onchange="onStyleChange('flexDirection')">
                                    <option value="">—</option>
                                    <option value="row">Row</option>
                                    <option value="column">Column</option>
                                    <option value="row-reverse">Row Rev</option>
                                    <option value="column-reverse">Col Rev</option>
                                </select>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Justify</span>
                                <select class="fb-input" id="prop-justifyContent" onchange="onStyleChange('justifyContent')">
                                    <option value="">—</option>
                                    <option value="flex-start">Start</option>
                                    <option value="center">Center</option>
                                    <option value="flex-end">End</option>
                                    <option value="space-between">Between</option>
                                    <option value="space-around">Around</option>
                                    <option value="space-evenly">Evenly</option>
                                </select>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Align</span>
                                <select class="fb-input" id="prop-alignItems" onchange="onStyleChange('alignItems')">
                                    <option value="">—</option>
                                    <option value="flex-start">Start</option>
                                    <option value="center">Center</option>
                                    <option value="flex-end">End</option>
                                    <option value="stretch">Stretch</option>
                                    <option value="baseline">Baseline</option>
                                </select>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Gap</span>
                                <input class="fb-input" id="prop-gap" placeholder="0px" oninput="onStyleChange('gap')">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Wrap</span>
                                <select class="fb-input" id="prop-flexWrap" onchange="onStyleChange('flexWrap')">
                                    <option value="">—</option>
                                    <option value="nowrap">No Wrap</option>
                                    <option value="wrap">Wrap</option>
                                    <option value="wrap-reverse">Wrap Rev</option>
                                </select>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Overflow</span>
                                <select class="fb-input" id="prop-overflow" onchange="onStyleChange('overflow')">
                                    <option value="">—</option>
                                    <option value="visible">Visible</option>
                                    <option value="hidden">Hidden</option>
                                    <option value="scroll">Scroll</option>
                                    <option value="auto">Auto</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Size -->
                    <div class="fb-section">
                        <div class="fb-sec-header" onclick="toggleSec(this)">
                            Size <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-2col" style="margin-bottom:6px">
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">W</div>
                                    <input class="fb-input-sm" id="prop-cssWidth" placeholder="auto" oninput="onStyleChange('width')">
                                </div>
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">H</div>
                                    <input class="fb-input-sm" id="prop-cssHeight" placeholder="auto" oninput="onStyleChange('height')">
                                </div>
                            </div>
                            <div class="fb-2col" style="margin-bottom:6px">
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Min W</div>
                                    <input class="fb-input-sm" id="prop-minWidth" placeholder="—" oninput="onStyleChange('minWidth')">
                                </div>
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Max W</div>
                                    <input class="fb-input-sm" id="prop-maxWidth" placeholder="—" oninput="onStyleChange('maxWidth')">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Spacing -->
                    <div class="fb-section">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Spacing <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div style="color:var(--fig-text4);font-size:9px;margin-bottom:4px;">Padding</div>
                            <div class="fb-4col" style="margin-bottom:8px">
                                <input class="fb-input-sm" id="prop-paddingTop" placeholder="T" oninput="onStyleChange('paddingTop')">
                                <input class="fb-input-sm" id="prop-paddingRight" placeholder="R" oninput="onStyleChange('paddingRight')">
                                <input class="fb-input-sm" id="prop-paddingBottom" placeholder="B" oninput="onStyleChange('paddingBottom')">
                                <input class="fb-input-sm" id="prop-paddingLeft" placeholder="L" oninput="onStyleChange('paddingLeft')">
                            </div>
                            <div style="color:var(--fig-text4);font-size:9px;margin-bottom:4px;">Margin</div>
                            <div class="fb-4col" style="margin-bottom:8px">
                                <input class="fb-input-sm" id="prop-marginTop" placeholder="T" oninput="onStyleChange('marginTop')">
                                <input class="fb-input-sm" id="prop-marginRight" placeholder="R" oninput="onStyleChange('marginRight')">
                                <input class="fb-input-sm" id="prop-marginBottom" placeholder="B" oninput="onStyleChange('marginBottom')">
                                <input class="fb-input-sm" id="prop-marginLeft" placeholder="L" oninput="onStyleChange('marginLeft')">
                            </div>
                        </div>
                    </div>

                    <!-- Typography -->
                    <div class="fb-section">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Typography <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">Family</span>
                                <select class="fb-input" id="prop-fontFamily" onchange="onStyleChange('fontFamily')">
                                    <option value="">—</option>
                                    <option value="Inter, sans-serif">Inter</option>
                                    <option value="system-ui, sans-serif">System</option>
                                    <option value="Arial, sans-serif">Arial</option>
                                    <option value="Georgia, serif">Georgia</option>
                                    <option value="'Times New Roman', serif">Times</option>
                                    <option value="'Courier New', monospace">Courier</option>
                                    <option value="monospace">Monospace</option>
                                </select>
                            </div>
                            <div class="fb-2col" style="margin-bottom:6px">
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Size</div>
                                    <input class="fb-input-sm" id="prop-fontSize" placeholder="16px" oninput="onStyleChange('fontSize')">
                                </div>
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Weight</div>
                                    <select class="fb-input-sm" id="prop-fontWeight" onchange="onStyleChange('fontWeight')" style="text-align:left;padding:0 4px;">
                                        <option value="">—</option>
                                        <option value="300">Light</option>
                                        <option value="400">Regular</option>
                                        <option value="500">Medium</option>
                                        <option value="600">Semi</option>
                                        <option value="700">Bold</option>
                                        <option value="800">Extra</option>
                                        <option value="900">Black</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fb-2col" style="margin-bottom:6px">
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Line H</div>
                                    <input class="fb-input-sm" id="prop-lineHeight" placeholder="auto" oninput="onStyleChange('lineHeight')">
                                </div>
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Letter</div>
                                    <input class="fb-input-sm" id="prop-letterSpacing" placeholder="0px" oninput="onStyleChange('letterSpacing')">
                                </div>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Align</span>
                                <div class="fb-icon-row">
                                    <button class="fb-icon-btn" data-align="left" onclick="onStyleSet('textAlign','left')"><i class="bi bi-text-left"></i></button>
                                    <button class="fb-icon-btn" data-align="center" onclick="onStyleSet('textAlign','center')"><i class="bi bi-text-center"></i></button>
                                    <button class="fb-icon-btn" data-align="right" onclick="onStyleSet('textAlign','right')"><i class="bi bi-text-right"></i></button>
                                    <button class="fb-icon-btn" data-align="justify" onclick="onStyleSet('textAlign','justify')"><i class="bi bi-justify"></i></button>
                                </div>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Style</span>
                                <div class="fb-icon-row">
                                    <button class="fb-icon-btn" id="btn-italic" onclick="toggleStyle('fontStyle','italic','normal')"><i class="bi bi-type-italic"></i></button>
                                    <button class="fb-icon-btn" id="btn-underline" onclick="toggleStyle('textDecorationLine','underline','none')"><i class="bi bi-type-underline"></i></button>
                                    <button class="fb-icon-btn" id="btn-strike" onclick="toggleStyle('textDecorationLine','line-through','none')"><i class="bi bi-type-strikethrough"></i></button>
                                </div>
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Case</span>
                                <select class="fb-input" id="prop-textTransform" onchange="onStyleChange('textTransform')">
                                    <option value="">—</option>
                                    <option value="none">None</option>
                                    <option value="uppercase">UPPER</option>
                                    <option value="lowercase">lower</option>
                                    <option value="capitalize">Title</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Fill -->
                    <div class="fb-section">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Fill <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-color-row">
                                <input type="color" class="fb-swatch" id="cp-bg" oninput="onColorChange('backgroundColor','cp-bg','prop-bg')">
                                <input class="fb-input" id="prop-bg" placeholder="transparent" oninput="onStyleChange('backgroundColor')">
                            </div>
                        </div>
                    </div>

                    <!-- Stroke (text color + border) -->
                    <div class="fb-section">
                        <div class="fb-sec-header open" onclick="toggleSec(this)">
                            Stroke <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div style="color:var(--fig-text4);font-size:9px;margin-bottom:4px;">Text Color</div>
                            <div class="fb-color-row">
                                <input type="color" class="fb-swatch" id="cp-color" oninput="onColorChange('color','cp-color','prop-color')">
                                <input class="fb-input" id="prop-color" placeholder="#000" oninput="onStyleChange('color')">
                            </div>
                            <div style="color:var(--fig-text4);font-size:9px;margin-bottom:4px;margin-top:4px;">Border</div>
                            <div class="fb-2col" style="margin-bottom:6px">
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Width</div>
                                    <input class="fb-input-sm" id="prop-borderWidth" placeholder="0px" oninput="onStyleChange('borderWidth')">
                                </div>
                                <div>
                                    <div style="color:var(--fig-text4);font-size:9px;margin-bottom:2px;">Style</div>
                                    <select class="fb-input-sm" id="prop-borderStyle" onchange="onStyleChange('borderStyle')" style="text-align:left;padding:0 4px;">
                                        <option value="">—</option>
                                        <option value="none">None</option>
                                        <option value="solid">Solid</option>
                                        <option value="dashed">Dashed</option>
                                        <option value="dotted">Dotted</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fb-color-row">
                                <input type="color" class="fb-swatch" id="cp-border" oninput="onColorChange('borderColor','cp-border','prop-borderColor')">
                                <input class="fb-input" id="prop-borderColor" placeholder="#000" oninput="onStyleChange('borderColor')">
                            </div>
                            <div style="color:var(--fig-text4);font-size:9px;margin-bottom:4px;margin-top:4px;">Radius</div>
                            <div class="fb-4col">
                                <input class="fb-input-sm" id="prop-borderTopLeftRadius" placeholder="TL" oninput="onStyleChange('borderTopLeftRadius')">
                                <input class="fb-input-sm" id="prop-borderTopRightRadius" placeholder="TR" oninput="onStyleChange('borderTopRightRadius')">
                                <input class="fb-input-sm" id="prop-borderBottomRightRadius" placeholder="BR" oninput="onStyleChange('borderBottomRightRadius')">
                                <input class="fb-input-sm" id="prop-borderBottomLeftRadius" placeholder="BL" oninput="onStyleChange('borderBottomLeftRadius')">
                            </div>
                        </div>
                    </div>

                    <!-- Effects -->
                    <div class="fb-section">
                        <div class="fb-sec-header" onclick="toggleSec(this)">
                            Effects <span class="chev"><i class="bi bi-chevron-right"></i></span>
                        </div>
                        <div class="fb-sec-body">
                            <div class="fb-row">
                                <span class="fb-lbl">Opacity</span>
                                <input class="fb-input" id="prop-opacity" placeholder="1" oninput="onStyleChange('opacity')">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Shadow</span>
                                <input class="fb-input" id="prop-boxShadow" placeholder="none" oninput="onStyleChange('boxShadow')">
                            </div>
                            <div class="fb-row">
                                <span class="fb-lbl">Cursor</span>
                                <select class="fb-input" id="prop-cursor" onchange="onStyleChange('cursor')">
                                    <option value="">—</option>
                                    <option value="auto">Auto</option>
                                    <option value="pointer">Pointer</option>
                                    <option value="default">Default</option>
                                    <option value="text">Text</option>
                                    <option value="move">Move</option>
                                    <option value="not-allowed">Not Allowed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Actions removed - now floating toolbar -->
                </div>

                <!-- Inspect Tab -->
                <div id="panel-inspect" style="display:none">
                    <!-- HTML Snippet -->
                    <div class="inspect-section">
                        <div class="inspect-label">HTML</div>
                        <div class="inspect-code">
                            <button class="inspect-copy" onclick="copyInspectHtml()" title="Copy"><i class="bi bi-clipboard"></i></button>
                            <pre id="inspect-html" contenteditable="true" ></pre>
                        </div>
                    </div>
                    <!-- Computed CSS -->
                    <div class="inspect-section" style="border-bottom:none;">
                        <div class="inspect-label">Computed Styles</div>
                        <div class="inspect-code">
                            <button class="inspect-copy" onclick="copyInspectCss()" title="Copy"><i class="bi bi-clipboard"></i></button>
                            <pre id="inspect-output"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="fb-toast" id="toast">Saved</div>
</div>

<script>
(function() {
    'use strict';

    const iframe = document.getElementById('builder-iframe');
    const panelEmpty = document.getElementById('panel-empty');
    const panelDesign = document.getElementById('panel-design');
    const panelInspect = document.getElementById('panel-inspect');
    const floatActions = document.getElementById('float-actions');
    let currentElement = null;
    let engineReady = false;
    let currentRightTab = 'design';
    let currentMode = 'select';
    let headData = {};
    let suppressHeadEcho = false;
    const headDebounce = {};

    // ── Load site into iframe ──
    const siteHtmlContent = <?php echo json_encode($site_html_content); ?>;
    const engineJsContent = <?php
        $engine_path = dirname(__FILE__) . '/engine.js';
        echo json_encode(file_exists($engine_path) ? file_get_contents($engine_path) : '');
    ?>;

    function loadSite() {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(siteHtmlContent);
        doc.close();
        setTimeout(injectEngine, 100);
    }

    function injectEngine() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            if (!doc.body) return;
            const existing = doc.getElementById('vb-engine-script');
            if (existing) existing.remove();
            const script = doc.createElement('script');
            script.id = 'vb-engine-script';
            script.setAttribute('data-vb-engine', 'true');
            script.textContent = engineJsContent;
            doc.body.appendChild(script);
        } catch (e) {
            console.warn('Could not inject engine:', e);
        }
    }

    // ── Messaging ──
    function sendToIframe(msg) {
        if (iframe.contentWindow) iframe.contentWindow.postMessage(msg, '*');
    }

    window.addEventListener('message', function(e) {
        const msg = e.data;
        if (!msg || !msg.type) return;

        if (msg.type === 'ENGINE_READY') { engineReady = true; sendToIframe({ type: 'SET_MODE', mode: currentMode }); }
        if (msg.type === 'ELEMENT_SELECTED') { currentElement = msg; showProperties(msg); }
        if (msg.type === 'ELEMENT_DESELECTED') { currentElement = null; hideProperties(); }
        if (msg.type === 'EDIT_MODE') { /* visual indicator could go here */ }
        if (msg.type === 'CONTENT_CHANGED') { /* live content sync */ }
        if (msg.type === 'HTML_CONTENT') { doSaveHtml(msg.html); }
        if (msg.type === 'ELEMENT_DELETED') { currentElement = null; hideProperties(); }
        if (msg.type === 'REQUEST_DELETE') { if (confirm('Delete this element?')) sendToIframe({ type: 'DELETE_ELEMENT' }); }
        if (msg.type === 'LAYERS_UPDATE') { renderLayers(msg.layers); }
        if (msg.type === 'HTML_SYNC_ERROR') { showToast('HTML error: ' + msg.error); }
        if (msg.type === 'HEAD_DATA') { applyHeadData(msg.data); }
    });

    // ── Show/Hide Properties ──
    function showProperties(info) {
        panelEmpty.style.display = 'none';
        // Respect current tab: show whichever tab is active
        panelDesign.style.display = currentRightTab === 'design' ? 'block' : 'none';
        panelInspect.style.display = currentRightTab === 'inspect' ? 'block' : 'none';
        floatActions.classList.add('show');

        // Element
        document.getElementById('prop-tag').textContent = info.tag.toLowerCase();
        document.getElementById('prop-size').textContent = Math.round(info.width) + ' × ' + Math.round(info.height);
        document.getElementById('prop-id').value = info.id || '';
        document.getElementById('prop-classes').value = info.classes || '';

        // Content / Image / Link
        const secContent = document.getElementById('sec-content');
        const secImage = document.getElementById('sec-image');
        const secLink = document.getElementById('sec-link');

        if (info.tag === 'IMG') {
            secContent.style.display = 'none'; secImage.style.display = 'block';
            document.getElementById('prop-src').value = info.src || '';
            document.getElementById('prop-alt').value = info.alt || '';
        } else {
            secContent.style.display = 'block'; secImage.style.display = 'none';
            document.getElementById('prop-text').value = info.text || '';
        }
        secLink.style.display = info.tag === 'A' ? 'block' : 'none';
        if (info.tag === 'A') document.getElementById('prop-href').value = info.href || '';

        // Layout
        setVal('prop-display', info.display);
        setVal('prop-flexDirection', info.flexDirection);
        setVal('prop-justifyContent', info.justifyContent);
        setVal('prop-alignItems', info.alignItems);
        document.getElementById('prop-gap').value = info.gap || '';
        setVal('prop-flexWrap', info.flexWrap);
        setVal('prop-overflow', info.overflow);

        // Size
        document.getElementById('prop-cssWidth').value = info.cssWidth || '';
        document.getElementById('prop-cssHeight').value = info.cssHeight || '';
        document.getElementById('prop-minWidth').value = info.minWidth || '';
        document.getElementById('prop-maxWidth').value = info.maxWidth || '';

        // Spacing
        document.getElementById('prop-paddingTop').value = info.paddingTop || '';
        document.getElementById('prop-paddingRight').value = info.paddingRight || '';
        document.getElementById('prop-paddingBottom').value = info.paddingBottom || '';
        document.getElementById('prop-paddingLeft').value = info.paddingLeft || '';
        document.getElementById('prop-marginTop').value = info.marginTop || '';
        document.getElementById('prop-marginRight').value = info.marginRight || '';
        document.getElementById('prop-marginBottom').value = info.marginBottom || '';
        document.getElementById('prop-marginLeft').value = info.marginLeft || '';

        // Typography
        document.getElementById('prop-fontSize').value = info.fontSize || '';
        setVal('prop-fontWeight', info.fontWeight);
        document.getElementById('prop-lineHeight').value = info.lineHeight || '';
        document.getElementById('prop-letterSpacing').value = info.letterSpacing || '';
        setVal('prop-textTransform', info.textTransform);

        // Font family - try to match
        const familySel = document.getElementById('prop-fontFamily');
        familySel.value = '';
        for (let i = 0; i < familySel.options.length; i++) {
            if (info.fontFamily && info.fontFamily.includes(familySel.options[i].value.split(',')[0].replace(/'/g, ''))) {
                familySel.value = familySel.options[i].value;
                break;
            }
        }

        // Text align buttons
        document.querySelectorAll('[data-align]').forEach(b => {
            b.classList.toggle('on', info.textAlign === b.dataset.align);
        });

        // Font style buttons
        document.getElementById('btn-italic').classList.toggle('on', info.fontStyle === 'italic');
        document.getElementById('btn-underline').classList.toggle('on', (info.textDecoration || '').includes('underline'));
        document.getElementById('btn-strike').classList.toggle('on', (info.textDecoration || '').includes('line-through'));

        // Fill
        document.getElementById('prop-bg').value = info.backgroundColor || '';
        setSwatchColor('cp-bg', info.backgroundColor);

        // Stroke
        document.getElementById('prop-color').value = info.color || '';
        setSwatchColor('cp-color', info.color);
        document.getElementById('prop-borderWidth').value = info.borderWidth || '';
        setVal('prop-borderStyle', info.borderStyle);
        document.getElementById('prop-borderColor').value = info.borderColor || '';
        setSwatchColor('cp-border', info.borderColor);
        document.getElementById('prop-borderTopLeftRadius').value = info.borderTopLeftRadius || '';
        document.getElementById('prop-borderTopRightRadius').value = info.borderTopRightRadius || '';
        document.getElementById('prop-borderBottomRightRadius').value = info.borderBottomRightRadius || '';
        document.getElementById('prop-borderBottomLeftRadius').value = info.borderBottomLeftRadius || '';

        // Effects
        document.getElementById('prop-opacity').value = info.opacity || '';
        document.getElementById('prop-boxShadow').value = info.boxShadow === 'none' ? '' : (info.boxShadow || '');
        setVal('prop-cursor', info.cursor);

        // Inspect tab
        updateInspect(info);
    }

    function hideProperties() {
        panelEmpty.style.display = 'flex';
        panelDesign.style.display = 'none';
        panelInspect.style.display = 'none';
        floatActions.classList.remove('show');
        document.getElementById('inspect-output').textContent = '';
        document.getElementById('inspect-html').innerHTML = '';
    }

    function setVal(id, val) {
        const el = document.getElementById(id);
        if (!el) return;
        // For selects, try to match option
        for (let i = 0; i < el.options.length; i++) {
            if (el.options[i].value === val) { el.selectedIndex = i; return; }
        }
        el.value = val || '';
    }

    function setSwatchColor(id, val) {
        const el = document.getElementById(id);
        try {
            const d = document.createElement('div');
            d.style.color = val;
            document.body.appendChild(d);
            const computed = getComputedStyle(d).color;
            document.body.removeChild(d);
            const m = computed.match(/\d+/g);
            if (m && m.length >= 3) {
                el.value = '#' + m.slice(0, 3).map(n => parseInt(n).toString(16).padStart(2, '0')).join('');
            }
        } catch (e) {}
    }

    function updateInspect(info) {
        if (!info) return;

        // HTML snippet with syntax highlighting
        if (info.htmlSnippet) {
            const formatted = formatHtml(info.htmlSnippet);
            document.getElementById('inspect-html').innerHTML = highlightHtml(formatted);
        } else {
            document.getElementById('inspect-html').innerHTML = '<span class="hl-text">No HTML available</span>';
        }

        // Computed CSS
        const lines = [];
        lines.push(`/* ${info.tag.toLowerCase()}${info.id ? '#'+info.id : ''}${info.classes ? '.'+info.classes.split(' ').join('.') : ''} */`);
        lines.push('');
        const props = [
            ['display', info.display], ['position', info.position],
            ['width', info.cssWidth], ['height', info.cssHeight],
            ['padding', `${info.paddingTop} ${info.paddingRight} ${info.paddingBottom} ${info.paddingLeft}`],
            ['margin', `${info.marginTop} ${info.marginRight} ${info.marginBottom} ${info.marginLeft}`],
            ['font-family', info.fontFamily], ['font-size', info.fontSize],
            ['font-weight', info.fontWeight], ['line-height', info.lineHeight],
            ['color', info.color], ['background-color', info.backgroundColor],
            ['border', `${info.borderWidth} ${info.borderStyle} ${info.borderColor}`],
            ['border-radius', info.borderRadius],
            ['opacity', info.opacity], ['box-shadow', info.boxShadow],
        ];
        props.forEach(([k, v]) => { if (v && v !== 'none' && v !== 'normal') lines.push(`${k}: ${v};`); });
        document.getElementById('inspect-output').textContent = lines.join('\n');
    }

    function formatHtml(html) {
        // Basic pretty-print: indent nested tags
        let result = '';
        let indent = 0;
        const pad = () => '  '.repeat(indent);
        // Split by tags
        const tokens = html.replace(/>\s*</g, '>\n<').split('\n');
        tokens.forEach(token => {
            const t = token.trim();
            if (!t) return;
            if (t.startsWith('</')) {
                indent = Math.max(0, indent - 1);
                result += pad() + t + '\n';
            } else if (t.startsWith('<') && !t.startsWith('<!') && !t.endsWith('/>') && !t.match(/^<(img|br|hr|input|meta|link|area|base|col|embed|source|track|wbr)\b/i)) {
                result += pad() + t + '\n';
                // Only indent if not self-closing and has a closing counterpart
                if (!t.includes('</')) indent++;
            } else {
                result += pad() + t + '\n';
            }
        });
        return result.trimEnd();
    }

    function highlightHtml(html) {
        // Process character by character to build highlighted output
        let out = '';
        let i = 0;
        const len = html.length;
        while (i < len) {
            if (html[i] === '<') {
                // Find end of tag
                const end = html.indexOf('>', i);
                if (end === -1) { out += esc(html.slice(i)); break; }
                const tag = html.slice(i, end + 1);
                out += highlightTag(tag);
                i = end + 1;
            } else {
                // Text content
                const next = html.indexOf('<', i);
                const text = next === -1 ? html.slice(i) : html.slice(i, next);
                out += '<span class="hl-text">' + esc(text) + '</span>';
                i = next === -1 ? len : next;
            }
        }
        return out;
    }

    function highlightTag(tag) {
        // Match: < or </, tag name, attributes, > or />
        const m = tag.match(/^(<\/?)(\w[\w-]*)([\s\S]*?)(\/?>)$/);
        if (!m) return esc(tag);
        let result = esc(m[1]) + '<span class="hl-tag">' + esc(m[2]) + '</span>';
        // Parse attributes
        const attrs = m[3];
        if (attrs.trim()) {
            result += attrs.replace(/([\w-]+)\s*=\s*"([^"]*)"/g, (_, name, val) =>
                ' <span class="hl-attr">' + esc(name) + '</span>=<span class="hl-val">"' + esc(val) + '"</span>'
            ).replace(/([\w-]+)\s*=\s*'([^']*)'/g, (_, name, val) =>
                ' <span class="hl-attr">' + esc(name) + '</span>=<span class="hl-val">\'' + esc(val) + '\'</span>'
            );
        }
        result += esc(m[4]);
        return result;
    }

    function esc(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    window.copyInspectHtml = function() {
        const el = document.getElementById('inspect-html');
        const text = el.textContent || el.innerText;
        navigator.clipboard.writeText(text).then(() => showToast('HTML copied'));
    };

    window.copyInspectCss = function() {
        const el = document.getElementById('inspect-output');
        navigator.clipboard.writeText(el.textContent).then(() => showToast('CSS copied'));
    };

    // ── HTML contenteditable sync ──
    const inspectHtmlEl = document.getElementById('inspect-html');
    let htmlSyncTimeout = null;

    inspectHtmlEl.addEventListener('input', function() {
        // Debounce — sync after user stops typing for 600ms
        clearTimeout(htmlSyncTimeout);
        htmlSyncTimeout = setTimeout(syncHtmlToElement, 600);
    });

    inspectHtmlEl.addEventListener('blur', function() {
        clearTimeout(htmlSyncTimeout);
        syncHtmlToElement();
    });

    function syncHtmlToElement() {
        if (!currentElement) return;
        const rawHtml = inspectHtmlEl.textContent || inspectHtmlEl.innerText;
        if (!rawHtml.trim()) return;
        sendToIframe({ type: 'SET_OUTER_HTML', html: rawHtml.trim() });
    }

    // ── Layers ──
    function renderLayers(layers) {
        const container = document.getElementById('tab-layers');
        if (!layers || !layers.length) {
            container.innerHTML = '<div class="fb-empty" style="height:200px"><i class="bi bi-layers"></i><p>No elements</p></div>';
            return;
        }
        container.innerHTML = layers.map(l => {
            const pad = 12 + l.depth * 16;
            const icons = { section: 'bi-layout-text-window', div: 'bi-square', nav: 'bi-list', header: 'bi-window', footer: 'bi-layout-text-window-reverse', img: 'bi-image', a: 'bi-link-45deg', button: 'bi-square-fill', input: 'bi-input-cursor-text', h1: 'bi-type-h1', h2: 'bi-type-h2', h3: 'bi-type-h3', p: 'bi-text-paragraph', span: 'bi-fonts', ul: 'bi-list-ul', ol: 'bi-list-ol', form: 'bi-ui-radios', article: 'bi-file-text', main: 'bi-window', aside: 'bi-layout-sidebar', figure: 'bi-image', blockquote: 'bi-blockquote-left', table: 'bi-table', hr: 'bi-dash-lg', video: 'bi-play-btn' };
            const icon = icons[l.tag] || 'bi-code';
            return `<div class="fb-layer${l.selected ? ' sel' : ''}" style="padding-left:${pad}px" onclick="selectLayer(${l.index})">
                <i class="bi ${icon}"></i>
                <span class="fb-layer-label">${l.label}</span>
                ${l.text ? `<span class="fb-layer-text">${l.text}</span>` : ''}
            </div>`;
        }).join('');
    }

    // ── Property handlers ──
    window.onStyleChange = function(prop) {
        const el = document.getElementById('prop-' + prop) || document.getElementById('prop-css' + prop.charAt(0).toUpperCase() + prop.slice(1));
        if (!el) return;
        const styles = {};
        styles[prop] = el.value;
        sendToIframe({ type: 'APPLY_STYLE', styles });
    };

    window.onStyleSet = function(prop, val) {
        sendToIframe({ type: 'APPLY_STYLE', styles: { [prop]: val } });
    };

    window.toggleStyle = function(prop, onVal, offVal) {
        if (!currentElement) return;
        const current = currentElement[prop] || currentElement[prop.replace(/([A-Z])/g, '-$1').toLowerCase()] || '';
        const val = current === onVal ? offVal : onVal;
        sendToIframe({ type: 'APPLY_STYLE', styles: { [prop]: val } });
    };

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

    window.onColorChange = function(prop, swatchId, inputId) {
        const val = document.getElementById(swatchId).value;
        document.getElementById(inputId).value = val;
        sendToIframe({ type: 'APPLY_STYLE', styles: { [prop]: val } });
    };

    window.onPropChange = function(prop) {
        const map = { text: 'innerText', src: 'src', alt: 'alt', href: 'href' };
        const attr = map[prop];
        if (attr) sendToIframe({ type: 'SET_ATTRIBUTE', attr, value: document.getElementById('prop-' + prop).value });
    };

    window.onAttrChange = function(attr) {
        const map = { id: 'prop-id', className: 'prop-classes' };
        sendToIframe({ type: 'SET_ATTRIBUTE', attr, value: document.getElementById(map[attr]).value });
    };

    // ── Viewport ──
    window.setViewport = function(mode) {
        document.getElementById('canvas-frame').className = 'fb-frame ' + mode;
        document.querySelectorAll('.fb-vp-btn').forEach(b => b.classList.remove('on'));
        document.querySelector('.fb-vp-btn[data-vp="' + mode + '"]').classList.add('on');
    };

    // ── Panel tabs ──
    window.setLeftTab = function(tab) {
        document.querySelectorAll('.fb-ptab').forEach(b => b.classList.toggle('on', b.dataset.tab === tab));
        document.getElementById('tab-layers').style.display = tab === 'layers' ? 'block' : 'none';
        document.getElementById('tab-add').style.display    = tab === 'add'    ? 'block' : 'none';
        document.getElementById('tab-page').style.display   = tab === 'page'   ? 'block' : 'none';
        if (tab === 'page') requestHead();
    };

    window.setRightTab = function(tab) {
        currentRightTab = tab;
        document.querySelectorAll('.fb-rtab').forEach(b => b.classList.toggle('on', b.dataset.rtab === tab));
        if (currentElement) {
            panelDesign.style.display = tab === 'design' ? 'block' : 'none';
            panelInspect.style.display = tab === 'inspect' ? 'block' : 'none';
        }
    };

    // ── Section collapse ──
    window.toggleSec = function(header) { header.classList.toggle('open'); };

    // ── Layers ──
    window.selectLayer = function(index) { sendToIframe({ type: 'SELECT_BY_INDEX', index }); };

    // ── Actions ──
    window.insertElement = function(template) { sendToIframe({ type: 'INSERT_ELEMENT', template }); };
    window.duplicateElement = function() { sendToIframe({ type: 'DUPLICATE_ELEMENT' }); };
    window.moveElement = function(dir) { sendToIframe({ type: 'MOVE_ELEMENT', direction: dir }); };
    window.deleteElement = function() { if (confirm('Delete this element?')) sendToIframe({ type: 'DELETE_ELEMENT' }); };

    // ── Undo/Redo placeholders ──
    window.undo = function() { /* TODO: history stack */ };
    window.redo = function() { /* TODO: history stack */ };

    // ── Save ──
    let saveForPreview = false;
    window.saveSite = function() { saveForPreview = false; sendToIframe({ type: 'GET_HTML' }); };

    function doSaveHtml(html) {
        if (saveForPreview) {
            saveForPreview = false;
            const win = window.open('', '_blank');
            if (win) { win.document.open(); win.document.write(html); win.document.close(); }
            return;
        }
        const form = new FormData();
        form.append('action', 'save_html');
        form.append('html', html);
        fetch(window.location.href, { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                showToast(data.success ? 'Saved' : 'Save failed: ' + (data.error || 'Unknown'));
            })
            .catch(() => showToast('Save failed'));
    }

    function showToast(msg) {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2500);
    }

    // ── Preview ──
    window.previewSite = function() {
        saveForPreview = true;
        sendToIframe({ type: 'GET_HTML' });
    };

    // ── Mode switching ──
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

    // ── Keyboard shortcuts ──
    document.addEventListener('keydown', function (e) {
        const t = e.target;
        if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        const k = e.key.toLowerCase();
        if (k === 'v') { setMode('select');      e.preventDefault(); }
        if (k === 'h') { setMode('interaction'); e.preventDefault(); }
        if (k === 'm') { setMode('drag');        e.preventDefault(); }
    });

    // ── Init ──
    loadSite();
})();
</script>
