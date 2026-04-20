<?php
// offline.php — Shown when user is offline and page isn't cached
http_response_code(503);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#7a1aab">
  <title>You're Offline — Varsity Market</title>
  <link rel="icon" href="/assets/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #111827;
      color: #e2e8f0;
      font-family: 'Inter', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .offline-container {
      text-align: center;
      padding: 2rem;
      max-width: 420px;
    }
    .offline-icon {
      font-size: 5rem;
      margin-bottom: 1.5rem;
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: .6; transform: scale(.95); }
    }
    h1 {
      font-size: 1.75rem;
      font-weight: 800;
      margin-bottom: .75rem;
      background: linear-gradient(135deg, #7a1aab, #a855f7);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    p {
      color: #94a3b8;
      font-size: 1rem;
      line-height: 1.6;
      margin-bottom: .5rem;
    }
    .retry-btn {
      display: inline-block;
      margin-top: 2rem;
      padding: .75rem 2rem;
      background: linear-gradient(135deg, #7a1aab, #a855f7);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: transform .15s, box-shadow .2s;
    }
    .retry-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(122, 26, 171, .4);
    }
    .retry-btn:active { transform: scale(.97); }
  </style>
</head>
<body>
<div class="offline-container">
  <div class="offline-icon">📡</div>
  <h1>You're Offline</h1>
  <p>No internet connection detected.</p>
  <p>Don't worry — any pending actions will sync automatically when you reconnect.</p>
  <button class="retry-btn" onclick="window.location.reload()">Try Again</button>
</div>
</body>
</html>
