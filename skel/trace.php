<?php
#   TITLE   : Routing File    
#   DESC    : Minimalist Shopify-style store router
#   VERSION : 2.0.0
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/07/15

@include_once __DIR__ . '/scripts.php';
@include_once __DIR__ . '/compiler.php';

// ─── Route map ────────────────────────────────────────────────────────────────
$segment1 = routes(1);
$segment2 = routes(2);

if (empty($segment1)) {
    $page = 'home';
}
elseif ($segment1 === 'product') {
    $GLOBALS['product_id'] = !empty($segment2) ? $segment2 : 1;
    $page = 'product';
}
elseif ($segment1 === 'account') {
    if (!empty($segment2) && in_array($segment2, ['orders', 'settings'])) {
        $page = $segment2;
    } else {
        $page = 'account';
    }
}
elseif (in_array($segment1, [
    'home', 'shop', 'collection', 'cart', 'checkout',
    'about', 'contact', 'policy', 'search', 'orders', 'account',
])) {
    $page = $segment1;
}
else {
    if (isset($_GET['page'])){
        $page = $_GET['page']; 
    }else{
        if (routes(1) == "websites"){
            if (!empty(routes(4))){
                $page = routes(4); 
            }else{
                $page = 'home'; 
            }
        } else if (routes(1) == "app"){
            if (!empty(routes(4))){
                $page = routes(4); 
            }else{
                $page = 'home'; 
            }
        }else{
            $page = '404';
        }
    }
}



@include_once __DIR__."/style.kit"; #GUI of the Website Not including The HTML
@include_once __DIR__."/script.kit"; #The Script That The Website Will execute 
@include_once __DIR__."/api.kit";    # How The System communicates with the API
@include_once __DIR__."/structure.kit"; #The processing Structure For The Base Applications
@include_once __DIR__."/template.kit";

$web = construct_structure($page);
// Pattern captures everything between '<!-- #!/engine/node/' and '-->'
$pattern = '/<!--\s*#!\/engine\/node\/\s*(.*?)\s*-->/s';

$result = preg_replace_callback($pattern, function ($matches) {
    // Clean up extra whitespace/newlines inside the PHP code
    $code = trim(preg_replace('/\s+/', ' ', $matches[1]));
    return "<?php {$code} ?>";
}, $web);
$web = $result; 
$tmp_file = dirname(__FILE__)."/exec.".hash("sha256","executable").".tmp.php"; 

file_put_contents($tmp_file,$web); 
include_once $tmp_file; 
unlink($tmp_file); 
