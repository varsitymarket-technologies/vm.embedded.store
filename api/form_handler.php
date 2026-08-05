<?php
// Public form integration endpoint for admin-built forms.

require_once dirname(__DIR__) . '/scripts.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$db = initiate_web_database();

$db->createTable("forms", [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
    "form_key" => "TEXT UNIQUE",
    "name" => "TEXT",
    "fields" => "TEXT",
    "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
]);

$db->createTable("form_submissions", [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
    "form_key" => "TEXT",
    "data" => "TEXT",
    "unread" => "INTEGER DEFAULT 1",
    "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP"
]);

function form_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function read_request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $contentType = strtolower(trim(explode(';', $contentType)[0] ?? ''));

    if ($contentType === 'application/json') {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function sanitize_submission_value($value)
{
    if (is_array($value)) {
        return array_map('sanitize_submission_value', $value);
    }

    if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
        return $value;
    }

    return trim((string) $value);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'get_fields';
    $formKey = trim((string) ($_GET['form_key'] ?? ''));

    if ($formKey === '') {
        form_response(['success' => false, 'error' => 'Missing form_key'], 400);
    }

    $forms = $db->query("SELECT form_key, name, fields FROM forms WHERE form_key = ? LIMIT 1", [$formKey]);
    if (empty($forms)) {
        form_response(['success' => false, 'error' => 'Form not found'], 404);
    }

    $form = $forms[0];
    $fields = json_decode($form['fields'] ?? '[]', true);
    if (!is_array($fields)) {
        $fields = [];
    }

    if ($action === 'get_form') {
        form_response([
            'success' => true,
            'form' => [
                'form_key' => $form['form_key'],
                'name' => $form['name'],
                'fields' => $fields,
            ],
        ]);
    }

    form_response([
        'success' => true,
        'fields' => $fields,
    ]);
}

if ($method === 'POST') {
    $data = read_request_data();
    $formKey = trim((string) ($data['form_key'] ?? ''));

    if ($formKey === '') {
        form_response(['success' => false, 'error' => 'Missing form_key'], 400);
    }

    $forms = $db->query("SELECT form_key, name, fields FROM forms WHERE form_key = ? LIMIT 1", [$formKey]);
    if (empty($forms)) {
        form_response(['success' => false, 'error' => 'Form not found'], 404);
    }

    $form = $forms[0];
    $schema = json_decode($form['fields'] ?? '[]', true);
    if (!is_array($schema)) {
        $schema = [];
    }

    $allowedKeys = [];
    foreach ($schema as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldName = trim((string) ($field['name'] ?? ''));
        if ($fieldName !== '') {
            $allowedKeys[] = $fieldName;
        }
    }

    $submission = [];
    foreach ($data as $key => $value) {
        if ($key === 'form_key') {
            continue;
        }
        if (!empty($allowedKeys) && !in_array($key, $allowedKeys, true)) {
            continue;
        }
        $submission[$key] = sanitize_submission_value($value);
    }

    foreach ($schema as $field) {
        if (!is_array($field)) {
            continue;
        }

        $fieldName = trim((string) ($field['name'] ?? ''));
        if ($fieldName === '') {
            continue;
        }

        $required = !empty($field['required']);
        $value = $submission[$fieldName] ?? null;
        if ($required && ($value === null || $value === '')) {
            form_response(['success' => false, 'error' => 'Missing required field: ' . $fieldName], 400);
        }
    }

    $payload = json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        form_response(['success' => false, 'error' => 'Failed to encode submission'], 500);
    }

    $db->query(
        "INSERT INTO form_submissions (form_key, data, unread) VALUES (?, ?, 1)",
        [$formKey, $payload]
    );

    form_response([
        'success' => true,
        'message' => 'Submission saved',
        'form' => [
            'form_key' => $form['form_key'],
            'name' => $form['name'],
        ],
    ]);
}

form_response(['success' => false, 'error' => 'Method not allowed'], 405);
