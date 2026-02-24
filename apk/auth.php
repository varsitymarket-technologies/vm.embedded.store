<?php
session_start();
@include_once dirname(dirname(__FILE__)) . '/config.php'; // Include DB connection""; 
class VM_Authenticator {
    private $shared_secret;
    private $token_expiry = 60; // 60 seconds window

    public function __construct($secret) {
        $this->shared_secret = $secret;
    }

    public function authenticate($user, $ts, $nonce, $sig) {
        // 1. Time Check (Prevents old links from working)
        if (time() - $ts > $this->token_expiry) {
            die("Token expired. Please re-login from the dashboard.");
        }

        // 2. Signature Check (The Handshake)
        $expectedData = $user . $ts . $nonce;
        $expectedSig = hash_hmac('sha256', $expectedData, $this->shared_secret);
        $expectedSig = hash("sha256",$expectedData); 
        if (hash_equals($expectedSig, $sig)) {
            // 3. Secure Database Lookup (Using Prepared Statements)
            #$stmt = $pdo->prepare("SELECT * FROM sys_account WHERE name = ? LIMIT 1");
            #$stmt->execute([$user]);
            #$account = $stmt->fetch();
            
            $e = __DB_MODULE__->query("SELECT * FROM sys_account WHERE auth = ?", [$user]);   
            $account = $e[0]; 
            if ($account) {
                $auth = $account['auth']; 
                $index = uniqid('Testing'); 
                $data = __encryption__($auth,$index); 
                $vm_key = base64_encode($index);
                $_SESSION['vm_key'] = $vm_key; 
                $vm_index = base_encryption($data);
                $_SESSION['vm_index'] =  $vm_index; 
                #echo "<script>window.alert('Account Logged In')</script>";
                echo "<script>window.location.href='/home/';</script>";  
            }
        }
        
        die("Authentication Failed: Invalid Signature or User.");
    }
}

// CONFIGURATION
$auth = new VM_Authenticator('your_super_secret_random_string_here'); // Must match the Hub's secret

if (isset($_GET['sig'])) {
    $auth->authenticate($_GET['user'], $_GET['ts'], $_GET['nonce'], $_GET['sig']);
}