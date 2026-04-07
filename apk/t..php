<?php 

    $auth = $_POST['account']; 
    $domain = website_data('domain',$auth);
    if ($domain == false){
        echo json_encode(['error' =>'Could Not Locate Domain'],JSON_PRETTY_PRINT); 
        exit(); 
    } 

    $folder = dirname(dirname(__FILE__))."/sites/".$domain; 
    $e = delete_folder($folder); 

    # Checking If The Account Already Exists.
    $sql = "DELETE FROM `sys_account` WHERE (auth = ?) "; 
    $e = __DB_MODULE__->query($sql, [$auth]);
    
    $sql = "DELETE FROM `sys_websites` WHERE (account_index = ?) "; 
    $e = __DB_MODULE__->query($sql, [$auth]);


    ?>