<?php 

$domain = "https://micro-web.levidoc.com"; 
$website = "yello"; 

?>
<div class="iframe-container">
  <iframe 
    src="<?php echo $domain ; ?>/app/<?php echo  $website ; ?>/" 
    title="Embedded Content" 
    width="100%" 
    height="100%" 
    style="border:0;" 
    loading="lazy" 
    allowfullscreen 
    sandbox="allow-scripts allow-same-origin allow-forms">
  </iframe>
</div>

