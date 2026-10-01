<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/TextGenerationClient.php';
require_once __DIR__ . '/src/GeminiClient.php';
require_once __DIR__ . '/src/OpenAIClient.php';
require_once __DIR__ . '/src/Agent.php';
require_once __DIR__ . '/src/WebsiteBuilder.php';

use AiEngine\Config;
use AiEngine\GeminiClient;
use AiEngine\OpenAIClient;
use AiEngine\WebsiteBuilder;

function websiteBuilder(string $root, ?string $skillsDirectory = null): WebsiteBuilder
{
    $config = Config::fromEnvironment($root);
    if ($skillsDirectory !== null) {
        $config = new Config(
            projectRoot: $config->projectRoot,
            provider: $config->provider,
            apiKey: $config->apiKey,
            model: $config->model,
            temperature: $config->temperature,
            outputDirectory: $config->outputDirectory,
            skillsDirectory: $skillsDirectory,
        );
    }

    $client = $config->provider === 'openai'
        ? new OpenAIClient($config)
        : new GeminiClient($config);

    return new WebsiteBuilder($config, $client);
}
