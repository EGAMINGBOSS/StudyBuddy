<?php
// Include database connection and auth class
require_once "config/database.php";
require_once "backend/auth.php";

// Create Auth instance
$auth = new Auth();

// Reset password for user
$email = 'khenjiehbo@gmail.com';
$new_password = 'password123';

echo "Resetting password for user with email: $email\n";

$result = $auth->resetPassword($email, $new_password);

if ($result['success']) {
    echo "Password reset successful!\n";
    echo $result['message'] . "\n";
} else {
    echo "Password reset failed: " . $result['message'] . "\n";
}
?>