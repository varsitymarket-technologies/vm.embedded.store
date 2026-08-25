<?php

require_once dirname(__DIR__) . '/module/delivery_zones_sync.php';

$sync = new DeliveryZonesSync();
$result = $sync->run();

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

