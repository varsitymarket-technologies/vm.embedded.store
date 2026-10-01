<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$builder = websiteBuilder(__DIR__, __DIR__ . '/fixtures/skills');
$reflection = new ReflectionClass($builder);
$method = $reflection->getMethod('skillGuide');
$method->setAccessible(true);

$guide = $method->invoke($builder, 'brand strategist');

if (!str_contains($guide, 'Lead with clarity and trust.')) {
    fwrite(STDERR, "Expected custom skill guide to be loaded for brand strategist.\n");
    exit(1);
}

fwrite(STDOUT, "Skill guide loaded successfully.\n");
