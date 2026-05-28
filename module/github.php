<?php
session_start();
@include_once dirname(dirname(__FILE__)) . "/config.php";
$client_id = $_SERVER['__GITHUB_APK_CLIENT__'];
$client_secret = $_SERVER['__GITHUB_APK_SECRET__'];
$code = $_GET['code'];

// Exchange code for Access Token
$ch = curl_init('https://github.com/login/oauth/access_token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $code
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['access_token'])) {
    $_SESSION['github_token'] = $response['access_token'];

    echo "<script>window.location.href='/vm-admin/" . __DOMAIN__ . "/settings?tab=deployment'</script>";
    exit();
    include_once "build.php";
    // You can now redirect to a dashboard
} else {
    die("Error retrieving token.");
}