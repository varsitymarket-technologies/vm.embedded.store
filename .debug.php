<?php
// 1. Get the URL input (via GET or POST)
$raw_url = $_GET['url'] ?? '';
$clean_url = filter_var($raw_url, FILTER_SANITIZE_URL);

// Simple validation
$is_valid = filter_var($clean_url, FILTER_VALIDATE_URL);

// Function to generate the snippets
function generateSnippets($url) {
    // Standard HTML Best Practice
    // - rel="noopener" prevents security risks and improves performance
    $html_snippet = '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer" class="link-btn">Visit Website</a>';

    // IFrame Best Practice
    // - loading="lazy" for performance
    // - sandbox for security (allows scripts but restricts top-level navigation)
    // - title for Accessibility (WCAG)
    $iframe_snippet = '<div class="iframe-container">' . "\n" .
                      '  <iframe ' . "\n" .
                      '    src="' . htmlspecialchars($url) . '" ' . "\n" .
                      '    title="Embedded Content" ' . "\n" .
                      '    width="100%" ' . "\n" .
                      '    height="450" ' . "\n" .
                      '    style="border:0;" ' . "\n" .
                      '    loading="lazy" ' . "\n" .
                      '    allowfullscreen ' . "\n" .
                      '    sandbox="allow-scripts allow-same-origin allow-forms">' . "\n" .
                      '  </iframe>' . "\n" .
                      '</div>';

    return ['html' => $html_snippet, 'iframe' => $iframe_snippet];
}

$codes = $is_valid ? generateSnippets($clean_url) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Code Export Tool</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; line-height: 1.6; background: #f4f4f9; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        textarea { width: 100%; height: 100px; font-family: monospace; padding: 10px; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px; }
        label { font-weight: bold; display: block; margin-top: 15px; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <h2>🔗 Embed Code Generator</h2>
    
    <form method="GET">
        <input type="url" name="url" placeholder="https://example.com" required style="padding: 10px; width: 70%;">
        <button type="submit" style="padding: 10px 20px; cursor: pointer;">Generate Code</button>
    </form>

    <?php if ($codes): ?>
        <div class="card">
            <h3>Standard HTML Link</h3>
            <p>Best for emails, blog posts, and Google Sites buttons.</p>
            <textarea readonly><?php echo htmlspecialchars($codes['html']); ?></textarea>
        </div>

        <div class="card">
            <h3>Responsive IFrame Embed</h3>
            <p>Best for embedding full apps or pages.</p>
            <textarea readonly><?php echo htmlspecialchars($codes['iframe']); ?></textarea>
            
            <h4>The CSS you need for responsiveness:</h4>
            <textarea readonly>.iframe-container { position: relative; width: 100%; overflow: hidden; border-radius: 8px; }</textarea>
        </div>
    <?php elseif ($raw_url): ?>
        <p class="error">Invalid URL. Please include http:// or https://</p>
    <?php endif; ?>

</body>
</html>