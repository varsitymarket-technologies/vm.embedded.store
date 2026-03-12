<?php 

#   TITLE   : APK Terminate Module   
#   DESC    : Terminate The Active .
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/03/08

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 'On'); 

@include dirname(dirname(__FILE__))."/config.php"; 

define("__AUTH_X__",$_SERVER['__VM_EMBEDDED_FREE_KEY__']); 
# Create The Admiin Account And Preserve The Session.

$key = __AUTH_X__ ?? null;
#Receive The Data From The Application.
@$input = encode($_POST['data'],$key) ?? false;
$input = json_decode($input,true);
@$session = base64_decode($_POST['key']) ?? false; 

if ($session == $key){
    $auth = $_POST['account']; 
    $domain = website_data('domain',$auth);
    if ($domain == false){
        echo json_encode(['error' =>'Could Not Locate Domain'],JSON_PRETTY_PRINT); 
        exit(); 
    } 

    $folder = dirname(dirname(__FILE__))."/sites/".$domain; 
    $e = delete_folder($folder); 

    # Checking If The Account Already Exists.
    $sql = "DELETE FROM `sys_account` WHERE (auth = '{$auth}') "; 
    $e = __DB_MODULE__->query($sql);
    
    $sql = "DELETE FROM `sys_websites` WHERE (account_index = '{$auth}') "; 
    $e = __DB_MODULE__->query($sql);
       
}else{
    echo json_encode(["error" => "Unauthorized Access"],JSON_PRETTY_PRINT);
}

function encode($data,$key){
    return $data; 
}
?>