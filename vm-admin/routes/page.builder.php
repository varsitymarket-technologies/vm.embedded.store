<?php
$db = initiate_web_database(); 
$configFile = dirname(dirname(dirname(__FILE__))). "/sites/".__DOMAIN__."/config.php"; 

// --- Handle Save Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $content = file_get_contents($configFile);
    preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);
    
    $currentConfig = [];
    if (!empty($matches[1])) {
        foreach ($matches[1] as $idx => $key) { $currentConfig[$key] = $matches[2][$idx]; }
    }

    $newFileContent = "<?php" . PHP_EOL;
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'CONF_') === 0) {
            $cleanKey = str_replace('CONF_', '', $key);
            $currentConfig[$cleanKey] = $value;
        }
    }

    foreach ($currentConfig as $key => $val) {
        $newFileContent .= "define(\"$key\", \"$val\");" . PHP_EOL;
    }
    
    file_put_contents($configFile, $newFileContent);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Load and Categorize ---
$content = file_get_contents($configFile);
preg_match_all('/define\("([^"]+)",\s*"([^"]*)"\);/', $content, $matches);
$groups = ['Site' => [], 'Images' => [], 'Shop' => [], 'Colors' => []];

if (!empty($matches[1])) {
    foreach ($matches[1] as $index => $key) {
        $val = $matches[2][$index];
        // Categorization Logic
        if (preg_match('/(LOGO|IMG|BANNER|ICON|IMAGE)/i', $key)) $groups['Images'][$key] = $val;
        elseif (strpos($key, 'SITE') !== false) $groups['Site'][$key] = $val;
        elseif (strpos($key, 'SHOP') !== false) $groups['Shop'][$key] = $val;
        elseif (strpos($key, 'COLOR') !== false) $groups['Colors'][$key] = $val;
    }
}

function formatLabel($key) {
    return ucwords(strtolower(str_replace(['SITE_', 'SHOP_', 'DESIGN_', 'COLOR_', 'IMG_', '_'], ['', '', '', '', '', ' '], $key)));
}
?>
<div class="flex flex-1 flex-col overflow-hidden">
                <?php @include_once "header.php"; ?>
                
<div class="  bg-[transparent] text-gray-300 overflow-hidden font-sans">
    
    <nav class="h-14 border-b border-white/10  bg-[#242527] flex items-center justify-between px-4 shrink-0">
        <div class="flex items-center gap-4">
            <h1 class="font-bold text-white text-sm tracking-wide">PAGE BUILDER <span class="text-gray-500 font-normal ml-2">| <?php echo __DOMAIN__; ?></span></h1>
        </div>

        <div class="hidden md:flex items-center bg-gray-800 rounded-md p-1 border border-white/5">
            <button onclick="setPreview('desktop')" id="btn-desktop" class="px-3 py-1 rounded bg-gray-700 text-white text-xs transition-all">
                <i class="bi bi-display mr-1"></i> Desktop
            </button>
            <button onclick="setPreview('mobile')" id="btn-mobile" class="px-3 py-1 rounded text-gray-400 text-xs hover:text-white transition-all">
                <i class="bi bi-phone mr-1"></i> Mobile
            </button>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?php echo __WEBSITE_URL__; ?>" target="_blank" class="text-xs text-gray-400 hover:text-white transition-colors">
                <i class="bi bi-box-arrow-up-right mr-1"></i> View Live
            </a>
            <button onclick="document.getElementById('builderForm').submit()" class="bg-emerald-500 hover:bg-emerald-400 text-[#0f111a] text-xs font-bold py-1.5 px-4 rounded shadow-lg transition-transform active:scale-95">
                SAVE CHANGES
            </button>
        </div>
    </nav>

    <div class="flex flex-1 overflow-hidden">
        <aside class="w-80 border-r border-white/10  bg-[#242527] flex flex-col">
            <form id="builderForm" method="POST" class="flex-1 overflow-y-auto p-4 space-y-6 custom-scrollbar">
                <input type="hidden" name="save_config" value="1">

                <?php foreach ($groups as $groupName => $items): if (empty($items)) continue; ?>
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-black text-indigo-400 uppercase tracking-[2px] border-b border-white/5 pb-2">
                            <?php echo $groupName; ?>
                        </h3>
                        
                        <?php foreach ($items as $key => $value): ?>
                            <div class="group">
                                <label class="block text-[11px] font-semibold text-gray-500 mb-1.5 group-focus-within:text-indigo-400 transition-colors">
                                    <?php echo formatLabel($key); ?>
                                </label>

                                <?php if ($groupName === 'Colors'): ?>
                                    <div class="flex items-center gap-2 rounded border border-white/10 p-1 focus-within:border-indigo-500/50">
                                        <input type="color" value="<?php echo $value; ?>" oninput="this.nextElementSibling.value = this.value" class="w-8 h-8 rounded bg-transparent border-0 cursor-pointer">
                                        <input type="text" name="CONF_<?php echo $key; ?>" value="<?php echo $value; ?>" class="bg-transparent border-0 text-xs text-white focus:ring-0 w-full uppercase">
                                    </div>

                                <?php elseif ($groupName === 'Images'): ?>
                                    <div class="space-y-2">
                                        <?php if(!empty($value)): ?>
                                            <div class="h-20 w-full rounded bg-black/20 border border-white/5 overflow-hidden flex items-center justify-center p-2">
                                                <img src="<?php echo $value; ?>" class="max-h-full max-w-full object-contain opacity-80" onerror="this.src='https://placehold.co/100x50?text=No+Image'">
                                            </div>
                                        <?php endif; ?>
                                        <input type="text" name="CONF_<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>" placeholder="https://..." class="w-full  bg-[#242527] border border-white/10 rounded px-3 py-2 text-xs text-white focus:border-indigo-500/50 outline-none">
                                    </div>

                                <?php else: ?>
                                    <input type="text" name="CONF_<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>" class="w-full  bg-[#242527] border border-white/10 rounded px-3 py-2 text-xs text-white focus:border-indigo-500/50 outline-none transition-all">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </form>
        </aside>

        <main class="flex-1  bg-[transparent] p-4 md:p-10 flex flex-col items-center justify-center relative shadow-inner">
            <div id="preview-container" class="w-full h-full bg-white rounded-xl shadow-2xl overflow-hidden transition-all duration-500 ease-in-out border-[8px] border-gray-800">
                <iframe src="<?php echo __WEBSITE_URL__; ?>" id="preview-frame" class="w-full h-full border-0"></iframe>
            </div>
            
            <div class="mt-4 text-[10px] text-gray-600 flex items-center gap-4">
                <span><i class="bi bi-info-circle mr-1"></i> Preview reflects last saved state</span>
                <span><i class="bi bi-shield-check mr-1"></i> SSL Protected</span>
            </div>
        </main>
    </div>
</div>

</div>

<script>
    function setPreview(device) {
        const container = document.getElementById('preview-container');
        const btnDesktop = document.getElementById('btn-desktop');
        const btnMobile = document.getElementById('btn-mobile');

        if (device === 'mobile') {
            container.style.width = '375px';
            container.style.height = '667px';
            container.classList.add('border-[12px]', 'rounded-[40px]');
            btnMobile.className = 'px-3 py-1 rounded bg-gray-700 text-white text-xs transition-all';
            btnDesktop.className = 'px-3 py-1 rounded text-gray-400 text-xs hover:text-white transition-all';
        } else {
            container.style.width = '100%';
            container.style.height = '100%';
            container.classList.remove('rounded-[40px]');
            container.classList.add('rounded-xl');
            btnDesktop.className = 'px-3 py-1 rounded bg-gray-700 text-white text-xs transition-all';
            btnMobile.className = 'px-3 py-1 rounded text-gray-400 text-xs hover:text-white transition-all';
        }
    }
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #2d334a; border-radius: 10px; }
    iframe { transition: all 0.3s ease; }
</style>