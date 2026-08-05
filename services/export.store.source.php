<?php 
@include_once dirname(dirname(__FILE__))."/config.php"; 
function export_application($website,$domain){
    $target = $website;
    $site_root = dirname(dirname(__FILE__))."/sites/".$target;
    $candidate_files = [
        $site_root . "/builder.cache.html",
        $site_root . "/pages/index.html",
        $site_root . "/index.html",
    ];

    foreach ($candidate_files as $file) {
        if (file_exists($file) && is_file($file)) {
            return file_get_contents($file);
        }
    }
    /* 

    $theme_file = $site_root . "/theme";
    if (file_exists($theme_file)) {
        $theme = trim((string) file_get_contents($theme_file));
        if ($theme !== '') {
            $theme_dir = dirname(dirname(__FILE__))."/themes/".$theme;
            foreach ([$theme_dir . "/index.html", $theme_dir . "/index.php", $theme_dir . "/interface"] as $theme_source) {
                if (file_exists($theme_source) && is_file($theme_source)) {
                    return file_get_contents($theme_source);
                }
            }
        }
    }

    */

    $website_hash = hash("sha256",$website);
    $link =  $domain."/app/".$website_hash."/";
    $template = file_get_contents(dirname(__FILE__)."/embedded.html");
    return str_ireplace(
        ['($WEBSITE_TITLE)','($WEBSITE_LINK)'],
        [$website,$link],$template
    );
}
?>
