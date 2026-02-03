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
    $db = __DB_MODULE__; 

    #Recieve THe Data From The Forms 
    $website_name = $_POST['wb_name']; 
    $website_domain = $_POST['wb_domain']; 

    $account_number = $_POST['account_number'];
    $account_type = $_POST['account_type'];
    $account_provider = $_POST['account_provider'];
    $account_branch = $_POST['account_branch'];

    $billing_street = $_POST['bstreet']; 
    $billing_zip = $_POST['bzip']; 
    $billing_city = $_POST['bcity']; 
    $billing_country = $_POST['bcountry']; 
    $billing_state = $_POST['bstate']; 

    $account_index = __ACCOUNT_INDEX__; 
    $name = $website_name; 
    $domain = $website_domain; 
    $theme = "exalt"; 
    
    $hash_key = hash('sha256',uniqid('key')); 
    $signature_key = str_shuffle(hash('sha256',uniqid('signature'))); 

    $banking_data = __encryption__(json_encode([
        'number' => $account_number,
        'type' => $account_type,
        'branch' => $account_branch,
        'provider' => $account_provider,
    ],JSON_PRETTY_PRINT),$signature_key);


    $account_data = base_encryption(json_encode([
        "street" => $billing_street,
        "city" => $billing_city,
        "state" => $billing_state,
        "zip" => $billing_zip,
        "country" => $billing_country,
    ],JSON_PRETTY_PRINT)); 

    $e = database_services($domain); 
    $e = website_services($domain,$theme); 

    $sql = "UPDATE sys_account SET `data` = '{$account_data}' WHERE (`auth` = '{$account_index}');"; 
    $db->query($sql);

    $sql = "INSERT INTO `sys_banking` (`account_index`,`signature_key`,`data`) VALUES ('{$account_index}','{$signature_key}','{$banking_data}')"; 
    $db->query($sql);

    $sql = "INSERT INTO `sys_websites` (`name`,`domain`,`theme`,`hash_key`,`account_index`,) VALUES ('{$name}','{$domain}','{$theme}','{$hash_key}','{$account_index}')"; 
    $db->query($sql);
        
    echo "<script>window.location.href = '/home/';</script>";
    exit;
}
?>
    <!-- DASHBOARD SECTION (Hidden by default) -->
    <div id="dashboard-container" class="container">
        <?php @include_once "header.php"; ?> 

    <main>
        <div>
            
            <?php @include_once "modal.setup.php"; ?>

        </div>

    </main>

    </div>
