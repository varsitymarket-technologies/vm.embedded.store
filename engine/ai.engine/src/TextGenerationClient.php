<?php

declare(strict_types=1);

namespace AiEngine;

interface TextGenerationClient
{
    public function generate(string $systemInstruction, string $prompt): string;
}
