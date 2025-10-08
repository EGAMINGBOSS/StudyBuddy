<?php
// Initialize the session
session_start();

// Check if user is in verification process
if(!isset($_SESSION["verification_user_id"]) || !isset($_SESSION["verification_email"])) {
    header("location: login.php");
    exit;
}

// Include required files
require_once "config/database.php";
require_once "backend/email_verification.php";

// Resend verification code
$emailVerification = new EmailVerification();
$code = $emailVerification->generateVerificationCode();
$emailVerification->storeVerificationCode($_SESSION["verification_user_id"], $_SESSION["verification_email"], $code);
$emailVerification->sendVerificationEmail($_SESSION["verification_email"], $_SESSION["verification_username"], $code);

// Store the new code in session for demo purposes
$_SESSION["demo_verification_code"] = $code;

// Redirect back to verification page
header("location: verify.php");
exit;
?>