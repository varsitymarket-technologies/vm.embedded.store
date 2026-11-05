<?php
$auth_db = __DB_MODULE__;
$auth_error = '';
$auth_notice = '';
$auth_mode = ($_POST['mode'] ?? $_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$pending_auth = $_SESSION['pending_account_auth'] ?? '';

function vm_auth_column_exists($db, string $column): bool
{
    foreach ($db->query("PRAGMA table_info(sys_account)") as $row) {
        if (($row['name'] ?? '') === $column) return true;
    }
    return false;
}

function vm_auth_prepare_schema($db): void
{
    if (!vm_auth_column_exists($db, 'password_hash')) {
        $db->query("ALTER TABLE sys_account ADD COLUMN password_hash TEXT");
    }
    if (!vm_auth_column_exists($db, 'email_verified')) {
        $db->query("ALTER TABLE sys_account ADD COLUMN email_verified INTEGER NOT NULL DEFAULT 0");
    }
    $db->query("CREATE TABLE IF NOT EXISTS sys_account_otp (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_auth TEXT NOT NULL,
        otp_code TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        verified INTEGER NOT NULL DEFAULT 0
    )");
}

function vm_auth_webhook_url(): string
{
    return trim((string) ($_SERVER['DISCORD_AUTH_WEBHOOK_URL'] ?? getenv('DISCORD_AUTH_WEBHOOK_URL') ?: ''));
}

function vm_auth_send_otp(string $webhook, string $name, string $email, string $otp): bool
{
    if ($webhook === '' || !function_exists('curl_init')) return false;
    $payload = [
        'username' => 'Embedded Store Auth',
        'content' => 'A new account confirmation is waiting.',
        'embeds' => [[
            'title' => 'Account confirmation',
            'description' => 'Use this one-time code to confirm the new account.',
            'color' => 5763719,
            'fields' => [
                ['name' => 'Name', 'value' => $name !== '' ? $name : 'Not provided', 'inline' => true],
                ['name' => 'Email', 'value' => $email, 'inline' => true],
                ['name' => 'OTP', 'value' => '`' . $otp . '`', 'inline' => false],
            ],
            'footer' => ['text' => 'Expires in 10 minutes'],
            'timestamp' => gmdate('Y-m-d\\TH:i:s\\Z'),
        ]],
    ];
    $curl = curl_init($webhook);
    curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return $status === 200 || $status === 204;
}

function vm_auth_start_session(array $account): void
{
    session_regenerate_id(true);
    $_SESSION['vm_key'] = base64_encode($account['auth']);
    $_SESSION['vm_index'] = base_encryption(__encryption__($account['auth'], $account['auth']));
}

vm_auth_prepare_schema($auth_db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $name = trim((string) ($_POST['name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($action === 'register') {
        $auth_mode = 'register';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || strlen($password) < 8) {
            $auth_error = 'Enter a name, valid email, and password of at least 8 characters.';
        } elseif (!empty($auth_db->query("SELECT id FROM sys_account WHERE email = ? OR name = ? LIMIT 1", [$email, $name]))) {
            $auth_error = 'An account with that name or email already exists.';
        } else {
            $account_auth = bin2hex(random_bytes(24));
            $auth_db->query("INSERT INTO sys_account (name, email, password_hash, email_verified, auth) VALUES (?, ?, ?, 0, ?)", [$name, $email, password_hash($password, PASSWORD_DEFAULT), $account_auth]);
            $account = $auth_db->query("SELECT * FROM sys_account WHERE auth = ? LIMIT 1", [$account_auth])[0] ?? [];
            $otp = (string) random_int(100000, 999999);
            $auth_db->query("INSERT INTO sys_account_otp (account_auth, otp_code, expires_at) VALUES (?, ?, datetime('now', '+10 minutes'))", [$account_auth, $otp]);
            if (vm_auth_send_otp(vm_auth_webhook_url(), $name, $email, $otp)) {
                $_SESSION['pending_account_auth'] = $account_auth;
                $pending_auth = $account_auth;
                $auth_notice = 'Confirmation code sent. Check Discord to finish creating your account.';
            } else {
                $auth_error = 'The account was not confirmed because the Discord webhook is unavailable. Configure DISCORD_AUTH_WEBHOOK_URL and try again.';
            }
        }
    } elseif ($action === 'confirm') {
        $auth_mode = 'confirm';
        $otp = trim((string) ($_POST['otp'] ?? ''));
        $account_auth = $_SESSION['pending_account_auth'] ?? '';
        $match = $auth_db->query("SELECT * FROM sys_account_otp WHERE account_auth = ? AND otp_code = ? AND verified = 0 AND expires_at > datetime('now') ORDER BY id DESC LIMIT 1", [$account_auth, $otp]);
        if (empty($match)) {
            $auth_error = 'That confirmation code is invalid or expired.';
        } else {
            $auth_db->query("UPDATE sys_account_otp SET verified = 1 WHERE id = ?", [$match[0]['id']]);
            $auth_db->query("UPDATE sys_account SET email_verified = 1 WHERE auth = ?", [$account_auth]);
            $account = $auth_db->query("SELECT * FROM sys_account WHERE auth = ? LIMIT 1", [$account_auth])[0] ?? [];
            unset($_SESSION['pending_account_auth']);
            vm_auth_start_session($account);
            header('Location: /home/');
            exit;
        }
    } elseif ($action === 'login') {
        $auth_mode = 'login';
        $identity = strtolower(trim((string) ($_POST['identity'] ?? '')));
        $accounts = $auth_db->query("SELECT * FROM sys_account WHERE (LOWER(email) = ? OR LOWER(name) = ?) LIMIT 1", [$identity, $identity]);
        $account = $accounts[0] ?? null;
        if (!$account) {
            $auth_error = 'Invalid email, username, or password.';
        } elseif (empty($account['password_hash']) || !is_string($account['password_hash'])) {
            $auth_error = 'This account does not have a password set. Contact your administrator to restore sign-in access.';
        } elseif (!password_verify($password, $account['password_hash'])) {
            $auth_error = 'Invalid email, username, or password.';
        } elseif (empty($account['email_verified'])) {
            $auth_error = 'Confirm your account from Discord before signing in.';
            $auth_mode = 'login';
        } else {
            if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
                $auth_db->query("UPDATE sys_account SET password_hash = ? WHERE auth = ?", [password_hash($password, PASSWORD_DEFAULT), $account['auth']]);
            }
            vm_auth_start_session($account);
            header('Location: /home/');
            exit;
        }
    }
}
?>
<?php
$random_anchor = random_int(1, 8);

$dataset = []; 
$dataset[1] = [
    'subtitle' => 'Less Problems, More Freedom',
    'title' => 'Your Brand Deserves Freedom From You',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide1.gif',
];

$dataset[2] = [
    'subtitle' => 'Live at ease, without limits',
    'title' => 'Be Yourself and Your Brand Will Follow',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide2.gif',
];

$dataset[3] = [
    'subtitle' => 'Who said Mobile is the future?',
    'title' => 'Manage Your Brand From Anywhere',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide3.gif',
];

$dataset[4] = [
    'subtitle' => 'Who said I wasnt On it?',
    'title' => 'Manage Your Brand From Anywhere',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide4.gif',
];

$dataset[5] = [
    'subtitle' => 'Who said we want a cut ?',
    'title' => 'Keep All Your Brand Profits',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide5.gif',
];

$dataset[6] = [
    'subtitle' => 'Your Brand Grows just like you do',
    'title' => 'Your Brand is an Entity, Not a Product',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide6.gif',
];

$dataset[7] = [
    'subtitle' => 'Your Brand Grows just like you do',
    'title' => 'Your Brand is an Entity, Not a Product',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide7.gif',
];

$dataset[8] = [
    'subtitle' => 'Your Brand Grows just like you do',
    'title' => 'Grow and scale your brand with Embedded Engine',
    'description' => 'Embedded Engine is a powerful platform that allows you to create and manage your own online store with ease.',
    'image' => '/assets/slide8.gif',
];

?>
<style>
    .vm-auth-shell { min-height: 100vh; display: grid; place-items: center; box-sizing: border-box; padding: 48px; background: #101010; color: #f5f5f5; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .vm-auth-card { width: min(1120px, 100%); min-height: 650px; display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr); overflow: hidden; border: 1px solid #303030; border-radius: 20px; background: #171717; box-shadow: 0 28px 90px #0008; }
    .vm-auth-visual { position: relative; min-height: 650px; overflow: hidden; background: #242321; }
    .vm-auth-visual-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; opacity: .75; }
    .vm-auth-visual::after { position: absolute; inset: 0; content: ""; background: linear-gradient(180deg, #0008 0%, #0000 36%, #0002 55%, #000d 100%); pointer-events: none; }
    .vm-auth-visual-top, .vm-auth-visual-caption { position: absolute; z-index: 1; left: 34px; right: 34px; }
    .vm-auth-visual-top { top: 30px; display: flex; align-items: center; gap: 11px; color: white; font-size: .9rem; font-weight: 650; letter-spacing: -.02em; }
    .vm-auth-mark { width: 30px; height: 30px; border-radius: 9px; object-fit: cover; }
    .vm-auth-visual-caption { bottom: 32px; }
    .vm-auth-eyebrow { margin: 0 0 13px; color: #ddd; font-size: .72rem; font-weight: 650; letter-spacing: .14em; text-transform: uppercase; }
    .vm-auth-visual-caption h2 { max-width: 470px; margin: 0; color: #fff; font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 620; line-height: 1.02; letter-spacing: -.055em; }
    .vm-auth-visual-caption p { max-width: 390px; margin: 15px 0 0; color: #d0d0d0; font-size: .94rem; line-height: 1.65; }
    .vm-auth-form { display: flex; flex-direction: column; justify-content: center; box-sizing: border-box; padding: 56px clamp(30px, 5vw, 68px); background: #171717; }
    .vm-auth-form h1 { margin: 0; color: #f5f5f5; text-align: left; font-size: 1.8rem; font-weight: 650; letter-spacing: -.045em; }
    .vm-auth-muted { margin: 9px 0 28px; color: #969696; text-align: left; font-size: .9rem; line-height: 1.55; }
    .vm-auth-label { display: block; margin: 19px 0 8px; color: #c7c7c7; font-size: .82rem; font-weight: 600; }
    .vm-auth-input { width: 100%; box-sizing: border-box; border: 1px solid #3d3d3d; border-radius: 8px; padding: 12px 13px; background: #202020; color: #f5f5f5; font: inherit; font-size: .92rem; outline: none; transition: border-color .15s, box-shadow .15s, background .15s; }
    .vm-auth-input:focus { border-color: #b5b5b5; background: #242424; box-shadow: 0 0 0 3px #fff1; }
    .vm-auth-button { width: 100%; margin-top: 24px; border: 1px solid #f3f3f3; border-radius: 8px; padding: 12px 16px; background: #f3f3f3; color: #171717; font: inherit; font-size: .9rem; font-weight: 650; cursor: pointer; transition: background .15s, transform .15s; }
    .vm-auth-button:hover { background: #dcdcdc; }
    .vm-auth-button:active { transform: translateY(1px); }
    .vm-auth-switch { margin-top: 24px; color: #969696; text-align: center; font-size: .85rem; }
    .vm-auth-switch a { color: #f2f2f2; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
    .vm-auth-switch a:hover { color: #c9c9c9; }
    .vm-auth-alert, .vm-auth-notice { margin: 0 0 20px; border-radius: 8px; padding: 11px 12px; font-size: .84rem; line-height: 1.5; }
    .vm-auth-alert { border: 1px solid #693b3b; background: #351f1f; color: #ffd1cd; }
    .vm-auth-notice { border: 1px solid #315b45; background: #1d3026; color: #c7f1d7; }
    .vm-auth-demo { margin-top: 14px; background: transparent; color: #eee; border-color: #414141; }
    .vm-auth-demo:hover { background: #252525; }
    @media (max-width: 760px) { .vm-auth-shell { padding: 24px; } .vm-auth-card { width: min(520px, 100%); min-height: 0; grid-template-columns: 1fr; } .vm-auth-visual { min-height: 230px; } .vm-auth-visual-top { top: 20px; left: 22px; right: 22px; } .vm-auth-visual-caption { left: 22px; right: 22px; bottom: 20px; } .vm-auth-visual-caption h2 { font-size: 1.8rem; } .vm-auth-visual-caption p { display: none; } .vm-auth-form { padding: 34px 28px 32px; } }
    @media (max-width: 420px) { .vm-auth-shell { padding: 12px; } .vm-auth-card { border-radius: 14px; } .vm-auth-visual { min-height: 185px; } .vm-auth-visual-caption h2 { font-size: 1.55rem; } .vm-auth-form { padding: 30px 22px; } }
</style>
<main class="vm-auth-shell">
    <section class="vm-auth-card">
        <aside class="vm-auth-visual" aria-label="Storefront preview">
            <img class="vm-auth-visual-image" src="<?php echo $dataset[$random_anchor]['image']; ?>" alt="Featured product from an independent storefront">
            <div class="vm-auth-visual-top"><img class="vm-auth-mark" src="/assets/favicon.png " alt=""><span>Embedded Engine</span></div>
            <div class="vm-auth-visual-caption">
                <p class="vm-auth-eyebrow"><?php echo $dataset[$random_anchor]['subtitle']; ?></p>
                <h2><?php echo $dataset[$random_anchor]['title']; ?></h2>
                <p><?php echo $dataset[$random_anchor]['description']; ?></p>
            </div>
        </aside>
        <div class="vm-auth-form">
            <?php if ($auth_error !== ''): ?><div class="vm-auth-alert"><?php echo htmlspecialchars($auth_error); ?></div><?php endif; ?>
            <?php if ($auth_notice !== ''): ?><div class="vm-auth-notice"><?php echo htmlspecialchars($auth_notice); ?></div><?php endif; ?>
            <?php if ($auth_mode === 'confirm' || $pending_auth !== ''): ?>
                <h1>Confirm your account</h1>
                <p class="vm-auth-muted">Enter the six-digit code delivered to your account confirmation channel.</p>
                <form method="post">
                    <input type="hidden" name="action" value="confirm">
                    <label class="vm-auth-label" for="otp">Confirmation code</label>
                    <input class="vm-auth-input" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
                    <button class="vm-auth-button" type="submit">Confirm account</button>
                </form>
            <?php elseif ($auth_mode === 'register'): ?>
                <h1>Create your account</h1>
                <p class="vm-auth-muted">Set up your workspace to get started.</p>
                <form method="post">
                    <input type="hidden" name="action" value="register">
                    <label class="vm-auth-label" for="name">Name</label><input class="vm-auth-input" id="name" name="name" required>
                    <label class="vm-auth-label" for="email">Email</label><input class="vm-auth-input" id="email" type="email" name="email" required>
                    <label class="vm-auth-label" for="password">Password</label><input class="vm-auth-input" id="password" type="password" name="password" minlength="8" required>
                    <button class="vm-auth-button" type="submit">Create account</button>
                </form>
                <div class="vm-auth-switch">Already have an account? <a href="?mode=login">Sign in</a></div>
            <?php else: ?>
                <h1>Sign in</h1>
                <p class="vm-auth-muted">Use your Embedded Store account to continue.</p>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <label class="vm-auth-label" for="identity">Email or username</label><input class="vm-auth-input" id="identity" name="identity" required autofocus>
                    <label class="vm-auth-label" for="login-password">Password</label><input class="vm-auth-input" id="login-password" type="password" name="password" required>
                    <button class="vm-auth-button" type="submit">Sign in</button>
                </form>
                <?php if (isset($_SERVER['__DEMO__']) && $_SERVER['__DEMO__'] === 'active'): ?>
                    <button class="vm-auth-button vm-auth-demo" type="button" onclick="window.location='/demo.php'">Open demo workspace</button>
                <?php endif; ?>
                <div class="vm-auth-switch">New to Embedded Store? <a href="?mode=register">Create an account</a></div>
            <?php endif; ?>
        </div>
    </section>
</main>