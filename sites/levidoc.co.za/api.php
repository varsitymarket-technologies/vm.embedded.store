<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

@include_once dirname(dirname(dirname(__FILE__))). "/scripts.php"; 
$db = __DB_MODULE__; 

$request = $_GET['state'] ?? ''; 

if($request == "products"){
    $data = $db->query("SELECT * FROM products"); 
    foreach($data as $key => $value){
        $data[$key]['price'] = (float) $value['price'];
        $data[$key]['id'] = (int) $value['id'];
    }
    echo json_encode($data);
}
?>