<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to welcome page
if(isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("location: welcome.php");
    exit;
}

// If not logged in, redirect to login page
header("location: login.php");
exit;
?>