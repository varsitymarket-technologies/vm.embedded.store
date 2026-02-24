<?php 
ini_set('display_errors', 'On'); 
error_reporting(E_ALL | E_STRICT);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Specify the error log file
ini_set('error_log', dirname(__FILE__).'/error-file.log');

$method = $_SERVER['REQUEST_METHOD'];
if ($method != "POST"){
    @include_once (dirname(__FILE__))."/auth.php";
}else{
    @include_once (dirname(__FILE__))."/module.php";
}

?>