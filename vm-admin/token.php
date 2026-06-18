<?php 

#   TITLE   : Admin Token Redirect    
#   DESC    : This file is used to handle the redirection of users to the admin panel after they have been authenticated. It checks for the presence of a valid token and redirects accordingly. 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/01/30


session_start();

@include dirname(dirname(__FILE__))."/config.php"; 
echo "<script>window.location='/vm-admin/".slugify(__DOMAIN__)."/home'</script>"; 

//echo "<script>if (window.confirm('Redirecting Traffic')){ window.location='/vm-admin/".slugify(__DOMAIN__)."/home' }; </script>";
