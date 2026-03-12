<?php 

#   TITLE   : APK Posts   
#   DESC    : Receive Insights about the rescource consumption.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/03/08

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
@include dirname(dirname(__FILE__))."/config.php"; 
$rescources = []; 
$rescources['name'] = $_SESSION['__APK_NAME__'];
$rescources['description'] =  $_SESSION['__APK_DESC__'];
$rescources['node'] = ""; 
$rescources['server'] =  $_SESSION['__APK_SERVER__']; 
$rescources['host'] =  $_SESSION['__APK_HOST__']; 
$rescources['hostname'] =  $_SESSION['__APK_HOSTNAME__']; 
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