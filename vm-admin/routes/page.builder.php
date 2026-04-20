<?php
// ══════════════════════════════════════════════════════════
//  BACKEND — keep all original backend logic intact
// ══════════════════════════════════════════════════════════
$siteDir = dirname(dirname(dirname(__FILE__))) . "/sites/" . __DOMAIN__;
$configFile = $siteDir . "/config.php";
$htmlFile = $siteDir . "/builder.cache.html";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_all') {
    if (!is_dir($siteDir))
        mkdir($siteDir, 0755, true);

    $configContent = "<?php" . PHP_EOL;
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'CONF_') === 0) {
            $configContent .= "define(\"" . str_replace('CONF_', '', $key) . "\", \"" . addslashes($value) . "\");" . PHP_EOL;
        }
    }
    // file_put_contents($configFile, $configContent);

    if (isset($_POST['site_rendered_html'])) {
        file_put_contents($htmlFile, $_POST['site_rendered_html']);
    }
    header("Location: #");
    exit;
}

$content = file_exists($configFile) ? file_get_contents($configFile) : '';
preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);
$config = [];
if (!empty($matches[1])) {
    foreach ($matches[1] as $idx => $key)
        $config[$key] = $matches[2][$idx];
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    /* ════════════════════════════════
   DESIGN TOKENS
════════════════════════════════ */
    :root {
        --bg: #07090d;
        --surface: #0d1018;
        --surf2: #141720;
        --surf3: #1c2030;
        --border: rgba(255, 255, 255, 0.06);
        --border2: rgba(255, 255, 255, 0.11);
        --text: #c2c9d9;
        --dim: #48506a;
        --accent: #3d7eff;
        --accent2: #7c5cfc;
        --green: #0fba81;
        --red: #f04444;
        --amber: #f59e0b;
        --fd: 'Syne', sans-serif;
        --fb: 'DM Sans', sans-serif;
    }

    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0
    }

    html,
    body {
        height: 100%;
        overflow: hidden
    }

    body {
        font-family: var(--fb);
        background: var(--bg);
        color: var(--text);
        display: flex;
        flex-direction: column;
    }

    /* ── scrollbar ── */
    ::-webkit-scrollbar {
        width: 4px;
        height: 4px
    }

    ::-webkit-scrollbar-track {
        background: transparent
    }

    ::-webkit-scrollbar-thumb {
        background: var(--border2);
        border-radius: 2px
    }

    /* ════════════════════════════════
   TOPBAR
════════════════════════════════ */
    #topbar {
        height: 50px;
        min-height: 50px;
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        padding: 0 10px;
        gap: 6px;
        z-index: 200;
        flex-shrink: 0;
    }

    .brand {
        font-family: var(--fd);
        font-weight: 800;
        font-size: 14px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 7px;
        padding-right: 14px;
        border-right: 1px solid var(--border);
        margin-right: 6px;
        flex-shrink: 0;
    }

    .brand-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--accent);
        box-shadow: 0 0 7px var(--accent);
        animation: blink 2.5s ease-in-out infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .35
        }
    }

    /* mode pills */
    .mode-sw {
        display: flex;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 7px;
        padding: 3px;
        gap: 2px;
        flex-shrink: 0;
    }

    .mpill {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 5px 11px;
        border-radius: 5px;
        border: none;
        background: transparent;
        color: var(--dim);
        font-family: var(--fb);
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }

    .mpill i {
        font-size: 13px
    }

    .mpill:hover {
        color: var(--text);
        background: var(--surf2)
    }

    .mpill.on {
        background: var(--accent);
        color: #fff;
        box-shadow: 0 2px 8px rgba(61, 126, 255, .4)
    }

    /* viewport */
    .vp-row {
        display: flex;
        align-items: center;
        gap: 2px;
        margin-left: 4px;
        flex-shrink: 0
    }

    .vpb {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: var(--dim);
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all .15s;
    }

    .vpb:hover {
        color: var(--text);
        background: var(--surf2)
    }

    .vpb.on {
        color: var(--accent);
        background: rgba(61, 126, 255, .1)
    }

    .tb-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 7px;
        flex-shrink: 0
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 13px;
        border-radius: 7px;
        border: none;
        font-family: var(--fb);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }

    .btn i {
        font-size: 12px
    }

    .btn-ghost {
        background: transparent;
        color: var(--dim);
        border: 1px solid var(--border2)
    }

    .btn-ghost:hover {
        color: var(--text);
        background: var(--surf2)
    }

    .btn-primary {
        background: var(--accent);
        color: #fff;
        box-shadow: 0 2px 10px rgba(61, 126, 255, .35)
    }

    .btn-primary:hover {
        background: #5a8fff;
        box-shadow: 0 2px 14px rgba(61, 126, 255, .5)
    }

    .btn-red {
        background: var(--red);
        color: #fff
    }

    .btn-red:hover {
        background: #f87171
    }

    .tb-sep {
        width: 1px;
        height: 20px;
        background: var(--border);
        margin: 0 4px;
        flex-shrink: 0
    }

    /* ════════════════════════════════
   BODY ROW
════════════════════════════════ */
    #body-row {
        flex: 1;
        display: flex;
        overflow: hidden
    }

    /* ── CANVAS ── */
    #canvas-area {
        flex: 1;
        overflow: auto;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        background: var(--bg);
        background-image:
            radial-gradient(circle at 18% 82%, rgba(61, 126, 255, .04) 0%, transparent 50%),
            radial-gradient(circle at 82% 18%, rgba(124, 92, 252, .04) 0%, transparent 50%),
            radial-gradient(#181c27 1px, transparent 1px);
        background-size: 100% 100%, 100% 100%, 22px 22px;
        padding: 16px;
        transition: padding .3s;
    }

    #iframe-wrap {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        flex-shrink: 0;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, .07), 0 24px 80px -20px rgba(0, 0, 0, .7);
        transition: width .3s cubic-bezier(.4, 0, .2, 1), border-radius .3s;
        height: calc(100vh - 82px);
        position: relative;
    }

    #iframe-wrap.vp-desktop {
        width: 100%
    }

    #iframe-wrap.vp-tablet {
        width: 768px;
        border-radius: 16px
    }

    #iframe-wrap.vp-mobile {
        width: 390px;
        border-radius: 22px
    }

    #editor-iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
        background: #fff
    }

    /* ── PANEL ── */
    #side-panel {
        width: 268px;
        min-width: 268px;
        background: var(--surface);
        border-left: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        flex-shrink: 0;
        transition: width .25s cubic-bezier(.4, 0, .2, 1), min-width .25s, opacity .2s;
    }

    #side-panel.collapsed {
        width: 0;
        min-width: 0;
        opacity: 0;
        pointer-events: none
    }

    .ph {
        padding: 12px 14px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .ph-title {
        font-family: var(--fd);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px
    }

    .pb {
        flex: 1;
        overflow-y: auto;
        padding: 13px;
        display: flex;
        flex-direction: column;
        gap: 11px
    }

    /* empty state */
    .p-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 40px 16px;
        color: var(--dim);
        text-align: center;
    }

    .p-empty i {
        font-size: 28px;
        opacity: .25
    }

    .p-empty p {
        font-size: 11px;
        line-height: 1.5
    }

    /* breadcrumb */
    #breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 2px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 6px 8px;
        min-height: 34px;
    }

    .bc-item {
        font-size: 10px;
        font-weight: 600;
        color: var(--dim);
        background: var(--surf2);
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 2px 7px;
        cursor: pointer;
        transition: all .12s;
        white-space: nowrap;
    }

    .bc-item:hover {
        color: var(--text);
        border-color: var(--border2)
    }

    .bc-item.bc-active {
        color: var(--accent);
        border-color: var(--accent);
        background: rgba(61, 126, 255, .1)
    }

    .bc-arrow {
        color: var(--dim);
        font-size: 9px;
        padding: 0 1px
    }

    /* inspector sections */
    .ins-section {
        display: flex;
        flex-direction: column;
        gap: 7px
    }

    .ins-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .9px;
        color: var(--dim)
    }

    .ins-field {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 7px;
        padding: 7px 9px;
        width: 100%;
        color: var(--text);
        font-family: var(--fb);
        font-size: 12px;
        outline: none;
        resize: none;
        transition: border-color .15s;
    }

    .ins-field:focus {
        border-color: var(--accent)
    }

    .ins-row {
        display: flex;
        gap: 6px
    }

    .ins-row .ins-field {
        flex: 1;
        min-width: 0
    }

    /* colour field */
    .col-wrap {
        position: relative
    }

    .col-wrap .ins-field {
        padding-left: 32px
    }

    .col-swatch {
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, .15);
        cursor: pointer;
    }

    /* rich-text toolbar */
    #rt-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 2px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 7px;
        padding: 4px;
    }

    .rt-btn {
        width: 27px;
        height: 27px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: var(--dim);
        border-radius: 5px;
        font-size: 13px;
        cursor: pointer;
        transition: all .12s;
        font-family: var(--fb);
        font-weight: 700;
    }

    .rt-btn:hover {
        color: var(--text);
        background: var(--surf2)
    }

    .rt-btn.on {
        color: var(--accent);
        background: rgba(61, 126, 255, .12)
    }

    .rt-sep {
        width: 1px;
        height: 18px;
        background: var(--border);
        margin: 0 2px
    }

    /* action grid */
    .act-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px
    }

    .act-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 10px 6px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--dim);
        font-size: 10px;
        font-weight: 500;
        cursor: pointer;
        transition: all .15s;
        font-family: var(--fb);
    }

    .act-btn i {
        font-size: 16px
    }

    .act-btn:hover {
        color: var(--text);
        background: var(--surf2);
        border-color: var(--border2)
    }

    .act-btn.danger:hover {
        color: var(--red);
        border-color: var(--red);
        background: rgba(240, 68, 68, .07)
    }

    .act-btn.prim:hover {
        color: var(--accent);
        border-color: var(--accent);
        background: rgba(61, 126, 255, .07)
    }

    /* size inputs */
    .size-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px
    }

    /* ── STATUS BAR ── */
    #statusbar {
        height: 25px;
        min-height: 25px;
        background: var(--surface);
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        padding: 0 14px;
        gap: 14px;
        flex-shrink: 0;
    }

    .sb-item {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        color: var(--dim)
    }

    .sb-badge {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 1px 6px;
        border-radius: 3px;
    }

    .badge-nav {
        background: rgba(15, 186, 129, .12);
        color: var(--green)
    }

    .badge-ins {
        background: rgba(61, 126, 255, .12);
        color: var(--accent)
    }

    /* ════════════════════════════════
   CSS FLOAT
════════════════════════════════ */
    #css-float {
        position: fixed;
        bottom: 38px;
        right: 18px;
        width: 330px;
        background: var(--surface);
        border: 1px solid var(--border2);
        border-radius: 13px;
        box-shadow: 0 20px 60px -10px rgba(0, 0, 0, .65);
        z-index: 400;
        display: none;
        flex-direction: column;
        overflow: hidden;
    }

    #css-float.open {
        display: flex;
        animation: fin .2s cubic-bezier(.16, 1, .3, 1)
    }

    @keyframes fin {
        from {
            transform: translateY(10px) scale(.97);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    .fh {
        padding: 9px 13px;
        background: var(--bg);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: move;
        user-select: none;
    }

    .fh-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        display: flex;
        align-items: center;
        gap: 6px
    }

    .fh-title i {
        color: var(--accent2)
    }

    #css-ta {
        background: #080c12;
        color: #7ec8a0;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.65;
        padding: 12px;
        border: none;
        outline: none;
        resize: none;
        height: 210px;
        width: 100%;
    }

    .ff {
        padding: 7px 11px;
        background: var(--bg);
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    /* ════════════════════════════════
   MODALS (block picker + config)
════════════════════════════════ */
    .modal-bg {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .72);
        backdrop-filter: blur(5px);
        z-index: 600;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    .modal-bg.open {
        display: flex
    }

    .modal-box {
        background: var(--surface);
        border: 1px solid var(--border2);
        border-radius: 16px;
        width: 100%;
        max-width: 460px;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 28px 80px -18px rgba(0, 0, 0, .75);
        animation: fin .2s cubic-bezier(.16, 1, .3, 1);
    }

    .mh {
        padding: 18px 20px 14px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .mh-title {
        font-family: var(--fd);
        font-size: 15px;
        font-weight: 700;
        color: #fff
    }

    .mclose {
        width: 27px;
        height: 27px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: var(--bg);
        border-radius: 6px;
        color: var(--dim);
        cursor: pointer;
        font-size: 13px;
        transition: all .15s;
    }

    .mclose:hover {
        color: #fff;
        background: var(--surf2)
    }

    .mbody {
        flex: 1;
        overflow-y: auto;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 18px
    }

    .msec-title {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.1px;
        color: var(--dim);
        margin-bottom: 7px
    }

    .field-card {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 9px 11px;
        display: flex;
        flex-direction: column;
        gap: 5px
    }

    .field-card label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--dim)
    }

    .field-card textarea {
        background: transparent;
        border: none;
        outline: none;
        color: var(--text);
        font-family: var(--fb);
        font-size: 13px;
        resize: none;
        min-height: 26px;
        width: 100%
    }

    .mfoot {
        padding: 14px 20px;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 7px;
        flex-shrink: 0
    }

    /* block picker grid */
    .bp-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 14px
    }

    .bp-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        padding: 14px 8px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 9px;
        color: var(--dim);
        font-size: 10px;
        font-weight: 500;
        cursor: pointer;
        transition: all .15s;
        font-family: var(--fb);
    }

    .bp-btn i {
        font-size: 20px
    }

    .bp-btn:hover {
        color: var(--text);
        background: var(--surf2);
        border-color: var(--border2)
    }

    /* ── TOAST ── */
    #toast-wrap {
        position: fixed;
        bottom: 34px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 900;
        display: flex;
        flex-direction: column;
        gap: 7px;
        pointer-events: none;
    }

    .toast {
        background: var(--surface);
        border: 1px solid var(--border2);
        color: var(--text);
        font-size: 12px;
        font-weight: 500;
        padding: 9px 14px;
        border-radius: 9px;
        box-shadow: 0 8px 22px rgba(0, 0, 0, .4);
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        animation: toastIn .22s cubic-bezier(.16, 1, .3, 1) forwards;
    }

    .toast.ok i {
        color: var(--green)
    }

    .toast.err i {
        color: var(--red)
    }

    .toast.inf i {
        color: var(--accent)
    }

    @keyframes toastIn {
        from {
            opacity: 0;
            transform: translateY(8px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    /* ── responsive ── */
    @media(max-width:640px) {
        .lbl {
            display: none
        }

        .vp-row {
            display: none
        }

        #side-panel {
            width: 100%;
            min-width: 100%;
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            z-index: 150
        }
    }
</style>
</head>

<main class="flex flex-1 flex-col h-screen overflow-hidden bg-[#09090b] text-zinc-100 font-sans">

    <?php @include_once "header.php"; ?>

    <!-- ══════════ TOPBAR ══════════ -->
    <div id="topbar">
        <div class="mode-sw">
            <button class="mpill" id="m-nav" onclick="setMode('navigate')">
                <i class="bi bi-cursor"></i><span class="lbl">Navigate</span>
            </button>
            <button class="mpill on" id="m-ins" onclick="setMode('inspect')">
                <i class="bi bi-vector-pen"></i><span class="lbl">Inspect</span>
            </button>
        </div>

        <div class="vp-row">
            <div class="tb-sep"></div>
            <button class="vpb on" id="vp-d" onclick="setVP('desktop')" title="Desktop"><i
                    class="bi bi-display"></i></button>
            <button class="vpb" id="vp-t" onclick="setVP('tablet')" title="Tablet"><i class="bi bi-tablet"></i></button>
            <button class="vpb" id="vp-m" onclick="setVP('mobile')" title="Mobile"><i class="bi bi-phone"></i></button>
        </div>

        <div class="tb-right">
            <button class="btn btn-ghost" onclick="toggleCSS()" title="CSS Editor">
                <i class="bi bi-braces"></i><span class="lbl">CSS</span>
            </button>
            <button class="btn btn-ghost" onclick="openCfg()">
                <i class="bi bi-sliders2"></i><span class="lbl">Settings</span>
            </button>
            <div class="tb-sep"></div>
            <button class="btn btn-primary" onclick="saveViaForm()">
                <i class="bi bi-cloud-check-fill"></i><span class="lbl">Save</span>
            </button>
        </div>
    </div>

    <!-- ══════════ BODY ROW ══════════ -->
    <div id="body-row">

        <!-- CANVAS -->
        <div id="canvas-area">
            <div id="iframe-wrap" class="vp-desktop">
                <iframe id="editor-iframe"
                    src="<?php echo defined('__WEBSITE_URL__') ? __WEBSITE_URL__ : 'about:blank'; ?>"></iframe>
            </div>
        </div>

        <!-- INSPECTOR PANEL -->
        <div id="side-panel">
            <div class="ph">
                <span class="ph-title">Inspector</span>
                <button class="mclose" onclick="collapsePanel()"><i class="bi bi-x"></i></button>
            </div>
            <div class="pb" id="panel-body">

                <!-- Empty state -->
                <div class="p-empty" id="p-empty">
                    <i class="bi bi-cursor-text"></i>
                    <p>Click any element on the page to select and edit it.</p>
                </div>

                <!-- Active state -->
                <div id="p-active" style="display:none;flex-direction:column;gap:11px;">

                    <!-- Breadcrumb -->
                    <div class="ins-section">
                        <span class="ins-label">Element Tree</span>
                        <div id="breadcrumb"></div>
                    </div>

                    <!-- Rich-text toolbar (hidden for images) -->
                    <div class="ins-section" id="sec-richtext">
                        <span class="ins-label">Text Formatting</span>
                        <div id="rt-toolbar">
                            <button class="rt-btn" title="Bold" onclick="rtCmd('bold')"><b>B</b></button>
                            <button class="rt-btn" title="Italic" onclick="rtCmd('italic')"><i>I</i></button>
                            <button class="rt-btn" title="Underline" onclick="rtCmd('underline')"><u>U</u></button>
                            <div class="rt-sep"></div>
                            <button class="rt-btn" title="Align Left" onclick="rtCmd('justifyLeft')"><i
                                    class="bi bi-text-left"></i></button>
                            <button class="rt-btn" title="Align Center" onclick="rtCmd('justifyCenter')"><i
                                    class="bi bi-text-center"></i></button>
                            <button class="rt-btn" title="Align Right" onclick="rtCmd('justifyRight')"><i
                                    class="bi bi-text-right"></i></button>
                            <div class="rt-sep"></div>
                            <button class="rt-btn" title="Link" onclick="rtLink()"><i
                                    class="bi bi-link-45deg"></i></button>
                            <button class="rt-btn" title="Unlink" onclick="rtCmd('unlink')"><i
                                    class="bi bi-link-break"></i></button>
                            <div class="rt-sep"></div>
                            <button class="rt-btn" title="Edit Mode" id="rt-edit-btn" onclick="toggleEditMode()"><i
                                    class="bi bi-pencil"></i></button>
                        </div>
                        <div style="display:flex;gap:6px;margin-top:4px">
                            <input class="ins-field" id="ip-fontsize" type="text" placeholder="Font size"
                                style="flex:1">
                            <input class="ins-field" id="ip-fontweight" type="text" placeholder="Weight" style="flex:1">
                        </div>
                    </div>

                    <!-- Image section -->
                    <div class="ins-section" id="sec-img" style="display:none">
                        <span class="ins-label">Image</span>
                        <input class="ins-field" id="ip-src" type="text" placeholder="Image URL…">
                        <div class="size-grid">
                            <input class="ins-field" id="ip-img-w" type="text" placeholder="Width (e.g. 100%)">
                            <input class="ins-field" id="ip-img-h" type="text" placeholder="Height (e.g. auto)">
                        </div>
                        <input class="ins-field" id="ip-alt" type="text" placeholder="Alt text">
                        <button class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:2px"
                            onclick="document.getElementById('img-upload').click()">
                            <i class="bi bi-upload"></i> Upload Image
                        </button>
                        <input type="file" id="img-upload" accept="image/*" style="display:none"
                            onchange="onImgUpload(event)">
                    </div>

                    <!-- Link -->
                    <div class="ins-section" id="sec-link" style="display:none">
                        <span class="ins-label">Link</span>
                        <input class="ins-field" id="ip-href" type="text" placeholder="https://…">
                        <label
                            style="font-size:10px;color:var(--dim);display:flex;align-items:center;gap:6px;margin-top:2px;cursor:pointer">
                            <input type="checkbox" id="ip-blank"> Open in new tab
                        </label>
                    </div>

                    <!-- Colour -->
                    <div class="ins-section">
                        <span class="ins-label">Colours</span>
                        <div class="col-wrap">
                            <input type="color" class="col-swatch" id="cp-color"
                                oninput="syncColor('ip-color','cp-color')">
                            <input class="ins-field" id="ip-color" type="text" placeholder="Text color"
                                oninput="syncPicker('ip-color','cp-color')">
                        </div>
                        <div class="col-wrap">
                            <input type="color" class="col-swatch" id="cp-bg" oninput="syncColor('ip-bg','cp-bg')">
                            <input class="ins-field" id="ip-bg" type="text" placeholder="Background color"
                                oninput="syncPicker('ip-bg','cp-bg')">
                        </div>
                    </div>

                    <!-- Spacing -->
                    <div class="ins-section">
                        <span class="ins-label">Spacing</span>
                        <div class="ins-row">
                            <input class="ins-field" id="ip-padding" type="text" placeholder="Padding">
                            <input class="ins-field" id="ip-margin" type="text" placeholder="Margin">
                        </div>
                    </div>

                    <!-- CSS Classes -->
                    <div class="ins-section">
                        <span class="ins-label">CSS Classes</span>
                        <textarea class="ins-field" id="ip-classes" rows="2" placeholder="class1 class2 …"></textarea>
                    </div>

                    <!-- Apply -->
                    <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="applyChanges()">
                        <i class="bi bi-check2-circle"></i> Apply Changes
                    </button>

                    <!-- Insert/Actions -->
                    <div class="ins-section">
                        <span class="ins-label">Actions</span>
                        <div class="act-grid">
                            <button class="act-btn prim" onclick="openBlockPicker('before')"><i
                                    class="bi bi-arrow-up-square"></i>Before</button>
                            <button class="act-btn prim" onclick="openBlockPicker('after')"><i
                                    class="bi bi-arrow-down-square"></i>After</button>
                            <button class="act-btn prim" onclick="openBlockPicker('inside')"><i
                                    class="bi bi-bounding-box"></i>Inside</button>
                            <button class="act-btn prim" onclick="dupEl()"><i class="bi bi-copy"></i>Duplicate</button>
                        </div>
                        <button class="act-btn danger"
                            style="width:100%;flex-direction:row;justify-content:center;gap:7px;margin-top:2px"
                            onclick="delEl()">
                            <i class="bi bi-trash3"></i> Delete Element
                        </button>
                    </div>

                </div><!-- /p-active -->
            </div><!-- /pb -->
        </div><!-- /side-panel -->

    </div><!-- /body-row -->

    <!-- ══════════ STATUS BAR ══════════ -->
    <div id="statusbar">
        <div class="sb-item"><span class="sb-badge badge-ins" id="sb-mode">Inspect</span></div>
        <div class="sb-item"><i class="bi bi-display"></i><span id="sb-vp">Desktop</span></div>
        <div class="sb-item" id="sb-el-wrap" style="display:none"><i class="bi bi-cursor-text"></i><span
                id="sb-el">—</span></div>
        <div class="sb-item" style="margin-left:auto"><span id="sb-saved" style="color:var(--dim)">No unsaved
                changes</span></div>
    </div>

    <!-- ══════════ CSS FLOAT ══════════ -->
    <div id="css-float">
        <div class="fh" id="css-drag">
            <div class="fh-title"><i class="bi bi-braces-asterisk"></i> Live CSS</div>
            <button class="mclose" onclick="toggleCSS()"><i class="bi bi-x"></i></button>
        </div>
        <div style="padding:9px 13px 6px;border-bottom:1px solid var(--border)">
            <span class="ins-label">Selector (blank = global)</span>
            <input class="ins-field" id="css-sel" type="text" placeholder=".hero h1, #banner …" style="margin-top:6px">
        </div>
        <textarea id="css-ta" placeholder="/* Live CSS — applied instantly */
color: red;
font-size: 18px;
background: #fff;"></textarea>
        <div class="ff">
            <button class="btn btn-ghost" style="font-size:10px;padding:5px 9px" onclick="clearCSS()"><i
                    class="bi bi-trash"></i></button>
            <button class="btn btn-primary" style="font-size:10px;padding:5px 12px" onclick="applyCSS()">
                <i class="bi bi-lightning-charge-fill"></i> Apply
            </button>
        </div>
    </div>

    <!-- ══════════ BLOCK PICKER MODAL ══════════ -->
    <div class="modal-bg" id="block-modal">
        <div class="modal-box" style="max-width:400px">
            <div class="mh">
                <span class="mh-title">Insert Block</span>
                <button class="mclose" onclick="closeBlockModal()"><i class="bi bi-x"></i></button>
            </div>
            <div class="bp-grid">
                <button class="bp-btn" onclick="doInsert('h2')"><i class="bi bi-type-h2"></i>Heading</button>
                <button class="bp-btn" onclick="doInsert('h3')"><i class="bi bi-type-h3"></i>Sub-heading</button>
                <button class="bp-btn" onclick="doInsert('p')"><i class="bi bi-text-paragraph"></i>Paragraph</button>
                <button class="bp-btn" onclick="doInsert('button')"><i class="bi bi-toggles2"></i>Button</button>
                <button class="bp-btn" onclick="doInsert('img')"><i class="bi bi-image"></i>Image</button>
                <button class="bp-btn" onclick="doInsert('div')"><i class="bi bi-bounding-box"></i>Div</button>
                <button class="bp-btn" onclick="doInsert('a')"><i class="bi bi-link-45deg"></i>Link</button>
                <button class="bp-btn" onclick="doInsert('span')"><i class="bi bi-fonts"></i>Span</button>
                <button class="bp-btn" onclick="showCustomHTML()"><i class="bi bi-code-slash"></i>Custom HTML</button>
            </div>
            <div id="custom-area" style="display:none;padding:0 14px 14px">
                <textarea class="ins-field" id="custom-html" rows="5" placeholder="<div>Your HTML here…</div>"
                    style="font-family:monospace;font-size:12px"></textarea>
                <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px"
                    onclick="doInsert('custom')">
                    <i class="bi bi-check2"></i> Insert
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════ CONFIG MODAL ══════════ -->
    <div class="modal-bg" id="cfg-modal">
        <div class="modal-box">
            <div class="mh">
                <span class="mh-title">Site Settings</span>
                <button class="mclose" onclick="closeCfg()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="mbody">
                <?php foreach (['Branding' => ['LOGO', 'ICON'], 'Content' => ['TITLE', 'TEXT']] as $sName => $keys): ?>
                    <div>
                        <div class="msec-title"><?= htmlspecialchars($sName) ?></div>
                        <div style="display:flex;flex-direction:column;gap:7px">
                            <?php foreach ($config as $key => $val):
                                $match = false;
                                foreach ($keys as $k)
                                    if (strpos($key, $k) !== false)
                                        $match = true;
                                if (!$match)
                                    continue; ?>
                                <div class="field-card">
                                    <label><?= htmlspecialchars($key) ?></label>
                                    <textarea
                                        data-conf-key="CONF_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($val) ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mfoot">
                <button class="btn btn-ghost" onclick="closeCfg()">Cancel</button>
                <button class="btn btn-primary" onclick="closeCfg()"><i class="bi bi-check2"></i> Done</button>
            </div>
        </div>
    </div>

    <!-- ══════════ TOASTS ══════════ -->
    <div id="toast-wrap"></div>

    <!-- ══════════ HIDDEN FORM ══════════ -->
    <form id="save-form" method="POST" action="" style="display:none">
        <input type="hidden" name="action" value="save_all">
        <input type="hidden" name="site_rendered_html" id="html-payload">
        <div id="conf-inputs"></div>
    </form>

    <!-- ══════════════════════════════════════════════════════════
     PARENT SCRIPT
══════════════════════════════════════════════════════════ -->
    <script>
        /* ─── GLOBALS ─── */
        const iframe = document.getElementById('editor-iframe');
        const sidePanel = document.getElementById('side-panel');
        let curMode = 'inspect';
        let curVP = 'desktop';
        let unsaved = false;
        let editMode = false;   // contenteditable active?
        let selPath = [];      // DOM ancestor array [deepest, ..., shallowest] — breadcrumb

        /* ─── TOAST ─── */
        function toast(msg, type = 'inf', ms = 2600) {
            const icons = { ok: 'bi-check-circle-fill', err: 'bi-x-circle-fill', inf: 'bi-info-circle-fill' };
            const wrap = document.getElementById('toast-wrap');
            const t = document.createElement('div');
            t.className = `toast ${type}`;
            t.innerHTML = `<i class="bi ${icons[type] || icons.inf}"></i>${msg}`;
            wrap.appendChild(t);
            setTimeout(() => { t.style.transition = '.3s'; t.style.opacity = '0'; t.style.transform = 'translateY(6px)'; setTimeout(() => t.remove(), 320); }, ms);
        }

        /* ─── MODE ─── */
        function setMode(m) {
            curMode = m;
            document.getElementById('m-nav').classList.toggle('on', m === 'navigate');
            document.getElementById('m-ins').classList.toggle('on', m === 'inspect');
            const badge = document.getElementById('sb-mode');
            if (m === 'navigate') {
                badge.textContent = 'Navigate'; badge.className = 'sb-badge badge-nav';
                sidePanel.classList.add('collapsed');
                toIframe({ type: 'SET_MODE', mode: 'navigate' });
                toast('Navigate mode — page links & scroll restored', 'inf');
            } else {
                badge.textContent = 'Inspect'; badge.className = 'sb-badge badge-ins';
                sidePanel.classList.remove('collapsed');
                toIframe({ type: 'SET_MODE', mode: 'inspect' });
                toast('Inspect mode — click any element to edit', 'inf');
            }
        }

        function collapsePanel() { sidePanel.classList.add('collapsed'); }

        /* ─── VIEWPORT ─── */
        function setVP(v) {
            curVP = v;
            const w = document.getElementById('iframe-wrap');
            w.className = `vp-${v}`;
            ['desktop', 'tablet', 'mobile'].forEach(x => document.getElementById(`vp-${x[0]}`).classList.toggle('on', x === v));
            document.getElementById('sb-vp').textContent = { desktop: 'Desktop', tablet: 'Tablet', mobile: 'Mobile' }[v];
        }

        /* ─── COLOUR SYNC ─── */
        function syncColor(fieldId, pickId) {
            document.getElementById(fieldId).value = document.getElementById(pickId).value;
        }
        function syncPicker(fieldId, pickId) {
            const v = document.getElementById(fieldId).value;
            if (/^#[0-9a-f]{6}$/i.test(v)) document.getElementById(pickId).value = v;
        }
        function rgb2hex(rgb) {
            if (!rgb || rgb.startsWith('#')) return rgb || '#000000';
            const m = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (!m) return '#000000';
            return '#' + [m[1], m[2], m[3]].map(x => parseInt(x).toString(16).padStart(2, '0')).join('');
        }

        /* ─── RICH TEXT ─── */
        function rtCmd(cmd) {
            toIframe({ type: 'RT_CMD', cmd });
        }
        function rtLink() {
            const url = prompt('Enter URL:', 'https://');
            if (url) toIframe({ type: 'RT_CMD', cmd: 'createLink', val: url });
        }
        function toggleEditMode() {
            editMode = !editMode;
            document.getElementById('rt-edit-btn').classList.toggle('on', editMode);
            toIframe({ type: 'SET_EDIT', editMode });
            toast(editMode ? 'Edit mode ON — type to edit content' : 'Edit mode OFF', 'inf', 1500);
        }

        /* ─── INSPECTOR POPULATE ─── */
        function showEmpty() {
            document.getElementById('p-empty').style.display = '';
            document.getElementById('p-active').style.display = 'none';
            document.getElementById('sb-el-wrap').style.display = 'none';
            selPath = [];
            if (editMode) { editMode = false; document.getElementById('rt-edit-btn').classList.remove('on'); }
        }

        function populate(d) {
            document.getElementById('p-empty').style.display = 'none';
            const pa = document.getElementById('p-active');
            pa.style.display = 'flex';

            const isImg = d.tag === 'IMG';
            const isA = d.tag === 'A';

            // breadcrumb
            selPath = d.breadcrumb || [d.tag];
            buildBreadcrumb(selPath, d.bcActive ?? selPath.length - 1);

            // section visibility
            document.getElementById('sec-richtext').style.display = isImg ? 'none' : '';
            document.getElementById('sec-img').style.display = isImg ? '' : 'none';
            document.getElementById('sec-link').style.display = isA ? '' : 'none';

            // fields
            if (!isImg) {
                document.getElementById('ip-fontsize').value = d.fontSize || '';
                document.getElementById('ip-fontweight').value = d.fontWeight || '';
            }
            if (isImg) {
                document.getElementById('ip-src').value = d.src || '';
                document.getElementById('ip-img-w').value = d.imgW || '';
                document.getElementById('ip-img-h').value = d.imgH || '';
                document.getElementById('ip-alt').value = d.alt || '';
            }
            if (isA) {
                document.getElementById('ip-href').value = d.href || '';
                document.getElementById('ip-blank').checked = d.blank || false;
            }

            document.getElementById('ip-color').value = d.color || '';
            document.getElementById('ip-bg').value = d.background || '';
            document.getElementById('ip-padding').value = d.padding || '';
            document.getElementById('ip-margin').value = d.margin || '';
            document.getElementById('ip-classes').value = d.classes || '';

            try { document.getElementById('cp-color').value = rgb2hex(d.color); } catch (e) { }
            try { document.getElementById('cp-bg').value = rgb2hex(d.background); } catch (e) { }

            document.getElementById('sb-el-wrap').style.display = '';
            document.getElementById('sb-el').textContent = d.tag + (d.classes ? ' .' + d.classes.split(' ')[0] : '');

            if (sidePanel.classList.contains('collapsed') && curMode === 'inspect')
                sidePanel.classList.remove('collapsed');
        }

        function buildBreadcrumb(path, activeIdx) {
            const bc = document.getElementById('breadcrumb');
            bc.innerHTML = '';
            path.forEach((tag, i) => {
                if (i > 0) { const arr = document.createElement('span'); arr.className = 'bc-arrow'; arr.textContent = '›'; bc.appendChild(arr); }
                const btn = document.createElement('button');
                btn.className = 'bc-item' + (i === activeIdx ? ' bc-active' : '');
                btn.textContent = tag;
                btn.onclick = () => { toIframe({ type: 'SELECT_ANCESTOR', index: i }); };
                bc.appendChild(btn);
            });
        }

        /* ─── APPLY CHANGES ─── */
        function applyChanges() {
            const d = {
                type: 'APPLY',
                fontSize: document.getElementById('ip-fontsize').value,
                fontWeight: document.getElementById('ip-fontweight').value,
                color: document.getElementById('ip-color').value,
                background: document.getElementById('ip-bg').value,
                padding: document.getElementById('ip-padding').value,
                margin: document.getElementById('ip-margin').value,
                classes: document.getElementById('ip-classes').value,
                src: document.getElementById('ip-src').value,
                imgW: document.getElementById('ip-img-w').value,
                imgH: document.getElementById('ip-img-h').value,
                alt: document.getElementById('ip-alt').value,
                href: document.getElementById('ip-href').value,
                blank: document.getElementById('ip-blank').checked,
            };
            toIframe(d);
            markUnsaved();
            toast('Changes applied', 'ok');
        }

        function onImgUpload(e) {
            const f = e.target.files[0]; if (!f) return;
            const r = new FileReader();
            r.onload = ev => { document.getElementById('ip-src').value = ev.target.result; toIframe({ type: 'APPLY', src: ev.target.result }); markUnsaved(); toast('Image uploaded', 'ok'); };
            r.readAsDataURL(f);
        }

        /* ─── DELETE / DUP ─── */
        function delEl() {
            toIframe({ type: 'DELETE' }); showEmpty(); markUnsaved(); toast('Element deleted', 'inf');
        }
        function dupEl() {
            toIframe({ type: 'DUPLICATE' }); markUnsaved(); toast('Element duplicated', 'ok');
        }

        /* ─── BLOCK PICKER ─── */
        let insertPos = 'after';
        function openBlockPicker(pos) { insertPos = pos; document.getElementById('custom-area').style.display = 'none'; document.getElementById('block-modal').classList.add('open'); }
        function closeBlockModal() { document.getElementById('block-modal').classList.remove('open'); }
        function showCustomHTML() { document.getElementById('custom-area').style.display = ''; }

        const BLOCK_TPLS = {
            h2: '<h2 style="font-size:1.75rem;font-weight:700;margin-bottom:12px;">New Heading</h2>',
            h3: '<h3 style="font-size:1.3rem;font-weight:600;margin-bottom:10px;">Sub-heading</h3>',
            p: '<p style="line-height:1.7;margin-bottom:14px;">New paragraph — click to edit.</p>',
            button: '<button style="background:#3d7eff;color:#fff;padding:10px 22px;border-radius:8px;border:none;font-weight:600;cursor:pointer;">Click Here</button>',
            img: '<img src="https://placehold.co/800x400/e2e8f0/64748b?text=Image" alt="Placeholder" style="width:100%;border-radius:10px;display:block;margin-bottom:14px;">',
            div: '<div style="padding:24px;background:#f8fafc;border-radius:10px;margin-bottom:14px;"><p>New section</p></div>',
            a: '<a href="#" style="color:#3d7eff;text-decoration:underline;">New link</a>',
            span: '<span>New span text</span>',
        };

        function doInsert(type) {
            const html = type === 'custom'
                ? document.getElementById('custom-html').value.trim()
                : BLOCK_TPLS[type] || '';
            if (!html) { toast('Enter HTML first', 'err'); return; }
            closeBlockModal();
            toIframe({ type: 'INSERT', html, position: insertPos });
            markUnsaved();
            toast(`${type.toUpperCase()} inserted`, 'ok');
        }

        /* ─── CSS EDITOR ─── */
        function toggleCSS() { document.getElementById('css-float').classList.toggle('open'); }
        function clearCSS() { document.getElementById('css-ta').value = ''; document.getElementById('css-sel').value = ''; }
        function applyCSS() {
            const sel = document.getElementById('css-sel').value.trim();
            const css = document.getElementById('css-ta').value.trim();
            if (!css) { toast('CSS is empty', 'err'); return; }
            toIframe({ type: 'INJECT_CSS', sel, css });
            markUnsaved(); toast('CSS injected', 'ok');
        }

        /* Drag CSS float */
        (() => {
            const fl = document.getElementById('css-float');
            const dh = document.getElementById('css-drag');
            let drag = false, ox = 0, oy = 0;
            dh.addEventListener('mousedown', e => {
                drag = true;
                const r = fl.getBoundingClientRect();
                ox = e.clientX - r.left; oy = e.clientY - r.top;
                fl.style.right = 'auto'; fl.style.bottom = 'auto';
                fl.style.left = r.left + 'px'; fl.style.top = r.top + 'px';
                e.preventDefault();
            });
            document.addEventListener('mousemove', e => { if (!drag) return; fl.style.left = (e.clientX - ox) + 'px'; fl.style.top = (e.clientY - oy) + 'px'; });
            document.addEventListener('mouseup', () => drag = false);
        })();

        /* ─── CONFIG MODAL ─── */
        function openCfg() { document.getElementById('cfg-modal').classList.add('open'); }
        function closeCfg() { document.getElementById('cfg-modal').classList.remove('open'); }

        /* ─── MESSAGING ─── */
        function toIframe(msg) { try { iframe.contentWindow.postMessage(msg, '*'); } catch (e) { } }

        window.addEventListener('message', e => {
            const d = e.data; if (!d || !d.type) return;
            if (d.type === 'SELECTED') populate(d);
            if (d.type === 'DESELECTED') showEmpty();
            if (d.type === 'CHANGED') markUnsaved();
        });

        /* ─── UNSAVED ─── */
        function markUnsaved() {
            unsaved = true;
            document.getElementById('sb-saved').textContent = '● Unsaved changes';
            document.getElementById('sb-saved').style.color = 'var(--amber)';
        }

        /* ─── IFRAME INIT ─── */
        iframe.addEventListener('load', () => {
            injectEngine();
            setTimeout(() => toIframe({ type: 'SET_MODE', mode: curMode }), 200);
        });

        function injectEngine() {
            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;

                // ── inject BI icons into iframe ──
                if (!doc.getElementById('vc-bi')) {
                    const l = doc.createElement('link');
                    l.id = 'vc-bi'; l.rel = 'stylesheet';
                    l.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css';
                    doc.head.appendChild(l);
                }

                // ── inject styles ──
                if (!doc.getElementById('vc-style')) {
                    const s = doc.createElement('style');
                    s.id = 'vc-style';
                    s.textContent = `
        .vc-h{outline:1.5px dashed rgba(61,126,255,.45)!important;outline-offset:2px;cursor:crosshair!important}
        .vc-sel{outline:2.5px solid #3d7eff!important;outline-offset:3px}
        #vc-tip{
          position:fixed;z-index:2147483646;pointer-events:none;
          background:#3d7eff;color:#fff;font:700 10px/1 system-ui,sans-serif;
          text-transform:uppercase;letter-spacing:.5px;
          padding:3px 8px;border-radius:4px;box-shadow:0 4px 12px rgba(61,126,255,.45);
          opacity:0;transition:opacity .1s;white-space:nowrap;
        }
        #vc-tip.show{opacity:1}
        /* resize handle */
        #vc-resize{
          position:fixed;z-index:2147483647;
          pointer-events:none;display:none;
        }
        #vc-resize .rh{
          position:absolute;width:10px;height:10px;
          background:#fff;border:2px solid #3d7eff;border-radius:2px;
          pointer-events:all;cursor:se-resize;
        }
        #vc-resize .rh.tl{top:-5px;left:-5px;cursor:nw-resize}
        #vc-resize .rh.tr{top:-5px;right:-5px;cursor:ne-resize}
        #vc-resize .rh.bl{bottom:-5px;left:-5px;cursor:sw-resize}
        #vc-resize .rh.br{bottom:-5px;right:-5px;cursor:se-resize}
        #vc-resize .rm{
          position:absolute;right:-5px;top:50%;transform:translateY(-50%);
          width:10px;height:10px;
          background:#fff;border:2px solid #3d7eff;border-radius:2px;
          pointer-events:all;cursor:e-resize;
        }
        #vc-resize .bm{
          position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);
          width:10px;height:10px;
          background:#fff;border:2px solid #3d7eff;border-radius:2px;
          pointer-events:all;cursor:s-resize;
        }
        [contenteditable]{outline:none!important}
      `;
                    doc.head.appendChild(s);
                }

                // ── inject tip ──
                if (!doc.getElementById('vc-tip')) {
                    const t = doc.createElement('div'); t.id = 'vc-tip'; doc.body.appendChild(t);
                }

                // ── inject resize overlay ──
                if (!doc.getElementById('vc-resize')) {
                    const r = doc.createElement('div'); r.id = 'vc-resize';
                    r.innerHTML = `<div class="rh tl" data-dir="tl"></div><div class="rh tr" data-dir="tr"></div><div class="rh bl" data-dir="bl"></div><div class="rh br" data-dir="br"></div><div class="rm" data-dir="mr"></div><div class="bm" data-dir="bm"></div>`;
                    doc.body.appendChild(r);
                }

                // ── inject engine script ──
                if (doc.getElementById('vc-script')) doc.getElementById('vc-script').remove();
                const sc = doc.createElement('script'); sc.id = 'vc-script';
                sc.textContent = `
(function(){
  // ── STATE ──
  var mode       = 'inspect';
  var curEl      = null;      // currently selected element
  var ancestorChain = [];     // [el, parent, grandparent …] path array
  var anchorIdx  = 0;         // which ancestor is "active"
  var isEditing  = false;     // contenteditable on?
  var tip        = document.getElementById('vc-tip');
  var resizeBox  = document.getElementById('vc-resize');
  var resizing   = false;
  var resDir     = '';
  var resStartX  = 0, resStartY = 0, resStartW = 0, resStartH = 0;

  // selectable tags — broad but excludes html/head/body/script/style
  var TAGS = 'h1,h2,h3,h4,h5,h6,p,span,a,img,button,label,li,td,th,blockquote,figure,figcaption,div,section,article,header,footer,nav,aside,main,form,input,textarea,select,ul,ol,table,thead,tbody,tr';

  // ── UTILS ──
  function sendUp(obj){ window.parent.postMessage(obj,'*'); }

  function getCS(el,prop){ return window.getComputedStyle(el)[prop]||''; }

  function buildAncestors(el){
    var chain=[]; var n=el;
    while(n && n!==document.body && n!==document.documentElement){
      if(n.nodeType===1) chain.push(n);
      n=n.parentNode;
    }
    return chain; // [el, parent, grandparent …]
  }

  function breadcrumbTags(chain){
    return chain.map(function(n){ return n.tagName; }).reverse(); // shallowest first
  }

  function elData(el){
    var cs  = window.getComputedStyle(el);
    var isImg = el.tagName==='IMG';
    var isA   = el.tagName==='A';
    var chain = buildAncestors(el);
    ancestorChain = chain;
    anchorIdx = 0; // el is at index 0 = deepest = rightmost in breadcrumb
    var bcTags = breadcrumbTags(chain); // shallowest→deepest
    return {
      type:       'SELECTED',
      tag:        el.tagName,
      classes:    (el.className||'').replace(/\\bvc-[\\w-]+\\b/g,'').trim(),
      breadcrumb: bcTags,
      bcActive:   bcTags.length-1, // deepest = rightmost
      src:        isImg ? (el.src||'')  : '',
      imgW:       isImg ? (el.style.width  || el.getAttribute('width')  || '') : '',
      imgH:       isImg ? (el.style.height || el.getAttribute('height') || '') : '',
      alt:        isImg ? (el.alt||'') : '',
      href:       isA   ? (el.getAttribute('href')||'') : '',
      blank:      isA   ? (el.target==='_blank') : false,
      fontSize:   el.style.fontSize   || cs.fontSize  || '',
      fontWeight: el.style.fontWeight || cs.fontWeight|| '',
      color:      el.style.color      || cs.color     || '',
      background: el.style.backgroundColor || cs.backgroundColor || '',
      padding:    el.style.padding    || '',
      margin:     el.style.margin     || '',
    };
  }

  // ── RESIZE OVERLAY ──
  function positionResize(el){
    if(el.tagName!=='IMG'){ resizeBox.style.display='none'; return; }
    var r=el.getBoundingClientRect();
    resizeBox.style.display='block';
    resizeBox.style.left   = r.left+'px';
    resizeBox.style.top    = r.top +'px';
    resizeBox.style.width  = r.width +'px';
    resizeBox.style.height = r.height+'px';
  }

  function hideResize(){ resizeBox.style.display='none'; }

  // ── SELECTION ──
  function selectEl(el){
    if(!el) return;
    deselect(false);
    curEl = el;
    el.classList.add('vc-sel');
    positionResize(el);
    sendUp(elData(el));
  }

  function deselect(notify){
    if(curEl){
      curEl.classList.remove('vc-sel');
      curEl.classList.remove('vc-h');
      if(isEditing){
        curEl.contentEditable='false';
        curEl.removeAttribute('contenteditable');
        isEditing=false;
      }
    }
    curEl=null; ancestorChain=[];
    hideResize();
    if(notify!==false) sendUp({type:'DESELECTED'});
  }

  // ── SETUP ELEMENT ──
  function setup(el){
    if(!el||el===tip||el===resizeBox||el.closest('#vc-resize')) return;
    if(el.id==='vc-tip'||el.id==='vc-resize'||el.id==='vc-style'||el.id==='vc-script') return;
    if(el.dataset.vcDone) return;
    el.dataset.vcDone='1';

    el.addEventListener('mouseover',function(e){
      if(mode!=='inspect') return;
      e.stopPropagation();
      if(curEl===el) return;
      el.classList.add('vc-h');
      if(tip){ tip.textContent=el.tagName; tip.classList.add('show'); }
    },true);

    el.addEventListener('mousemove',function(e){
      if(mode!=='inspect'||!tip) return;
      tip.style.left=(e.clientX+12)+'px';
      tip.style.top =(e.clientY-28)+'px';
    },true);

    el.addEventListener('mouseout',function(){
      el.classList.remove('vc-h');
      if(tip) tip.classList.remove('show');
    },true);

    el.addEventListener('click',function(e){
      if(mode!=='inspect') return;
      // Prevent link navigation / form submit
      if(el.tagName==='A'||el.tagName==='BUTTON'||el.tagName==='INPUT'||el.tagName==='SELECT') e.preventDefault();

      // ── CLICK LOGIC ──
      // If clicking the already-selected element and edit mode is on → allow normal cursor
      if(el===curEl && isEditing) return;
      // If clicking the already-selected element → just update position
      if(el===curEl){ positionResize(el); return; }

      // Stop event from bubbling to parent (parent-child fix)
      e.stopPropagation();

      selectEl(el);
    },true);

    el.addEventListener('input',function(){
      syncTpl(el);
      sendUp({type:'CHANGED'});
    });
  }

  // ── TEMPLATE SYNC ──
  function syncTpl(liveEl, fullRoot){
    var root = liveEl.closest('[data-source-tpl]');
    if(!root) return;
    var tplId  = root.getAttribute('data-source-tpl');
    var tplIdx = parseInt(root.getAttribute('data-tpl-idx')||'0');
    var tpl    = document.getElementById(tplId);
    if(!tpl||!tpl.content) return;
    var tplRoot = tpl.content.children[tplIdx];
    if(!tplRoot) return;

    if(fullRoot){
      var clone=root.cloneNode(true);
      clone.querySelectorAll('[contenteditable]').forEach(function(e){e.removeAttribute('contenteditable');});
      clone.querySelectorAll('[data-vc-done]').forEach(function(e){delete e.dataset.vcDone;});
      clone.querySelectorAll('[data-source-tpl]').forEach(function(e){e.removeAttribute('data-source-tpl');e.removeAttribute('data-tpl-idx');});
      tplRoot.innerHTML=clone.innerHTML;
    } else {
      // path-based sync
      var path=[]; var cur=liveEl;
      while(cur!==root && cur){
        path.unshift(Array.from(cur.parentNode.children).indexOf(cur));
        cur=cur.parentNode;
      }
      var t=tplRoot;
      for(var i=0;i<path.length;i++){ t=t.children[path[i]]; if(!t) return; }
      if(liveEl.tagName==='IMG') t.src=liveEl.src;
      else t.innerHTML=liveEl.innerHTML;
    }
  }

  // ── INIT ALL ──
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

  // deselect on body click (only when not clicking a builder element)
  document.addEventListener('click',function(e){
    if(!curEl) return;
    if(curEl.contains(e.target)) return;
    if(resizeBox.contains(e.target)) return;
    if(isEditing){ curEl.contentEditable='false'; curEl.removeAttribute('contenteditable'); syncTpl(curEl); isEditing=false; }
    deselect(true);
  });

  // ── RESIZE HANDLES ──
  resizeBox.querySelectorAll('.rh,.rm,.bm').forEach(function(h){
    h.addEventListener('mousedown',function(e){
      if(!curEl||curEl.tagName!=='IMG') return;
      resizing=true;
      resDir=h.dataset.dir;
      resStartX=e.clientX; resStartY=e.clientY;
      var r=curEl.getBoundingClientRect();
      resStartW=r.width; resStartH=r.height;
      e.stopPropagation(); e.preventDefault();
    });
  });

  document.addEventListener('mousemove',function(e){
    if(!resizing||!curEl) return;
    var dx=e.clientX-resStartX;
    var dy=e.clientY-resStartY;
    if(resDir==='br'||resDir==='tr'||resDir==='mr') curEl.style.width=(Math.max(20,resStartW+dx))+'px';
    if(resDir==='br'||resDir==='bl'||resDir==='bm') curEl.style.height=(Math.max(20,resStartH+dy))+'px';
    if(resDir==='tl'){ curEl.style.width=(Math.max(20,resStartW-dx))+'px'; curEl.style.height=(Math.max(20,resStartH-dy))+'px'; }
    if(resDir==='bl'){ curEl.style.width=(Math.max(20,resStartW-dx))+'px'; curEl.style.height=(Math.max(20,resStartH+dy))+'px'; }
    positionResize(curEl);
  });

  document.addEventListener('mouseup',function(){
    if(resizing && curEl){
      resizing=false;
      syncTpl(curEl);
      sendUp({type:'CHANGED'});
      // re-populate panel with new size
      var d=elData(curEl);
      d.imgW=curEl.style.width;
      d.imgH=curEl.style.height;
      sendUp(d);
    }
    resizing=false;
  });

  // keep resize box in sync when page scrolls
  document.addEventListener('scroll',function(){
    if(curEl&&curEl.tagName==='IMG') positionResize(curEl);
  },true);

  // ── BLOCK TEMPLATES ──
  var TPLS={
    h2:     '<h2 style="font-size:1.75rem;font-weight:700;margin-bottom:12px;">New Heading</h2>',
    h3:     '<h3 style="font-size:1.3rem;font-weight:600;margin-bottom:10px;">Sub-heading</h3>',
    p:      '<p style="line-height:1.7;margin-bottom:14px;">New paragraph — click to edit.</p>',
    button: '<button style="background:#3d7eff;color:#fff;padding:10px 22px;border-radius:8px;border:none;font-weight:600;cursor:pointer;">Click Here</button>',
    img:    '<img src="https://placehold.co/800x400/e2e8f0/64748b?text=Image" alt="Placeholder" style="width:100%;border-radius:10px;display:block;margin-bottom:14px;">',
    div:    '<div style="padding:24px;background:#f8fafc;border-radius:10px;margin-bottom:14px;"><p>New section</p></div>',
    a:      '<a href="#" style="color:#3d7eff;text-decoration:underline;">New link</a>',
    span:   '<span>New span text</span>',
  };

  // ── MESSAGE HANDLER ──
  window.addEventListener('message',function(e){
    var d=e.data; if(!d||!d.type) return;

    if(d.type==='SET_MODE'){
      mode=d.mode;
      if(d.mode==='navigate') deselect(false);
    }

    // ── SELECT ANCESTOR (breadcrumb click) ──
    if(d.type==='SELECT_ANCESTOR'){
      // breadcrumb is built shallowest→deepest, chain is deepest→shallowest
      // d.index is the position in breadcrumb (0=shallowest)
      var chainIdx = ancestorChain.length-1-d.index;
      var target   = ancestorChain[chainIdx];
      if(target) selectEl(target);
    }

    // ── SET EDIT MODE ──
    if(d.type==='SET_EDIT'){
      if(!curEl||curEl.tagName==='IMG') return;
      isEditing=d.editMode;
      if(isEditing){
        curEl.contentEditable='true';
        curEl.focus();
      } else {
        curEl.contentEditable='false';
        curEl.removeAttribute('contenteditable');
        syncTpl(curEl);
      }
    }

    // ── RICH TEXT CMD ──
    if(d.type==='RT_CMD'){
      if(!curEl||!isEditing) return;
      document.execCommand(d.cmd,false,d.val||null);
      syncTpl(curEl);
      sendUp({type:'CHANGED'});
    }

    // ── APPLY CHANGES ──
    if(d.type==='APPLY'){
      if(!curEl) return;
      var s=curEl.style;
      var isImg=curEl.tagName==='IMG';
      var isA  =curEl.tagName==='A';

      if(isImg){
        if(d.src)  curEl.src=d.src;
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
        if(d.fontSize)   s.fontSize  =d.fontSize;
        if(d.fontWeight) s.fontWeight=d.fontWeight;
      }
      if(d.color)      s.color          =d.color;
      if(d.background) s.backgroundColor=d.background;
      if(d.padding)    s.padding        =d.padding;
      if(d.margin)     s.margin         =d.margin;
      if(d.classes!==undefined){
        var keep=Array.from(curEl.classList).filter(function(c){return c.startsWith('vc-');});
        curEl.className=(d.classes+' '+keep.join(' ')).trim();
      }
      syncTpl(curEl, isImg);
      sendUp({type:'CHANGED'});
    }

    // ── DELETE ──
    if(d.type==='DELETE'){
      if(!curEl) return;
      var root=curEl.closest('[data-source-tpl]');
      curEl.remove();
      curEl=null;
      hideResize();
      ancestorChain=[];
      if(root) syncTpl(root,true);
      sendUp({type:'DESELECTED'});
    }

    // ── DUPLICATE ──
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

    // ── INSERT BLOCK ──
    if(d.type==='INSERT'){
      if(!curEl) return;
      var tmp=document.createElement('div');
      tmp.innerHTML=d.html;
      var newEl=tmp.firstElementChild;
      if(!newEl) return;
      if(d.position==='before')  curEl.before(newEl);
      else if(d.position==='inside') curEl.appendChild(newEl);
      else curEl.after(newEl);
      var root=curEl.closest('[data-source-tpl]');
      if(root) syncTpl(root,true);
      setup(newEl);
      newEl.querySelectorAll&&newEl.querySelectorAll(TAGS).forEach(setup);
      selectEl(newEl);
    }

    // ── INJECT CSS ──
    if(d.type==='INJECT_CSS'){
      var st=document.getElementById('vc-css');
      if(!st){ st=document.createElement('style'); st.id='vc-css'; document.head.appendChild(st); }
      if(d.sel) st.textContent+='\\n'+d.sel+'{'+d.css+'}';
      else st.textContent+='\\n'+d.css;
    }
  });
})();
    `;
                doc.body.appendChild(sc);
            } catch (err) {
                console.warn('[VC] inject error', err);
                toast('Cross-origin iframe — some features limited', 'err', 4000);
            }
        }

        /* ─── SAVE ─── */
        function saveViaForm() {
            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                const form = document.getElementById('save-form');

                // config inputs
                const ci = document.getElementById('conf-inputs');
                ci.innerHTML = '';
                document.querySelectorAll('[data-conf-key]').forEach(a => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = a.getAttribute('data-conf-key'); i.value = a.value;
                    ci.appendChild(i);
                });

                // clone & clean
                const clean = doc.documentElement.cloneNode(true);
                ['#vc-style', '#vc-script', '#vc-tip', '#vc-resize', '#vc-bi'].forEach(s => clean.querySelector(s)?.remove());
                const appRoot = clean.querySelector('#app-root') || clean.querySelector('.js-grid');
                if (appRoot) appRoot.innerHTML = '';
                clean.querySelectorAll('.vc-sel,.vc-h').forEach(e => { e.classList.remove('vc-sel', 'vc-h'); });
                clean.querySelectorAll('[contenteditable]').forEach(e => e.removeAttribute('contenteditable'));
                clean.querySelectorAll('[data-vc-done]').forEach(e => delete e.dataset.vcDone);
                clean.querySelectorAll('[data-source-tpl]').forEach(e => { e.removeAttribute('data-source-tpl'); e.removeAttribute('data-tpl-idx'); });

                document.getElementById('html-payload').value = clean.outerHTML;
                form.submit();

                unsaved = false;
                document.getElementById('sb-saved').textContent = 'Saved ✓';
                document.getElementById('sb-saved').style.color = 'var(--green)';
                toast('Page saved', 'ok');
            } catch (err) {
                toast('Save failed — see console', 'err');
                console.error('[VC] save error', err);
            }
        }

        /* ─── KEYBOARD ─── */
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey)) {
                if (e.key === 's') { e.preventDefault(); saveViaForm(); }
                if (e.key === '1') { e.preventDefault(); setMode('navigate'); }
                if (e.key === '2') { e.preventDefault(); setMode('inspect'); }
            }
            // Delete key removes selected element
            if ((e.key === 'Delete' || e.key === 'Backspace') && e.target === document.body) {
                // only if an element is selected and we're NOT in edit mode
                if (!editMode) { delEl(); }
            }
        });

        window.addEventListener('beforeunload', e => {
            if (unsaved) { e.preventDefault(); return (e.returnValue = ''); }
        });

        /* ─── INIT ─── */
        setMode('inspect');
        setVP('desktop');
    </script>
</main>