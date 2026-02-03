    <?php 
        
    ?>
    <!-- AUTH SECTION -->
    <div id="auth-container" class="container center-content">
        <div class="auth-card">

            <?php if (defined("SYSTEM_ERROR")){?>
            
            <div style="color:white; border-radius:10px; padding:10px; background-color: #9b1818e8;">
                <p style="margin: 0; font-weight: bold;"><?php echo SYSTEM_ERROR; ?></p>
            </div>
            
            <?php }?>

            <h1><span class="text-purple">Embedded Webstore</span></h1>
            <p>Launch your free store. Monetize your passion.</p>
            
            <div class="auth-buttons">
                <button class="btn btn-github" onclick="window.location='/home/'">
                    <i class="fab fa-google"></i> Continue With Google
                </button>
            </div>
        </div>
    </div>