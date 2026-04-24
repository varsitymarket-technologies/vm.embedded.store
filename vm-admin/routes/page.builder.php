<?php
// ── PHP BACKEND ──
$active_theme_file = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/theme";
$active_theme_name = file_exists($active_theme_file) ? trim(file_get_contents($active_theme_file)) : '';
$theme_base_path   = dirname(dirname(dirname(__FILE__))) . '/themes/' . $active_theme_name;

if (file_exists(dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/builder.cache.html")) {
    $index_file = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/builder.cache.html";
} elseif (file_exists($theme_base_path . '/index.php')) {
    $index_file = $theme_base_path . '/index.php';
} else {
    $index_file = null;
}

// Config
$config = [];
$config_path = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/config.json";
if (file_exists($config_path)) {
    $config = json_decode(file_get_contents($config_path), true) ?: [];
}

// Save handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_all') {
    if (isset($_POST['site_rendered_html']) && $index_file) {
        $cache_path = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__ . "/builder.cache.html";
        file_put_contents($cache_path, $_POST['site_rendered_html']);
    }
    $updated = false;
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'CONF_') === 0) {
            $real_key = substr($k, 5);
            $config[$real_key] = $v;
            $updated = true;
        }
    }
    if ($updated) {
        file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    echo "<script>window.__SAVED__=true;</script>";
}

$site_url    = "https://8016-cs-303aa018-3691-48e4-861b-ac80ea045b86.cs-asia-southeast1-palm.cloudshell.dev";
$domain = defined('__WEBSITE_DOMAIN__') ? __WEBSITE_DOMAIN__ : '';
$target = defined('__DOMAIN__') ? __DOMAIN__ : '';

@include_once dirname(dirname(dirname(__FILE__))) . "/services/export.store.source.php";

// Load existing content: builder cache > theme > export
$current_code = '';
if ($index_file && file_exists($index_file)) {
    $current_code = file_get_contents($index_file);
} elseif (!empty($target)) {
    $theme_check = dirname(dirname(dirname(__FILE__))) . "/sites/" . $target . "/theme";
    if (file_exists($theme_check)) {
        try {
            $current_code = export_application($target, $domain);
        } catch (\Throwable $e) {
            $current_code = '';
        }
    }
}
// Fallback: starter template when no site content exists
if (empty(trim($current_code ?? ''))) {
    $current_code = '<!DOCTYPE html>
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
.section h2 { font-size: 1.8rem; font-weight: 700; margin-bottom: 16px; }
.section p { font-size: 1rem; line-height: 1.7; color: #555; }
</style>
</head>
<body>
<div class="hero">
  <h1>Welcome to Your Store</h1>
  <p>Start building your storefront by editing this page. Click any element to select it, double-click text to edit inline.</p>
  <button>Get Started</button>
</div>
<div class="section">
  <h2>About Us</h2>
  <p>This is your starter template. Use the Page Builder to customise every element. Add new blocks using the Insert button above.</p>
</div>
</body>
</html>';
}
?>

<style>
/* ══════════════════════════════════════════
   FIGMA-STYLE PAGE BUILDER - DESIGN SYSTEM
   ══════════════════════════════════════════ */
:root {
    --bg-app: #1e1e1e;
    --bg-panel: #252526;
    --bg-panel-alt: #2d2d2d;
    --bg-input: #3c3c3c;
    --bg-hover: #37373d;
    --bg-active: #094771;
    --bg-canvas: #111;
    --border: #3e3e42;
    --border-light: #4e4e52;
    --text: #cccccc;
    --text-dim: #858585;
    --text-bright: #e0e0e0;
    --accent: #0d99ff;
    --accent-hover: #38b6ff;
    --accent-soft: rgba(13,153,255,0.12);
    --green: #3cb371;
    --amber: #e8a73e;
    --red: #f44747;
    --purple: #c678dd;
    --radius: 6px;
    --panel-w: 260px;
    --topbar-h: 44px;
}

* { box-sizing: border-box; }

.fb-root {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
    background: var(--bg-app);
    color: var(--text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
    font-size: 12px;
    -webkit-font-smoothing: antialiased;
}

/* ── TOPBAR ── */
.fb-topbar {
    height: var(--topbar-h);
    min-height: var(--topbar-h);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 12px;
    background: var(--bg-panel);
    border-bottom: 1px solid var(--border);
    z-index: 100;
    gap: 8px;
}
.fb-topbar-left, .fb-topbar-center, .fb-topbar-right {
    display: flex;
    align-items: center;
    gap: 6px;
}
.fb-topbar-center { flex: 1; justify-content: center; }

.fb-logo {
    font-weight: 700;
    font-size: 13px;
    color: var(--text-bright);
    display: flex;
    align-items: center;
    gap: 6px;
    padding-right: 12px;
    border-right: 1px solid var(--border);
    margin-right: 4px;
}
.fb-logo i { color: var(--accent); font-size: 16px; }

.tb-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 5px 10px;
    border: none;
    border-radius: var(--radius);
    background: transparent;
    color: var(--text);
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.tb-btn:hover { background: var(--bg-hover); color: var(--text-bright); }
.tb-btn.active { background: var(--accent-soft); color: var(--accent); }
.tb-btn i { font-size: 14px; }
.tb-sep { width: 1px; height: 20px; background: var(--border); margin: 0 2px; }

.tb-btn-primary {
    background: var(--accent);
    color: #fff;
    font-weight: 600;
    padding: 5px 14px;
}
.tb-btn-primary:hover { background: var(--accent-hover); color: #fff; }

.vp-group {
    display: flex;
    background: var(--bg-input);
    border-radius: var(--radius);
    overflow: hidden;
}
.vp-btn {
    padding: 5px 10px;
    border: none;
    background: transparent;
    color: var(--text-dim);
    cursor: pointer;
    font-size: 13px;
    transition: all 0.15s;
}
.vp-btn:hover { color: var(--text); }
.vp-btn.on { background: var(--accent-soft); color: var(--accent); }

.undo-group {
    display: flex;
    gap: 2px;
}
.undo-btn {
    padding: 4px 7px;
    border: none;
    background: transparent;
    color: var(--text-dim);
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.15s;
}
.undo-btn:hover:not(:disabled) { background: var(--bg-hover); color: var(--text); }
.undo-btn:disabled { opacity: 0.3; cursor: default; }

.zoom-display {
    font-size: 11px;
    color: var(--text-dim);
    padding: 3px 8px;
    background: var(--bg-input);
    border-radius: 4px;
    min-width: 48px;
    text-align: center;
}

/* ── BODY LAYOUT ── */
.fb-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* ── LEFT PANEL (LAYERS) ── */
.fb-left {
    width: var(--panel-w);
    min-width: var(--panel-w);
    background: var(--bg-panel);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: width 0.2s, min-width 0.2s;
}
.fb-left.collapsed { width: 0; min-width: 0; border: none; }
.fb-left.collapsed * { display: none; }

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-dim);
    border-bottom: 1px solid var(--border);
    user-select: none;
}
.panel-header button {
    border: none;
    background: none;
    color: var(--text-dim);
    cursor: pointer;
    padding: 2px;
    font-size: 14px;
}
.panel-header button:hover { color: var(--text); }

.layers-search {
    padding: 8px 10px;
    border-bottom: 1px solid var(--border);
}
.layers-search input {
    width: 100%;
    padding: 5px 8px 5px 28px;
    background: var(--bg-input);
    border: 1px solid transparent;
    border-radius: 4px;
    color: var(--text);
    font-size: 11px;
    outline: none;
    transition: border-color 0.15s;
}
.layers-search input:focus { border-color: var(--accent); }
.layers-search { position: relative; }
.layers-search i {
    position: absolute;
    left: 19px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-dim);
    font-size: 12px;
    pointer-events: none;
}

.layers-tree {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 4px 0;
}
.layers-tree::-webkit-scrollbar { width: 6px; }
.layers-tree::-webkit-scrollbar-thumb { background: var(--bg-input); border-radius: 3px; }

.layer-item {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px 3px calc(8px + var(--depth, 0) * 14px);
    cursor: pointer;
    user-select: none;
    transition: background 0.1s;
    font-size: 11px;
    color: var(--text);
    min-height: 26px;
}
.layer-item:hover { background: var(--bg-hover); }
.layer-item.selected { background: var(--bg-active); color: var(--text-bright); }
.layer-item.drag-over { border-top: 2px solid var(--accent); }

.layer-toggle {
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    color: var(--text-dim);
    cursor: pointer;
    font-size: 10px;
    flex-shrink: 0;
    border-radius: 3px;
    transition: all 0.15s;
}
.layer-toggle:hover { background: var(--bg-input); color: var(--text); }
.layer-toggle.open { transform: rotate(90deg); }
.layer-toggle.empty { visibility: hidden; }

.layer-icon {
    font-size: 11px;
    color: var(--text-dim);
    width: 16px;
    text-align: center;
    flex-shrink: 0;
}
.layer-name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.layer-tag {
    font-size: 9px;
    color: var(--text-dim);
    padding: 1px 4px;
    background: var(--bg-input);
    border-radius: 3px;
    flex-shrink: 0;
}

/* ── CANVAS ── */
.fb-canvas {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-canvas);
    overflow: hidden;
    position: relative;
}

.canvas-wrap {
    background: #fff;
    box-shadow: 0 0 0 1px rgba(255,255,255,0.05), 0 20px 60px rgba(0,0,0,0.5);
    border-radius: 4px;
    overflow: hidden;
    transition: width 0.3s ease, height 0.3s ease;
    position: relative;
}
.canvas-wrap.vp-desktop { width: 100%; height: 100%; border-radius: 0; }
.canvas-wrap.vp-tablet  { width: 768px; height: 100%; max-height: 1024px; }
.canvas-wrap.vp-mobile  { width: 375px; height: 100%; max-height: 812px; }

.canvas-wrap iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

/* ── RIGHT PANEL (DESIGN) ── */
.fb-right {
    width: 280px;
    min-width: 280px;
    background: var(--bg-panel);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: width 0.2s, min-width 0.2s;
}
.fb-right.collapsed { width: 0; min-width: 0; border: none; }
.fb-right.collapsed * { display: none; }

.design-tabs {
    display: flex;
    border-bottom: 1px solid var(--border);
}
.design-tab {
    flex: 1;
    padding: 8px;
    border: none;
    background: transparent;
    color: var(--text-dim);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    border-bottom: 2px solid transparent;
    transition: all 0.15s;
}
.design-tab:hover { color: var(--text); }
.design-tab.active { color: var(--accent); border-bottom-color: var(--accent); }

.design-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
}
.design-body::-webkit-scrollbar { width: 6px; }
.design-body::-webkit-scrollbar-thumb { background: var(--bg-input); border-radius: 3px; }

/* Empty state */
.design-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: var(--text-dim);
    gap: 10px;
}
.design-empty i { font-size: 32px; opacity: 0.3; }
.design-empty p { font-size: 11px; line-height: 1.5; }

/* Property sections */
.prop-section {
    border-bottom: 1px solid var(--border);
}
.prop-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    cursor: pointer;
    user-select: none;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-dim);
    transition: color 0.15s;
}
.prop-header:hover { color: var(--text); }
.prop-header i { font-size: 10px; transition: transform 0.15s; }
.prop-header.open i { transform: rotate(90deg); }

.prop-body {
    padding: 0 12px 10px;
    display: none;
}
.prop-body.open { display: block; }

.prop-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}
.prop-label {
    width: 60px;
    font-size: 11px;
    color: var(--text-dim);
    flex-shrink: 0;
}
.prop-input {
    flex: 1;
    padding: 4px 8px;
    background: var(--bg-input);
    border: 1px solid transparent;
    border-radius: 4px;
    color: var(--text);
    font-size: 11px;
    outline: none;
    transition: border-color 0.15s;
}
.prop-input:focus { border-color: var(--accent); }

.prop-input-sm {
    width: 50px;
    padding: 4px 6px;
    background: var(--bg-input);
    border: 1px solid transparent;
    border-radius: 4px;
    color: var(--text);
    font-size: 11px;
    outline: none;
    text-align: center;
}
.prop-input-sm:focus { border-color: var(--accent); }

.prop-color-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}
.prop-color-swatch {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 1px solid var(--border);
    cursor: pointer;
    padding: 0;
    overflow: hidden;
}
.prop-color-swatch::-webkit-color-swatch-wrapper { padding: 0; }
.prop-color-swatch::-webkit-color-swatch { border: none; }

/* Spacing box model */
.spacing-box {
    position: relative;
    width: 100%;
    padding: 24px 32px;
    margin: 4px 0 8px;
}
.spacing-margin-box {
    border: 1px dashed var(--border-light);
    border-radius: 6px;
    padding: 16px;
    position: relative;
    background: rgba(232,167,62,0.04);
}
.spacing-padding-box {
    border: 1px dashed var(--accent);
    border-radius: 4px;
    padding: 14px;
    background: rgba(13,153,255,0.04);
    display: flex;
    align-items: center;
    justify-content: center;
}
.spacing-center-label {
    font-size: 9px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.spacing-input {
    position: absolute;
    width: 32px;
    padding: 2px;
    background: var(--bg-input);
    border: 1px solid transparent;
    border-radius: 3px;
    color: var(--text);
    font-size: 10px;
    text-align: center;
    outline: none;
}
.spacing-input:focus { border-color: var(--accent); }
.sp-mt { top: 2px; left: 50%; transform: translateX(-50%); }
.sp-mb { bottom: 2px; left: 50%; transform: translateX(-50%); }
.sp-ml { left: 6px; top: 50%; transform: translateY(-50%); }
.sp-mr { right: 6px; top: 50%; transform: translateY(-50%); }
.sp-pt { top: 2px; left: 50%; transform: translateX(-50%); }
.sp-pb { bottom: 2px; left: 50%; transform: translateX(-50%); }
.sp-pl { left: 2px; top: 50%; transform: translateY(-50%); }
.sp-pr { right: 2px; top: 50%; transform: translateY(-50%); }
.spacing-label-m { position: absolute; top: 4px; left: 8px; font-size: 8px; color: var(--amber); text-transform: uppercase; letter-spacing: 0.5px; }
.spacing-label-p { position: absolute; top: 2px; left: 6px; font-size: 8px; color: var(--accent); text-transform: uppercase; letter-spacing: 0.5px; }

/* Quick actions */
.prop-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px;
    margin-top: 6px;
}
.prop-act-btn {
    padding: 6px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text);
    border-radius: 4px;
    cursor: pointer;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.15s;
}
.prop-act-btn:hover { background: var(--bg-hover); border-color: var(--border-light); }
.prop-act-btn.danger { color: var(--red); border-color: rgba(244,71,71,0.3); }
.prop-act-btn.danger:hover { background: rgba(244,71,71,0.1); }

/* ── FLOATING TOOLBAR ── */
.fb-float-bar {
    position: fixed;
    z-index: 1000;
    display: none;
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 4px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    gap: 2px;
    align-items: center;
}
.fb-float-bar.show { display: flex; }
.fl-btn {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    color: var(--text);
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    transition: all 0.1s;
}
.fl-btn:hover { background: var(--bg-hover); color: var(--text-bright); }
.fl-btn.on { background: var(--accent-soft); color: var(--accent); }
.fl-sep { width: 1px; height: 20px; background: var(--border); margin: 0 2px; }

/* ── BLOCK PICKER ── */
.modal-bg {
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: rgba(0,0,0,0.55);
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.modal-bg.open { display: flex; }
.modal-box {
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: 480px;
    max-width: 90vw;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(0,0,0,0.5);
    animation: modalIn 0.2s ease-out;
}
@keyframes modalIn { from { opacity:0; transform:scale(0.95) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }

.modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
}
.modal-head h3 { font-size: 14px; font-weight: 600; color: var(--text-bright); margin: 0; }
.modal-close {
    border: none;
    background: none;
    color: var(--text-dim);
    cursor: pointer;
    font-size: 16px;
    padding: 4px;
    border-radius: 4px;
}
.modal-close:hover { background: var(--bg-hover); color: var(--text); }

.block-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 16px;
}
.block-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px 8px;
    background: var(--bg-panel-alt);
    border: 1px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    color: var(--text);
}
.block-card:hover { border-color: var(--accent); background: var(--accent-soft); color: var(--accent); }
.block-card i { font-size: 22px; }
.block-card span { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }

.custom-html-area {
    display: none;
    padding: 0 16px 16px;
}
.custom-html-area textarea {
    width: 100%;
    height: 100px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text);
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    padding: 10px;
    outline: none;
    resize: vertical;
}
.custom-html-area textarea:focus { border-color: var(--accent); }

/* ── CONFIG MODAL ── */
.cfg-body { padding: 16px; max-height: 60vh; overflow-y: auto; }
.cfg-body::-webkit-scrollbar { width: 6px; }
.cfg-body::-webkit-scrollbar-thumb { background: var(--bg-input); border-radius: 3px; }
.cfg-section { margin-bottom: 16px; }
.cfg-section-title { font-size: 11px; font-weight: 700; color: var(--accent); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.cfg-field { margin-bottom: 8px; }
.cfg-field label { display: block; font-size: 10px; color: var(--text-dim); margin-bottom: 3px; font-weight: 600; }
.cfg-field textarea {
    width: 100%;
    padding: 6px 8px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 4px;
    color: var(--text);
    font-size: 11px;
    outline: none;
    resize: vertical;
    min-height: 32px;
}
.cfg-field textarea:focus { border-color: var(--accent); }
.modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 16px;
    border-top: 1px solid var(--border);
}

/* ── TOASTS ── */
#toast-wrap {
    position: fixed;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
}
.toast {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    animation: toastIn 0.25s ease-out;
    white-space: nowrap;
}
.toast.ok  { background: #1a3a2a; color: #4ade80; border: 1px solid rgba(74,222,128,0.2); }
.toast.err { background: #3a1a1a; color: #f87171; border: 1px solid rgba(248,113,113,0.2); }
.toast.inf { background: #1a2a3a; color: #60a5fa; border: 1px solid rgba(96,165,250,0.2); }
@keyframes toastIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

/* ── SHORTCUTS PANEL ── */
.shortcuts-panel {
    position: fixed;
    bottom: 60px;
    right: 20px;
    z-index: 8000;
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    width: 240px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.4);
    display: none;
}
.shortcuts-panel.show { display: block; }
.shortcuts-panel h4 { margin: 0 0 10px; font-size: 12px; color: var(--text-bright); }
.sc-row {
    display: flex;
    justify-content: space-between;
    padding: 3px 0;
    font-size: 11px;
}
.sc-row span:first-child { color: var(--text-dim); }
.sc-key {
    padding: 1px 6px;
    background: var(--bg-input);
    border-radius: 3px;
    font-size: 10px;
    font-family: monospace;
    color: var(--text);
}

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
    .fb-left { display: none; }
    .fb-right { width: 240px; min-width: 240px; }
}
@media (max-width: 640px) {
    .fb-right { display: none; }
    .fb-topbar-center { display: none; }
}
</style>

<!-- ══════════════════════════════════════════
     HTML STRUCTURE
     ══════════════════════════════════════════ -->
<div class="fb-root">

    <!-- TOPBAR -->
    <div class="fb-topbar">
        <div class="fb-topbar-left">
            <div class="fb-logo">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                Page Builder
            </div>
            <button class="tb-btn" id="btn-toggle-layers" onclick="toggleLeft()" title="Toggle Layers">
                <i class="bi bi-layers"></i>
            </button>
            <div class="tb-sep"></div>
            <div class="undo-group">
                <button class="undo-btn" id="btn-undo" onclick="undo()" title="Undo (Ctrl+Z)" disabled>
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
                <button class="undo-btn" id="btn-redo" onclick="redo()" title="Redo (Ctrl+Shift+Z)" disabled>
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <div class="fb-topbar-center">
            <div class="vp-group">
                <button class="vp-btn on" id="vp-d" onclick="setVP('desktop')" title="Desktop"><i class="bi bi-display"></i></button>
                <button class="vp-btn" id="vp-t" onclick="setVP('tablet')" title="Tablet"><i class="bi bi-tablet"></i></button>
                <button class="vp-btn" id="vp-m" onclick="setVP('mobile')" title="Mobile"><i class="bi bi-phone"></i></button>
            </div>
            <span class="zoom-display" id="zoom-display">100%</span>
        </div>

        <div class="fb-topbar-right">
            <button class="tb-btn" onclick="openBlockPicker('after')" title="Insert Element">
                <i class="bi bi-plus-square"></i> Insert
            </button>
            <button class="tb-btn" onclick="openCfg()" title="Site Settings">
                <i class="bi bi-gear"></i>
            </button>
            <div class="tb-sep"></div>
            <a href="<?php echo $site_url; ?>" target="_blank" class="tb-btn" title="Preview Site">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
            <button class="tb-btn-primary tb-btn" onclick="saveViaForm()" title="Save (Ctrl+S)">
                <i class="bi bi-check-lg"></i> Save
            </button>
        </div>
    </div>

    <!-- BODY -->
    <div class="fb-body">

        <!-- LEFT: LAYERS -->
        <div class="fb-left" id="left-panel">
            <div class="panel-header">
                <span>Layers</span>
                <button onclick="refreshLayers()" title="Refresh"><i class="bi bi-arrow-repeat"></i></button>
            </div>
            <div class="layers-search">
                <i class="bi bi-search"></i>
                <input type="text" id="layer-search" placeholder="Search layers..." oninput="filterLayers(this.value)">
            </div>
            <div class="layers-tree" id="layers-tree"></div>
        </div>

        <!-- CANVAS -->
        <div class="fb-canvas" id="canvas-area">
            <div class="canvas-wrap vp-desktop" id="canvas-wrap">
                <iframe id="editor-iframe" src="about:blank"></iframe>
            </div>
        </div>

        <!-- RIGHT: DESIGN PANEL -->
        <div class="fb-right" id="right-panel">
            <div class="design-tabs">
                <button class="design-tab active" data-tab="design" onclick="switchTab('design')">Design</button>
                <button class="design-tab" data-tab="inspect" onclick="switchTab('inspect')">Inspect</button>
            </div>
            <div class="design-body" id="design-body">
                <!-- Empty state -->
                <div class="design-empty" id="design-empty">
                    <i class="bi bi-cursor"></i>
                    <p>Click any element on the<br>canvas to start editing</p>
                    <span style="font-size:10px;color:var(--text-dim)">Double-click text to edit inline</span>
                </div>

                <!-- Active element properties -->
                <div id="design-active" style="display:none">

                    <!-- Element info -->
                    <div class="prop-section">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-tag"></i> Element</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <div class="prop-row">
                                <span class="prop-label">Tag</span>
                                <span id="el-tag" style="font-weight:600;color:var(--purple)">DIV</span>
                            </div>
                            <div class="prop-row">
                                <span class="prop-label">Classes</span>
                                <input class="prop-input" id="ip-classes" placeholder="class1 class2">
                            </div>
                        </div>
                    </div>

                    <!-- Typography -->
                    <div class="prop-section" id="sec-typo">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-fonts"></i> Typography</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <div class="prop-row">
                                <span class="prop-label">Size</span>
                                <input class="prop-input" id="ip-fontsize" placeholder="16px">
                            </div>
                            <div class="prop-row">
                                <span class="prop-label">Weight</span>
                                <select class="prop-input" id="ip-fontweight">
                                    <option value="">Auto</option>
                                    <option value="300">Light (300)</option>
                                    <option value="400">Regular (400)</option>
                                    <option value="500">Medium (500)</option>
                                    <option value="600">Semi-Bold (600)</option>
                                    <option value="700">Bold (700)</option>
                                    <option value="800">Extra-Bold (800)</option>
                                </select>
                            </div>
                            <div class="prop-row" style="gap:3px">
                                <button class="fl-btn" onclick="rtCmd('bold')" title="Bold"><i class="bi bi-type-bold"></i></button>
                                <button class="fl-btn" onclick="rtCmd('italic')" title="Italic"><i class="bi bi-type-italic"></i></button>
                                <button class="fl-btn" onclick="rtCmd('underline')" title="Underline"><i class="bi bi-type-underline"></i></button>
                                <button class="fl-btn" onclick="rtCmd('strikeThrough')" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
                                <div class="fl-sep"></div>
                                <button class="fl-btn" onclick="rtCmd('justifyLeft')" title="Align Left"><i class="bi bi-text-left"></i></button>
                                <button class="fl-btn" onclick="rtCmd('justifyCenter')" title="Center"><i class="bi bi-text-center"></i></button>
                                <button class="fl-btn" onclick="rtCmd('justifyRight')" title="Align Right"><i class="bi bi-text-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Colors -->
                    <div class="prop-section">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-palette"></i> Fill & Color</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <div class="prop-color-row">
                                <input type="color" class="prop-color-swatch" id="cp-color" oninput="syncColor('ip-color','cp-color')">
                                <span class="prop-label">Text</span>
                                <input class="prop-input" id="ip-color" placeholder="#000000" oninput="syncPicker('ip-color','cp-color')">
                            </div>
                            <div class="prop-color-row">
                                <input type="color" class="prop-color-swatch" id="cp-bg" oninput="syncColor('ip-bg','cp-bg')">
                                <span class="prop-label">Fill</span>
                                <input class="prop-input" id="ip-bg" placeholder="transparent" oninput="syncPicker('ip-bg','cp-bg')">
                            </div>
                        </div>
                    </div>

                    <!-- Spacing (box model) -->
                    <div class="prop-section">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-bounding-box"></i> Spacing</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <div class="spacing-box">
                                <div class="spacing-margin-box">
                                    <span class="spacing-label-m">Margin</span>
                                    <input class="spacing-input sp-mt" id="sp-mt" placeholder="0" title="Margin Top">
                                    <input class="spacing-input sp-mb" id="sp-mb" placeholder="0" title="Margin Bottom">
                                    <input class="spacing-input sp-ml" id="sp-ml" placeholder="0" title="Margin Left">
                                    <input class="spacing-input sp-mr" id="sp-mr" placeholder="0" title="Margin Right">
                                    <div class="spacing-padding-box">
                                        <span class="spacing-label-p">Padding</span>
                                        <input class="spacing-input sp-pt" id="sp-pt" placeholder="0" title="Padding Top">
                                        <input class="spacing-input sp-pb" id="sp-pb" placeholder="0" title="Padding Bottom">
                                        <input class="spacing-input sp-pl" id="sp-pl" placeholder="0" title="Padding Left">
                                        <input class="spacing-input sp-pr" id="sp-pr" placeholder="0" title="Padding Right">
                                        <span class="spacing-center-label">content</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image (shown for IMG) -->
                    <div class="prop-section" id="sec-img" style="display:none">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-image"></i> Image</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <div class="prop-row">
                                <span class="prop-label">Source</span>
                                <input class="prop-input" id="ip-src" placeholder="https://...">
                            </div>
                            <div class="prop-row">
                                <span class="prop-label">Width</span>
                                <input class="prop-input-sm" id="ip-img-w" placeholder="auto">
                                <span class="prop-label" style="width:auto">Height</span>
                                <input class="prop-input-sm" id="ip-img-h" placeholder="auto">
                            </div>
                            <div class="prop-row">
                                <span class="prop-label">Alt</span>
                                <input class="prop-input" id="ip-alt" placeholder="Image description">
                            </div>
                            <button class="prop-act-btn" style="width:100%" onclick="document.getElementById('img-upload').click()">
                                <i class="bi bi-upload"></i> Upload Image
                            </button>
                            <input type="file" id="img-upload" accept="image/*" style="display:none" onchange="onImgUpload(event)">
                        </div>
                    </div>

                    <!-- Link (shown for A) -->
                    <div class="prop-section" id="sec-link" style="display:none">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-link-45deg"></i> Link</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <div class="prop-row">
                                <span class="prop-label">URL</span>
                                <input class="prop-input" id="ip-href" placeholder="https://...">
                            </div>
                            <div class="prop-row">
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:11px;color:var(--text)">
                                    <input type="checkbox" id="ip-blank"> Open in new tab
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="prop-section">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-lightning"></i> Actions</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <button class="prop-act-btn" style="width:100%;margin-bottom:6px" onclick="applyChanges()">
                                <i class="bi bi-check-circle"></i> Apply Changes
                            </button>
                            <div class="prop-actions">
                                <button class="prop-act-btn" onclick="openBlockPicker('before')"><i class="bi bi-arrow-up"></i> Before</button>
                                <button class="prop-act-btn" onclick="openBlockPicker('after')"><i class="bi bi-arrow-down"></i> After</button>
                                <button class="prop-act-btn" onclick="openBlockPicker('inside')"><i class="bi bi-box"></i> Inside</button>
                                <button class="prop-act-btn" onclick="dupEl()"><i class="bi bi-copy"></i> Clone</button>
                            </div>
                            <button class="prop-act-btn danger" style="width:100%;margin-top:6px" onclick="delEl()">
                                <i class="bi bi-trash3"></i> Delete Element
                            </button>
                        </div>
                    </div>

                </div><!-- /design-active -->

                <!-- Inspect tab content -->
                <div id="inspect-tab" style="display:none">
                    <div class="prop-section">
                        <div class="prop-header open" onclick="toggleProp(this)">
                            <span><i class="bi bi-braces"></i> Computed CSS</span>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="prop-body open">
                            <pre id="computed-css" style="font-size:10px;color:var(--text-dim);white-space:pre-wrap;word-break:break-all;margin:0;max-height:400px;overflow-y:auto;font-family:'JetBrains Mono',monospace"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /fb-body -->
</div><!-- /fb-root -->

<!-- BLOCK PICKER MODAL -->
<div class="modal-bg" id="block-modal">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Insert Element</h3>
            <button class="modal-close" onclick="closeBlockModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="block-grid">
            <div class="block-card" onclick="doInsert('h2')"><i class="bi bi-type-h2"></i><span>Heading</span></div>
            <div class="block-card" onclick="doInsert('h3')"><i class="bi bi-type-h3"></i><span>Subheading</span></div>
            <div class="block-card" onclick="doInsert('p')"><i class="bi bi-text-paragraph"></i><span>Paragraph</span></div>
            <div class="block-card" onclick="doInsert('button')"><i class="bi bi-ui-radios"></i><span>Button</span></div>
            <div class="block-card" onclick="doInsert('img')"><i class="bi bi-image"></i><span>Image</span></div>
            <div class="block-card" onclick="doInsert('div')"><i class="bi bi-bounding-box-circles"></i><span>Container</span></div>
            <div class="block-card" onclick="doInsert('a')"><i class="bi bi-link-45deg"></i><span>Link</span></div>
            <div class="block-card" onclick="doInsert('ul')"><i class="bi bi-list-ul"></i><span>List</span></div>
            <div class="block-card" onclick="showCustomHTML()"><i class="bi bi-code-slash"></i><span>Custom HTML</span></div>
        </div>
        <div class="custom-html-area" id="custom-area">
            <textarea id="custom-html" placeholder="<div>Your HTML here...</div>"></textarea>
            <button class="tb-btn-primary tb-btn" style="width:100%;justify-content:center;margin-top:8px" onclick="doInsert('custom')">
                <i class="bi bi-check-lg"></i> Insert
            </button>
        </div>
    </div>
</div>

<!-- CONFIG MODAL -->
<div class="modal-bg" id="cfg-modal">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-head">
            <h3>Site Settings</h3>
            <button class="modal-close" onclick="closeCfg()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="cfg-body">
            <?php foreach (['Branding' => ['LOGO', 'ICON'], 'Content' => ['TITLE', 'TEXT']] as $sName => $keys): ?>
                <div class="cfg-section">
                    <div class="cfg-section-title"><?= htmlspecialchars($sName) ?></div>
                    <?php foreach ($config as $key => $val):
                        $match = false;
                        foreach ($keys as $k) if (strpos($key, $k) !== false) $match = true;
                        if (!$match) continue; ?>
                        <div class="cfg-field">
                            <label><?= htmlspecialchars($key) ?></label>
                            <textarea data-conf-key="CONF_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($val) ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-foot">
            <button class="tb-btn" onclick="closeCfg()">Cancel</button>
            <button class="tb-btn-primary tb-btn" onclick="closeCfg()"><i class="bi bi-check-lg"></i> Done</button>
        </div>
    </div>
</div>

<!-- SHORTCUTS PANEL -->
<div class="shortcuts-panel" id="shortcuts-panel">
    <h4>Keyboard Shortcuts</h4>
    <div class="sc-row"><span>Save</span><span class="sc-key">Ctrl+S</span></div>
    <div class="sc-row"><span>Undo</span><span class="sc-key">Ctrl+Z</span></div>
    <div class="sc-row"><span>Redo</span><span class="sc-key">Ctrl+Shift+Z</span></div>
    <div class="sc-row"><span>Delete element</span><span class="sc-key">Delete</span></div>
    <div class="sc-row"><span>Duplicate</span><span class="sc-key">Ctrl+D</span></div>
    <div class="sc-row"><span>Deselect</span><span class="sc-key">Escape</span></div>
    <div class="sc-row"><span>Shortcuts</span><span class="sc-key">Ctrl+/</span></div>
</div>

<!-- TOASTS -->
<div id="toast-wrap"></div>

<!-- HIDDEN SAVE FORM -->
<form id="save-form" method="POST" action="" style="display:none">
    <input type="hidden" name="action" value="save_all">
    <input type="hidden" name="site_rendered_html" id="html-payload">
    <div id="conf-inputs"></div>
</form>

<!-- SITE CONTENT (loaded into iframe via JS) -->
<textarea id="site-source" style="display:none"><?php echo htmlspecialchars($current_code); ?></textarea>

<!-- ══════════════════════════════════════════
     MAIN SCRIPT
     ══════════════════════════════════════════ -->
<script>
(function() {
    'use strict';

    /* ─── GLOBALS ─── */
    const iframe     = document.getElementById('editor-iframe');
    const leftPanel  = document.getElementById('left-panel');
    const rightPanel = document.getElementById('right-panel');
    let unsaved      = false;
    let editMode     = false;

    /* ─── UNDO / REDO ─── */
    const history    = [];
    let historyIdx   = -1;
    const MAX_HISTORY = 50;

    function pushHistory() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const snap = doc.documentElement.outerHTML;
            // Remove future states if we undid
            if (historyIdx < history.length - 1) history.splice(historyIdx + 1);
            history.push(snap);
            if (history.length > MAX_HISTORY) history.shift();
            historyIdx = history.length - 1;
            updateUndoButtons();
        } catch(e) {}
    }

    function updateUndoButtons() {
        document.getElementById('btn-undo').disabled = historyIdx <= 0;
        document.getElementById('btn-redo').disabled = historyIdx >= history.length - 1;
    }

    window.undo = function() {
        if (historyIdx <= 0) return;
        historyIdx--;
        restoreHistory();
    };

    window.redo = function() {
        if (historyIdx >= history.length - 1) return;
        historyIdx++;
        restoreHistory();
    };

    function restoreHistory() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write(history[historyIdx]);
            doc.close();
            setTimeout(() => injectEngine(), 100);
            updateUndoButtons();
            markUnsaved();
            showEmpty();
            refreshLayers();
        } catch(e) {}
    }

    /* ─── TOAST ─── */
    window.toast = function(msg, type = 'inf', ms = 2400) {
        const icons = { ok: 'bi-check-circle-fill', err: 'bi-x-circle-fill', inf: 'bi-info-circle-fill' };
        const wrap = document.getElementById('toast-wrap');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.innerHTML = `<i class="bi ${icons[type] || icons.inf}"></i>${msg}`;
        wrap.appendChild(t);
        setTimeout(() => {
            t.style.transition = '.25s';
            t.style.opacity = '0';
            t.style.transform = 'translateY(6px)';
            setTimeout(() => t.remove(), 280);
        }, ms);
    };

    /* ─── VIEWPORT ─── */
    window.setVP = function(v) {
        const wrap = document.getElementById('canvas-wrap');
        wrap.className = `canvas-wrap vp-${v}`;
        document.querySelectorAll('.vp-btn').forEach(b => b.classList.remove('on'));
        document.getElementById(`vp-${v[0]}`).classList.add('on');
    };

    /* ─── PANELS ─── */
    window.toggleLeft = function() {
        leftPanel.classList.toggle('collapsed');
        document.getElementById('btn-toggle-layers').classList.toggle('active', !leftPanel.classList.contains('collapsed'));
    };

    window.switchTab = function(tab) {
        document.querySelectorAll('.design-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
        document.getElementById('design-active').style.display = tab === 'design' ? '' : 'none';
        document.getElementById('inspect-tab').style.display = tab === 'inspect' ? '' : 'none';
        document.getElementById('design-empty').style.display = 'none';
    };

    window.toggleProp = function(header) {
        header.classList.toggle('open');
        const body = header.nextElementSibling;
        body.classList.toggle('open');
    };

    /* ─── COLOUR SYNC ─── */
    window.syncColor = function(fieldId, pickId) {
        document.getElementById(fieldId).value = document.getElementById(pickId).value;
    };
    window.syncPicker = function(fieldId, pickId) {
        const v = document.getElementById(fieldId).value;
        if (/^#[0-9a-f]{6}$/i.test(v)) document.getElementById(pickId).value = v;
    };
    function rgb2hex(rgb) {
        if (!rgb || rgb.startsWith('#')) return rgb || '#000000';
        const m = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!m) return '#000000';
        return '#' + [m[1], m[2], m[3]].map(x => parseInt(x).toString(16).padStart(2, '0')).join('');
    }

    /* ─── RICH TEXT ─── */
    window.rtCmd = function(cmd) {
        toIframe({ type: 'RT_CMD', cmd });
    };

    /* ─── LAYERS PANEL ─── */
    let layerCounter = 0;

    window.refreshLayers = function() {
        try {
            
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const body = doc.body;
            if (!body) return;
            const tree = document.getElementById('layers-tree');
            tree.innerHTML = '';
            layerCounter = 0;
            buildLayerTree(body, tree, 0);
        } catch(e) {}
    };

    function buildLayerTree(el, container, depth) {
        if (!el || el.nodeType !== 1) return;
        // skip builder-injected elements
        if (el.id && /^vc-/.test(el.id)) return;
        if (el.tagName === 'SCRIPT' || el.tagName === 'STYLE' || el.tagName === 'LINK' || el.tagName === 'META') return;

        const id = 'layer-' + (layerCounter++);
        const children = Array.from(el.children).filter(c =>
            c.nodeType === 1 && !/^vc-/.test(c.id) && !['SCRIPT','STYLE','LINK','META'].includes(c.tagName)
        );
        const hasChildren = children.length > 0;

        const item = document.createElement('div');
        item.className = 'layer-item';
        item.style.setProperty('--depth', depth);
        item.dataset.layerId = id;

        // Toggle button
        const toggle = document.createElement('button');
        toggle.className = 'layer-toggle' + (hasChildren ? '' : ' empty');
        toggle.innerHTML = '<i class="bi bi-chevron-right"></i>';
        if (hasChildren) {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                toggle.classList.toggle('open');
                const childContainer = document.getElementById(id + '-children');
                if (childContainer) childContainer.style.display = childContainer.style.display === 'none' ? '' : 'none';
            });
        }

        // Icon
        const icon = document.createElement('span');
        icon.className = 'layer-icon';
        const iconMap = {
            'IMG': 'bi-image', 'A': 'bi-link-45deg', 'BUTTON': 'bi-ui-radios',
            'H1': 'bi-type-h1', 'H2': 'bi-type-h2', 'H3': 'bi-type-h3',
            'P': 'bi-text-paragraph', 'UL': 'bi-list-ul', 'OL': 'bi-list-ol',
            'INPUT': 'bi-input-cursor', 'FORM': 'bi-ui-checks',
            'NAV': 'bi-compass', 'HEADER': 'bi-layout-text-window',
            'FOOTER': 'bi-layout-text-sidebar-reverse', 'SECTION': 'bi-layout-split',
            'DIV': 'bi-bounding-box', 'SPAN': 'bi-fonts',
        };
        icon.innerHTML = `<i class="bi ${iconMap[el.tagName] || 'bi-code'}"></i>`;

        // Name
        const name = document.createElement('span');
        name.className = 'layer-name';
        const cls = (el.className || '').replace(/\bvc-[\w-]+\b/g, '').trim().split(' ')[0];
        const elId = el.id && !/^vc-/.test(el.id) ? '#' + el.id : '';
        name.textContent = el.tagName.toLowerCase() + (elId || (cls ? '.' + cls : ''));

        // Tag badge
        const tag = document.createElement('span');
        tag.className = 'layer-tag';
        tag.textContent = el.tagName;

        item.appendChild(toggle);
        item.appendChild(icon);
        item.appendChild(name);
        item.appendChild(tag);

        // Click to select
        item.addEventListener('click', () => {
            toIframe({ type: 'SELECT_BY_PATH', path: getElementPath(el) });
            document.querySelectorAll('.layer-item.selected').forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');
        });

        container.appendChild(item);

        if (hasChildren) {
            const childContainer = document.createElement('div');
            childContainer.id = id + '-children';
            // Collapse deep levels by default
            if (depth > 2) childContainer.style.display = 'none';
            else toggle.classList.add('open');
            children.forEach(child => buildLayerTree(child, childContainer, depth + 1));
            container.appendChild(childContainer);
        }
    }

    function getElementPath(el) {
        const path = [];
        let node = el;
        while (node && node.parentNode) {
            const parent = node.parentNode;
            if (parent.nodeType !== 1) break;
            const idx = Array.from(parent.children).indexOf(node);
            path.unshift(idx);
            node = parent;
            if (node.tagName === 'HTML') break;
        }
        return path;
    }

    window.filterLayers = function(query) {
        const q = query.toLowerCase();
        document.querySelectorAll('.layer-item').forEach(item => {
            const name = item.querySelector('.layer-name');
            if (!name) return;
            const match = !q || name.textContent.toLowerCase().includes(q);
            item.style.display = match ? '' : 'none';
        });
    };

    /* ─── ELEMENT INSPECTOR ─── */
    function showEmpty() {
        document.getElementById('design-empty').style.display = '';
        document.getElementById('design-active').style.display = 'none';
        document.querySelectorAll('.layer-item.selected').forEach(i => i.classList.remove('selected'));
    }

    function populate(d) {
        document.getElementById('design-empty').style.display = 'none';
        document.getElementById('design-active').style.display = '';

        const isImg = d.tag === 'IMG';
        const isA   = d.tag === 'A';

        document.getElementById('el-tag').textContent = d.tag;

        // Section visibility
        document.getElementById('sec-typo').style.display = isImg ? 'none' : '';
        document.getElementById('sec-img').style.display  = isImg ? '' : 'none';
        document.getElementById('sec-link').style.display  = isA ? '' : 'none';

        // Fields
        if (!isImg) {
            document.getElementById('ip-fontsize').value   = d.fontSize || '';
            document.getElementById('ip-fontweight').value  = d.fontWeight || '';
        }
        if (isImg) {
            document.getElementById('ip-src').value   = d.src || '';
            document.getElementById('ip-img-w').value = d.imgW || '';
            document.getElementById('ip-img-h').value = d.imgH || '';
            document.getElementById('ip-alt').value   = d.alt || '';
        }
        if (isA) {
            document.getElementById('ip-href').value   = d.href || '';
            document.getElementById('ip-blank').checked = d.blank || false;
        }

        document.getElementById('ip-color').value   = d.color || '';
        document.getElementById('ip-bg').value       = d.background || '';
        document.getElementById('ip-classes').value   = d.classes || '';

        try { document.getElementById('cp-color').value = rgb2hex(d.color); } catch(e) {}
        try { document.getElementById('cp-bg').value    = rgb2hex(d.background); } catch(e) {}

        // Spacing
        parseSpacing(d.padding || '', 'p');
        parseSpacing(d.margin || '', 'm');

        // Computed CSS for inspect tab
        if (d.computedCSS) {
            document.getElementById('computed-css').textContent = d.computedCSS;
        }

        // Highlight in layers
        highlightLayer(d.tag, d.classes);
    }

    function parseSpacing(val, prefix) {
        const parts = val.replace(/px/g, '').trim().split(/\s+/);
        const ids = ['t', 'r', 'b', 'l'];
        let values = [0, 0, 0, 0];
        if (parts.length === 1) values = [parts[0], parts[0], parts[0], parts[0]];
        else if (parts.length === 2) values = [parts[0], parts[1], parts[0], parts[1]];
        else if (parts.length === 3) values = [parts[0], parts[1], parts[2], parts[1]];
        else if (parts.length >= 4) values = [parts[0], parts[1], parts[2], parts[3]];
        ids.forEach((dir, i) => {
            const el = document.getElementById(`sp-${prefix}${dir}`);
            if (el) el.value = values[i] || '';
        });
    }

    function getSpacing(prefix) {
        const t = document.getElementById(`sp-${prefix}t`).value || '0';
        const r = document.getElementById(`sp-${prefix}r`).value || '0';
        const b = document.getElementById(`sp-${prefix}b`).value || '0';
        const l = document.getElementById(`sp-${prefix}l`).value || '0';
        const addPx = v => /^\d+$/.test(v) ? v + 'px' : v;
        return `${addPx(t)} ${addPx(r)} ${addPx(b)} ${addPx(l)}`;
    }

    function highlightLayer(tag, classes) {
        const cls = (classes || '').split(' ')[0];
        const search = tag.toLowerCase() + (cls ? '.' + cls : '');
        document.querySelectorAll('.layer-item').forEach(item => {
            item.classList.remove('selected');
            const name = item.querySelector('.layer-name');
            if (name && name.textContent === search) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });
    }

    /* ─── APPLY CHANGES ─── */
    window.applyChanges = function() {
        pushHistory();
        const d = {
            type: 'APPLY',
            fontSize:   document.getElementById('ip-fontsize').value,
            fontWeight: document.getElementById('ip-fontweight').value,
            color:      document.getElementById('ip-color').value,
            background: document.getElementById('ip-bg').value,
            padding:    getSpacing('p'),
            margin:     getSpacing('m'),
            classes:    document.getElementById('ip-classes').value,
            src:        document.getElementById('ip-src').value,
            imgW:       document.getElementById('ip-img-w').value,
            imgH:       document.getElementById('ip-img-h').value,
            alt:        document.getElementById('ip-alt').value,
            href:       document.getElementById('ip-href').value,
            blank:      document.getElementById('ip-blank').checked,
        };
        toIframe(d);
        markUnsaved();
        toast('Changes applied', 'ok', 1500);
    };

    window.onImgUpload = function(e) {
        const f = e.target.files[0]; if (!f) return;
        const r = new FileReader();
        r.onload = ev => {
            document.getElementById('ip-src').value = ev.target.result;
            pushHistory();
            toIframe({ type: 'APPLY', src: ev.target.result });
            markUnsaved();
            toast('Image uploaded', 'ok');
        };
        r.readAsDataURL(f);
    };

    /* ─── DELETE / DUPLICATE ─── */
    window.delEl = function() {
        pushHistory();
        toIframe({ type: 'DELETE' });
        showEmpty();
        markUnsaved();
        refreshLayers();
        toast('Element deleted', 'inf');
    };

    window.dupEl = function() {
        pushHistory();
        toIframe({ type: 'DUPLICATE' });
        markUnsaved();
        refreshLayers();
        toast('Element duplicated', 'ok');
    };

    /* ─── BLOCK PICKER ─── */
    let insertPos = 'after';

    window.openBlockPicker = function(pos) {
        insertPos = pos || 'after';
        document.getElementById('custom-area').style.display = 'none';
        document.getElementById('block-modal').classList.add('open');
    };

    window.closeBlockModal = function() {
        document.getElementById('block-modal').classList.remove('open');
    };

    window.showCustomHTML = function() {
        document.getElementById('custom-area').style.display = '';
    };

    const BLOCK_TPLS = {
        h2:     '<h2 style="font-size:1.75rem;font-weight:700;margin-bottom:12px;">New Heading</h2>',
        h3:     '<h3 style="font-size:1.3rem;font-weight:600;margin-bottom:10px;">Sub-heading</h3>',
        p:      '<p style="line-height:1.7;margin-bottom:14px;">New paragraph text. Click to edit.</p>',
        button: '<button style="background:#0d99ff;color:#fff;padding:10px 24px;border-radius:8px;border:none;font-weight:600;cursor:pointer;">Button</button>',
        img:    '<img src="https://placehold.co/800x400/1e1e1e/666?text=Image" alt="Placeholder" style="width:100%;border-radius:8px;display:block;margin-bottom:14px;">',
        div:    '<div style="padding:24px;background:#f5f5f5;border-radius:10px;margin-bottom:14px;"><p>New container</p></div>',
        a:      '<a href="#" style="color:#0d99ff;text-decoration:underline;">New link</a>',
        ul:     '<ul style="margin-bottom:14px;padding-left:20px;"><li>Item one</li><li>Item two</li><li>Item three</li></ul>',
    };

    window.doInsert = function(type) {
        const html = type === 'custom'
            ? document.getElementById('custom-html').value.trim()
            : BLOCK_TPLS[type] || '';
        if (!html) { toast('Enter HTML first', 'err'); return; }
        closeBlockModal();
        pushHistory();
        toIframe({ type: 'INSERT', html, position: insertPos });
        markUnsaved();
        refreshLayers();
        toast(`${type} inserted`, 'ok');
    };

    /* ─── CONFIG MODAL ─── */
    window.openCfg = function() { document.getElementById('cfg-modal').classList.add('open'); };
    window.closeCfg = function() { document.getElementById('cfg-modal').classList.remove('open'); };

    /* ─── MESSAGING ─── */
    function toIframe(msg) { try { iframe.contentWindow.postMessage(msg, '*'); } catch(e) {} }
    window.toIframe = toIframe;

    window.addEventListener('message', e => {
        const d = e.data; if (!d || !d.type) return;
        if (d.type === 'SELECTED') populate(d);
        if (d.type === 'DESELECTED') showEmpty();
        if (d.type === 'CHANGED') markUnsaved();
        if (d.type === 'DBLCLICK_EDIT') {
            editMode = true;
            toast('Editing text — click away when done', 'inf', 1800);
        }
        if (d.type === 'LAYERS_READY') refreshLayers();
    });

    /* ─── UNSAVED STATE ─── */
    function markUnsaved() { unsaved = true; }
    window.markUnsaved = markUnsaved;

    /* ─── SAVE ─── */
    window.saveViaForm = function() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const form = document.getElementById('save-form');

            // Config inputs
            const ci = document.getElementById('conf-inputs');
            ci.innerHTML = '';
            document.querySelectorAll('[data-conf-key]').forEach(a => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = a.getAttribute('data-conf-key'); inp.value = a.value;
                ci.appendChild(inp);
            });

            // Clean the HTML
            const clean = doc.documentElement.cloneNode(true);
            ['#vc-style', '#vc-script', '#vc-tip', '#vc-resize', '#vc-bi'].forEach(s => clean.querySelector(s)?.remove());
            const appRoot = clean.querySelector('#app-root') || clean.querySelector('.js-grid');
            if (appRoot) appRoot.innerHTML = '';
            clean.querySelectorAll('.vc-sel,.vc-h').forEach(e => { e.classList.remove('vc-sel', 'vc-h'); });
            clean.querySelectorAll('[contenteditable]').forEach(e => e.removeAttribute('contenteditable'));
            clean.querySelectorAll('[data-vc-done]').forEach(e => delete e.dataset.vcDone);
            clean.querySelectorAll('[data-source-tpl]').forEach(e => { e.removeAttribute('data-source-tpl'); e.removeAttribute('data-tpl-idx'); });

            const html = clean.outerHTML;
            document.getElementById('html-payload').value = html;
            // Update source textarea so undo/reload uses latest saved version
            const source = document.getElementById('site-source');
            if (source) source.value = html;
            form.submit();
            unsaved = false;
            toast('Page saved successfully', 'ok');
        } catch (err) {
            toast('Save failed — check console', 'err');
            console.error('[Builder] save error', err);
        }
    };

    /* ─── KEYBOARD SHORTCUTS ─── */
    document.addEventListener('keydown', e => {
        const ctrl = e.ctrlKey || e.metaKey;
        if (ctrl && e.key === 's') { e.preventDefault(); saveViaForm(); }
        if (ctrl && e.key === 'z' && !e.shiftKey) { e.preventDefault(); undo(); }
        if (ctrl && e.key === 'z' && e.shiftKey) { e.preventDefault(); redo(); }
        if (ctrl && e.key === 'Z') { e.preventDefault(); redo(); }
        if (ctrl && e.key === 'd') { e.preventDefault(); dupEl(); }
        if (ctrl && e.key === '/') { e.preventDefault(); document.getElementById('shortcuts-panel').classList.toggle('show'); }
        if (e.key === 'Escape') {
            toIframe({ type: 'DESELECT' });
            showEmpty();
            // Close modals
            document.querySelectorAll('.modal-bg').forEach(m => m.classList.remove('open'));
            document.getElementById('shortcuts-panel').classList.remove('show');
        }
        if ((e.key === 'Delete' || e.key === 'Backspace') && e.target === document.body) {
            if (!editMode) delEl();
        }
    });

    window.addEventListener('beforeunload', e => {
        if (unsaved) { e.preventDefault(); return (e.returnValue = ''); }
    });

    /* ─── IFRAME INIT ─── */
    function loadSiteContent() {
        const source = document.getElementById('site-source');
        const content = source ? source.value : '';
        if (!content) {
            toast('No site content found — create your site first', 'err', 4000);
            return;
        }
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write(content);
            doc.close();
        } catch(e) {
            console.error('[Builder] Failed to write content to iframe', e);
        }
    }

    iframe.addEventListener('load', () => {
        // Only inject engine if iframe has real content (not about:blank with empty body)
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            if (doc.body && doc.body.innerHTML.trim().length > 0) {
                injectEngine();
                setTimeout(() => {
                    pushHistory();
                    refreshLayers();
                }, 300);
            }
        } catch(e) {}
    });

    // Initial load: write site content into iframe
    loadSiteContent();

    function injectEngine() {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;

            // BI icons
            if (!doc.getElementById('vc-bi')) {
                const l = doc.createElement('link');
                l.id = 'vc-bi'; l.rel = 'stylesheet';
                l.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css';
                doc.head.appendChild(l);
            }

            // Styles
            if (!doc.getElementById('vc-style')) {
                const s = doc.createElement('style');
                s.id = 'vc-style';
                s.textContent = `
.vc-h{outline:2px dashed rgba(13,153,255,0.4)!important;outline-offset:1px;cursor:pointer!important}
.vc-sel{outline:2px solid #0d99ff!important;outline-offset:1px;position:relative}
#vc-tip{
  position:fixed;z-index:2147483646;pointer-events:none;
  background:#0d99ff;color:#fff;font:600 10px/1 'Inter',system-ui,sans-serif;
  text-transform:uppercase;letter-spacing:.4px;
  padding:3px 7px;border-radius:3px 3px 3px 0;
  box-shadow:0 2px 8px rgba(13,153,255,0.35);
  opacity:0;transition:opacity .12s;white-space:nowrap;
}
#vc-tip.show{opacity:1}
#vc-resize{position:fixed;z-index:2147483647;pointer-events:none;display:none}
#vc-resize .rh{
  position:absolute;width:8px;height:8px;
  background:#fff;border:1.5px solid #0d99ff;border-radius:1px;
  pointer-events:all;
}
#vc-resize .rh.tl{top:-4px;left:-4px;cursor:nw-resize}
#vc-resize .rh.tr{top:-4px;right:-4px;cursor:ne-resize}
#vc-resize .rh.bl{bottom:-4px;left:-4px;cursor:sw-resize}
#vc-resize .rh.br{bottom:-4px;right:-4px;cursor:se-resize}
#vc-resize .rm{position:absolute;right:-4px;top:50%;transform:translateY(-50%);width:8px;height:8px;background:#fff;border:1.5px solid #0d99ff;border-radius:1px;pointer-events:all;cursor:e-resize}
#vc-resize .bm{position:absolute;bottom:-4px;left:50%;transform:translateX(-50%);width:8px;height:8px;background:#fff;border:1.5px solid #0d99ff;border-radius:1px;pointer-events:all;cursor:s-resize}
[contenteditable]{outline:2px solid #0d99ff!important;outline-offset:2px;background:rgba(13,153,255,0.03)!important}
[contenteditable]:focus{outline-color:#38b6ff!important}
`;
                doc.head.appendChild(s);
            }

            // Tip
            if (!doc.getElementById('vc-tip')) {
                const t = doc.createElement('div'); t.id = 'vc-tip'; doc.body.appendChild(t);
            }

            // Resize overlay
            if (!doc.getElementById('vc-resize')) {
                const r = doc.createElement('div'); r.id = 'vc-resize';
                r.innerHTML = '<div class="rh tl" data-dir="tl"></div><div class="rh tr" data-dir="tr"></div><div class="rh bl" data-dir="bl"></div><div class="rh br" data-dir="br"></div><div class="rm" data-dir="mr"></div><div class="bm" data-dir="bm"></div>';
                doc.body.appendChild(r);
            }

            // Engine script
            if (doc.getElementById('vc-script')) doc.getElementById('vc-script').remove();
            const sc = doc.createElement('script'); sc.id = 'vc-script';
            sc.textContent = `
(function(){
  var curEl=null, ancestorChain=[], isEditing=false;
  var tip=document.getElementById('vc-tip');
  var resizeBox=document.getElementById('vc-resize');
  var resizing=false, resDir='', resStartX=0, resStartY=0, resStartW=0, resStartH=0;

  var TAGS='h1,h2,h3,h4,h5,h6,p,span,a,img,button,label,li,td,th,blockquote,figure,figcaption,div,section,article,header,footer,nav,aside,main,form,input,textarea,select,ul,ol,table,thead,tbody,tr';

  function sendUp(obj){ window.parent.postMessage(obj,'*'); }

  function buildAncestors(el){
    var chain=[]; var n=el;
    while(n && n!==document.body && n!==document.documentElement){
      if(n.nodeType===1) chain.push(n);
      n=n.parentNode;
    }
    return chain;
  }

  function getComputedInfo(el){
    var cs=window.getComputedStyle(el);
    var props=['display','position','width','height','font-family','font-size','font-weight','line-height','color','background-color','border','border-radius','padding','margin','overflow','opacity','z-index'];
    var lines=[];
    props.forEach(function(p){ lines.push(p+': '+cs.getPropertyValue(p)+';'); });
    return lines.join('\\n');
  }

  function elData(el){
    var cs=window.getComputedStyle(el);
    var isImg=el.tagName==='IMG', isA=el.tagName==='A';
    return {
      type:'SELECTED', tag:el.tagName,
      classes:(el.className||'').replace(/\\bvc-[\\w-]+\\b/g,'').trim(),
      src:isImg?(el.src||''):'',
      imgW:isImg?(el.style.width||el.getAttribute('width')||''):'',
      imgH:isImg?(el.style.height||el.getAttribute('height')||''):'',
      alt:isImg?(el.alt||''):'',
      href:isA?(el.getAttribute('href')||''):'',
      blank:isA?(el.target==='_blank'):false,
      fontSize:el.style.fontSize||cs.fontSize||'',
      fontWeight:el.style.fontWeight||cs.fontWeight||'',
      color:el.style.color||cs.color||'',
      background:el.style.backgroundColor||cs.backgroundColor||'',
      padding:el.style.padding||'',
      margin:el.style.margin||'',
      computedCSS:getComputedInfo(el)
    };
  }

  function positionResize(el){
    if(!el){resizeBox.style.display='none';return;}
    var r=el.getBoundingClientRect();
    resizeBox.style.display='block';
    resizeBox.style.left=r.left+'px';
    resizeBox.style.top=r.top+'px';
    resizeBox.style.width=r.width+'px';
    resizeBox.style.height=r.height+'px';
  }

  function selectEl(el){
    if(!el) return;
    deselect(false);
    curEl=el;
    el.classList.add('vc-sel');
    positionResize(el);
    sendUp(elData(el));
  }

  function deselect(notify){
    if(curEl){
      curEl.classList.remove('vc-sel','vc-h');
      if(isEditing){
        curEl.contentEditable='false';
        curEl.removeAttribute('contenteditable');
        syncTpl(curEl);
        isEditing=false;
      }
    }
    curEl=null;
    resizeBox.style.display='none';
    if(notify!==false) sendUp({type:'DESELECTED'});
  }

  function setup(el){
    if(!el||el===tip||el===resizeBox||el.closest('#vc-resize')) return;
    if(el.id&&/^vc-/.test(el.id)) return;
    if(el.dataset.vcDone) return;
    el.dataset.vcDone='1';

    el.addEventListener('mouseover',function(e){
      e.stopPropagation();
      if(curEl===el) return;
      el.classList.add('vc-h');
      if(tip){tip.textContent=el.tagName;tip.classList.add('show');}
    },true);

    el.addEventListener('mousemove',function(e){
      if(!tip) return;
      tip.style.left=(e.clientX+12)+'px';
      tip.style.top=(e.clientY-26)+'px';
    },true);

    el.addEventListener('mouseout',function(){
      el.classList.remove('vc-h');
      if(tip) tip.classList.remove('show');
    },true);

    el.addEventListener('click',function(e){
      if(el.tagName==='A'||el.tagName==='BUTTON'||el.tagName==='INPUT'||el.tagName==='SELECT') e.preventDefault();
      if(el===curEl&&isEditing) return;
      if(el===curEl){positionResize(el);return;}
      e.stopPropagation();
      selectEl(el);
    },true);

    // Double-click for inline editing
    el.addEventListener('dblclick',function(e){
      if(el.tagName==='IMG') return;
      e.preventDefault();
      e.stopPropagation();
      if(!curEl||curEl!==el) selectEl(el);
      isEditing=true;
      el.contentEditable='true';
      el.focus();
      sendUp({type:'DBLCLICK_EDIT'});
    },true);

    el.addEventListener('input',function(){
      syncTpl(el);
      sendUp({type:'CHANGED'});
    });
  }

  function syncTpl(liveEl,fullRoot){
    var root=liveEl.closest('[data-source-tpl]');
    if(!root) return;
    var tplId=root.getAttribute('data-source-tpl');
    var tplIdx=parseInt(root.getAttribute('data-tpl-idx')||'0');
    var tpl=document.getElementById(tplId);
    if(!tpl||!tpl.content) return;
    var tplRoot=tpl.content.children[tplIdx];
    if(!tplRoot) return;
    if(fullRoot){
      var clone=root.cloneNode(true);
      clone.querySelectorAll('[contenteditable]').forEach(function(e){e.removeAttribute('contenteditable');});
      clone.querySelectorAll('[data-vc-done]').forEach(function(e){delete e.dataset.vcDone;});
      clone.querySelectorAll('[data-source-tpl]').forEach(function(e){e.removeAttribute('data-source-tpl');e.removeAttribute('data-tpl-idx');});
      tplRoot.innerHTML=clone.innerHTML;
    } else {
      var path=[];var cur=liveEl;
      while(cur!==root&&cur){path.unshift(Array.from(cur.parentNode.children).indexOf(cur));cur=cur.parentNode;}
      var t=tplRoot;
      for(var i=0;i<path.length;i++){t=t.children[path[i]];if(!t)return;}
      if(liveEl.tagName==='IMG') t.src=liveEl.src;
      else t.innerHTML=liveEl.innerHTML;
    }
  }

  function initAll(){
    document.querySelectorAll('template').forEach(function(tpl){
      Array.from(tpl.content.children).forEach(function(child,idx){
        if(!child.hasAttribute('data-source-tpl')){
          child.setAttribute('data-source-tpl',tpl.id);
          child.setAttribute('data-tpl-idx',idx);
        }
      });
    });
    document.querySelectorAll(TAGS).forEach(setup);
    sendUp({type:'LAYERS_READY'});
  }
  initAll();

  new MutationObserver(function(ms){
    ms.forEach(function(m){
      m.addedNodes.forEach(function(n){
        if(n.nodeType!==1) return;
        if(n.matches&&n.matches(TAGS)) setup(n);
        n.querySelectorAll&&n.querySelectorAll(TAGS).forEach(setup);
      });
    });
  }).observe(document.body,{childList:true,subtree:true});

  document.addEventListener('click',function(e){
    if(!curEl) return;
    if(curEl.contains(e.target)) return;
    if(resizeBox.contains(e.target)) return;
    if(isEditing){curEl.contentEditable='false';curEl.removeAttribute('contenteditable');syncTpl(curEl);isEditing=false;}
    deselect(true);
  });

  // Resize
  resizeBox.querySelectorAll('.rh,.rm,.bm').forEach(function(h){
    h.addEventListener('mousedown',function(e){
      if(!curEl) return;
      resizing=true;resDir=h.dataset.dir;
      resStartX=e.clientX;resStartY=e.clientY;
      var r=curEl.getBoundingClientRect();
      resStartW=r.width;resStartH=r.height;
      e.stopPropagation();e.preventDefault();
    });
  });

  document.addEventListener('mousemove',function(e){
    if(!resizing||!curEl) return;
    var dx=e.clientX-resStartX,dy=e.clientY-resStartY;
    if(resDir==='br'||resDir==='tr'||resDir==='mr') curEl.style.width=Math.max(20,resStartW+dx)+'px';
    if(resDir==='br'||resDir==='bl'||resDir==='bm') curEl.style.height=Math.max(20,resStartH+dy)+'px';
    if(resDir==='tl'){curEl.style.width=Math.max(20,resStartW-dx)+'px';curEl.style.height=Math.max(20,resStartH-dy)+'px';}
    if(resDir==='bl'){curEl.style.width=Math.max(20,resStartW-dx)+'px';curEl.style.height=Math.max(20,resStartH+dy)+'px';}
    positionResize(curEl);
  });

  document.addEventListener('mouseup',function(){
    if(resizing&&curEl){
      resizing=false;
      syncTpl(curEl);
      sendUp({type:'CHANGED'});
      sendUp(elData(curEl));
    }
    resizing=false;
  });

  document.addEventListener('scroll',function(){if(curEl) positionResize(curEl);},true);

  // Message handler
  window.addEventListener('message',function(e){
    var d=e.data;if(!d||!d.type) return;

    if(d.type==='DESELECT') deselect(true);

    if(d.type==='SELECT_BY_PATH'){
      var target=document.documentElement;
      for(var i=0;i<d.path.length;i++){
        if(!target.children[d.path[i]]) break;
        target=target.children[d.path[i]];
      }
      if(target&&target!==document.documentElement) selectEl(target);
    }

    if(d.type==='SET_EDIT'){
      if(!curEl||curEl.tagName==='IMG') return;
      isEditing=d.editMode;
      if(isEditing){curEl.contentEditable='true';curEl.focus();}
      else{curEl.contentEditable='false';curEl.removeAttribute('contenteditable');syncTpl(curEl);}
    }

    if(d.type==='RT_CMD'){
      if(!curEl||!isEditing) return;
      if(!isEditing){isEditing=true;curEl.contentEditable='true';curEl.focus();}
      document.execCommand(d.cmd,false,d.val||null);
      syncTpl(curEl);sendUp({type:'CHANGED'});
    }

    if(d.type==='APPLY'){
      if(!curEl) return;
      var s=curEl.style,isImg=curEl.tagName==='IMG',isA=curEl.tagName==='A';
      if(isImg){
        if(d.src) curEl.src=d.src;
        if(d.imgW) s.width=d.imgW;
        if(d.imgH) s.height=d.imgH;
        if(d.alt!==undefined) curEl.alt=d.alt;
        positionResize(curEl);
      }
      if(isA){
        if(d.href!==undefined) curEl.setAttribute('href',d.href);
        curEl.target=d.blank?'_blank':'';
      }
      if(!isImg){
        if(d.fontSize) s.fontSize=d.fontSize;
        if(d.fontWeight) s.fontWeight=d.fontWeight;
      }
      if(d.color) s.color=d.color;
      if(d.background) s.backgroundColor=d.background;
      if(d.padding) s.padding=d.padding;
      if(d.margin) s.margin=d.margin;
      if(d.classes!==undefined){
        var keep=Array.from(curEl.classList).filter(function(c){return c.startsWith('vc-');});
        curEl.className=(d.classes+' '+keep.join(' ')).trim();
      }
      syncTpl(curEl,isImg);
      sendUp({type:'CHANGED'});
    }

    if(d.type==='DELETE'){
      if(!curEl) return;
      var root=curEl.closest('[data-source-tpl]');
      curEl.remove();curEl=null;
      resizeBox.style.display='none';
      if(root) syncTpl(root,true);
      sendUp({type:'DESELECTED'});
    }

    if(d.type==='DUPLICATE'){
      if(!curEl) return;
      var clone=curEl.cloneNode(true);
      clone.removeAttribute('data-vc-done');
      clone.classList.remove('vc-sel','vc-h');
      curEl.after(clone);
      var root=curEl.closest('[data-source-tpl]');
      if(root) syncTpl(root,true);
      setup(clone);
      clone.querySelectorAll&&clone.querySelectorAll(TAGS).forEach(setup);
      selectEl(clone);
    }

    if(d.type==='INSERT'){
      var tmp=document.createElement('div');
      tmp.innerHTML=d.html;
      var newEl=tmp.firstElementChild;
      if(!newEl) return;
      if(curEl){
        if(d.position==='before') curEl.before(newEl);
        else if(d.position==='inside') curEl.appendChild(newEl);
        else curEl.after(newEl);
      } else {
        document.body.appendChild(newEl);
      }
      var root=curEl?curEl.closest('[data-source-tpl]'):null;
      if(root) syncTpl(root,true);
      setup(newEl);
      newEl.querySelectorAll&&newEl.querySelectorAll(TAGS).forEach(setup);
      selectEl(newEl);
    }
  });
})();
`;
            doc.body.appendChild(sc);
        } catch (err) {
            console.warn('[Builder] inject error', err);
            toast('Cross-origin iframe — some features limited', 'err', 4000);
        }
    }

    // Show saved toast if PHP set the flag
    if (window.__SAVED__) toast('Page saved successfully', 'ok');
})();
</script>
</main>
