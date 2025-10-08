<?php
// Initialize the session
session_start();

// Include database connection and auth class
require_once "config/database.php";
require_once "backend/auth.php";

// Create Auth instance
$auth = new Auth();

// Logout the user
$result = $auth->logout();

// Redirect to login page
header("location: login.php");
exit;
?>