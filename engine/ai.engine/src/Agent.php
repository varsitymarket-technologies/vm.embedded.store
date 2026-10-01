<?php

declare(strict_types=1);

namespace AiEngine;

final class Agent
{
    public function __construct(
        public readonly string $name,
        public readonly string $skillSet,
        private readonly TextGenerationClient $client,
        private readonly string $skillGuide = '',
    ) {
    }

    public function run(string $brief, string $context = ''): string
    {
        $customGuide = $this->skillGuide === '' ? '' : "\n\nCustom system guide:\n" . trim($this->skillGuide);

        $systemInstruction = <<<PROMPT
You are the {$this->name} on a collaborative static website team.
Your specialist skill set: {$this->skillSet}{$customGuide}
Be decisive, practical, and specific. Do not mention being an AI. Return only the work product requested by the user prompt.
PROMPT;

        return $this->client->generate($systemInstruction, $brief . "\n\nExisting team context:\n" . ($context ?: 'No previous work.'));
    }
}
