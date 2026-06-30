<?php
$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$store_domain = __DOMAIN__ ?? '';

// Init Local Deployment DB
$db = __DB_WEBSITE__; // Local storage.data

// Handle Post Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'publish') {
            // Generate HTML string
            @include_once dirname(dirname(__FILE__)). "/services/export.store.source.php";
            $html_content = export_application(__DOMAIN__, __WEBSITE_DOMAIN__);
            
            // Deploy
            $user = __USERNAME__;
            $res = deploy_engine_website(__DOMAIN__, $html_content, $user);
            
            // Save to deployments table
            $hash = substr(md5($html_content . time()), 0, 10);
            
            try {
                $stmt = $db->pdo->prepare("INSERT INTO deployments (version_hash, html_content) VALUES (?, ?)");
                $stmt->execute([$hash, $html_content]);
            } catch (Exception $e) {
                // Table might not exist yet if database.install.php wasn't run again, let's create it inline
                $db->pdo->exec("CREATE TABLE IF NOT EXISTS deployments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    version_hash TEXT NOT NULL,
                    html_content TEXT NOT NULL,
                    status TEXT DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $stmt = $db->pdo->prepare("INSERT INTO deployments (version_hash, html_content) VALUES (?, ?)");
                $stmt->execute([$hash, $html_content]);
            }
            
            header("Location: {$admin_base}publish?success=deployed");
            exit;
        } elseif ($_POST['action'] === 'rollback') {
            $id = $_POST['deployment_id'] ?? 0;
            
            try {
                $stmt = $db->pdo->prepare("SELECT html_content FROM deployments WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    $html_content = $row['html_content'];
                    $user = __USERNAME__;
                    $res = deploy_engine_website(__DOMAIN__, $html_content, $user);
                    
                    // Add new rollback entry to continue the chain
                    $hash = substr(md5($html_content . time()), 0, 10);
                    $stmt = $db->pdo->prepare("INSERT INTO deployments (version_hash, html_content, status) VALUES (?, ?, ?)");
                    $stmt->execute([$hash, $html_content, 'rollback']);
                    
                    header("Location: {$admin_base}publish?success=rollback");
                    exit;
                }
            } catch (Exception $e) {
                // Ignore, table doesn't exist
            }
        }
    }
}

// Fetch deployments
$deployments = [];
try {
    $stmt = $db->pdo->prepare("SELECT * FROM deployments ORDER BY created_at DESC");
    $stmt->execute();
    $deployments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Deployments table may not exist yet
}

?>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body { background: #09090b !important; font-family: 'Inter', -apple-system, sans-serif; }
</style>

<?php @include_once "header.php"; ?>

<div class="grid-layout">
    <main class="overflow-x-hidden bg-[#09090b] md:p-8">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">Publish Store</h1>
                <p class="text-zinc-400 text-sm mt-1">Publish to <span class="text-violet-400 font-medium"><?php echo htmlspecialchars($store_domain); ?></span></p>
            </div>
            <a href="<?php echo $admin_base; ?>" class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Back to Dashboard
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl mb-8 flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-xl"></i>
                <span class="text-sm font-medium">Store successfully <?php echo htmlspecialchars($_GET['success']); ?>!</span>
            </div>
        <?php endif; ?>

        <!-- Publish Action -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 mb-8 relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h3 class="text-xl font-bold text-white">Deploy Updates</h3>
                    <p class="text-zinc-400 text-sm mt-2 max-w-xl">Compile your products, theme, and configuration into a high-performance static HTML site and publish it live.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="publish">
                    <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-8 py-4 rounded-xl font-bold transition-colors shadow-lg shadow-violet-500/20 flex items-center gap-2">
                        <i class="bi bi-cloud-arrow-up text-xl"></i> Publish Now
                    </button>
                </form>
            </div>
            <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-violet-600/5 blur-3xl"></div>
        </div>

        <!-- Version History -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-800 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Deployment History</h3>
                <span class="text-zinc-500 text-xs font-mono"><?php echo count($deployments); ?> records</span>
            </div>
            <div class="divide-y divide-zinc-800">
                <?php if (empty($deployments)): ?>
                    <div class="p-8 text-center text-zinc-500 text-sm">
                        No previous deployments found. Publish your store to create the first version!
                    </div>
                <?php else: ?>
                    <?php foreach ($deployments as $index => $dep): ?>
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-white/5 transition-colors">
                            <div class="flex items-center gap-4">
                                <?php if ($index === 0): ?>
                                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                                        <i class="bi bi-rocket-takeoff"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-500">
                                        <i class="bi bi-clock-history"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-white font-medium font-mono text-sm">v_<?php echo htmlspecialchars($dep['version_hash']); ?></span>
                                        <?php if ($index === 0): ?>
                                            <span class="bg-emerald-500/20 text-emerald-400 text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded">Current Live</span>
                                        <?php endif; ?>
                                        <?php if ($dep['status'] === 'rollback'): ?>
                                            <span class="bg-amber-500/20 text-amber-400 text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded">Rollback</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-zinc-500 text-xs mt-1">
                                        Deployed on <?php echo date('M j, Y g:i A', strtotime($dep['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($index !== 0): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to rollback to this version?');">
                                    <input type="hidden" name="action" value="rollback">
                                    <input type="hidden" name="deployment_id" value="<?php echo $dep['id']; ?>">
                                    <button type="submit" class="text-sm font-medium text-amber-400 hover:text-amber-300 bg-amber-400/10 hover:bg-amber-400/20 px-4 py-2 rounded-lg transition-colors border border-amber-400/20">
                                        <i class="bi bi-arrow-counterclockwise mr-1"></i> Rollback
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="text-sm font-medium text-zinc-600 bg-zinc-800 px-4 py-2 rounded-lg cursor-not-allowed">
                                    Active
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>
