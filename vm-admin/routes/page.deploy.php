<?php
// 1. Setup Paths
$active_theme_file = dirname(dirname(dirname(__FILE__)))."/sites/".__DOMAIN__."/theme";
$active_theme_name = file_exists($active_theme_file) ? trim(file_get_contents($active_theme_file)) : '';

$theme_base_path = dirname(dirname(dirname(__FILE__))).'/themes/'.$active_theme_name;

if (file_exists(dirname(dirname(dirname(__FILE__)))."/sites/".__DOMAIN__."/builder.cache.html")) {
    $index_file = dirname(dirname(dirname(__FILE__)))."/sites/".__DOMAIN__."/builder.cache.html";
} elseif (file_exists($theme_base_path.'/index.php')) {
    $index_file = $theme_base_path . '/index.php';
} else {
    $index_file = null; // No file to edit
}

// 2. Handle Save Request
if (isset($_POST['save_code'])) {
    $new_code = $_POST['code_content'];
    file_put_contents($index_file, $new_code);
    echo "<script>alert('Code deployed successfully!');</script>";
}

// 3. Load existing code
$current_code = file_exists($index_file) ? file_get_contents($index_file) : "";

// 4. Generate Site Links
$site_url = "https://" . __DOMAIN__;
$preview_url = $site_url . "?preview=true&theme=" . $active_theme_name;

$domain = __WEBSITE_DOMAIN__; 
$target = __DOMAIN__; 

@include dirname(dirname(dirname(__FILE__))). "/services/export.store.source.php"; 
if (!empty($current_code)){
    # pass; 
}else{
    $current_code = (export_application($target,$domain));
} 
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?> 

<div class="flex flex-col h-screen bg-[#060606] text-gray-200">
    <div class="h-16 border-b border-white/10 bg-[#111] px-6 flex items-center justify-between py-4">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-sm font-bold uppercase">Webstore Deployment</h1>
            </div>
        </div>

        <div class="flex items-center gap-3 items-justify-center">
            <div class="hidden lg:flex items-center bg-black/40 border border-white/5 rounded-lg px-3 py-1.5 text-xs">
                <i class="bi bi-globe2 text-gray-500 mr-2"></i>
                <span class="text-gray-400"><?php echo $site_url; ?></span>
                <a href="<?php echo $site_url; ?>" target="_blank" class="ml-3 text-purple-400 hover:text-purple-300">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>

            <button onclick="downloadCode()" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="bi bi-download mr-2"></i> Download Code
            </button>
            
            <form method="POST" class="m-0">
                <textarea name="code_content" id="hidden_code" class="hidden"></textarea>
                <button type="submit" name="save_code" onclick="syncCode()" class="bg-purple-600 hover:bg-purple-500 text-white px-6 py-2 rounded-md text-sm font-bold transition-all shadow-lg shadow-purple-500/20">
                    Publish Webstore
                </button>
            </form>
        </div>
    </div>
    <button onclick="code_editor()" class="lg:hidden absolute bottom-4 right-4 bg-gray-600 hover:bg-blue-500 text-white px-4 py-2 rounded-full uppercase shadow-lg shadow-blue-900/20 z-10">
        Edit Code
    </button>
    <script>
        function code_editor() {
            const editorPanel = document.getElementById('editor_panel');
            const previewPanel = document.getElementById('preview_panel');
            if (editorPanel.style.display === 'none' || !editorPanel.style.display) {
                editorPanel.style.display = 'flex';
                previewPanel.style.display = 'none';
            } else {
                editorPanel.style.display = 'none';
                previewPanel.style.display = 'flex';
            }
        }
    </script>


    <div class="flex flex-1 overflow-hidden">
        
        <div id="editor_panel" class="w-full lg:w-1/2 hidden lg:flex flex-col border-r border-white/10">
            <div class="bg-[#1a1a1a] px-4 py-2 text-[10px] font-bold text-gray-500 uppercase flex justify-between">
                <span>Source Editor</span>
                <span id="save_status">Saved</span>
            </div>
            <textarea id="editor" class="flex-1 bg-[#0d0d0d] text-emerald-400 p-6 font-mono text-sm outline-none resize-none leading-relaxed" spellcheck="false"><?php echo htmlspecialchars($current_code); ?></textarea>
        </div>

        <div id="preview_panel" class="w-full lg:w-1/2 flex flex-col bg-white">
            <div class="bg-[#1a1a1a] px-4 py-2 text-[10px] font-bold text-gray-500 uppercase">
                <span>Live Preview (Responsive)</span>

            </div>
            <iframe id="preview" class="w-full h-full border-none"></iframe>
        </div>

    </div>
</div>

</div>

<script>
const editor = document.getElementById('editor');
const preview = document.getElementById('preview');
const hiddenInput = document.getElementById('hidden_code');
const status = document.getElementById('save_status');

// Update preview in real-time
function updatePreview() {
    const content = editor.value;
    const doc = preview.contentDocument || preview.contentWindow.document;
    doc.open();
    doc.write(content);
    doc.close();
    status.innerText = "Unsaved Changes...";
    status.classList.add('text-yellow-500');
}

// Sync for form submission
function syncCode() {
    hiddenInput.value = editor.value;
}

// Download code as HTML file
function downloadCode() {
    const text = editor.value;
    const blob = new Blob([text], { type: 'text/html' });
    const a = document.createElement('a');
    a.download = 'store.html';
    a.href = window.URL.createObjectURL(blob);
    a.click();
}

// Listen for typing
editor.addEventListener('input', updatePreview);

// Initial Load
window.onload = updatePreview;
</script>