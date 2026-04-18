<style>
    :root {
        --primary-accent: #6366f1;
        --primary-accent-hover: #4f46e5;
        --sidebar-bg: #f9fafb;
        --border-color: #e5e7eb;
        --text-main: #111827;
        --text-muted: #6b7280;
        --card-bg: #555555ff;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
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
        max-width: 900px;
        height: 600px;
        border-radius: 16px;
        display: flex;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    /* Sidebar */
    .setup-sidebar {
        width: 280px;
        background: var(--sidebar-bg);
        border-right: 1px solid var(--border-color);
        padding: 2.5rem 1.5rem;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        margin-bottom: 2.5rem;
    }

    .sidebar-header h2 {
        font-size: 1.25rem;
        font-weight: 800;
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
        padding: 0.875rem 1rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
        transition: all 0.2s ease;
        cursor: default;
    }

    .nav-step.active {
        background: white;
        color: var(--primary-accent);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .nav-step.completed {
        color: #10b981;
    }

    .step-num {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid currentColor;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .nav-step.completed .step-num {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }

    /* Content Area */
    .setup-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: white;
    }

    .form-header {
        padding: 2.5rem 3rem 1.5rem 3rem;
        border-bottom: 1px solid var(--border-color);
    }

    .form-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-main);
    }

    .form-body {
        flex: 1;
        padding: 2rem 3rem;
        overflow-y: auto;
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
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 0.5rem;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    input:focus {
        outline: none;
        border-color: var(--primary-accent);
        ring: 2px solid rgba(99, 102, 241, 0.2);
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
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        background: #555555ff;
    }

    .choice-card:hover {
        border-color: var(--primary-accent);
    }

    .choice-card.selected {
        border-color: var(--primary-accent);
        background: rgba(99, 102, 241, 0.05);
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
        padding: 1.5rem 3rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--sidebar-bg);
    }

    button {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        font-size: 0.875rem;
    }

    .btn-next {
        background: var(--primary-accent);
        color: white;
    }

    .btn-next:hover {
        background: var(--primary-accent-hover);
    }

    .btn-prev {
        background: white;
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }

    .btn-prev:hover {
        background: var(--sidebar-bg);
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
            padding: 1.5rem;
        }
    }

    h3,
    h2,
    p {
        color: #fff !important;
    }

    textarea {
        background-color: black;
        color: white;
    }

    .setup-content,
    .form-header,
    .form-body,
    .setup-sidebar,
    .form-footer {
        background: #1a1a1a;
        border: 1px solid #1a1a1a;
    }
</style>

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
                    <span>Store Identity</span>
                </li>
                <li class="nav-step" data-step-nav="1">
                    <div class="step-num">2</div>
                    <span>Domain Setup</span>
                </li>
                <li class="nav-step" data-step-nav="2">
                    <div class="step-num">3</div>
                    <span>Business Profile</span>
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
                        <div class="input-group">
                            <label>Website Name</label>
                            <input name="wb_name" type="text" placeholder="e.g. My Awesome Boutique" required>
                        </div>
                        <div class="input-group">
                            <label>Store Subtitle (Optional)</label>
                            <input name="wb_subtitle" type="text" placeholder="e.g. Premium Fashion & Accessories">
                        </div>
                    </div>

                    <!-- Step 2: Domain -->
                    <div class="form-step-content" id="step1">
                        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">How would you
                            like customers to find your store?</p>

                        <input type="hidden" name="domain_type" id="domainType" value="subdomain">
                        <div class="domain-choice">
                            <?php if (isset($_SERVER['PARENT_DOMAIN'])): ?>
                                <div class="choice-card selected" id="choiceSubdomain" onclick="setDomainType('subdomain')">
                                    <i class="fas fa-magic"></i>
                                    <span>Free Subdomain</span>
                                    <small
                                        style="font-size: 0.75rem; color: var(--text-muted)">*.<?php echo $_SERVER['PARENT_DOMAIN']; ?></small>
                                </div>
                            <?php endif; ?>
                            <div class="choice-card <?php echo !isset($_SERVER['PARENT_DOMAIN']) ? 'selected' : ''; ?>"
                                id="choiceCustom" onclick="setDomainType('custom')">
                                <i class="fas fa-globe"></i>
                                <span>Own Domain</span>
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
                        <div style="text-align: center; padding: 2rem 0;">
                            <div style="font-size: 3rem; color: var(--primary-accent); margin-bottom: 1rem;">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <h4 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Almost There!</h4>
                            <p style="color: var(--text-muted)">Your store is ready to be deployed. Click finish to
                                launch your online presence.</p>
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
</script>