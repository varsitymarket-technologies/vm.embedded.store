<?php
/**
 * page.settings.php - Settings Router
 * Delegates each tab to its own file under settings/
 */

// --- Domain & Store Ownership Check ---
$db_engine = __DB_MODULE__;
$domain = __DOMAIN__;
$url_domain = ex(2);

// Verify the logged-in user owns the store they're trying to access
$store_record = $db_engine->query("SELECT * FROM sys_websites WHERE account_index = ? LIMIT 1", [__ACCOUNT_INDEX__]);
$owned_domain = $store_record[0]['domain'] ?? null;

if (empty($owned_domain)) {
    // User has no store — redirect to setup
    echo '<div class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center">
            <i class="bi bi-shop text-6xl text-gray-600"></i>
            <h2 class="text-2xl font-bold text-white mt-4">No Store Found</h2>
            <p class="text-gray-400 mt-2">You need to create a store before accessing settings.</p>
            <a href="/home/" class="inline-block mt-6 bg-purple-600 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-purple-500 transition-all">Go to Dashboard</a>
        </div>
    </div>';
    return;
}

// Prevent accessing another user's store via URL manipulation
if (!empty($url_domain) && $url_domain !== $owned_domain) {
    echo '<div class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center">
            <i class="bi bi-shield-x text-6xl text-red-500"></i>
            <h2 class="text-2xl font-bold text-white mt-4">Access Denied</h2>
            <p class="text-gray-400 mt-2">You do not have permission to manage this store.</p>
            <a href="/vm-admin/' . htmlspecialchars($owned_domain, ENT_QUOTES, 'UTF-8') . '/settings" class="inline-block mt-6 bg-purple-600 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-purple-500 transition-all">Go to Your Settings</a>
        </div>
    </div>';
    return;
}

$domain = $owned_domain;
$store_id = $store_record[0]['id'] ?? '';

// --- Initialize Site Database ---
$db_site = initiate_web_database();
if (!empty($domain) && $db_site === null) {
    $fallback_db_dir = dirname(dirname(dirname(__FILE__))) . "/sites/" . $domain;
    if (!is_dir($fallback_db_dir)) {
        @mkdir($fallback_db_dir, 0755, true);
    }
    $fallback_db_file = $fallback_db_dir . "/storage.data";
    $db_file = dirname(dirname(dirname(__FILE__))) . "/module/database.php";
    @include_once $db_file;
    $db_site = new database_manager($fallback_db_file);
    $db_site->createTable("settings", [
        "key" => "VARCHAR(255) PRIMARY KEY",
        "value" => "TEXT"
    ]);
}

// --- Encryption Helpers ---
$config_key = create_enc_key();
$config_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/email.config.enc";

function save_encrypted_config($path, $data, $key)
{
    if (!file_exists(dirname($path)))
        mkdir(dirname($path), 0777, true);
    $json = json_encode($data);
    $encrypted = __encryption__($json, $key);
    return file_put_contents($path, $encrypted);
}

function load_encrypted_config($path, $key)
{
    if (!file_exists($path))
        return [];
    $encrypted = file_get_contents($path);
    $json = __decryption__($encrypted, $key);
    return json_decode($json, true) ?: [];
}

// --- Helper: load a setting from the site DB ---
function get_setting($db, $key, $default = '') {
    if ($db === null) return $default;
    try {
        $result = $db->query("SELECT value FROM settings WHERE `key` = ? LIMIT 1", [$key]);
        return $result[0]['value'] ?? $default;
    } catch (\Throwable $th) {
        return $default;
    }
}

// --- Helper: get private DB via raw PDO (avoids uncatchable fatal from database_manager) ---
function get_private_pdo($domain) {
    if (empty($domain)) return null;
    $store_hash = hash('sha256', $domain);
    $base_dir = dirname(dirname(dirname(__FILE__)));
    $private_dir = dirname($base_dir) . "/data/" . $store_hash;
    if (!is_dir(dirname($base_dir) . "/data/") && !@mkdir(dirname($base_dir) . "/data/", 0755, true)) {
        $private_dir = $base_dir . "/build/data/" . $store_hash;
    }
    if (!is_dir($private_dir)) {
        @mkdir($private_dir, 0755, true);
    }
    $db_path = $private_dir . "/" . $domain;
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key_name VARCHAR(255),
        api_key VARCHAR(255) UNIQUE,
        active INTEGER DEFAULT 1,
        last_used DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        api_key VARCHAR(255),
        endpoint VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS cors_domains (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        domain VARCHAR(255) UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

// --- Handle POST Save Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_email_config') {
        $email_settings = $_POST['email'] ?? [];
        save_encrypted_config($config_path, $email_settings, $config_key);
        header("Location: ?tab=messaging&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_branding') {
        $branding = $_POST['branding'] ?? [];
        foreach ($branding as $key => $val) {
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$key, $val, $val]);
        }
        header("Location: ?tab=branding&saved=1");
        exit;
    }

    if ($_POST['action'] === 'generate_api_key') {
        $key_name = htmlspecialchars(trim($_POST['key_name'] ?? 'Untitled Key'), ENT_QUOTES, 'UTF-8');
        $prefix = 'vm_live_';
        $api_key = $prefix . bin2hex(random_bytes(24));
        $private_pdo = get_private_pdo($domain);
        if ($private_pdo) {
            $stmt = $private_pdo->prepare("INSERT INTO api_keys (key_name, api_key, active) VALUES (?, ?, 1)");
            $stmt->execute([$key_name, $api_key]);
        }
        header("Location: ?tab=dev&saved=1&new_key=" . urlencode($api_key));
        exit;
    }

    if ($_POST['action'] === 'revoke_api_key') {
        $key_id = (int) ($_POST['key_id'] ?? 0);
        $private_pdo = get_private_pdo($domain);
        if ($private_pdo && $key_id > 0) {
            $stmt = $private_pdo->prepare("UPDATE api_keys SET active = 0 WHERE id = ?");
            $stmt->execute([$key_id]);
        }
        header("Location: ?tab=dev&saved=1");
        exit;
    }

    if ($_POST['action'] === 'delete_api_key') {
        $key_id = (int) ($_POST['key_id'] ?? 0);
        $private_pdo = get_private_pdo($domain);
        if ($private_pdo && $key_id > 0) {
            $stmt = $private_pdo->prepare("DELETE FROM api_keys WHERE id = ?");
            $stmt->execute([$key_id]);
        }
        header("Location: ?tab=dev&saved=1");
        exit;
    }

    if ($_POST['action'] === 'add_cors_domain') {
        $cors_domain = trim($_POST['cors_domain'] ?? '');
        if (!empty($cors_domain)) {
            // Normalize: strip trailing slashes, ensure scheme
            $cors_domain = rtrim($cors_domain, '/');
            if (!preg_match('#^https?://#', $cors_domain)) {
                $cors_domain = 'https://' . $cors_domain;
            }
            $private_pdo = get_private_pdo($domain);
            if ($private_pdo) {
                $stmt = $private_pdo->prepare("INSERT OR IGNORE INTO cors_domains (domain) VALUES (?)");
                $stmt->execute([$cors_domain]);
            }
        }
        header("Location: ?tab=dev&saved=1");
        exit;
    }

    if ($_POST['action'] === 'remove_cors_domain') {
        $cors_id = (int) ($_POST['cors_id'] ?? 0);
        $private_pdo = get_private_pdo($domain);
        if ($private_pdo && $cors_id > 0) {
            $stmt = $private_pdo->prepare("DELETE FROM cors_domains WHERE id = ?");
            $stmt->execute([$cors_id]);
        }
        header("Location: ?tab=dev&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_currency') {
        $currency = $_POST['currency'] ?? [];
        foreach ($currency as $key => $val) {
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$key, $val, $val]);
        }
        $accepted = $_POST['accepted_currencies'] ?? [];
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('accepted_currencies', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [json_encode($accepted), json_encode($accepted)]);
        header("Location: ?tab=currency&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_payment') {
        $payment_config = $_POST['payment'] ?? [];
        $payment_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/payment.config.enc";
        save_encrypted_config($payment_path, $payment_config, $config_key);
        header("Location: ?tab=payment&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_discord') {
        $discord = $_POST['discord'] ?? [];
        foreach ($discord as $key => $val) {
            $db_site->query("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", ['discord_' . $key, $val, $val]);
        }
        $events = $_POST['discord_events'] ?? [];
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('discord_events', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [json_encode($events), json_encode($events)]);
        $mentions = $_POST['discord_mentions'] ?? [];
        $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('discord_mentions', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [json_encode($mentions), json_encode($mentions)]);
        header("Location: ?tab=app&saved=1");
        exit;
    }

    if ($_POST['action'] === 'save_console') {
        $console = $_POST['console'] ?? [];
        if (!empty($console['regenerate_secret'])) {
            $new_secret = 'vm_sec_' . bin2hex(random_bytes(12));
            if ($db_site !== null) {
                $db_site->query("INSERT INTO settings (`key`, `value`) VALUES ('console_secret_key', ?) ON CONFLICT(`key`) DO UPDATE SET value = ?", [$new_secret, $new_secret]);
            }
        }
        header("Location: ?tab=console&saved=1");
        exit;
    }
}

// --- Load Current Configs (shared across tabs) ---
$email_current = load_encrypted_config($config_path, $config_key);
$site_name = website_data('name');
$site_domain = website_data('domain');
$site_theme = website_data('theme');

// Currency settings
$cur_default = get_setting($db_site, 'default_currency', 'ZAR');
$cur_symbol_pos = get_setting($db_site, 'symbol_position', 'left');
$cur_decimals = get_setting($db_site, 'decimal_places', '2');
$cur_thousands = get_setting($db_site, 'thousand_separator', ',');
$cur_decimal_sep = get_setting($db_site, 'decimal_separator', '.');
$cur_auto_convert = get_setting($db_site, 'auto_conversion', '1');
$cur_accepted = json_decode(get_setting($db_site, 'accepted_currencies', '["ZAR","USD"]'), true) ?: ['ZAR','USD'];

// Payment settings
$payment_path = dirname(dirname(dirname(__FILE__))) . "/sites/$domain/payment.config.enc";
$payment_current = load_encrypted_config($payment_path, $config_key);

// Discord settings
$discord_webhook = get_setting($db_site, 'discord_webhook_url', '');
$discord_enabled = get_setting($db_site, 'discord_enabled', '0');
$discord_events = json_decode(get_setting($db_site, 'discord_events', '["new_order","payment_received","order_fulfilled","low_stock"]'), true) ?: [];
$discord_mentions = json_decode(get_setting($db_site, 'discord_mentions', '[]'), true) ?: [];
$discord_style = get_setting($db_site, 'discord_message_style', 'embed');
$discord_color = get_setting($db_site, 'discord_embed_color', '#5865F2');
$discord_bot_name = get_setting($db_site, 'discord_bot_name', 'Varsity Market Store');
$discord_avatar = get_setting($db_site, 'discord_avatar_url', '');

// Defaults for Email
$email_current['host'] = $email_current['host'] ?? '';
$email_current['port'] = $email_current['port'] ?? '587';
$email_current['user'] = $email_current['user'] ?? '';
$email_current['pass'] = $email_current['pass'] ?? '';
$email_current['template'] = $email_current['template'] ?? "<html>\n<body style=\"font-family: sans-serif; color: #333;\">\n  <div style=\"max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;\">\n    <h1 style=\"color: #7a1aab;\">Hello {{name}}!</h1>\n    <p>{{message}}</p>\n    <hr style=\"border: none; border-top: 1px solid #eee; margin: 20px 0;\">\n    <small style=\"color: #888;\">Sent from Your Online Store</small>\n  </div>\n</body>\n</html>";

// --- Tab Routing ---
$active_tab = $_GET['tab'] ?? 'general';
$settings_dir = dirname(__FILE__) . '/settings/';

$tab_files = [
    'general'    => 'settings.general.php',
    'branding'   => 'settings.branding.php',
    'domain'     => 'settings.domain.php',
    'email'      => 'settings.email.php',
    'deployment' => 'settings.deployment.php',
    'currency'   => 'settings.currency.php',
    'payment'    => 'settings.payment.php',
    'dev'        => 'settings.dev.php',
    'console'    => 'settings.console.php',
    'app'        => 'settings.app.php',
];
?>

<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden">
    <!-- Header -->
    <?php @include_once "header.php"; ?>

    <div class="animate-fade-in pb-20 flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
        <!-- Save Toast -->
        <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <?php if (isset($_GET['saved'])): ?>
                <div id="saveToast"
                    class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-full text-sm font-bold animate-bounce">
                    <i class="bi bi-check2-circle text-lg"></i>
                    <span>Changes saved successfully</span>
                </div>
                <script>setTimeout(() => document.getElementById('saveToast').remove(), 5000);</script>
            <?php endif; ?>
        </div>

        <?php if ($active_tab == 'general'): ?>
            <?php @include $settings_dir . $tab_files['general']; ?>
        <?php endif; ?>

        <div class="settings-layout">
            <main class="settings-content">
                <?php
                if ($active_tab !== 'general' && isset($tab_files[$active_tab])) {
                    @include $settings_dir . $tab_files[$active_tab];
                }
                ?>
            </main>
        </div>
    </div>

</div>
