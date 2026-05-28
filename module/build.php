<?php 
// Enable error logging
session_start(); 
ini_set('log_errors', 1);


include_once "vm.github.php";

$name = "danger"; 

$seed = "Our Online Embedded Micro Websetore."; 

$env_data = [
    'description'=>('paraphase the following: {'.$seed.'}, description cannot be more than 200 characters'),
    'homepage'=>'https://'.($name).".varsitymarket.club",
    'private'=>true,
]; 

$subdomain = strtolower($name.".levidoc.co.za"); 

#$session = new varsitymarket_github_services(file_get_contents(dirname(__FILE__)."/phase3"));
$session = new varsitymarket_github_services($_SESSION['github_token']); 

# Creating A New Enviroment
$session->create_enviroment($name,$env_data); 

#Create Custom Domain 
$session->configure_subdomain($subdomain); 

#Update The Repository 
$rep_data = [
    'private'=>false,
    'homepage'=>'https://'.strtolower($subdomain),
];  

$session->update_enviroment($name,$rep_data); 
# Listing Available Enviroments

#Reconfigure Github Pages 
$session->enable_domain(strtolower($subdomain)); 

echo "Completed Transactions"; 
?>