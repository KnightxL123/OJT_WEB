<?php
session_start();
require_once __DIR__ . '/../paths.php';

session_unset();  // Unset all session variables
session_destroy();  // Destroy the session

redirect_to('auth/login.php');  // Redirect to login page
?>
