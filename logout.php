<?php
#   TITLE   : Application Logout
#   DESC    : Shown when user is logged out
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.2
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/06/18

// Unified session termination at the top of the file
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out | Varsity Market</title>

    <!-- Original Meta & Links -->
    <meta name="description" content="Varsity Market — Premium Embedded Store Engine">
    <meta name="keywords" content="store, ecommerce, embedded, varsity market">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#7a1aab">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Embedded Admin Store ">

    <link href="/assets/favicon.png" rel="icon">
    <link href="/assets/favicon.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Tailwind CSS (via CDN for immediate styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: '#7a1aab',
                        brandHover: '#601289'
                    }
                }
            }
        }
    </script>
</head>

<body
    class="bg-[#1a1b1b] text-gray-800 font-sans h-screen flex flex-col items-center justify-center antialiased selection:bg-brand selection:text-white">

    <!-- Main Auth Container -->
    <div class="w-full max-w-sm px-6 text-center">

        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <img src="/assets/favicon.png" alt="Varsity Market" class="w-12 h-12 rounded-sm shadow-sm">
        </div>

        <!-- Typography -->
        <h1 class="text-3xl text-white font-bold tracking-tight mb-2 text-gray-900">You have been logged out</h1>
        <p class="text-gray-500 mb-8 text-sm">Sorry to see you leave. Have a great day.</p>

        <!-- Actions -->
        <div class="flex flex-col gap-3">
            <a href="/"
                class="w-full flex justify-center items-center py-3 px-4 rounded-md text-white bg-brand hover:bg-brandHover focus:ring-2 focus:ring-offset-2 focus:ring-brand font-semibold transition-colors duration-200">
                Log back in
            </a>
            <a href="/home/" class="text-white hover:underline text-sm font-medium mt-2">
                Return to homepage
            </a>
        </div>

    </div>

</body>

</html>