<?php
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the auth page or home page
header("Location: /auth");
exit();
?>