<?php

final class DeliveryZonesSync
{
    private string $outputFile;
    private string $baseUrl;

    public function __construct(array $options = [])
    {
        $this->outputFile = (string)($options['output_file'] ?? dirname(__DIR__) . '/build/delivery-zones.storage.json');
        $this->baseUrl = rtrim((string)($options['base_url'] ?? 'https://cdn.jsdelivr.net/gh/srestre/world-countries-cities-db@main'), '/');
    }

    public function run(): array
    {
        $payload = [
            'synced_at' => gmdate('c'),
            'source' => $this->baseUrl,
            'countries' => [],
        ];

        $countries = $this->request('/metadata/countries.json');
        if (!is_array($countries)) {
            $countries = [];
        }

        foreach ($countries as $country) {
            $iso2 = strtoupper((string)($country['iso2'] ?? ''));
            if ($iso2 === '') {
                continue;
            }

            $countryData = $this->request('/countries/' . $iso2 . '.json');
            if (!is_array($countryData) || empty($countryData)) {
                continue;
            }

            $payload['countries'][] = $countryData;
        }

        $this->persist($payload);

        return [
            'success' => true,
            'countries' => count($payload['countries']),
            'output_file' => $this->outputFile,
            'synced_at' => $payload['synced_at'],
        ];
    }

    public function readCache(): ?array
    {
        if (!file_exists($this->outputFile)) {
            return null;
        }

        $decoded = json_decode((string)file_get_contents($this->outputFile), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function request(string $path): array
    {
        $response = @file_get_contents($this->baseUrl . $path);
        if ($response === false) {
            return [];
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $payload): void
    {
        $dir = dirname($this->outputFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload['generated_at'] = gmdate('c');
        file_put_contents(
            $this->outputFile,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}

