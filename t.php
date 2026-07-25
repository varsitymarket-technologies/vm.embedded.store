<?php
@include_once "config.php";
@include_once "engine/gateway.php";

$domain = "harden.app.varsitymarket.co.za";
$path = dirname(__FILE__)."/sites/".$domain;
$e = vmpages_deploy($domain,$path);
die(); 
$server_ip = $_SERVER['__ENGINE_SOURCE__'];

print_r($domain . " " . $server_ip);

$engine = new emb_engine($_SERVER['__CLOUDFLARE_ZONEID__'], $_SERVER['__CLOUDFLARE_TOKEN__']);
// configure_subdomain creates an A record: <prefix>.<PARENT_DOMAIN> → $server_ip
$engine->configure_subdomain($domain, $server_ip ?: 'levidoc.github.io');

?>