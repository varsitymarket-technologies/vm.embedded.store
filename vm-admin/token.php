<?php 
session_start();

@include dirname(dirname(__FILE__))."/config.php"; 
echo "<script>window.location='/vm-admin/".slugify(__DOMAIN__)."/home'</script>"; 

//echo "<script>if (window.confirm('Redirecting Traffic')){ window.location='/vm-admin/".slugify(__DOMAIN__)."/home' }; </script>";
