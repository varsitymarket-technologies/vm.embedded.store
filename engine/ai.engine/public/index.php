<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        throw new RuntimeException('Use POST with a JSON body containing a "brief" field.');
    }

    $input = json_decode(file_get_contents('php://input') ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    $brief = is_array($input) ? (string) ($input['brief'] ?? '') : '';
    $dryRun = is_array($input) && (bool) ($input['dry_run'] ?? false);
    $result = websiteBuilder(dirname(__DIR__))->build($brief, $dryRun);

    echo json_encode([
        'ok' => true,
        'path' => $result['path'],
        'agent_count' => count($result['agents']),
        'message' => 'Website generated successfully.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['ok' => false, 'error' => $exception->getMessage()], JSON_PRETTY_PRINT);
}



## This will be the example for the uSER system 

# Receive The Input From The User 
@$prompt = $_GET[''] ?? $_POST['']; 

class engine {
    public $data;
    
    function sync(){
        if class_exists('websiteBuilder'){
            trigger_error('The Website builder AI Module Does Not Exists'); 
        }

        
        $this->data = websiteBuilder($this->path)->build($brief, $dryRun); 
    }

    function communicate($message){
        
    }
}
