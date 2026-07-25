<?php

function vmpages_deploy($domain, $path)
{
    $engine_tokens = $_SERVER['__ENGINE_TOKENS__'];
    $engine_secrets = $_SERVER['__ENGINE_SECRETS__'];
    // Provide a default source if generic
    $engine_source = $_SERVER['__ENGINE_SOURCE__'];
    $apiUrl = $engine_source;

    $directoryToUpload = $path;
    $sourceDir = $directoryToUpload . '';
    $apiKey = $engine_secrets;

    if (!is_dir($sourceDir)) {
        die("Source directory does not exist.\n");
    }

    // Recursively iterate through the directory
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $successCount = 0;
    $failCount = 0;

    foreach ($iterator as $file) {
        if ($file->isDir()) continue; // Skip directories, they are created automatically by the server

        $filePath = $file->getRealPath();

        // Calculate the relative path to send to the server
        $relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
        $relativePath = str_replace('\\', '/', $relativePath); // Normalize for Windows clients

        // Prepare the multipart form data
        $postFields = [
            'domain' => $domain,
            'relative_path' => $relativePath,
            'file' => new CURLFile($filePath)
        ];

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-Key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30-second timeout per file

        // Execute upload
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle response
        if ($httpCode === 200) {
            echo "[SUCCESS] Uploaded: $relativePath\n";
            $successCount++;
        } else {
            echo "[FAILED] $relativePath (HTTP $httpCode)\n";
            if ($curlError) {
                // echo "         cURL Error: $curlError\n";
            } else {
                 echo "         Server Response: $response\n";
            }
            $failCount++;
        }
    }
}

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

    public function publishWebsite(string $domain, array $html, array $options = []): array
    {
        $payload = array_merge($options, [
            'action' => 'publish.website',
            'domain' => $domain,
            'html' => $html['html'] ?? '',
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
                //debug(json_decode($body, true));

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
            ], static fn($value) => $value !== null),
        ]);

        $response = @file_get_contents($url, false, $context);
        debug($body . "Gateway response for $method $url: ");
        $responseHeaders = $http_response_header ?? [];
        $status = $this->parseStatus($responseHeaders);
        $parsedHeaders = $this->parseHeaders($responseHeaders);
        $decoded = $response !== false ? json_decode($response, true) : null;

        if (!is_array($decoded)) {
            $decoded = ['raw' => $response === false ? '' : $response];
        }
        print_r($decoded);

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
