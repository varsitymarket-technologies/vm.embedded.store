<?php

// Source - https://stackoverflow.com/q/1053424
// Posted by Abs, modified by community. See post 'Timeline' for change history
// Retrieved 2026-07-25, License - CC BY-SA 4.0

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Example Usage (for demonstration purposes - replace with your actual usage)
@include_once dirname(dirname(__FILE__)) . "/config.php";
$apiToken = '60742338502292543628447602016492';

$engine_tokens = $_SERVER['__ENGINE_TOKENS__'];
$engine_secrets = $_SERVER['__ENGINE_SECRETS__'];
// Provide a default source if generic
$engine_source = $_SERVER['__ENGINE_SOURCE__'];
$apiUrl = $engine_source;
$directoryToUpload = dirname(__DIR__) . '/sites/eros.se';
$domain = 'september.app.varsitymarket.co.za';

// deploy.php

$sourceDir = $directoryToUpload .'';
$apiKey = 'YOUR_SECRET_API_KEY_123';

if (!is_dir($sourceDir)) {
    die("Source directory does not exist.\n");
}

echo "Starting deployment for $domain...\n";

// Recursively iterate through the directory
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$successCount = 0;
$failCount = 0;

foreach ($iterator as $file) {
    if ($file->isDir()) continue; // Skip directories, they are created automatically by the server

    $filePath = $file->getRealPath();
    
    // Calculate the relative path to send to the server
    $relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
    $relativePath = str_replace('\\', '/', $relativePath); // Normalize for Windows clients

    // Prepare the multipart form data
    $postFields = [
        'domain' => $domain,
        'relative_path' => $relativePath,
        'file' => new CURLFile($filePath)
    ];

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30-second timeout per file

    // Execute upload
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Handle response
    if ($httpCode === 200) {
        echo "[SUCCESS] Uploaded: $relativePath\n";
        $successCount++;
    } else {
        echo "[FAILED] $relativePath (HTTP $httpCode)\n";
        if ($curlError) {
            echo "         cURL Error: $curlError\n";
        } else {
            echo "         Server Response: $response\n";
        }
        $failCount++;
    }
}

echo "\nDeployment Complete.\n";
echo "Successfully uploaded: $successCount files.\n";
echo "Failed: $failCount files.\n";