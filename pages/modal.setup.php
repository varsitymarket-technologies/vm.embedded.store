<style>
        :root {
            --bg-overlay:rgba(0, 0, 0, 0.9);
        }


        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-overlay);
            display: none; justify-content: center; align-items: center;
        }

        .modal-overlay.active { display: flex; }

        .modal-content {
            background: var(--card-bg); padding: 2rem; border-radius: 12px;
            width: 100%; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        /* Progress Indicator */
        .steps-indicator { display: flex; justify-content: space-between; margin: 2rem 4rem 2rem 4rem; }
        .step-dot { width: 30px; height: 30px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; }
        .step-dot.active { background: var(--purple-primary); color: white; }

        /* Form Steps */
        .form-step { display: none; }
        .form-step.active { display: block; }

        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 600; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }

        /* Buttons */
        .btn-group { display: flex; justify-content: space-between; margin-top: 2rem; }
        button { padding: 0.75rem 1.5rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .btn-next { background: var(--purple-primary); color: white; margin-left: auto; }
        .btn-prev { background: #e5e7eb; color: #374151; }
        .hidden { display: none; }

        .input-group {
            margin-bottom:1rem;
        }
</style>

<div class="modal-overlay active" id="modalOverlay">
    <div class="modal-content">
        <div class="steps-indicator">
            <div class="step-dot active" data-step="1"></div>
            <div class="step-dot" data-step="2"></div>
            <div class="step-dot" data-step="3"></div>
        </div>

        <form method="POST" action="" id="multiStepForm">
            <div class="form-step active" id="step1">
                <p class="subtext">Please Fill In Your Website Details </p> 
                <div class="input-group">
                    <label>Website Name</label>
                    <input name="wb_name" type="text" placeholder="" required>
                </div>
                <div class="input-group">
                    <label>Website Domain</label>
                    <input name="wb_domain" type="text" placeholder="example.co.za" required>
                </div>
            </div>

            <div class="form-step" id="step2">
                <p class="subtext">Please Fill In Your Billing Information</p> 
                <div class="input-group">
                    <label>Street Address</label>
                    <input name="bstreet" type="text" placeholder="">
                </div>
                <div class="input-group">
                    <label>Zip Code</label>
                    <input name="bzip" type="text" placeholder="">
                </div>
                <div class="input-group">
                    <label>Province</label>
                    <input name="bstate" type="text" placeholder="">
                </div>
                <div class="input-group">
                    <label>City</label>
                    <input name="bcity" type="text" placeholder="">
                </div>
                <div class="input-group">
                    <label>Country</label>
                    <input name="bcountry" type="text" placeholder="">
                </div>
            </div>

            <div class="form-step" id="step3">
                <p class="subtext">Please Fill In Your Payment Information</p>
                <div class="input-group">
                    <label>Select Bank</label>
                    <input name="account_provider" type="text" placeholder="">
                </div>

                <div class="input-group">
                    <label>Account Number</label>
                    <input name="account_number" type="text" placeholder="" required>
                </div>

                <div class="input-group">
                    <label>Account Type</label>
                    <input name="account_type" type="text" placeholder="">
                </div>

                <div class="input-group">
                    <label>Branch Code</label>
                    <input name="account_branch" type="text" placeholder="">
                </div>
            </div>

            <div class="btn-group">
                <button type="button" class="btn-prev hidden" id="prevBtn">Back</button>
                <button type="button" class="btn-next" id="nextBtn">Next</button>
            </div>
        </form>
    </div>
</div>


<script>
    const modal = document.getElementById('modalOverlay');
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const steps = document.querySelectorAll('.form-step');
    const dots = document.querySelectorAll('.step-dot');
    const forms = document.getElementById('multiStepForm');
    
    let currentStep = 0;

    function updateStep() {
        steps.forEach((step, index) => {
            step.classList.toggle('active', index === currentStep);
        });
        
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index <= currentStep);
        });

        // Button management
        prevBtn.classList.toggle('hidden', currentStep === 0);
        
        if (currentStep === steps.length - 1) {
            nextBtn.innerText = 'Launch Website';
            nextBtn.style.background = '#059669'; // Success green
        } else {
            nextBtn.innerText = 'Next';
            nextBtn.style.background = '#4f46e5';
        }
    }

    nextBtn.onclick = () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            updateStep();
        } else {

            forms.submit(); 
            alert('Website Created Successfully!');
            modal.classList.remove('active');
            // Logic to submit form data goes here
        }
    };

    prevBtn.onclick = () => {
        if (currentStep > 0) {
            currentStep--;
            updateStep();
        }
    };
</script>


