<?php

declare(strict_types=1);

namespace AiEngine;

final class Config
{
    public function __construct(
        public readonly string $projectRoot,
        public readonly string $provider,
        public readonly string $apiKey,
        public readonly string $model,
        public readonly float $temperature,
        public readonly string $outputDirectory,
        public readonly string $skillsDirectory,
    ) {
    }

    public static function fromEnvironment(string $projectRoot): self
    {
        self::loadDotEnv($projectRoot . '/.env');

        $outputDirectory = dirname(dirname(dirname(dirname(__FILE__)))) . '/website';
        $skillsDirectory = trim((string) (getenv('SKILLS_DIRECTORY') ?: 'skills'));

        $provider = strtolower(trim((string) (getenv('AI_PROVIDER') ?: 'google')));
        if (!in_array($provider, ['google', 'openai'], true)) {
            $provider = 'google';
        }

        return new self(
            projectRoot: $projectRoot,
            provider: $provider,
            apiKey: trim((string) getenv($provider === 'openai' ? 'OPENAI_API_KEY' : 'GEMINI_API_KEY')),
            model: trim((string) (getenv($provider === 'openai' ? 'OPENAI_MODEL' : 'GEMINI_MODEL') ?: ($provider === 'openai' ? 'gpt-4.1-mini' : 'gemini-2.5-flash'))),
            temperature: (float) (getenv('GEMINI_TEMPERATURE') ?: 0.7),
            outputDirectory: $outputDirectory,
            skillsDirectory: $skillsDirectory,
        );
    }

    private static function loadDotEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"");
            if ($name !== '' && getenv($name) === false) {
                putenv($name . '=' . $value);
            }
        }
    }
}
