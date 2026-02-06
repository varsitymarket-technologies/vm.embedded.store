<?php
#   TITLE   : System Database Restoration   
#   DESC    : This script is required to handle the main database restoration function.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/02

require_once dirname(dirname(__FILE__)).'/scripts.php';
$backup_file = dirname(dirname(__FILE__))."/build/engine.backup";
$index_file = dirname(dirname(__FILE__))."/build/".uniqid(date('d-m-Y_')).".engine.backup";
$target_file = dirname(dirname(__FILE__))."/build/vm.engine.sql"; 
echo "\nStarting Database Backup\n";
if (!file_exists($target_file)){
    trigger_error('Database File Is Not Present'); 
    exit(0); 
}
$e = base_encryption(file_get_contents($target_file)); 

#Make A Temporary Backup 
$ex = file_put_contents($backup_file,$e); 

#Index Backup 
$ex = file_put_contents($index_file,$e); 
echo "\nBackup File: ".$index_file."\n";
echo "\nDatabase Backup Complete\n\n";
exit(0);  
?>

