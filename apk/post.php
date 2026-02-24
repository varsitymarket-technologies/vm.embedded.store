<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$rescources = []; 
$rescources['name'] = "Embedded Free Stall";
$rescources['description'] = "High-performance dedicated hosting solution with 24/7 support.";
$rescources['node'] = ""; 
$rescources['server'] = "ZA-EMB-FREE"; 
$rescources['host'] = "http://beta.embedded.varsitymarket.tech/"; 
$rescources['hostname'] = "beta.embedded.varsitymarket.tech"; 
$rescources['hosting'] = 'shared'; 
$rescources['status'] = 'runing';
//$rescources['preview'] = 'http://beta.embedded.varsitymarket.tech/app/2562c0a94aa05716d93dd5f4839d14a6ee19045abfb09c5f187ecdad41b06d81/'; 
$rescources['rescources'] = [
            "Storage"=>[
                "cap"=>"5",
                "usage"=>"1.6",
                "units"=>"GB",
                "description"=>"NVMe High-Speed Storage"
            ],"Usage"=>[
                "cap"=>"5000",
                "usage"=>"100",
                "units"=>"Views",
                "description"=>"Standard Monthly Traffic Allowance"
            ]
        ];

$rescources['features'] = [
            "Auto-Backups"=>[
                "info"=>"Daily at 02:00 AM"
            ]
        ];
        

echo json_encode($rescources,JSON_PRETTY_PRINT);  
?>