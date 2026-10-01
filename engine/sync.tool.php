<?php
/**
 * GitHub push webhook for deploying this checkout.
 * Configure VM_GITHUB_WEBHOOK_SECRET in the PHP/FPM environment before use.
 */

declare(strict_types=1);

const VM_SYNC_DEFAULT_REPOSITORY = 'varsitymarket-technologies/vm.embedded.store';
const VM_SYNC_DEFAULT_BRANCH = 'master';
const VM_SYNC_MAX_PAYLOAD_BYTES = 1048576;

function vm_sync_respond(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}

function vm_sync_run(array $command, string $directory): array
{
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $directory);

    if (!is_resource($process)) {
        throw new RuntimeException('Could not start git.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        error_log('GitHub sync git command failed: ' . trim((string) $stderr));
        throw new RuntimeException('Git operation failed.');
    }

    return [$exitCode, trim((string) $stdout)];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    vm_sync_respond(405, 'Method not allowed.');
}

$secret = (string) getenv('VM_GITHUB_WEBHOOK_SECRET');
if ($secret === '') {
    error_log('GitHub sync is disabled: VM_GITHUB_WEBHOOK_SECRET is not configured.');
    vm_sync_respond(503, 'Webhook is not configured.');
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > VM_SYNC_MAX_PAYLOAD_BYTES) {
    vm_sync_respond(413, 'Payload too large.');
}

$payload = file_get_contents('php://input');
if (!is_string($payload) || strlen($payload) > VM_SYNC_MAX_PAYLOAD_BYTES) {
    vm_sync_respond(400, 'Could not read webhook payload.');
}

$signature = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
$expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if ($signature === '' || !hash_equals($expectedSignature, $signature)) {
    vm_sync_respond(401, 'Invalid webhook signature.');
}

$event = (string) ($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '');
if ($event === 'ping') {
    vm_sync_respond(200, 'Webhook connected.');
}
if ($event !== 'push') {
    vm_sync_respond(202, 'Event ignored.');
}

$data = json_decode($payload, true);
if (!is_array($data)) {
    vm_sync_respond(400, 'Invalid JSON payload.');
}

$repository = (string) (getenv('VM_GITHUB_REPOSITORY') ?: VM_SYNC_DEFAULT_REPOSITORY);
$branch = (string) (getenv('VM_GITHUB_BRANCH') ?: VM_SYNC_DEFAULT_BRANCH);
if (!preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repository)
    || !preg_match('/^[A-Za-z0-9._\/-]+$/', $branch)
    || strpos($branch, '..') !== false
    || strpos($branch, '//') !== false) {
    error_log('GitHub sync has invalid repository or branch configuration.');
    vm_sync_respond(500, 'Invalid sync configuration.');
}

if (($data['repository']['full_name'] ?? '') !== $repository) {
    vm_sync_respond(403, 'Repository does not match.');
}
if (($data['ref'] ?? '') !== 'refs/heads/' . $branch) {
    vm_sync_respond(202, 'Branch ignored.');
}

$repoPath = dirname(__DIR__);
$lockPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR . 'vm-github-sync-' . hash('sha256', $repoPath) . '.lock';
$lock = fopen($lockPath, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    if (is_resource($lock)) {
        fclose($lock);
    }
    vm_sync_respond(409, 'A sync is already running.');
}

try {
    vm_sync_run(['git', 'rev-parse', '--is-inside-work-tree'], $repoPath);
    vm_sync_run(['git', 'fetch', '--no-tags', 'origin', 'refs/heads/' . $branch], $repoPath);
    vm_sync_run(['git', 'reset', '--hard', 'FETCH_HEAD'], $repoPath);
    error_log('GitHub sync completed for ' . $repository . ' (' . $branch . ').');
    vm_sync_respond(200, 'Sync successful.');
} catch (Throwable $error) {
    error_log('GitHub sync failed: ' . $error->getMessage());
    vm_sync_respond(500, 'Sync failed. Check the server error log.');
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
