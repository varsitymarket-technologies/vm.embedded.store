<?php 
#   TITLE   : Routing File    
#   DESC    : The file responsible for re-routing traffic to the designated source.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/01

session_start(); 

// Enable error logging
ini_set('log_errors', 1);

// Specify the error log file
ini_set('error_log', dirname(__FILE__).'/error-file.log');

// Set the error reporting level
error_reporting(E_ALL);

@include_once dirname(__FILE__)."/interface.php"; 
?>