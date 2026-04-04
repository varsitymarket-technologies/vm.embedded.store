<?php
// 1. Get the currently active theme to show an "Active" badge
$current_theme_path = dirname(dirname(dirname(__FILE__)))."/sites/".__DOMAIN__."/theme";
$active_theme = file_exists($current_theme_path) ? file_get_contents($current_theme_path) : '';

# Save The Theme 
if (isset($_POST['edthemes'])){
    $name = $_POST['edthemes'] ?? null; 
    $saved_path = dirname(dirname(dirname(__FILE__)))."/sites/".__DOMAIN__."/theme"; 
    file_put_contents($saved_path, $name); 

    # Remove The Encoded File 
    unlink(dirname($saved_path)."/config.php"); 
    unlink(dirname($saved_path)."/encode.php");
    unlink(dirname($saved_path)."/builder.cache.html");

    
    // Refresh to show active state
    echo "<script>window.location.href = window.location.href;</script>";
    exit;
}

$path = dirname(dirname(dirname(__FILE__))).'/themes/*';
$directories = glob($path, GLOB_ONLYDIR);
$theme_library = []; 

foreach ($directories as $key => $value) {
    $name = str_ireplace(dirname(dirname(dirname(__FILE__))).'/themes/','',$value);
    
    // Check if poster exists, otherwise use a default or keep null for placeholder
    $poster_path = '/themes/'.$name.'/poster.png';
    
    $theme = [];  
    $theme['id'] = $key; 
    $theme['title'] = $name; 
    $theme['image'] = $poster_path; 
    $theme['color'] = 'from-white-600 to-purple-600'; 
    $theme['author'] = 'vmTECH'; 
    $theme['type'] = 'E-commerce';
    $theme['version'] = 'Embedded v1.0';  
    $theme['is_active'] = ($name === $active_theme);

    $theme_library[] = $theme; 
}

$themes = $theme_library; 
?>

<div class="flex flex-1 flex-col overflow-hidden bg-[#060606]">
    <?php @include_once "header.php"; ?>

    <div class="p-8 overflow-y-auto">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white">Template Library</h2>
            <p class="text-gray-400">Select a visual style for your project</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ($themes as $theme): ?>
                <div class="group bg-[#1a1a1a] rounded-xl overflow-hidden border <?php echo $theme['is_active'] ? 'border-purple-500' : 'border-white/5'; ?> hover:border-purple-500/50 transition-all duration-300 flex flex-col shadow-xl">
                    
                    <div class="relative aspect-[16/10] bg-[#0f0f0f] overflow-hidden">
                        
                        <img src="<?php echo $theme['image']; ?>" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                             alt="<?php echo $theme['title']; ?>">

                        <div class="hidden absolute inset-0 p-6 opacity-30 flex flex-col gap-3">
                            <div class="h-4 w-3/4 rounded bg-gradient-to-r <?php echo $theme['color']; ?>"></div>
                            <div class="h-24 w-full rounded-lg bg-white/5 border border-white/10"></div>
                        </div>
                        
                        <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center gap-3 backdrop-blur-sm">
                            <form method="POST">
                                <input type="hidden" name="edthemes" value="<?php echo $theme['title']; ?>">
                                <button type="submit" class="bg-white text-black font-bold px-4 py-2 rounded-md hover:bg-purple-500 hover:text-white transition-all transform translate-y-2 group-hover:translate-y-0 shadow-lg">
                                    Activate Theme
                                </button>
                            </form>
                            <button class="bg-gray-800 text-white p-2.5 rounded-md hover:bg-gray-700 transition-all transform translate-y-2 group-hover:translate-y-0">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <?php if($theme['is_active']): ?>
                            <span class="absolute top-3 right-3 px-2 py-1 bg-purple-500 text-white text-[10px] font-black uppercase tracking-widest rounded shadow-lg">
                                Active
                            </span>
                        <?php endif; ?>

                        <span class="absolute top-3 left-3 px-2 py-1 bg-black/50 backdrop-blur-md rounded text-[10px] font-bold uppercase tracking-widest border border-white/10">
                            <?php echo $theme['type']; ?>
                        </span>
                    </div>

                    <div class="p-5 flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-gray-100 group-hover:text-purple-400 transition-colors leading-tight">
                                    <?php echo ucwords(str_replace('_', ' ', $theme['title'])); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">by <?php echo $theme['author']; ?></p>
                            </div>
                            <span class="text-[10px] text-gray-400 bg-white/5 px-2 py-1 rounded">v<?php echo $theme['version']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>