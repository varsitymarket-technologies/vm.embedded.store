<?php 
$database_build = dirname(__FILE__)."/build/vm.engine.sql";
if (!file_exists($database_build)) {
    @include_once dirname(__FILE__)."/pages/error.500.database.php";

    @include_once dirname(__FILE__)."/services/sys.database.reboot.php";
    
    exit(0);  
}
?>