<?php
#   TITLE   : Application Interface   
#   DESC    : The Interface handling the Application GUI 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varsity Market</title>
    <link rel="stylesheet" href="/assets/style.css">

    <meta name="description" content="Varsity Market — Premium Embedded Store Engine">
    <meta name="keywords" content="store, ecommerce, embedded, varsity market">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Theme Color -->
    <meta name="theme-color" content="#7a1aab">

    <!-- iOS PWA Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Embedded Admin Store ">

    <!-- Favicons -->
    <link href="/assets/favicon.png" rel="icon">
    <link href="/assets/favicon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php if (isset($_GET['source']) && $_GET['source'] == "pwa") { ?>
        <?php @include_once "pages/page.mobile.php"; ?>
    <?php } else { ?>
        <?php @include_once "pages.php"; ?>
    <?php } ?>

    <!-- PWA Install Card (hidden until install prompt fires) -->
    <div id="vm-install-card"
        style="display:none; position:fixed; bottom:1.5rem; right:1.5rem; background:linear-gradient(135deg,#1e293b,#0f172a); border:1px solid #334155; border-radius:14px; padding:1.25rem 1.5rem; z-index:9999; box-shadow:0 12px 40px rgba(0,0,0,.5); font-family:'Inter',sans-serif; max-width:300px;">
        <p style="color:#e2e8f0; font-size:.95rem; margin:0 0 .75rem;">Install <strong>Varsity Market</strong> for the
            best experience.</p>
        <button id="vm-install-btn"
            style="background:linear-gradient(135deg,#7a1aab,#a855f7); color:#fff; border:none; padding:.6rem 1.25rem; border-radius:8px; font-size:.9rem; font-weight:600; cursor:pointer;">Install
            App</button>
    </div>

    <!-- PWA Scripts -->
    <script src="/assets/pwa.js"></script>
</body>

</html>