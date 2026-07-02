<?php
$admin_base = '/vm-admin/' . (__DOMAIN__ ?? '') . '/';
$site_url = "http://" . __DOMAIN__;

// Init Local Deployment DB
$db = initiate_web_database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'publish') {
            @include_once dirname(dirname(dirname(__FILE__))) . "/services/export.store.source.php";
            $html_content = export_application(__DOMAIN__, __WEBSITE_DOMAIN__);

            $user = __USERNAME__;
            $res = deploy_engine_website(__DOMAIN__, $html_content, $user);

            $hash = substr(md5($html_content . time()), 0, 10);

            try {
                $db->query("INSERT INTO deployments (version_hash, html_content) VALUES (?, ?)", [$hash, $html_content]);
            } catch (Exception $e) {
                $db->query("CREATE TABLE IF NOT EXISTS deployments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    version_hash TEXT NOT NULL,
                    html_content TEXT NOT NULL,
                    status TEXT DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $db->query("INSERT INTO deployments (version_hash, html_content) VALUES (?, ?)", [$hash, $html_content]);
            }

            header("Location: {$admin_base}publish?success=deployed");
            exit;
        } elseif ($_POST['action'] === 'rollback') {
            $id = $_POST['deployment_id'] ?? 0;

            try {
                $row = $db->query("SELECT html_content FROM deployments WHERE id = ? LIMIT 1", [$id]);

                if (!empty($row[0])) {
                    $html_content = $row[0]['html_content'];
                    $user = __USERNAME__;
                    $res = deploy_engine_website(__DOMAIN__, $html_content, $user);

                    $hash = substr(md5($html_content . time()), 0, 10);
                    $db->query("INSERT INTO deployments (version_hash, html_content, status) VALUES (?, ?, ?)", [$hash, $html_content, 'rollback']);

                    header("Location: {$admin_base}publish?success=rollback");
                    exit;
                }
            } catch (Exception $e) {
            }
        }
    }
}

$deployments = [];
try {
    $deployments = $db->query("SELECT * FROM deployments ORDER BY created_at DESC");
} catch (Exception $e) {
}

$verification_domain = engine_validate_domain_ownership(__DOMAIN__);
$domain_connected = engine_validate_domain(__DOMAIN__);

if ($domain_connected == true) {
    $domain_source = $site_url;
} else if ($verification_domain == true) {
    $domain_source = "http://" . get_domain() . "/pages/error.500.deployment.php";
} else {
    $domain_source = "http://" . get_domain() . "/pages/error.500.verification.php";
}

?>
<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <?php if (!isset($_SERVER['__ENGINE_SOURCE__'])): ?>
        <div style="max-width: 25rem; margin:10rem auto; padding: 2rem 1rem;">

        <div class="bg-[#1e2a27] border border-[#2b5c4b] rounded-xl p-4 flex items-center justify-between transition-all">
            <div class="flex items-center gap-3">
                <div class="text-sm">
                    <span class="font-semibold text-white">Embedded Engine not connected</span>
                    <br>
                    <span class="text-shopifySecondary ml-1">This embededd engine is not connected to the remote server. Your website cannot be published with this engine.</span>
                </div>
            </div>
        </div>

        </div>
    <?php else: ?>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <div class="flex-1 px-6 py-8 space-y-6" style="overflow-y: scroll;">

        <!-- Breadcrumb and Title Section -->
        <div
            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-2 border-b border-shopifyBorder">
            <div>
                <div class="text-xs text-shopifySecondary flex items-center gap-1 mb-1">
                    <span>Online Store</span> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" data-lucide="chevron-right" aria-hidden="true"
                        class="lucide lucide-chevron-right w-3 h-3">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg> <span>Embedded Engine</span>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Publish</h1>
            </div>
            <div class="flex gap-2">
                <button onclick="window.location.href='<?php echo $admin_base; ?>deploy'"
                    class="bg-[#2c2d30] hover:bg-[#36373a] border border-shopifyBorder text-white px-3 py-1.5 rounded-lg font-medium text-sm transition flex items-center gap-1.5">
                    <span>Deploy With Github</span>
                </button>
                <button onclick="document.getElementById('publishForm').submit();"
                    class="bg-[#7a1aab] hover:bg-shopifyGreenHover text-white px-4 py-1.5 rounded-lg font-medium text-sm transition flex items-center gap-1.5 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        data-lucide="upload-cloud" aria-hidden="true" class="lucide lucide-upload-cloud w-4 h-4">
                        <path d="M12 13v8"></path>
                        <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path>
                        <path d="m8 17 4-4 4 4"></path>
                    </svg>
                    <span>Publish Changes</span>
                </button>
                <form id="publishForm" method="POST" style="display: none;">
                    <input type="hidden" name="action" value="publish">
                </form>
            </div>
        </div>

        <!-- Simulation Banner -->
        <div id="publishProgress"
            class="hidden bg-[#1e2a27] border border-[#2b5c4b] rounded-xl p-4 flex items-center justify-between transition-all">
            <div class="flex items-center gap-3">
                <div class="w-4 h-4 border-2 border-shopifyGreen border-t-transparent rounded-full animate-spin"></div>
                <div class="text-sm">
                    <span class="font-semibold text-white">Publishing live modifications...</span>
                    <span class="text-shopifySecondary ml-1">CDN nodes are syncing cache files.</span>
                </div>
            </div>
        </div>

        <!-- Shopify Standard Layout Layout Grid -->
        <div class="grid grid-cols-1 gap-6">
            <?php if (($verification_domain !== true) && ($domain_connected !== true)): ?>
                <!-- Primary Active Theme Card -->
                <div class="bg-shopifyCard border border-shopifyBorder rounded-xl shadow-sm p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                        <div class="flex gap-4">
                            <!-- Theme Thumbnail Placeholder -->
                            <div
                                class="w-20 h-24 bg-[#2c2d30] border border-shopifyBorder rounded-lg flex flex-col justify-between p-2 text-[10px] text-shopifySecondary font-mono relative overflow-hidden">
                                <div class="w-full h-1 bg-[#7a1aab] absolute top-0 left-0"></div>
                                <span class="bg-shopifyBg/80 px-1 py-0.5 rounded text-[8px] text-center">Verify Domain</span>
                                <div class="space-y-1">
                                    <div class="h-1.5 bg-[#3a3b3e] rounded w-full"></div>
                                    <div class="h-1.5 bg-[#3a3b3e] rounded w-5/6"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-base text-white">Verify Domain</h3>
                                </div>
                                <p class="text-xs text-shopifySecondary mt-0.5">Insert the following text record to your domain register for confirmation.</p>
                                <p>Type: TXT</p>
                                <div
                                    class="text-xs text-shopifySecondary mt-3 font-mono bg-shopifyBg px-2 py-1 rounded inline-block border border-shopifyBorder">
                                    <?php echo hash("sha256", __DOMAIN__); ?>
                                </div>
                            </div>

                        </div>
                        <div class="w-full sm:w-auto flex sm:flex-col gap-2 justify-end">
                            <button id="openModal"
                                class="flex-1 sm:flex-none text-center bg-[#2c2d30] hover:bg-[#36373a] border border-shopifyBorder text-white px-3 py-1.5 rounded-lg font-medium text-xs transition">
                                Verify Domain
                            </button>
                        </div>


                    </div>
                </div>

            <?php endif; ?>

            <?php if (($domain_connected == false) && ($verification_domain == true)): ?>
                <!-- Primary Active Theme Card -->
                <div class="bg-shopifyCard border border-shopifyBorder rounded-xl shadow-sm p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                        <div class="flex gap-4">
                            <!-- Theme Thumbnail Placeholder -->
                            <div
                                class="w-20 h-24 bg-[#2c2d30] border border-shopifyBorder rounded-lg flex flex-col justify-between p-2 text-[10px] text-shopifySecondary font-mono relative overflow-hidden">
                                <div class="w-full h-1 bg-[#7a1aab] absolute top-0 left-0"></div>
                                <span class="bg-shopifyBg/80 px-1 py-0.5 rounded text-[8px] text-center">Connect Domain</span>
                                <div class="space-y-1">
                                    <div class="h-1.5 bg-[#3a3b3e] rounded w-full"></div>
                                    <div class="h-1.5 bg-[#3a3b3e] rounded w-5/6"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-base text-white">Connect Domain</h3>
                                </div>
                                <p class="text-xs text-shopifySecondary mt-0.5">Configure your domain to the embedded servers</p>
                                <div style="display: flex;">
                                    <p>A Records</p>
                                    <div
                                        class="text-xs text-shopifySecondary font-mono bg-shopifyBg px-2 py-1 rounded inline-block border border-shopifyBorder">
                                        <?php echo "84.12.34.23"; ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="w-full sm:w-auto flex sm:flex-col gap-2 justify-end">
                            <button id="openModal"
                                class="flex-1 sm:flex-none text-center bg-[#2c2d30] hover:bg-[#36373a] border border-shopifyBorder text-white px-3 py-1.5 rounded-lg font-medium text-xs transition">
                                Connect Domain
                            </button>
                        </div>


                    </div>
                </div>
            <?php elseif ($domain_connected == true): ?>
                <!-- Primary Active Theme Card -->
                <div class="bg-shopifyCard border border-shopifyBorder rounded-xl shadow-sm p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                        <div class="flex gap-4">
                            <!-- Theme Thumbnail Placeholder -->
                            <div
                                class="w-20 h-24 bg-[#2c2d30] border border-shopifyBorder rounded-lg flex flex-col justify-between p-2 text-[10px] text-shopifySecondary font-mono relative overflow-hidden">
                                <div class="w-full h-1 bg-[#0fb70f] absolute top-0 left-0"></div>
                                <span class="bg-shopifyBg/80 px-1 py-0.5 rounded text-[8px] text-center">Engine Connected</span>
                                <div class="space-y-1">
                                    <div class="h-1.5 bg-[#3a3b3e] rounded w-full"></div>
                                    <div class="h-1.5 bg-[#3a3b3e] rounded w-5/6"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-base text-white">All Set</h3>
                                </div>
                                <p class="text-xs text-shopifySecondary mt-0.5">You can now proceed to publish your website.</p>
                            </div>

                        </div>


                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-red-500/60"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500/60"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/60"></span>
                        </div>
                        <span class="text-zinc-500 text-xs font-mono"><?php echo $site_url; ?></span>
                    </div>

                </div>
                <iframe src="<?php echo $domain_source ?>" class="w-full border-none" style="height: 65vh;" frameborder="0"></iframe>
            </div>


            <!-- Shopify Version & Theme Library Section -->
            <div class="bg-shopifyCard border border-shopifyBorder rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-shopifyBorder bg-[#26272a]">
                    <h3 class="font-semibold text-white">Website Version History</h3>
                    <p class="text-xs text-shopifySecondary mt-0.5">View your previously published iterations. You can
                        roll back or preview older architecture snapshots anytime.</p>
                </div>

                <!-- Shopify Style Table Container -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-shopifyBg/40 border-b border-shopifyBorder text-shopifySecondary font-medium">
                                <th class="p-3.5 pl-5">Version &amp; Build</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5">Actions Log</th>
                                <th class="p-3.5">Author</th>
                                <th class="p-3.5 pr-5 text-right">Date Modified</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-shopifyBorder text-shopifyText">
                            <?php if (empty($deployments)): ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-shopifySecondary">No previous deployments found. Publish your store to create the first version!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deployments as $index => $dep): ?>
                                    <tr class="hover:bg-[#26272b]/50 group transition">
                                        <td class="p-4 pl-5 <?php echo $index === 0 ? 'font-bold text-white' : 'font-medium text-shopifySecondary'; ?> font-mono">v_<?php echo htmlspecialchars($dep['version_hash']); ?></td>
                                        <td class="p-4">
                                            <?php if ($index === 0): ?>
                                                <span class="text-[#86efac] bg-[#1b2b24] px-2 py-0.5 rounded font-medium border border-[#224834]">Current</span>
                                            <?php elseif ($dep['status'] === 'rollback'): ?>
                                                <span class="text-rose-400 bg-rose-950/30 px-2 py-0.5 rounded border border-rose-900/40">Rolled back</span>
                                            <?php else: ?>
                                                <span class="text-shopifySecondary bg-shopifyBg px-2 py-0.5 rounded border border-shopifyBorder">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($index !== 0): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to rollback to this version?');" class="inline">
                                                    <input type="hidden" name="action" value="rollback">
                                                    <input type="hidden" name="deployment_id" value="<?php echo $dep['id']; ?>">
                                                    <button type="submit" class="text-xs text-shopifySecondary underline hover:text-white transition">Restore this version</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-white font-medium">Currently deployed active view</span>
                                            <?php endif; ?>
                                            <div class="text-shopifySecondary text-[11px] font-mono mt-0.5">Deployment UID: <?php echo $dep['id']; ?></div>
                                        </td>
                                        <td class="p-4 text-shopifySecondary"><?php echo htmlspecialchars(__USERNAME__); ?></td>
                                        <td class="p-4 pr-5 text-right text-shopifySecondary"><?php echo date('M j, Y g:i A', strtotime($dep['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php endif; ?>
</div>

<style>
    /* Trigger Button */
    .open-btn {
        background-color: #2563eb;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
    }

    .open-btn:hover {
        background-color: #1d4ed8;
    }

    /* Modal Styles */
    dialog {
        border: none;
        border-radius: 8px;
        padding: 24px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }

    dialog::backdrop {
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    h2 {
        margin-top: 0;
        color: #111827;
        font-size: 1.25rem;
    }

    p {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    /* DNS Record Data Box */
    .dns-table {
        width: 100%;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 12px;
        margin: 20px 0;
        font-family: monospace;
        font-size: 0.9rem;
        border-collapse: collapse;
    }

    .dns-table td {
        padding: 6px 8px;
    }

    .label {
        color: #6b7280;
        font-weight: bold;
        width: 80px;
    }

    .value {
        color: #111827;
        word-break: break-all;
    }

    /* Action Buttons */
    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .btn-secondary {
        background: transparent;
        border: 1px solid #d1d5db;
        color: #374151;
    }

    .btn-secondary:hover {
        background: #f9fafb;
    }

    .btn-primary {
        background: #2563eb;
        border: none;
        color: white;
    }

    .btn-primary:hover {
        background: #1d4ed8;
    }
</style>


<!-- The Modal -->
<dialog id="dnsModal">
    <?php
    if (($domain_connected == false) && ($verification_domain == true)):
    ?>
        <h2>DNS Authentication Required</h2>
        <p>To verify ownership of your domain, please add the following TXT record to your DNS provider configurations (Cloudflare, GoDaddy, Namecheap, etc.).</p>

        <table class="dns-table">
            <tr>
                <td class="label">Type</td>
                <td class="value">A record</td>
            </tr>
            <tr>
                <td class="label">Name</td>
                <td class="value">@</td>
            </tr>
            <tr>
                <td class="label">Value</td>
                <td class="value"><?php echo $_SERVER['__SERVER_IP__'] ?? 'Unconfigured' ?></td>
            </tr>
            <tr>
                <td class="label">TTL</td>
                <td class="value">3600 (or Automatic)</td>
            </tr>
        </table>

        <p class="value" style="font-size: 0.85rem; color: #6b7280;">Note: DNS changes can take anywhere from a few minutes up to 24 hours to propagate globally.</p>

        <div class="modal-actions">
            <button class="btn btn-secondary" id="closeModal">Cancel</button>
            <button class="btn btn-primary" id="verifyBtn">Check DNS Record</button>
        </div>
    <?php elseif (($verification_domain !== true)): ?>

        <h2>DNS Authentication Required</h2>
        <p>To verify ownership of your domain, please add the following TXT record to your DNS provider configurations (Cloudflare, GoDaddy, Namecheap, etc.).</p>

        <table class="dns-table">
            <tr>
                <td class="label">Type</td>
                <td class="value">TXT</td>
            </tr>
            <tr>
                <td class="label">Name</td>
                <td class="value">@</td>
            </tr>
            <tr>
                <td class="label">Value</td>
                <td class="value"><?php echo "vm_".hash("sha256",__DOMAIN__); ?></td>
            </tr>
            <tr>
                <td class="label">TTL</td>
                <td class="value">3600 (or Automatic)</td>
            </tr>
        </table>

        <p class="value" style="font-size: 0.85rem; color: #6b7280;">Note: DNS changes can take anywhere from a few minutes up to 24 hours to propagate globally.</p>

        <div class="modal-actions">
            <button class="btn btn-secondary" id="closeModal">Cancel</button>
            <button class="btn btn-primary" id="verifyBtn">Check DNS Record</button>
        </div>

    <?php endif; ?>
</dialog>

<script>
    const modal = document.getElementById('dnsModal');
    const openBtn = document.getElementById('openModal');
    const closeBtn = document.getElementById('closeModal');
    const verifyBtn = document.getElementById('verifyBtn');

    // Open modal using native showModal() for backdrop support
    openBtn.addEventListener('click', () => {
        modal.showModal();
    });

    // Close modal
    closeBtn.addEventListener('click', () => {
        modal.close();
    });

    // Handle verification action
    verifyBtn.addEventListener('click', () => {
        verifyBtn.textContent = 'Verifying...';
        verifyBtn.disabled = true;

        // Simulating a backend API check
        setTimeout(() => {
            alert('DNS record not found yet. Please wait a few minutes and try again.');
            verifyBtn.textContent = 'Check DNS Record';
            verifyBtn.disabled = false;
        }, 1500);
    });

    // Close modal if user clicks outside of the dialog box
    modal.addEventListener('click', (e) => {
        const dialogDimensions = modal.getBoundingClientRect();
        if (
            e.clientX < dialogDimensions.left ||
            e.clientX > dialogDimensions.right ||
            e.clientY < dialogDimensions.top ||
            e.clientY > dialogDimensions.bottom
        ) {
            modal.close();
        }
    });
</script>