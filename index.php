<?php 
session_start(); 

// Enable error logging
ini_set('log_errors', 1);

// Specify the error log file
ini_set('error_log', dirname(__FILE__).'/build/error-file.log');

// Set the error reporting level
error_reporting(E_ALL);


@include_once ".register.php"; 

@include_once "interface.php"; 

?> 