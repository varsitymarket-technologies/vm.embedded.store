<?php 
@include_once dirname(dirname(__FILE__))."/config.php";

function export_application($website,$domain){
    $website_hash = hash("sha256",$website); 
    return $domain."/app/".$website_hash."/"; 
}
?>