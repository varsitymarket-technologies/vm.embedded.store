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

if (!file_exists($database)) {
    @include_once __DIR__ . "/trace.php";
    die();
} else {

    @include_once dirname(dirname(dirname(__FILE__))) . "/scripts.php";

    #Restart Database 
    $database_module = __DB_MODULE__;
    $database_module->override_connection($database);


    $page_slug = routes(1);
    if (empty($page_slug) || $page_slug === 'home') {
        $page_slug = 'index';
    }

    $segment2 = routes(2);
    if ($page_slug === 'product') {
        $GLOBALS['product_id'] = !empty($segment2) ? $segment2 : 1;
    }

    $pages_dir = dirname(__FILE__) . "/pages/";
    $site_preview = $pages_dir . $page_slug . ".html";

    if (!file_exists($site_preview)) {
        if ($page_slug === 'product' && file_exists($pages_dir . "product.html")) {
            $site_preview = $pages_dir . "product.html";
        } elseif (file_exists($pages_dir . "500.html")) {
            $site_preview = $pages_dir . "500.html";
        }
    }

    // Fallback to builder.cache.html if pages directory routing didn't find anything
    if (!file_exists($site_preview) && file_exists(dirname(__FILE__) . "/builder.cache.html")) {
        $site_preview = dirname(__FILE__) . "/builder.cache.html";
    }

    if (file_exists($site_preview)) {
        //@include_once $site_preview;
        $website_sontents = file_get_contents($site_preview);
        $website = new compiler($website_sontents);
        $website->run();
    } else {

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

            file_put_contents($site_config, $data_set);
        }

        @include_once $site_config;
        $site_encode = dirname(__FILE__) . "/data/encode.php";
        if (!file_exists($site_encode)) {
            $template_theme = file_get_contents(dirname($site_encode) . "/body.php");
            $template = $template_theme;
            $pattern = '/e\(\$([a-zA-Z0-9_]+)\)/';
            $result = preg_replace_callback($pattern, function ($matches) {
                $key = $matches[1];

                if (defined($key)) {
                    return constant($key);
                }

                return $matches[0];
            }, $template);

            file_put_contents(dirname($site_encode) . "/body.php", $result);
            file_put_contents($site_encode, '<?php #The Encode File ?>');
        }

        # REPLACE THE PLACEHOLDERS ON THE THEME


        @include_once __DIR__ . "/trace.php";
        die();
    }
}
