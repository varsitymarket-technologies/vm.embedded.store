<?php 
#   TITLE   : Website Interface    
#   DESC    : The website script to restart essential services for the website interface.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/01

# Theme Folder
$theme_template = file_get_contents(__DIR__."/theme");
$theme_dir = dirname( dirname( dirname(__FILE__) )) ."/themes/" . $theme_template . "/interface"; 

# Module Folder 
$module_dir = dirname( dirname( dirname(__FILE__) ))."/module/";

# System Database 
$database = dirname(__FILE__)."/storage.data";
@include_once dirname(dirname(dirname(__FILE__))). "/scripts.php"; 

#Restart Database 
$database_module = __DB_MODULE__; 
$database_module->override_connection($database); 
# Configuration 
$encode_node = extract_theme_nodes($theme_dir); 
$encode_node = array_unique($encode_node); 

function e($data){echo $data;}

$site_config = dirname(__FILE__)."/config.php"; 
if (!file_exists($site_config)){
    $data_set = "<?php".PHP_EOL;
    foreach ($encode_node as $key => $value) {
        @include_once dirname( dirname( dirname(__FILE__)))."/config.php"; 
        
        if ($value == "__SYSTEM_API__"){
            $data_set .= 'define("'.$value.'","api.php");'.PHP_EOL;
        }else if ($value == "__SYSTEM_CURRENCY__"){
            $data_set .= 'define("'.$value.'","'.__CURRENCY_SIGN__.'");'.PHP_EOL;
        }else{
            $data_set .= 'define("'.$value.'","");'.PHP_EOL;
        }

         
    }
    file_put_contents($site_config, $data_set);
}

@include_once $site_config; 
$site_encode = dirname(__FILE__)."/encode.php"; 
if (!file_exists($site_encode)){
    $template_structure = file_get_contents($theme_dir); 
    foreach ($encode_node as $key => $value) {
        $template_structure = str_ireplace("e(".$value.")", '<?php e('.$value.'); ?>',$template_structure); 
    }
    file_put_contents($site_encode, $template_structure);
}
@include_once $site_encode; 

?>