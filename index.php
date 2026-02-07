<?php 
session_start(); 

// Enable error logging
ini_set('log_errors', 1);

// Specify the error log file
ini_set('error_log', dirname(__FILE__).'/build/error-file.log');

// Set the error reporting level
error_reporting(E_ALL);


@include_once ".register.php"; 
@include_once "config.php";

$map = map(); 

if (strlen(ex(1)) <= 0){
    $page = "home";  
}else{
    $page = ex(); 
}

if ($page == "app"){
    $e = dirname(__FILE__)."/app/index.php";
    include_once $e; 
    die(0);  
}else if ($page == "vm-admin"){
    $e = dirname(__FILE__)."/vm-admin/index.php";
    include_once $e; 
    exit(0); 
}else if (isset($map[$page])){
    @include_once dirname(__FILE__)."/interface.php"; 
    die(0); 
}else{
    @include_once dirname(__FILE__)."/pages/error.404.lost.php";
    die(0);  
}
?> 