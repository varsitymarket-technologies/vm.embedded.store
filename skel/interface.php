<?php
#   TITLE   : Website Interface    
#   DESC    : The website script to restart essential services for the website interface.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.2
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/01

# Theme Folder
$theme_template = file_get_contents(__DIR__ . "/theme");
$theme_dir = dirname(dirname(dirname(__FILE__))) . "/themes/" . $theme_template . "/interface";

# Module Folder 
$module_dir = dirname(dirname(dirname(__FILE__))) . "/module/";

# System Database 
$database = dirname(__FILE__) . "/storage.data";
@include_once dirname(dirname(dirname(__FILE__))) . "/scripts.php";

#Restart Database 
$database_module = __DB_MODULE__;
$database_module->override_connection($database);


$site_preview = dirname(__FILE__) . "/builder.cache.html";
if (file_exists($site_preview)) {
    //@include_once $site_preview;
    $website_sontents = file_get_contents($site_preview); 
    $website = new compiler($website_sontents); 
    $website->run(); 
} else {

    # Configuration 
    $encode_node = extract_theme_nodes($theme_dir);
    $encode_node = array_unique($encode_node);

    # Extract The Auto Fill 
    $auto_fill_file = dirname($theme_dir) . "/autofill.json";
    @$auto_fill = json_decode(file_get_contents($auto_fill_file), true) ?? [];

    function e($data)
    {
        echo $data;
    }

    $site_config = dirname(__FILE__) . "/config.php";
    if (!file_exists($site_config)) {
        $data_set = "<?php" . PHP_EOL;
        @include_once dirname(__FILE__) . "/autofill.php";
        $data_set .= construct_config_web_structure();

        $system_placeholders = [
            "__SYSTEM_API__",
            "__SYSTEM_ANALYTICS__",
            "__SYSTEM_API_KEYS__",
            "__STORE_INDEX__",
            "__SYSTEM_JS_API__",
            "__SYSTEM_JS_THEME__",
            "__SYSTEM_JS_CONNECT__",
            "__SYSTEM_CURRENCY__",
            "__SITE_TITLE__"
        ];

        $encode_node_config = $encode_node;

        // Source - https://stackoverflow.com/a/7225113
        // Posted by Bojangles, modified by community. See post 'Timeline' for change history
        // Retrieved 2026-06-21, License - CC BY-SA 3.0
        foreach ($system_placeholders as $placeholder) {
            if (($key = array_search($placeholder, $encode_node_config)) !== false) {
                unset($encode_node_config[$key]);
            }
        }

        foreach ($encode_node_config as $key => $value) {
            @include_once dirname(dirname(dirname(__FILE__))) . "/config.php";

            if ($value == "__SITE_THEME_AUTOFILL__") {
                $data_set .= 'define("' . $value . '","ACTIVE");' . PHP_EOL;
            } else {
                if (isset($auto_fill[$value])) {
                    $auto_data = $auto_fill[$value];
                } else {
                    $auto_data = "";
                }
                $data_set .= 'define("' . $value . '","' . $auto_data . '");' . PHP_EOL;
            }
        }
        file_put_contents($site_config, $data_set);
    }

    @include_once $site_config;
    $site_encode = dirname(__FILE__) . "/encode.php";
    if (!file_exists($site_encode)) {
        $template_structure = file_get_contents($theme_dir);
        foreach ($encode_node as $key => $value) {
            $template_structure = str_ireplace("e(" . $value . ")", '<?php e(' . $value . '); ?>', $template_structure);
        }
        file_put_contents($site_encode, $template_structure);
    }
    $website_sontents = file_get_contents($site_encode); 
    $website = new compiler($website_sontents); 
    $website->run(); 
    //@include_once $site_encode;
}
