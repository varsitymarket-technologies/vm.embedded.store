
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorry to see you leave</title>
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
<?php
// remove all session variables
session_unset();

// destroy the session
session_destroy();
@include 'includes/notification.php';

constructNotificationModal("Logged Out", "You have been logged out of the system.");
//print_r($_SESSION); 

#echo "<script>window.location='/home/'</script>"
?>

<?php

?>
<!-- AUTH SECTION -->
<div id="auth-container" class="container center-content">
    <div class="auth-card" style="margin: 10px;">
        <img src="/assets/favicon.png">


        <h1>You have been logged out.</h1>
        <p>
           Sorry to see you leave.
        </p>
    </div>
</div>

</body>
</html>
