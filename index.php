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

// Note: /track/ is served directly by Apache from track/index.php (self-contained, no bootstrap)

if ($page == "store-access"){
    $e = dirname(__FILE__)."/api/index.php";
    include_once $e;
    die(0);
}

if ($page == "apk"){
    $e = dirname(__FILE__)."/apk/index.php";
    include_once $e; 
    die(0);
}

if ($page == "sync-github"){
    @include_once dirname(__FILE__)."/module/github.php";
    die(0);  
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
}else if ($page == "websites"){
    $website = dirname(__FILE__)."/sites/".__DOMAIN__."/index.php";
    if (file_exists($website)){
        include_once $website; 
        die(0); 
    }else{
        @include_once dirname(__FILE__)."/pages/error.404.lost.php";
        die(0);  
    }
}else{
    @include_once dirname(__FILE__)."/pages/error.404.lost.php";
    die(0);  
}
?> 