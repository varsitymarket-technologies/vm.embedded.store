<?php 
#   TITLE   : Micro API    
#   DESC    : This will act as the websites micro api services.  
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/02/01


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

@include_once dirname(dirname(dirname(__FILE__))). "/scripts.php"; 
$db = __DB_MODULE__;  
$db->override_connection(dirname(__FILE__).'/storage.data'); 

$request = $_GET['state'] ?? ''; 

if($request == "products"){
    $data = $db->query("SELECT * FROM products"); 
    foreach($data as $key => $value){
        $data[$key]['price'] = (float) $value['price'];
        $data[$key]['id'] = (int) $value['id'];
    }
    echo json_encode($data);
}

if ($request == "categories"){
    $data = $db->query("SELECT * FROM categories"); 
    foreach($data as $key => $value){
        //$data[$key]['price'] = (float) $value['price'];
        $data[$key]['id'] = (int) $value['id'];
    }
    echo json_encode($data);
}
?>