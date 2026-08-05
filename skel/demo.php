<?php 

error_reporting(E_ALL); 
ini_set('display_errors',1); 

die(); 

@include_once __DIR__."/style.kit"; #GUI of the Website Not including The HTML
@include_once __DIR__."/script.kit"; #The Script That The Website Will execute 
@include_once __DIR__."/api.kit";    # How The System communicates with the API
@include_once __DIR__."/structure.kit"; #The processing Structure For The Base Applications
@include_once __DIR__."/template.kit";
@include_once __DIR__."/engine.kit";

#die();  
#app()->create_shop_ui(api()->async_products());

?>