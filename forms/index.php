<?php
@include_once dirname(__DIR__) . "/scripts.php";

$hash = trim((string) ex(2));
if ($hash === '') {
    http_response_code(404);
    echo "Invalid form route.";
    exit;
}

$siteFolder = null;
$sitesPath = dirname(__DIR__) . '/sites';
if (is_dir($sitesPath)) {
    foreach (scandir($sitesPath) as $siteFolderName) {
        if ($siteFolderName === '.' || $siteFolderName === '..') {
            continue;
        }
        if (!is_dir($sitesPath . '/' . $siteFolderName)) {
            continue;
        }
        if (hash('sha256', $siteFolderName) === $hash) {
            $siteFolder = $siteFolderName;
            break;
        }
    }
}

if (empty($siteFolder)) {
    http_response_code(404);
    echo "Form gateway not found.";
    exit;
}

// Simple public redirect to the form handler with website context.
$submitUrl = '/api/form_handler.php?website_hash=' . urlencode($hash);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Form</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f7fb; color: #111; margin: 0; padding: 24px; }
        .panel { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 28px; box-shadow: 0 18px 45px rgba(15, 23, 42, .08); }
        h1 { margin-top: 0; font-size: 28px; }
        p { color: #475569; }
        label { display: block; margin: 18px 0 8px; font-weight: 600; }
        input, textarea { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 15px; }
        button { margin-top: 22px; background: #2563eb; color: #fff; padding: 14px 18px; border: none; border-radius: 10px; cursor: pointer; font-size: 15px; font-weight: 700; }
        .info { margin: 20px 0; padding: 14px 16px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; color: #312e81; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Submit your details</h1>
        <div class="info">This form routes to the form handler for website hash <strong><?= htmlspecialchars($hash); ?></strong>.</div>
        <form id="website-form" action="<?= htmlspecialchars($submitUrl); ?>" method="POST">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4"></textarea>
            <button type="submit">Send</button>
        </form>
    </div>
</body>
</html>
