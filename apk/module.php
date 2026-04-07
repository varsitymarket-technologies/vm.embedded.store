<?php 

#   TITLE   : APK Module   
#   DESC    : Creates and links your account to the avaialable susbscription.
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
    $name = $input['name']; 
    $image = $input['image'];
    $email = $input['email'];
    $auth = uniqid(str_shuffle(bin2hex(random_bytes(32))));

    # Checking If The Account Already Exists.
    $sql = "SELECT * FROM `sys_account` WHERE (`email` = ?) OR (`name` = ?) LIMIT 1";
    $e = __DB_MODULE__->query($sql, [$email, $name]); 
    $e = count($e); 
    if (($e) > 0){
        echo json_encode(["error"=>"Account Already Exists"],JSON_PRETTY_PRINT);
        #return false; 
    }else{
        $sql = "INSERT INTO `sys_account` (`name`,`email`,`image`,`auth`) VALUES (?, ?, ?, ?)"; 
        echo json_encode(["index"=>$auth,"message"=>"Account Created"],JSON_PRETTY_PRINT); 
        $e = __DB_MODULE__->query($sql, [$name, $email, $image, $auth]); 
    }   
}else{
    echo json_encode(["error" => "Unauthorized Access"],JSON_PRETTY_PRINT);
}

function encode($data,$key){
    return $data; 
}
?>