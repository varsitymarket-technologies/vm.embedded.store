<?php
@include_once(__DIR__ . "/config.php");

function construct_webpage($page){
    $webpage = dirname(__FILE__)."/data/pages/".$page.".page"; 
    if (!file_exists($webpage)){
        return "<!-- #/engine/body/encode/:BEGIN -->".PHP_EOL.file_get_contents(dirname(__FILE__)."/data/pages/404.page")."<!-- #/engine/body/encode/:END; -->".PHP_EOL ?? false; 
    }else{
        return "<!-- #/engine/body/encode/:BEGIN -->".PHP_EOL.file_get_contents($webpage)."<!-- #/engine/body/encode/:END; -->".PHP_EOL;
    }
}

function get_navbar(){
    $webpage = dirname(__FILE__)."/data/navbar.card"; 
    if (!file_exists($webpage)){
        return false; 
    }else{
        return file_get_contents($webpage);
    }
}

function terminate(){
    die(); 
}

function construct_structure($page){
    if (empty($page)) {
        $page = 'index';
    }

    $structure = ""; 
    $head = __DIR__."/data/head.php"; 
    if (file_exists($head) && is_file($head)){
        $structure .= file_get_contents($head);
    }

    $body = __DIR__."/data/body.php"; 
    if (file_exists($body) && is_file($body)){
        $body_structure = file_get_contents($body);
        $code = str_ireplace(
            ['# $app()->card(navbar);','# $app()->page(e);'],
            [get_navbar(),construct_webpage($page)],
            
            $body_structure
        );
        
        $structure .= $code; 
    }
    
    return $structure;
}

function analytics(){
    @include_once dirname(__FILE__)."/config.php"; 
    if (defined('__SYSTEM_ANALYTICS__')){
        echo __SYSTEM_ANALYTICS__; 
    }
}
function page($i,$d=false){
    echo change_page($i); 
}

function change_page($page,$id=false){
    if (isset($_GET['page'])){
        @$_GET['page'] = $page; 
    }else{
        if (routes(1) == "app"){
            $anchor = 3;
            return '/'.routes(1).'/'.routes(3).'/'.$anchor.'/'; 
        }else if (routes(1) == "websites"){
            $anchor = 4;  
        }else{
            $anchor = 1; 
        }
    }

    if (isset($anchor)){
        
        $routeParts = [];

        // Loop from 1 up to your $anchor limit
        for ($i = 1; $i < $anchor; $i++) {
            $routeParts[] = routes($i); // Calls routes(1), routes(2), routes(3)
        }
        $routeParts[$anchor] = $page; 
        
        // Join the pieces with '/' and append the final anchor at the end
        return '/' . implode('/', $routeParts) . '/' . $anchor . '/';
    }else{
        return '/?page='.$page; 
    }
    
    
}

function routes($section)
{
    $x = $_SERVER['REQUEST_URI'];
    // Strip query string from the URI to ensure clean segment matching
    $x = strtok($x, '?');
    $_xm = explode("/", $x);
    return $_xm[$section] ?? '';
}


?>
