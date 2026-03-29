    <?php 
        
    ?>
    <!-- AUTH SECTION -->
    <div id="auth-container" class="container center-content">
        <div class="auth-card">
            <img src="/assets/favicon.png">

            <?php if (defined("SYSTEM_ERROR")){?>
            
            <div style="color:white; border-radius:10px; padding:10px; background-color: #9b1818e8;">
                <p style="margin: 0; font-weight: bold;"><?php echo SYSTEM_ERROR; ?></p>
            </div>
            
            <?php }?>

            <h1>Embedded Webstores</h1>
            <p>
                A lightweight, plug-and-play e-commerce micro-service. 
            </p>
            
            <?php 
            if (isset($_SERVER['__DEMO__'])): 
                if ($_SERVER['__DEMO__'] == "active"): 
            ?>
            <div>
                <div class="auth-buttons">
                    <button class="btn btn-github" onclick="window.location='/demo.php'">
                        Demo Account
                    </button>
                </div>
            </div>
            <?php
                endif; 
            endif; 
            ?>
            <br><br>
        </div>
    </div>