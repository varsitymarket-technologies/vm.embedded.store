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
    <title>Embedded Store Engine</title>
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
</body>

</html>