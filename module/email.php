<?php
#   TITLE   : Email Service Module
#   DESC    : Handle email sending via SMTP configuration
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES

// Load encryption functions
if (!function_exists('__encryption__')) {
    @include_once dirname(dirname(__FILE__)) . "/scripts.php";
}

if (!function_exists('create_enc_key')) {
    @include_once dirname(dirname(__FILE__)) . "/scripts.php";
}

/**
 * Load encrypted email config for a domain.
 * 
 * @param string $domain Domain name to load config for
 * @return array|null Config array with keys: host, port, user, pass, template, or null if not configured
 */
function email_load_config(string $domain): ?array
{
    $config_path = dirname(dirname(__FILE__)) . "/sites/$domain/email.config.enc";
    
    if (!file_exists($config_path)) {
        return null;
    }
    
    try {
        $key = create_enc_key();
        $encrypted = file_get_contents($config_path);
        $json = __decryption__($encrypted, $key);
        $config = json_decode($json, true);
        
        if (!is_array($config)) {
            return null;
        }
        
        return $config;
    } catch (\Throwable $th) {
        return null;
    }
}

/**
 * Send an email via SMTP.
 * 
 * @param string $domain Domain for SMTP config lookup
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @return array{ok: bool, error?: string}
 */
function email_send(string $domain, string $to, string $subject, string $body): array
{
    $config = email_load_config($domain);
    
    if (!$config) {
        return ['ok' => false, 'error' => 'Email not configured for this domain'];
    }
    
    $host = $config['host'] ?? '';
    $port = (int)($config['port'] ?? 587);
    $user = $config['user'] ?? '';
    $pass = $config['pass'] ?? '';
    
    if (empty($host) || empty($user) || empty($pass)) {
        return ['ok' => false, 'error' => 'Email configuration incomplete'];
    }
    
    // Build email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . ($config['from_email'] ?? $user),
        'Reply-To: ' . ($config['reply_to'] ?? $user),
    ];
    
    // Use native PHP mail with SMTP settings via ini_set if available
    // For production, consider using PHPMailer or SwiftMailer
    
    try {
        // Attempt to use built-in mail function
        // Note: Requires proper PHP mail configuration or postfix/sendmail
        $result = @mail(
            $to,
            $subject,
            $body,
            implode("\r\n", $headers),
            "-f{$user}"
        );
        
        if ($result) {
            return ['ok' => true];
        } else {
            return ['ok' => false, 'error' => 'Failed to send email'];
        }
    } catch (\Throwable $th) {
        return ['ok' => false, 'error' => 'Email send exception: ' . $th->getMessage()];
    }
}

/**
 * Send OTP email with interpolated template.
 * 
 * @param string $domain Domain for SMTP config
 * @param string $to Recipient email
 * @param string $name Customer name
 * @param string $otp_code The 6-digit OTP code
 * @return array{ok: bool, error?: string}
 */
function email_send_otp(string $domain, string $to, string $name, string $otp_code): array
{
    $config = email_load_config($domain);
    
    if (!$config) {
        return ['ok' => false, 'error' => 'Email not configured'];
    }
    
    $subject = 'Your Login Verification Code';
    
    // Use template if configured, otherwise use default
    $template = $config['template'] ?? '';
    
    if (!empty($template)) {
        // Interpolate template with OTP details
        $body = str_replace(
            ['{{name}}', '{{otp}}', '{{message}}'],
            [$name, $otp_code, "Your one-time password is: $otp_code"],
            $template
        );
    } else {
        // Default email template
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center; }
        .code { font-size: 32px; font-weight: bold; color: #0066cc; margin: 20px 0; text-align: center; }
        .footer { color: #999; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Verify Your Login</h2>
        </div>
        <p>Hello {$name},</p>
        <p>You've requested to log in to your account. To complete the login, please enter this verification code:</p>
        <div class="code">{$otp_code}</div>
        <p>This code will expire in 10 minutes.</p>
        <p>If you didn't request this code, please ignore this email.</p>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    return email_send($domain, $to, $subject, $body);
}

?>
