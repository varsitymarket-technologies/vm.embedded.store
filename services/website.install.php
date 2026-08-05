<?php
#   TITLE   : Website Installation   
#   DESC    : This script is required to restore scripts that the website uses for functionality.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/01

if (!defined("__ANCHOR_SITE__")){
    trigger_error("Missing Website Details"); 
}

$anchor_site = __ANCHOR_SITE__; 


if (!defined("__ANCHOR_THEME__")){
    trigger_error("Missing Website Details"); 
}

$anchor_theme = __ANCHOR_THEME__; 


$website_folder = dirname(dirname(__FILE__))."/sites/";
$skel_folder = dirname($website_folder)."/skel";
$site_folder = $website_folder.$anchor_site;

if (!is_dir($site_folder)){
    # Make The Website Directory
    mkdir($site_folder,0777,true);
}

/**
 * Copy the shared skeleton into a site folder without clobbering
 * existing site-specific files.
 */
$copy_skel_tree = function (string $source, string $target) use (&$copy_skel_tree): void {
    if (is_dir($source)) {
        if (!is_dir($target)) {
            mkdir($target, 0777, true);
        }

        $entries = scandir($source);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === "." || $entry === "..") {
                continue;
            }

            $child_source = $source . DIRECTORY_SEPARATOR . $entry;
            $child_target = $target . DIRECTORY_SEPARATOR . $entry;

            if ($entry === "theme" && is_dir($child_source)) {
                # The site already uses /theme as a marker file.
                continue;
            }

            $copy_skel_tree($child_source, $child_target);
        }

        return;
    }

    if (!file_exists($target)) {
        file_put_contents($target, file_get_contents($source));
    }
};

$copy_skel_tree($skel_folder, $site_folder);

$theme_marker = $site_folder . "/theme";
if (!file_exists($theme_marker)) {
    file_put_contents($theme_marker, $anchor_theme);
}

?>
