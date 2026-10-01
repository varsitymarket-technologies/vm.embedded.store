<?php

declare(strict_types=1);

namespace AiEngine;

use RuntimeException;

final class GeminiClient implements TextGenerationClient
{
    public function __construct(private readonly Config $config)
    {
    }

    public function generate(string $systemInstruction, string $prompt): string
    {
        if ($this->config->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is missing. Add it to .env or the environment.');
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($this->config->model),
            rawurlencode($this->config->apiKey),
        );
        $payload = json_encode([
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => $this->config->temperature,
                'responseMimeType' => 'text/plain',
            ],
        ], JSON_THROW_ON_ERROR);

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException('Gemini request failed: ' . $error);
        }

        $decoded = json_decode($response, true);
        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? 'Unknown Gemini API error';
            throw new RuntimeException(sprintf('Gemini API returned HTTP %d: %s', $status, $message));
        }

        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned no usable text.');
        }

        return trim($text);
    }
}
