#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Safe staging harness for the per-website engine layout sketched in t.php.
 *
 * Usage:
 *   php engine/test-engine-registration.php <website-domain>
 *
 * Copies the website and AI engine into a new temporary directory. It never
 * writes to sites/ or engine/agents/, and omits known credentials/runtime data.
 */

function usage(): never
{
    fwrite(STDERR, "Usage: php engine/test-engine-registration.php <website-domain>\n");
    exit(2);
}

/**
 * Copy a directory tree while excluding private/runtime files.
 *
 * @return array{copied:int, skipped:int}
 */
function copyTreeForTest(string $source, string $destination): array
{
    if (!is_dir($source)) {
        throw new RuntimeException('Source directory does not exist: ' . $source);
    }
    if (!is_dir($destination) && !mkdir($destination, 0700, true) && !is_dir($destination)) {
        throw new RuntimeException('Could not create staging directory: ' . $destination);
    }

    $copied = 0;
    $skipped = 0;
    $excludedFiles = [
        '.env',
        'config.php',
        'storage.data',
        'sensitive.data',
        'analytics.data',
        'ai_agent.config.enc',
        'email.config.enc',
        'payment.config.enc',
    ];
    $excludedDirectories = ['.git', 'log', 'logs'];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $entry) {
        $relative = $iterator->getSubPathName();
        $parts = preg_split('~[\\\\/]~', $relative) ?: [];
        $name = $entry->getFilename();
        $excluded = false;

        foreach ($parts as $part) {
            if (in_array($part, $excludedDirectories, true)) {
                $excluded = true;
                break;
            }
        }

        if ($name === '.env' || str_starts_with($name, '.env.') || in_array($name, $excludedFiles, true)) {
            $excluded = true;
        }

        if ($excluded || $entry->isLink()) {
            $skipped++;
            continue;
        }

        $target = $destination . DIRECTORY_SEPARATOR . $relative;
        if ($entry->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0700, true) && !is_dir($target)) {
                throw new RuntimeException('Could not create staged folder: ' . $target);
            }
            continue;
        }

        if (!$entry->isFile()) {
            $skipped++;
            continue;
        }

        $parent = dirname($target);
        if (!is_dir($parent) && !mkdir($parent, 0700, true) && !is_dir($parent)) {
            throw new RuntimeException('Could not create staged folder: ' . $parent);
        }
        if (!copy($entry->getPathname(), $target)) {
            throw new RuntimeException('Could not stage file: ' . $relative);
        }
        $copied++;
    }

    return ['copied' => $copied, 'skipped' => $skipped];
}

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ($argc !== 2 || in_array($argv[1], ['-h', '--help'], true)) {
    usage();
}

$domain = trim($argv[1]);
if (!preg_match('/\A[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?\z/', $domain)) {
    fwrite(STDERR, "Invalid website domain. Use a domain name without a path.\n");
    exit(2);
}

$projectRoot = dirname(__DIR__);
$websiteSource = $projectRoot . '/sites/' . $domain;
$engineSource = __DIR__ . '/ai.engine';
$sitesRoot = realpath($projectRoot . '/sites');
$websiteRealPath = realpath($websiteSource);

if ($sitesRoot === false || $websiteRealPath === false || !str_starts_with($websiteRealPath, $sitesRoot . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "Website folder not found under sites/: {$domain}\n");
    exit(1);
}

try {
    $stagingRoot = sys_get_temp_dir() . '/vm-engine-registration-' . bin2hex(random_bytes(8));
    $agentRoot = $stagingRoot . '/agents/' . $domain;
    $websiteDestination = $agentRoot . '/website/' . $domain;
    $serviceDestination = $agentRoot . '/services/ai.engine';

    $websiteResult = copyTreeForTest($websiteRealPath, $websiteDestination);
    $engineResult = copyTreeForTest($engineSource, $serviceDestination);

    $requiredPaths = [
        $websiteDestination . '/index.php',
        $websiteDestination . '/routes.php',
        $serviceDestination . '/bootstrap.php',
        $serviceDestination . '/src/WebsiteBuilder.php',
        $serviceDestination . '/skills/frontend-engineer.md',
    ];
    $missing = array_values(array_filter($requiredPaths, static fn(string $path): bool => !is_file($path)));
    if ($missing !== []) {
        throw new RuntimeException('Staged layout is missing expected files: ' . implode(', ', $missing));
    }

    fwrite(STDOUT, "Engine registration staging succeeded.\n");
    fwrite(STDOUT, "Domain: {$domain}\n");
    fwrite(STDOUT, "Website files copied: {$websiteResult['copied']} (skipped: {$websiteResult['skipped']})\n");
    fwrite(STDOUT, "AI engine files copied: {$engineResult['copied']} (skipped: {$engineResult['skipped']})\n");
    fwrite(STDOUT, "Staging directory: {$stagingRoot}\n");
    fwrite(STDOUT, "No files under sites/ or engine/agents/ were changed.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Staging failed: {$error->getMessage()}\n");
    exit(1);
}
