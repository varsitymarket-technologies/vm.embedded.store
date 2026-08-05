<style>
    :root {
        --primary-accent: #4ade80;
        --primary-accent-hover: #22c55e;
        --primary-soft: rgba(74, 222, 128, 0.12);
        --sidebar-bg: #111318;
        --border-color: #2a2f3a;
        --text-main: #f3f4f6;
        --text-muted: #9ca3af;
        --card-bg: #151922;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(3, 6, 12, 0.84);
        backdrop-filter: blur(4px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .setup-container {
        background: var(--card-bg);
        width: 95%;
        max-width: 1040px;
        min-height: 680px;
        max-height: 90vh;
        border-radius: 20px;
        display: flex;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    /* Sidebar */
    .setup-sidebar {
        width: 300px;
        background: linear-gradient(180deg, #181c24 0%, var(--sidebar-bg) 100%);
        border-right: 1px solid var(--border-color);
        padding: 2rem 1.5rem;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        margin-bottom: 2.5rem;
    }

    .sidebar-header h2 {
        font-size: 1.35rem;
        font-weight: 750;
        color: var(--text-main);
        margin: 0;
    }

    .sidebar-header p {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-top: 0.5rem;
    }

    .nav-steps {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-step {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.95rem 1rem;
        border-radius: 14px;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
        transition: all 0.2s ease;
        cursor: default;
    }

    .nav-step.active {
        background: rgba(255, 255, 255, 0.04);
        color: var(--primary-accent);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22);
        border: 1px solid var(--border-color);
    }

    .nav-step.completed {
        color: var(--primary-accent);
    }

    .step-num {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 2px solid currentColor;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .nav-step.completed .step-num {
        background: var(--primary-accent);
        border-color: var(--primary-accent);
        color: white;
    }

    /* Content Area */
    .setup-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, #151922 0%, #11151c 100%);
    }

    .form-header {
        padding: 2rem 2.5rem 1.25rem 2.5rem;
        border-bottom: 1px solid var(--border-color);
        background: linear-gradient(180deg, #171b23 0%, #141820 100%);
    }

    .form-header h3 {
        font-size: 1.75rem;
        font-weight: 750;
        margin: 0;
        color: var(--text-main);
    }

    .form-body {
        flex: 1;
        padding: 2rem 2.5rem;
        overflow-y: auto;
        background: transparent;
    }

    .form-step-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .form-step-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Form Elements */
    .input-group {
        margin-bottom: 1.5rem;
    }

    label {
        display: block;
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 0.5rem;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 0.85rem 0.95rem;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: 1rem;
        transition: border-color 0.2s;
        box-sizing: border-box;
        background: #10141b;
        color: var(--text-main);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    input:focus {
        outline: none;
        border-color: var(--primary-accent);
        box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.12);
    }

    /* Domain Selector */
    .domain-choice {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .choice-card {
        border: 2px solid var(--border-color);
        border-radius: 16px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        background: #10141b;
    }

    .choice-card:hover {
        border-color: var(--primary-accent);
        transform: translateY(-1px);
    }

    .choice-card.selected {
        border-color: var(--primary-accent);
        background: var(--primary-soft);
        box-shadow: inset 0 0 0 1px rgba(74, 222, 128, 0.14);
    }

    .choice-card i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
    }

    .choice-card.selected i {
        color: var(--primary-accent);
    }

    .choice-card span {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
    }

    /* Footer */
    .form-footer {
        padding: 1.25rem 2.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #12161d;
    }

    button {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        font-size: 0.875rem;
    }

    .btn-next {
        background: var(--primary-accent);
        color: #08110b;
        box-shadow: 0 10px 22px rgba(74, 222, 128, 0.18);
    }

    .btn-next:hover {
        background: var(--primary-accent-hover);
        transform: translateY(-1px);
    }

    .btn-prev {
        background: #10141b;
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }

    .btn-prev:hover {
        background: #171c26;
    }

    .btn-prev.hidden {
        visibility: hidden;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .setup-container {
            flex-direction: column;
            height: auto;
            max-height: 90vh;
        }

        .setup-sidebar {
            width: 100%;
            padding: 1rem;
            border-right: none;
            border-bottom: 1px solid var(--border-color);
        }

        .nav-steps {
            display: flex;
            overflow-x: auto;
            gap: 0.5rem;
        }

        .nav-step {
            white-space: nowrap;
            padding: 0.5rem 1rem;
            margin-bottom: 0;
        }

        .sidebar-header {
            display: none;
        }

        .form-header,
        .form-body,
        .form-footer {
            padding: 1.25rem;
        }
    }

    .form-body p,
    .sidebar-header p {
        color: var(--text-muted) !important;
    }

    textarea {
        min-height: 108px;
        resize: vertical;
    }

    .form-step-content h4 {
        color: var(--text-main) !important;
    }

    /* Error banner */
    .setup-error-banner {
        display: none;
        position: fixed;
        top: 1.25rem;
        left: 50%;
        transform: translateX(-50%);
        background: #ef4444;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        z-index: 10000;
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
        max-width: 90vw;
        text-align: center;
        animation: fadeIn 0.3s ease;
    }
    .setup-error-banner.visible { display: block; }

    .form-step-content.active p,
    .form-step-content.active small,
    .form-step-content.active span {
        color: inherit;
    }

    .form-footer,
    .form-header,
    .setup-sidebar {
        backdrop-filter: blur(10px);
    }

    .setup-sidebar::-webkit-scrollbar,
    .form-body::-webkit-scrollbar {
        width: 10px;
    }

    .setup-sidebar::-webkit-scrollbar-thumb,
    .form-body::-webkit-scrollbar-thumb {
        background: #2a2f3a;
        border-radius: 999px;
        border: 2px solid transparent;
        background-clip: padding-box;
    }
</style>

<div id="setupErrorBanner" class="setup-error-banner"></div>
<div class="modal-overlay active" id="modalOverlay">
    <div class="setup-container">
        <!-- Sidebar -->
        <aside class="setup-sidebar">
            <div class="sidebar-header">
                <h2>Store Setup</h2>
                <p>Launch your store in minutes</p>
            </div>
            <ul class="nav-steps">
                <li class="nav-step active" data-step-nav="0">
                    <div class="step-num">1</div>
                    <span>Store identity</span>
                </li>
                <li class="nav-step" data-step-nav="1">
                    <div class="step-num">2</div>
                    <span>Domain setup</span>
                </li>
                <li class="nav-step" data-step-nav="2">
                    <div class="step-num">3</div>
                    <span>Business profile</span>
                </li>
                <li class="nav-step" data-step-nav="3">
                    <div class="step-num">4</div>
                    <span>Launch</span>
                </li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="setup-content">
            <form method="POST" action="" id="setupForm" onsubmit="return handleFinalSubmit(event)">
                <div class="form-header">
                    <h3 id="stepTitle">Store Identity</h3>
                </div>

                <div class="form-body">
                    <!-- Step 1: Identity -->
                    <div class="form-step-content active" id="step0">
                        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">Give your store a name and the contact details customers will see.</p>
                        <div class="input-group">
                            <label>Website Name</label>
                            <input name="wb_name" type="text" placeholder="e.g. My Awesome Boutique" required>
                        </div>
                        <div class="input-group">
                            <label>Store Email (Optional)</label>
                            <input name="wb_email" type="email" placeholder="e.g. info@myawesomeboutique.com">
                        </div>

                        <div class="input-group">
                            <label>Store Contact (Optional)</label>
                            <input name="wb_contact" type="text" placeholder="e.g. 081 234 5678">
                        </div>
                    </div>

                    <!-- Step 2: Domain -->
                    <div class="form-step-content" id="step1">
                        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">Choose how customers will reach your storefront.</p>

                        <input type="hidden" name="domain_type" id="domainType" value="subdomain">
                        <div class="domain-choice">
                            <?php if (isset($_SERVER['PARENT_DOMAIN'])): ?>
                                <div class="choice-card selected" id="choiceSubdomain" onclick="setDomainType('subdomain')">
                                    <i class="fas fa-magic"></i>
                                    <span>Free subdomain</span>
                                    <small
                                        style="font-size: 0.75rem; color: var(--text-muted)">*.<?php echo $_SERVER['PARENT_DOMAIN']; ?></small>
                                </div>
                            <?php endif; ?>
                            <div class="choice-card <?php echo !isset($_SERVER['PARENT_DOMAIN']) ? 'selected' : ''; ?>"
                                id="choiceCustom" onclick="setDomainType('custom')">
                                <i class="fas fa-globe"></i>
                                    <span>Own domain</span>
                                    <small style="font-size: 0.75rem; color: var(--text-muted)">e.g. yourstore.com</small>
                                </div>
                            </div>

                        <div class="input-group" id="subdomainWrap" <?php echo !isset($_SERVER['PARENT_DOMAIN']) ? 'style="display:none"' : ''; ?>>
                            <label>Choose your subdomain</label>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input name="subdomain_prefix" type="text" placeholder="mystore"
                                    style="text-align: right;">
                                <span
                                    style="font-weight: 600; color: var(--text-muted)">.<?php echo $_SERVER['PARENT_DOMAIN'] ?? ''; ?></span>
                            </div>
                        </div>

                        <div class="input-group" id="customDomainWrap" <?php echo isset($_SERVER['PARENT_DOMAIN']) ? 'style="display:none"' : ''; ?>>
                            <label>Enter your custom domain</label>
                            <input name="wb_domain" type="text" placeholder="example.com">
                        </div>
                    </div>

                    <!-- Step 3: Business -->
                    <div class="form-step-content" id="step2">
                        <div class="input-group">
                            <label>Industry</label>
                            <select name="wb_industry">
                                <option value="retail">Retail</option>
                                <option value="services">Services</option>
                                <option value="fnb">Food & Beverage</option>
                                <option value="tech">Technology</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Store Description</label>
                            <textarea name="wb_desc" rows="3"
                                placeholder="Briefly describe what you sell..."></textarea>
                        </div>
                        <div class="input-group">
                            <label>City / Location</label>
                            <input name="bcity" type="text" placeholder="e.g. Cape Town">
                        </div>
                    </div>

                    <!-- Step 4: Launch -->
                    <div class="form-step-content" id="step3">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; color: var(--primary-accent); margin-bottom: 1rem;">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <h4
                                style="font-size: 1.25rem; font-weight: 750; margin-bottom: 0.5rem; color: var(--text-main) !important;">
                                Almost There!</h4>
                            <p style="color: var(--text-muted)">Your store is ready to be deployed. Click finish to
                                launch your online presence.</p>
                        </div>

                        <!-- Demo Data Toggle -->
                        <div
                            style="margin-top: 1rem; border: 1px solid var(--border-color); border-radius: 16px; padding: 1.25rem; background: #10141b;">
                            <label
                                style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; margin: 0;">
                                <input type="checkbox" name="load_demo_data" value="1"
                                    style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary-accent); flex-shrink: 0; cursor: pointer;">
                                <div>
                                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">Load sample
                                        data</span>
                                    <p
                                        style="font-size: 0.8rem; color: var(--text-muted) !important; margin-top: 4px; line-height: 1.5;">
                                        Populate your store with demo categories, products, and orders so you can
                                        explore all features right away. You can remove them later.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div
                            style="margin-top: 1rem; border: 1px solid var(--border-color); border-radius: 16px; padding: 0 1.25rem; background: #10141b;">
                            <label
                                style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; margin: 0;">
                                <input type="checkbox" name="promotion_data" value="1"
                                    style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary-accent); flex-shrink: 0; cursor: pointer;">
                                <div>
                                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">Enhance user experience</span>
                                    <p style="font-size: 0.8rem; color: var(--text-muted) !important; line-height: 1.5;">
                                        We may also send you occasional tips and updates to help you get the most out of
                                        your store. You can opt out at any time.
                                    </p>
                                </div>
                            </label>
                        </div>

                    </div>
                </div>

                <div class="form-footer">
                    <button type="button" class="btn-prev hidden" id="prevBtn" onclick="navigateStep(-1)">Back</button>
                    <button type="button" class="btn-next" id="nextBtn" onclick="navigateStep(1)">Next Step</button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
    let currentStep = 0;
    const totalSteps = 4;
    const stepTitles = ["Store Identity", "Domain Setup", "Business Profile", "Launch Your Store"];

    function navigateStep(dir) {
        if (dir === 1 && !validateCurrentStep()) return;

        // If on the last step and clicking Next, submit the form
        if (currentStep === totalSteps - 1 && dir === 1) {
            document.getElementById('setupForm').submit();
            return;
        }

        const nextStep = currentStep + dir;
        if (nextStep < 0 || nextStep >= totalSteps) return;

        // Hide current
        document.getElementById(`step${currentStep}`).classList.remove('active');
        document.querySelector(`[data-step-nav="${currentStep}"]`).classList.remove('active');
        if (dir === 1) document.querySelector(`[data-step-nav="${currentStep}"]`).classList.add('completed');

        // Show next
        currentStep = nextStep;
        document.getElementById(`step${currentStep}`).classList.add('active');
        document.querySelector(`[data-step-nav="${currentStep}"]`).classList.add('active');

        // Update UI
        document.getElementById('stepTitle').innerText = stepTitles[currentStep];
        document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 0);

        const nextBtn = document.getElementById('nextBtn');
        if (currentStep === totalSteps - 1) {
            nextBtn.innerText = 'Launch Store';
            nextBtn.style.background = '#10b981';
        } else {
            nextBtn.innerText = 'Next Step';
            nextBtn.style.background = '#6366f1';
        }
    }

    function validateCurrentStep() {
        const activeForm = document.getElementById(`step${currentStep}`);
        const inputs = activeForm.querySelectorAll('input[required], select[required]');
        let valid = true;
        inputs.forEach(input => {
            if (!input.value) {
                input.style.borderColor = '#ef4444';
                valid = false;
            } else {
                input.style.borderColor = '#e5e7eb';
            }
        });
        return valid;
    }

    function setDomainType(type) {
        document.getElementById('domainType').value = type;
        document.getElementById('choiceSubdomain')?.classList.toggle('selected', type === 'subdomain');
        document.getElementById('choiceCustom').classList.toggle('selected', type === 'custom');

        document.getElementById('subdomainWrap').style.display = type === 'subdomain' ? 'block' : 'none';
        document.getElementById('customDomainWrap').style.display = type === 'custom' ? 'block' : 'none';
    }

    function handleFinalSubmit(e) {
        // If we are on the last step, allow submission
        if (currentStep === totalSteps - 1) {
            return true;
        }
        // Otherwise, move to next step
        e.preventDefault();
        navigateStep(1);
        return false;
    }

    // Show domain-taken error from server redirect
    (function () {
        const err = sessionStorage.getItem('setup_error');
        if (err) {
            sessionStorage.removeItem('setup_error');
            const banner = document.getElementById('setupErrorBanner');
            banner.textContent = err;
            banner.classList.add('visible');
            // Jump straight to the Domain Setup step so the user can correct it
            // Trigger navigation to step 1 (domain step)
            setTimeout(function () {
                while (currentStep < 1) navigateStep(1);
            }, 100);
            // Auto-dismiss after 8 seconds
            setTimeout(function () {
                banner.classList.remove('visible');
            }, 8000);
        }
    })();
</script>
