<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Output JavaScript to clear localStorage and redirect
echo "<script>
    localStorage.removeItem('currentUser');
    window.location.href = 'index.html';
</script>";
exit();
?> 