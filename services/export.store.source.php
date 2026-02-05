<?php 
@include_once dirname(dirname(__FILE__))."/config.php"; 
$target = "hastings.com"; 

$theme_file = dirname(dirname(__FILE__))."/sites/".$target."/theme"; 
$theme = file_get_contents($theme_file); 
$theme_dir = dirname(dirname(__FILE__))."/themes/".$theme;

# Configuration 
$encode_node = extract_theme_nodes($theme_dir."/interface"); 
$encode_node = array_unique($encode_node); 

@include_once dirname($theme_file)."/config.php";

#source_code 
$source_code = file_get_contents($theme_dir."/interface"); 
foreach ($encode_node as $key => $value) {
    $source_code = str_ireplace("e(".$value.")",constant($value),$source_code); 
}
echo $source_code;
die(0); 
?>
?>