<?php
#   TITLE   : Website Installation   
#   DESC    : This script is required to restore scripts that the website uses for functionality.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/01


$anchor_site = "reiddrop.com";
$anchor_theme = "exalt"; 

$website_folder = dirname(dirname(__FILE__))."/sites/";

if (!is_dir($website_folder.$anchor_site)){
    #Make The Website Directory 
    mkdir($website_folder.$anchor_site,0777,true); 
}


#require_once '../scripts.php';


# Copy Elements From The Skeleton Structure 

    $file = $website_folder."skel/.htaccess"; 
    $target_file = $website_folder.$anchor_site."/.htaccess"; 
    # Start with .htaccess 
    if (!file_exists($target_file)){
        file_put_contents($target_file,file_get_contents($file));
    }


    $element = "routes.php"; 
    $file = $website_folder."skel/".$element; 
    $target_file = $website_folder.$anchor_site."/".$element; 
    # Start with Routes File
    if (!file_exists($target_file)){
        file_put_contents($target_file,file_get_contents($file));
    }

    $element = "index.php"; 
    $file = $website_folder."skel/".$element; 
    $target_file = $website_folder.$anchor_site."/".$element; 
    # Start with Index File
    if (!file_exists($target_file)){
        file_put_contents($target_file,file_get_contents($file));
    }

    $element = "interface.php"; 
    $file = $website_folder."skel/".$element; 
    $target_file = $website_folder.$anchor_site."/".$element; 
    # Start with Routes File
    if (!file_exists($target_file)){
        file_put_contents($target_file,file_get_contents($file));
    }

    $element = "api.php"; 
    $file = $website_folder."skel/".$element; 
    $target_file = $website_folder.$anchor_site."/".$element; 
    # Start with Routes File
    if (!file_exists($target_file)){
        file_put_contents($target_file,file_get_contents($file));
    }

    $element = "theme"; 
    $target_file = $website_folder.$anchor_site."/".$element; 
    # Start with Routes File
    if (!file_exists($target_file)){
        file_put_contents($target_file,$anchor_theme);
    }

?>