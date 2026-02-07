<?php 

function export_application($website,$domain){ 
  $website_hash = hash("sha256",$website); 
  $e = '<div class="iframe-container">
    <iframe 
      src="'.$domain.'/app/'.$website_hash .'" 
      title="Micro Webstore Services" 
      width="100%" 
      height="100%" 
      style="border:0;" 
      loading="lazy" 
      allowfullscreen 
      sandbox="allow-scripts allow-same-origin allow-forms">
    </iframe>
  </div>'; 
  return $e; 
}
?>

