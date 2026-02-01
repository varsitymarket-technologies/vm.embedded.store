<div id="dashboard-container" class="container">
        <?php @include_once "header.php"; ?> 

        <?php 
        # Save The Theme 
        if (isset($_POST['edthemes'])){
            $name = $_POST['edthemes'] ?? null; 
            $saved_path = dirname(dirname(__FILE__))."/sites/".__DOMAIN__."/theme"; 
            file_put_contents($saved_path,$name); 


            #Remove The Encoded File 
            unlink(dirname($saved_path)."/config.php"); 
            unlink(dirname($saved_path)."/encode.php"); 

            file_put_contents('debug',dirname($saved_path)."/encode.php"); 
        }

        $path = dirname(dirname(__FILE__)).'/themes/*';
        $directories = glob($path, GLOB_ONLYDIR);

        $theme_library = []; 
        $combo_theme = ''; 

        $preview_node = $_GET['preview'] ?? null; 
        $preview_image = ''; 
        foreach ($directories as $key => $value) {
            # code...
            $theme = [];
            $selected = ""; 
            $name = str_ireplace(dirname(dirname(__FILE__)).'/themes/','',$value);
            if ($preview_node == $name){
                $preview_image = '/themes/'.$name.'/poster.png';
                $selected = "selected";
            }  
            $theme['name'] = $name; 
            $theme['image'] = 'poster.png'; 
            $theme_library[] = $theme;
            $combo_theme .= '<option value="'.($name).'" '.$selected.'  onclick="window.location=\'?preview='.$name.'\'">'.strtoupper($name).'</option>';  
        }
        if ($preview_node == null){
            $preview_image = '/themes/'.$theme_library[0]['name'].'/poster.png';
        }
        ?>
    <main>

        
        <div>
            <div>
                <button onclick="window.location='/home/'"  style="width:auto; max-width: 20rem; margin: 2rem; background-color: #333; display: block;" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z"/>
                        <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
                    </svg>
                    Back To Home
                </button>    
            </div>
            <div class="card" style="margin:2rem;">
                <form action="" method="POST">
                <div class="input-group">
                    <label>Available Themes</label>
                    <select name="edthemes" id="edtheme">
                        <?php echo $combo_theme; ?>
                    </select>
                </div>
                <br><br>
                <h3>Theme Preview</h3>
                <div>
                    <img src="<?php echo $preview_image; ?>"
                    style="height:auto%; width: 100%; border: none;"
                    >
                </div>
                <br>

                <button type="submit" value="Submit" style="max-width: 20rem; margin: auto; display: block;" class="btn btn-primary">Save Theme</button>
                </form>
            </div>
        </div>
    </main>
</div>
