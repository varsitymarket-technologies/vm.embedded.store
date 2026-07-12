<?php
     
class emb_engine
{

    public $cloudflare_zoneid;
    public $cloudflare_api;

    public function __construct($cloudflare_zoneid, $cloudflare_api)
    {
        $this->cloudflare_zoneid = $cloudflare_zoneid;
        $this->cloudflare_api = $cloudflare_api;
    }

    function craft_dns_record($zoneId, $apiKey, $name, $content, $type = "CNAME", $comment = 'Online Store Automation Task', $ttl = 3600, $proxied = false)
    {
        $url = "https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records";

        $data = [
            'comment' => $comment,
            'content' => $content,
            'name' => $name,
            'proxied' => $proxied,
            'ttl' => $ttl,
            'type' => $type,
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$apiKey}",
                // "X-Auth-Key: {$apiKey}",
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        print_r($response); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'response' => json_decode($response, true),
            'http_code' => $httpCode,
        ];
    }

    function convertToDomainFormat($wildString)
    {
        // Replace '+' with '-'
        $formattedString = str_replace('+', '-', $wildString);

        // Convert the string to lowercase
        $formattedString = strtolower($formattedString);

        // Remove any invalid characters (only keep alphanumeric, hyphens, and dots)
        $formattedString = preg_replace('/[^a-z0-9\-\.]/', '', $formattedString);

        // Remove multiple consecutive hyphens
        $formattedString = preg_replace('/-{2,}/', '-', $formattedString);

        // Trim hyphens from the start and end
        $formattedString = trim($formattedString, '-');

        return $formattedString;
    }

    public function configure_subdomain($domain, $ip = 'levidoc.github.io')
    {
        $function = "craft_dns_record";
        $zoneId = $this->cloudflare_zoneid;
        $apiKey = $this->cloudflare_api;
        $name = $this->convertToDomainFormat($domain);
        $content = $ip;
        $ttl = 3600;
        $proxied = false;
        $comment = 'Online Store Automation Task';
        $type = "A";

        $exec = $this->$function($zoneId, $apiKey, $name, $content, $type, $comment, $ttl, $proxied);
        return $exec;

        $exec = $this->$function($zoneId, $apiKey, $name, $content, $ttl, $proxied);
    }
}
