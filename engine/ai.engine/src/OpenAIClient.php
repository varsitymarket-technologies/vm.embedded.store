<?php

declare(strict_types=1);

namespace AiEngine;

use RuntimeException;

final class OpenAIClient implements TextGenerationClient
{
    public function __construct(private readonly Config $config)
    {
    }

    public function generate(string $systemInstruction, string $prompt): string
    {
        if ($this->config->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is missing. Add it to .env or the environment.');
        }

        $payload = json_encode([
            'model' => $this->config->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $this->config->temperature,
        ], JSON_THROW_ON_ERROR);

        $handle = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config->apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 120,
        ]);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException('OpenAI request failed: ' . $error);
        }

        $decoded = json_decode($response, true);
        if ($status < 200 || $status >= 300) {
            $message = $decoded['error']['message'] ?? 'Unknown OpenAI API error';
            throw new RuntimeException(sprintf('OpenAI API returned HTTP %d: %s', $status, $message));
        }

        $text = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('OpenAI returned no usable text.');
        }

        return trim($text);
    }
}
