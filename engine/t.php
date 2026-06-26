<?php 

function deploy_engine_website(string $domain, string $site_contents = "", string $user )
    {
    @include_once "gateway.php";
    @include_once "encryption.php"; 
    #Create The Website Signature 

    $engine_tokens = $_SERVER['__ENGINE_TOKENS__'] ?? '12345678901234567890'; 
    $engine_secrets = $_SERVER['__ENGINE_SECRETS__'] ?? '';
    $engine_source = $_SERVER['__ENGINE_SOURCE__'] ?? ''; 



    $encryption = new encryption_services($engine_tokens); 
    $local_signature = $encryption->encryption_threading($user);
    $this->encryption_keys = ['threading' => $user,'silk'=>$user]; 
    $remote_signature = $encryption->encryption_threading($domain);
    echo $signature; 
    exit(); 

    #Website COnstruction 
    #Create The Engine Connection 
    $engine = new WebPublisherClient($engine_source,$engine_secrets); 

    #Publish THE Website 
    $e = $engine->publishWebsite($domain,['html'=>$site_contents]); 
    return $e; 
}

?>