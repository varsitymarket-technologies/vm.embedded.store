<?php
declare(strict_types=1);

/**
 * Copy a folder into a working directory.
 * Usage: php initiate-engine.php <source-folder> <working-directory>
 */
function copy_folder(string $source, string $workingDirectory): void
{
    $source = realpath($source) ?: '';
    if ($source === '' || !is_dir($source)) {
        throw new InvalidArgumentException('Source must be an existing folder.');
    }

    if (!is_dir($workingDirectory) && !mkdir($workingDirectory, 0775, true) && !is_dir($workingDirectory)) {
        throw new RuntimeException('Could not create working directory.');
    }
    $workingDirectory = realpath($workingDirectory) ?: '';
    if ($workingDirectory === '' || $workingDirectory === $source || str_starts_with($workingDirectory . DIRECTORY_SEPARATOR, $source . DIRECTORY_SEPARATOR)) {
        throw new InvalidArgumentException('Working directory cannot be the source or a folder inside it.');
    }

    $destination = $workingDirectory . DIRECTORY_SEPARATOR . basename($source);
    if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
        throw new RuntimeException('Could not create destination folder.');
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $entry) {
        $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($entry->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
                throw new RuntimeException("Could not create folder: {$target}");
            }
        } elseif ($entry->isFile() && !copy($entry->getPathname(), $target)) {
            throw new RuntimeException("Could not copy file: {$entry->getPathname()}");
        }
    }
}

if (PHP_SAPI === 'cli' && isset($argv) && realpath($argv[0]) === __FILE__) {
    if ($argc !== 3) {
        fwrite(STDERR, "Usage: php initiate-engine.php <source-folder> <working-directory>\n");
        exit(2);
    }

    try {
        copy_folder($argv[1], $argv[2]);
        fwrite(STDOUT, "Folder copied successfully.\n");
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage() . "\n");
        exit(1);
    }
}