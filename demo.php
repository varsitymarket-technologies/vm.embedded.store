<?php 
session_start();

@include dirname(__FILE__)."/config.php"; 

$testing = false;
if (isset($_SERVER['__DEMO__'])){
    if ($_SERVER['__DEMO__'] == "active"){
        $testing = true;
    }
}

if ($testing){
        # hard Coding The Auth Credentials 
        $name = "demo_".substr(str_shuffle('1234567890'),4);
        $email = $name."@vmtech.co.za";
        $image = '/assets/favicon.png';   
        $auth = uniqid(str_shuffle(bin2hex(random_bytes(32))));
        $sql = "INSERT INTO `sys_account` (`name`,`email`,`image`,`auth`) VALUES (?, ?, ?, ?)"; 
        $e = __DB_MODULE__->query($sql, [$name, $email, $image, $auth]); 

        $index = str_shuffle(uniqid("sys_account")); 
        $data = __encryption__($auth,$index); 

        $vm_key = base64_encode($index);
        $_SESSION['vm_key'] = $vm_key; 
        $vm_index = base_encryption($data);
        $_SESSION['vm_index'] =  $vm_index; 
        #echo "<script>window.alert('Demo Account Activated'); </script>"; 
        echo "<script>window.location='/home/'</script>";
        exit();  
    //define('SYSTEM_ERROR',$sql); 
}

echo "<script>window.location='/home/'</script>";