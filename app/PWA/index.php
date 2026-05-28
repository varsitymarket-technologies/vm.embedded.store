<?php
// index.php — Main entry point
session_start();

$pageTitle = 'PHP PWA Demo';
$notes = [];

// Simple in-session note storage (swap for a DB in production)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'])) {
    $_SESSION['notes'][] = htmlspecialchars(trim($_POST['note']));
}
$notes = $_SESSION['notes'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#6366f1">
  <meta name="description" content="A PHP-powered Progressive Web App demo">

  <title><?= $pageTitle ?></title>

  <!-- PWA manifest -->
  <link rel="manifest" href="/manifest.json">

  <!-- iOS PWA support -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="PWA Demo">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">

  <link rel="stylesheet" href="/css/app.css">
</head>
<body>

<header class="app-header">
  <h1>⚡ PHP PWA</h1>
  <span id="online-badge" class="badge">Online</span>
</header>

<main class="container">

  <!-- Note form (also works offline via Background Sync) -->
  <section class="card">
    <h2>Add a Note</h2>
    <form id="note-form" action="/index.php" method="POST">
      <input type="text" name="note" id="note-input" placeholder="Type something…" required>
      <button type="submit">Save</button>
    </form>
  </section>

  <!-- Notes list -->
  <section class="card">
    <h2>Your Notes (<?= count($notes) ?>)</h2>
    <?php if (empty($notes)): ?>
      <p class="empty">No notes yet. Add one above!</p>
    <?php else: ?>
      <ul id="notes-list">
        <?php foreach (array_reverse($notes) as $note): ?>
          <li><?= $note ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- API demo -->
  <section class="card">
    <h2>Live API Fetch</h2>
    <button id="fetch-btn">Fetch from API</button>
    <pre id="api-output">Press the button…</pre>
  </section>

  <!-- Install prompt -->
  <section class="card" id="install-card" style="display:none">
    <h2>Install App</h2>
    <p>Add this app to your home screen for the best experience.</p>
    <button id="install-btn">Install</button>
  </section>

</main>

<script src="/js/app.js"></script>
</body>
</html>
