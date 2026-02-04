<?php
$db = initiate_web_database(); 

$configFile = dirname(dirname(dirname(__FILE__))). "/sites/".__DOMAIN__."/config.php"; 

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $e = $_POST;
    $content = file_get_contents($configFile);
    preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);

    $config = [];
    if (!empty($matches[1])) {
        foreach ($matches[1] as $index => $key) {
            $config[$key] = $matches[2][$index];
        }
    }
    $config_file = "<?php".PHP_EOL; 
    foreach ($e as $set_key => $set_value) {
        $search = str_ireplace('$_CONFIG_ANCHOR___','__',$set_key); 
        if (isset($config[$search])){ 
            $config[$search] = $set_value; 
        }
    }

    foreach ($config as $key => $value) {
        $config_file .= 'define("'.$key.'","'.$value.'");'.PHP_EOL; 
    }
    file_put_contents($configFile,$config_file); 

    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}


$content = file_get_contents($configFile);
preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);

$settings = [];
if (!empty($matches[1])) {
    foreach ($matches[1] as $index => $key) {
        $settings[$key] = $matches[2][$index];
    }
}

$shopItems = [];

$siteItems = []; 

$siteFonts = []; 

$siteColor = []; 

foreach ($settings as $key => $value) {
    if (strpos($key, 'SITE') !== false) {
        $siteItems[$key] = $value; 
    }

    if (strpos($key, 'SHOP') !== false) {
        $shopItems[$key] = $value;
    }

    if (strpos($key, 'DESIGN_FONT') !== false) {
        $siteFonts[$key] = $value;
    }

    if (strpos($key, 'DESIGN_COLOR') !== false) {
        $siteColor[$key] = $value;
    }

}

?>

<div class="flex flex-1 flex-col overflow-hidden h-full">
    <!-- Header -->
    <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10 shrink-0">
        <div class="flex items-center gap-4">
            <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
                <i class="bi bi-list text-2xl"></i>
            </button>
            <h2 class="text-lg font-semibold text-white">Page Builder</h2>
        </div>
        <div class="flex items-center gap-3">
             <button onclick="document.getElementById('builderForm').submit()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors text-sm font-medium">
                <i class="bi bi-save"></i> Save Changes
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Controls Sidebar -->
        <aside class="w-80 bg-gray-900 border-r border-white/10 overflow-y-auto custom-scrollbar z-10">
            <form id="builderForm" action="" method="POST" class="p-4 space-y-6">
                
                <!-- From Config -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Website Data</h3>
                    <?php foreach ($siteItems as $key => $value): ?>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1"><?php echo ucwords (strtolower( str_replace('_', ' ', trim($key, '_')))  ); ?></label>
                            <input type="text" name="$_CONFIG_ANCHOR_<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        </div>
                    <?php endforeach; ?>
                    <hr class="border-white/10">
                </div>


                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Shop Branding</h3>
                    <?php foreach ($shopItems as $key => $value): ?>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1"><?php echo ucwords (strtolower( str_replace('_', ' ', trim($key, '_')))  ); ?></label>
                            <input type="text" name="$_CONFIG_ANCHOR_<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                        </div>
                    <?php endforeach; ?>
                    <hr class="border-white/10">
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Color Theme</h3>
                    <?php foreach ($siteColor as $key => $value): ?>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1"><?php echo ucwords (strtolower( str_replace('_', ' ', trim($key, '_')))  ); ?></label>
                            <div class="flex gap-2">
                                <input type="color" value="<?php echo htmlspecialchars($value); ?>"  class="h-9 w-9 rounded cursor-pointer bg-transparent border-0 p-0">
                                <input type="text" name="$_CONFIG_ANCHOR_<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>"   class="flex-1 bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none uppercase" onchange="this.previousElementSibling.value = this.value">
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    <hr class="border-white/10">
                </div>


                
                <!-- Colors Section 
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Colors</h3>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Primary Color</label>
                        <div class="flex gap-2">
                            <input type="color" name="settings[primary_color]" value="<?php echo htmlspecialchars($current_settings['primary_color']); ?>" class="h-9 w-9 rounded cursor-pointer bg-transparent border-0 p-0">
                            <input type="text" value="<?php echo htmlspecialchars($current_settings['primary_color']); ?>" class="flex-1 bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none uppercase" onchange="this.previousElementSibling.value = this.value">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Secondary Color</label>
                        <div class="flex gap-2">
                            <input type="color" name="settings[secondary_color]" value="<?php echo htmlspecialchars($current_settings['secondary_color']); ?>" class="h-9 w-9 rounded cursor-pointer bg-transparent border-0 p-0">
                            <input type="text" value="<?php echo htmlspecialchars($current_settings['secondary_color']); ?>" class="flex-1 bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none uppercase" onchange="this.previousElementSibling.value = this.value">
                        </div>
                    </div>
                </div>

                <hr class="border-white/10">

                Typography 
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Typography</h3>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Font Family</label>
                        <select name="settings[font_family]" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-purple-500 focus:outline-none">
                            <option value="Inter" <?php echo $current_settings['font_family'] === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                            <option value="Roboto" <?php echo $current_settings['font_family'] === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                            <option value="Open Sans" <?php echo $current_settings['font_family'] === 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                        </select>
                    </div>
                </div>

                <hr class="border-white/10">

                 Layout 
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Layout</h3>
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-400">Show Hero Section</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[show_hero]" value="0">
                            <input type="checkbox" name="settings[show_hero]" value="1" class="sr-only peer" <?php echo $current_settings['show_hero'] ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                </div>
                -->

            </form>
        </aside>

        <!-- Preview Area -->
        <main class="flex-1 bg-gray-800 relative">
            <div class="absolute inset-0 p-4 md:p-8 bg-gray-900/50">
                <div class="bg-white h-full w-full rounded-lg shadow-2xl overflow-hidden border border-gray-700">
                    <iframe src="<?php echo __WEBSITE_URL__; ?>" class="w-full h-full border-0 bg-white"></iframe>
                </div>
            </div>
        </main>
    </div>
</div>