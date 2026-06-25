<?php
declare(strict_types=1);

final class WebPublisherClient
{
    private string $baseUrl;
    private string $token;
    private int $timeout;
    private int $maxRetries;

    public function __construct(
        string $baseUrl,
        string $token = '',
        int $timeout = 30,
        int $maxRetries = 2
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        $this->timeout = max(1, $timeout);
        $this->maxRetries = max(0, $maxRetries);
    }

    public function publishFiles(string $domain, array $files, array $options = []): array
    {
        $payload = array_merge($options, [
            'action' => 'publish',
            'domain' => $domain,
            'files' => $files,
        ]);

        return $this->request('POST', '/publish', $payload);
    }

    public function publishZip(string $domain, string $zipBase64, array $options = []): array
    {
        $payload = array_merge($options, [
            'action' => 'publish',
            'domain' => $domain,
            'zip_base64' => $zipBase64,
        ]);

        return $this->request('POST', '/publish', $payload);
    }

    public function requestSsl(string $domain, ?string $email = null, array $options = []): array
    {
        $payload = array_merge($options, [
            'action' => 'ssl.request',
            'domain' => $domain,
        ]);

        if ($email !== null && $email !== '') {
            $payload['email'] = $email;
        }

        return $this->request('POST', '/ssl/request', $payload);
    }

    public function suspend(string $domain, string $reason = 'Site suspended by operator', array $options = []): array
    {
        $payload = array_merge($options, [
            'action' => 'site.suspend',
            'domain' => $domain,
            'reason' => $reason,
        ]);

        return $this->request('POST', '/site/suspend', $payload);
    }

    public function activate(string $domain, array $options = []): array
    {
        $payload = array_merge($options, [
            'action' => 'site.activate',
            'domain' => $domain,
        ]);

        return $this->request('POST', '/site/activate', $payload);
    }

    public function status(string $domain): array
    {
        return $this->request('GET', '', ['domain' => $domain]);
    }

    public function request(string $method, string $path, array $payload = []): array
    {
        $attempt = 0;
        $method = strtoupper($method);
        $url = $this->endpointUrl($path);

        if ($method === 'GET' && $payload !== []) {
            $query = http_build_query($payload);
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        do {
            [$status, $headers, $body] = $this->dispatch($method, $url, $payload);

            if ($status !== 429 || $attempt >= $this->maxRetries) {
                return [
                    'status' => $status,
                    'headers' => $headers,
                    'body' => $body,
                ];
            }

            $retryAfter = (int)($headers['retry-after'] ?? $headers['x-retry-after'] ?? $headers['retry_after'] ?? 1);
            sleep(max(1, $retryAfter));
            $attempt++;
        } while (true);
    }

    private function dispatch(string $method, string $url, array $payload): array
    {
        $body = null;
        if ($method !== 'GET') {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        }
        if ($body === false) {
            throw new RuntimeException('Unable to encode request payload');
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $context = stream_context_create([
            'http' => array_filter([
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => $this->timeout,
            ], static fn ($value) => $value !== null),
        ]);

        $response = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $status = $this->parseStatus($responseHeaders);
        $parsedHeaders = $this->parseHeaders($responseHeaders);
        $decoded = $response !== false ? json_decode($response, true) : null;

        if (!is_array($decoded)) {
            $decoded = ['raw' => $response === false ? '' : $response];
        }

        return [$status, $parsedHeaders, $decoded];
    }

    private function endpointUrl(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        if ($path === '/') {
            $path = '';
        }

        return $this->baseUrl . '/api.php' . $path;
    }

    private function parseStatus(array $headers): int
    {
        if (!isset($headers[0]) || !preg_match('/\s(\d{3})\s/', (string)$headers[0], $matches)) {
            return 0;
        }

        return (int)$matches[1];
    }

    private function parseHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $line) {
            if (!is_string($line) || !str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $result[strtolower(trim($name))] = trim($value);
        }

        return $result;
    }
}