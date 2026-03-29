<?php 
$database_build = dirname(__FILE__)."/build/vm.engine.sql";
if (!file_exists($database_build)) {
    @include_once dirname(__FILE__)."/pages/error.500.database.php";

    @include_once dirname(__FILE__)."/services/sys.database.reboot.php";
    
    exit(0);  
}


function sync_themes() {
    $remote_manifest_url = "https://raw.githubusercontent.com/varsitymarket-technologies/embedded-themes/refs/heads/main/collection/records.json";
    $remote_base_url = "https://raw.githubusercontent.com/varsitymarket-technologies/embedded-themes/refs/heads/main/collection/";
    $local_root = __DIR__ . '/themes/';

    $manifest_json = @file_get_contents($remote_manifest_url);
    if ($manifest_json === false) return;
    
    $local_json = file_get_contents($local_root."records.json"); 
    if ($local_json  == $manifest_json){
        return null;    
    } 

    $themes_library = json_decode($manifest_json, true);
    if (!is_array($themes_library)) return;

    foreach ($themes_library as $theme_id => $data) {
        $theme_dir = $local_root . $theme_id . '/';
        $hash_file = $theme_dir . '.version_hash';
        
        $remote_hash = $data['hash'] ?? '';
        $files = $data['files'] ?? [];
        $local_hash = file_exists($hash_file) ? trim(file_get_contents($hash_file)) : '';

        // Only sync if the hash has changed
        if ($remote_hash !== $local_hash) {
            if (!is_dir($theme_dir)) mkdir($theme_dir, 0755, true);

            foreach ($files as $file_path) {
                $remote_file_url = $remote_base_url . $theme_id . '/' . $file_path;
                $local_file_path = $theme_dir . $file_path;

                // Create sub-sub-directories (e.g., theme/neon-nights/scripts/)
                $sub_dir = dirname($local_file_path);
                if (!is_dir($sub_dir)) mkdir($sub_dir, 0755, true);

                $content = @file_get_contents($remote_file_url);
                if ($content !== false) {
                    file_put_contents($local_file_path, $content);
                }
            }

            // Update this specific theme's hash
            file_put_contents($hash_file, $remote_hash);
        }
    }

    file_put_contents($local_root."records.json",$manifest_json);
}

$e = sync_themes(); 
?>