<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 'On'); 
@include dirname(dirname(__FILE__))."/config.php"; 

# Create The Admiin Account And Preserve The Session.

$key = 'vm_key' ?? null;
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
    $sql = "SELECT * FROM `sys_account` WHERE (`email` = '{$email}') OR (`name` = '{$name}') LIMIT 1";
    $e = __DB_MODULE__->query($sql); 
    $e = count($e); 
    if (($e) > 0){
        echo json_encode(["error"=>"Account Already Exists"],JSON_PRETTY_PRINT);
        #return false; 
    }else{
        $sql = "INSERT INTO `sys_account` (`name`,`email`,`image`,`auth`) VALUES ('{$name}','{$email}','{$image}','{$auth}')"; 
        echo json_encode(["index"=>$auth,"message"=>"Account Created"],JSON_PRETTY_PRINT); 
        $e = __DB_MODULE__->query($sql); 
    }   
}else{
    echo json_encode(["error" => "Unauthorized Access"],JSON_PRETTY_PRINT);
}

function encode($data,$key){
    return $data; 
}
?>