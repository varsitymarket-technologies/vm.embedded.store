<?php
declare(strict_types=1);

/**
 * VMAppSync — single-file GitHub folder updater.
 *
 * Put this file in your application root and include it from your normal
 * bootstrap. It runs at most once per INTERVAL seconds per installation.
 *
 * IMPORTANT:
 * - Only files under ALLOWED_FOLDERS can be created or overwritten.
 * - Files missing from GitHub are NOT deleted.
 * - Existing files under approved folders are overwritten when different,
 *   including local modifications, as requested.
 * - Keep credentials, customer uploads, and environment/configuration files
 *   outside approved folders.
 * - This trusts the configured GitHub repository/branch. For stronger supply
 *   chain security, add signed-release verification before deploying widely.
 */

final class VMAppSync
{
    private const OWNER = 'varsitymarket-technologies';
    private const REPO = 'vm.embedded.store';
    private const BRANCH = 'master';

    /** Check for updates every 15 minutes. Change to 3600 for hourly checks. */
    private const INTERVAL = 900;

    /** Per-file and overall safety limits. */
    private const MAX_FILE_SIZE = 5242880;       // 5 MiB
    private const MAX_TREE_RESPONSE_SIZE = 15728640; // 15 MiB
    private const MAX_FILES_PER_RUN = 500;
    private const MAX_TOTAL_DOWNLOAD_SIZE = 26214400; // 25 MiB

    /**
     * Repository-relative folders whose contents are managed by this updater.
     * All nested files/folders are included. Use forward slashes.
     */
    private const ALLOWED_FOLDERS = [
        'api',
        'apk',
        'app',
        'assets',
        'engine',
        'forms',
        'pages',
        'includes',
        'img',
        'module',
        'services',
        'skel',
        'track',
        'vm-admin',
    ];

    private string $root;
    private string $stateDir;
    private string $logFile;

    public function __construct(string $applicationRoot)
    {
        $root = realpath($applicationRoot);
        if ($root === false || !is_dir($root)) {
            throw new RuntimeException('Application root is not a valid directory.');
        }

        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
        $this->stateDir = ($this->root) . DIRECTORY_SEPARATOR . '.engine-data';
        $this->logFile = $this->stateDir . DIRECTORY_SEPARATOR . 'sync.log';
    }

    public function run(): void
    {
        $this->ensureStateDirectory();

        $lockPath = $this->stateDir . DIRECTORY_SEPARATOR . 'sync.lock';
        $lock = @fopen($lockPath, 'c');
        if ($lock === false) {
            $this->log('Unable to open lock file; sync skipped.');
            return;
        }

        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                return; // Another request is already syncing.
            }

            $state = $this->readState();
            $lastAttempt = (int)($state['last_attempt'] ?? 0);

            if ($lastAttempt > 0 && (time() - $lastAttempt) < self::INTERVAL) {
                return;
            }

            // Save attempt time first, so GitHub/network errors do not cause
            // every page request to retry immediately.
            $state['last_attempt'] = time();
            $this->writeState($state);

            $this->syncFromGitHub($state);
        } catch (Throwable $e) {
            $this->log('Sync failed: ' . $e->getMessage());
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }

    private function syncFromGitHub(array $state): void
    {
        $apiBase = 'https://api.github.com/repos/' .
            rawurlencode(self::OWNER) . '/' . rawurlencode(self::REPO);

        $branchJson = $this->getJson($apiBase . '/branches/' . rawurlencode(self::BRANCH), 1048576);
        $commit = $branchJson['commit']['sha'] ?? null;

        if (!is_string($commit) || !preg_match('/^[a-f0-9]{40}$/i', $commit)) {
            throw new RuntimeException('GitHub did not return a valid branch commit SHA.');
        }

        $treeJson = $this->getJson(
            $apiBase . '/git/trees/' . rawurlencode($commit) . '?recursive=1',
            self::MAX_TREE_RESPONSE_SIZE
        );

        if (!empty($treeJson['truncated'])) {
            throw new RuntimeException('GitHub repository tree is truncated; refusing a partial sync.');
        }

        $entries = $treeJson['tree'] ?? null;
        if (!is_array($entries)) {
            throw new RuntimeException('GitHub response did not contain a repository tree.');
        }

        $selected = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $path = $entry['path'] ?? '';
            $type = $entry['type'] ?? '';
            $mode = $entry['mode'] ?? '';

            // Only regular Git blobs are eligible. Ignore directories, symlinks,
            // submodules, and other special Git objects.
            if ($type !== 'blob' || $mode === '120000' || $mode === '160000') {
                continue;
            }
            if (!is_string($path) || !$this->isAllowedPath($path) || !$this->isSafeRelativePath($path)) {
                continue;
            }

            $size = $entry['size'] ?? null;
            $sha = $entry['sha'] ?? null;
            if (!is_int($size) || $size < 0 || $size > self::MAX_FILE_SIZE) {
                $this->log('Skipped file with invalid/oversized size: ' . $path);
                continue;
            }
            if (!is_string($sha) || !preg_match('/^[a-f0-9]{40}$/i', $sha)) {
                $this->log('Skipped file with invalid Git blob SHA: ' . $path);
                continue;
            }

            $selected[$path] = ['size' => $size, 'sha' => strtolower($sha)];
        }

        if (count($selected) > self::MAX_FILES_PER_RUN) {
            throw new RuntimeException(
                'Approved file count (' . count($selected) . ') exceeds the per-run limit of ' .
                self::MAX_FILES_PER_RUN . '; refusing to sync.'
            );
        }

        if ($selected === []) {
            $this->log('No files found inside the configured approved folders at commit ' . $commit . '.');
            $state['last_success'] = time();
            $state['last_commit'] = $commit;
            $this->writeState($state);
            return;
        }

        $stageDir = $this->stateDir . DIRECTORY_SEPARATOR . 'staging-' . bin2hex(random_bytes(6));
        if (!@mkdir($stageDir, 0700, true) && !is_dir($stageDir)) {
            throw new RuntimeException('Unable to create staging directory.');
        }

        $staged = [];
        $totalDownloaded = 0;
        $changed = 0;

        try {
            // Download and verify every changed file before installing any file.
            foreach ($selected as $path => $meta) {
                $destination = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
                $this->assertNoSymlinkPath($destination);

                if (is_file($destination)) {
                    $localHash = $this->gitBlobSha1FromFile($destination);
                    if (hash_equals($meta['sha'], $localHash)) {
                        continue;
                    }
                } elseif (file_exists($destination)) {
                    $this->log('Skipped path that exists but is not a regular file: ' . $path);
                    continue;
                }

                $remaining = self::MAX_TOTAL_DOWNLOAD_SIZE - $totalDownloaded;
                if ($remaining <= 0 || $meta['size'] > $remaining) {
                    throw new RuntimeException('Total download limit exceeded; refusing this sync.');
                }

                $rawUrl = 'https://raw.githubusercontent.com/' .
                    rawurlencode(self::OWNER) . '/' .
                    rawurlencode(self::REPO) . '/' .
                    rawurlencode($commit) . '/' .
                    implode('/', array_map('rawurlencode', explode('/', $path)));

                $contents = $this->getBytes($rawUrl, min(self::MAX_FILE_SIZE, $remaining));
                if (strlen($contents) !== $meta['size']) {
                    throw new RuntimeException('Downloaded size mismatch for ' . $path . '.');
                }

                $actualSha = sha1("blob " . strlen($contents) . "\0" . $contents);
                if (!hash_equals($meta['sha'], $actualSha)) {
                    throw new RuntimeException('Git blob hash verification failed for ' . $path . '.');
                }

                $totalDownloaded += strlen($contents);
                $stagePath = $stageDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
                $this->makeDirectory(dirname($stagePath), 0700);

                if (@file_put_contents($stagePath, $contents, LOCK_EX) !== strlen($contents)) {
                    throw new RuntimeException('Unable to stage downloaded file: ' . $path);
                }

                $staged[$path] = [
                    'stage' => $stagePath,
                    'destination' => $destination,
                    'existed' => is_file($destination),
                ];
                $changed++;
            }

            if ($staged === []) {
                $this->log('Already up to date at commit ' . $commit . '.');
                $state['last_success'] = time();
                $state['last_commit'] = $commit;
                $this->writeState($state);
                return;
            }

            $backupRoot = $this->stateDir . DIRECTORY_SEPARATOR . 'backups' .
                DIRECTORY_SEPARATOR . $commit;
            $installed = [];

            try {
                foreach ($staged as $path => $item) {
                    $destination = $item['destination'];
                    $this->assertNoSymlinkPath($destination);
                    $this->makeDirectory(dirname($destination), 0755);

                    $backupPath = null;
                    if (is_file($destination)) {
                        $backupPath = $backupRoot . DIRECTORY_SEPARATOR .
                            str_replace('/', DIRECTORY_SEPARATOR, $path);
                        $this->makeDirectory(dirname($backupPath), 0700);
                        if (!@copy($destination, $backupPath)) {
                            throw new RuntimeException('Unable to back up existing file: ' . $path);
                        }
                    }

                    $mode = is_file($destination) ? (fileperms($destination) & 0777) : 0644;
                    if (!@rename($item['stage'], $destination)) {
                        // rename can fail on some shared-hosting filesystems.
                        $contents = @file_get_contents($item['stage']);
                        if ($contents === false ||
                            @file_put_contents($destination, $contents, LOCK_EX) !== strlen($contents)) {
                            throw new RuntimeException('Unable to install file: ' . $path);
                        }
                        @unlink($item['stage']);
                    }
                    @chmod($destination, $mode ?: 0644);

                    $installed[$path] = [
                        'destination' => $destination,
                        'backup' => $backupPath,
                    ];
                }
            } catch (Throwable $installError) {
                // Best-effort rollback of this run.
                foreach (array_reverse($installed, true) as $path => $item) {
                    if ($item['backup'] !== null && is_file($item['backup'])) {
                        @copy($item['backup'], $item['destination']);
                    } else {
                        @unlink($item['destination']);
                    }
                }
                throw $installError;
            }

            $state['last_success'] = time();
            $state['last_commit'] = $commit;
            $this->writeState($state);
            $this->log('Synced ' . count($staged) . ' file(s) from commit ' . $commit . '.');
        } finally {
            $this->removeDirectory($stageDir);
        }
    }

    private function isAllowedPath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        foreach (self::ALLOWED_FOLDERS as $folder) {
            $folder = trim(str_replace('\\', '/', $folder), '/');
            if ($folder === '') {
                continue;
            }

            // Do not allow a configured folder to escape the app root.
            if (str_contains($folder, '..')) {
                continue;
            }

            if (str_starts_with($path, $folder . '/')) {
                return true;
            }
        }

        return false;
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/') ||
            str_contains($path, '\\')) {
            return false;
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function assertNoSymlinkPath(string $absolutePath): void
    {
        $root = $this->root;
        $relative = ltrim(substr($absolutePath, strlen($root)), DIRECTORY_SEPARATOR);
        $current = $root;

        foreach (explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            if ($segment === '') {
                continue;
            }
            $current .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($current)) {
                throw new RuntimeException('Refusing to write through symbolic link: ' . $current);
            }
        }
    }

    private function getJson(string $url, int $maxBytes): array
    {
        $body = $this->getBytes($url, $maxBytes);
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from GitHub.');
        }
        return $data;
    }

    private function getBytes(string $url, int $maxBytes): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for GitHub sync.');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialise cURL.');
        }

        $body = '';
        $tooLarge = false;

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'VMAppSync/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, &$tooLarge, $maxBytes): int {
                if (strlen($body) + strlen($chunk) > $maxBytes) {
                    $tooLarge = true;
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($tooLarge) {
            throw new RuntimeException('Remote response exceeded the configured size limit.');
        }
        if ($ok === false || $status < 200 || $status >= 300) {
            throw new RuntimeException(
                'GitHub request failed (HTTP ' . $status . ')' .
                ($error !== '' ? ': ' . $error : '') . '.'
            );
        }

        return $body;
    }

    private function gitBlobSha1FromFile(string $path): string
    {
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Unable to inspect local file: ' . $path);
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read local file: ' . $path);
        }

        $ctx = hash_init('sha1');
        hash_update($ctx, 'blob ' . $size . "\0");
        while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            if ($chunk === false) {
                fclose($handle);
                throw new RuntimeException('Unable to hash local file: ' . $path);
            }
            hash_update($ctx, $chunk);
        }
        fclose($handle);

        return hash_final($ctx);
    }

    private function ensureStateDirectory(): void
    {
        if (!is_dir($this->stateDir) && !@mkdir($this->stateDir, 0700, true) && !is_dir($this->stateDir)) {
            throw new RuntimeException('Unable to create updater state directory.');
        }

        // Best-effort protection for Apache/cPanel. Also create an index file.
        $htaccess = $this->stateDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
        $index = $this->stateDir . DIRECTORY_SEPARATOR . 'index.php';
        if (!is_file($index)) {
            @file_put_contents($index, "<?php http_response_code(404); exit;\n");
        }

        @chmod($this->stateDir, 0700);
    }

    private function readState(): array
    {
        $path = $this->stateDir . DIRECTORY_SEPARATOR . 'state.json';
        if (!is_file($path)) {
            return [];
        }

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return [];
        }

        $state = json_decode($contents, true);
        return is_array($state) ? $state : [];
    }

    private function writeState(array $state): void
    {
        $path = $this->stateDir . DIRECTORY_SEPARATOR . 'state.json';
        $temp = $path . '.tmp';
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false || @file_put_contents($temp, $json, LOCK_EX) === false ||
            !@rename($temp, $path)) {
            @unlink($temp);
            throw new RuntimeException('Unable to save updater state.');
        }
        @chmod($path, 0600);
    }

    private function makeDirectory(string $directory, int $mode): void
    {
        if (is_dir($directory)) {
            return;
        }
        if (!@mkdir($directory, $mode, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create directory: ' . $directory);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory) || is_link($directory)) {
            return;
        }

        $items = @scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }

    private function log(string $message): void
    {
        $line = '[' . gmdate('Y-m-d H:i:s') . ' UTC] [VMAppSync] ' . $message . PHP_EOL;
        @error_log($line, 3, $this->logFile);
        // Also send errors to the hosting PHP error log.
        if (str_contains($message, 'failed') || str_contains($message, 'Unable') ||
            str_contains($message, 'Refusing') || str_contains($message, 'exceeds')) {
            error_log(rtrim($line));
        }
    }
}
