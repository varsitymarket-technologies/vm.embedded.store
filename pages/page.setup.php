<?php 
#   TITLE   : Page Setup    
#   DESC    : The setup page of the application. 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30
?>


<?php 
    #Recieve THe Data From The Forms 

    //$db->query($sql, [$id]);
        
    //echo "<script>window.location.href = window.location.href;</script>";
    //exit;
?>
    <!-- DASHBOARD SECTION (Hidden by default) -->
    <div id="dashboard-container" class="container">
        <?php @include_once "header.php"; ?> 

    <main>
        <div>
            
            <?php @include_once "modal.setup.php"; ?>

        </div>

    </main>

    </div>
