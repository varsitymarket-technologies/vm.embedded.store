<?php
// offline.php — Shown when user is offline and page isn't cached
http_response_code(503);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You're Offline</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="container offline-page">
  <div class="offline-icon">📡</div>
  <h1>You're Offline</h1>
  <p>No internet connection detected. Some features may be unavailable.</p>
  <p>Notes you add will sync automatically when you reconnect.</p>
  <button onclick="window.location.reload()">Try Again</button>
</div>
</body>
</html>
