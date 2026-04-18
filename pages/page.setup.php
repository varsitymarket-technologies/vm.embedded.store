<?php 
#   TITLE   : Page Setup    
#   DESC    : The setup page of the application. 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30
?>


<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = dirname(dirname(__FILE__))."/build/vm.engine.sql";
    $dbm = new database_manager($file);

    # Receive Data From The Forms
    $website_name = $_POST['wb_name'];
    
    // Domain Selection Logic
    $domain_type = $_POST['domain_type'] ?? 'custom';
    if ($domain_type === 'subdomain' && isset($_SERVER['PARENT_DOMAIN'])) {
        $prefix = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['subdomain_prefix']));
        $website_domain = $prefix . "." . $_SERVER['PARENT_DOMAIN'];
    } else {
        $website_domain = $_POST['wb_domain'];
    }

    $account_index = __ACCOUNT_INDEX__;
    $name = $website_name;
    $domain = $website_domain;
    $theme = "default";
    
    $hash_key = hash('sha256',uniqid('key'));
    $signature_key = str_shuffle(hash('sha256',uniqid('signature')));

    $account_data = base_encryption(json_encode([
        "street" => "Default",
        "city" => $_POST['bcity'] ?? "Default",
        "state" => "Default",
        "zip" => "0000",
        "country" => "South Africa",
    ],JSON_PRETTY_PRINT));

    @$e = database_services($domain);
    $e = website_services($domain,$theme);

    $sql = "UPDATE sys_account SET `data` = ? WHERE (`auth` = ?);";
    $e = $dbm->query($sql, [$account_data, $account_index]);

    $sql = "INSERT INTO `sys_websites` (`name`,`domain`,`theme`,`hash_key`,`account_index`) VALUES (?, ?, ?, ?, ?)";
    $e = $dbm->query($sql, [$name, $domain, $theme, $hash_key, $account_index]);

    echo "<script>window.location.href = '/home/';</script>";
    exit;
}

?>
    <!-- DASHBOARD SECTION (Hidden by default) -->
    <div id="dashboard-container" class="container">
        <?php @include_once "header.php"; ?> 

    <main>
        <div>
            
            <?php
            $active_billing = false; 
            if ($active_billing){
                @include_once "modal.billing.setup.php";
            }else{
                @include_once "modal.setup.php";
            }
            
            ?>

        </div>

    </main>

    </div>
