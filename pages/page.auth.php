<?php
$auth_db = __DB_MODULE__;
$auth_error = '';
$auth_notice = '';
$auth_mode = ($_POST['mode'] ?? 'login') === 'register' ? 'register' : 'login';
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
    return trim((string) ('https://discord.com/api/webhooks/1551196754349793315/Rjo11alOlHRNEZgnafB6C6IsWaKTTyI1VmmPA_7Z8NKRlmMwQ33hnmGRvYwnuei5APLq'));
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
        $accounts = $auth_db->query("SELECT * FROM sys_account WHERE (email = ? OR name = ?) LIMIT 1", [$email, trim((string) ($_POST['identity'] ?? ''))]);
        $account = $accounts[0] ?? null;
        if (!$account || empty($account['password_hash']) || !password_verify($password, $account['password_hash'])) {
            $auth_error = 'Invalid email, username, or password.';
        } elseif (empty($account['email_verified'])) {
            $auth_error = 'Confirm your account from Discord before signing in.';
            $auth_mode = 'login';
        } else {
            vm_auth_start_session($account);
            header('Location: /home/');
            exit;
        }
    }
}
?>
<style>
    .vm-auth-shell { min-height: 100vh; display: grid; place-items: center; padding: 32px 18px; background: #171717; color: #ececec; font-family: Inter, ui-sans-serif, sans-serif; }
    .vm-auth-card { width: min(920px, 100%); display: grid; grid-template-columns: 1.05fr .95fr; overflow: hidden; border: 1px solid #3a3a3a; border-radius: 18px; background: #202020; box-shadow: 0 24px 80px #0008; }
    .vm-auth-copy { padding: 48px; background: linear-gradient(145deg, #252525, #181818); }
    .vm-auth-form { padding: 48px; background: #202020; }
    .vm-auth-mark { width: 42px; height: 42px; border-radius: 12px; margin-bottom: 52px; }
    .vm-auth-copy h1 { max-width: 360px; margin: 0 0 18px; font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1.02; letter-spacing: -.04em; }
    .vm-auth-copy p { max-width: 340px; color: #a1a1aa; line-height: 1.7; }
    .vm-auth-form h2 { margin: 0 0 8px; font-size: 1.45rem; }
    .vm-auth-muted { color: #8d8d93; font-size: .9rem; }
    .vm-auth-label { display: block; margin: 22px 0 8px; color: #b7b7bc; font-size: .78rem; font-weight: 600; }
    .vm-auth-input { width: 100%; box-sizing: border-box; border: 1px solid #444; border-radius: 9px; padding: 13px 14px; background: #181818; color: #fff; outline: none; }
    .vm-auth-input:focus { border-color: #10a37f; box-shadow: 0 0 0 3px #10a37f22; }
    .vm-auth-button { width: 100%; margin-top: 26px; border: 0; border-radius: 9px; padding: 13px 16px; background: #10a37f; color: #fff; font-weight: 700; cursor: pointer; }
    .vm-auth-button:hover { background: #0d8f70; }
    .vm-auth-switch { margin-top: 22px; text-align: center; color: #8d8d93; font-size: .85rem; }
    .vm-auth-switch button { border: 0; background: none; color: #65cdb0; cursor: pointer; }
    .vm-auth-alert { margin-top: 16px; border-radius: 8px; padding: 11px 12px; background: #642b2b; color: #ffd9d9; font-size: .85rem; }
    .vm-auth-notice { margin-top: 16px; border-radius: 8px; padding: 11px 12px; background: #194638; color: #d6fff1; font-size: .85rem; }
    @media (max-width: 700px) { .vm-auth-card { grid-template-columns: 1fr; } .vm-auth-copy, .vm-auth-form { padding: 30px; } .vm-auth-mark { margin-bottom: 28px; } }
</style>
<main class="vm-auth-shell">
    <section class="vm-auth-card">
        <div class="vm-auth-copy">
            <img class="vm-auth-mark" src="/assets/favicon.png" alt="Embedded Store">
            <h1>Build what matters.</h1>
            <p>Sign in to your Embedded Store workspace and keep your storefront moving.</p>
            <?php if (isset($_SERVER['__DEMO__']) && $_SERVER['__DEMO__'] === 'active'): ?>
                <button class="vm-auth-button" style="max-width:220px;background:#343434" onclick="window.location='/demo.php'">Open demo workspace</button>
            <?php endif; ?>
        </div>
        <div class="vm-auth-form">
            <?php if ($auth_error !== ''): ?><div class="vm-auth-alert"><?php echo htmlspecialchars($auth_error); ?></div><?php endif; ?>
            <?php if ($auth_notice !== ''): ?><div class="vm-auth-notice"><?php echo htmlspecialchars($auth_notice); ?></div><?php endif; ?>
            <?php if ($auth_mode === 'confirm' || $pending_auth !== ''): ?>
                <h2>Confirm your account</h2>
                <p class="vm-auth-muted">Enter the six-digit code delivered to your Discord webhook.</p>
                <form method="post">
                    <input type="hidden" name="action" value="confirm">
                    <label class="vm-auth-label" for="otp">Confirmation code</label>
                    <input class="vm-auth-input" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
                    <button class="vm-auth-button" type="submit">Confirm account</button>
                </form>
            <?php elseif ($auth_mode === 'register'): ?>
                <h2>Create your workspace account</h2>
                <p class="vm-auth-muted">A confirmation code will be sent to Discord.</p>
                <form method="post">
                    <input type="hidden" name="action" value="register">
                    <label class="vm-auth-label" for="name">Name</label><input class="vm-auth-input" id="name" name="name" required>
                    <label class="vm-auth-label" for="email">Email</label><input class="vm-auth-input" id="email" type="email" name="email" required>
                    <label class="vm-auth-label" for="password">Password</label><input class="vm-auth-input" id="password" type="password" name="password" minlength="8" required>
                    <button class="vm-auth-button" type="submit">Create account</button>
                </form>
                <div class="vm-auth-switch">Already have an account? <button type="button" onclick="showLogin()">Sign in</button></div>
            <?php else: ?>
                <h2>Welcome back</h2>
                <p class="vm-auth-muted">Sign in to continue to your workspace.</p>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <label class="vm-auth-label" for="identity">Email or username</label><input class="vm-auth-input" id="identity" name="identity" required autofocus>
                    <label class="vm-auth-label" for="login-password">Password</label><input class="vm-auth-input" id="login-password" type="password" name="password" required>
                    <button class="vm-auth-button" type="submit">Sign in</button>
                </form>
                <div class="vm-auth-switch">New to Embedded Store? <button type="button" onclick="showRegister()">Create an account</button></div>
            <?php endif; ?>
        </div>
    </section>
</main>
<script>
function showRegister() { document.querySelector('input[name="action"]').value = 'register'; document.querySelector('.vm-auth-form').innerHTML = '<h2>Create your workspace account</h2><p class="vm-auth-muted">A confirmation code will be sent to Discord.</p><form method="post"><input type="hidden" name="action" value="register"><label class="vm-auth-label">Name</label><input class="vm-auth-input" name="name" required><label class="vm-auth-label">Email</label><input class="vm-auth-input" type="email" name="email" required><label class="vm-auth-label">Password</label><input class="vm-auth-input" type="password" name="password" minlength="8" required><button class="vm-auth-button" type="submit">Create account</button></form><div class="vm-auth-switch">Already have an account? <button type="button" onclick="location.reload()">Sign in</button></div>'; }
function showLogin() { location.reload(); }
</script>