<?php
/**
 * 1. FIND THE FILE
 * We look for any .php file that contains our signature constant "__SITE_TITLE__"
 */
function findConfigFile($directory = '.') {
    $files = glob($directory . "/*.php");
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if (strpos($content, '__SITE_TITLE__') !== false) {
            return $file;
        }
    }
    return null;
}

$configFile = findConfigFile();

if (!$configFile) {
    die("Config file not found. Please ensure a file with '__SITE_TITLE__' exists.");
}

/**
 * 2. SCRAPE THE CONTENT
 * We use Regex to pull the key and value from define("KEY", "VALUE");
 */
$content = file_get_contents($configFile);
preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);

$settings = [];
if (!empty($matches[1])) {
    foreach ($matches[1] as $index => $key) {
        $settings[$key] = $matches[2][$index];
    }
}

/**
 * 3. CATEGORIZE THE DATA
 */
$shopItems = [];
$storeItems = [];

foreach ($settings as $key => $value) {
    // If it has "SHOP" in the name, put it in the Shop category
    if (strpos($key, 'SHOP') !== false) {
        $shopItems[$key] = $value;
    } else {
        $storeItems[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Config Manager</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; background: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        section { margin-bottom: 30px; }
        h3 { border-bottom: 2px solid #eee; padding-bottom: 5px; color: #333; }
        .field { margin-bottom: 15px; }
        label { display: block; font-weight: bold; font-size: 0.8em; color: #666; }
        input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h2>Configuration Scraper</h2>
    <p><small>Editing file: <strong><?php echo htmlspecialchars($configFile); ?></strong></small></p>

    <form method="POST">
        
        <section>
            <h3>General Store Settings</h3>
            <?php foreach ($storeItems as $key => $value): ?>
                <div class="field">
                    <label><?php echo str_replace('_', ' ', trim($key, '_')); ?></label>
                    <input type="text" name="config[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                </div>
            <?php endforeach; ?>
        </section>

        <section>
            <h3>Shop Specific Items</h3>
            <?php foreach ($shopItems as $key => $value): ?>
                <div class="field">
                    <label><?php echo str_replace('_', ' ', trim($key, '_')); ?></label>
                    <input type="text" name="config[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                </div>
            <?php endforeach; ?>
        </section>

        <button type="submit">Update Configuration</button>
    </form>
</div>

</body>
</html>