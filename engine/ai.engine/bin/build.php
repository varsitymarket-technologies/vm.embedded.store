#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['brief:', 'dry-run']);
$brief = (string) ($options['brief'] ?? '');
if ($brief === '') {
    fwrite(STDERR, "Usage: php bin/build.php --brief=\"A landing page for...\" [--dry-run]\n");
    exit(1);
}

try {
    $result = websiteBuilder(dirname(__DIR__))->build($brief, array_key_exists('dry-run', $options));
    fwrite(STDOUT, "Generated {$result['path']} using " . count($result['agents']) . " specialist agents.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Build failed: {$exception->getMessage()}\n");
    exit(1);
}
