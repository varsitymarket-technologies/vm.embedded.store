<?php
/**
 * GitHub Webhook Git Sync Script (No Secrets)
 */

// --- CONFIGURATION ---
define('REPO_PATH', '/path/to/your/local/repo');            // Path to your git repository
define('LOG_FILE', __DIR__ . '/git_sync.log');              // Path to log file
define('GIT_BRANCH', 'master');                             // Target branch

// --- 1. VALIDATE METHOD ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

logMessage("Sync triggered via Webhook POST.");

// --- 2. EXECUTE DEPLOYMENT ---
$commands = [
    "cd " . escapeshellarg(REPO_PATH),
    "git fetch origin " . escapeshellarg(GIT_BRANCH) . " 2>&1",
    "git reset --hard origin/" . escapeshellarg(GIT_BRANCH) . " 2>&1"
];

$output = [];
$exitCode = 0;

// Chain commands with && so it stops if one fails
$fullCommand = implode(' && ', $commands);
exec($fullCommand, $output, $exitCode);

// --- 3. LOG & RESPOND ---
if ($exitCode === 0) {
    logMessage("SUCCESS: Repository synced successfully.\n" . implode("\n", $output));
    http_response_code(200);
    echo "Sync successful.";
} else {
    logMessage("ERROR (Exit Code $exitCode): Sync failed.\n" . implode("\n", $output));
    http_response_code(500);
    echo "Sync failed. Check logs.";
}

// Helper function for logging
function logMessage($message) {
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents(LOG_FILE, "$timestamp $message\n", FILE_APPEND);
}