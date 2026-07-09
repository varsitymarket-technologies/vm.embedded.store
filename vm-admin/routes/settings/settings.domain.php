<?php

#   TITLE   : Settings Domain Configuration   
#   DESC    : Custom domain provisioning system for pointing external domains to the platform engine via IPv4 A-records.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.0.0
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/07/09

// Core infrastructure IP where domains must point
$engine_ipv4 = '102.22.41.189'; 

// Simulated backend variables
$domain_input = $_POST['domain']['name'] ?? '';
$is_www_redirect = isset($_POST['domain']['www_redirect']) ? '1' : '0';
$ssl_mode = $_POST['domain']['ssl_mode'] ?? 'flexible';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="min-h-screen bg-black text-zinc-300 p-6 font-sans selection:bg-violet-500/30">
    <a href="?tab=general" class="inline-flex items-center gap-2 text-zinc-500 hover:text-violet-400 text-sm font-medium transition-colors mb-8">
        <i class="bi bi-arrow-left"></i> Back to Settings
    </a>

    <form method="POST" id="domain-setup-form" class="max-w-6xl mx-auto flex flex-col lg:flex-row gap-8">
        <input type="hidden" name="action" value="save_domain">

        <div class="flex-1">

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Domain Connection</h2>
                <div class="flex items-center justify-between relative">
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-0.5 bg-zinc-800 -z-10"></div>

                    <button type="button" class="step-indicator flex flex-col items-center gap-2" data-target="1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-violet-500 text-violet-400 font-bold transition-all" id="ind-1">1</div>
                        <span class="text-xs font-medium text-zinc-400">Target Domain</span>
                    </button>

                    <button type="button" class="step-indicator flex flex-col items-center gap-2" data-target="2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-zinc-800 text-zinc-600 font-bold transition-all" id="ind-2">2</div>
                        <span class="text-xs font-medium text-zinc-400">DNS Alignment</span>
                    </button>

                    <button type="button" class="step-indicator flex flex-col items-center gap-2" data-target="3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-zinc-800 text-zinc-600 font-bold transition-all" id="ind-3">3</div>
                        <span class="text-xs font-medium text-zinc-400">SSL & Routing</span>
                    </button>
                </div>
            </div>

            <div id="step-1" class="wizard-step bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-bold text-white mb-1">Custom Domain</h3>
                <p class="text-zinc-500 text-sm mb-6">Link your own branding by routing your registered domain name to our engine cluster.</p>

                <div class="space-y-6">
                    <div>
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Domain Name</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-600 text-sm font-mono">https://</span>
                            <input type="text" name="domain[name]" id="input-domain-name" 
                                value="<?= htmlspecialchars($domain_input) ?>" placeholder="yourstore.com"
                                class="w-full bg-black border border-zinc-700 rounded-lg pl-20 pr-4 py-3 text-white text-sm font-mono focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                        </div>
                        <p class="text-zinc-500 text-xs mt-2">Enter your root domain or standard subdomain (do not include paths or trailing slashes).</p>
                    </div>

                    <div class="bg-black border border-zinc-800/50 rounded-lg p-4">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="domain[www_redirect]" value="1" id="input-www-redirect"
                                class="w-4 h-4 mt-0.5 bg-black border-zinc-700 accent-violet-600 rounded"
                                <?= $is_www_redirect === '1' ? 'checked' : '' ?>>
                            <span class="text-sm select-none">
                                <span class="block text-zinc-300 font-medium group-hover:text-white transition-colors">Auto-forward WWW variant</span>
                                <span class="block text-zinc-500 text-xs mt-0.5">Enabling this seamlessly captures traffic sent to both <span class="font-mono">www.yourstore.com</span> and your root domain.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="button" class="btn-next bg-violet-600 hover:bg-violet-500 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors" data-next="2">
                        Configure DNS Records <i class="bi bi-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <div id="step-2" class="wizard-step hidden bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-bold text-white mb-1">DNS Zone Alignment</h3>
                <p class="text-zinc-500 text-sm mb-6">Log in to your DNS registrar account (e.g., GoDaddy, Namecheap, Cloudflare) and append the following parameters to your zone file.</p>

                <div class="space-y-4">
                    <div class="bg-black border border-zinc-800 rounded-lg p-4 relative overflow-hidden">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="grid grid-cols-3 gap-4 flex-1">
                                <div>
                                    <span class="block text-zinc-500 text-[10px] font-bold uppercase tracking-wider">Type</span>
                                    <span class="text-violet-400 font-mono text-sm font-bold">A</span>
                                </div>
                                <div>
                                    <span class="block text-zinc-500 text-[10px] font-bold uppercase tracking-wider">Host</span>
                                    <span class="text-white font-mono text-sm">@</span>
                                </div>
                                <div>
                                    <span class="block text-zinc-500 text-[10px] font-bold uppercase tracking-wider">Value (IPv4 Target)</span>
                                    <span class="text-white font-mono text-sm" id="dns-target-ip"><?= $engine_ipv4 ?></span>
                                </div>
                            </div>
                            <button type="button" onclick="copyText('dns-target-ip', this)" class="inline-flex items-center justify-center gap-1.5 bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-xs text-zinc-400 hover:text-white px-3 py-1.5 rounded transition-all">
                                <i class="bi bi-clipboard"></i> <span>Copy IP</span>
                            </button>
                        </div>
                    </div>

                    <div id="dns-cname-panel" class="bg-black border border-zinc-800 rounded-lg p-4 relative overflow-hidden transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="grid grid-cols-3 gap-4 flex-1">
                                <div>
                                    <span class="block text-zinc-500 text-[10px] font-bold uppercase tracking-wider">Type</span>
                                    <span class="text-violet-400 font-mono text-sm font-bold">CNAME</span>
                                </div>
                                <div>
                                    <span class="block text-zinc-500 text-[10px] font-bold uppercase tracking-wider">Host</span>
                                    <span class="text-white font-mono text-sm">www</span>
                                </div>
                                <div>
                                    <span class="block text-zinc-500 text-[10px] font-bold uppercase tracking-wider">Value</span>
                                    <span class="text-white font-mono text-sm" id="dns-target-cname">@</span>
                                </div>
                            </div>
                            <button type="button" onclick="copyText('dns-target-cname', this)" class="inline-flex items-center justify-center gap-1.5 bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-xs text-zinc-400 hover:text-white px-3 py-1.5 rounded transition-all">
                                <i class="bi bi-clipboard"></i> <span>Copy Host</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-zinc-800 pt-4 flex items-start gap-2 text-zinc-500 text-xs">
                    <i class="bi bi-info-circle text-violet-400 flex-shrink-0 mt-0.5"></i>
                    <span>DNS records can take anywhere from a few minutes to 24 hours to propagate internationally across authoritative resolvers.</span>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" class="btn-prev text-zinc-400 hover:text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors" data-prev="1">
                        <i class="bi bi-arrow-left mr-1"></i> Back
                    </button>
                    <button type="button" class="btn-next bg-violet-600 hover:bg-violet-500 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors" data-next="3">
                        Continue to Routing <i class="bi bi-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <div id="step-3" class="wizard-step hidden bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-bold text-white mb-1">Encryption Layer & Handshake</h3>
                <p class="text-zinc-500 text-sm mb-6">Select automated enforcement mechanisms for Edge SSL Certificates.</p>

                <div class="space-y-4 mb-8">
                    <div>
                        <label class="block text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">SSL Deployment Strategy</label>
                        <select name="domain[ssl_mode]" id="input-ssl-mode"
                            class="w-full bg-black border border-zinc-700 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-violet-500 transition-colors">
                            <option value="strict" <?= $ssl_mode === 'strict' ? 'selected' : '' ?>>Strict HTTPS Enforcement (Recommended)</option>
                            <option value="flexible" <?= $ssl_mode === 'flexible' ? 'selected' : '' ?>>Flexible Routing (HTTP & HTTPS proxy mapping)</option>
                        </select>
                    </div>

                    <div class="p-4 bg-zinc-950 border border-zinc-800 rounded-lg">
                        <div class="flex items-center gap-3 text-xs text-zinc-400">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span>Engine reverse proxy validation pipeline is listening for downstream connections.</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" class="btn-prev text-zinc-400 hover:text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors" data-prev="2">
                        <i class="bi bi-arrow-left mr-1"></i> Back
                    </button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-[0_0_15px_rgba(139,92,246,0.4)] transition-all">
                        Initialize Connection
                    </button>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/3">
            <div class="sticky top-6 bg-black border border-zinc-800 rounded-xl overflow-hidden shadow-[0_0_30px_rgba(0,0,0,0.5)]">
                <div class="bg-zinc-900 border-b border-zinc-800 px-4 py-3 flex items-center justify-between">
                    <div class="flex gap-1.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-zinc-700"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-zinc-700"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-zinc-700"></div>
                    </div>
                    <div class="bg-black/40 px-3 py-1 rounded text-[11px] font-mono text-zinc-400 flex items-center gap-1.5 w-48 overflow-hidden whitespace-nowrap text-ellipsis border border-zinc-800/30">
                        <i class="bi bi-lock-fill text-emerald-500 text-[10px]" id="preview-ssl-icon"></i>
                        <span id="preview-address-bar">yourstore.com</span>
                    </div>
                    <i class="bi bi-arrow-clockwise text-zinc-600 text-xs"></i>
                </div>

                <div class="p-6 space-y-6">
                    <div class="bg-zinc-900 rounded-lg p-5 border border-zinc-800/50">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-4">Topology Simulation</h4>
                        
                        <div class="space-y-4 font-mono text-xs relative">
                            <div class="absolute left-3 top-3 bottom-3 w-0.5 bg-dashed border-l border-zinc-800 -z-0"></div>

                            <div class="flex items-center gap-3 relative z-10">
                                <div class="w-6 h-6 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-400">
                                    <i class="bi bi-globe text-[11px]"></i>
                                </div>
                                <div>
                                    <span class="block text-zinc-400 text-[11px]">External Alias</span>
                                    <span class="text-white font-bold text-xs" id="node-custom-domain">yourstore.com</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 relative z-10">
                                <div class="w-6 h-6 rounded-full bg-violet-950 border border-violet-800 flex items-center justify-center text-violet-400 shadow-[0_0_8px_rgba(139,92,246,0.2)]">
                                    <i class="bi bi-hdd-network text-[11px]"></i>
                                </div>
                                <div>
                                    <span class="block text-zinc-500 text-[11px]">IPv4 Proxy Mapping</span>
                                    <span class="text-violet-400 font-bold text-xs"><?= $engine_ipv4 ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 relative z-10">
                                <div class="w-6 h-6 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-500">
                                    <i class="bi bi-cpu text-[11px]"></i>
                                </div>
                                <div>
                                    <span class="block text-zinc-500 text-[11px]">SaaS Engine Core</span>
                                    <span class="text-zinc-400 text-xs">VarsityMarket Node</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t border-dashed border-zinc-800 pt-4 flex flex-col gap-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Security Encapsulation:</span>
                            <span id="preview-ssl-mode" class="text-zinc-300 font-mono font-medium">STRICT</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Subdomain Redirection:</span>
                            <span id="preview-www-status" class="text-zinc-500 font-mono">DISABLED</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // 1. Wizard Layout Navigation Core Mechanics
    document.querySelectorAll('.btn-next, .btn-prev, .step-indicator').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetStep = e.currentTarget.dataset.next || e.currentTarget.dataset.prev || e.currentTarget.dataset.target;
            if (targetStep) goToStep(parseInt(targetStep));
        });
    });

    function goToStep(stepNumber) {
        document.querySelectorAll('.wizard-step').forEach(step => step.classList.add('hidden'));
        document.getElementById(`step-${stepNumber}`).classList.remove('hidden');

        document.querySelectorAll('.step-indicator').forEach((ind, index) => {
            const circle = document.getElementById(`ind-${index + 1}`);
            if (index + 1 === stepNumber) {
                circle.className = "w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-violet-500 text-violet-400 font-bold transition-all shadow-[0_0_10px_rgba(139,92,246,0.3)]";
            } else if (index + 1 < stepNumber) {
                circle.className = "w-8 h-8 rounded-full flex items-center justify-center border-2 bg-violet-600 border-violet-600 text-white font-bold transition-all";
                circle.innerHTML = '<i class="bi bi-check"></i>';
            } else {
                circle.className = "w-8 h-8 rounded-full flex items-center justify-center border-2 bg-black border-zinc-800 text-zinc-600 font-bold transition-all";
                circle.innerHTML = index + 1;
            }
        });
    }

    // 2. Real-Time Form Mutation Visual Synchronizer Engine
    function syncDomainPreview() {
        const rawInput = document.getElementById('input-domain-name').value.trim();
        const cleanDomain = rawInput ? rawInput.replace(/^(https?:\/\/)?(www\.)?/, '') : 'yourstore.com';
        
        const wwwRedirectChecked = document.getElementById('input-www-redirect').checked;
        const sslStrategySelection = document.getElementById('input-ssl-mode').value;

        // Sync visual textual labels
        document.getElementById('preview-address-bar').innerText = cleanDomain;
        document.getElementById('node-custom-domain').innerText = cleanDomain;
        
        // Sync secondary parameters inside proxy summary architecture components
        const wwwStatusNode = document.getElementById('preview-www-status');
        if (wwwRedirectChecked) {
            wwwStatusNode.innerText = "WWW → ROOT";
            wwwStatusNode.className = "text-violet-400 font-mono font-medium";
            document.getElementById('dns-cname-panel').style.opacity = "1";
        } else {
            wwwStatusNode.innerText = "DISABLED";
            wwwStatusNode.className = "text-zinc-500 font-mono";
            document.getElementById('dns-cname-panel').style.opacity = "0.3";
        }

        // Handle security representation states configuration variants
        const sslPreviewNode = document.getElementById('preview-ssl-mode');
        const lockIcon = document.getElementById('preview-ssl-icon');
        if (sslStrategySelection === 'strict') {
            sslPreviewNode.innerText = "STRICT HTTPS";
            sslPreviewNode.className = "text-emerald-400 font-mono font-bold";
            lockIcon.className = "bi bi-lock-fill text-emerald-500 text-[10px]";
        } else {
            sslPreviewNode.innerText = "FLEXIBLE PROXY";
            sslPreviewNode.className = "text-amber-400 font-mono font-medium";
            lockIcon.className = "bi bi-unlock-fill text-amber-500 text-[10px]";
        }
    }

    // Attach listeners to fire interface adjustments automatically upon configuration modification
    document.getElementById('input-domain-name').addEventListener('input', syncDomainPreview);
    document.getElementById('input-www-redirect').addEventListener('change', syncDomainPreview);
    document.getElementById('input-ssl-mode').addEventListener('change', syncDomainPreview);

    // 3. Native Clipboard Extension API Tool Utility
    function copyText(elementId, buttonEl) {
        const textToCopy = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(textToCopy).then(() => {
            const genericLabel = buttonEl.querySelector('span');
            const nativeIcon = buttonEl.querySelector('i');
            
            const originalText = genericLabel.innerText;
            genericLabel.innerText = "Copied!";
            nativeIcon.className = "bi bi-check2 text-emerald-400";
            
            setTimeout(() => {
                genericLabel.innerText = originalText;
                nativeIcon.className = "bi bi-clipboard";
            }, 1800);
        });
    }

    // Execute state sync engines on baseline initial execution passes
    goToStep(1);
    syncDomainPreview();
</script>