
<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>

<?php
// remove all session variables
session_unset();

// destroy the session
session_destroy();

echo "<script>window.alert('Logged Out Of The System')</script>"; 
print_r($_SESSION); 

echo "<script>window.location='/home/'</script>"
?>

</body>
</html>
