<?php 
@include_once dirname(dirname(__FILE__))."/config.php";

$site_request = ex(2); 
$website_folder = dirname(dirname(__FILE__))."/sites/"; 
$websites = scandir($website_folder);
$website = dirname(dirname(__FILE__))."/sites//index.php";
foreach ($websites as $key => $value) {
    $e = hash("sha256",$value); 
    if ($e == $site_request){
        $website_id = $key; 
        $website = dirname(dirname(__FILE__))."/sites/".$websites[$website_id]."/index.php";
    }
}

if (file_exists($website)){
    if (stripos("u:".ex(3),"api.php") >= 1 ){
        include_once dirname($website)."/api.php" ; 
    }else{
        include_once ($website); 
    }
    die(0); 

}

$page = dirname(dirname(__FILE__))."/pages/error.500.deployment.php";
@include_once $page;  
die(0); 
?>