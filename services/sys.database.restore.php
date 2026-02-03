<?php
#   TITLE   : System Database Restoration   
#   DESC    : This script is required to handle the main database restoration function.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/02

require_once dirname(dirname(__FILE__)).'/scripts.php';
$backup_file = "/home/hastings/vm.embedded-sites/build/03-02-2026_6981ac5b40d40.engine.backup"; 
echo "\nRestorng Database From Backup Point\n";
$contents = base_decryption(file_get_contents($backup_file)) ?? false;
echo "\nDecoding Backup File\n";
if ($contents == false){
    if (file_exists($backup_file)){
        trigger_error("Failed To Decode The Database"); 
    }else{
        trigger_error("Database File Is Invalid"); 
    }
} 
$db = dirname(dirname(__FILE__))."/build/vm.engine.sql"; 
#Delete Current Database 
echo "\nRemoving Current Database\n";
unlink($db); 
$e = file_put_contents($db,$contents); 
echo "\nDatabase Restoration Complete\n\n";
exit(0);  